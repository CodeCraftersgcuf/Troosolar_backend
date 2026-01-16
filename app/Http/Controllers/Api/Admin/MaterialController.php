<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Material;
use App\Models\MaterialCategory;
use App\Helpers\ResponseHelper;
use Illuminate\Http\Request;
use Exception;

class MaterialController extends Controller
{
    /**
     * Display a listing of materials
     */
    public function index(Request $request)
    {
        try {
            $query = Material::with('category');

            // Filter by category if provided
            if ($request->has('category_id')) {
                $query->where('material_category_id', $request->category_id);
            }

            // Filter by active status
            if ($request->has('is_active')) {
                $query->where('is_active', $request->is_active);
            }

            // Search by name
            if ($request->has('search')) {
                $query->where('name', 'like', '%' . $request->search . '%');
            }

            $materials = $query->orderBy('sort_order')->orderBy('name')->get();
            return ResponseHelper::success($materials, 'Materials fetched successfully.');
        } catch (Exception $e) {
            return ResponseHelper::error('Failed to fetch materials.', 500);
        }
    }

    /**
     * Store a newly created material
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'material_category_id' => 'required|exists:material_categories,id',
                'name' => 'required|string|max:255',
                'unit' => 'required|string|max:50',
                'warranty' => 'nullable|integer|min:0',
                'rate' => 'nullable|numeric|min:0',
                'selling_rate' => 'nullable|numeric|min:0',
                'profit' => 'nullable|numeric',
                'sort_order' => 'nullable|integer|min:0',
                'is_active' => 'nullable|boolean',
            ]);

            // Auto-calculate profit if not provided
            if (!isset($validated['profit']) && isset($validated['rate']) && isset($validated['selling_rate'])) {
                $validated['profit'] = $validated['selling_rate'] - $validated['rate'];
            }

            $material = Material::create($validated);
            return ResponseHelper::success($material->load('category'), 'Material created successfully.', 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed.',
                'errors' => $e->errors()
            ], 422);
        } catch (Exception $e) {
            return ResponseHelper::error('Failed to create material.', 500);
        }
    }

    /**
     * Display the specified material
     */
    public function show($id)
    {
        try {
            $material = Material::with('category')->find($id);
            if (!$material) {
                return ResponseHelper::error('Material not found.', 404);
            }
            return ResponseHelper::success($material, 'Material fetched successfully.');
        } catch (Exception $e) {
            return ResponseHelper::error('Failed to fetch material.', 500);
        }
    }

    /**
     * Update the specified material
     */
    public function update(Request $request, $id)
    {
        try {
            $material = Material::find($id);
            if (!$material) {
                return ResponseHelper::error('Material not found.', 404);
            }

            $validated = $request->validate([
                'material_category_id' => 'sometimes|required|exists:material_categories,id',
                'name' => 'sometimes|required|string|max:255',
                'unit' => 'sometimes|required|string|max:50',
                'warranty' => 'nullable|integer|min:0',
                'rate' => 'nullable|numeric|min:0',
                'selling_rate' => 'nullable|numeric|min:0',
                'profit' => 'nullable|numeric',
                'sort_order' => 'nullable|integer|min:0',
                'is_active' => 'nullable|boolean',
            ]);

            // Auto-calculate profit if rate or selling_rate changed
            if (isset($validated['rate']) || isset($validated['selling_rate'])) {
                $rate = $validated['rate'] ?? $material->rate;
                $sellingRate = $validated['selling_rate'] ?? $material->selling_rate;
                if (!isset($validated['profit'])) {
                    $validated['profit'] = $sellingRate - $rate;
                }
            }

            $material->update($validated);
            return ResponseHelper::success($material->fresh()->load('category'), 'Material updated successfully.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed.',
                'errors' => $e->errors()
            ], 422);
        } catch (Exception $e) {
            return ResponseHelper::error('Failed to update material.', 500);
        }
    }

    /**
     * Remove the specified material
     */
    public function destroy($id)
    {
        try {
            $material = Material::find($id);
            if (!$material) {
                return ResponseHelper::error('Material not found.', 404);
            }

            $material->delete();
            return ResponseHelper::success(null, 'Material deleted successfully.');
        } catch (Exception $e) {
            return ResponseHelper::error('Failed to delete material.', 500);
        }
    }

    /**
     * Get materials by category
     */
    public function getByCategory($categoryId)
    {
        try {
            $category = MaterialCategory::find($categoryId);
            if (!$category) {
                return ResponseHelper::error('Material category not found.', 404);
            }

            $materials = Material::where('material_category_id', $categoryId)
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get();

            return ResponseHelper::success($materials, 'Materials fetched by category successfully.');
        } catch (Exception $e) {
            return ResponseHelper::error('Failed to fetch materials by category.', 500);
        }
    }
}
