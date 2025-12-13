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
        // Check if this is a registration request (POST to /register)
        $path = $this->path();
        $isRegistration = $this->isMethod('post') && (str_contains($path, 'register'));
        
        return [
        'first_name'       => $isRegistration ? 'required|string|max:255' : 'nullable|string|max:255',
        'sur_name'         => 'nullable|string|max:255',
        'email'            => $isRegistration ? 'required|email|unique:users,email|max:255' : 'nullable|email|max:255',
        'password'         => $isRegistration ? 'required|string|min:6|max:255' : 'nullable|string|min:6|max:255',
        'phone'            => 'nullable|string|max:20',
        'profile_picture'  => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        'refferal_code'    => 'nullable|string|max:255',
        'user_code'        => 'nullable|string|max:255',
        'role'             => 'nullable|string|max:255',
        'is_verified'      => 'nullable|boolean',
        'is_active'        => 'nullable|boolean',
        'otp'              => 'nullable|string|max:10',
        'bvn'              => 'nullable|string|max:20',
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
