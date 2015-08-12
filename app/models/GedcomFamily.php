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
     * Hidden fields in the JSON export.
     * @var array
     */
    protected $hidden = array('id', 'gedcom_id', 'gedcom',
        'indi_id_husb', 'indi_id_wife',
        'husband', 'wife', 'children',
        'created_at', 'updated_at');

    /**
     * Fields to be appended in the JSON export.
     * @var array
     */
    protected $appends = array('husb_key', 'wife_key', 'children_keys');

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
     * Returns the Gedcom key of the husband of this GedcomFamily.
     * @return string
     */
    public function getHusbKeyAttribute()
    {
        return $this->husband ? $this->husband->gedcom_key : "";
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
     * Returns the Gedcom key of the wife of this GedcomFamily.
     * @return string
     */
    public function getWifeKeyAttribute()
    {
        return $this->wife ? $this->wife->gedcom_key : "";
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
     * Returns the GedcomEvents of a certain type (e.g. BIRT, DEAT).
     * @param string $type
     * @return Illuminate\Database\Eloquent\Collection
     */
    public function eventsByType($type)
    {
        return $this->events()->whereEvent($type);
    }

    /**
     * Returns the GedcomErrors belonging to this GedcomFamily.
     * @return Illuminate\Database\Eloquent\Collection
     */
    public function errors()
    {
        return $this->hasMany('GedcomError', 'indi_id');
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
     * Returns the GedcomNotes belonging to this GedcomFamily via a GedcomEvent.
     * @return Illuminate\Database\Eloquent\Collection
     */
    public function eventNotes()
    {
        return $this->hasManyThrough('GedcomNote', 'GedcomEvent', 'fami_id', 'even_id');
    }

    /**
     * Returns the GedcomSources belonging to this GedcomFamily.
     * @return Illuminate\Database\Eloquent\Collection
     */
    public function sources()
    {
        return $this->hasMany('GedcomSource', 'fami_id');
    }

    /**
     * Returns the GedcomSources belonging to this GedcomFamily via a GedcomEvent.
     * @return Illuminate\Database\Eloquent\Collection
     */
    public function eventSources()
    {
        return $this->hasManyThrough('GedcomSource', 'GedcomEvent', 'fami_id', 'even_id');
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
     * Returns the Gedcom key of the children of this GedcomFamily.
     * TODO: maybe output this in plain list format?
     * @return string
     */
    public function getChildrenKeysAttribute()
    {
        $result = array();
        foreach ($this->children->lists('gedcom_key') as $child)
        {
            array_push($result, array('gedcom_key' => $child));
        }
        return $result;
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
