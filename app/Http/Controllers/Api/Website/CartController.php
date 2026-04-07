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
use App\Models\CheckoutSetting;
use App\Models\ReferralSettings;
use App\Support\CheckoutPricing;
use Illuminate\Support\Arr;

class CartController extends Controller
{
    private function resolveItemUnitPrice($model): float
    {
        if ($model instanceof Product) {
            $discount = (float) ($model->discount_price ?? 0);
            $basePrice = (float) ($model->price ?? 0);
            return $discount > 0 ? $discount : max(0, $basePrice);
        }

        if ($model instanceof Bundles) {
            $discount = (float) ($model->discount_price ?? 0);
            $basePrice = (float) ($model->total_price ?? 0);
            return $discount > 0 ? $discount : max(0, $basePrice);
        }

        return (float) ($model->price ?? $model->total_price ?? 0);
    }

    private function applyOutrightDiscount(float $amount, ?float $percentage): float
    {
        $pct = max(0, (float) ($percentage ?? 0));
        if ($pct <= 0 || $amount <= 0) {
            return $amount;
        }

        return max(0, round($amount - (($amount * $pct) / 100), 2));
    }

    public function index()
    {
        try {
            $userId = Auth::id();

            $items = CartItem::with('itemable')
                ->where('user_id', $userId)
                ->get();

            // Heal previously saved invalid rows where unit_price became 0 because discount_price was 0.
            foreach ($items as $item) {
                if (!$item->itemable) {
                    continue;
                }
                $resolvedUnitPrice = $this->resolveItemUnitPrice($item->itemable);
                $qty = max(1, (int) $item->quantity);
                $currentUnitPrice = (float) ($item->unit_price ?? 0);
                if ($currentUnitPrice <= 0 && $resolvedUnitPrice > 0) {
                    $item->unit_price = $resolvedUnitPrice;
                    $item->subtotal = $resolvedUnitPrice * $qty;
                    $item->save();
                }
            }

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
            $settings = CheckoutSetting::get();
            $deliveryFee = (int) $settings->delivery_fee;
            $installationText = trim((string) ($settings->installation_description ?? ''));
            if ($installationText === '') {
                $installationText = (string) config('checkout.installation_text',
                    'Installation will be carried out by our skilled technicians. You can choose to use our installers.'
                );
            }
            $vatPct = (float) ($settings->vat_percentage ?? config('checkout.vat_percentage', 7.5));
            $insPct = (float) ($settings->insurance_fee_percentage ?? config('checkout.insurance_fee_percentage', 3));
            $installationFlatAddon = (int) ($settings->installation_flat_addon ?? 0);
            $deliveryWindow = CheckoutPricing::deliveryWindow($settings);
            $includeInstallation = $request->boolean('include_installation');

            // 1) Load cart items and their polymorphic models
            $rawItems = CartItem::query()
                ->where('user_id', $userID)
                ->with('itemable')                 // Product / Bundle / etc.
                ->orderBy('created_at', 'asc')
                ->get();

            if ($rawItems->isEmpty()) {
                $itemsSubtotal = 0;
                $installationFromProducts = 0;
                $installationFull = $installationFromProducts + $installationFlatAddon;
                $insuranceAmount = $includeInstallation
                    ? CheckoutPricing::insuranceAmountFromPercent(0.0, (float) $installationFull, $insPct)
                    : 0;
                $taxableBase = (float) $itemsSubtotal + (float) $deliveryFee;
                if ($includeInstallation) {
                    $taxableBase += (float) $installationFull + (float) $insuranceAmount;
                }
                $vatAmount = CheckoutPricing::vatAmount($taxableBase, $vatPct);
                $grandTotal = (int) round($taxableBase + (float) $vatAmount);

                return response()->json([
                    'status'  => 'success',
                    'message' => 'Cart is empty.',
                    'data'    => [
                        'cart' => ['items' => [], 'items_count' => 0, 'items_total' => 0],
                        'addresses' => [],
                        'delivery' => [
                            'price' => $deliveryFee,
                            'estimate_label' => $deliveryWindow['label'],
                            'estimated_from' => $deliveryWindow['estimated_from'],
                            'estimated_to' => $deliveryWindow['estimated_to'],
                        ],
                        'installation' => [
                            'description' => $installationText,
                            'installation_products_total' => 0,
                            'installation_flat_addon' => $installationFlatAddon,
                            'price' => $installationFull,
                            'insurance_fee_percentage' => $insPct,
                            'insurance_price' => $insuranceAmount,
                            'estimated_date' => CheckoutPricing::installationEstimatedDate($settings),
                        ],
                        'totals' => [
                            'items_total' => 0,
                            'delivery' => $deliveryFee,
                            'installation_products' => 0,
                            'installation_flat_addon' => $installationFlatAddon,
                            'installation_total' => $installationFull,
                            'insurance' => $insuranceAmount,
                            'insurance_fee_percentage' => $insPct,
                            'vat_percentage' => $vatPct,
                            'vat_amount' => $vatAmount,
                            'taxable_base' => (int) round($taxableBase),
                            'grand_total' => $grandTotal,
                            'include_installation' => $includeInstallation,
                        ],
                        'grand_total' => $grandTotal,
                    ],
                ], 200);
            }

            // Align line pricing with OrderController::store (referral outright discount on direct/cash).
            $paymentMethod = strtolower((string) $request->input('payment_method', 'direct'));
            $isOutrightCheckout = in_array($paymentMethod, ['direct', 'cash'], true);
            $outrightPct = $isOutrightCheckout
                ? (float) (ReferralSettings::getSettings()->outright_discount_percentage ?? 0)
                : 0.0;

            // 2) Build items with product details
            $cartItems = $rawItems->map(function ($item) use ($outrightPct) {
                $qty = max(1, (int) $item->quantity);

                if (!$item->itemable) {
                    Log::warning('CartItem missing itemable', ['cart_item_id' => $item->id]);
                    return null;
                }

                $catalogUnit = $this->resolveItemUnitPrice($item->itemable);
                $unit = $outrightPct > 0
                    ? $this->applyOutrightDiscount($catalogUnit, $outrightPct)
                    : $catalogUnit;
                $subtotal = round($unit * $qty, 2);

                $product = $this->transformItemable($item->itemable);

                $row = [
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

                if ($outrightPct > 0 && $catalogUnit > $unit + 0.005) {
                    $row['list_unit_price'] = round($catalogUnit, 2);
                    $row['referral_outright_discount_percent'] = $outrightPct;
                }

                return $row;
            })->filter()->values();

            if ($cartItems->isEmpty()) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Your cart contains invalid items.',
                    'data'    => [],
                ], 422);
            }

            // 3a) Installation total from cart line items (per-product installation_price) + optional shop addon.
            $installationFromProducts = CheckoutPricing::installationTotalFromCartItems($rawItems);
            $installationFull = $installationFromProducts + $installationFlatAddon;

            // 3) Totals
            $itemsSubtotal = (float) round($cartItems->sum('subtotal'), 2);
            $itemsCount    = (int) $cartItems->sum('quantity');

            $insuranceAmount = $includeInstallation
                ? CheckoutPricing::insuranceAmountFromPercent((float) $itemsSubtotal, (float) $installationFull, $insPct)
                : 0;
            $taxableBase = (float) $itemsSubtotal + (float) $deliveryFee;
            if ($includeInstallation) {
                $taxableBase += (float) $installationFull + (float) $insuranceAmount;
            }
            $vatAmount = CheckoutPricing::vatAmount($taxableBase, $vatPct);
            $grandTotal = (int) round($taxableBase + (float) $vatAmount);

            // 4) Addresses (+ contact name fallback to account holder)
            $viewer = Auth::user();
            $fallbackName = $viewer ? trim(($viewer->first_name ?? '').' '.($viewer->sur_name ?? '')) : '';
            $addresses = DeliveryAddress::query()
                ->where('user_id', $userID)
                ->orderByDesc('id')
                ->get(['id', 'title', 'contact_name', 'address', 'state', 'phone_number'])
                ->map(function ($a) use ($fallbackName) {
                    $cn = trim((string) ($a->contact_name ?? ''));

                    return [
                        'id' => $a->id,
                        'title' => $a->title,
                        'contact_name' => $cn !== '' ? $cn : $fallbackName,
                        'address' => $a->address,
                        'state' => $a->state,
                        'phone_number' => $a->phone_number,
                    ];
                });

            // 5) Blocks
            $installation = [
                'description' => $installationText,
                'installation_products_total' => $installationFromProducts,
                'installation_flat_addon' => $installationFlatAddon,
                'price' => $installationFull,
                'insurance_fee_percentage' => $insPct,
                'insurance_price' => $insuranceAmount,
                'estimated_date' => CheckoutPricing::installationEstimatedDate($settings),
            ];
            $delivery = [
                'price' => $deliveryFee,
                'estimate_label' => $deliveryWindow['label'],
                'estimated_from' => $deliveryWindow['estimated_from'],
                'estimated_to' => $deliveryWindow['estimated_to'],
            ];

            return response()->json([
                'status'  => 'success',
                'message' => 'Checkout summary retrieved successfully.',
                'data'    => [
                    'cart' => [
                        'items'       => $cartItems,
                        'items_count' => $itemsCount,
                        'items_total' => $itemsSubtotal,
                    ],
                    'addresses' => $addresses,
                    'delivery' => $delivery,
                    'installation' => $installation,
                    'totals' => [
                        'items_total' => $itemsSubtotal,
                        'delivery' => $deliveryFee,
                        'installation_products' => $installationFromProducts,
                        'installation_flat_addon' => $installationFlatAddon,
                        'installation_total' => $installationFull,
                        'insurance' => $insuranceAmount,
                        'insurance_fee_percentage' => $insPct,
                        'vat_percentage' => $vatPct,
                        'vat_amount' => $vatAmount,
                        'taxable_base' => (int) round($taxableBase),
                        'grand_total' => $grandTotal,
                        'include_installation' => $includeInstallation,
                    ],
                    'grand_total' => $grandTotal,
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
            if ($resolvedType === Product::class) {
                $availableStock = (int) ($model->stock ?? 0);
                if ($availableStock <= 0) {
                    return ResponseHelper::error('This product is out of stock.', 422);
                }
                if ((int) $quantity > $availableStock) {
                    return ResponseHelper::error("Only {$availableStock} unit(s) left in stock.", 422);
                }
            }

            $price = $this->resolveItemUnitPrice($model);
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
            if ($cartItem->itemable_type === Product::class && $model) {
                $availableStock = (int) ($model->stock ?? 0);
                if ($availableStock <= 0) {
                    return ResponseHelper::error('This product is out of stock.', 422);
                }
                if ((int) $quantity > $availableStock) {
                    return ResponseHelper::error("Only {$availableStock} unit(s) left in stock.", 422);
                }
            }
            $price = $this->resolveItemUnitPrice($model);

            $cartItem->quantity = $quantity;
            $cartItem->unit_price = $price;
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

    /**
     * Access cart via token (from email link)
     * GET /api/cart/access/{token}
     */
    public function accessCartViaToken($token)
    {
        try {
            $user = \App\Models\User::where('cart_access_token', $token)->first();

            if (!$user) {
                return ResponseHelper::error('Invalid or expired cart link', 404);
            }

            $cartItems = CartItem::with('itemable')
                ->where('user_id', $user->id)
                ->get();

            return ResponseHelper::success([
                'user' => [
                    'id' => $user->id,
                    'name' => $user->first_name . ' ' . $user->sur_name,
                    'email' => $user->email,
                ],
                'cart_items' => $cartItems,
                'requires_login' => !Auth::check() || Auth::id() !== $user->id,
                'message' => Auth::check() && Auth::id() === $user->id 
                    ? 'Cart accessed successfully' 
                    : 'Please login to proceed with checkout',
            ], 'Cart accessed successfully');

        } catch (Exception $e) {
            Log::error('Error accessing cart via token: ' . $e->getMessage());
            return ResponseHelper::error('Failed to access cart', 500);
        }
    }
}