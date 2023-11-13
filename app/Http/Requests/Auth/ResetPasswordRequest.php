<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class ResetPasswordRequest extends FormRequest
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
            'email' => "required|email|exists:users",
            'otp' => "required|exists:password_resets,token,email," . request()->email,
            'password' => "required|min:8|confirmed",
        ];
    }

    public function attributes(): array
    {
        return [
            'email' => "Email",
            'otp' => "OTP",
            'password' => "Password",
        ];
    }
}
