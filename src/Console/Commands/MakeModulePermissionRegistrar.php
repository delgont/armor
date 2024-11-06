<?php

namespace Delgont\Armor\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;


class MakeModulePermissionRegistrar extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'module:make-permissionRegistrar {name} {module}';

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

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $registrar = $this->argument('name');
        $module = $this->argument('module');

        $registrarClassName = Str::ucfirst($registrar);
         
        $registrarStub = file_get_contents(__DIR__ . '/../../../stubs/permission_registrar.stub');

        $classTargetPath = base_path('Modules/'.$module.'/'.class_basename($registrarClassName) . '.php');
        
        file_put_contents($classTargetPath, strtr($registrarStub, [
            '{{registrarNamespace}}' => 'Modules\\'.$module,
            '{{registrar}}' => $registrarClassName
        ]));

        $this->info('Permission registrar created successfully .....');
        $this->info($classTargetPath);
    }


    protected function getQualifiedClass($class)
    {
        return str_replace('/', '\\', $class);
    }
}
