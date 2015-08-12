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

abstract class ParseController extends BaseController
{

    public function __construct()
    {
        parent::__construct();

        // Prevent access to controller methods without login
        $this->beforeFilter('auth');
    }

    /**
     * Parses a Gedcom and creates parse errors when necessary.
     * @param int $gedcom_id
     * @return void (if finished)
     */
    abstract protected function getParse($gedcom_id);

    /**
     * Looks up a GedcomIndividual by key. 
     * Creates a GedcomError if not found. 
     * Returns the id (or NULL if not found) of the GedcomIndividual
     * @param int $gedcom_id
     * @param string $gedcom_key
     * @return int
     */
    protected function getIndividualId($gedcom_id, $gedcom_key)
    {
        if (!$gedcom_key)
        {
            return NULL;
        }

        $ind = GedcomIndividual::GedcomKey($gedcom_id, $gedcom_key)->first();

        if (!$ind)
        {
            $error = new GedcomError();
            $error->gedcom_id = $gedcom_id;
            $error->stage = 'parsing';
            $error->type_broad = 'missing';
            $error->type_specific = 'individual missing';
            $error->eval_broad = 'error';
            $error->eval_specific = '';
            $error->message = sprintf('No individual found for %s', $gedcom_key);
            $error->save();
        }

        return $ind ? $ind->id : NULL;
    }

}
