<?php

namespace Delgont\Armor\Jobs;

use Delgont\Armor\Armor;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;

class WarmPermissionCacheJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public int $timeout = 900;
    public int $uniqueFor = 900;   // never run two warmers at once

    public function __construct(
        public ?string $only = null,   // permissionable alias or class
        public int $chunk = 500
    ) {
    }

    public function handle(): void
    {
        $mode = config('armor.permission_mode', 'direct');

        foreach (Armor::getPermissionables() as $alias => $class) {
            if ($this->only && $this->only !== $alias && $this->only !== $class) {
                continue;
            }

            if (! class_exists($class) || ! method_exists($class, 'permissionNames')) {
                continue;
            }

            $class::query()->chunkById($this->chunk, function ($models) use ($mode) {
                foreach ($models as $model) {
                    $model->permissionNames();

                    if ($mode !== 'direct') {
                        $model->effectivePermissionNames();
                    }
                }
            });
        }
    }
}