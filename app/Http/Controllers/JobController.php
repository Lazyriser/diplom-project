<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\JobService;
use App\Models\Job;

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

		// Получаем вакансии из API
		$vacanciesData = $this->jobService->getVacancies('php', $region, $page, $perPage);

		if (isset($vacanciesData['error'])) {
			return response()->json(['error' => $vacanciesData['error']], 500);
		}

		foreach ($vacanciesData['items'] as $vacancy) {
			\Log::info('Сохранение вакансии:', $vacancy);

			Job::updateOrCreate(
				['id' => $vacancy['id']],
				[
					'title' => $vacancy['name'],
					'description' => $vacancy['description'] ?? '',
					'region_id' => $region,
				]
			);
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
			'title' => 'required|string|max:255',
			'description' => 'required|string',
			'region_id' => 'required|exists:regions,id',
		]);

		$job = $this->jobService->createJob($data);
		return response()->json($job, 201);
	}

	public function show($id)
	{
		$job = $this->jobService->getJobById($id);

		if (!$job) {
			return response()->json(['error' => 'Вакансия не найдена'], 404);
		}

		return response()->json($job);
	}

	public function update(Request $request, $id)
	{
		$data = $request->validate([
			'title' => 'sometimes|required|string|max:255',
			'description' => 'sometimes|required|string',
			'region_id' => 'sometimes|required|integer',
		]);

		$job = $this->jobService->getJobById($id);

		if (!$job) {
			return response()->json(['error' => 'Вакансия не найдена'], 404);
		}

		$job = $this->jobService->updateJob($id, $data);
		return response()->json($job);
	}

	public function destroy($id)
	{
		$job = $this->jobService->getJobById($id);

		if (!$job) {
			return response()->json(['error' => 'Вакансия не найдена'], 404);
		}

		$this->jobService->deleteJob($id);
		return response()->json(['message' => 'Вакансия удалена'], 200);
	}
}
