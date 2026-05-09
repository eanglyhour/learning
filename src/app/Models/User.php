<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'status'
    ];

    protected $hidden = [
        'password'
    ];

    public function refreshTokens()
    {
        return $this->hasMany(RefreshToken::class);
    }
    public function person()
    {
        return $this->hasOne(Person::class);
    }
}
