<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Person extends Model
{
    protected $fillable = [
        'name',
        'title',
        'image'
    ];
    public function user()
{
    return $this->belongsTo(User::class);
}
}
