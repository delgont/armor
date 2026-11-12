<?php

namespace Delgont\Armor\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class SystemActionGroup extends Model
{
    protected $fillable = [
        'uuid',
        'key',
        'name',
        'registrar',
    ];

    protected static function booted()
    {
        static::creating(function ($group) {

            if (! $group->uuid) {
                $group->uuid = (string) Str::uuid();
            }
        });
    }

    public function actions(): HasMany
    {
        return $this->hasMany(
            SystemAction::class
        );
    }
}