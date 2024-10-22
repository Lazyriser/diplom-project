<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Модель вакансии
 */
class Job extends Model
{
	use HasFactory, SoftDeletes; // Используем трейты

	/**
	 * Колонки, доступные для записи
	 * @var array
	 */
	protected $fillable = [
		'api_id',
		'title',
		'description',
		'region_id',
		'company_name',
		'salary_from',
		'salary_to',
		'employment_type',
		'schedule',
		'key_skills',
		'experience',
		'address',
		'currency',
		'deleted_at',
		'is_updated',
	];

	/**
	 * Явное указание имени таблицы
	 * @var string
	 */
	protected $table = 'vacancies';

	/**
	 * Отношение к модели Region
	 */
	public function region()
	{
		return $this->belongsTo(Region::class);
	}

	/**
	 * Форматирование зарплаты
	 */
	public function getFormattedSalaryAttribute()
	{
		// Если зарплата задана, форматируем ее
		return $this->salary_from && $this->salary_to
			? number_format($this->salary_from, 0, ',', ' ') . ' - ' . number_format($this->salary_to, 0, ',', ' ') . ' руб.'
			: 'Не указана'; // Возвращаем текст, если зарплата не указана
	}

	/**
	 * Имя региона
	 */
	public function getRegionNameAttribute()
	{
		return $this->region ? $this->region->name : 'Не указан'; // Имя региона
	}

	/**
	 * Валидация данных для создания/обновления вакансии
	 */
	public static function rules($ignoreApiId = null)
	{
		return [
			'api_id' => 'required|string' . ($ignoreApiId ? '|unique:vacancies,api_id,' . $ignoreApiId : '|unique:vacancies,api_id'),
			'title' => 'required|string|max:255',
			'description' => 'nullable|string', // Изменено на 'string' для правильной валидации
			'region_id' => 'required|exists:regions,id',
			'salary_from' => 'nullable|numeric',
			'salary_to' => 'nullable|numeric|gte:salary_from', // Проверка на диапазон
			'currency' => 'nullable|string|max:255',
			'employment_type' => 'nullable|string|in:full-time,part-time,contract',
			'company_name' => 'nullable|string|max:255',
			'schedule' => 'nullable|string|max:255',
			'key_skills' => 'nullable|string',
			'experience' => 'nullable|string|max:255',
			'address' => 'nullable|string|max:255',
			'is_updated' => 'boolean',
			'deleted_at' => 'nullable|date',
		];
	}
}

