<?php

namespace App\Models;

use Database\Factories\SchoolTermFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class SchoolTerm extends Model
{
    /** @use HasFactory<SchoolTermFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'academic_year_id',
        'term_number',
        'start_date',
        'end_date',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
        ];
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }
}
