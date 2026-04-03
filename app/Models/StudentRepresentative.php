<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\SoftDeletes;

class StudentRepresentative extends Pivot
{
    use SoftDeletes;

    protected $table = 'student_representatives';

    public $incrementing = true;

    protected $with = ['relationshipType'];

    protected $fillable = [
        'student_id',
        'representative_id',
        'relationship_type_id',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function representative(): BelongsTo
    {
        return $this->belongsTo(User::class, 'representative_id');
    }

    public function relationshipType(): BelongsTo
    {
        return $this->belongsTo(RelationshipType::class);
    }
}
