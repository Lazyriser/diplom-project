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
			$table->string('api_id')->nullable()->default(''); // Добавьте значение по умолчанию
			$table->string('title');
			$table->text('description');
			$table->unsignedBigInteger('region_id')->constrained('regions')->onDelete('cascade');
			$table->string('company_name')->nullable();
			$table->decimal('salary', 10, 2)->nullable();
			$table->enum('employment_type', ['full-time', 'part-time', 'contract'])->nullable();
			$table->timestamps();
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
