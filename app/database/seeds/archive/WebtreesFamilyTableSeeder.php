<?php

use Carbon\Carbon;

class WebtreesFamilyTableSeeder extends Seeder
{

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('families')->truncate();

        $families = DB::connection('webtrees')->
                select('SELECT * FROM wt_families '
                . 'WHERE f_file = ?', array(WEBTREES_GEDCOM_ID));

        $n = 0;
        foreach ($families as $family)
        {
            //$this->command->info("Family: " . $family->f_husb . "\t" . $family->f_wife);

            $husb = GedcomIndividual::GedcomKey(WEBTREES_GEDCOM_ID, $family->f_husb)->first();
            $wife = GedcomIndividual::GedcomKey(WEBTREES_GEDCOM_ID, $family->f_wife)->first();

            if (!(empty($husb) && empty($wife)))
            {
                $f = GedcomFamily::create(array(
                            'gedcom_key' => $family->f_id,
                            'indi_id_husb' => empty($husb) ? NULL : $husb->id,
                            'indi_id_wife' => empty($wife) ? NULL : $wife->id,
                            'gedcom' => $family->f_gedcom,
                ));

                $this->update_individuals($f);

                $this->create_events($f->id, $f->gedcom_key);
            }
            
            $n++;
            if ($n == 50)
            {
                break;
            }
        }
    }

    /**
     * Creates the GedcomEvents for an GedcomFamily
     * @param int $family_id
     * @param string $gedcom_key
     */
    private function create_events($family_id, $gedcom_key)
    {
        $events = DB::connection('webtrees')->
                select('SELECT d_year, d_mon, d_day, d_fact FROM wt_dates '
                . 'WHERE d_file = ? AND d_gid = ?', array(WEBTREES_GEDCOM_ID, $gedcom_key));

        foreach ($events as $event)
        {
            GedcomEvent::create(array(
                'fami_id' => $family_id,
                'event' => $event->d_fact,
                'date' => Carbon::create($event->d_year, $event->d_mon, $event->d_day),
                'place' => 'test',
                'gedcom' => 'test',
            ));
        }
    }

    /**
     * Updates the father/mother of the children in a GedcomFamily
     * @param GedcomFamily $f
     */
    private function update_individuals(GedcomFamily $f)
    {
        $children = $this->get_children($f->gedcom);
        foreach ($children AS $child)
        {
            //$this->command->info("Looking for: " . $child);
            $ind = GedcomIndividual::GedcomKey(WEBTREES_GEDCOM_ID, $child)->first();

            if (!empty($ind))
            {
                //$this->command->info("Found: " . $ind->id);
                $ind->indi_id_father = $f->indi_id_husb;
                $ind->indi_id_mother = $f->indi_id_wife;
                $ind->save();
            }
            else 
            {
                //$this->command->info("Not found.");
            }
        }
    }

    /**
     * Parses the children GEDCOM keys from a GEDCOM family fragment.
     * @param string $gedcom
     * @return array The GEDCOM keys of the children
     */
    private function get_children($gedcom)
    {
        $children = array();
        $f_lines = explode("\n", $gedcom);
        foreach ($f_lines AS $f_line)
        {
            if (starts_with($f_line, '1 CHIL'))
            {
                $matches = array();
                preg_match('/@(.*?)@/', $f_line, $matches);
                $child = $matches[1];
                array_push($children, $child);
            }
        }

        return $children;
    }

}
