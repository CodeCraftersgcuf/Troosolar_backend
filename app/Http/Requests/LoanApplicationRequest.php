<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class LoanApplicationRequest extends FormRequest
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
        'title_document' => 'nullable|string|max:255',
        'upload_document' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048', // Or use 'file' if you're uploading
        'beneficiary_name' => 'nullable|string|max:255',
        'beneficiary_email' => 'nullable|email|max:255',
        'beneficiary_relationship' => 'nullable|string|max:255',
        'beneficiary_phone' => 'nullable|string|max:20',
        'status' => 'nullable|string', // customize values as needed
        'user_id' => 'nullable',
        'mono_loan_calculation' => 'nullable',
        'loan_amount'=>'nullable',
        'repayment_duration'=>'nullable',

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
