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
     * Actions before parsing: 
     * - none
     * @param int $gedcom_id
     */
    protected function doBeforeParse($gedcom_id)
    {
        return;
    }

    /**
     * Actions after parsing: 
     * - none
     * @param int $gedcom_id
     */
    protected function doAfterParse($gedcom_id)
    {
        return;
    }

    /**
     * Decode the JSON and start the importing.
     * @param integer $gedcom_id
     * @param string $gedcom
     */
    protected function doImport($gedcom_id, $gedcom)
    {
        // Decode the JSON 
        $json = json_decode($gedcom);
        $g = $json[0];

        // Create GedcomSystems, GedcomIndividuals and GedcomFamilies
        $this->createSystem($gedcom_id, $g->system);
        foreach ($g->individuals as $i)
        {
            $this->createIndividual($gedcom_id, $i);
        }
        foreach ($g->families as $f)
        {
            $this->createFamily($gedcom_id, $f);
        }
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

    /**
     * Creates a GedcomIndividual from the JSON input
     * @param integer $gedcom_id
     * @param object $i
     */
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

    /**
     * Creates a GedcomFamily from the JSON input
     * @param integer $gedcom_id
     * @param object $f
     */
    private function createFamily($gedcom_id, $f)
    {
        $husb_ind = $this->retrieveIndividual($gedcom_id, $f->husb_key);
        $wife_ind = $this->retrieveIndividual($gedcom_id, $f->wife_key);
        
        $family = new GedcomFamily();
        $family->gedcom_id = $gedcom_id;
        $family->gedcom_key = $f->gedcom_key;
        $family->indi_id_husb = $husb_ind ? $husb_ind->id : NULL;
        $family->indi_id_wife = $wife_ind ? $wife_ind->id : NULL;
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

    /**
     * Creates a GedcomChild from the JSON input
     * @param integer $gedcom_id
     * @param object $c
     * @param integer $fami_id
     */
    private function createChild($gedcom_id, $c, $fami_id)
    {
        $indi = $this->retrieveIndividual($gedcom_id, $c->gedcom_key);
        
        $child = new GedcomChild();
        $child->gedcom_id = $gedcom_id;
        $child->fami_id = $fami_id;
        $child->indi_id = $indi ? $indi->id : NULL;
        $child->save();
    }

    /**
     * Creates a GedcomEvent from the JSON input
     * @param integer $gedcom_id
     * @param object $e
     * @param integer $indi_id
     * @param integer $fami_id
     */
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

    /**
     * Creates a GedcomNote from the JSON input
     * @param integer $gedcom_id
     * @param object $n
     * @param integer $indi_id
     * @param integer $fami_id
     * @param integer $even_id
     */
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

    /**
     * Creates a GedcomSource from the JSON input
     * @param integer $gedcom_id
     * @param object $s
     * @param integer $indi_id
     * @param integer $fami_id
     * @param integer $even_id
     * @param integer $note_id
     */
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
