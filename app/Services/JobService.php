<?php

namespace App\Services;

use GuzzleHttp\Client;
use Illuminate\Http\Request;
use App\Models\Job;
use Illuminate\Support\Facades\Validator;

class JobService
{
	protected $client;

	public function __construct()
	{
		$this->client = new Client([
			'base_uri' => 'https://api.hh.ru/',
		]);
	}

	public function index(Request $request)
	{
		$page = $request->input('page', 0);
		$perPage = $request->input('per_page', 20);
		$region = $request->input('region', 1);

		$vacanciesData = $this->getVacancies('php', $region, $page, $perPage);

		if (isset($vacanciesData['items'])) {
			$this->saveVacancies($vacanciesData['items'], $region);
		}

		return response()->json($vacanciesData);
	}

	public function getVacancies($text = 'php', $area = 1, $page = 0, $perPage = 20)
	{
		try {
			$response = $this->client->get('vacancies', [
				'query' => [
					'text' => $text,
					'area' => $area,
					'page' => $page,
					'per_page' => $perPage,
				]
			]);
			return json_decode($response->getBody()->getContents(), true);
		} catch (\Exception $e) {
			return response()->json(['error' => $e->getMessage()], 500);
		}
	}

	protected function saveVacancies($vacancies, $region)
	{
		foreach ($vacancies as $vacancy) {
			\Log::info("Сохранение вакансии: " . json_encode($vacancy));

			$apiId = $vacancy['id'] ?? null;

			if (!$apiId) {
				\Log::warning("Пропускаем вакансию без api_id: " . json_encode($vacancy));
				continue;
			}

			try {
				// Здесь убедитесь, что вы правильно обрабатываете поля
				$dataToSave = [
					'title' => $vacancy['name'] ?? null, // Название вакансии
					'description' => $vacancy['description'] ?? '', // Описание вакансии
					'region_id' => $vacancy['area']['id'] ?? $region, // ID региона
					'company_name' => $vacancy['employer']['name'] ?? null, // Название компании
					'salary' => $vacancy['salary']['from'] ?? null, // Заработная плата
					'employment_type' => $vacancy['employment']['name'] ?? null, // Тип занятости
				];

				\Log::info("Данные для вставки: ", array_merge(['api_id' => $apiId], $dataToSave));

				Job::updateOrCreate(
					['api_id' => $apiId],
					$dataToSave
				);
			} catch (\Exception $e) {
				\Log::error("Ошибка при сохранении вакансии с ID: " . $apiId . ". Ошибка: " . $e->getMessage());
			}
		}
	}

	public function getAreas()
	{
		$response = $this->client->get('areas');
		return json_decode($response->getBody()->getContents(), true);
	}

	public function createJob($data)
	{
		// Убедимся, что api_id корректно обрабатывается
		$data['api_id'] = $data['api_id'] ?? $data['id']; // Предположим, что $data['id'] приходит из формы
		$this->validateJob($data);
		return Job::create($data);
	}

	public function getJobById($apiId)
	{
		return Job::where('api_id', $apiId)->firstOrFail();
	}

	public function updateJob($apiId, $data)
	{
		$this->validateJob($data, $apiId);
		$job = Job::where('api_id', $apiId)->firstOrFail();
		$job->update($data);
		return $job;
	}

	public function deleteJob($apiId)
	{
		$job = Job::where('api_id', $apiId)->firstOrFail();
		$job->delete();
	}

	protected function validateJob($data, $ignoreApiId = null)
	{
		$validator = Validator::make($data, [
			'api_id' => 'nullable|string' . ($ignoreApiId ? '|unique:jobs,api_id,' . $ignoreApiId : '|unique:jobs,api_id'),
			'title' => 'required|string|max:255',
			'description' => 'nullable|string',
			'region_id' => 'required|exists:regions,id',
		]);

		// Проверка, чтобы хотя бы одно поле api_id или id было заполнено
		if (empty($data['api_id']) && empty($data['id'])) {
			$validator->errors()->add('api_id', 'Поле api_id или id должно быть заполнено.');
		}

		if ($validator->fails()) {
			throw new \Illuminate\Validation\ValidationException($validator);
		}
	}
}
