<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Log;

class UserController extends Controller
{
    //Get All Users
    public function getAllUsers(){
        $users = User::all();
        return response()->json([
            'success' => 1,
            'data' => $users
        ]);
    }

    //Create User
    public function createUser(Request $request){
        $request->validate([
            'name' => 'required|string',
            'username' => 'required|string|unique:users',
            'email' => 'required|string|email|unique:users',
            'password' => 'required|string|min:6',
            'phone' => 'nullable|string',
            'role'     => 'required|in:admin,staff,user',
            'image' => 'nullable|image|max:2048',
        ]);

        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')
                ->store('uploads/users', 'public');
        }

        $user = User::create([
            'name' => $request->name,
            'username' => $request->username,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'phone' => $request->phone,
            'role' => $request->role,
            'image' => $imagePath,
        ]);

        return response()->json([
            'success' => 1,
            'message' => 'User created successfully',
            'data' => $user
        ], 201);
    }

    //Update User
    public function updateUser(Request $request, $id){
        $user = User::find($id);
        if (!$user) {
            return response()->json([
                'success' => 0,
                'error' => true,
                'message' => 'User not found',
                'data' => null
            ], 404);
        }
        Log::info($request->method());
        Log::info($request->all());

        $request->validate([
            'name' => 'sometimes|required|string',
            'username' => ['sometimes','required','string',Rule::unique('users')->ignore($user->id)],
            'email' => ['sometimes','required','string','email',Rule::unique('users')->ignore($user->id)],
            'password' => 'sometimes|nullable|string|min:6',
            'phone' => 'nullable|string',
            'role'     => 'sometimes|required|in:admin,staff,user',
            'image' => 'nullable|image|max:2048',
        ]);

        if ($request->hasFile('image')) {
            if ($user->image) {
                Storage::disk('public')->delete($user->image);
            }
            $user->image = $request->file('image')->store('uploads/users', 'public');
        }

        if ($request->filled('password')) {
            $user->password = Hash::make($request->password);
        }

        $user->fill($request->only(['name', 'username', 'email', 'phone', 'role']));
        $user->save();

        return response()->json([
            'success' => 1,
            'message' => 'User updated successfully',
            'data' => $user
        ]);
    }

    //Delete User
    public function deleteUser($id){
        $user = User::find($id);
        if (!$user) {
            return response()->json([
                'success' => 0,
                'error' => true,
                'message' => 'User not found',
                'data' => null
            ], 404);
        }

        if ($user->image) {
            Storage::disk('public')->delete($user->image);
        }

        $user->delete();

        return response()->json([
            'success' => 1,
            'message' => 'User deleted successfully'
        ]);
    }
}
