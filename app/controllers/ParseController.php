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
    
    public function __construct()
    {
        parent::__construct();
        
        //prevent access to controller methods without login
        $this->beforeFilter('auth');
    }    

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
        $gedcom->geocodes()->delete();
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
            case '_PLAC':
                $this->processGeocode($gedrec, $gedcom_id);
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

        $this->processEvents($record, $gedcom_id, $individual->id);        
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
        $this->processEvents($record, $gedcom_id, NULL, $family->id);
    }

    
    /**
     * Create Geocode (place definition) record 
     * @param string $gedrec
     * @param int $gedcom_id
     */
    private function processGeocode($gedrec, $gedcom_id)
    {

        //deafult null values for attributes
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
                        $latitude = ($match[2]*-1) . '.' . $match[4];
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
                        $longitude = ($match[2]*-1) . '.' . $match[4];
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
                        $latitude = ($match[2]*-1) . $this->decimalsExist($match);
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
                        $longitude = ($match[2]*-1) . $this->decimalsExist($match);
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
     * @return array
     */
    private function processEvents($record, $gedcom_id, $indi_id = NULL, $fami_id = NULL)
    {
        $events = array();
        foreach ($record->getFacts() as $fact)
        {
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
                        $latitude = ($match[2]*-1) . $this->decimalsExist($match);
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
                        $longitude = ($match[2]*-1) . $this->decimalsExist($match);
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
                'NAME', 'CREA', '_FID', 'OBJE', 'HUSB', 'WIFE', 'SEX', 'NOTE', 'SOUR', )))
            {
                $time = new DateTime();
                $events[] = array(
                    'gedcom_id' => $gedcom_id,
                    'indi_id' => $indi_id,
                    'fami_id' => $fami_id,
                    'event' => $fact->getTag(),
                    'date' => $date ? $date['date'] : NULL,
                    'estimate' => $date ? $date['estimate'] : NULL,
                    'datestring' => $date ? $date['string'] : NULL,
                    'place' => $place,
                    'lati' => $latitude,
                    'long' => $longitude,                    
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
     * @param $gedcom_id
     * @param $indi_id
     * @param $fami_id
     * @return array
     */
    private function retrieveDate($fact, $gedcom_id, $indi_id, $fami_id)
    {
        $result = array();
        $date = $fact->getDate();

        //webtrees date processing 
        if (get_class($date->date1) != 'NumericGregorianDate') 
        {
            if($date->isOk())
            {    
            $result['date'] = implode('-', array($date->date1->y, $date->date1->m, $date->date1->d));
            $result['string'] = $fact->getAttribute('DATE');
            $result['estimate'] = $date->estimate;
            return $result;
            }
        }
        //additional date processing to deal with purely numeric dates, e.g. 12-03-1786
        if (get_class($date->date1) == 'NumericGregorianDate')
            {
                if(checkdate($date->date1->m, $date->date1->d, $date->date1->y))
                {
                $result['date'] = implode('-', array($date->date1->y, $date->date1->m, $date->date1->d));
                $result['string'] = $fact->getAttribute('DATE');
                $result['estimate'] = $date->estimate;
                return $result;
                }
                else
                {
                    $error = new GedcomError();
                    $error->gedcom_id = $gedcom_id;
                    $error->indi_id = $indi_id;
                    $error->fami_id = $fami_id;
                    $error->stage = 'parsing';
                    $error->classification = 'incorrect';
                    $error->severity = 'error';
                    $error->message = sprintf('Impossible or US formatted date ' . implode('-', array($date->date1->y, $date->date1->m, $date->date1->d)) .'');
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
