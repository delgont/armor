<?php

namespace Delgont\Armor\Console\Commands;

use Illuminate\Console\Command;

class ShowPermissionRegistrars extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'armor:show-permission-registrars';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Shows registered permission registrar classes with permission counts from config';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $registrars = config('armor.permission_registrars');

        if (empty($registrars) || !is_array($registrars)) {
            $this->warn('No permission registrars found in config(armor.permission_registrars).');
            return 0;
        }

        $rows = [];
        foreach ($registrars as $i => $registrarClass) {
            if (!class_exists($registrarClass)) {
                $rows[] = [
                    'Index' => $i + 1,
                    'Registrar Class' => $registrarClass,
                    'Permissions' => 'Class not found',
                ];
                continue;
            }

            $instance = app($registrarClass);
            $count = method_exists($instance, 'descriptions')
                ? count($instance->descriptions())
                : 'No descriptions()';

            $rows[] = [
                'Index' => $i + 1,
                'Registrar Class' => $registrarClass,
                'Permissions' => $count,
            ];
        }

        $this->table(['Index', 'Registrar Class', 'Permissions'], $rows);
        return 0;
    }
}
