<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AppIntro extends Model
{
    use HasFactory;

    protected $table = 'app_intros';

    protected $fillable = [
        'title',
        'description',
        'image',
        'button_text',
        'is_active',
        'order_no',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'order_no'  => 'integer',
    ];
}

