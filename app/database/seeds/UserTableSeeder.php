<?php

class UserTableSeeder extends Seeder
{

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {        
        DB::table('users')->truncate();

        User::create(array(
            'first_name'    => 'Corry',
            'last_name'     => 'Gellatly',
            'role'          => 'admin',            
            'email'         => 'c.gellatly@uu.nl',
            'password'      => Hash::make('gedcomcheck')
        ));

        User::create(array(
            'first_name'    => 'Martijn',
            'last_name'     => 'van der Klis',
            'role'          => 'admin',     
            'email'         => 'M.H.vanderKlis@uu.nl',
            'password'      => Hash::make('gedcomcheck')
        ));
        
        User::create(array(
            'first_name'    => 'Newt',
            'last_name'     => 'Frog',
            'role'          => 'typic',     
            'email'         => 'newtfrog@newtfrog.com',
            'password'      => Hash::make('gedcomcheck')
        ));
    }

}
