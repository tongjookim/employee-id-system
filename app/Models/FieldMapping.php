<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FieldMapping extends Model
{
    protected $fillable = [
        'design_template_id', 'field_key', 'label', 'field_type',
        'pos_x', 'pos_y', 'width', 'height',
        'font_size', 'font_color', 'font_family', 'text_align',
        'is_bold', 'is_visible', 'sort_order',
    ];

    protected $casts = [
        'is_bold'    => 'boolean',
        'is_visible' => 'boolean',
    ];

    public function designTemplate(): BelongsTo
    {
        return $this->belongsTo(DesignTemplate::class);
    }
}
