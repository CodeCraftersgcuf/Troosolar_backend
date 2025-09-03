<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\CategoryRequest;
use App\Models\Category;
use App\Helpers\ResponseHelper;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Exception;

class CategoryController extends Controller
{
    public function index()
    {
        try {
            $categories = Category::all();
            return ResponseHelper::success($categories, 'Categories fetched.');
        } catch (Exception $e) {
            return ResponseHelper::error('Something went wrong.', 500, $e->getMessage());
        }
    }

    public function store(CategoryRequest $request)
    {
        try {
            $data = $request->validated();

            if ($request->hasFile('icon')) {
                $file = $request->file('icon');
                $name = Str::uuid() . '.' . $file->getClientOriginalExtension();
                $data['icon'] = Storage::url($file->storeAs('public/icons', $name));
            }

            $category = Category::create($data);
            return ResponseHelper::success($category, 'Category created.', 201);
        } catch (Exception $e) {
            return ResponseHelper::error('Failed to create category.', 500, $e->getMessage());
        }
    }

    public function show($id)
    {
        try {
            $category = Category::find($id);
            return $category
                ? ResponseHelper::success($category, 'Category found.')
                : ResponseHelper::error('Category not found.', 404);
        } catch (Exception $e) {
            return ResponseHelper::error('Failed to fetch category.', 500, $e->getMessage());
        }
    }

    public function update(CategoryRequest $request, $id)
    {
        try {
            $category = Category::find($id);
            if (!$category) return ResponseHelper::error('Category not found.', 404);

            $data = $request->validated();

            if ($request->hasFile('icon')) {
                if ($category->icon) {
                    $old = str_replace('/storage/', 'public/', $category->icon);
                    Storage::delete($old);
                }

                $file = $request->file('icon');
                $name = Str::uuid() . '.' . $file->getClientOriginalExtension();
                $data['icon'] = Storage::url($file->storeAs('public/icons', $name));
            }

            $category->update($data);
            return ResponseHelper::success($category, 'Category updated.');
        } catch (Exception $e) {
            return ResponseHelper::error('Failed to update category.', 500, $e->getMessage());
        }
    }

    public function destroy($id)
    {
        try {
            $category = Category::find($id);
            if (!$category) return ResponseHelper::error('Category not found.', 404);

            if ($category->icon) {
                $old = str_replace('/storage/', 'public/', $category->icon);
                Storage::delete($old);
            }

            $category->delete();
            return ResponseHelper::success(null, 'Category deleted.');
        } catch (Exception $e) {
            return ResponseHelper::error('Failed to delete category.', 500, $e->getMessage());
        }
    }



public function getProducts($id)
{
    try {
        $category = Category::with('brands.products.reviews')->find($id);

        if (!$category) {
            return ResponseHelper::error('Category not found.', 404);
        }

        $products = $category->brands->flatMap(function ($brand) {
            return $brand->products;
        });

        return ResponseHelper::success($products, 'Products fetched by category.');
    } catch (\Exception $e) {
        return ResponseHelper::error($e->getMessage(), 500); // TEMP for debugging
    }
}


}
