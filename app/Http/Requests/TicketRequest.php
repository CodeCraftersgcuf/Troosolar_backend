<?php

namespace App\Http\Requests;

use App\Models\TicketSubject;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $activeTitles = TicketSubject::query()->active()->pluck('title')->all();

        return [
            'subject' => [
                'required',
                'string',
                'max:255',
                Rule::in($activeTitles),
            ],
            'message' => 'required|string',
        ];
    }

    public function messages(): array
    {
        return [
            'subject.in' => 'Please select a valid ticket subject.',
        ];
    }
}
