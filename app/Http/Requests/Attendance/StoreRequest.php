<?php

namespace App\Http\Requests\Attendance;

use Illuminate\Foundation\Http\FormRequest;

class StoreRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'time_in' => 'required',
            'time_out' => 'nullable',
        ];
    }

    public function attributes(): array
    {
        return [
            'time_in' => 'Time In',
            'time_out' => 'Time Out',
        ];
    }
}
