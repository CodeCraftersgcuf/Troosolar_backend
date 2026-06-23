<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreProductReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'review' => trim((string) $this->input('review', '')),
        ]);
    }

    public function rules(): array
    {
        return [
            'product_id' => 'nullable|integer|exists:products,id|required_without:bundle_id',
            'bundle_id' => 'nullable|integer|exists:bundles,id|required_without:product_id',
            'order_id' => 'nullable|integer|exists:orders,id',
            'review' => 'nullable|string|max:10000',
            'rating' => 'required|in:1,2,3,4,5',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $hasProduct = $this->filled('product_id');
            $hasBundle = $this->filled('bundle_id');
            if ($hasProduct && $hasBundle) {
                $validator->errors()->add('product_id', 'Provide either product_id or bundle_id, not both.');
            }
        });
    }
}
