<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\JobController;
use Inertia\Inertia;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\RegisteredUserController;

// Главная страница
Route::get('/', function () {
	return Inertia::render('Home', [
		'canLogin' => Route::has('login'),
		'canRegister' => Route::has('register'),
		'laravelVersion' => Application::VERSION,
		'phpVersion' => PHP_VERSION,
	]);
});

// Маршруты аутентификации
Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
Route::post('/login', [AuthenticatedSessionController::class, 'store']);
Route::get('/register', [RegisteredUserController::class, 'create'])->name('register');
Route::post('/register', [RegisteredUserController::class, 'store']);

// Список вакансий (веб маршрут)
Route::get('/jobs', function () {
	return Inertia::render('JobList');
})->name('jobs.index');

// Защищенные маршруты для редактирования, создания и просмотра вакансий
Route::middleware(['auth'])->group(function () {
	Route::get('/jobs/create', function () {
		return Inertia::render('CreateJob'); // Обработка создания вакансии
	})->name('jobs.create');

	Route::get('/jobs/{id}', [JobController::class, 'show'])->name('jobs.show');

	Route::get('/jobs/{id}/edit', function ($id) {
		return Inertia::render('EditJob', ['id' => $id]); // Обработка редактирования вакансии
	})->name('jobs.edit');
});

// Страница дашборда
Route::get('/dashboard', function () {
	return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

// Профиль пользователя
Route::middleware('auth')->group(function () {
	Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
	Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
	Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// API маршруты
Route::prefix('api')->group(function () {
	Route::get('areas', [JobController::class, 'getRegions'])->name('api.areas.index'); // Для получения регионов
	Route::get('vacancies', [JobController::class, 'index'])->name('api.vacancies.index'); // Для получения списка вакансий
	Route::resource('jobs', JobController::class)->except(['create', 'edit'])->names([
		'index' => 'api.jobs.index',     // Для получения списка вакансий
		'show' => 'api.jobs.show',       // Для получения конкретной вакансии
		'store' => 'api.jobs.store',     // Для создания вакансии
		'update' => 'api.jobs.update',   // Для обновления вакансии
		'destroy' => 'api.jobs.destroy', // Для удаления вакансии
	]);
});


Route::get('/jobs/{id}/edit', [JobController::class, 'edit'])->name('jobs.edit');
