<?php

namespace App\Models;

use Askedio\SoftCascade\Traits\SoftCascadeTrait;
use Database\Factories\AttendanceActivityFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class AttendanceActivity extends Model implements HasMedia
{
    /** @use HasFactory<AttendanceActivityFactory> */
    use HasFactory, InteractsWithMedia, SoftCascadeTrait, SoftDeletes;

    protected $softCascade = [];

    protected $fillable = [
        'attendance_id',
        'activity_category_id',
        'hours',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'hours' => 'decimal:2',
        ];
    }

    /**
     * @return BelongsTo<Attendance, $this>
     */
    public function attendance(): BelongsTo
    {
        return $this->belongsTo(Attendance::class);
    }

    /**
     * @return BelongsTo<ActivityCategory, $this>
     */
    public function activityCategory(): BelongsTo
    {
        return $this->belongsTo(ActivityCategory::class);
    }
}
