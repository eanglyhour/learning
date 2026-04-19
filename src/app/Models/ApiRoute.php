<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApiRoute extends Model
{
    protected $fillable = [
        'name',
        'uri',
        'method',
        'action',
        'middleware',
    ];
}
