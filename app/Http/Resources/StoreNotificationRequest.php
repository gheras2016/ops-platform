<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreNotificationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title'   => 'required|string|max:255',
            'message' => 'required|string',
            'type'    => 'required|string|in:info,warning,error,success',
            'user_id' => 'required|exists:users,id',
        ];
    }
}
