<?php

namespace App\Services;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class JwtService
{
    public static function generateAccessToken($user)
    {
        $payload = [
            'iss' => env('APP_URL'),
            'sub' => $user->id,
            'role' => $user->role,
            'exp' => time() + (15 * 60)
        ];

        return JWT::encode($payload, env('JWT_SECRET'), 'HS256');
    }

    public static function generateRefreshToken($user)
    {
        $payload = [
            'sub' => $user->id,
            'exp' => time() + (7 * 24 * 60 * 60)
        ];

        return JWT::encode($payload, env('JWT_REFRESH_SECRET'), 'HS256');
    }

    public static function decodeAccessToken($token)
    {
        return JWT::decode($token, new Key(env('JWT_SECRET'), 'HS256'));
    }

    public static function decodeRefreshToken($token)
    {
        return JWT::decode($token, new Key(env('JWT_REFRESH_SECRET'), 'HS256'));
    }
}