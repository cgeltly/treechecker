<?php

use Carbon\Carbon;

class IndividualTableSeeder extends DatabaseSeeder
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

        $parser = new \PhpGedcom\Parser();
        $gedcom = $parser->parse(__DIR__ . '/royal92.ged');

        $n = 0;
        foreach ($gedcom->getIndi() as $individual)
        {
            $i = GedcomIndividual::create(array(
                        'gedcom_id' => WEBTREES_GEDCOM_ID,
                        'first_name' => current($individual->getName())->getName(),
                        'last_name' => current($individual->getName())->getName(),
                        'sex' => strtolower($individual->getSex()),
                        'gedcom_key' => $individual->getId(),
                        'gedcom' => 'test',
            ));

            $this->create_events($individual, $i->id);

            $n++;
            if ($n == 250)
            {
                break;
            }
        }
    }

    /**
     * Creates the GedcomEvents for an GedcomIndividual
     * @param \PhpGedcom\Record\Indi $ind
     * @param int $individual_id
     */
    private function create_events(\PhpGedcom\Record\Indi $ind, $individual_id)
    {
        foreach ($ind->getEven() as $event)
        {
            $date = $this->parse_date($event->getDate());
            $place = $event->getPlac() ? $event->getPlac()->getPlac() : NULL;

            GedcomEvent::create(array(
                'indi_id' => $individual_id,
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
