<?php

namespace Delgont\Armor\Console\Commands;

use Illuminate\Console\Command;


use Faker\Generator as Faker;
use Illuminate\Support\Str;

use Delgont\Armor\Models\Permission;


class GeneratePermissions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'generate:permissions {permissions? : Permisions to be created, seperate with a comma} {--show : Show all the permissions}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate permissions ';


      /**
     * User model attributes use to display users on console
     *
     * @var array
     */
    private $attributes = ['id', 'name', 'guard'];


    /**
     * 
     *
     * @var Faker
     */
    private $faker;

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
     * @return mixed
     */
    public function handle()
    {
       if ($this->argument('permissions')) {
          $permissions = explode(',', $this->argument('permissions'));
          for ($i=0; $i < count($permissions) ; $i++) { 
            Permission::updateOrCreate([
              'name' => $permissions[$i]
            ]);
          }
          $this->show();
          return;
       }
       $permissions = config('permissions.permissions', []);
       if (count($permissions)) {
          for ($i=0; $i < count($permissions); $i++) { 
             Permission::updateOrCreate([
                'name' => $permissions[$i]
             ]);
          }
          $this->show();
       } else {
          $this->info('No permissions ... please set your default permissions in the permissions configuration file');
       }
       
    }

    private function show()
    {
       if ($this->option('show')) {
          $this->table($this->attributes, Permission::all());
       }
       
    }


}
