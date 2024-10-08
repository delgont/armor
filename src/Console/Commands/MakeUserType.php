<?php

namespace Delgont\Armor\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;

class MakeUserType extends Command
{
    protected $signature = 'make:usertype {usertype}';

    protected $description = 'Make User Type Model';

    protected $name;

    public function handle()
    {
        $name = $this->argument('usertype');
        

        $this->info('Generating user type model...');

        $modelStub = file_get_contents(__DIR__ . '/../../../stubs/user_type_model.stub');

        $modelTargetPath = app_path($name . '.php');
        
        if (file_exists($modelTargetPath)) {
            $this->error('Model file already exist');
            return;
        }

        file_put_contents($modelTargetPath, strtr($modelStub, ['{{model}}' => $name]));


        $this->info('Generating user type table migration...');

        $migrationStub = file_get_contents(__DIR__ . '/../../../database/migrations/user_type_migration.stub');


        $migrationClassName = Str::plural($name);
        $tableName = Str::snake($migrationClassName);

        $migrationTargetPath = database_path('migrations/' . date('Y_m_d_his', time()) . '_create_' . $tableName . '_table.php');
        file_put_contents($migrationTargetPath, strtr($migrationStub, [
            '{{migrationClassName}}' => $migrationClassName,
            '{{table}}' => $tableName,
        ]));
        
        $this->info('user type generated...');
    }
}
