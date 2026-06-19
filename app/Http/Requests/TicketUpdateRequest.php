<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TicketUpdateRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'title'         => 'sometimes|string|max:255',
            'description'   => 'nullable|string',
            'priority_id'   => 'sometimes|integer|exists:priorities,id',
            'status_id'     => 'sometimes|integer|exists:ticket_statuses,id',
            'requester_id'  => 'sometimes|integer|exists:users,id',
            'department_id' => 'sometimes|integer|exists:departments,id',
            'asset_id'      => 'nullable|integer|exists:assets,id',
        ];
    }
}
