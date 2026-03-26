<?php

namespace App\Http\Controllers\Api;

use App\Models\Customer;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CustomerController extends Controller
{
    public function getAllCustomer(){
        $customers = Customer::all();
        return response()->json([
            'success' => 1,
            'data' => $customers
        ], 200);
    }

    public function createCustomer(Request $request){
        $request->validate([
            'name' => 'required|string',
            'email' => 'required|string|email|unique:customers',
            'phone' => 'nullable|string',
            'city' => 'nullable|string',
            'country' => 'nullable|string',
            'status' => 'required|in:active,inactive',
            'address' => 'nullable|string',
            'opening_balance' => 'nullable|numeric',
        ]);

        $customer = Customer::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'city' => $request->city,
            'country' => $request->country,
            'status' => $request->status,
            'address' => $request->address,
            'opening_balance' => $request->opening_balance,
        ]);

        return response()->json([
            'success' => 1,
            'message' => 'Customer created successfully',
            'data' => $customer
        ], 201);
    }

    public function updateCustomer(Request $request, $id){
        $customer = Customer::find($id);
        if(!$customer){
            return response()->json([
                'success' => 0,
                'error' => true,
                'message' => 'Customer not found',
                'data' => null
            ], 404);
        }

        $request->validate([
            'name' => 'required|string',
            'email'   => ['required', 'string', 'email', Rule::unique('customers')->ignore($customer->id)],
            'phone' => 'nullable|string',
            'city' => 'nullable|string',
            'country' => 'nullable|string',
            'status' => 'required|in:active,inactive',
            'address' => 'nullable|string',
            'opening_balance' => 'nullable|numeric',
        ]);

        $customer->update($request->only([
            'name', 'email', 'phone', 'city', 'country', 'status', 'address', 'opening_balance'
        ]));

        return response()->json([
            'success' => 1,
            'message' => 'Customer updated successfully',
            'data' => $customer
        ], 200);
    }

    public function viewCustomer($id){
        $customer = Customer::find($id);
        if(!$customer){
            return response()->json([
                'success' => 0,
                'message' => 'Customer not found'
            ], 404);
        }

        return response()->json([
            'success' => 1,
            'message' => 'Customer retrieved successfully',
            'data' => $customer
        ], 200);
    }

    public function deleteCustomer($id){
        $customer = Customer::find($id);
        if(!$customer){
            return response()->json([
                'success' => 0,
                'message' => 'Customer not found'
            ], 404);
        }

        $customer->delete();

        return response()->json([
            'success' => 1,
            'message' => 'Customer deleted successfully'
        ], 200);
    }
}
