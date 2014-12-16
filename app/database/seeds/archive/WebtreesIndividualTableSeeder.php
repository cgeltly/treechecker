<?php

use Carbon\Carbon;

class WebtreesIndividualTableSeeder extends Seeder
{

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('individuals')->truncate();
        DB::table('events')->truncate();

        $individuals = DB::connection('webtrees')->
                select('SELECT * FROM wt_individuals '
                . 'LEFT JOIN wt_name ON i_file = n_file AND i_id = n_id '
                . 'WHERE i_file = ? AND n_type = "NAME"', array(WEBTREES_GEDCOM_ID));

        $n = 0;
        foreach ($individuals as $individual)
        {
            $i = GedcomIndividual::create(array(
                        'gedcom_id' => WEBTREES_GEDCOM_ID,
                        'first_name' => $individual->n_givn,
                        'last_name' => $individual->n_surname,
                        'sex' => $individual->i_sex,
                        'gedcom_key' => $individual->i_id,
                        'gedcom' => $individual->i_gedcom,
            ));

            $this->create_events($i->id, $i->gedcom_key, $i->gedcom);

            $n++;
            if ($n == 250)
            {
                break;
            }
        }
    }

    /**
     * Creates the GedcomEvents for an GedcomIndividual
     * @param int $individual_id
     * @param string $gedcom_key
     */
    private function create_events($individual_id, $gedcom_key, $gedcom)
    {
        $events = DB::connection('webtrees')->
                select('SELECT d_year, d_mon, d_day, d_fact FROM wt_dates '
                . 'WHERE d_file = ? AND d_gid = ?', array(WEBTREES_GEDCOM_ID, $gedcom_key));

        foreach ($events as $event)
        {
            $event_gedcom = $this->get_event_gedcom($event->d_fact, $gedcom);
            //$this->command->info("Gedcom: " . $event_gedcom);

            GedcomEvent::create(array(
                'indi_id' => $individual_id,
                'event' => $event->d_fact,
                'date' => Carbon::create($event->d_year, $event->d_mon, $event->d_day),
                'place' => 'test',
                'gedcom' => $event_gedcom,
            ));
        }
    }

    /**
     * Parses the GEDCOM event from a GEDCOM individual fragment.
     * @param string $fact
     * @param string $gedcom
     * @return array The GEDCOM event for the $fact
     */
    private function get_event_gedcom($fact, $gedcom)
    {
        $result = "";
        $f_lines = explode("\n", $gedcom);
        for ($i = 0; $i < count($f_lines); $i++)
        {
            if ($f_lines[$i] === "1 " . $fact)
            {
                $result .= $f_lines[$i];
                $next = $f_lines[++$i];
                while (!starts_with($next, '1 ') || $i > count($f_lines))
                {
                    $result .= "\n" . $next;
                    $next = $f_lines[++$i];
                }
                break;
            }
        }

        return $result;
    }

}
