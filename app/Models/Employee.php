<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;

class Employee extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'employee_number', 'password', 'name', 'name_en',
        'department', 'position', 'rank', 'email', 'phone',
        'photo', 'hire_date', 'birth_date', 'blood_type', 'address',
        'qr_token', 'qr_generated_at', 'qr_expires_at',
        'design_template_id', 'status',
    ];

    protected $hidden = ['password'];

    protected $casts = [
        'hire_date'       => 'date',
        'birth_date'      => 'date',
        'qr_generated_at' => 'datetime',
        'qr_expires_at'   => 'datetime',
    ];

    public function designTemplate(): BelongsTo
    {
        return $this->belongsTo(DesignTemplate::class);
    }

    public function qrAccessLogs(): HasMany
    {
        return $this->hasMany(QrAccessLog::class);
    }

    public function getActiveTemplate(): ?DesignTemplate
    {
        return $this->designTemplate ?? DesignTemplate::getDefault();
    }

    public static function generateQrToken(): string
    {
        do {
            $token = Str::random(48);
        } while (self::where('qr_token', $token)->exists());
        return $token;
    }

    /**
     * QR 토큰 재생성 (유효시간 포함)
     */
    public function regenerateQrToken(): void
    {
        $ttl = (int) config('idcard.qr_ttl_seconds', 10);

        $this->update([
            'qr_token'        => self::generateQrToken(),
            'qr_generated_at' => now(),
            'qr_expires_at'   => now()->addSeconds($ttl),
        ]);
    }

    /**
     * QR이 만료되었는지 확인
     */
    public function isQrExpired(): bool
    {
        if (!$this->qr_expires_at) return true;
        return Carbon::now()->greaterThan($this->qr_expires_at);
    }

    /**
     * QR 남은 유효시간 (초)
     */
    public function qrRemainingSeconds(): int
    {
        if (!$this->qr_expires_at) return 0;
        $remaining = Carbon::now()->diffInSeconds($this->qr_expires_at, false);
        return max(0, (int) $remaining);
    }

    // 필드 키에 해당하는 값 반환
    public function getFieldValue(string $fieldKey): ?string
    {
        $map = [
            'name'            => $this->name,
            'name_en'         => $this->name_en,
            'department'      => $this->department,
            'position'        => $this->position,
            'rank'            => $this->rank,
            'email'           => $this->email,
            'phone'           => $this->phone,
            'employee_number' => $this->employee_number,
            'hire_date'       => $this->hire_date?->format('Y.m.d'),
            'birth_date'      => $this->birth_date?->format('Y.m.d'),
            'blood_type'      => $this->blood_type,
        ];

        return $map[$fieldKey] ?? null;
    }
}
