<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\MaterialCategory;
use App\Helpers\ResponseHelper;
use Illuminate\Http\Request;
use Exception;

class MaterialCategoryController extends Controller
{
    /**
     * Display a listing of material categories
     */
    public function index()
    {
        try {
            $categories = MaterialCategory::with('materials')
                ->orderBy('sort_order')
                ->get();
            return ResponseHelper::success($categories, 'Material categories fetched successfully.');
        } catch (Exception $e) {
            return ResponseHelper::error('Failed to fetch material categories.', 500);
        }
    }

    /**
     * Store a newly created material category
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'code' => 'nullable|string|max:10|unique:material_categories,code',
                'description' => 'nullable|string',
                'sort_order' => 'nullable|integer|min:0',
                'is_active' => 'nullable|boolean',
            ]);

            $category = MaterialCategory::create($validated);
            return ResponseHelper::success($category, 'Material category created successfully.', 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed.',
                'errors' => $e->errors()
            ], 422);
        } catch (Exception $e) {
            return ResponseHelper::error('Failed to create material category.', 500);
        }
    }

    /**
     * Display the specified material category
     */
    public function show($id)
    {
        try {
            $category = MaterialCategory::with('materials')->find($id);
            if (!$category) {
                return ResponseHelper::error('Material category not found.', 404);
            }
            return ResponseHelper::success($category, 'Material category fetched successfully.');
        } catch (Exception $e) {
            return ResponseHelper::error('Failed to fetch material category.', 500);
        }
    }

    /**
     * Update the specified material category
     */
    public function update(Request $request, $id)
    {
        try {
            $category = MaterialCategory::find($id);
            if (!$category) {
                return ResponseHelper::error('Material category not found.', 404);
            }

            $validated = $request->validate([
                'name' => 'sometimes|required|string|max:255',
                'code' => 'sometimes|nullable|string|max:10|unique:material_categories,code,' . $id,
                'description' => 'nullable|string',
                'sort_order' => 'nullable|integer|min:0',
                'is_active' => 'nullable|boolean',
            ]);

            $category->update($validated);
            return ResponseHelper::success($category->fresh(), 'Material category updated successfully.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed.',
                'errors' => $e->errors()
            ], 422);
        } catch (Exception $e) {
            return ResponseHelper::error('Failed to update material category.', 500);
        }
    }

    /**
     * Remove the specified material category
     */
    public function destroy($id)
    {
        try {
            $category = MaterialCategory::find($id);
            if (!$category) {
                return ResponseHelper::error('Material category not found.', 404);
            }

            // Check if category has materials
            if ($category->materials()->count() > 0) {
                return ResponseHelper::error('Cannot delete category with existing materials.', 400);
            }

            $category->delete();
            return ResponseHelper::success(null, 'Material category deleted successfully.');
        } catch (Exception $e) {
            return ResponseHelper::error('Failed to delete material category.', 500);
        }
    }
}
