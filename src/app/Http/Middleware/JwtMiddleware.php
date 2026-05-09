<?php

namespace App\Http\Middleware;

use Closure;
use App\Models\User;
use App\Services\JwtService;
use Illuminate\Support\Facades\Auth;

class JwtMiddleware
{
    public function handle($request, Closure $next)
    {
        $header = $request->header('Authorization');

        if (!$header || !str_starts_with($header, 'Bearer ')) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $token = substr($header, 7);

        if (empty($token)) {
            return response()->json(['message' => 'Token missing'], 401);
        }

        try {
            $payload = JwtService::decodeAccessToken($token);

            if (!isset($payload->sub)) {
                return response()->json(['message' => 'Invalid token payload'], 401);
            }

            $user = User::find($payload->sub);

            if (!$user) {
                return response()->json(['message' => 'User not found'], 404);
            }

            Auth::setUser($user);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Invalid token',
                'error' => $e->getMessage()
            ], 401);
        }

        return $next($request);
    }
}