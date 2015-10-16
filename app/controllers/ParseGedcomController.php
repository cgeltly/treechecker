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

class ParseGedcomController extends ParseController
{

    private $noteMap = array();
    private $sourceMap = array();
    private $familyMap = array();

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Actions before parsing: 
     * - Set some definitions used during parsing. 
     * @param int $gedcom_id
     */
    protected function doBeforeParse($gedcom_id)
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
     * Actions after parsing: 
     * - Enter place names from Events table into the Geocodes table
     * - Create the families in the familyMap
     * - Add parse errors for non-matched notes in the noteMap
     * - Add parse errors for non-matched sources in the sourceMap
     * @param int $gedcom_id
     */
    protected function doAfterParse($gedcom_id)
    {

        //insert place names and coordinates from the events table into geocodes table
        $eventPlaces = $this->eventPlaces($gedcom_id);

        foreach ($eventPlaces as $eventPlace)
        {
            $geocode = new GedcomGeocode();
            $geocode->gedcom_id = $eventPlace->gedcom_id;
            $geocode->place = $eventPlace->place;
            $geocode->town = null;
            $geocode->region = null;
            $geocode->country = null;
            $geocode->lati = $eventPlace->lati;
            $geocode->long = $eventPlace->long;
            $geocode->checked = 0;
            $geocode->gedcom = 'See events table';
            $geocode->save();
        }        

        //update the events table geo_id with the geocodes table id 
        //based on unique place, latitude and longitude 
        DB::statement("update `events` as `e` 
                        inner join `geocodes` as `g` 
                        on `e`.`place` <=> `g`.`place` 
                            AND `e`.`lati` <=> `g`.`lati`
                            AND `e`.`long` <=> `g`.`long`                            
                        set `e`.`geo_id` = `g`.`id`
                        where `e`.`gedcom_id` = $gedcom_id");

        
        // Create the families in the familyMap
        foreach ($this->familyMap as $f => $r)
        {
            $this->createFamily($f, $r, $gedcom_id);
        }

        // If we reached this point, and there are still notes in the noteMap, add parse errors
        foreach ($this->noteMap as $n => $r)
        {
            $error = new GedcomError();
            $error->gedcom_id = $gedcom_id;
            $error->stage = 'parsing';
            $error->type_broad = 'missing';
            $error->type_specific = 'note definition';
            $error->eval_broad = 'error';
            $error->eval_specific = '';
            $error->message = sprintf('No definition found for NOTE %s on %s', $n, $r);
            $error->save();
        }

        // If we reached this point, and there are still notes in the sourceMap, add parse errors
        foreach ($this->sourceMap as $s => $r)
        {
            $error = new GedcomError();
            $error->gedcom_id = $gedcom_id;
            $error->stage = 'parsing';
            $error->type_broad = 'missing';
            $error->type_specific = 'note definition';
            $error->eval_broad = 'error';
            $error->eval_specific = '';
            $error->message = sprintf('No definition found for SOUR %s on %s', $s, $r);
            $error->save();
        }
    }

    /**
     * Split records per 0 line and import.
     * @param integer $gedcom_id
     * @param string $gedcom
     */
    protected function doImport($gedcom_id, $gedcom)
    {
        foreach (preg_split('/\n+(?=0)/', $gedcom) as $record)
        {
            $this->importRecord($record, $gedcom_id);
        }
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
        elseif (preg_match('/0 (_PLAC |_PLAC_DEFN)/', $gedrec, $match))
        {
            $type = '_PLAC';
            $xref = $type; // Again use type as pseudo XREF.
        }
        else
        {
            // If there's no match, add a parsing error and return
            $error = new GedcomError();
            $error->gedcom_id = $gedcom_id;
            $error->stage = 'parsing';
            $error->type_broad = 'standards';
            $error->type_specific = 'non-standard tags';
            $error->eval_broad = 'warning';
            $error->eval_specific = '';
            $error->message = sprintf('Invalid GEDCOM format: %s', $gedrec);
            $error->save();

            return;
        }

        switch ($type)
        {
            case 'HEAD':
                $this->processHeader($xref, $gedrec, $gedcom_id);
                break;
            case 'INDI':
                $this->processIndividual($xref, $gedrec, $gedcom_id);
                break;
            case 'FAM':
                $this->processFamily($xref, $gedrec, $gedcom_id);
                break;
            case 'NOTE':
                $this->processNote($xref, $gedrec, $gedcom_id);
                break;
            case 'SOUR':
                $this->processSource($xref, $gedrec, $gedcom_id);
                break;
            case '_PLAC':
                $this->processGeocode($gedrec, $gedcom_id);
                break;
            default:
                break;
        }
    }

    /**
     * Creates a GedcomSystem. 
     * @param string $xref
     * @param string $gedrec
     * @param int $gedcom_id
     */
    private function processHeader($xref, $gedrec, $gedcom_id)
    {
        $record = new WT_GedcomRecord($xref, $gedrec, null, $gedcom_id);
        $source = $record->getFacts('SOUR')[0]->getGedcom();

        $system = new GedcomSystem();
        $system->gedcom_id = $gedcom_id;
        $system->system_id = $this->matchTag($source, 'SOUR');
        $system->version_number = $this->matchTag($source, 'VERS');
        $system->product_name = $this->matchTag($source, 'NAME');
        $system->corporation = $this->matchTag($source, 'CORP');
        $system->gedcom = $gedrec;
        $system->save();
    }

    private function matchTag($source, $tag)
    {
        preg_match('/\d ' . $tag . ' (.*)/', $source, $matches);
        return $matches ? $matches[1] : NULL;
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
        $individual->private = $this->isPrivate($gedrec);
        $individual->save();

        $this->processEvents($record, $gedcom_id, $individual->id);
    }

    private function isPrivate($gedrec)
    {
        return str_contains($gedrec, '1 RESN privacy') ||
                str_contains($gedrec, '1 RESN confidential');
    }

    /**
     * Creates a GedcomFamily and possibly GedcomChildren and GedcomEvents. 
     * @param string $xref
     * @param string $gedrec
     * @param int $gedcom_id
     */
    private function processFamily($xref, $gedrec, $gedcom_id)
    {
        // Find the husband and wife in the Gedcom
        $husb = $this->getIndividualKey($gedrec, 'HUSB');
        $wife = $this->getIndividualKey($gedrec, 'WIFE');

        // Find the husband and wife in the database
        $husb_ind = GedcomIndividual::GedcomKey($gedcom_id, $husb)->first();
        $wife_ind = GedcomIndividual::GedcomKey($gedcom_id, $wife)->first();

        // If we can't find either the husband or the wife, 
        // delay family creation until after the whole file has been processed. 
        // Families might be stated before individuals (#16)
        if (($husb && !$husb_ind) || ($wife && !$wife_ind))
        {
            $this->familyMap[$xref] = $gedrec;
        }
        // Otherwise, directly create the family.
        else
        {
            $this->createFamily($xref, $gedrec, $gedcom_id);
        }
    }

    private function createFamily($xref, $gedrec, $gedcom_id)
    {
        $record = new WT_Family($xref, $gedrec, null, $gedcom_id);

        // Find the husband and wife in the Gedcom
        $husb = $this->getIndividualKey($gedrec, 'HUSB');
        $wife = $this->getIndividualKey($gedrec, 'WIFE');
        $husb_ind = $this->retrieveIndividual($gedcom_id, $husb);
        $wife_ind = $this->retrieveIndividual($gedcom_id, $wife);

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
        $this->processEvents($record, $gedcom_id, NULL, $family->id);
    }

    /**
     * Retrieves the key for an individual of a given sex in a family record
     * @param string $gedrec
     * @param string $sex
     * @return string
     */
    private function getIndividualKey($gedrec, $sex)
    {
        if (preg_match('/\n1 ' . $sex . ' @(' . WT_REGEX_XREF . ')@/', $gedrec, $match))
        {
            $result = $match[1];
        }
        else
        {
            $result = '';
        }
        return $result;
    }

    /**
     * Processes NOTE tags, finds its reference. 
     * Creates a GedcomNote if the reference if found, adds a parse error otherwise.
     * @param string $xref
     * @param string $gedrec
     * @param int $gedcom_id
     */
    private function processNote($xref, $gedrec, $gedcom_id)
    {
        $record = new WT_Note($xref, $gedrec, null, $gedcom_id);

        // Find the Note in the noteMap 
        if (array_key_exists($xref, $this->noteMap))
        {
            $ref = $this->noteMap[$xref];

            // Create the GedcomNote
            $this->createNote($xref, $gedrec, $gedcom_id, $ref, $record->getNote());

            // Remove the key from the noteMap
            unset($this->noteMap[$xref]);
        }
        // If the Note doesn't exist, add a parse error
        else
        {
            $error = new GedcomError();
            $error->gedcom_id = $gedcom_id;
            $error->stage = 'parsing';
            $error->type_broad = 'missing';
            $error->type_specific = 'note missing';
            $error->eval_broad = 'error';
            $error->eval_specific = '';
            $error->message = sprintf('No NOTE reference found for %s', $xref);
            $error->save();
        }
    }

    /**
     * Creates a new GedcomNote and finds it's reference(s)
     * @param string $xref
     * @param string $gedrec
     * @param int $gedcom_id
     * @param string $ref
     * @param string $note_text
     */
    private function createNote($xref, $gedrec, $gedcom_id, $ref, $note_text)
    {
        $note = new GedcomNote();
        $note->gedcom_id = $gedcom_id;
        $note->gedcom_key = $xref;
        if (starts_with($ref, 'I'))
        {
            $note->indi_id = substr($ref, 1);
        }
        else if (starts_with($ref, 'F'))
        {
            $note->fami_id = substr($ref, 1);
        }
        else if (starts_with($ref, 'E'))
        {
            $note->even_id = substr($ref, 1);
        }
        $note->note = $note_text;
        $note->gedcom = $gedrec;
        $note->save();
    }

    /**
     * Processes SOUR tags, finds its reference. 
     * Creates a GedcomSource if the reference if found, adds a parse error otherwise.
     * @param string $xref
     * @param string $gedrec
     * @param int $gedcom_id
     */
    private function processSource($xref, $gedrec, $gedcom_id)
    {
        // Find the Source in the sourceMap 
        if (array_key_exists($xref, $this->sourceMap))
        {
            $ref = $this->sourceMap[$xref];

            // Create the GedcomSource
            $this->createSource($xref, $gedrec, $gedcom_id, $ref);

            // Remove the key from the sourceMap
            unset($this->sourceMap[$xref]);
        }
        // If the Source doesn't exist, add a parse error
        else
        {
            $error = new GedcomError();
            $error->gedcom_id = $gedcom_id;
            $error->stage = 'parsing';
            $error->type_broad = 'missing';
            $error->type_specific = 'source missing';
            $error->eval_broad = 'error';
            $error->eval_specific = '';
            $error->message = sprintf('No SOUR reference found for %s', $xref);
            $error->save();
        }
    }

    /**
     * Creates a new GedcomSource and finds it's reference(s)
     * @param string $xref
     * @param string $gedrec
     * @param int $gedcom_id
     * @param string $ref
     */
    private function createSource($xref, $gedrec, $gedcom_id, $ref)
    {
        $source = new GedcomSource();
        $source->gedcom_id = $gedcom_id;
        $source->gedcom_key = $xref;

        // Set title
        if (preg_match('/\n1 TITL (.+)/', $gedrec, $match))
        {
            $source->title = $match[1];
        }
        else if (preg_match('/\n1 ABBR (.+)/', $gedrec, $match))
        {
            $source->title = $match[1];
        }
        else
        {
            $source->title = $xref;
        }

        // Set references
        if (starts_with($ref, 'I'))
        {
            $source->indi_id = substr($ref, 1);
        }
        else if (starts_with($ref, 'F'))
        {
            $source->fami_id = substr($ref, 1);
        }
        else if (starts_with($ref, 'E'))
        {
            $source->even_id = substr($ref, 1);
        }

        $source->gedcom = $gedrec;
        $source->save();
    }

    /**
     * Create Geocode (place definition) record 
     * @param string $gedrec
     * @param int $gedcom_id
     */
    private function processGeocode($gedrec, $gedcom_id)
    {
        //default null values for attributes
        $place = null;
        $latitude = 99.9999999;
        $longitude = 999.9999999;

        if (preg_match('/(?:0 _PLAC) +(.+)/', $gedrec, $match))
        {

            //'RootsMagic' and 'Next Generation of Genealogy Sitebuilding' 
            //GEDCOM exports have separate place definitions under the _PLAC tag, 
            //which may be linked to events via the place name, e.g.
            //0 @I1235@ INDI
            //1 BIRT
            //2 DATE 1689
            //2 PLAC Brögbern
            //
            //0 _PLAC Brögbern
            //1 MAP
            //2 LATI N52,5666667
            //2 LONG E7,3666667

            if ($match[1])
            {
                $place = $match[1];
            }

            //Match LATI/LONG in RootsMagic files, which use N,S,W,E in the coordinates 
            if (preg_match('/\n2 LATI (N|S)(\d{1,2})(,|.)(\d{1,7})/', $gedrec, $match))
            {
                //$match[1] = N or S; $match[2] = degree integer; $match[3] = decimal point/comma 
                //$match[4] = numbers after decimal
                //convert to numeric latitude - negative if southern hemisphere
                switch ($match[1])
                {
                    case 'N':
                        $latitude = $match[2] . '.' . $match[4];
                        break;
                    case 'S':
                        $latitude = ($match[2] * -1) . '.' . $match[4];
                        break;
                    default:
                        break;
                }
            }

            if (preg_match('/\n2 LONG (W|E)(\d{1,3})(,|.)(\d{1,7})/', $gedrec, $match))
            {
                //$match[1] = N or S; $match[2] = degree integer; $match[3] = decimal point/comma 
                //$match[4] = numbers after decimal
                //convert to numeric latitude - negative if western hemisphere
                switch ($match[1])
                {
                    case 'E':
                        $longitude = $match[2] . '.' . $match[4];
                        break;
                    case 'W':
                        $longitude = ($match[2] * -1) . '.' . $match[4];
                        break;
                    default:
                        break;
                }
            }

            //Match LATI/LONG in 'Next Generation of Genealogy Sitebuilding' files, 
            //which do not use N,S,W,E in the coordinates 
            if (preg_match('/\n2 LATI (-|)(\d{1,2})(,|.)(\d{1,7})/', $gedrec, $match))
            {
                //$match[1] = - or NULL; $match[2] = degree integer; $match[3] = decimal point/comma 
                //$match[4] = numbers after decimal

                $latitude = $match[1] . $match[2] . $match[3] . $match[4];
            }

            if (preg_match('/\n2 LONG (-|)(\d{1,3})(,|.)(\d{1,7})/', $gedrec, $match))
            {
                //$match[1] = - or NULL; $match[2] = degree integer; $match[3] = decimal point/comma 
                //$match[4] = numbers after decimal

                $longitude = $match[1] . $match[2] . $match[3] . $match[4];
            }
        }
        elseif (preg_match('/0 _PLAC_DEFN/', $gedrec))
        {
            //'Legacy' GEDCOM exports program have separate place definitions 
            //under the _PLAC_DEFN tag, which again may be linked via the place name, e.g. 
            //
            //0 @I3@ INDI
            //1 DEAT
            //2 DATE 14 Apr 1976
            //2 PLAC Hasselt, Limburg, Belgium
            //
            //0 _PLAC_DEFN
            //1 PLAC Hasselt, Limburg, Belgium
            //2 ABBR Hasselt, Limburg, BEL
            //2 MAP
            //3 LATI N51.2
            //3 LONG E5.41666666666667

            if (preg_match('/(?:1 PLAC) +(.+)/', $gedrec, $match))
            {
                $place = $match[1];
            }

            //Match LATI/LONG, which use N,S,W,E in the coordinates 
            //and may exclude any decimals
            if (preg_match('/\n3 LATI (N|S)(\d{1,2})(.\d{1,7}.*?)?/', $gedrec, $match))
            {
                //$match[1] = N or S; $match[2] = degree integer; 
                //$match[3] = decimal point and numbers after
                //convert to numeric latitude - negative if southern hemisphere
                switch ($match[1])
                {
                    case 'N':
                        $latitude = $match[2] . $this->decimalsExist($match);
                        break;
                    case 'S':
                        $latitude = ($match[2] * -1) . $this->decimalsExist($match);
                        break;
                    default:
                        break;
                }
            }

            if (preg_match('/\n3 LONG (W|E)(\d{1,3})(.\d{1,7}.*?)?/', $gedrec, $match))
            {
                //$match[1] = N or S; $match[2] = degree integer; 
                //$match[3] = decimal point and numbers after
                //$numbers = $match[3];
                //convert to numeric latitude - negative if western hemisphere
                switch ($match[1])
                {
                    case 'E':
                        $longitude = $match[2] . $this->decimalsExist($match);
                        break;
                    case 'W':
                        $longitude = ($match[2] * -1) . $this->decimalsExist($match);
                        break;
                    default:
                        break;
                }
            }
        }

        $geocode = new GedcomGeocode();
        $geocode->gedcom_id = $gedcom_id;
        $geocode->place = $place;
        $geocode->lati = $latitude;
        $geocode->long = $longitude;
        $geocode->gedcom = $gedrec;
        $geocode->save();
    }

    /*
     * Checks for missing decimal numbers and returns blank string if so.
     * @param array $match
     */

    private function decimalsExist($match)
    {
        if (!array_key_exists(3, $match))
        {
            return '';
        }
        else
        {
            return $match[3];
        }
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
                $child->gedcom_id = $gedcom_id;
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
                $error->type_broad = 'data integrity';
                $error->type_specific = 'no @I ref. for child';
                $error->eval_broad = 'error';
                $error->eval_specific = '';
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
     * @return array
     */
    private function processEvents($record, $gedcom_id, $indi_id = NULL, $fami_id = NULL)
    {
        foreach ($record->getFacts() as $fact)
        {
            $event = NULL;

            // Retrieve the date and place
            $date = $this->retrieveDate($fact, $gedcom_id, $indi_id, $fami_id);
            $place = $this->retrievePlace($fact);
            $latitude = $this->retrieveLati($fact);
            $longitude = $this->retrieveLong($fact);

            //Match LATI/LONG, which use N,S,W,E in the coordinates 
            //and may exclude the decimal
            if (preg_match('/(N|S)(\d{1,2})(.\d{1,7}.*?)?/', $latitude, $match))
            {
                //$match[1] = N or S; $match[2] = degree integer; 
                //$match[3] = decimal point and numbers after
                //convert to numeric latitude - negative if southern hemisphere
                switch ($match[1])
                {
                    case 'N':
                        $latitude = $match[2] . $this->decimalsExist($match);
                        break;
                    case 'S':
                        $latitude = ($match[2] * -1) . $this->decimalsExist($match);
                        break;
                    default:
                        break;
                }
            }

            if (preg_match('/(W|E)(\d{1,3})(.\d{1,7}.*?)?/', $longitude, $match))
            {
                //$match[1] = N or S; $match[2] = degree integer; 
                //$match[3] = decimal point and numbers after
                //convert to numeric latitude - negative if western hemisphere
                switch ($match[1])
                {
                    case 'E':
                        $longitude = $match[2] . $this->decimalsExist($match);
                        break;
                    case 'W':
                        $longitude = ($match[2] * -1) . $this->decimalsExist($match);
                        break;
                    default:
                        break;
                }
            }

            // Create the event, except with the following tags:
            // CHAN 
            // NEW 
            // _UID 
            // FAMS 
            // FAMC
            // CHIL 
            // NAME
            // CREA
            // _FID
            // OBJE
            // HUSB
            // WIFE
            // SEX
            //NOTE
            //SOUR
            if (!in_array($fact->getTag(), array('CHAN', 'NEW', '_UID', 'FAMS', 'FAMC', 'CHIL',
                        'NAME', 'CREA', '_FID', 'OBJE', 'HUSB', 'WIFE', 'SEX', 'NOTE', 'SOUR',)))
            {
                $time = new DateTime();
                $event = array(
                    'gedcom_id' => $gedcom_id,
                    'indi_id' => $indi_id,
                    'fami_id' => $fami_id,
                    'event' => $fact->getTag(),
                    'date' => $date ? $date['date'] : NULL,
                    'est_date' => $date ? $date['est_date'] : NULL,
                    'datestring' => $date ? $date['string'] : NULL,
                    'place' => $place,
                    'lati' => $latitude,
                    'long' => $longitude,
                    'gedcom' => $fact->getGedcom(),
                    'created_at' => $time,
                    'updated_at' => $time,
                );
            }
            else if ($fact->getTag() == 'NOTE')
            {
                $this->addOrCreateNote($fact, $gedcom_id, $indi_id, $fami_id);
            }
            else if ($fact->getTag() == 'SOUR')
            {
                $this->addSource($fact, $indi_id, $fami_id);
            }

            if ($event)
            {
                // Check if an event exists for this event type
                $this->checkEventExists($event, $gedcom_id, $indi_id, $fami_id);

                // Insert the event into the database 
                $event_id = DB::table('events')->insertGetId($event);

                // Parse event notes and sources
                $this->parseEventNotes($fact, $gedcom_id, $event_id);
                $this->parseEventSources($fact, $event_id);
            }
        }
    }

    /**
     * Checks whether an event exists. If so, creates a parse warning.
     * @param GedcomEvent $event
     * @param integer $gedcom_id
     * @param integer $indi_id
     * @param integer $fami_id
     * @return boolean
     */
    private function checkEventExists($event, $gedcom_id, $indi_id = NULL, $fami_id = NULL)
    {
        $event_type = $event['event'];

        if ($indi_id)
        {
            if (GedcomEvent::where('indi_id', $indi_id)->where('event', $event_type)->first())
            {
                $i = GedcomIndividual::find($indi_id);

                $error = new GedcomError();
                $error->gedcom_id = $gedcom_id;
                $error->indi_id = $indi_id;
                $error->stage = 'parsing';
                $error->type_broad = 'event';
                $error->type_specific = 'duplicate event';
                $error->eval_broad = 'warning';
                $error->eval_specific = '';
                $error->message = sprintf('Duplicate event of type %s for individual %s', $event_type, $i->gedcom_key);
                $error->save();
            }
        }
        else if ($fami_id)
        {
            if (GedcomEvent::where('fami_id', $fami_id)->where('event', $event_type)->first())
            {
                $f = GedcomFamily::find($fami_id);

                $error = new GedcomError();
                $error->gedcom_id = $gedcom_id;
                $error->fami_id = $fami_id;
                $error->stage = 'parsing';
                $error->type_broad = 'event';
                $error->type_specific = 'duplicate event';
                $error->eval_broad = 'warning';
                $error->eval_specific = '';
                $error->message = sprintf('Duplicate event of type %s for family %s', $event_type, $f->gedcom_key);
                $error->save();
            }
        }
    }

    /**
     * Either creates a note in the noteMap for later lookup 
     * or creates the note directly (for embedded notes)
     * @param WT_Fact $fact
     * @param int $gedcom_id
     * @param int $indi_id
     * @param int $fami_id
     */
    private function addOrCreateNote($fact, $gedcom_id, $indi_id, $fami_id)
    {
        // Create a reference for later lookup
        $ref = $indi_id ? ('I' . $indi_id) : ('F' . $fami_id);

        // If there is a reference to a note; save that in the noteMap
        if (starts_with($fact->getValue(), '@'))
        {
            $key = trim($fact->getValue(), '@');
            $this->noteMap[$key] = $ref;
        }
        // If it's an embedded note, save it directly
        else
        {
            $this->createNote(NULL, $fact->getGedcom(), $gedcom_id, $ref, $fact->getValue());
        }
    }

    /**
     * Parses embedded and referenced notes on an event.
     * @param WT_Fact $fact
     * @param int $gedcom_id
     * @param int $event_id
     */
    private function parseEventNotes($fact, $gedcom_id, $event_id)
    {
        // Create a reference for later lookup
        $ref = 'E' . $event_id;

        // Parse notes on an event
        preg_match_all('/\n2 NOTE ?(.*(?:\n3.*)*)/', $fact->getGedcom(), $matches);
        foreach ($matches[1] as $match)
        {
            $note = preg_replace("/\n3 CONT ?/", "\n", $match);
            if (preg_match('/@(' . WT_REGEX_XREF . ')@/', $note, $nmatch))
            {
                // If there is a reference to a note; save that in the noteMap
                $this->noteMap[$nmatch[1]] = $ref;
            }
            else
            {
                // If it's an embedded note, save it directly
                $this->createNote(NULL, $match, $gedcom_id, $ref, $note);
            }
        }
    }

    /**
     * Creates a note in the sourceMap for later lookup
     * @param WT_Fact $fact
     * @param int $indi_id
     * @param int $fami_id
     */
    private function addSource($fact, $indi_id, $fami_id)
    {
        // Create a reference for later lookup
        $ref = $indi_id ? ('I' . $indi_id) : ('F' . $fami_id);
        $key = trim($fact->getValue(), '@');
        $this->sourceMap[$key] = $ref;
    }

    /**
     * Parses referenced sources on an event.
     * @param WT_Fact $fact
     * @param int $event_id
     */
    private function parseEventSources($fact, $event_id)
    {
        // Create a reference for later lookup
        $ref = 'E' . $event_id;

        // Parse notes on an event
        preg_match_all('/\n2 SOUR ?(.*(?:\n3.*)*)/', $fact->getGedcom(), $matches);
        foreach ($matches[1] as $match)
        {
            if (preg_match('/@(' . WT_REGEX_XREF . ')@/', $match, $nmatch))
            {
                // If there is a reference to a source; save that in the sourceMap
                $this->sourceMap[$nmatch[1]] = $ref;
            }
        }
    }

    /**
     * Retrieve the date from a fact. 
     * @param WT_Fact $fact
     * @param int $gedcom_id
     * @param int $indi_id
     * @param int $fami_id
     * @return array
     */
    private function retrieveDate($fact, $gedcom_id, $indi_id, $fami_id)
    {
        $result = array();
        $date = $fact->getDate();

        //webtrees date processing 
        if (get_class($date->date1) != 'NumericGregorianDate')
        {
            if ($date->isOk())
            {
                $result['date'] = implode('-', array($date->date1->y, $date->date1->m, $date->date1->d));
                $result['string'] = $fact->getAttribute('DATE');
                $result['est_date'] = $date->estimate;
                return $result;
            }
        }
        //additional date processing to deal with purely numeric dates, e.g. 12-03-1786
        if (get_class($date->date1) == 'NumericGregorianDate')
        {
            if (checkdate($date->date1->m, $date->date1->d, $date->date1->y))
            {
                $result['date'] = implode('-', array($date->date1->y, $date->date1->m, $date->date1->d));
                $result['string'] = $fact->getAttribute('DATE');
                $result['est_date'] = $date->estimate;
                return $result;
            }
            else
            {
                $error = new GedcomError();
                $error->gedcom_id = $gedcom_id;
                $error->indi_id = $indi_id;
                $error->fami_id = $fami_id;
                $error->stage = 'parsing';
                $error->type_broad = 'date format';
                $error->type_specific = 'impossible or US format';
                $error->eval_broad = 'error';
                $error->eval_specific = '';
                $error->message = sprintf('Impossible or US formatted date ' . implode('-', array($date->date1->y, $date->date1->m, $date->date1->d)) . '');
                $error->save();
            }
        }
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
     * Retrieve the latitude from a fact
     * @param WT_Lati $fact
     * @return string
     */
    private function retrieveLati($fact)
    {
        $result = $fact->getLati()->getGedcomName();
        if (empty($result))
        {
            $result = NULL;
        }
        return $result;
    }

    /**
     * Retrieve the longitude from a fact
     * @param WT_Long $fact
     * @return string
     */
    private function retrieveLong($fact)
    {
        $result = $fact->getLong()->getGedcomName();
        if (empty($result))
        {
            $result = NULL;
        }
        return $result;
    }
    
    // Run a group by query to get unique place names from the events table.
    private function eventPlaces($gedcom_id)
    {
        return DB::table('events')
                        ->select('gedcom_id', 'id', 'place', 'lati', 'long')
                        ->where('gedcom_id', $gedcom_id)
                        ->groupBy('place', 'lati', 'long')
                        ->get();
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
                $error->type_broad = 'gender';
                $error->type_specific = 'parent of other sex';
                $error->eval_broad = 'error';
                $error->eval_specific = '';
                $error->message = sprintf('Individual %s is listed as %s in family record, '
                        . 'but listed as %s in individual record.', $ind->gedcom_key, $gender === 'm' ? 'husband' : 'wife', $ind->sex === 'm' ? 'male' : 'female');
                $error->save();
            }
        }
    }

}
