<?php

namespace App\Http\Controllers\Api;

use App\Models\Product;
use App\Models\Category;
use App\Models\Brand;
use App\Http\Controllers\Controller;
use App\Services\ActivityLogService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProductController extends Controller
{
    /**
     * Resolve category/brand: accept either an ID (integer string) or a name string.
     * Always stores the name in the products table (matching the Category/Brand model relationship).
     */
    private function resolveCategoryName($value): ?string
    {
        if (!$value) return null;
        if (is_numeric($value)) {
            $cat = Category::find((int) $value);
            return $cat ? $cat->name : null;
        }
        return $value;
    }

    private function resolveBrandName($value): ?string
    {
        if (!$value) return null;
        if (is_numeric($value)) {
            $brand = Brand::find((int) $value);
            return $brand ? $brand->name : null;
        }
        return $value;
    }

    public function getAllProducts()
    {
        $categories = Category::pluck('id', 'name'); // ['Electronics' => 1, ...]
        $brands     = Brand::pluck('id', 'name');

        $products = Product::all()->map(function ($product) use ($categories, $brands) {
            $product->image       = $product->image ? asset('storage/' . $product->image) : null;
            $product->category_id = $categories[$product->category] ?? null;
            $product->brand_id    = $brands[$product->brand] ?? null;
            return $product;
        });
        return response()->json(['success' => 1, 'data' => $products], 200);
    }

    public function createProduct(Request $request)
    {
        $request->validate([
            'name'           => 'required|string',
            'sku'            => 'required|string|unique:products',
            'category_id'    => 'required',
            'brand_id'       => 'nullable',
            'unit'           => 'nullable|string',
            'purchase_price' => 'required|numeric',
            'selling_price'  => 'required|numeric',
            'quantity'       => 'required|integer',
            'alert_quantity' => 'nullable|integer',
            'tax'            => 'nullable|numeric',
            'description'    => 'nullable|string',
            'image'          => 'nullable|image|mimes:jpg,jpeg,png,gif|max:2048',
        ]);

        $categoryName = $this->resolveCategoryName($request->category_id ?? $request->category);
        $brandName    = $this->resolveBrandName($request->brand_id ?? $request->brand);

        if (!$categoryName) {
            return response()->json(['success' => 0, 'message' => 'Invalid category'], 422);
        }

        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('uploads/products', 'public');
        }

        $product = Product::create([
            'name'           => $request->name,
            'sku'            => $request->sku,
            'category'       => $categoryName,
            'brand'          => $brandName,
            'unit'           => $request->unit,
            'purchase_price' => $request->purchase_price,
            'selling_price'  => $request->selling_price,
            'quantity'       => $request->quantity,
            'alert_quantity' => $request->alert_quantity,
            'tax'            => $request->tax,
            'description'    => $request->description,
            'image'          => $imagePath,
        ]);

        ActivityLogService::log('Add', 'Products', "Added new product \"{$product->name}\"");
        $product->image = $product->image ? asset('storage/' . $product->image) : null;
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
            'category_id'    => 'required',
            'brand_id'       => 'nullable',
            'unit'           => 'nullable|string',
            'purchase_price' => 'required|numeric',
            'selling_price'  => 'required|numeric',
            'quantity'       => 'required|integer',
            'alert_quantity' => 'nullable|integer',
            'tax'            => 'nullable|numeric',
            'description'    => 'nullable|string',
            'image'          => 'nullable|image|mimes:jpg,jpeg,png,gif|max:2048',
        ]);

        $categoryName = $this->resolveCategoryName($request->category_id ?? $request->category);
        $brandName    = $this->resolveBrandName($request->brand_id ?? $request->brand);

        if (!$categoryName) {
            return response()->json(['success' => 0, 'message' => 'Invalid category'], 422);
        }

        if ($request->hasFile('image')) {
            $product->image = $request->file('image')->store('uploads/products', 'public');
        }

        $product->update([
            'name'           => $request->name,
            'sku'            => $request->sku,
            'category'       => $categoryName,
            'brand'          => $brandName,
            'unit'           => $request->unit,
            'purchase_price' => $request->purchase_price,
            'selling_price'  => $request->selling_price,
            'quantity'       => $request->quantity,
            'alert_quantity' => $request->alert_quantity,
            'tax'            => $request->tax,
            'description'    => $request->description,
        ]);

        if ($request->hasFile('image')) {
            $product->save();
        }

        ActivityLogService::log('Edit', 'Products', "Updated product \"{$product->name}\"");
        $product->image = $product->image ? asset('storage/' . $product->image) : null;
        return response()->json(['success' => 1, 'message' => 'Product updated successfully', 'data' => $product], 200);
    }

    public function viewProduct($id)
    {
        $product = Product::find($id);
        if (!$product) {
            return response()->json(['success' => 0, 'message' => 'Product not found'], 404);
        }

        $product->image       = $product->image ? asset('storage/' . $product->image) : null;
        $product->category_id = Category::where('name', $product->category)->value('id');
        $product->brand_id    = Brand::where('name', $product->brand)->value('id');

        return response()->json(['success' => 1, 'message' => 'Product retrieved successfully', 'data' => $product], 200);
    }

    public function deleteProduct($id)
    {
        $product = Product::find($id);
        if (!$product) {
            return response()->json(['success' => 0, 'message' => 'Product not found'], 404);
        }

        $name = $product->name;
        $product->delete();
        ActivityLogService::log('Delete', 'Products', "Deleted product \"{$name}\"");
        return response()->json(['success' => 1, 'message' => 'Product deleted successfully'], 200);
    }
}
