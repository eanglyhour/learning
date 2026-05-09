<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function index()
    {
        $authUser = Auth::user();

        $query = User::select('id','name','email','role','status');

        if ($authUser->role === 'admin') {

            $data = $query->latest()->paginate(10);

        } elseif ($authUser->role === 'manager') {

            $data = $query->where('role', '!=', 'admin')
                          ->latest()
                          ->paginate(10);

        } else {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        return response()->json([
            'data' => $data
        ]);
    }

    public function store(Request $request)
    {
        $authUser = Auth::user();

        $request->validate([
            'name' => 'required|string',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:6',
            'role' => 'required|in:admin,manager,cashier,user'
        ]);

        $allowedRoles = match ($authUser->role) {
            'admin' => ['admin', 'manager', 'cashier', 'user'],
            'manager' => ['cashier'],
            default => []
        };

        if (!in_array($request->role, $allowedRoles)) {
            return response()->json(['message' => 'Not allowed'], 403);
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
            'status' => 1
        ]);

        return response()->json([
            'message' => 'User created',
            'data' => $user
        ]);
    }

    public function show($id)
    {
        $authUser = Auth::user();

        $user = User::find($id);

        if (!$user) {
            return response()->json(['message' => 'Not found'], 404);
        }

        if ($authUser->role !== 'admin' && $authUser->id !== $user->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        return response()->json($user);
    }

    public function update(Request $request, $id)
    {
        $authUser = Auth::user();

        $user = User::find($id);

        if (!$user) {
            return response()->json(['message' => 'Not found'], 404);
        }

        if (
            $authUser->role !== 'admin' &&
            $authUser->role !== 'manager' &&
            $authUser->id !== $user->id
        ) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        if ($authUser->role === 'manager' && $user->role === 'admin') {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $request->validate([
            'name' => 'sometimes|string',
            'email' => 'sometimes|email|unique:users,email,' . $id,
            'status' => 'sometimes|boolean'
        ]);

        $user->update([
            'name' => $request->name ?? $user->name,
            'email' => $request->email ?? $user->email,
            'status' => $request->status ?? $user->status
        ]);

        return response()->json([
            'message' => 'Updated successfully',
            'data' => $user
        ]);
    }

    public function destroy($id)
    {
        $authUser = Auth::user();

        $user = User::find($id);

        if (!$user) {
            return response()->json(['message' => 'Not found'], 404);
        }

        if ($authUser->role !== 'admin') {
            return response()->json(['message' => 'Only admin can delete'], 403);
        }

        if ($authUser->id === $user->id) {
            return response()->json(['message' => 'You cannot delete yourself'], 403);
        }

        $user->delete();

        return response()->json(['message' => 'Deleted successfully']);
    }
}