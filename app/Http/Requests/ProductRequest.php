<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        // Define common rules
        $rules = [
            'brand_id' => 'nullable|exists:brands,id',
            'discount_price' => 'nullable|numeric|min:0',
            'discount_end_date' => 'nullable|date',
            'stock' => 'nullable|string|max:255',
            'installation_price' => 'nullable|numeric|min:0',
            'top_deal' => 'boolean',
            'installation_compulsory' => 'boolean',
            'featured_image' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            'images' => 'nullable|array',
            'images.*' => 'image|mimes:jpg,jpeg,png,webp|max:2048',
            'product_details' => 'nullable|array',
            'product_details.*' => 'required|string|max:255',
        ];

        if ($this->isMethod('post')) {
            // Fields required on CREATE
            $rules = array_merge($rules, [
                'title' => 'required|string|max:255',
                'category_id' => 'required|exists:categories,id',
                'price' => 'required|numeric|min:0',
            ]);
        } else {
            // Fields optional on UPDATE
            $rules = array_merge($rules, [
                'title' => 'sometimes|required|string|max:255',
                'category_id' => 'sometimes|exists:categories,id',
                'price' => 'sometimes|numeric|min:0',
            ]);
        }

        return $rules;
    }
}
