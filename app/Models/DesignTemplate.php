<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DesignTemplate extends Model
{
    protected $fillable = [
        'name', 'background_image', 'canvas_width', 'canvas_height',
        'is_default', 'is_active', 'description',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'is_active'  => 'boolean',
    ];

    public function fieldMappings(): HasMany
    {
        return $this->hasMany(FieldMapping::class)->orderBy('sort_order');
    }

    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }

    public static function getDefault(): ?self
    {
        return self::where('is_default', true)->where('is_active', true)->first();
    }
}
