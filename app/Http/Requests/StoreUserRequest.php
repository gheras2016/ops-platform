<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UserStoreRequest extends FormRequest
{
    public function authorize()
    {
        return true; // عدلها لاحقاً لو عندك صلاحيات
    }

    public function rules()
    {
        return [
            'name'     => 'required|string|max:150',
            'email'    => 'required|email|unique:users,email',
            'password' => 'required|min:6',
        ];
    }

    public function attributes()
    {
        return [
            'name'     => 'الاسم',
            'email'    => 'البريد الإلكتروني',
            'password' => 'كلمة المرور',
        ];
    }
}
