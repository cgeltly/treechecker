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
     * Generates statistics and checks the Gedcom file for errors. 
     * @param int $gedcom_id
     * @return Response the error overview page
     */
    public function getStart($gedcom_id)
    {
        $gedcom = Gedcom::findOrFail($gedcom_id);

        $this->populateStats($gedcom);
        $this->populateErrors($gedcom);

        // Set the file as error checked
        $gedcom->error_checked = true;
        $gedcom->save();

        // Redirect to the errors overview
        return Redirect::to('errors/gedcom/' . $gedcom_id);
    }

    /**
     * Deletes errors for this Gedcom and then checks the file for errors
     * @param Gedcom $gedcom
     */
    private function populateErrors($gedcom)
    {
        // Delete existing errors from error check phase
        $gedcom->errors()->where('stage', 'error_check')->delete();

        // Start a transaction
        DB::connection()->disableQueryLog();
        DB::beginTransaction();

        // Check the lifespan and parental age of individuals
        $this->checkLifespan($gedcom);
        $this->checkParentalAge($gedcom);
        // Check spousal age gaps
        $this->checkSpousalAgeGaps($gedcom);
        // Check the chronology of event dates
        $this->checkEventDates($gedcom);
        // Check whether childs are listed in multiple families
        $this->checkMultipleFamilies($gedcom);
        // Check marriage ages
        $this->checkHusbMarriageAges($gedcom);
        $this->checkWifeMarriageAges($gedcom);
                
        // End the transaction
        DB::commit();
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
            $error->type_broad = 'chronology';
            $error->type_specific = 'events < 1000AD';
            $error->eval_broad = 'warning';
            $error->eval_specific = '';
            $error->message = sprintf('Event prior to 1000AD, dated %s.', $e->date);
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
            $error->type_broad = 'chronology';
            $error->type_specific = 'event before birth';
            $error->eval_broad = 'error';
            $error->eval_specific = '';
            $error->message = sprintf('There is a ' . $e->event . ' event before the BIRT event for '
                            . $e->first_name . ' ' . $e->last_name . ' (' . $e->gedcom_key) . ').';
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
            $error->type_broad = 'lifespan';
            $error->type_specific = 'warn: >110, err: >122';
            $error->eval_broad = $i->age > 122 ? 'error' : 'warning';
            $error->eval_specific = '';
            $error->message = sprintf('Lifespan of ' . $i->age . ' years for ' .
                    $i->first_name . ' ' . $i->last_name . ' (' . $i->gedcom_key . ').');
            $error->save();
        }

        foreach ($gedcom->lifespan_less_than(0) as $i)
        {
            $error = new GedcomError();
            $error->gedcom_id = $gedcom->id;
            $error->indi_id = $i->id;
            $error->type_broad = 'lifespan';
            $error->type_specific = 'err: <0';
            $error->eval_broad = 'error';
            $error->eval_specific = '';
            $error->message = sprintf('Death of ' . $i->first_name . ' ' .
                    $i->last_name . '(' . $i->gedcom_key . ') occurs before birth: ' . $i->age . ' years.');
            $error->save();
        }
    }

    /**
     * Checks age of parents, creates errors when (probably) incorrect.
     * @param Gedcom $gedcom
     */
    private function checkParentalAge($gedcom)
    {
        foreach ($gedcom->parentalAgeLargerThan('wife', 55) as $i)
        {
            $error = new GedcomError();
            $error->gedcom_id = $gedcom->id;
            $error->indi_id = $i->indi_id;
            $error->type_broad = 'parental age';
            $error->type_specific = 'warn: >55, err: >60';
            $error->eval_broad = $i->age > 60 ? 'error' : 'warning';
            $error->eval_specific = '';
            $error->message = sprintf('Maternal age of ' . $i->age . ' years for ' .
                    $i->par_fn . ' ' . $i->par_ln . ' (' . $i->gedcom_i_key . ').');
            $error->save();
        }

        foreach ($gedcom->parentalAgeLargerThan('husb', 80) as $i)
        {
            $error = new GedcomError();
            $error->gedcom_id = $gedcom->id;
            $error->indi_id = $i->indi_id;
            $error->type_broad = 'parental age';
            $error->type_specific = 'warn: >80, err: >92';
            $error->eval_broad = $i->age > 92 ? 'error' : 'warning';
            $error->eval_specific = '';
            $error->message = sprintf('Paternal age of ' . $i->age . ' years for ' .
                    $i->par_fn . ' ' . $i->par_ln . ' (' . $i->gedcom_i_key . ').');
            $error->save();
        }


        foreach (array('husb', 'wife') as $parent)
        {
            foreach ($gedcom->parentalAgeLessThan($parent, 11) as $i)
            {
                $error = new GedcomError();
                $error->gedcom_id = $gedcom->id;
                $error->indi_id = $i->indi_id;
                $error->type_broad = 'parental age';
                $error->type_specific = 'warn: <11, err: <7';
                $error->eval_broad = $i->age < 7 ? 'error' : 'warning';
                $error->eval_specific = '';
                $error->message = sprintf('Parental age of ' . $i->age . ' years for ' .
                        $i->par_fn . ' ' . $i->par_ln . ' (' . $i->gedcom_i_key . ').');
                $error->save();
            }
        }
    }

    /**
     * Checks the spousal age gap of GedcomFamilies, creates errors when (probably) incorrect.
     * @param Gedcom $gedcom
     */
    private function checkSpousalAgeGaps($gedcom)
    {
        foreach ($gedcom->spousalAgeGapLargerThan(30) as $f)
        {
            $error = new GedcomError();
            $error->gedcom_id = $gedcom->id;
            $error->fami_id = $f->id;
            $error->type_broad = 'marriage age';
            $error->type_specific = 'warn: >30';
            $error->eval_broad = 'warning';
            $error->eval_specific = '';
            $error->message = sprintf('Spousal age gap of ' . abs($f->age) . ' years for couple with '
                    . 'family ID ' . $f->gedcom_key . '.');
            $error->save();
        }
    }

    /**
     * Checks the marriage age of husbands, creates errors when (probably) incorrect.
     * @param Gedcom $gedcom
     */
    private function checkHusbMarriageAges($gedcom)
    {
        foreach ($gedcom->marriageAgeHusbLessThan(12) as $f)
        {
            $error = new GedcomError();
            $error->gedcom_id = $gedcom->id;
            $error->fami_id = $f->fami_id;
            $error->indi_id = $f->indi_id_husb;
            $error->type_broad = 'marriage age';
            $error->type_specific = 'err: < 12';
            $error->eval_broad = 'error';
            $error->eval_specific = '';
            $error->message = sprintf('Marriage age of ' . ($f->marr_age_husb) . ' years for husband '
                    . $f->indi_id_husb . ' in family ' . ($f->fami_id) . '.');
            $error->save();
        }
    }    
    
        /**
     * Checks the marriage age of wives, creates errors when (probably) incorrect.
     * @param Gedcom $gedcom
     */
    private function checkWifeMarriageAges($gedcom)
    {
        foreach ($gedcom->marriageAgeWifeLessThan(10) as $f)
        {
            $error = new GedcomError();
            $error->gedcom_id = $gedcom->id;
            $error->fami_id = $f->fami_id;
            $error->indi_id = $f->indi_id_wife;
            $error->type_broad = 'marriage age';
            $error->type_specific = 'err: < 10';
            $error->eval_broad = 'error';
            $error->eval_specific = '';
            $error->message = sprintf('Marriage age of ' . ($f->marr_age_wife) . ' years for wife '
                    . $f->indi_id_wife . ' in family ' . ($f->fami_id) . '.');
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
                $error->type_broad = 'multiple parentage';
                $error->type_specific = 'child in >1 family';
                $error->eval_broad = 'warning';
                $error->eval_specific = '';
                $error->message = sprintf('Individual %s is listed as child in %d families', $ind->gedcom_key, $i->count);
                $error->save();
            }
        }
    }

    /**
     * Deletes statistics for this Gedcom and then calculates statistics
     * @param Gedcom $gedcom
     */
    private function populateStats($gedcom)
    {
        // Delete existing stats in stat tables
        $gedcom->parental_ages()->delete();
        $gedcom->marriage_ages()->delete();
        $gedcom->lifespans()->delete();

        // Populate the stat tables, each in a separate transaction
        DB::connection()->disableQueryLog();
        DB::beginTransaction();
        $this->parentalAgeStats($gedcom);
        DB::commit();

        DB::connection()->disableQueryLog();
        DB::beginTransaction();
        $this->marriageAgeStats($gedcom);
        DB::commit();

        DB::connection()->disableQueryLog();
        DB::beginTransaction();
        $this->lifespanStats($gedcom);
        DB::commit();
    }

    /**
     * Calculates lifespan of individuals.
     * @param Gedcom $gedcom
     */
    private function lifespanStats($gedcom)
    {
        foreach ($gedcom->allLifespans() as $i)
        {
            $lifespan = new GedcomStatsLifespans();
            $lifespan->gedcom_id = $gedcom->id;
            $lifespan->indi_id = $i->indi_id;
            $lifespan->birth_event_id = $i->birth_event_id;
            $lifespan->death_event_id = $i->death_event_id;
            $lifespan->lifespan = $i->lifespan;
            $lifespan->est_date = $i->est_date;
            $lifespan->save();
        }
    }

    /**
     * Calculates age of parents at birth of children.
     * @param Gedcom $gedcom
     */
    private function parentalAgeStats($gedcom)
    {
        foreach ($gedcom->parentalAges('wife') as $i)
        {
            $mother_age = new GedcomStatsParents();
            $mother_age->gedcom_id = $gedcom->id;
            $mother_age->fami_id = $i->fami_id;
            $mother_age->par_id = $i->pare_id;
            $mother_age->chil_id = $i->chil_id;
            $mother_age->par_birth_event_id = $i->par_birth_event_id;
            $mother_age->chil_birth_event_id = $i->chil_birth_event_id;
            $mother_age->par_age = $i->parental_age;
            $mother_age->est_date = $i->est_date;
            $mother_age->par_sex = $i->pare_sex;
            $mother_age->save();
        }

        foreach ($gedcom->parentalAges('husb') as $i)
        {
            $father_age = new GedcomStatsParents();
            $father_age->gedcom_id = $gedcom->id;
            $father_age->fami_id = $i->fami_id;
            $father_age->par_id = $i->pare_id;
            $father_age->chil_id = $i->chil_id;
            $father_age->par_birth_event_id = $i->par_birth_event_id;
            $father_age->chil_birth_event_id = $i->chil_birth_event_id;
            $father_age->par_age = $i->parental_age;
            $father_age->est_date = $i->est_date;
            $father_age->par_sex = $i->pare_sex;
            $father_age->save();
        }
    }

    /**
     * Calculates the marriage ages
     * @param Gedcom $gedcom
     */
    private function marriageAgeStats($gedcom)
    {
        //TODO: count marriages per person
        foreach ($gedcom->marriageAges() as $i)
        {
            $marriage_age = new GedcomStatsMarriages();
            $marriage_age->gedcom_id = $gedcom->id;
            $marriage_age->marr_event_id = $i->marr_event_id;
            $marriage_age->fami_id = $i->fami_id;
            $marriage_age->indi_id_husb = $i->indi_id_husb;
            $marriage_age->indi_id_wife = $i->indi_id_wife;
            
            $marriage_age->marr_cnt_husb = null;
            $marriage_age->marr_cnt_wife = null;
            
            $marriage_age->marr_age_husb = $i->marr_age_husb;
            $marriage_age->marr_age_wife = $i->marr_age_wife;
            $marriage_age->est_date_age_husb = $i->est_date_age_husb;
            $marriage_age->est_date_age_wife = $i->est_date_age_wife;
            $marriage_age->save();
        }
    }

}
