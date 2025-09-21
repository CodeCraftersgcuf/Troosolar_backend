<?php

namespace App\Http\Controllers\Api\Admin;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\ProductRequest;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    public function index()
    {
        try {
            $products = Product::with(['details', 'images','reviews'])->get();
            return ResponseHelper::success($products, 'Products fetched successfully.');
        } catch (Exception $e) {
            return ResponseHelper::error('Failed to fetch products.', 500, $e->getMessage());
        }
    }
    public function topProducts()
    {
        try {
            $products =Product::where('top_deal', true)->with(['details', 'images','reviews'])->get();
            return ResponseHelper::success($products, 'Top products fetched successfully.');
        } catch (Exception $e) {
            return ResponseHelper::error('Failed to fetch top products.', 500, $e->getMessage());
        }
    }

  public function show($id)
{
    try {
        $product = Product::with(['details', 'images', 'reviews'])->find($id);

        return $product
            ? ResponseHelper::success($product, 'Product fetched successfully.')
            : ResponseHelper::error('Product not found.', 404);
    } catch (Exception $e) {
        return ResponseHelper::error('Failed to fetch product.', 500, $e->getMessage());
    }
}

    public function store(ProductRequest $request)
    {
        try {
            $data = $this->processRequest($request);
            $product = Product::create($data);

            $this->handleImages($product, $request);
            $this->saveDetails($product, $request->product_details ?? []);
            return ResponseHelper::success(
                $product->refresh()->load(['details', 'images']),
                'Product created successfully.',
                201
            );
        } catch (Exception $e) {
            return ResponseHelper::error('Failed to create product.', 500, $e->getMessage());
        }
    }

    public function update(ProductRequest $request, $id)
    {
        try {
            $product = Product::find($id);
            if (!$product) return ResponseHelper::error('Product not found.', 404);

            $data = $this->processRequest($request, $product);
            $product->update($data);

            $product->details()->delete();
            $this->deleteGalleryImages($product);

            $this->handleImages($product, $request);
            $this->saveDetails($product, $request->product_details ?? []);

            return ResponseHelper::success(
                $product->refresh()->load(['details', 'images']),
                'Product updated successfully.'
            );
        } catch (Exception $e) {
            return ResponseHelper::error('Failed to update product.', 500, $e->getMessage());
        }
    }

    public function destroy($id)
    {
        try {
            $product = Product::find($id);
            if (!$product) return ResponseHelper::error('Product not found.', 404);

            $this->deleteImage($product->featured_image);
            $this->deleteGalleryImages($product);
            $product->details()->delete();
            $product->delete();

            return ResponseHelper::success(null, 'Product deleted successfully.');
        } catch (Exception $e) {
            return ResponseHelper::error('Failed to delete product.', 500, $e->getMessage());
        }
    }

    // ======================
    // HELPERS
    // ======================

    private function processRequest($request, $product = null)
    {
        $data = $request->validated();

        if ($request->hasFile('featured_image')) {
            if ($product) $this->deleteImage($product->featured_image);
            $data['featured_image'] = $this->uploadImage($request->file('featured_image'), 'products');
        }

        return $data;
    }

    private function uploadImage($file, $folder)
    {
        $name = Str::uuid() . '.' . $file->getClientOriginalExtension();
        return Storage::url($file->storeAs("public/{$folder}", $name));
    }

    private function deleteImage($url)
    {
        if ($url) {
            $path = str_replace('/storage/', 'public/', $url);
            if (Storage::exists($path)) Storage::delete($path);
        }
    }

    private function handleImages($product, $request)
    {
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $file) {
                $path = $this->uploadImage($file, 'products/gallery');
                $product->images()->create(['image' => $path]);
            }
        }
    }

    private function deleteGalleryImages($product)
    {
        foreach ($product->images as $img) {
            $this->deleteImage($img->image);
            $img->delete();
        }
    }

    private function saveDetails($product, $details)
    {
        foreach ($details as $item) {
            $text = is_array($item) ? ($item['detail'] ?? null) : $item;
            if (is_string($text) && trim($text)) {
                $product->details()->create(['detail' => $text]);
            }
        }
    }







public function getProductsByBrand($ids)
{
    try {
        // Convert comma-separated IDs into array
        $brandIds = explode(',', $ids);

        $brands = Brand::with('products.reviews')->whereIn('id', $brandIds)->get();

        if ($brands->isEmpty()) {
            return ResponseHelper::error('No brands found.', 404);
        }

        // Merge all products into one collection
        $products = $brands->pluck('products')->flatten();

        return ResponseHelper::success($products, 'Products fetched by brand(s).');
    } catch (\Exception $e) {
        return ResponseHelper::error('Failed to fetch products.', 500, $e->getMessage());
    }
}


public function showProductByBrand($brandIds, $productId)
{
    try {
        $brandIds = explode(',', $brandIds);

        $product = Product::whereIn('brand_id', $brandIds)
                          ->where('id', $productId)
                          ->with(['details', 'images','reviews'])
                          ->first();

        if (!$product) {
            return ResponseHelper::error('Product not found under these brand(s).', 404);
        }

        return ResponseHelper::success($product, 'Product fetched successfully.');
    } catch (\Exception $e) {
        return ResponseHelper::error('Failed to fetch product.', 500, $e->getMessage());
    }
}




public function showProductByCategory(
    $categoryId, 
    $brandIds = null,
    $productId
) {
    try {
        $query = Product::where('category_id', $categoryId)
                    //   ->where('brand_id', $brandIds)
                        ->where('id', $productId)
                        ->with(['details', 'images', 'reviews']);

        if ($brandIds) {
            // $brandIdsArray = explode(',', $brandIds);
            $query->where('brand_id', $brandIds);
        }

        $product = $query->first();

        if (!$product) {
            return ResponseHelper::error('Product not found under this category/brand.', 404);
        }

        return ResponseHelper::success($product, 'Product found with full details2.');

    } catch (\Exception $e) {
        return ResponseHelper::error('Failed to fetch product.', 500, $e->getMessage());
    }
}
public function showProductByCategoryBrand(
    $categoryId, 
    $productId
) {
    try {
        $query = Product::where('category_id', $categoryId)
                        ->where('id', $productId)
                        ->with(['details', 'images', 'reviews']);

        $product = $query->first();

        if (!$product) {
            return ResponseHelper::error('Product not found under this category/brand.', 404);
        }

        return ResponseHelper::success($product, 'Product found with category and full details.');

    } catch (\Exception $e) {
        return ResponseHelper::error('Failed to fetch product.', 500, $e->getMessage());
    }
}





}