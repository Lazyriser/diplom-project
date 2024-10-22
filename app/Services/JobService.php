<?php

namespace App\Services;

use GuzzleHttp\Client;
use Illuminate\Http\Request;
use App\Models\Job;
use App\Models\Region;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class JobService
{
	protected $client;

	public function __construct()
	{
		$this->client = new Client([
			'base_uri' => 'https://api.hh.ru/',
			'verify' => false, // Отключить проверку SSL
		]);
	}

	public function index(Request $request)
	{
		$page = $request->input('page', 0);
		$perPage = $request->input('per_page', 20);
		$region = $request->input('region', 1);

		\Log::info('Используемый регион: ' . $region);

		$vacanciesData = $this->getVacancies('php', $region, $page, $perPage);

		$fullVacanciesData = [];

		if (isset($vacanciesData['items'])) {
			$newVacancies = $this->filterNewVacancies($vacanciesData['items']);
			if (!empty($newVacancies)) {
				$fullVacanciesData = $this->saveVacancies($newVacancies, $region);
			}
		}

		return response()->json([
			'vacancies' => $vacanciesData,
			'full_vacancies_data' => $fullVacanciesData,
		]);
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

			if ($response->getStatusCode() !== 200) {
				Log::error("Получен неожиданный статус кода: " . $response->getStatusCode());
				return ['error' => 'Не удалось получить вакансии.'];
			}

			$data = json_decode($response->getBody()->getContents(), true);

			return $data;
		} catch (\Exception $e) {
			\Log::error("Ошибка при получении вакансий: " . $e->getMessage());
			return ['error' => 'Не удалось получить вакансии.'];
		}
	}

	protected function filterNewVacancies($vacancies)
	{
		$existingVacanciesIds = Job::pluck('api_id')->toArray();
		return array_filter($vacancies, function ($vacancy) use ($existingVacanciesIds) {
			return !in_array($vacancy['id'], $existingVacanciesIds);
		});
	}

	protected function saveVacancies($vacancies, $region)
	{
		$requestCount = 0;
		$fullVacanciesData = [];

		$regions = Region::all()->keyBy('name');

		foreach ($vacancies as $vacancy) {
			$apiId = $vacancy['id'] ?? null;
			$regionId = $regions->get($vacancy['area']['name'] ?? '')?->id ?? null;

			if (!$apiId) {
				\Log::warning("Пропускаем вакансию без api_id: " . json_encode($vacancy));
				continue;
			}

			$fullVacancyData = $this->getFullVacancyData($apiId);
			$fullVacanciesData[] = $fullVacancyData;

			if (empty($vacancy['name']) || empty($vacancy['employer']['name'])) {
				Log::warning("Пропускаем вакансию без заголовка или имени компании: " . json_encode($vacancy));
				continue;
			}

			// Проверяем, существует ли вакансия в базе данных
			$job = Job::where('api_id', $apiId)->first();

			// Если вакансия не существует, создаем новую
			if (!$job) {
				$dataToSave = [
					'api_id' => $apiId,
					'title' => $vacancy['name'] ?? null,
					'description' => $fullVacancyData['description'] ?? null,
					'region_id' => $regionId ?? $region,
					'salary_from' => $vacancy['salary']['from'] ?? null,
					'salary_to' => $vacancy['salary']['to'] ?? null,
					'currency' => $vacancy['salary']['currency'] ?? null,
					'company_name' => $vacancy['employer']['name'] ?? null,
					'schedule' => $vacancy['schedule']['name'] ?? null,
					'address' => $vacancy['address']['raw'] ?? null,
					'experience' => $vacancy['experience']['name'] ?? null,
					'employment_type' => $vacancy['employment']['name'] ?? null,
					'key_skills' => !empty($fullVacancyData['key_skills']) ? implode(', ', array_column($fullVacancyData['key_skills'], 'name')) : null,
					'url' => $fullVacancyData['alternate_url'] ?? null,
					'deleted_at' => null,
					'is_updated' => false, // Устанавливаем флаг
				];

				Job::create($dataToSave);
			} else {
				// Если вакансия существует, проверяем флаг is_updated
				if (!$job->is_updated) {
					// Создаем массив для обновляемых данных
					$updates = [];

					// Добавляем только те поля, которые нужно обновить
					if ($vacancy['name'] !== $job->title) {
						$updates['title'] = $vacancy['name'];
					}
					if ($fullVacancyData['description'] !== $job->description) {
						$updates['description'] = $fullVacancyData['description'];
					}
					if ($regionId !== $job->region_id) {
						$updates['region_id'] = $regionId;
					}
					if ($vacancy['salary']['from'] !== $job->salary_from) {
						$updates['salary_from'] = $vacancy['salary']['from'];
					}
					if ($vacancy['salary']['to'] !== $job->salary_to) {
						$updates['salary_to'] = $vacancy['salary']['to'];
					}
					if ($vacancy['salary']['currency'] !== $job->currency) {
						$updates['currency'] = $vacancy['salary']['currency'];
					}
					if ($vacancy['employer']['name'] !== $job->company_name) {
						$updates['company_name'] = $vacancy['employer']['name'];
					}
					if ($vacancy['schedule']['name'] !== $job->schedule) {
						$updates['schedule'] = $vacancy['schedule']['name'];
					}
					if ($vacancy['address']['raw'] !== $job->address) {
						$updates['address'] = $vacancy['address']['raw'];
					}
					if ($vacancy['experience']['name'] !== $job->experience) {
						$updates['experience'] = $vacancy['experience']['name'];
					}
					if ($vacancy['employment']['name'] !== $job->employment_type) {
						$updates['employment_type'] = $vacancy['employment']['name'];
					}
					if (!empty($fullVacancyData['key_skills'])) {
						$newKeySkills = implode(', ', array_column($fullVacancyData['key_skills'], 'name'));
						if ($newKeySkills !== $job->key_skills) {
							$updates['key_skills'] = $newKeySkills;
						}
					}
					if ($fullVacancyData['alternate_url'] !== $job->url) {
						$updates['url'] = $fullVacancyData['alternate_url'];
					}

					// Обновляем только если есть изменения
					if (!empty($updates)) {
						$job->update($updates);
					}
				}
			}

			$requestCount++;

			if ($requestCount % 5 === 0) {
				sleep(1);
			}
		}

		return $fullVacanciesData;
	}




	public function getFullVacancyData($vacancyId)
	{
		try {
			$response = $this->client->get("vacancies/{$vacancyId}", [
				'headers' => ['Cache-Control' => 'no-cache']
			]);
			$data = json_decode($response->getBody()->getContents(), true);

			return ['data' => $data];
		} catch (\Exception $e) {
			return ['data' => []];
		}
	}


	public function getAreas()
	{
		return $this->fetchRegionsFromAPI();
	}

	public function createJob(array $data)
	{
		// Если api_id передан, проверяем, существует ли вакансия с таким api_id
		if (isset($data['api_id'])) {
			$existingJob = Job::where('api_id', $data['api_id'])->first();
			if ($existingJob) {
				throw new \Exception('Вакансия с таким api_id уже существует.');
			}
		}

		return Job::create($data);
	}




	public function getJobById($apiId)
	{
		return Job::where('api_id', $apiId)->firstOrFail();
	}

	public function updateJob($apiId, $data)
	{
		$this->validateJob($data, $apiId);

		Log::info("Обновление вакансии с api_id: {$apiId}, данные: ", $data);

		$job = Job::where('api_id', $apiId)->firstOrFail();
		$job->update($data);

		Log::info("Вакансия с api_id: {$apiId} обновлена успешно.");
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
			'title' => 'required|string|max:255',
			'description' => 'nullable|string',
			'region_id' => 'required|exists:regions,id',
			'salary_from' => 'nullable|numeric',
			'salary_to' => 'nullable|numeric|gte:salary_from',
			'currency' => 'nullable|string|max:3',
			'employment_type' => 'nullable|string',
			'company_name' => 'nullable|string|max:255',
			'schedule' => 'nullable|string|max:255',
			'key_skills' => 'nullable|string',
			'experience' => 'nullable|string|max:255',
			'address' => 'nullable|string|max:255',
			'deleted_at' => 'nullable|date',
		]);

		if ($validator->fails()) {
			throw new \Illuminate\Validation\ValidationException($validator);
		}
	}



	public function getRegions()
	{
		return Region::all();
	}

	public function fetchRegionsFromAPI()
	{
		try {
			$response = $this->client->get('areas');
			\Log::info('Ответ API для регионов: ' . $response->getBody());
			$regionsData = json_decode($response->getBody(), true);

			$savedRegions = [];

			if (is_array($regionsData)) {
				foreach ($regionsData as $regionData) {
					if (isset($regionData['id'], $regionData['name'], $regionData['areas'])) {
						$this->saveRegions($regionData);
						$savedRegions[] = $regionData;
						$this->saveSubRegions($regionData['areas'], $regionData['id']);
					} else {
						\Log::warning('Недостаточно данных в ответе региона: ', ['regionData' => $regionData]);
					}
				}
			} else {
				\Log::error('Неожиданный ответ API: ', ['response' => $regionsData]);
			}

			return $savedRegions;
		} catch (\Exception $e) {
			\Log::error("Ошибка при получении регионов: " . $e->getMessage());
			return [];
		}
	}

	private function saveSubRegions(array $areas, $parentId)
	{
		foreach ($areas as $area) {
			if (isset($area['id'], $area['name'], $area['areas'])) {

				$this->saveRegions($area, $parentId);

				$this->saveSubRegions($area['areas'], $area['id']);
			} else {
				\Log::warning('Недостаточно данных в ответе подрегиона: ', ['area' => $area]);
			}
		}
	}

	protected function saveRegions($response, $parentId = null)
	{

		$existingRegion = Region::where('api_id', $response['id'])->first();


		if ($existingRegion) {
			if ($existingRegion->name !== $response['name'] || $existingRegion->parent_id !== $parentId) {
				$existingRegion->update([
					'name' => $response['name'],
					'parent_id' => $parentId,
				]);
			}
			return;
		}


		Region::create([
			'api_id' => $response['id'],
			'name' => $response['name'],
			'parent_id' => $parentId,
		]);

		if (!empty($response['areas'])) {
			$this->saveSubRegions($response['areas'], $response['id']);
		}
	}
}
