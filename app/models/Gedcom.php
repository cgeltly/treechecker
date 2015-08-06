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

class Gedcom extends Eloquent
{

    /**
     * The database table used by the model.
     * @var string
     */
    protected $table = 'gedcoms';

    /**
     * The validation rules for creating Gedcoms.
     * @var array
     */
    public static $rules = array(
        'tree_name' => 'required',
    );

    /**
     * The validation rules for updating Gedcoms.
     * @var array
     */
    public static $update_rules = array(
        'tree_name' => 'required'
    );

    /**
     * Hidden fields in the JSON export.
     * @var array
     */
    protected $hidden = array('id', 'user_id',
        'path', 'parsed', 'error_checked',
        'created_at', 'updated_at');

    /**
     * Returns the User to which this Gedcom belongs.
     * @return User
     */
    public function user()
    {
        return $this->belongsTo('User');
    }

    /**
     * Returns the GedcomIndividuals belonging to this Gedcom.
     * @return Illuminate\Database\Eloquent\Collection
     */
    public function individuals()
    {
        return $this->hasMany('GedcomIndividual', 'gedcom_id');
    }

    /**
     * Returns the GedcomFamilies belonging to this Gedcom.
     * @return Illuminate\Database\Eloquent\Collection
     */
    public function families()
    {
        return $this->hasMany('GedcomFamily', 'gedcom_id');
    }

    /**
     * Returns the GedcomErrors belonging to this Gedcom.
     * @return Illuminate\Database\Eloquent\Collection
     */
    public function errors()
    {
        return $this->hasMany('GedcomError', 'gedcom_id');
    }

    /**
     * Returns the GedcomParentalAges belonging to this Gedcom.
     * @return Illuminate\Database\Eloquent\Collection
     */
    public function parental_ages()
    {
        return $this->hasMany('GedcomStatsParents', 'gedcom_id');
    }

    /**
     * Returns the GedcomMarriageAges belonging to this Gedcom.
     * @return Illuminate\Database\Eloquent\Collection
     */
    public function marriage_ages()
    {
        return $this->hasMany('GedcomStatsMarriages', 'gedcom_id');
    }

    /**
     * Returns the GedcomLifespans belonging to this Gedcom.
     * @return Illuminate\Database\Eloquent\Collection
     */
    public function lifespans()
    {
        return $this->hasMany('GedcomStatsLifespans', 'gedcom_id');
    }

    /**
     * Returns the GedcomGeocodes belonging to this Gedcom.
     * @return Illuminate\Database\Eloquent\Collection
     */
    public function geocodes()
    {
        return $this->hasMany('GedcomGeocode', 'gedcom_id');
    }

    /**
     * Returns the GedcomNotes belonging to this Gedcom.
     * @return Illuminate\Database\Eloquent\Collection
     */
    public function notes()
    {
        return $this->hasMany('GedcomNote', 'gedcom_id');
    }

    /**
     * Returns the GedcomSources belonging to this Gedcom.
     * @return Illuminate\Database\Eloquent\Collection
     */
    public function sources()
    {
        return $this->hasMany('GedcomSource', 'gedcom_id');
    }

    /**
     * Returns the GedcomSystem belonging to this Gedcom.
     * @return Illuminate\Database\Eloquent\Collection
     */
    public function system()
    {
        return $this->hasOne('GedcomSystem', 'gedcom_id');
    }

    /**
     * Returns the GedcomChildren belonging to this Gedcom through the GedcomFamilies.
     * @return Illuminate\Database\Eloquent\Collection
     */
    public function childrenThroughFamily()
    {
        return $this->hasManyThrough('GedcomChild', 'GedcomFamily', 'gedcom_id', 'fami_id');
    }

    /**
     * Returns the GedcomChildren belonging to this Gedcom through the GedcomIndividuals.
     * @return Illuminate\Database\Eloquent\Collection
     */
    public function childrenThroughIndividual()
    {
        return $this->hasManyThrough('GedcomChild', 'GedcomIndividual', 'gedcom_id', 'indi_id');
    }

    /**
     * Returns GedcomIndividuals that are part of multiple GedcomFamilies for this Gedcom.
     * @return type
     */
    public function individualNrFamilies()
    {
        return $this->childrenThroughIndividual()
                        ->select('indi_id', DB::raw('count(*) as count'))
                        ->groupBy('indi_id')
                        ->having('count', '>', '1');
    }

    /*
     * Events
     */

    /**
     * Returns the GedcomEvents belonging to this Gedcom through the GedcomIndividuals.
     * @return Illuminate\Database\Eloquent\Collection
     */
    public function individualEvents()
    {
        return $this->hasManyThrough('GedcomEvent', 'GedcomIndividual', 'gedcom_id', 'indi_id');
    }

    /**
     * Returns the GedcomEvents belonging to this Gedcom through the GedcomFamilies.
     * @return Illuminate\Database\Eloquent\Collection
     */
    public function familyEvents()
    {
        return $this->hasManyThrough('GedcomEvent', 'GedcomFamily', 'gedcom_id', 'fami_id');
    }

    public function individualEventsBeforeBirth()
    {
        //compare event dates where the second event is not missing month or day
        $compare_full_dates = DB::table('individuals as i')
                ->select('i.id', 'i.gedcom_key', 'i.first_name', 'i.last_name', 'e2.event')
                ->join('events as e1', 'e1.indi_id', '=', 'i.id')
                ->join('events as e2', 'e2.indi_id', '=', 'i.id')
                ->where('e1.event', 'BIRT')
                ->where('e2.event', '!=', 'BIRT')
                ->WhereRaw('((e2.date < e1.date) AND (MONTH(e2.date) != 00) '
                        . 'AND (DAY(e2.date) != 00))')
                ->where('i.gedcom_id', $this->id);
        //compare event dates only on years
        $compare_years = DB::table('individuals as i')
                ->select('i.id', 'i.gedcom_key', 'i.first_name', 'i.last_name', 'e2.event')
                ->join('events as e1', 'e1.indi_id', '=', 'i.id')
                ->join('events as e2', 'e2.indi_id', '=', 'i.id')
                ->where('e1.event', 'BIRT')
                ->where('e2.event', '!=', 'BIRT')
                ->whereRaw('(YEAR(e2.date) < YEAR(e1.date))')
                ->where('i.gedcom_id', $this->id);
        //union of queries
        return $compare_full_dates->union($compare_years)->get();
    }

    /*
     * Lifespan
     */

    public function lifespanJoins()
    {
        return DB::table('individuals as i')
                        ->join('events as e1', 'e1.indi_id', '=', 'i.id')
                        ->join('events as e2', 'e2.indi_id', '=', 'i.id')
                        ->where('e1.event', 'BIRT')
                        ->where('e2.event', 'DEAT')
                        ->whereNotNull('e1.date')
                        ->whereNotNull('e2.date')
                        ->where('i.gedcom_id', $this->id);
    }

    public function avg_lifespan()
    {
        return $this->lifespanJoins()->avg($this->sqlAge());
    }

    public function max_lifespan()
    {
        return $this->lifespanJoins()->max($this->sqlAge());
    }

    public function min_lifespan()
    {
        return $this->lifespanJoins()->min($this->sqlAge());
    }

    public function lifespan_larger_than($lifespan)
    {
        return $this->lifespanJoins()
                        ->select('i.id', 'i.gedcom_key', 'i.first_name as first_name', 'i.last_name as last_name', $this->sqlAge('age'))
                        ->having('age', '>=', $lifespan)->get();
    }

    public function lifespan_less_than($lifespan = 0)
    {
        return $this->lifespanJoins()
                        ->select('i.id', 'i.gedcom_key', 'i.first_name as first_name', 'i.last_name as last_name', $this->dateDiff('e1', 'age'))
                        ->having('age', '<', $lifespan)->get();
    }

    public function allLifespans()
    {
        return $this->lifespanJoins()
                        ->select('i.id as indi_id', 'e1.id as birth_event_id', 'e2.id as death_event_id', $this->dateDiff('e1', 'lifespan'), $this->estDate('e1.est_date', 'e2.est_date', 'est_date'))
                        ->get();
    }

    /*
     * Parental age
     */

    public function parentalAgesJoins($parent)
    {
        //parent id is e1.indi_id or i.id, child id is e2.indi_id
        return DB::table('children as c')
                        ->join('families as f', 'c.fami_id', '=', 'f.id')
                        ->join('events as e1', 'f.indi_id_' . $parent, '=', 'e1.indi_id')
                        ->join('events as e2', 'c.indi_id', '=', 'e2.indi_id')
                        ->join('individuals as i', 'e1.indi_id', '=', 'i.id')
                        ->where('e1.event', 'BIRT')
                        ->where('e2.event', 'BIRT')
                        ->whereNotNull('e1.date')
                        ->whereNotNull('e2.date')
                        ->where('c.gedcom_id', $this->id);
    }

    public function parentalAges($parent)
    {
        return $this->parentalAgesJoins($parent)
                        ->select('c.fami_id as fami_id', 'i.id as pare_id', 'e2.indi_id as chil_id', 'i.sex as pare_sex', 'e1.date as pare_birth', 'e2.date as chil_birth', 'e1.id as par_birth_event_id', 'e2.id as chil_birth_event_id', $this->sqlAge('parental_age'), $this->estDate('e1.est_date', 'e2.est_date', 'est_date'))
                        ->get();
    }

    public function parentalAgeLargerThan($parent, $age)
    {
        return $this->parentalAgesJoins($parent)
                        ->select('i.id as indi_id', 'i.gedcom_key as gedcom_i_key', 'i.first_name as par_fn', 'i.last_name as par_ln', $this->sqlAge('age'))
                        ->having('age', '>=', $age)->get();
    }

    public function parentalAgeLessThan($parent, $age)
    {
        return $this->parentalAgesJoins($parent)
                        ->select('i.id as indi_id', 'i.gedcom_key as gedcom_i_key', 'i.first_name as par_fn', 'i.last_name as par_ln', $this->sqlAge('age'))
                        ->having('age', '<=', $age)->get();
    }

    /*
     * Marriage age
     */

    public function marriageAgesJoins()
    {
        return DB::table('families as f')
                        ->join(DB::raw('(SELECT id, fami_id, date, est_date, event, MIN(YEAR(date)) MinDate
                                FROM events WHERE event = "MARR" AND gedcom_id = ' . $this->id . ' ' .
                                        'GROUP BY event, fami_id) AS e2'), 'f.id', '=', 'e2.fami_id')
                        ->join('events as e1', 'f.indi_id_husb', '=', 'e1.indi_id')
                        ->join('events as e0', 'f.indi_id_wife', '=', 'e0.indi_id')
                        ->where('e2.event', 'MARR')
                        ->where('e1.event', 'BIRT')
                        ->where('e0.event', 'BIRT')
                        ->whereRaw('((e1.date IS NOT NULL OR e0.date IS NOT NULL) AND e2.date IS NOT NULL) ')
                        ->where('f.gedcom_id', $this->id);
    }

    public function marriageAges()
    {
        return $this->marriageAgesJoins()
                        ->select('f.id as fami_id', 'e1.indi_id as indi_id_husb', 'e0.indi_id as indi_id_wife', 'e2.id as marr_event_id', $this->dateDiff('e1', 'marr_age_husb'), $this->dateDiff('e0', 'marr_age_wife'), $this->estDate('e1.est_date', 'e2.est_date', 'est_date_age_husb'), $this->estDate('e0.est_date', 'e2.est_date', 'est_date_age_wife'))
                        ->get();
    }

    /*
     * Spousal age gap
     */

    public function spousalAgeGap()
    {
        return DB::table('families as f')
                        ->join('events as e1', 'e1.indi_id', '=', 'f.indi_id_husb')
                        ->join('events as e2', 'e2.indi_id', '=', 'f.indi_id_wife')
                        ->where('e1.event', 'BIRT')
                        ->where('e2.event', 'BIRT')
                        ->where('f.gedcom_id', $this->id);
    }

    public function spousalAgeGapLargerThan($age)
    {
        return $this->spousalAgeGap()
                        ->select('f.id', 'f.gedcom_key', $this->sqlAge('age'))
                        ->havingRaw('ABS(age) >= ?', array($age))
                        ->get();
    }

    /*
     * Marriage ages
     */

    public function marriageAge()
    {
        return DB::table('individuals as i')
                        ->join('families as f', function($join)
                        {
                            $join->on('f.indi_id_husb', '=', 'i.id')->orOn('f.indi_id_wife', '=', 'i.id');
                        })
                        ->join('events as e1', 'e1.indi_id', '=', 'i.id')
                        ->join('events as e2', 'e2.fami_id', '=', 'f.id')
                        ->where('e1.event', 'BIRT')
                        ->where('e2.event', 'MARR')
                        ->whereNotNull('e1.date')
                        ->whereNotNull('e2.date')
                        ->where('i.gedcom_id', $this->id);
    }

    public function avgMarriageAge()
    {
        return $this->marriageAge()->avg($this->sqlAge());
    }

    public function maxMarriageAge()
    {
        return $this->marriageAge()->max($this->sqlAge());
    }

    public function minMarriageAge()
    {
        return $this->marriageAge()->min($this->sqlAge());
    }

    /* public function marriageAges()
      {
      return $this->marriageAge()
      ->select('i.id as indi_id', 'i.sex as indi_sex', 'e1.date as indi_birth', 'f.id as fami_id', 'e2.date as fami_marriage', $this->dateDiff('e1', 'marriage_age'), $this->estDate('e1.estimate', 'e2.estimate', 'estimated'))
      ->get();
      } */

    /*
     * Helpers
     */

    /**
     * Returns the raw SQL for calculating the age in years, given two events.
     * Do note we don't use TIMESTAMPDIFF, as this doesn't work for incomplete days.
     * @param string $as possible alias for this column
     * @return string
     */
    private function sqlAge($as = NULL)
    {
        return DB::raw('YEAR(e2.date) - YEAR(e1.date) - (RIGHT(e1.date, 5) > RIGHT(e2.date, 5))'
                        . ($as ? ' AS ' . $as : ''));
    }

    /**
     * Use MySQL DATEDIFF to get difference between dates if both are complete, 
     * otherwise do straight subtraction of one year from the other 
     * @param string $birth_alias is birth event query alias
     * @param string $as possible alias for this column
     * @return string
     */
    private function dateDiff($birth_alias, $as = NULL)
    {
        return DB::raw('IF(ISNULL(DATEDIFF(e2.date, ' . $birth_alias . '.date)), YEAR(e2.date)-YEAR(' . $birth_alias . '.date), '
                        . 'TRUNCATE(((DATEDIFF(e2.date, ' . $birth_alias . '.date))/365.25), 0))'
                        . ($as ? ' AS ' . $as : ''));
    }

    /**
     * Check if either of two dates is estimated
     * @param $est_date1 and $est_date2 as SQL alias to events.est_date for each date
     * @param string $as possible alias for this column
     * @return boolean
     */
    private function estDate($est_date1, $est_date2, $as = NULL)
    {
        return DB::raw('IF(' . $est_date1 . '+' . $est_date2 . '= 0, 0, 1)'
                        . ($as ? ' AS ' . $as : ''));
    }

}
