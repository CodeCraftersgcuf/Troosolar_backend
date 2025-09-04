<?php

namespace App\Http\Controllers\Api\Website;

use App\Models\DeliveryAddress;
use Exception;
use App\Models\Product;
use App\Models\CartItem;
use App\Helpers\ResponseHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\StoreCartItemRequest;
use App\Http\Requests\UpdateCartItemRequest;
use App\Models\Bundles;
use Illuminate\Support\Arr;

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

    public function checkoutSummary(Request $request)
    {
        // $user = $request->user();
        $userID = Auth::id();
        // if (!$user) {
        //     return response()->json([
        //         'status'  => 'error',
        //         'message' => 'Unauthorized. Please login first.',
        //     ], 401);
        // }

        try {
            // ---- Configurable
            $deliveryFee       = (int) config('checkout.delivery_fee', 20000);
            $installationPrice = (int) config('checkout.installation_price', 25000);
            $installationText  = (string) config('checkout.installation_text',
                'Installation will be carried out by our skilled technicians. You can choose to use our installers.'
            );

            // 1) Load cart items and their polymorphic models
            $rawItems = CartItem::query()
                ->where('user_id', $userID)
                ->with('itemable')                 // Product / Bundle / etc.
                ->orderBy('created_at', 'asc')
                ->get();

            if ($rawItems->isEmpty()) {
                return response()->json([
                    'status'  => 'success',
                    'message' => 'Cart is empty.',
                    'data'    => [
                        'cart' => ['items' => [], 'items_count' => 0, 'items_total' => 0],
                        'addresses' => [],
                        'delivery' => ['price' => $deliveryFee],
                        'installation' => [
                            'description'    => $installationText,
                            'price'          => $installationPrice,
                            'estimated_date' => now()->addDays(7)->toDateString(),
                        ],
                        'grand_total' => $deliveryFee + $installationPrice,
                    ],
                ], 200);
            }

            // 2) Build items with product details
            $cartItems = $rawItems->map(function ($item) {
                $qty      = max(1, (int) $item->quantity);
                $unit     = max(0, (int) $item->unit_price);
                $subtotal = $item->subtotal !== null ? max(0, (int) $item->subtotal) : $qty * $unit;

                if (!$item->itemable) {
                    Log::warning('CartItem missing itemable', ['cart_item_id' => $item->id]);
                    return null;
                }

                $product = $this->transformItemable($item->itemable);

                return [
                    'id'         => (int) $item->id,
                    'type'       => class_basename($item->itemable_type),
                    'ref_id'     => (int) $item->itemable_id,
                    'name'       => $product['name'] ?? 'Item',
                    'unit_price' => $unit,
                    'quantity'   => $qty,
                    'subtotal'   => $subtotal,
                    'image'      => $product['image'] ?? null,
                    'product'    => $product,           // <── full product/bundle details
                ];
            })->filter()->values();

            if ($cartItems->isEmpty()) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Your cart contains invalid items.',
                    'data'    => [],
                ], 422);
            }

            // 3) Totals
            $itemsSubtotal = (int) $cartItems->sum('subtotal');
            $itemsCount    = (int) $cartItems->sum('quantity');

            // 4) Addresses
            $addresses = DeliveryAddress::query()
                ->where('user_id', $userID)
                ->orderByDesc('id')
                ->get(['id', 'title', 'address', 'state', 'phone_number']);

            // 5) Blocks
            $installation = [
                'description'    => $installationText,
                'price'          => $installationPrice,
                'estimated_date' => now()->addDays(7)->toDateString(),
            ];
            $delivery = ['price' => $deliveryFee];

            // 6) Grand total
            $grandTotal = $itemsSubtotal + $deliveryFee + $installationPrice;

            return response()->json([
                'status'  => 'success',
                'message' => 'Checkout summary retrieved successfully.',
                'data'    => [
                    'cart' => [
                        'items'       => $cartItems,
                        'items_count' => $itemsCount,
                        'items_total' => $itemsSubtotal,
                    ],
                    'addresses'    => $addresses,
                    'delivery'     => $delivery,
                    'installation' => $installation,
                    'grand_total'  => $grandTotal,
                ],
            ], 200);

        } catch (\Throwable $e) {
            Log::error('Checkout summary error', [
                'user_id' => $user->id ?? null,
                'error'   => $e->getMessage(),
            ]);

            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to load checkout summary.',
            ], 500);
        }
    }

    /**
     * Safely extract detailed fields for the polymorphic item (Product/Bundle/etc.)
     */
    protected function transformItemable($model): array
    {
        if (!$model) {
            return [];
        }

        // Convert to array once
        $arr = method_exists($model, 'toArray') ? $model->toArray() : [];

        // Common fields we try to expose (works for Product/Bundle with different schemas)
        $core = Arr::only($arr, [
            'id', 'uuid', 'slug', 'sku',
            'name', 'title',
            'short_description', 'description',
            'price', 'sale_price', 'currency',
            'brand', 'model', 'warranty',
            'specs', 'attributes',
        ]);

        // Try to find a main image & a gallery
        $image = $arr['image_url'] ?? $arr['image'] ?? $arr['thumbnail'] ?? null;
        $gallery = [];

        // If you keep images in a relation/array field, try common keys
        foreach (['images', 'gallery', 'media'] as $k) {
            if (!empty($arr[$k]) && is_array($arr[$k])) {
                $gallery = array_values($arr[$k]);
                break;
            }
        }

        // Provide a normalized payload
        return array_filter([
            'id'          => $core['id']   ?? null,
            'slug'        => $core['slug'] ?? null,
            'sku'         => $core['sku']  ?? null,
            'name'        => $core['name'] ?? $core['title'] ?? null,
            'price'       => isset($core['price']) ? (int) $core['price'] : null,
            'sale_price'  => isset($core['sale_price']) ? (int) $core['sale_price'] : null,
            'description' => $core['short_description'] ?? $core['description'] ?? null,
            'brand'       => $core['brand'] ?? null,
            'model'       => $core['model'] ?? null,
            'warranty'    => $core['warranty'] ?? null,
            'specs'       => $core['specs'] ?? null,
            'attributes'  => $core['attributes'] ?? null,
            'image'       => $image,
            'gallery'     => $gallery,
            'raw'         => $arr,  // keep the full original payload for the frontend if needed
        ], fn ($v) => $v !== null && $v !== []);
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