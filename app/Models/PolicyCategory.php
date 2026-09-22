<?php

namespace App\Models;

use Database\Factories\PolicyCategoryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'description', 'is_active'])]
class PolicyCategory extends Model
{
    /** @use HasFactory<PolicyCategoryFactory> */
    use HasFactory;

    /** @return HasMany<Policy, $this> */
    public function policies(): HasMany
    {
        return $this->hasMany(Policy::class);
    }

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }
}
