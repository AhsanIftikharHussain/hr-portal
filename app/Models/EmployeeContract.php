<?php

namespace App\Models;

use App\ContractStatus;
use App\ContractType;
use Database\Factories\EmployeeContractFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'employee_id', 'contract_type', 'start_date', 'end_date', 'status', 'notice_period', 'notes',
    'file_path', 'original_filename', 'mime_type', 'file_size', 'uploaded_by',
])]
class EmployeeContract extends Model
{
    /** @use HasFactory<EmployeeContractFactory> */
    use HasFactory;

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class)->withTrashed();
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    protected function casts(): array
    {
        return [
            'contract_type' => ContractType::class,
            'start_date' => 'date',
            'end_date' => 'date',
            'status' => ContractStatus::class,
            'file_size' => 'integer',
        ];
    }
}
