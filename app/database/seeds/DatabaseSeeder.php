<?php

class DatabaseSeeder extends Seeder
{

    public static $gedcom;

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        if (App::environment() === 'production')
        {
            exit('Don\'t seed in production.');
        }

        // Some default settings: unguard Eloquent, disable the logs and foreign keys (on local env)
        Eloquent::unguard();
        DB::disableQueryLog();
        
        if (App::environment() === 'local')
        {
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        }
        
        // Run the default Seeders
        $this->call('UserTableSeeder');

        // Turn foreign keys on again (on local env)
        if (App::environment() === 'local')
        {
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        }
    }

}
