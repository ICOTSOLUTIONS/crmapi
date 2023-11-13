<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class ProfileUpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'first_name' => "nullable",
            'last_name' => "nullable",
            'email' => "required|email|unique:users,email," . auth()->user()->id,
            'image' => "nullable|image|mimes:png,jpg,jpeg",
            'phone' => "nullable",
        ];
    }

    public function attributes(): array
    {
        return [
            'first_name' => "First name",
            'last_name' => "Last name",
            'email' => "Email",
            'image' => "Image",
            'phone' => "Phone",
        ];
    }
}
