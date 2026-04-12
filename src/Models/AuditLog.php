<?php
/**
 * Delgont Armor
 *
 * @link      https://github.com/delgont/armor
 * @copyright Copyright (c) 2024 - Present Delgont Technologies Co. Ltd
 * @license   MIT License (https://opensource.org/licenses/MIT)
 *
 */

namespace Delgont\Armor\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class AuditLog extends Model
{
    protected $fillable = [
        'user_id',
        'user_type',
        'action',
        'message',
        'auditlogable_id',
        'auditlogable_type',
        'before',
        'after',
        'metadata',
        'ip_address',
        'user_agent',
        'url',
        'method',
        'performed_at',
        'links'
    ];

    protected $casts = [
        'before' => 'array',
        'after' => 'array',
        'metadata' => 'array',
        'performed_at' => 'datetime',
    ];

    /**
     * Get the parent auditable model (morph relation).
     */
    public function auditlogable(): MorphTo
    {
        return $this->morphTo();
    }

    public function user(): MorphTo
    {
        return $this->morphTo();
    }
}
