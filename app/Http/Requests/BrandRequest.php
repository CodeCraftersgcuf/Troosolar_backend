<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BrandRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => 'nullable|string|max:255',
            'icon' => 'nullable|image|mimes:jpg,jpeg,png,svg,webp|max:2048',
            'category_id' => 'nullable|exists:categories,id',
        ];
    }
}
