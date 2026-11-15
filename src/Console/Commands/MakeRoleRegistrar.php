<?php

namespace Delgont\Armor\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

class MakeRoleRegistrar extends Command
{
    protected $signature = 'armor:roles:make-registrar
                            {name : Class name, e.g. Staff or Hr/Staff}
                            {--group= : Role group name (defaults to the class name)}
                            {--f|force : Overwrite an existing file}';

    protected $description = 'Create a new role registrar class in app/Roles';

    public function handle(Filesystem $files): int
    {
        // 1. "hr/staff" or "Hr\Staff" becomes ['Hr', 'Staff']. Each part must be a valid identifier.
        $segments = preg_split('/[\\\\\/]+/', trim($this->argument('name'), '\\/'));

        foreach ($segments as $segment) {
            if (! preg_match('/^[A-Za-z][A-Za-z0-9_]*$/', $segment)) {
                $this->error("'{$segment}' is not a valid class name.");
                return self::FAILURE;
            }
        }

        $segments = array_map([Str::class, 'studly'], $segments);

        // 2. Always end in "Registrar" so generated classes are easy to find and list.
        $class = array_pop($segments);
        if (! str_ends_with($class, 'Registrar')) {
            $class .= 'RoleRegistrar';
        }

        // 3. Namespace and path follow the app's root namespace, under app/Roles.
        $namespace = implode('\\', array_merge([rtrim($this->laravel->getNamespace(), '\\'), 'Roles'], $segments));
        $path = app_path(implode('/', array_merge(['Roles'], $segments, [$class.'.php'])));

        // 4. Never overwrite someone's work unless asked.
        if ($files->exists($path) && ! $this->option('force')) {
            $this->error("{$namespace}\\{$class} already exists. Use --force to overwrite.");
            return self::FAILURE;
        }

        // 5. Without a group, roles are never reported as stale or cleaned, so default to one.
        $group = $this->option('group') ?: $this->defaultGroup($class);

        $stubPath = __DIR__.'/../../../stubs/role_registrar.stub';
        if (! $files->exists($stubPath)) {
            $this->error('Stub not found: '.$stubPath);
            return self::FAILURE;
        }

        if (! $files->isDirectory(dirname($path))) {
            $files->makeDirectory(dirname($path), 0755, true);
        }

        $files->put($path, strtr($files->get($stubPath), [
            '{{ namespace }}' => $namespace,
            '{{ class }}'     => $class,
            '{{ group }}'     => addcslashes($group, "'\\"),   // safe inside a single-quoted PHP string
        ]));

        $this->info("Role registrar created: {$namespace}\\{$class}");
        $this->newLine();
        $this->line('  Next:');
        $this->line("   1. Add constants for your roles in {$class}.");
        $this->line("   2. List <comment>{$namespace}\\{$class}::class</comment> under 'role_registrars' in config/armor.php.");
        $this->line('   3. Run <comment>php artisan armor:roles:sync</comment>.');

        return self::SUCCESS;
    }

    /** "StaffRoleRegistrar" becomes "Staff", "HrManagersRegistrar" becomes "Hr Managers". */
    protected function defaultGroup(string $class): string
    {
        $base = preg_replace('/(Role)?Registrar$/', '', $class) ?: $class;

        return Str::title(Str::snake($base, ' '));
    }
}