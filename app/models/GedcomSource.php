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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

class GedcomSource extends Eloquent
{

    /**
     * The database table used by the model.
     * @var string
     */
    protected $table = 'sources';

    /**
     * Hidden fields in the JSON export.
     * @var array
     */
    protected $hidden = array('id', 'gedcom_id', 'gedcom',
        'indi_id', 'fami_id', 'even_id', 'note_id',
        'created_at', 'updated_at');

    /**
     * Returns the Gedcom to which this GedcomSource belongs.
     * @return Gedcom
     */
    public function gc()
    {
        return $this->belongsTo('Gedcom', 'gedcom_id');
    }

}
