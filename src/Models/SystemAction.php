<?php

namespace Delgont\Armor\Models;

use Delgont\Armor\Enums\RiskLevel;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class SystemAction extends Model
{
    protected $fillable = [
        'uuid',
        'action',
        'description',
        'risk_level',
        'supports_undo',
        'otp_protected',
        'system_action_group_id',
        'order',
    ];

    protected $casts = [
        'supports_undo' => 'boolean',
        'otp_protected' => 'boolean',
        'risk_level' => RiskLevel::class,
    ];

    protected static function booted()
    {
        static::creating(function ($action) {

            if (! $action->uuid) {
                $action->uuid = (string) Str::uuid();
            }
        });
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(
            SystemActionGroup::class
        );
    }

    public function supportsUndo(): bool
    {
        return $this->supports_undo;
    }

    public function requiresOtp(): bool
    {
        return $this->otp_protected;
    }

    public function isLowRisk(): bool
    {
        return $this->risk_level === RiskLevel::LOW;
    }

    public function isMediumRisk(): bool
    {
        return $this->risk_level === RiskLevel::MEDIUM;
    }

    public function isHighRisk(): bool
    {
        return $this->risk_level === RiskLevel::HIGH;
    }

    public function isCriticalRisk(): bool
    {
        return $this->risk_level === RiskLevel::CRITICAL;
    }

    public static function byAction(
        string $action
    ): ?static
    {
        return static::where(
            'action',
            $action
        )->first();
    }

    /**
     * Get audit logs associated with this action.
     */
    public function auditLogs()
    {
        return $this->hasMany(
            AuditLog::class,
            'action_id'
        );
    }
}