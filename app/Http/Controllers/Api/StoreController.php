<?php

namespace App\Http\Controllers\Api;

use App\Models\Store;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class StoreController extends Controller
{
    public function getAllStore(){
        $Stores = Store::all();
        return response()->json([
            'success' => 1,
            'data' => $Stores
        ], 200);
    }

    public function createStore(Request $request){
        $request->validate([
            'name' => 'required|string',
            'code' => 'required|string|unique:stores',
            'email' => 'nullable|string|email|unique:stores',
            'phone' => 'nullable|string',
            'manager' => 'nullable|string',
            'status' => 'required|in:active,inactive',
            'address' => 'nullable|string',
        ]);

        $store = Store::create([
            'name' => $request->name,
            'code' => $request->code,
            'email' => $request->email,
            'phone' => $request->phone,
            'manager' => $request->manager,
            'status' => $request->status,
            'address' => $request->address,
        ]);

        return response()->json([
            'success' => 1,
            'message' => 'Store created successfully',
            'data' => $store
        ], 201);
    }

    public function updateStore(Request $request, $id){
        $store = Store::find($id);
        if(!$store){
            return response()->json([
                'success' => 0,
                'error' => true,
                'message' => 'Store not found',
                'data' => null
            ], 404);
        }

        $request->validate([
            'name' => 'required|string',
            'code' => ['required', 'string', Rule::unique('stores')->ignore($store->id)],
            'email' => ['nullable', 'string', 'email', Rule::unique('stores')->ignore($store->id)],
            'phone' => 'nullable|string',
            'manager' => 'nullable|string',
            'status' => 'required|in:active,inactive',
            'address' => 'nullable|string',
        ]);

        $store->update($request->only([ 
            'name', 'code', 'email', 'phone', 'manager', 'status', 'address',
        ]));

        return response()->json([
            'success' => 1,
            'message' => 'Store updated successfully',
            'data' => $store
        ], 200);
    }

    public function viewStore($id){
        $store = Store::find($id);
        if(!$store){
            return response()->json([
                'success' => 0,
                'message' => 'Store not found'
            ], 404);
        }

        return response()->json([
            'success' => 1,
            'data' => $store
        ], 200);
    }

    public function deleteStore($id){
        $store = Store::find($id);
        if(!$store){
            return response()->json([
                'success' => 0,
                'message' => 'Store not found'
            ], 404);
        }

        $store->delete();

        return response()->json([
            'success' => 1,
            'message' => 'Store deleted successfully'
        ], 200);
    }
}
