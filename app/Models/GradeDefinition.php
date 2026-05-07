<?php

namespace App\Models;

use Database\Factories\GradeDefinitionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class GradeDefinition extends Model
{
    /** @use HasFactory<GradeDefinitionFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'order' => 'integer',
        ];
    }
}
