<?php

namespace App\Http\Controllers\Api\Website;

use Exception;
use App\Models\Product;
use App\Models\CartItem;
use App\Helpers\ResponseHelper;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\StoreCartItemRequest;
use App\Http\Requests\UpdateCartItemRequest;
use App\Models\Bundles;

class CartController extends Controller
{
    public function index()
    {
        try {
            $userId = Auth::id();

            $items = CartItem::with('itemable')
                ->where('user_id', $userId)
                ->get();

            return ResponseHelper::success($items, 'Cart items fetched successfully');
        } catch (Exception $e) {
            return ResponseHelper::error('Failed to fetch cart items', $e->getMessage());
        }
    }

    public function store(StoreCartItemRequest $request)
    {
        try {
            $userId = auth()->id();
            $itemableType = $request->itemable_type;
            $itemableId = $request->itemable_id;
            $quantity = $request->quantity;

            // Convert to full model class
            $resolvedType = $itemableType === 'product' ? Product::class : Bundles::class;

            // Check duplicate
            $existingItem = CartItem::where('user_id', $userId)
                ->where('itemable_type', $resolvedType)
                ->where('itemable_id', $itemableId)
                ->first();

            if ($existingItem) {
                return ResponseHelper::error('This item is already in your cart.', 409);
            }

            $model = $resolvedType::findOrFail($itemableId);

            $price = $model->discount_price ?? $model->price ?? $model->total_price;
            $subtotal = $price * $quantity;

            $cartItem = CartItem::create([
                'user_id'       => $userId,
                'itemable_type' => $resolvedType,
                'itemable_id'   => $itemableId,
                'quantity'      => $quantity,
                'unit_price'    => $price,
                'subtotal'      => $subtotal,
            ]);

            $cartItem->load('itemable'); // load relationship for response

            return ResponseHelper::success($cartItem, 'Item added to cart successfully');
        } catch (Exception $e) {
            Log::error("Cart Store Error: " . $e->getMessage());
            return ResponseHelper::error('Failed to add item to cart', 500);
        }
    }

    public function update(UpdateCartItemRequest $request, $id)
    {
        try {
            $cartItem = CartItem::where('user_id', auth()->id())->findOrFail($id);
            $quantity = $request->quantity;

            $model = $cartItem->itemable;
            $price = $model->discount_price ?? $model->price ?? $model->total_price;

            $cartItem->quantity = $quantity;
            $cartItem->subtotal = $price * $quantity;
            $cartItem->save();

            $cartItem->load('itemable');

            return ResponseHelper::success($cartItem, 'Cart item updated successfully');
        } catch (Exception $e) {
            Log::error("Cart Update Error: " . $e->getMessage());
            return ResponseHelper::error('Failed to update cart item', 500);
        }
    }

    public function destroy($id)
    {
        try {
            $cartItem = CartItem::where('user_id', auth()->id())->findOrFail($id);
            $cartItem->delete();
            return ResponseHelper::success('Cart item removed successfully');
        } catch (Exception $e) {
            Log::error("Cart Delete Error: " . $e->getMessage());
            return ResponseHelper::error('Failed to remove cart item', 500);
        }
    }

    public function clear()
    {
        try {
            CartItem::where('user_id', auth()->id())->delete();
            return ResponseHelper::success('Cart cleared successfully');
        } catch (Exception $e) {
            Log::error("Cart Clear Error: " . $e->getMessage());
            return ResponseHelper::error('Failed to clear cart', 500);
        }
    }
}