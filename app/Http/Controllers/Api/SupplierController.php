<?php

namespace App\Http\Controllers\Api;

use App\Models\Supplier;
use App\Http\Controllers\Controller;
use App\Services\ActivityLogService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SupplierController extends Controller
{
    public function getAllSuppliers(){
        $suppliers = Supplier::all();
        return response()->json([
            'success' => 1,
            'data' => $suppliers
        ], 200);
    }

    public function createSupplier(Request $request){
        $request->validate([
            'name' => 'required|string',
            'company' => 'nullable|string',
            'email' => 'required|string|email|unique:suppliers',
            'phone' => 'nullable|string',
            'taxNumber' => 'nullable|string',
            'status' => 'required|in:active,inactive',
            'address' => 'nullable|string',
            'opening_balance' => 'nullable|numeric',
        ]);

        $supplier = Supplier::create([
            'name' => $request->name,
            'company' => $request->company,
            'email' => $request->email,
            'phone' => $request->phone,
            'taxNumber' => $request->taxNumber,
            'status' => $request->status,
            'address' => $request->address,
            'opening_balance' => $request->opening_balance,
        ]);

        ActivityLogService::log('Add', 'Suppliers', "Added new supplier \"{$supplier->name}\"");
        return response()->json(['success' => 1, 'message' => 'Supplier created successfully', 'data' => $supplier], 201);
    }

    public function updateSupplier(Request $request, $id){
        $supplier = Supplier::find($id);
        if(!$supplier){
            return response()->json([
                'success' => 0,
                'error' => true,
                'message' => 'Supplier not found',
                'data' => null
            ], 404);
        }

        $request->validate([
            'name' => 'required|string',
            'company' => 'nullable|string',
            'email' => ['required','string','email', Rule::unique('suppliers')->ignore($id)],
            'phone' => 'nullable|string',
            'taxNumber' => 'nullable|string',
            'status' => 'required|in:active,inactive',
            'address' => 'nullable|string',
            'opening_balance' => 'nullable|numeric',
        ]);

        $supplier->update($request->only([
            'name','company','email','phone','taxNumber','status','address','opening_balance'
        ]));

        ActivityLogService::log('Edit', 'Suppliers', "Updated supplier \"{$supplier->name}\"");
        return response()->json(['success' => 1, 'message' => 'Supplier updated successfully', 'data' => $supplier], 200);
    }

    public function viewSupplier($id){
        $supplier = Supplier::find($id);
        if(!$supplier){
            return response()->json([
                'success' => 0,
                'message' => 'Supplier not found'
            ], 404);
        }

        return response()->json([
            'success' => 1,
            'message' => 'Supplier retrieved successfully',
            'data' => $supplier
        ], 200);
    }

    public function deleteSupplier($id){
        $supplier = Supplier::find($id);
        if(!$supplier){
            return response()->json([
                'success' => 0,
                'message' => 'Supplier not found'
            ], 404);
        }

        $name = $supplier->name;
        $supplier->delete();
        ActivityLogService::log('Delete', 'Suppliers', "Deleted supplier \"{$name}\"");
        return response()->json(['success' => 1, 'message' => 'Supplier deleted successfully'], 200);
    }
}
