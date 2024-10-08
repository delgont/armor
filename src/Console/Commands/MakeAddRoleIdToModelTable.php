<?php

namespace Delgont\Armor\Console\Commands;


use Illuminate\Console\Command;
use Illuminate\Support\Str;

class MakeAddRoleIdToModelTable extends Command
{
    protected $signature = 'make:addRoleIdToModelTableMigration {modelTable}';

    protected $description = 'Make add role id to model table migration';

    protected $modelTable;

    public function handle()
    {
        $modelTable = $this->argument('modelTable');
        

        $this->info('creating migration');


        $migrationStub = file_get_contents(__DIR__ . '/../../../database/migrations/add_role_id_to_model_migration.stub');


        $migrationClassName = 'AddRoleIdTo'.Str::ucfirst($modelTable);

        $migrationTargetPath = database_path('migrations/' . date('Y_m_d_his', time()) . '_add_role_id_to_' . $modelTable . '_table.php');


        file_put_contents($migrationTargetPath, strtr($migrationStub, [
            '{{migrationClassName}}' => $migrationClassName,
            '{{table}}' => $modelTable,
        ]));
        
        $this->info('add role id to '.$modelTable.' migration generated successfully ....');
    }
}
