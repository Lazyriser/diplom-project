<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\JobService;
use App\Models\Job;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class JobController extends Controller
{
    protected $jobService;

    public function __construct(JobService $jobService)
    {
        $this->jobService = $jobService;
    }

    // Метод для получения списка вакансий
    public function index(Request $request)
    {
        // Получение параметров пагинации и региона из запроса
        $page = $request->input('page', 0);
        $perPage = $request->input('per_page', 20);
        $region = $request->input('region', 1);

        // Получение данных о вакансиях из сервиса
        $vacanciesData = $this->jobService->getVacancies('php', $region, $page, $perPage);

        // Проверка на наличие ошибки в данных
        if (isset($vacanciesData['error'])) {
            return response()->json(['error' => $vacanciesData['error']], 500);
        }

        // Проверка существования и типа данных
        if (isset($vacanciesData['items']) && is_array($vacanciesData['items'])) {
            foreach ($vacanciesData['items'] as $vacancy) {
                // Получение полных данных по вакансии
                $fullVacancyDataResult = $this->jobService->getFullVacancyData($vacancy['id']);
                $fullVacancyData = $fullVacancyDataResult['data'];

                // Получение ключевых навыков
                $key_skills = isset($fullVacancyData['key_skills'])
                    ? implode(', ', array_column($fullVacancyData['key_skills'], 'name'))
                    : null;

                try {
                    // Обновление или создание записи вакансии в базе данных
                    Job::updateOrCreate(
                        ['api_id' => $vacancy['id']],
                        [
                            'title' => $vacancy['name'] ?? null,
                            'description' => $fullVacancyData['description'] ?? '',
                            'region_id' => $vacancy['area']['id'] ?? $region,
                            'salary_from' => $vacancy['salary']['from'] ?? null,
                            'salary_to' => $vacancy['salary']['to'] ?? null,
                            'currency' => $vacancy['salary']['currency'] ?? null,
                            'company_name' => $vacancy['employer']['name'] ?? null,
                            'schedule' => $vacancy['schedule']['name'] ?? null,
                            'key_skills' => $key_skills,
                            'address' => $vacancy['address']['raw'] ?? null,
                            'experience' => $vacancy['experience']['name'] ?? null,
                            'employment_type' => $vacancy['employment']['name'] ?? null,
                            'is_updated' => true, // Устанавливаем флаг обновления
                        ]
                    );
                } catch (\Exception $e) {
                    // Обработка в случае ошибки
                    continue;
                }
            }
        }

        // Получаем свежие данные из базы данных
        $jobs = Job::where('region_id', $region)
            ->where('deleted_at', null)
            ->paginate($perPage);

        return response()->json($jobs);
    }

    // Метод для получения списка регионов
    public function getRegions()
    {
        $areas = $this->jobService->getAreas();
        return response()->json($areas);
    }

    // Метод для создания новой вакансии
    public function store(Request $request)
    {
        // Валидация данных
        $data = $request->validate([
            'api_id' => 'sometimes|string|unique:vacancies,api_id,' . $request->input('id') . ',id',
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'region_id' => 'required|exists:regions,id',
            'company_name' => 'nullable|string|max:255',
            'salary_from' => 'nullable|numeric',
            'salary_to' => 'nullable|numeric|gte:salary_from',
            'currency' => 'nullable|string|max:3',
            'employment_type' => 'nullable|string',
            'schedule' => 'nullable|string|max:255',
            'key_skills' => 'nullable|string',
            'experience' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:255',
        ]);

        // Логирование данных
        Log::info("Данные для создания вакансии: ", $data);

        try {
            // Создание новой вакансии через сервис
            $job = $this->jobService->createJob($data);

            Log::info("Вакансия успешно создана: ", ['job' => $job]);

            // Перенаправление на страницу вакансий с сообщением об успехе
            return redirect()->route('jobs.index')->with('success', 'Вакансия успешно создана!');
        } catch (\Exception $e) {
            Log::error("Ошибка при сохранении вакансии: " . $e->getMessage());

            // Перенаправление обратно на страницу создания вакансии с сообщением об ошибке
            return redirect()->route('jobs.create')->withErrors(['error' => 'Не удалось сохранить вакансию.']);
        }
    }

    // Метод для отображения конкретной вакансии
    public function show($id)
    {
        $job = $this->jobService->getJobById($id);

        if (!$job) {
            return response()->json(['message' => 'Вакансия не найдена'], 404);
        }

        return Inertia::render('JobView', [
            'job' => $job,
        ]);
    }

    // Метод для редактирования вакансии
    public function edit($apiId)
    {
        // Получаем вакансию по api_id
        $job = $this->jobService->getJobById($apiId);
        $regions = $this->jobService->getRegions();

        if (!$job) {
            // Если вакансия не найдена, возвращаем 404
            return response()->json(['message' => 'Вакансия не найдена'], 404);
        }

        // Возвращаем вьюху для редактирования вакансии с данными
        return Inertia::render('EditJob', [
            'job' => $job,
            'regions' => $regions, // Передаем регионы во вьюху
        ]);
    }

    // Метод для обновления вакансии
    public function update(Request $request, $apiId)
    {
        \Log::info('api_id передан в запросе: ' . $apiId);
        \Log::info('Обновление вакансии с api_id: ' . $apiId, ['request_data' => $request->all()]);

        // Валидация данных
        try {
            // Получаем вакансию для использования её id в уникальности
            $job = $this->jobService->getJobById($apiId);
            if (!$job) {
                \Log::error("Вакансия не найдена с api_id: {$apiId}");
                return response()->json(['error' => 'Вакансия не найдена'], 404);
            }

            $data = $request->validate([
                'api_id' => 'sometimes|string|unique:vacancies,api_id,' . $job->id,
                'title' => 'required|string|max:255',
                'description' => 'required|string',
                'region_id' => 'required|exists:regions,id',
                'company_name' => 'nullable|string|max:255',
                'salary_from' => 'nullable|numeric',
                'salary_to' => 'nullable|numeric|gte:salary_from',
                'currency' => 'nullable|string|max:3',
                'employment_type' => 'nullable|string',
                'schedule' => 'nullable|string|max:255',
                'key_skills' => 'nullable|string',
                'experience' => 'nullable|string|max:255',
                'address' => 'nullable|string|max:255',
            ]);
            \Log::info('Данные успешно валидированы', ['validated_data' => $data]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            \Log::error('Ошибка валидации данных при обновлении вакансии', [
                'api_id' => $apiId,
                'errors' => $e->validator->errors(),
            ]);
            return response()->json(['error' => 'Ошибка валидации данных', 'details' => $e->validator->errors()], 422);
        }

        // Обновление вакансии
        $data['is_updated'] = true; // Установить флаг обновления
        $job->update($data);
        \Log::info("Вакансия успешно обновлена: ", ['api_id' => $apiId, 'updated_data' => $job->toArray()]);

        return Inertia::render('JobView', [
            'job' => $job,
            'message' => 'Вакансия успешно обновлена!'
        ]);
    }

    // Метод для удаления вакансии
    public function destroy($id)
    {
        $job = $this->jobService->getJobById($id);

        if (!$job) {
            return response()->json(['error' => 'Вакансия не найдена'], 404);
        }

        // Установка времени удаления
        $job->update(['deleted_at' => now()]);

        return response()->json(['message' => 'Вакансия помечена как удаленная'], 200);
    }
}

