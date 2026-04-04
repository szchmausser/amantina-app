<?php

namespace App\Models;

use Askedio\SoftCascade\Traits\SoftCascadeTrait;
use Database\Factories\HealthConditionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class HealthCondition extends Model
{
    /** @use HasFactory<HealthConditionFactory> */
    use HasFactory, SoftCascadeTrait, SoftDeletes;

    /**
     * Cascade soft deletes to related student health records.
     * Note: This uses soft deletes, so records are not permanently removed.
     * Physical files are only deleted when forceDelete() is called.
     */
    protected $softCascade = ['studentRecords'];

    protected $fillable = [
        'name',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function studentRecords(): HasMany
    {
        return $this->hasMany(StudentHealthRecord::class, 'health_condition_id');
    }
}
