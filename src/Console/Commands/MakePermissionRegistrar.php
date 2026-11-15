<?php

namespace Delgont\Armor\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

class MakePermissionRegistrar extends Command
{
    protected $signature = 'armor:permissions:make-registrar
                            {name : Class name, e.g. User or Hr/Payroll}
                            {--group= : Group name (defaults to "<Name> Permissions")}
                            {--f|force : Overwrite an existing file}';

    protected $description = 'Create a new permission registrar class in app/Permissions';

    public function handle(Filesystem $files): int
    {
        // 1. "hr/payroll" or "Hr\Payroll" -> ['Hr', 'Payroll']. Validate before touching the disk.
        $segments = preg_split('/[\\\\\/]+/', trim($this->argument('name'), '\\/'));

        foreach ($segments as $segment) {
            if (! preg_match('/^[A-Za-z][A-Za-z0-9_]*$/', $segment)) {
                $this->error("'{$segment}' is not a valid class name.");
                return self::FAILURE;
            }
        }

        $segments = array_map([Str::class, 'studly'], $segments);

        // 2. Normalise the class name: "User", "UserPermissions" and "UserPermissionRegistrar"
        //    all become UserPermissionRegistrar.
        $class = array_pop($segments);
        if (! str_ends_with($class, 'Registrar')) {
            $class = (preg_replace('/Permissions?$/', '', $class) ?: $class).'PermissionRegistrar';
        }

        // 3. Namespace and path follow the app's real root namespace.
        $namespace = implode('\\', array_merge([rtrim($this->laravel->getNamespace(), '\\'), 'Permissions'], $segments));
        $path = app_path(implode('/', array_merge(['Permissions'], $segments, [$class.'.php'])));

        // 4. Never overwrite someone's work unless asked.
        if ($files->exists($path) && ! $this->option('force')) {
            $this->error("{$namespace}\\{$class} already exists. Use --force to overwrite.");
            return self::FAILURE;
        }

        $stubPath = __DIR__.'/../../../stubs/permission_registrar.stub';
        if (! $files->exists($stubPath)) {
            $this->error('Stub not found: '.$stubPath);
            return self::FAILURE;
        }

        $group = $this->option('group') ?: $this->defaultGroup($class);

        if (! $files->isDirectory(dirname($path))) {
            $files->makeDirectory(dirname($path), 0755, true);
        }

        $files->put($path, strtr($files->get($stubPath), [
            '{{ namespace }}' => $namespace,
            '{{ class }}'     => $class,
            '{{ group }}'     => addcslashes($group, "'\\"),   // safe inside a single-quoted PHP string
        ]));

        $this->info("Permission registrar created: {$namespace}\\{$class}");
        $this->newLine();
        $this->line('  Next:');
        $this->line("   1. Add snake_case constants for your permissions in {$class}.");
        $this->line("   2. Register it: <comment>Armor::registerPermissionRegistrars([\\{$namespace}\\{$class}::class]);</comment>");
        $this->line('   3. Run <comment>php artisan armor:permissions:sync</comment>.');

        return self::SUCCESS;
    }

    /** "UserPermissionRegistrar" -> "User Permissions". Matches the style of your own registrars. */
    protected function defaultGroup(string $class): string
    {
        $base = preg_replace('/(Permission)?Registrar$/', '', $class);

        return $base === '' ? 'Permissions' : Str::title(Str::snake($base, ' ')).' Permissions';
    }
}