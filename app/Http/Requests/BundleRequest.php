<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BundleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => 'nullable|string|max:255',
            'featured_image' => 'nullable|image|mimes:jpg,jpeg,png|max:3048',
            'bundle_type' => 'nullable|string',
            'total_price' => 'nullable|numeric|min:0',
            'discount_price' => 'nullable|numeric|min:0',
            'discount_end_date' => 'nullable|date',

            'items' => 'nullable|array',
            'items.*' => 'nullable|exists:products,id',

            'custom_services' => 'nullable|array',
            'custom_services.*.title' => 'required|string|max:255',
            'custom_services.*.service_amount' => 'required|numeric|min:0',
        ];
    }
}
