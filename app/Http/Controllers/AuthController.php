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
        $request->validate([
            'identifier' => 'required|string',
            'password'   => 'required|string',
        ]);

        $identifier = $request->identifier;

        try {
            $user = User::where('email', $identifier)
                ->orWhere('username', $identifier)
                ->first();

            if (!$user) {
                return response()->json([
                    'success' => 0,
                    'error'   => 1,
                    'message' => 'Validation failed',
                    'data'    => ['errors' => ['identifier' => ['Username or email is incorrect.']]]
                ], 401);
            }

            if (!Hash::check($request->password, $user->password)) {
                return response()->json([
                    'success' => 0,
                    'error'   => 1,
                    'message' => 'Validation failed',
                    'data'    => ['errors' => ['password' => ['Password is incorrect.']]]
                ], 401);
            }

            $token = JWTAuth::fromUser($user);
            if (!$token) {
                return response()->json([
                    'success' => 0,
                    'error'   => 1,
                    'message' => 'Failed to generate token',
                    'data'    => null
                ], 500);
            }

            return response()->json([
                'success' => 1,
                'error'   => 0,
                'message' => 'Login Success',
                'data'    => ['token' => $token, 'userData' => $user]
            ], 200);

        } catch (\Throwable $e) {
            return response()->json('Login Failed ' . $e->getMessage(), 500);
        }
    }

    // Get authenticated user profile
    public function profile()
    {
        $user = Auth::user();
        return response()->json([
            'success' => 1,
            'data' => $user
        ]);
    }

    // Update authenticated user profile
    public function updateProfile(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'name'     => 'sometimes|required|string',
            'email'    => 'sometimes|required|email|unique:users,email,' . $user->id,
            'phone'    => 'nullable|string',
            'password' => 'sometimes|nullable|string|min:6',
            'image'    => 'nullable|image|max:2048',
        ]);

        if ($request->hasFile('image')) {
            if ($user->image) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($user->image);
            }
            $user->image = $request->file('image')->store('uploads/users', 'public');
        }

        if ($request->filled('password')) {
            $user->password = Hash::make($request->password);
        }

        $user->fill($request->only(['name', 'email', 'phone']));
        $user->save();

        return response()->json([
            'success' => 1,
            'message' => 'Profile updated successfully',
            'data'    => $user
        ]);
    }

    // Logout
    public function logout()
    {
        Auth::logout();
        return response()->json(['message' => 'Successfully logged out']);
    }
}
