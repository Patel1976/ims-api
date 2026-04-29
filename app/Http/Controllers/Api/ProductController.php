<?php

namespace App\Http\Controllers\Api;

use App\Models\Product;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProductController extends Controller
{
    public function getAllProducts()
    {
        $products = Product::all();
        return response()->json(['success' => 1, 'data' => $products], 200);
    }

    public function createProduct(Request $request)
    {
        $request->validate([
            'name'           => 'required|string',
            'sku'            => 'required|string|unique:products',
            'category'       => 'required|string',
            'brand'          => 'nullable|string',
            'unit'           => 'nullable|string',
            'purchase_price' => 'required|numeric',
            'selling_price'  => 'required|numeric',
            'quantity'       => 'required|integer',
            'alert_quantity' => 'nullable|integer',
            'tax'            => 'nullable|numeric',
            'description'    => 'nullable|string',
            'image'          => 'nullable|image|mimes:jpg,jpeg,png,gif|max:2048',
        ]);

        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('uploads/products', 'public');
        }

        $product = Product::create([
            'name'           => $request->name,
            'sku'            => $request->sku,
            'category'       => $request->category,
            'brand'          => $request->brand,
            'unit'           => $request->unit,
            'purchase_price' => $request->purchase_price,
            'selling_price'  => $request->selling_price,
            'quantity'       => $request->quantity,
            'alert_quantity' => $request->alert_quantity,
            'tax'            => $request->tax,
            'description'    => $request->description,
            'image'          => $imagePath,
        ]);

        return response()->json(['success' => 1, 'message' => 'Product created successfully', 'data' => $product], 201);
    }

    public function updateProduct(Request $request, $id)
    {
        $product = Product::find($id);
        if (!$product) {
            return response()->json(['success' => 0, 'message' => 'Product not found'], 404);
        }

        $request->validate([
            'name'           => 'required|string',
            'sku'            => ['required', 'string', Rule::unique('products')->ignore($id)],
            'category'       => 'required|string',
            'brand'          => 'nullable|string',
            'unit'           => 'nullable|string',
            'purchase_price' => 'required|numeric',
            'selling_price'  => 'required|numeric',
            'quantity'       => 'required|integer',
            'alert_quantity' => 'nullable|integer',
            'tax'            => 'nullable|numeric',
            'description'    => 'nullable|string',
            'image'          => 'nullable|image|mimes:jpg,jpeg,png,gif|max:2048',
        ]);

        if ($request->hasFile('image')) {
            $product->image = $request->file('image')->store('uploads/products', 'public');
        }

        $product->update($request->only([
            'name', 'sku', 'category', 'brand', 'unit',
            'purchase_price', 'selling_price', 'quantity',
            'alert_quantity', 'tax', 'description',
        ]));

        if ($request->hasFile('image')) {
            $product->image = $request->file('image')->store('uploads/products', 'public');
            $product->save();
        }

        return response()->json(['success' => 1, 'message' => 'Product updated successfully', 'data' => $product], 200);
    }

    public function viewProduct($id)
    {
        $product = Product::find($id);
        if (!$product) {
            return response()->json(['success' => 0, 'message' => 'Product not found'], 404);
        }

        return response()->json(['success' => 1, 'message' => 'Product retrieved successfully', 'data' => $product], 200);
    }

    public function deleteProduct($id)
    {
        $product = Product::find($id);
        if (!$product) {
            return response()->json(['success' => 0, 'message' => 'Product not found'], 404);
        }

        $product->delete();

        return response()->json(['success' => 1, 'message' => 'Product deleted successfully'], 200);
    }
}
