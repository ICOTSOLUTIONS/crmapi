<?php

namespace App\Http\Requests\Employee;

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
            'first_name' => "required",
            'last_name' => "required",
            'email' => "required|email|unique:users,email," . $this->route('employee')->id,
            'image' => "nullable",
        ];
    }

    public function attributes(): array
    {
        return [
            'first_name' => "First name",
            'last_name' => "Last name",
            'email' => "Email",
            'image' => "Image",
        ];
    }
}
