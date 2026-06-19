<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TicketStoreRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'title'         => 'required|string|max:255',
            'description'   => 'nullable|string',
            'priority_id'   => 'required|integer|exists:priorities,id',
            'status_id'     => 'required|integer|exists:ticket_statuses,id',
            'requester_id'  => 'required|integer|exists:users,id',
            'department_id' => 'required|integer|exists:departments,id',
            'asset_id'      => 'nullable|integer|exists:assets,id',
        ];
    }
}
