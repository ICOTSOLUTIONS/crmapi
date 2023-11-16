<?php

namespace App\Http\Requests\Recess;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRequest extends FormRequest
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
            'break_in' => 'nullable',
            'break_out' => 'nullable',
        ];
    }

    public function attributes(): array
    {
        return [
            'break_in' => 'Break In',
            'break_out' => 'Break Out',
        ];
    }
}
