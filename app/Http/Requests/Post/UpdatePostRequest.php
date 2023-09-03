<?php

namespace App\Http\Requests\Post;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePostRequest extends FormRequest
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
            'name' => 'nullable|string',
            'email' => 'nullable|string',
            'status' => 'nullable|integer|min:-2147483648|max:2147483647',
            'file_id' => 'nullable|integer|min:-2147483648|max:2147483647|exists:files,id',
            'type' => 'nullable|integer|min:-2147483648|max:2147483647',
            'balance' => 'nullable|numeric',
            'user_id' => 'nullable|integer|min:-2147483648|max:2147483647|exists:users,id',
            'email_verified_at' => 'nullable|date',
            'password' => 'nullable|string',
            'remember_token' => 'nullable|string',
        ];
    }
}
