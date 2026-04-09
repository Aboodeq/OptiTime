<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Room extends Model
{
    use HasUuids;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'name_ar', 'name_en', 'type', 'capacity',
        'location_ar', 'location_en', 'status', 'notes_ar', 'notes_en',
    ];

    public function getNameAttribute(): string
    {
        return (string) ($this->name_en ?? $this->name_ar ?? '');
    }

    public function isLabType(): bool
    {
        return $this->type === 'lab';
    }
}
