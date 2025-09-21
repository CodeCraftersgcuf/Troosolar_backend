<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

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
            'total_load' => 'nullable|string',
            'inver_rating' => 'nullable|string',
            'total_output' => 'nullable|string',
        ];
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
