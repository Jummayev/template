<?php

namespace App\Http\Requests\Post;

use Illuminate\Foundation\Http\FormRequest;

class StorePostRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string',
            'email' => 'required|string',
            'status' => 'required|integer|min:-2147483648|max:2147483647',
            'file_id' => 'required|integer|min:-2147483648|max:2147483647|exists:files,id',
            'type' => 'nullable|integer|min:-2147483648|max:2147483647',
            'balance' => 'nullable|numeric',
            'user_id' => 'required|integer|min:-2147483648|max:2147483647|exists:users,id',
            'email_verified_at' => 'nullable|date',
            'password' => 'required|string',
            'remember_token' => 'nullable|string',
        ];
    }
}
