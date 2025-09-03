<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreOrderRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
           return [
        'delivery_address_id'     => 'required|exists:delivery_addresses,id',
        'payment_method'          => 'required|in:direct,card,bank_transfer,loan,withdrawal',
        'note'                    => 'nullable|string|max:1000',
        'items'                   => 'required|array|min:1',
        'items.*.itemable_type'   => 'required|in:product,bundle',
        'items.*.itemable_id'     => 'required|integer',
        'items.*.quantity'        => 'required|integer|min:1',
        'installation_price'      => 'nullable|numeric',
        'mono_loan_calculation_id'=> 'nullable|exists:mono_loan_calculations,id',
    ];
    }
}