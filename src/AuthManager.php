<?php
/**
 * Delgont Armor  (https://delgont.co.ug).
 *
 * @link https://github.com/delgont/armor source repository
 *
 * @copyright Copyright (c) 2024. Delgont Technologies Co. Ltd (https://delgont.co.ug)
 *
 * @license https://opensource.org/licenses/MIT MIT License
 */

namespace Delgont\Armor;

use Illuminate\Support\Facades\Log;
use Throwable;
use Delgont\Armor\Events\PermissionsSynchronized;

class AuthManager
{
    public function syncPermissionss() : string
    {
        $registrars = config('armor.permission_registrars');
        $allPermissions = [];

        if (is_array($registrars) && count($registrars) > 0) {
            foreach ($registrars as $registrar) {
                if (class_exists($registrar)) {
                    $instance = app($registrar);
                    $instance->sync();

                    // Collect permissions from this registrar
                    $allPermissions = array_merge($allPermissions, $instance->getPermissions());
                } else {
                    \Log::warning("Permission registrar class {$registrar} does not exist.");
                }
            }

            // Dispatch event with actual permissions
            event(new PermissionsSynchronized($allPermissions));

            return 'Permissions successfully synchronized';
        } else {
            return 'There are no permissions to sync';
        }
    }



    public function syncPermissions(?callable $report = null): string
    {
        $report ??= static fn () => null;
        $registrars = config('armor.permission_registrars');

        if (! is_array($registrars) || count($registrars) === 0) {
            return 'There are no permissions to sync';
        }

        $allPermissions = [];
        $failed = 0;

        foreach (array_unique($registrars) as $registrar) {
            if (! class_exists($registrar)) {
                Log::warning("Permission registrar class {$registrar} does not exist.");
                $report('skipped', $registrar);
                continue;
            }

            $report('start', $registrar);

            try {
                $instance = app($registrar);

                $instance->sync(
                    fn (int $done, int $total) => $report('progress', $registrar, $done, $total)
                );

                $allPermissions = array_merge($allPermissions, $instance->getPermissions());

                $report('done', $registrar);
            } catch (Throwable $e) {
                $failed++;
                Log::error("Permission registrar {$registrar} failed to sync: ".$e->getMessage());
                $report('failed', $registrar, $e);
            }
        }

        event(new PermissionsSynchronized($allPermissions));

        return $failed > 0
            ? "Permissions synchronized with {$failed} failure(s)"
            : 'Permissions successfully synchronized';
    }


    public function syncRoles(?callable $report = null): string
    {
        $report ??= static fn () => null;
        $registrars = config('armor.role_registrars');

        if (! is_array($registrars) || $registrars === []) {
            return 'no roles to sync';
        }

        $failed = 0;

        foreach (array_unique($registrars) as $registrar) {
            if (! is_string($registrar) || ! class_exists($registrar)) {
                Log::warning('Role registrar class '.json_encode($registrar).' does not exist.');
                $report('skipped', (string) $registrar);
                continue;
            }

            $report('start', $registrar);

            try {
                $instance = app($registrar);
                $instance->sync();

                $report('done', $registrar, method_exists($instance, 'roleNames') ? count($instance->roleNames()) : 0);
            } catch (Throwable $e) {
                $failed++;
                Log::error("Role registrar {$registrar} failed to sync: ".$e->getMessage());
                $report('failed', $registrar, $e);
            }
        }

        return $failed > 0
            ? "Roles synchronized with {$failed} failure(s)"
            : 'roles synchronized successfully';
    }

    /**
     * Sync every registered system action registrar.
     * One failing registrar never stops the others.
     */
    public function syncActions(?callable $report = null): string
    {
        $report ??= static fn () => null;
        $registrars = Armor::getActionRegistrars();

        if ($registrars === []) {
            return 'There are no actions to sync';
        }

        $failed = 0;
        $seen = [];

        foreach ($registrars as $registrar) {
            $label = is_string($registrar) ? $registrar : json_encode($registrar);

            // Skip duplicates, and entries that are not usable class names.
            if (isset($seen[$label])) {
                continue;
            }
            $seen[$label] = true;

            if (! is_string($registrar) || ! class_exists($registrar)) {
                Log::warning("Action registrar {$label} does not exist.");
                $report('skipped', $label, 'class does not exist');
                continue;
            }

            if (! is_subclass_of($registrar, SystemActionRegistrar::class)) {
                $report('skipped', $registrar, 'does not extend SystemActionRegistrar');
                continue;
            }

            $report('start', $registrar);

            try {
                $instance = app($registrar);
                $instance->sync();

                $report('done', $registrar, count($instance->getActions()));
            } catch (Throwable $e) {
                $failed++;
                Log::error("Action registrar {$registrar} failed to sync: ".$e->getMessage());
                $report('failed', $registrar, $e);
            }
        }

        return $failed > 0
            ? "Actions synchronized with {$failed} failure(s)"
            : 'Actions synchronized successfully';
    }

}
