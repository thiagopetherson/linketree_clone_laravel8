<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class View extends Model
{
	protected $fillable = ['id_page','view_date'];

   	public $timestamps = false;
}
