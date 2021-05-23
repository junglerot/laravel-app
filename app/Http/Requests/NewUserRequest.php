<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class NewUserRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
            'user.username' => 'required|alpha_num|unique:users,username|max:255',
            'user.email' => 'required|email|unique:users,email|max:255',
            'user.password' => 'required|string|max:255',
        ];
    }
}
