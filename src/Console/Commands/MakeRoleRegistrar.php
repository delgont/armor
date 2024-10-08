<?php

namespace Delgont\Armor\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;


class MakeRoleRegistrar extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:roleRegistrar {name}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**P
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $registrar = $this->argument('name');

        $registrarClassName = Str::ucfirst($registrar);
         
        $registrarStub = file_get_contents(__DIR__ . '/../../../stubs/role_registrar.stub');

        $classTargetPath = app_path('/' .$registrar. '.php');

        file_put_contents($classTargetPath, strtr($registrarStub, [
            '{{registrar}}' => $registrarClassName
        ]));

        $this->info('Role registrar created successfully .....');
    }
}
