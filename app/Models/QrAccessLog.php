<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QrAccessLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'employee_id', 'qr_token', 'ip_address',
        'user_agent', 'access_type', 'is_valid',
    ];

    protected $casts = [
        'is_valid'   => 'boolean',
        'created_at' => 'datetime',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
