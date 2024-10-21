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

	public function index(Request $request)
	{
		$page = $request->input('page', 0);
		$perPage = $request->input('per_page', 20);
		$region = $request->input('region', 1);

		$existingVacanciesIds = Job::pluck('api_id')->toArray();

		$vacanciesData = $this->jobService->getVacancies('php', $region, $page, $perPage);

		if (isset($vacanciesData['error'])) {
			return response()->json(['error' => $vacanciesData['error']], 500);
		}

		if (isset($vacanciesData['items']) && is_array($vacanciesData['items'])) {
			foreach ($vacanciesData['items'] as $vacancy) {
				if (in_array($vacancy['id'], $existingVacanciesIds)) {
					Log::info("Вакансия с api_id {$vacancy['id']} уже существует, пропускаем.");
					continue;
				}

				$fullVacancyDataResult = $this->jobService->getFullVacancyData($vacancy['id']);
				$fullVacancyData = $fullVacancyDataResult['data'];
				$logs = $fullVacancyDataResult['logs'];

				$key_skills = isset($fullVacancyData['key_skills'])
					? implode(', ', array_column($fullVacancyData['key_skills'], 'name'))
					: null;

				try {
					Job::create([
						'api_id' => $vacancy['id'],
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
						'deleted_at' => $vacancy['deleted_at'] ?? null,
					]);

					$job = Job::where('api_id', $vacancy['id'])->first();
					Log::info("Запись вакансии сохранена: ", $job->toArray());

					foreach ($logs as $log) {
						Log::info($log);
					}
				} catch (\Exception $e) {
					Log::error("Ошибка при сохранении вакансии с api_id {$vacancy['id']}: " . $e->getMessage());
				}
			}
		}

		return response()->json($vacanciesData);
	}


	public function getRegions()
	{
		$areas = $this->jobService->getAreas();
		return response()->json($areas);
	}

	public function store(Request $request)
	{
		$data = $request->validate([
			'api_id' => 'required|string|unique:jobs,api_id',
			'title' => 'required|string|max:255',
			'description' => 'nullable|string',
			'region_id' => 'required|exists:regions,id',
			'company_name' => 'nullable|string|max:255',
			'salary_from' => 'nullable|numeric',
			'salary_to' => 'nullable|numeric|gte:salary_from',
			'currency' => 'nullable|string|max:255',
			'employment_type' => 'nullable|string|in:full-time,part-time,contract',
			'schedule' => 'nullable|string|max:255',
			'key_skills' => 'nullable|string',
			'experience' => 'nullable|string|max:255',
			'address' => 'nullable|string|max:255',
			'deleted_at' => 'nullable|date',
		]);

		$job = $this->jobService->createJob($data);
		return response()->json($job, 201);
	}

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

	public function edit($id)
	{
		$job = $this->jobService->getJobById($id);
		$regions = $this->jobService->getRegions();

		if (!$job) {
			return response()->json(['message' => 'Вакансия не найдена'], 404);
		}

		return Inertia::render('EditJob', [
			'job' => $job,
			'regions' => $regions, // Передаем регионы во вьюху
		]);
	}

	public function update(Request $request, $id)
	{
		$data = $request->validate([
			'api_id' => 'sometimes|string|unique:jobs,api_id,' . $id,
			'title' => 'required|string|max:255',
			'description' => 'required|string',
			'region_id' => 'required|exists:regions,id',
			'company_name' => 'nullable|string|max:255',
			'salary_from' => 'nullable|numeric',
			'salary_to' => 'nullable|numeric|gte:salary_from', // Проверка на диапазон
			'currency' => 'nullable|string|max:255',
			'employment_type' => 'nullable|string|in:full-time,part-time,contract',
			'schedule' => 'nullable|string|max:255',
			'key_skills' => 'nullable|string',
			'experience' => 'nullable|string|max:255',
			'address' => 'nullable|string|max:255',
			'deleted_at' => 'nullable|date',
		]);

		$job = $this->jobService->getJobById($id);

		if (!$job) {
			return response()->json(['error' => 'Вакансия не найдена'], 404);
		}

		$job->update($data);

		return response()->json($job);
	}

	public function destroy($id)
	{
		$job = $this->jobService->getJobById($id);

		if (!$job) {
			return response()->json(['error' => 'Вакансия не найдена'], 404);
		}

		$job->delete();

		return response()->json(['message' => 'Вакансия удалена'], 200);
	}
}
