<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Course extends Model
{
    use HasUuids;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'department_id', 'code', 'name_ar', 'name_en',
        'required_hours', 'room_consumed_hours', 'lab_consumed_hours',
        'has_lab_component',
    ];

    protected $casts = [
        'has_lab_component' => 'boolean',
    ];

    public function getNameAttribute(): string
    {
        return (string) ($this->name_en ?? $this->name_ar ?? '');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function sections(): HasMany
    {
        return $this->hasMany(CourseSection::class);
    }

    public function offerings(): HasMany
    {
        return $this->hasMany(CourseOffering::class);
    }
}
