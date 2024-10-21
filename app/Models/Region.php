<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Region extends Model
{
	use HasFactory;

	protected $fillable = ['api_id', 'name', 'parent_id'];

	public function jobs()
	{
		return $this->hasMany(Job::class);
	}
}
