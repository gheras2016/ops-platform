<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseApproval extends Model
{
    protected $fillable = [
        'purchase_request_id', 'department_id', 'stage', 'approver_id', 'decision', 'note', 'decided_at',
    ];

    protected $casts = [
        'decided_at' => 'datetime',
    ];

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approver_id');
    }

    public function stageLabel(): string
    {
        return $this->stage === 'finance' ? 'اعتماد المالية' : 'اعتماد ' . ($this->department?->name ?? 'القسم');
    }

    public function decisionLabel(): string
    {
        return match ($this->decision) {
            'approved' => 'اعتمد',
            'rejected' => 'رفض',
            'auto' => 'تخطٍّ تلقائي',
            default => $this->decision,
        };
    }
}
