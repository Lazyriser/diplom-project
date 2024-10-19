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
			$table->string('name'); // Название региона
			$table->timestamps(); // Поля для created_at и updated_at
		});
	}

	public function down()
	{
		Schema::dropIfExists('regions');
	}
}
