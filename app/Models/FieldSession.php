<?php

namespace App\Models;

use Askedio\SoftCascade\Traits\SoftCascadeTrait;
use Database\Factories\FieldSessionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class FieldSession extends Model
{
    /** @use HasFactory<FieldSessionFactory> */
    use HasFactory, SoftCascadeTrait, SoftDeletes;

    protected $softCascade = [];

    protected $fillable = [
        'name',
        'description',
        'academic_year_id',
        'school_term_id',
        'user_id',
        'activity_name',
        'location_name',
        'start_datetime',
        'end_datetime',
        'base_hours',
        'status_id',
        'cancellation_reason',
    ];

    protected function casts(): array
    {
        return [
            'start_datetime' => 'datetime',
            'end_datetime' => 'datetime',
            'base_hours' => 'decimal:2',
        ];
    }

    /**
     * @return BelongsTo<AcademicYear, $this>
     */
    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    /**
     * @return BelongsTo<SchoolTerm, $this>
     */
    public function schoolTerm(): BelongsTo
    {
        return $this->belongsTo(SchoolTerm::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * @return BelongsTo<FieldSessionStatus, $this>
     */
    public function status(): BelongsTo
    {
        return $this->belongsTo(FieldSessionStatus::class, 'status_id');
    }
}
