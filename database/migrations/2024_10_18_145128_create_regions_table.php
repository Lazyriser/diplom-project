<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRegionsTable extends Migration
{
	public function up()
	{
		Schema::create('regions', function (Blueprint $table) {
			$table->id();
			$table->string('name');
			$table->string('api_id')->default('0')->unique()->nullable(); // Добавление api_id
			$table->unsignedBigInteger('parent_id')->nullable(); // Для хранения родительского региона
			$table->timestamps();
		});
	}

	public function down()
	{
		Schema::dropIfExists('regions');
	}
}
