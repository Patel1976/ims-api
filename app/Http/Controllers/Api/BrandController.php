<?php

namespace App\Http\Controllers\Api;

use App\Models\Brand;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BrandController extends Controller
{
    public function getAllBrands()
    {
        $brands = Brand::withCount('products')->get()->map(function ($brand) {
            $brand->products = $brand->products_count;
            $brand->logo = $brand->logo ? asset('storage/' . $brand->logo) : null;
            return $brand;
        });
        return response()->json(['success' => 1, 'data' => $brands], 200);
    }

    public function createBrand(Request $request)
    {
        $request->validate([
            'name'        => 'required|string|unique:brands',
            'logo'        => 'nullable|image|mimes:jpg,jpeg,png,gif|max:2048',
            'description' => 'nullable|string',
            'status'      => 'required|in:Active,Inactive',
        ]);

        $logoPath = null;
        if ($request->hasFile('logo')) {
            $logoPath = $request->file('logo')->store('uploads/brands', 'public');
        }

        $brand = Brand::create([
            'name'        => $request->name,
            'logo'        => $logoPath,
            'description' => $request->description,
            'status'      => $request->status,
        ]);

        return response()->json(['success' => 1, 'message' => 'Brand created successfully', 'data' => $brand], 201);
    }

    public function updateBrand(Request $request, $id)
    {
        $brand = Brand::find($id);
        if (!$brand) {
            return response()->json(['success' => 0, 'message' => 'Brand not found'], 404);
        }

        $request->validate([
            'name'        => ['required', 'string', Rule::unique('brands')->ignore($id)],
            'logo'        => 'nullable|image|mimes:jpg,jpeg,png,gif|max:2048',
            'description' => 'nullable|string',
            'status'      => 'required|in:Active,Inactive',
        ]);

        $brand->update($request->only(['name', 'description', 'status']));

        if ($request->hasFile('logo')) {
            $brand->logo = $request->file('logo')->store('uploads/brands', 'public');
            $brand->save();
        }

        return response()->json(['success' => 1, 'message' => 'Brand updated successfully', 'data' => $brand], 200);
    }

    public function viewBrand($id)
    {
        $brand = Brand::find($id);
        if (!$brand) {
            return response()->json(['success' => 0, 'message' => 'Brand not found'], 404);
        }

        return response()->json(['success' => 1, 'message' => 'Brand retrieved successfully', 'data' => $brand], 200);
    }

    public function deleteBrand($id)
    {
        $brand = Brand::find($id);
        if (!$brand) {
            return response()->json(['success' => 0, 'message' => 'Brand not found'], 404);
        }

        $brand->delete();

        return response()->json(['success' => 1, 'message' => 'Brand deleted successfully'], 200);
    }
}
