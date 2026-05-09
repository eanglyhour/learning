<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\RefreshToken;
use App\Services\JwtService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6'
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => 'user',
            'status' => true
        ]);

        return response()->json([
            'message' => 'Registered successfully',
            'user' => $this->userData($user)
        ], 201);
    }

    public function loginDashboard(Request $request)
    {
        return $this->login($request, ['admin', 'manager', 'cashier'], 'Dashboard');
    }

    public function loginApp(Request $request)
    {
        return $this->login($request, ['user'], 'Mobile App');
    }

    private function login(Request $request, array $roles, string $type)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string'
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'message' => 'Invalid credentials'
            ], 401);
        }

        if (!in_array($user->role, $roles)) {
            return response()->json([
                'message' => "Unauthorized for $type"
            ], 403);
        }

        return $this->issueTokens($user);
    }

    private function issueTokens(User $user)
    {
        $accessToken = JwtService::generateAccessToken($user);
        $refreshToken = JwtService::generateRefreshToken($user);

        RefreshToken::where('user_id', $user->id)->delete();

        RefreshToken::create([
            'user_id' => $user->id,
            'token' => $refreshToken,
            'expires_at' => now()->addDays(7)
        ]);

        return response()
            ->json([
                'access_token' => $accessToken,
                'user' => $this->userData($user)
            ])
            ->cookie(
                'refresh_token',
                $refreshToken,
                60 * 24 * 7,
                '/',
                null,
                true,
                true,
                false,
                'Strict'
            );
    }

    public function refresh(Request $request)
    {
        $token = $request->cookie('refresh_token');

        if (!$token) {
            return response()->json([
                'message' => 'No refresh token'
            ], 401);
        }

        $storedToken = RefreshToken::where('token', $token)->first();

        if (!$storedToken || $storedToken->expires_at < now()) {
            return response()->json([
                'message' => 'Invalid or expired token'
            ], 401);
        }

        $payload = JwtService::decodeRefreshToken($token);

        if (!$payload || !isset($payload->sub)) {
            return response()->json([
                'message' => 'Invalid token payload'
            ], 401);
        }

        $user = User::find($payload->sub);

        if (!$user) {
            return response()->json([
                'message' => 'User not found'
            ], 404);
        }

        $storedToken->delete();

        $newAccessToken = JwtService::generateAccessToken($user);
        $newRefreshToken = JwtService::generateRefreshToken($user);

        RefreshToken::create([
            'user_id' => $user->id,
            'token' => $newRefreshToken,
            'expires_at' => now()->addDays(7)
        ]);

        return response()
            ->json([
                'access_token' => $newAccessToken
            ])
            ->cookie(
                'refresh_token',
                $newRefreshToken,
                60 * 24 * 7,
                '/',
                null,
                true,
                true,
                false,
                'Strict'
            );
    }

    public function logout(Request $request)
    {
        $token = $request->cookie('refresh_token');

        if ($token) {
            RefreshToken::where('token', $token)->delete();
        }

        return response()
            ->json([
                'message' => 'Logged out successfully'
            ])
            ->cookie(
                'refresh_token',
                '',
                -1,
                '/',
                null,
                true,
                true,
                false,
                'Strict'
            );
    }

    public function me()
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'message' => 'Unauthenticated'
            ], 401);
        }

        return response()->json([
            'user' => $this->userData($user)
        ]);
    }

    private function userData(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role
        ];
    }
}