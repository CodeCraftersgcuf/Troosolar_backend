<?php

namespace App\Http\Controllers\Api\Website;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreDeliveryAddressRequest;
use App\Models\DeliveryAddress;
use Illuminate\Http\Request;
use App\Helpers\ResponseHelper;
use Exception;
use Illuminate\Support\Facades\Log;

class DeliveryAddressController extends Controller
{
   public function index(Request $request)
{
    try {
        $addresses = DeliveryAddress::where('user_id', auth()->id())->get();
        return ResponseHelper::success('Delivery addresses fetched successfully', $addresses);
    } catch (Exception $e) {
        Log::error("Index Error: " . $e->getMessage());
        return ResponseHelper::error('Failed to fetch addresses', 500);
    }
}


 public function store(StoreDeliveryAddressRequest $request)
{
    try {
        $data = $request->validated();
        $data['user_id'] = auth()->id(); // Automatically set user_id
        $address = DeliveryAddress::create($data);

        return ResponseHelper::success('Address created successfully', $address);
    } catch (Exception $e) {
        Log::error("Store Error: " . $e->getMessage());
        return ResponseHelper::error('Failed to store address', 500);
    }
}


public function update(Request $request, $id)
{
    try {
        $request->validate([
            'phone_number' => 'required|string|max:20',
            'title' => 'required|string|max:100',
            'address' => 'required|string|max:255',
            'state' => 'required|string|max:100',
        ]);

        $deliveryAddress = DeliveryAddress::where('user_id', auth()->id())->findOrFail($id);
        $deliveryAddress->update($request->all());

        return response()->json([
            'status' => 'success',
            'message' => 'Delivery address updated successfully',
            'data' => $deliveryAddress,
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => 'Update failed: ' . $e->getMessage(),
        ], 400);
    }
}



public function show($id)
{
    try {
        $address = DeliveryAddress::where('user_id', auth()->id())->findOrFail($id);
        return ResponseHelper::success('Address fetched successfully', $address);
    } catch (Exception $e) {
        Log::error("Show Error: " . $e->getMessage());
        return ResponseHelper::error('Address not found', 404);
    }
}

public function destroy($id)
{
    try {
        $address = DeliveryAddress::where('user_id', auth()->id())->findOrFail($id);
        $address->delete();

        return ResponseHelper::success('Address deleted successfully');
    } catch (Exception $e) {
        Log::error("Delete Error: " . $e->getMessage());
        return ResponseHelper::error('Failed to delete address', 500);
    }
}

}