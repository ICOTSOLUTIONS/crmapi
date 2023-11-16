<?php

namespace App\Http\Requests\Recess;

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
            'attendance_id' => 'required|exists:attendances,id',
            'break_in' => 'required',
            'break_type' => 'required',
            'break_out' => 'nullable',
        ];
    }

    public function attributes(): array
    {
        return [
            'attendance_id' => 'Attendance',
            'break_in' => 'Break In',
            'break_type' => 'Break Type',
            'break_out' => 'Break Out',
        ];
    }
}
