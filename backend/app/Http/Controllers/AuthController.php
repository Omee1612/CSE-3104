<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email',
            'phone' => 'required|string|unique:users,phone',
            'password' => 'required|string|min:8',
            'role' => 'required|in:worker,agency,nominee',
        ]);

        $trackingId = 'DNK-' . date('Y') . '-' . str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $now = now();

        $userId = DB::table('users')->insertGetId([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'],
            'password' => Hash::make($data['password']),
            'role' => $data['role'],
            'tracking_id' => $trackingId,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $user = DB::select("SELECT * FROM users WHERE id = ?", [$userId])[0];

        $userModel = \App\Models\User::find($userId);
        $token = $userModel->createToken('dunki-spa')->plainTextToken;

        return response()->json(['user' => $user, 'token' => $token], 201);
    }

    public function login(Request $request)
    {
        $data = $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        $results = DB::select("SELECT * FROM users WHERE email = ?", [$data['email']]);
        $user = $results[0] ?? null;

        if (!$user || !Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['These credentials do not match our records.'],
            ]);
        }

        $userModel = \App\Models\User::find($user->id);
        $token = $userModel->createToken('dunki-spa')->plainTextToken;

        return response()->json(['user' => $user, 'token' => $token]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Logged out']);
    }

    public function me(Request $request)
    {
        $user = DB::select("SELECT * FROM users WHERE id = ?", [$request->user()->id])[0];
        return response()->json($user);
    }
}
