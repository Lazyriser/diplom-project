<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\JobService;
use App\Models\Job;
use App\Models\Region;

class UpdateJobsCommand extends Command
{
	protected $signature = 'jobs:update';
	protected $description = 'Обновить вакансии и регионы';

	protected $jobService;

	public function __construct(JobService $jobService)
	{
		parent::__construct();
		$this->jobService = $jobService;
	}

	public function handle()
	{
		$this->info('Обновляем регионы');
		// Обновляем регионы
		$regions = $this->jobService->getAreas();

		if (isset($regions['items']) && is_array($regions['items'])) {
			foreach ($regions['items'] as $region) {
				Region::updateOrCreate(
					['api_id' => $region['id']],
					['name' => $region['name']]
				);
			}
		} else {
			$this->warn('Не удалось получить регионы. Ответ API: ' . json_encode($regions));
		}

		$this->info('Обновляем вакансии');
		// Получаем все регионы из базы данных
		$regions = Region::all();

		$page = 0; // Начальная страница
		$perPage = 100; // Количество вакансий на странице
		$totalVacancies = 0;

		// Проходим по каждому региону
		foreach ($regions as $region) {
			$this->info("Обновляем вакансии для региона: {$region->name}");

			do {
				// Передаем идентификатор региона в метод getVacancies
				$vacanciesData = $this->jobService->getVacancies('php', $region->api_id, $page, $perPage);

				if (isset($vacanciesData['items']) && is_array($vacanciesData['items'])) {
					foreach ($vacanciesData['items'] as $vacancy) {
						$fullVacancyDataResult = $this->jobService->getFullVacancyData($vacancy['id']);
						$fullVacancyData = $fullVacancyDataResult['data'];

						$key_skills = isset($fullVacancyData['key_skills'])
							? implode(', ', array_column($fullVacancyData['key_skills'], 'name'))
							: null;

						Job::updateOrCreate(
							['api_id' => $vacancy['id']],
							[
								'title' => $vacancy['name'] ?? null,
								'description' => $fullVacancyData['description'] ?? '',
								'region_id' => $region->id, // Используем ID региона из базы
								'salary_from' => $vacancy['salary']['from'] ?? null,
								'salary_to' => $vacancy['salary']['to'] ?? null,
								'currency' => $vacancy['salary']['currency'] ?? null,
								'company_name' => $vacancy['employer']['name'] ?? null,
								'schedule' => $vacancy['schedule']['name'] ?? null,
								'key_skills' => $key_skills,
								'address' => $vacancy['address']['raw'] ?? null,
								'experience' => $vacancy['experience']['name'] ?? null,
								'employment_type' => $vacancy['employment']['name'] ?? null,
								'is_updated' => true,
							]
						);

						$totalVacancies++;
					}
				} else {
					$this->warn('Не удалось получить вакансии на странице ' . $page . '. Ответ API: ' . json_encode($vacanciesData));
					break; // Выход из цикла, если данные не получены
				}

				$page++; // Переход к следующей странице
			} while (count($vacanciesData['items']) === $perPage); // Продолжаем, пока есть вакансии на странице
		}

		$this->info("Вакансии и регионы успешно обновлены. Всего загружено вакансий: {$totalVacancies}");
	}
}

