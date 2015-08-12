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

class ParseJsonController extends ParseController
{

    public function __construct()
    {
        parent::__construct();
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

        if ($this->allowedAccess($gedcom->user_id))
        {
            Session::put('progress', 1);
            Session::save();

            // Delete the related individuals, families and errors
            $gedcom->individuals()->delete();
            $gedcom->families()->delete();
            $gedcom->geocodes()->delete();
            $gedcom->errors()->delete();
            $gedcom->notes()->delete();

            Session::put('progress', 2);
            Session::save();

            // Retrieve the file folder
            $abs_storage_dir = Config::get('app.upload_dir') . $gedcom->path;
            chdir($abs_storage_dir);

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
                Session::put('progress', min(floor(($i / $filecount) * 100), 99));
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
        else
        {
            return Response::make('Unauthorized', 401);
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

        // Allow 3 minutes of execution time
        set_time_limit(180);

        // Query log is save in memory, so we need to disable it for large file parsing
        DB::connection()->disableQueryLog();

        // Start the transaction
        DB::beginTransaction();

        // Decode the JSON 
        $json = json_decode($gedcom);
        $g = $json[0];
        //updateGedcom($gedcom_id);
        $this->createSystem($gedcom_id, $g->system);

        foreach ($g->individuals as $i)
        {
            $this->createIndividual($gedcom_id, $i);
        }

        foreach ($g->families as $f)
        {
            $this->createFamily($gedcom_id, $f);
        }
        
        // End the transaction
        DB::commit();
    }

    /**
     * Creates a GedcomSystem from the JSON input
     * @param integer $gedcom_id
     * @param object $s
     */
    private function createSystem($gedcom_id, $s)
    {
        $system = new GedcomSystem();
        $system->gedcom_id = $gedcom_id;
        foreach ($s as $key => $value)
        {
            $system->$key = $value;
        }
        $system->save();
    }

    private function createIndividual($gedcom_id, $i)
    {
        $events = array();
        $notes = array();
        $sources = array();

        $individual = new GedcomIndividual();
        $individual->gedcom_id = $gedcom_id;
        foreach ($i as $key => $value)
        {
            switch ($key)
            {
                case 'events':
                    $events = $value;
                    break;
                case 'notes':
                    $notes = $value;
                    break;
                case 'sources':
                    $sources = $value;
                    break;
                default:
                    $individual->$key = $value;
                    break;
            }
        }
        $individual->save();

        foreach ($events as $e)
        {
            $this->createEvent($gedcom_id, $e, $individual->id);
        }
        foreach ($notes as $n)
        {
            $this->createNote($gedcom_id, $n, $individual->id);
        }
        foreach ($sources as $s)
        {
            $this->createSource($gedcom_id, $s, $individual->id);
        }
    }

    private function createFamily($gedcom_id, $f)
    {
        $family = new GedcomFamily();
        $family->gedcom_id = $gedcom_id;
        $family->gedcom_key = $f->gedcom_key;
        $family->indi_id_husb = $this->getIndividualId($gedcom_id, $f->husb_key);
        $family->indi_id_wife = $this->getIndividualId($gedcom_id, $f->wife_key);
        $family->save();

        foreach ($f->children_keys as $c)
        {
            $this->createChild($gedcom_id, $c, $family->id);
        }
        foreach ($f->events as $e)
        {
            $this->createEvent($gedcom_id, $e, NULL, $family->id);
        }
        foreach ($f->notes as $n)
        {
            $this->createNote($gedcom_id, $n, NULL, $family->id);
        }
        foreach ($f->sources as $s)
        {
            $this->createSource($gedcom_id, $s, NULL, $family->id);
        }
    }
    
    private function createChild($gedcom_id, $c, $fami_id)
    {
        $child = new GedcomChild();
        $child->gedcom_id = $gedcom_id;
        $child->fami_id = $fami_id;
        $child->indi_id = $this->getIndividualId($gedcom_id, $c->gedcom_key);
        $child->save();
    }

    private function createEvent($gedcom_id, $e, $indi_id = NULL, $fami_id = NULL)
    {
        $notes = array();
        $sources = array();

        $event = new GedcomEvent();
        $event->gedcom_id = $gedcom_id;
        $event->indi_id = $indi_id;
        $event->fami_id = $fami_id;
        foreach ($e as $key => $value)
        {
            switch ($key)
            {
                case 'notes':
                    $notes = $value;
                    break;
                case 'sources':
                    $sources = $value;
                    break;
                default:
                    $event->$key = $value;
                    break;
            }
        }

        $event->save();

        foreach ($notes as $n)
        {
            $this->createNote($gedcom_id, $n, NULL, NULL, $event->id);
        }
        foreach ($sources as $s)
        {
            $this->createSource($gedcom_id, $s, NULL, NULL, $event->id);
        }
    }

    private function createNote($gedcom_id, $n, $indi_id = NULL, $fami_id = NULL, $even_id = NULL)
    {
        $sources = array();

        $note = new GedcomNote();
        $note->gedcom_id = $gedcom_id;
        $note->indi_id = $indi_id;
        $note->fami_id = $fami_id;
        $note->even_id = $even_id;
        foreach ($n as $key => $value)
        {
            switch ($key)
            {
                case 'sources':
                    $sources = $value;
                    break;
                default:
                    $note->$key = $value;
                    break;
            }
        }

        $note->save();

        foreach ($sources as $s)
        {
            $this->createSource($gedcom_id, $s, NULL, NULL, NULL, $note->id);
        }
    }

    private function createSource($gedcom_id, $s, $indi_id = NULL, $fami_id = NULL, $even_id = NULL, $note_id = NULL)
    {
        $source = new GedcomSource();
        $source->gedcom_id = $gedcom_id;
        $source->indi_id = $indi_id;
        $source->fami_id = $fami_id;
        $source->even_id = $even_id;
        $source->note_id = $note_id;
        foreach ($s as $key => $value)
        {
            $source->$key = $value;
        }
        $source->save();
    }

}
