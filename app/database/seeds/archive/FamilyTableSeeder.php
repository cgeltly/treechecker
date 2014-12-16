<?php

use Carbon\Carbon;

class FamilyTableSeeder extends DatabaseSeeder
{

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('families')->truncate();

        $parser = new \PhpGedcom\Parser();
        $gedcom = $parser->parse(__DIR__ . '/royal92.ged');

        $n = 0;
        foreach ($gedcom->getFam() as $family)
        {
            //$this->command->info("Family: " . $family->f_husb . "\t" . $family->f_wife);

            $husb = GedcomIndividual::GedcomKey(WEBTREES_GEDCOM_ID, $family->getHusb())->first();
            $wife = GedcomIndividual::GedcomKey(WEBTREES_GEDCOM_ID, $family->getWife())->first();

            if (!(empty($husb) && empty($wife)))
            {
                $f = GedcomFamily::create(array(
                            'gedcom_id' => WEBTREES_GEDCOM_ID,
                            'gedcom_key' => $family->getId(),
                            'indi_id_husb' => empty($husb) ? NULL : $husb->id,
                            'indi_id_wife' => empty($wife) ? NULL : $wife->id,
                            'gedcom' => 'test',
                ));

                $this->create_events($family, $f->id);
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
     * @param \PhpGedcom\Record\Fam $fam
     * @param int $family_id
     */
    private function create_events(\PhpGedcom\Record\Fam $fam, $family_id)
    {
        foreach ($fam->getEven() as $event)
        {
            $date = $this->parse_date($event->getDate());
            $place = $event->getPlac() ? $event->getPlac()->getPlac() : NULL;

            GedcomEvent::create(array(
                'fami_id' => $family_id,
                'event' => $event->getType(),
                'date' => $date,
                'place' => $place,
                'gedcom' => 'test',
            ));
        }
    }

    /**
     * This should parse a date, but doesn't yet fully work for incomplete dates. 
     * @param string $date
     * @return null
     */
    private function parse_date($date)
    {
        if (empty($date))
        {
            return NULL;
        }

        try
        {
            return Carbon::createFromFormat('d M Y', $date);
        }
        catch (Exception $e)
        {
            try
            {
                return Carbon::createFromFormat('M Y', $date);
            }
            catch (Exception $e)
            {
                try
                {
                    return Carbon::createFromFormat('Y', $date);
                }
                catch (Exception $e)
                {
                    $this->command->info('Could not parse date: ' . $date);
                }
            }
        }
    }

}
