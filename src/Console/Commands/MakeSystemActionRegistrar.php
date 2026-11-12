<?php

namespace Delgont\Armor\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;

class MakeSystemActionRegistrar extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'armor:actions:make-registrar {name}';

    /**
     * The console command description.
     */
    protected $description = 'Create a system action registrar';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $registrar = $this->argument('name');

        $registrarClassName = Str::ucfirst($registrar);

        $registrarStub = file_get_contents(
            __DIR__ . '/../../../stubs/system_action_registrar.stub'
        );

        $actionsDirectory = app_path('Actions');

        if (!is_dir($actionsDirectory)) {
            mkdir($actionsDirectory, 0755, true);
        }

        $classTargetPath = $actionsDirectory . '/' . $registrar . '.php';

        if (file_exists($classTargetPath)) {

            $this->error(
                'Action registrar already exists.'
            );

            return self::FAILURE;
        }

        file_put_contents(
            $classTargetPath,
            strtr($registrarStub, [
                '{{registrarNamespace}}' => 'App\\Actions',
                '{{registrar}}' => $registrarClassName,
                '{{group}}' => Str::of($registrarClassName)
                    ->replace('ActionRegistrar', '')
                    ->snake()
                    ->plural(),
            ])
        );

        $this->info(
            'System action registrar created successfully.'
        );

        $this->line(
            $classTargetPath
        );

        return self::SUCCESS;
    }
}