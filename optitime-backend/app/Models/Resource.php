<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Resource extends Model
{
    use HasUuids;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'name_ar', 'name_en', 'type', 'quantity',
        'location_ar', 'location_en', 'status', 'notes_ar', 'notes_en',
    ];
}
