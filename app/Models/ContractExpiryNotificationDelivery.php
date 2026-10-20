<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'employee_contract_id', 'contract_end_date', 'database_notified_at', 'mail_notified_at'])]
class ContractExpiryNotificationDelivery extends Model
{
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function contract(): BelongsTo
    {
        return $this->belongsTo(EmployeeContract::class, 'employee_contract_id');
    }

    protected function casts(): array
    {
        return [
            'contract_end_date' => 'date',
            'database_notified_at' => 'datetime',
            'mail_notified_at' => 'datetime',
        ];
    }
}
