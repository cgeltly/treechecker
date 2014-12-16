<?php

class GedcomTableSeeder extends Seeder
{

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('gedcoms')->truncate();

        $faker = Faker\Factory::create();

        for ($i = 0; $i < 20; $i++)
        {
            Gedcom::create(array(
                'user_id' => 1,
                'file_name' => $faker->lastName,
                'tree_name' => $faker->lastName,
                'source' => $faker->lastName,
                'notes' => $faker->paragraph(),
            ));
        }

        $gedcom = Gedcom::find(WEBTREES_GEDCOM_ID);
        $gedcom->parsed = true;
        $gedcom->save();
    }

}
