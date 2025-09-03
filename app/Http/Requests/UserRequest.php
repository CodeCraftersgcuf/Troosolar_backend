<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class UserRequest extends FormRequest
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
        'first_name'       => 'nullable|string',
        'sur_name'         => 'nullable|string',
        'email'            => 'nullable|email',
        'password'         => 'nullable|string|min:6', // Adjust min length as needed
        'phone'            => 'nullable|string',
        'profile_picture'  => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        'refferal_code'    => 'nullable|string',
        'user_code'        => 'nullable|string',
        'role'             => 'nullable', // Adjust allowed roles
        'is_verified'      => 'nullable|boolean',
        'is_active'        => 'nullable|boolean',
        'otp'              => 'nullable|string',
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
