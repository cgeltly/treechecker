<?php

/*
 * TreeChecker: Error recognition for genealogical trees
 * 
 * Copyright (C) 2014 Digital Humanities Lab, Faculty of Humanities, Universiteit Utrecht
 * Corry Gellatly <corry.gellatly@gmail.com>
 * Martijn van der Klis <M.H.vanderKlis@uu.nl>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

class CheckController extends BaseController
{

    public function __construct()
    {
        parent::__construct();
        
        //prevent access to controller methods without login
        $this->beforeFilter('auth');
    }
    
    /**
     * Checks a Gedcom for errors. 
     * @param int $gedcom_id
     * @return Response the error overview page
     */
    public function getStart($gedcom_id)
    {
        // Get the GEDCOM, delete existing errors from error check phase
        $gedcom = Gedcom::findOrFail($gedcom_id);
        $gedcom->errors()->where('stage', 'error_check')->delete();

        // Query log is save in memory, so need to disable it for large file parsing
        DB::connection()->disableQueryLog();
        DB::beginTransaction();

        // Check the lifespan and parental age of individuals
        $this->checkLifespan($gedcom);
        $this->checkParentalAge($gedcom);

        // Check marriage age difference of families
        $this->checkMarriageAgeDiff($gedcom);

        // Check all event dates on correctness
        $this->checkEventDates($gedcom);

        // Check whether childs are listed in multiple families
        $this->checkMultipleFamilies($gedcom);

        // End the transaction
        DB::commit();

        // Set the file as error checked
        $gedcom->error_checked = true;
        $gedcom->save();

        // Redirect to the GEDCOM overview
        return Redirect::to('errors/gedcom/' . $gedcom_id);
    }

    /**
     * Checks the GedcomEvents for validity. 
     * - If the year of the event is before 1000 A.D., suppose an error
     * - If there's any event for an individual before the BIRT event, suppose an error
     * @param Gedcom $gedcom
     */
    private function checkEventDates($gedcom)
    {
        $this->checkEventsBeforeDate($gedcom, '1000-00-00');
        $this->checkEventsBeforeBirth($gedcom);
    }

    /**
     * Check whether there are events before a specified date
     * @param Gedcom $gedcom
     * @param string $date formatted Y-m-d
     */
    private function checkEventsBeforeDate($gedcom, $date)
    {
        $i_events = $gedcom->individualEvents()->where('date', '<', $date)->get();
        $f_events = $gedcom->familyEvents()->where('date', '<', $date)->get();
        $events = $i_events->merge($f_events);

        foreach ($events AS $e)
        {
            $error = new GedcomError();
            $error->gedcom_id = $gedcom->id;
            $error->indi_id = $e->indi_id;
            $error->fami_id = $e->fami_id;
            $error->classification = 'incorrect';
            $error->severity = 'warning';
            $error->message = sprintf('There is an event dated %s, is this correct?', $e->date);
            $error->save();
        }
    }

    /**
     * Checks whether there are events before the birth of a GedcomIndividual
     * @param Gedcom $gedcom
     */
    private function checkEventsBeforeBirth($gedcom)
    {
        foreach ($gedcom->individualEventsBeforeBirth() AS $e)
        {
            $error = new GedcomError();
            $error->gedcom_id = $gedcom->id;
            $error->indi_id = $e->id;
            $error->classification = 'incorrect';
            $error->severity = 'error';
            $error->message = sprintf('There is a %s event before the BIRT event for %s.', $e->event, $e->gedcom_key);
            $error->save();
        }
    }

    /**
     * Checks the lifespan of GedcomIndividuals, creates errors when (probably) incorrect.
     * @param Gedcom $gedcom
     */
    private function checkLifespan($gedcom)
    {
        foreach ($gedcom->lifespan_larger_than(110) as $i)
        {
            $error = new GedcomError();
            $error->gedcom_id = $gedcom->id;
            $error->indi_id = $i->id;
            $error->classification = 'incorrect';
            $error->severity = $i->age > 122 ? 'error' : 'warning';
            $error->message = sprintf('The lifespan of %s is %d years, is this correct?', $i->gedcom_key, $i->age);
            $error->save();
        }

        foreach ($gedcom->lifespan_less_than(0) as $i)
        {
            $error = new GedcomError();
            $error->gedcom_id = $gedcom->id;
            $error->indi_id = $i->id;
            $error->classification = 'incorrect';
            $error->severity = 'error';
            $error->message = sprintf('The DEAT event of %s occurs before the BIRT event.', $i->gedcom_key);
            $error->save();
        }
    }

    /**
     * Checks the parental age of GedcomIndividuals, creates errors when (probably) incorrect.
     * @param Gedcom $gedcom
     */
    private function checkParentalAge($gedcom)
    {
        foreach ($gedcom->parentalAgeLargerThan('wife', 55) as $i)
        {
            $error = new GedcomError();
            $error->gedcom_id = $gedcom->id;
            $error->indi_id = $i->id;
            $error->classification = 'incorrect';
            $error->severity = $i->age > 60 ? 'error' : 'warning';
            $error->message = sprintf('The parental age of %s is %d years, is this correct?', $i->gedcom_key, $i->age);
            $error->save();
        }

        foreach ($gedcom->parentalAgeLargerThan('husb', 80) as $i)
        {
            $error = new GedcomError();
            $error->gedcom_id = $gedcom->id;
            $error->indi_id = $i->id;
            $error->classification = 'incorrect';
            $error->severity = $i->age > 92 ? 'error' : 'warning';
            $error->message = sprintf('The parental age of %s is %d years, is this correct?', $i->gedcom_key, $i->age);
            $error->save();
        }

        foreach (array('husb', 'wife') as $parent)
        {
            foreach ($gedcom->parentalAgeLessThan($parent, 11) as $i)
            {
                $error = new GedcomError();
                $error->gedcom_id = $gedcom->id;
                $error->indi_id = $i->id;
                $error->classification = 'incorrect';
                $error->severity = $i->age < 7 ? 'error' : 'warning';
                $error->message = sprintf('The parental age of %s is %d years, is this correct?', $i->gedcom_key, $i->age);
                $error->save();
            }

            foreach ($gedcom->bornBeforeParent($parent) as $i)
            {
                $error = new GedcomError();
                $error->gedcom_id = $gedcom->id;
                $error->indi_id = $i->child_id;
                $error->classification = 'incorrect';
                $error->severity = 'error';
                $error->message = sprintf('Individual %s is born before %s %s.', $i->child_key, $parent == 'husb' ? 'father' : 'mother', $i->parent_key);
                $error->save();
            }
        }
    }

    /**
     * Checks the marriage age difference of GedcomFamilies, creates errors when (probably) incorrect.
     * @param Gedcom $gedcom
     */
    private function checkMarriageAgeDiff($gedcom)
    {
        foreach ($gedcom->marriageAgeDiffLargerThan(30) as $f)
        {
            $error = new GedcomError();
            $error->gedcom_id = $gedcom->id;
            $error->fami_id = $f->id;
            $error->classification = 'incorrect';
            $error->severity = 'warning';
            $error->message = sprintf('The marriage age difference of %s is %d years, is this correct?', $f->gedcom_key, $f->age);
            $error->save();
        }
    }

    /**
     * Checks whether there are individuals listed as child in multiple families. 
     * If they have an adoption tag, that's OK. 
     * @param Gedcom $gedcom
     */
    private function checkMultipleFamilies($gedcom)
    {
        foreach ($gedcom->individualNrFamilies AS $i)
        {
            $ind = GedcomIndividual::find($i->indi_id);

            if (!$ind->isAdopted())
            {
                $error = new GedcomError();
                $error->gedcom_id = $gedcom->id;
                $error->indi_id = $ind->id;
                $error->classification = 'incorrect';
                $error->severity = 'warning';
                $error->message = sprintf('Individual %s is listed as child in %d families', $ind->gedcom_key, $i->count);
                $error->save();
            }
        }
    }

}
