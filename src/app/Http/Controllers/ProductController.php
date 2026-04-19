<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    // format response
    private function format($product)
    {
        return [
            'id'       => $product->id,
            'name'     => $product->name,
            'price'    => $product->price,
            'stock'    => $product->stock,
            'image'    => $product->image ? asset('storage/' . $product->image) : null,
            'category' => $product->category ? [
                'id'   => $product->category->id,
                'name' => $product->category->name
            ] : null
        ];
    }

    // GET ALL
    public function index(Request $request)
    {
        $query = Product::with('category');

        if ($request->category_id) {
            $query->where('category_id', $request->category_id);
        }

        $products = $query->latest()->get()
            ->map(fn($p) => $this->format($p));

        return response()->json([
            'success' => true,
            'data' => $products
        ]);
    }

    // STORE
    public function store(Request $request)
    {
        $request->validate([
            'name'        => 'required|string|max:255',
            'price'       => 'required|numeric',
            'stock'       => 'required|integer',
            'category_id' => 'required|exists:categories,id', //important
            'image'       => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048'
        ]);

        //double check (optional but safe)
        $category = Category::find($request->category_id);
        if (!$category) {
            return response()->json([
                'success' => false,
                'message' => 'Category not found'
            ], 404);
        }

        $imagePath = $this->handleImage($request);

        $product = Product::create([
            'name'        => $request->name,
            'price'       => $request->price,
            'stock'       => $request->stock,
            'category_id' => $category->id,
            'image'       => $imagePath,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Created successfully',
            'data' => $this->format($product)
        ]);
    }

    // SHOW
    public function show($id)
    {
        $product = Product::with('category')->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $this->format($product)
        ]);
    }

    // UPDATE
    public function update(Request $request, $id)
    {
        $product = Product::findOrFail($id);

        $request->validate([
            'name'        => 'sometimes|string|max:255',
            'price'       => 'sometimes|numeric',
            'stock'       => 'sometimes|integer',
            'category_id' => 'sometimes|exists:categories,id',
            'image'       => 'sometimes|image|mimes:jpg,jpeg,png,webp|max:2048'
        ]);

        $data = [];

        // name
        if ($request->has('name')) {
            $data['name'] = $request->name;
        }
        if ($request->has('price')) {
            $data['price'] = $request->price;
        }
        if ($request->has('stock')) {
            $data['stock'] = $request->stock;
        }
        if ($request->has('category_id')) {
            $category = Category::find($request->category_id);

            if (!$category) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid category'
                ], 400);
            }

            $data['category_id'] = $category->id;
        }
        if ($request->hasFile('image')) {

            if ($product->image) {
                Storage::disk('public')->delete($product->image);
            }

            $data['image'] = $this->handleImage($request);
        }

        $product->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Updated successfully',
            'data' => $this->format($product)
        ]);
    }

    public function destroy($id)
    {
        $product = Product::findOrFail($id);

        if ($product->image) {
            Storage::disk('public')->delete($product->image);
        }

        $product->delete();

        return response()->json([
            'success' => true,
            'message' => 'Deleted successfully'
        ]);
    }
    private function handleImage(Request $request)
    {
        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $filename = Str::random(10) . '_' . time() . '.' . $file->getClientOriginalExtension();

            return $file->storeAs('products', $filename, 'public');
        }

        return null;
    }
}
