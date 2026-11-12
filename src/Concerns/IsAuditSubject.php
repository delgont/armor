<?php
/**
 * Delgont Armor
 *
 * @link      https://github.com/delgont/armor
 * @copyright Copyright (c) 2024 - Present Delgont Technologies Co. Ltd
 * @license   MIT License (https://opensource.org/licenses/MIT)
 *
 */

namespace Delgont\Armor\Concerns;

use Delgont\Armor\Models\AuditLog;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait IsAuditSubject
{
    /**
     * Audit logs affecting this model.
     */
    public function auditLogs(): MorphMany
    {
        return $this->morphMany(
            AuditLog::class,
            'subject',
            'subject_type',
            'subject_id'
        );
    }

    /**
     * Latest modification.
     */
    public function latestAuditLog()
    {
        return $this->auditLogs()
            ->latest('performed_at')
            ->first();
    }

    /**
     * Last actor who modified this record.
     */
    public function lastModifiedBy()
    {
        return optional(
            $this->latestAuditLog()
        )->user;
    }

    /**
     * Was this record ever modified?
     */
    public function hasAuditLogs(): bool
    {
        return $this->auditLogs()->exists();
    }
}