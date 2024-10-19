<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Job extends Model
{
	use HasFactory;

	protected $fillable = [
		'api_id', // Это поле должно быть включено
		'title',
		'description',
		'region_id',
		'company_name',
		'salary',
		'employment_type',
	];

	protected $table = 'vacancies'; // Явное указание имени таблицы

	public function region()
	{
		return $this->belongsTo(Region::class);
	}

	public function getFormattedSalaryAttribute()
	{
		return number_format($this->salary, 0, ',', ' ') . ' руб.'; // Форматирование зарплаты
	}

	public function getRegionNameAttribute()
	{
		return $this->region ? $this->region->name : 'Не указан'; // Имя региона
	}

	// Валидация данных для создания/обновления вакансии
	public static function rules($ignoreApiId = null)
	{
		return [
			'api_id' => 'required|string' . ($ignoreApiId ? '|unique:vacancies,api_id,' . $ignoreApiId : '|unique:vacancies,api_id'),
			'title' => 'required|string|max:255',
			'description' => 'nullable|string', // Измените на nullable, если нужно
			'region_id' => 'required|exists:regions,id',
			'company_name' => 'string|max:255|nullable',
			'salary' => 'numeric|nullable',
			'employment_type' => 'string|in:full-time,part-time,contract|nullable',
		];
	}
}
