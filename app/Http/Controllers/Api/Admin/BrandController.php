<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\BrandRequest;
use App\Models\Brand;
use App\Helpers\ResponseHelper;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Exception;

class BrandController extends Controller
{
    public function index()
    {
        try {
            $brands = Brand::all();
            return ResponseHelper::success($brands, 'Brands fetched.');
        } catch (Exception $e) {
            Log::error('Error fetching brands: ' . $e->getMessage());
            return ResponseHelper::error('Something went wrong.', 500);
        }
    }

    public function store(BrandRequest $request)
    {
        try {
            $data = $request->validated();

            try {
                if ($request->hasFile('icon')) {
                    $file = $request->file('icon');
                    $name = Str::uuid() . '.' . $file->getClientOriginalExtension();
                    $data['icon'] = Storage::url($file->storeAs('public/brands', $name));
                }
            } catch (Exception $e) {
                Log::error('Error storing icon: ' . $e->getMessage());
                return ResponseHelper::error('Failed to upload brand icon.', 500);
            }

            $brand = Brand::create($data);
            return ResponseHelper::success($brand, 'Brand created.', 201);
        } catch (Exception $e) {
            Log::error('Error creating brand: ' . $e->getMessage());
            return ResponseHelper::error('Failed to create brand.', 500);
        }
    }

    public function show($id)
    {
        try {
            $brand = Brand::find($id);
            if (!$brand) {
                return ResponseHelper::error('Brand not found.', 404);
            }

            return ResponseHelper::success($brand, 'Brand found.');
        } catch (Exception $e) {
            Log::error('Error showing brand: ' . $e->getMessage());
            return ResponseHelper::error('Something went wrong.', 500);
        }
    }

    public function update(BrandRequest $request, $id)
    {
        try {
            $brand = Brand::find($id);
            if (!$brand) return ResponseHelper::error('Brand not found.', 404);

            $data = $request->validated();

            try {
                if ($request->hasFile('icon')) {
                    if ($brand->icon) {
                        try {
                            $old = str_replace('/storage/', 'public/', $brand->icon);
                            Storage::delete($old);
                        } catch (Exception $e) {
                            Log::warning('Old icon could not be deleted: ' . $e->getMessage());
                        }
                    }

                    $file = $request->file('icon');
                    $name = Str::uuid() . '.' . $file->getClientOriginalExtension();
                    $data['icon'] = Storage::url($file->storeAs('public/brands', $name));
                }
            } catch (Exception $e) {
                Log::error('Error handling icon update: ' . $e->getMessage());
                return ResponseHelper::error('Failed to update brand icon.', 500);
            }

            $brand->update($data);
            return ResponseHelper::success($brand, 'Brand updated.');
        } catch (Exception $e) {
            Log::error('Error updating brand: ' . $e->getMessage());
            return ResponseHelper::error('Failed to update brand.', 500);
        }
    }

    public function destroy($id)
    {
        try {
            $brand = Brand::find($id);
            if (!$brand) return ResponseHelper::error('Brand not found.', 404);

            try {
                if ($brand->icon) {
                    $old = str_replace('/storage/', 'public/', $brand->icon);
                    Storage::delete($old);
                }
            } catch (Exception $e) {
                Log::warning('Could not delete brand icon: ' . $e->getMessage());
            }

            $brand->delete();
            return ResponseHelper::success(null, 'Brand deleted.');
        } catch (Exception $e) {
            Log::error('Error deleting brand: ' . $e->getMessage());
            return ResponseHelper::error('Failed to delete brand.', 500);
        }
    }




    public function getByCategory($categoryId)
{
    try {
        // Verify category exists
        $category = \App\Models\Category::find($categoryId);
        if (!$category) {
            return ResponseHelper::error('Category not found.', 404);
        }

        $brands = Brand::where('category_id', $categoryId)->get();

        // Return empty array instead of error if no brands found
        return ResponseHelper::success($brands, $brands->isEmpty() 
            ? 'No brands found for this category.' 
            : 'Brands fetched by category.');

    } catch (Exception $e) {
        \Log::error('Error fetching brands by category: ' . $e->getMessage());
        return ResponseHelper::error('Failed to fetch brands by category.', 500);
    }
}



public function showBrandByCategory($categoryId, $brandId)
{
    try {
        $brand = Brand::where('category_id', $categoryId)
                      ->where('id', $brandId)
                      ->first();

        if (!$brand) {
            return ResponseHelper::error('Brand not found in this category.', 404);
        }

        return ResponseHelper::success($brand, 'Brand fetched successfully.');
    } catch (\Exception $e) {
        return ResponseHelper::error('Failed to fetch brand.', 500, $e->getMessage());
    }
}


}
