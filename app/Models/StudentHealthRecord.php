<?php

namespace App\Models;

use Askedio\SoftCascade\Traits\SoftCascadeTrait;
use Database\Factories\StudentHealthRecordFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class StudentHealthRecord extends Model implements HasMedia
{
    /** @use HasFactory<StudentHealthRecordFactory> */
    use HasFactory, InteractsWithMedia, SoftCascadeTrait, SoftDeletes;

    /**
     * Cascade soft deletes to associated media.
     * Physical files are deleted on soft delete to free storage space.
     */
    protected $softCascade = ['media'];

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

    protected static function booted(): void
    {
        // Delete physical files when soft deleted (to free storage space).
        // Note: If the record is restored later, the files are gone.
        static::deleted(function (StudentHealthRecord $record) {
            $record->getMedia('health_documents')->each->delete();
        });
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

    /**
     * Get all media associated with this record.
     */
    public function media(): MorphMany
    {
        return $this->morphMany(Media::class, 'model');
    }
}
