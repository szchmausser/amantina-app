<?php

namespace App\Models;

use Database\Factories\StudentHealthRecordFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class StudentHealthRecord extends Model implements HasMedia
{
    /** @use HasFactory<StudentHealthRecordFactory> */
    use HasFactory, InteractsWithMedia, SoftDeletes;

    protected $fillable = [
        'user_id',
        'health_condition_id',
        'received_by',
        'received_at',
        'received_at_location',
        'observations',
    ];

    protected function casts(): array
    {
        return [
            'received_at' => 'datetime',
        ];
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('health_documents')
            ->acceptsMimeTypes(['application/pdf', 'image/jpeg', 'image/png', 'image/gif', 'image/webp']);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function condition(): BelongsTo
    {
        return $this->belongsTo(HealthCondition::class, 'health_condition_id');
    }

    public function receivedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }
}
