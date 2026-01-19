<?php

namespace App\Http\Controllers;

use App\Models\User;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'username' => 'required|string|unique:users',
            'email' => 'required|string|email|unique:users',
            'password' => 'required|string|min:6',
        ]);

        $user = User::create([
            'name' => $request->name,
            'username' => $request->username,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        $token = Auth::login($user);

        return response()->json([
            'message' => 'User registered successfully'
        ]);
    }

    // Login
    public function login(Request $request)
    {
        $credentials = $request->only('username', 'password');

        try {
            $user = \App\Models\User::where('username', $credentials['username'])->first();
            if (!$user) {
                return response()->json([
                    'success' => 0,
                    'error' => 1,
                    'message' => 'Validation failed',
                    'data' => [
                        'errors' => [
                            'username' => ['Username is incorrect.']
                        ]
                    ]
                ], 401);
            }
            if (!Hash::check($credentials['password'], $user->password)) {
                return response()->json([
                    'success' => 0,
                    'error' => 1,
                    'message' => 'Validation failed',
                    'data' => [
                        'errors' => [
                            'password' => ['Password is incorrect.']
                        ]
                    ]
                ], 401);
            }
            $token = JWTAuth::fromUser($user);
            if (!$token) {
                return response()->json([
                    'success' => 0,
                    'error' => 1,
                    'message' => 'Failed to generate token',
                    'data' => null
                ], 500);
            }
            return response()->json([
                'success' => 1,
                'error' => 0,
                'message' => 'Login Success',
                'data' => [
                    'token' => $token,
                    'userData' => $user,
                ]
            ], 200);
        } catch (\Throwable $e) {
            return response()->json("Login Failed " . $e->getMessage(), 500);
        }
    }

    // Logout
    public function logout()
    {
        Auth::logout();
        return response()->json(['message' => 'Successfully logged out']);
    }
}
