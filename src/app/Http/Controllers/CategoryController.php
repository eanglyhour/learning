<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class CategoryController extends Controller
{
    public function index(Request $request)
    {
        try {
            $perPage = $request->per_page ?? 5;

            $query = Category::query();

            if ($request->search) {
                $query->where(function ($q) use ($request) {
                    $q->where('name', 'like', '%' . $request->search . '%')
                        ->orWhere('slug', 'like', '%' . $request->search . '%');
                });
            }

            if ($request->status) {
                $query->whereIn('status', (array) $request->status);
            }

            $query->orderByRaw("
            CASE 
                WHEN status = 1 THEN 1
                WHEN status = 0 THEN 2
                ELSE 3
            END
        ");

            $categories = $query->latest()->paginate($perPage);

            return response()->json([
                'data' => $categories->items()
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 500);
        }
    }
    public function show($id)
    {
        try {
            $category_id = Category::find($id);
            if (!$category_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Category not found'
                ], 404);
            }

            return response()->json($category_id);
        } catch (\Throwable $e) { // /Throwable $e

            return response()->json([
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $request->validate([
                "name" => "required|string|max:100",
                "status" => "nullable|boolean"
            ]);

            $slug = Str::slug($request->name);
            $count = Category::where('slug', 'LIKE', "{$slug}%")->count();
            if ($count > 0) {
                $slug .= '-' . ($count + 1);
            }
            $category = Category::create([
                'name' => $request->name,
                'slug' => $slug,
                'status' => isset($request->status) ? $request->status : 1,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Category created successfully',
                'data' => $category
            ], 201); // 201 = Created

        } catch (\Throwable $e) { // /Throwable $e

            return response()->json([
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $category = Category::find($id);

            if (!$category) {
                return response()->json([
                    'success' => false,
                    'message' => 'Category not found'
                ], 404);
            }

            $request->validate([
                'name' => 'sometimes|string|max:100',
                'status' => 'nullable|boolean'
            ]);

            // $data = $request->only(['name', 'status']);

            // if (isset($data['name'])) {
            //     $data['slug'] = Str::slug($data['name']);
            // }

            $data = [];

            // check if name exists
            if ($request->has('name') && $request->name !== $category->name) {
                $data['name'] = $request->name;

                // generate slug
                $slug = Str::slug($request->name);
                $count = Category::where('slug', 'LIKE', "{$slug}%")->count();

                if ($count > 0) {
                    $slug .= '-' . ($count + 1);
                }

                $data['slug'] = $slug;
            }

            // check status
            if ($request->has('status')) {
                $data['status'] = $request->status;
            }

            // update only fields that exist
            $category->update($data);

            if (empty($data)) {
                return response()->json([
                    'message' => 'No data to update'
                ], 400);
            }

            // $category->update($data);

            return response()->json([
                'message' => 'Updated successfully',
                'data' => $category
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Error updating category',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
