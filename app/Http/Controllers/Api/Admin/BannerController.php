<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Banner;
use App\Http\Requests\BannerRequest;
use Illuminate\Support\Facades\Storage;
use App\Helpers\ResponseHelper;

class BannerController extends Controller
{
    public function index()
    {
        try {
            $banners = Banner::latest()->get();
            return ResponseHelper::success($banners, 'Banners fetched successfully.');
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }

    public function store(BannerRequest $request)
    {
        try {
            $path = null;

            if ($request->hasFile('image')) {
                $path = $request->file('image')->store('banners', 'public');
            }

            $banner = Banner::create(['image' => $path]);

            return ResponseHelper::success($banner, 'Banner created successfully.');
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }

    public function update(BannerRequest $request, $id)
    {
        try {
            $banner = Banner::findOrFail($id);

            if ($request->hasFile('image')) {
                if ($banner->image) {
                    Storage::disk('public')->delete($banner->image);
                }

                $path = $request->file('image')->store('banners', 'public');
                $banner->update(['image' => $path]);
            }

            return ResponseHelper::success($banner, 'Banner updated successfully.');
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }

    public function destroy($id)
    {
        try {
            $banner = Banner::findOrFail($id);

            if ($banner->image) {
                Storage::disk('public')->delete($banner->image);
            }

            $banner->delete();

            return ResponseHelper::success(null, 'Banner deleted successfully.');
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }

    public function show($id)
    {
        try {
            $banner = Banner::findOrFail($id);
            return ResponseHelper::success($banner, 'Banner details retrieved.');
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }
}
