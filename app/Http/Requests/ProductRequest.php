<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

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
            'stock' => 'nullable|string',
            'installation_price' => 'nullable|numeric|min:0',
            'top_deal' => 'boolean',
            'installation_compulsory' => 'boolean',
            'featured_image' => 'nullable|image|mimes:jpg,jpeg,png',
            'images' => 'nullable|array',
            'images.*' => 'image|mimes:jpg,jpeg,png,webp',
            'product_details' => 'nullable|array',
            'product_details.*' => 'required|string',
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

    protected function prepareForValidation(): void
    {
        if ($this->has('brand_id') && $this->input('brand_id') === '') {
            $this->merge(['brand_id' => null]);
        }
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            response()->json([
                'status' => 'error',
                'data' => $validator->errors(),
                'message' => $validator->errors()->first()
            ], 422)
        );
    }
}
