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

class ParseController extends BaseController
{

    /**
     * Parses a Gedcom and creates parse errors when necessary.
     * @param int $gedcom_id
     * @return void (if finished)
     */
    public function getParse($gedcom_id)
    {
        // Get the GEDCOM
        $gedcom = Gedcom::findOrFail($gedcom_id);
        Session::put('progress', 1);
        Session::save();

        // Delete the related individuals, families and errors
        $gedcom->individuals()->delete();
        $gedcom->families()->delete();
        $gedcom->errors()->delete();
        Session::put('progress', 2);
        Session::save();

        // Retrieve the file folder
        $rel_storage_dir = $gedcom->path;
        $default_path = storage_path() . DIRECTORY_SEPARATOR . 'uploads';
        $abs_storage_dir = $default_path . $rel_storage_dir;
        chdir($abs_storage_dir);

        // Set some definitions
        $this->setDefines($gedcom_id);


        $filecount = 0;
        $files = glob("*");
        if ($files)
        {
            $filecount = count($files);
        }
         
        // Loop through the chunked files and parse/import them
        for ($i = 1; file_exists($i); ++$i)
        {
            $this->importRecords($i, $gedcom_id);
            Session::put('progress', min(floor(($i/$filecount)*100), 99));
            Session::save();
        }

        // Set the file as parsed, but not checked for errors
        $gedcom->parsed = true;
        $gedcom->error_checked = false;
        $gedcom->save();

        Session::put('progress', 100);
        Session::save();

        return;
    }

    /**
     * Returns the progress of the current parse session. 
     * @return Response the progress in JSON format
     */
    public function getProgress()
    {
        $progress = Session::has('progress') ? Session::get('progress') : 0;
        return Response::json(array($progress));
    }

    /**
     * Set some definitions used during parsing. 
     * @param int $gedcom_id
     */
    private function setDefines($gedcom_id)
    {
        if (!defined('WT_GED_ID'))
        {
            define('WT_GED_ID', $gedcom_id);
            define('WT_REGEX_XREF', '[A-Za-z0-9:_-]+');
            define('WT_REGEX_TAG', '[_A-Z][_A-Z0-9]*');
            define('WT_USER_ACCESS_LEVEL', 0);
            define('WT_WEBTREES', 'webtrees');
            define('WT_UTF8_BOM', "\xEF\xBB\xBF");
            define('WT_UTF8_LRM', "\xE2\x80\x8E");
            define('WT_UTF8_RLM', "\xE2\x80\x8F");
        }
    }

    /**
     * Imports all records, per 0 line. 
     * @param string $file
     * @param int $gedcom_id
     */
    private function importRecords($file, $gedcom_id)
    {
        // Retrieve the contents of the file
        $gedcom = file_get_contents($file);

        // Allow sixty seconds of execution time, start a transaction
        set_time_limit(180);
        // Query log is save in memory, so need to disable it for large file parsing
        DB::connection()->disableQueryLog();
        DB::beginTransaction();

        // Split records per 0 line and import
        foreach (preg_split('/\n+(?=0)/', $gedcom) as $record)
        {
            $this->importRecord($record, $gedcom_id);
        }

        // End the transaction
        DB::commit();
    }

    /**
     * Import record into database (COPIED/MODIFIED FROM webtrees). 
     * This function will parse the given GEDCOM record and add it to the database. 
     * @author webtrees
     * @param string $gedcom the raw gedcom record to parse
     * @param int $gedcom_id import the record into this gedcom
     */
    private function importRecord($gedcom, $gedcom_id)
    {
        // Standardise gedcom format
        $gedrec = \Webtrees\Import::reformat_record_import($gedcom);
        // import different types of records
        if (preg_match('/^0 @(' . WT_REGEX_XREF . ')@ (' . WT_REGEX_TAG . ')/', $gedrec, $match))
        {
            list(, $xref, $type) = $match;
            // check for a _UID, if the record doesn't have one, add one
            if (!strpos($gedrec, "\n1 _UID "))
            {
                $gedrec .= "\n1 _UID " . \Webtrees\Import::uuid();
            }
        }
        elseif (preg_match('/0 (HEAD|TRLR)/', $gedrec, $match))
        {
            $type = $match[1];
            $xref = $type; // For HEAD/TRLR, use type as pseudo XREF.
        }
        else
        {
            // If there's no match, add a parsing error and return
            $error = new GedcomError();
            $error->gedcom_id = $gedcom_id;
            $error->stage = 'parsing';
            $error->classification = 'missing';
            $error->severity = 'fatal';
            $error->message = sprintf('Invalid GEDCOM format: %s', $gedrec);
            $error->save();

            return;
        }

        switch ($type)
        {
            case 'INDI':
                $this->processIndividual($xref, $gedrec, $gedcom_id);
                break;
            case 'FAM':
                $this->processFamily($xref, $gedrec, $gedcom_id);
                break;
            default:
                break;
        }
    }

    /**
     * Creates a GedcomIndividual and possibly GedcomEvents. 
     * @param string $xref
     * @param string $gedrec
     * @param int $gedcom_id
     */
    private function processIndividual($xref, $gedrec, $gedcom_id)
    {
        $record = new WT_Individual($xref, $gedrec, null, $gedcom_id);

        $name = $record->getAllNames()[0];
        $givn = trim($name["givn"]);
        $surname = trim($name["surname"]);

        $individual = new GedcomIndividual();
        $individual->gedcom_id = $gedcom_id;
        $individual->first_name = $givn;
        $individual->last_name = $surname;
        $individual->sex = strtolower($record->getSex());
        $individual->gedcom_key = $xref;
        $individual->gedcom = $gedrec;
        $individual->save();

        $this->processEvents($record, $individual->id);
    }

    /**
     * Creates a GedcomFamily and possibly GedcomChildren and GedcomEvents. 
     * @param string $xref
     * @param string $gedrec
     * @param int $gedcom_id
     */
    private function processFamily($xref, $gedrec, $gedcom_id)
    {
        $record = new WT_Family($xref, $gedrec, null, $gedcom_id);

        // Find the husband and wife in the Gedcom
        if (preg_match('/\n1 HUSB @(' . WT_REGEX_XREF . ')@/', $gedrec, $match))
        {
            $husb = $match[1];
        }
        else
        {
            $husb = '';
        }
        if (preg_match('/\n1 WIFE @(' . WT_REGEX_XREF . ')@/', $gedrec, $match))
        {
            $wife = $match[1];
        }
        else
        {
            $wife = '';
        }

        // Find the husband and wife in the database
        $husb_ind = GedcomIndividual::GedcomKey($gedcom_id, $husb)->first();
        $wife_ind = GedcomIndividual::GedcomKey($gedcom_id, $wife)->first();

        // Create the GedcomFamily
        $family = new GedcomFamily();
        $family->gedcom_id = $gedcom_id;
        $family->indi_id_husb = $husb_ind ? $husb_ind->id : NULL;
        $family->indi_id_wife = $wife_ind ? $wife_ind->id : NULL;
        $family->gedcom_key = $xref;
        $family->gedcom = $gedrec;
        $family->save();

        // Check the gender of husband and wife
        $this->checkGender($gedcom_id, $family->id, $husb_ind, 'm');
        $this->checkGender($gedcom_id, $family->id, $wife_ind, 'f');

        // Process the GedcomChildren and GedcomEvents
        $this->processChildren($record, $gedcom_id, $family);
        $this->processEvents($record, NULL, $family->id);
    }

    /**
     * Creates the GedcomChildren for a GedcomFamily. 
     * @param string $record
     * @param int $gedcom_id
     * @param GedcomFamily $family
     */
    private function processChildren($record, $gedcom_id, $family)
    {
        foreach ($record->getChildren() as $child)
        {
            // Try to find the individual in the database
            $ind = GedcomIndividual::GedcomKey($gedcom_id, $child)->first();
            if ($ind)
            {
                // If found, create a GedcomChild
                $child = new GedcomChild();
                $child->fami_id = $family->id;
                $child->indi_id = $ind->id;
                $child->save();
            }
            else
            {
                // If not found, add a parsing error
                $error = new GedcomError();
                $error->gedcom_id = $gedcom_id;
                $error->fami_id = $family->id;
                $error->stage = 'parsing';
                $error->classification = 'missing';
                $error->severity = 'error';
                $error->message = sprintf('No record for individual %s, but listed as a child in family %s.', $child, $family->gedcom_key);
                $error->save();
            }
        }
    }

    /**
     * Create events for either an individual or family record. 
     * @param WT_GedcomRecord $record
     * @param int $indi_id
     * @param int $fami_id
     */
    private function processEvents($record, $indi_id = NULL, $fami_id = NULL)
    {
        $events = array();
        foreach ($record->getFacts() as $fact)
        {
            // Retrieve the date and place
            $date = $this->retrieveDate($fact);
            $place = $this->retrievePlace($fact);

            // Create the event (but not when it's CHAN or NEW, #14) 
            if (!in_array($fact->getTag(), array('CHAN', 'NEW')))
            {
                $time = new DateTime();
                $events[] = array(
                    'indi_id' => $indi_id,
                    'fami_id' => $fami_id,
                    'event' => $fact->getTag(),
                    'date' => $date ? $date['date'] : NULL,
                    'datestring' => $date ? $date['string'] : NULL,
                    'place' => $place,
                    'gedcom' => $fact->getGedcom(),
                    'created_at' => $time,
                    'updated_at' => $time,
                );
            }
        }

        if ($events)
        {
            DB::table('events')->insert($events);
        }
    }

    /**
     * Retrieve the date from a fact. 
     * @param WT_Fact $fact
     * @return array
     */
    private function retrieveDate($fact)
    {
        $result = array();
        $date = $fact->getDate();
        if ($date->isOk())
        {
            $result['date'] = implode('-', array($date->date1->y, $date->date1->m, $date->date1->d));
            $result['string'] = $fact->getAttribute('DATE');
        }
        return $result;
    }

    /**
     * Retrieve the place from a fact
     * @param WT_Fact $fact
     * @return string
     */
    private function retrievePlace($fact)
    {
        $result = $fact->getPlace()->getGedcomName();
        if (empty($result))
        {
            $result = NULL;
        }
        return $result;
    }

    /**
     * Checks whether the given husband/wife is actually male/female. 
     * If not, a GedcomError will be created.
     * @param int $gedcom_id
     * @param int $family_id
     * @param GedcomIndividual $ind
     * @param string $gender
     */
    private function checkGender($gedcom_id, $family_id, $ind, $gender)
    {
        if ($ind)
        {
            if (!in_array($ind->sex, array('u', $gender)))
            {
                $error = new GedcomError();
                $error->gedcom_id = $gedcom_id;
                $error->indi_id = $ind->id;
                $error->fami_id = $family_id;
                $error->stage = 'parsing';
                $error->classification = 'missing';
                $error->severity = 'error';
                $error->message = sprintf('Individual %s is listed as %s in family record, '
                        . 'but listed as %s in individual record.', $ind->gedcom_key, $gender === 'm' ? 'husband' : 'wife', $ind->sex === 'm' ? 'male' : 'female');
                $error->save();
            }
        }
    }

}
