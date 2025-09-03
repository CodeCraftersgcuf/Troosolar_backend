<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreLinkAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'account_number' => 'required|string|max:50',
            'account_name' => 'required|string|max:100',
            'bank_name'     => 'required|string|max:100',
            'status'        => 'nullable|string|max:20',
        ];
    }
}