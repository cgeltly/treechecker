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

class GedcomFamily extends Eloquent
{

    /**
     * The database table used by the model.
     * @var string
     */
    protected $table = 'families';

    /**
     * Returns the Gedcom to which this GedcomFamily belongs.
     * @return Gedcom
     */
    public function gc()
    {
        return $this->belongsTo('Gedcom', 'gedcom_id');
    }

    /**
     * Returns the husband of this GedcomFamily.
     * @return GedcomIndividual
     */
    public function husband()
    {
        return $this->belongsTo('GedcomIndividual', 'indi_id_husb');
    }

    /**
     * Returns the wife of this GedcomFamily.
     * @return GedcomIndividual
     */
    public function wife()
    {
        return $this->belongsTo('GedcomIndividual', 'indi_id_wife');
    }
    
    /**
     * Returns the spouse of the given GedcomIndividual in this GedcomFamily.
     * @return GedcomIndividual
     */
    public function spouse($individual)
    {
        if ($individual->id === $this->indi_id_husb)
        {
            return $this->wife;
        }
        else
        {
            return $this->husband;
        }
    }

    /**
     * Returns the GedcomEvents belonging to this GedcomFamily.
     * @return Illuminate\Database\Eloquent\Collection
     */
    public function events()
    {
        return $this->hasMany('GedcomEvent', 'fami_id');
    }

    /**
     * Returns the GedcomNotes belonging to this GedcomFamily.
     * @return Illuminate\Database\Eloquent\Collection
     */
    public function notes()
    {
        return $this->hasMany('GedcomNote', 'fami_id');
    }

    /**
     * Returns the children of this GedcomFamily.
     * @return Illuminate\Database\Eloquent\Collection
     */
    public function children()
    {
        return $this->belongsToMany('GedcomIndividual', 'children', 'fami_id', 'indi_id');
    }

    /**
     * Finds all families for the given Gedcom ID and key.
     * @param Illuminate\Database\Eloquent\Builder $query
     * @param int $gedcom_id
     * @param string $gedcom_key
     * @return Illuminate\Database\Eloquent\Builder
     */
    public function scopeGedcomKey($query, $gedcom_id, $gedcom_key)
    {
        return $query->where('gedcom_id', $gedcom_id)->where('gedcom_key', $gedcom_key);
    }
    
}
