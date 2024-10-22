<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
	/**
	 * Run the migrations.
	 */
	public function up(): void
	{
		Schema::create('vacancies', function (Blueprint $table) {
			$table->id(); // Сначала создаем поле id
			$table->string('api_id')->unique()->nullable(); // api_id уникально, может быть null
			$table->string('title');
			$table->string('company_name')->nullable();
			$table->text('description');
			$table->unsignedBigInteger('region_id'); // Без 'constrained' на данном этапе
			$table->decimal('salary_from', 10, 2)->nullable();
			$table->decimal('salary_to', 10, 2)->nullable();
			$table->string('currency')->nullable();
			$table->string('address')->nullable();
			$table->string('experience')->nullable();
			$table->string('schedule')->nullable();
			$table->text('key_skills')->nullable();
			$table->string('employment_type')->nullable();
			$table->boolean('is_updated')->default(false);
			$table->softDeletes();
			$table->timestamps();

			// Добавление внешнего ключа после создания поля
			$table->foreign('region_id')->references('id')->on('regions')->onDelete('cascade');
		});
	}

	/**
	 * Reverse the migrations.
	 */
	public function down(): void
	{
		Schema::dropIfExists('vacancies');
	}
};
