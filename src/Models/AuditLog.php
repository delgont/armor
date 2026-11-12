<?php

namespace Delgont\Armor\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    protected $fillable = [

        'user_id',
        'user_type',

        'action',
        'action_id',

        'message',
        'message_html',

        'before_change',
        'after_change',

        'ip_address',
        'user_agent',

        'url',
        'method',

        'subject_type',
        'subject_id',

        'performed_at',
    ];

    protected $casts = [

        'before_change' => 'array',

        'after_change' => 'array',

        'performed_at' => 'datetime',
    ];

    /**
     * Actor that performed the action.
     */
    public function user(): MorphTo
    {
        return $this->morphTo(
            __FUNCTION__,
            'user_type',
            'user_id'
        );
    }

    /**
     * Model affected by the action.
     */
    public function subject(): MorphTo
    {
        return $this->morphTo(
            __FUNCTION__,
            'subject_type',
            'subject_id'
        );
    }

    /**
     * Related system action.
     */
    public function systemAction(): BelongsTo
    {
        return $this->belongsTo(
            SystemAction::class,
            'action_id'
        );
    }

    /**
     * Determine whether the action modified data.
     */
    public function hasChanges(): bool
    {
        return ! empty($this->before_change)
            || ! empty($this->after_change);
    }

    /**
     * Determine whether the action has a subject.
     */
    public function hasSubject(): bool
    {
        return $this->subject_type
            && $this->subject_id;
    }

    /**
     * Determine whether the action has an actor.
     */
    public function hasUser(): bool
    {
        return $this->user_type
            && $this->user_id;
    }
}