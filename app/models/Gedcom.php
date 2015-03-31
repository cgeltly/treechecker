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
        return $this->hasMany('GedcomParentalAge', 'gedcom_id');
    }
    
    /**
     * Returns the GedcomMarriageAges belonging to this Gedcom.
     * @return Illuminate\Database\Eloquent\Collection
     */
    public function marriage_ages()
    {
        return $this->hasMany('GedcomMarriageAge', 'gedcom_id');
    }

    /**
     * Returns the GedcomLifespans belonging to this Gedcom.
     * @return Illuminate\Database\Eloquent\Collection
     */
    public function lifespans()
    {
        return $this->hasMany('GedcomLifespan', 'gedcom_id');
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
                        ->select('i.id', 'i.gedcom_key', 'i.first_name as first_name', 'i.last_name as last_name',
                                $this->sqlAge('age'))
                        ->having('age', '>=', $lifespan)->get();
    }

    public function lifespan_less_than($lifespan = 0)
    {
        return $this->lifespanJoins()
                        ->select('i.id', 'i.gedcom_key', 'i.first_name as first_name', 'i.last_name as last_name',
                                $this->dateDiff('e1', 'age'))
                        ->having('age', '<', $lifespan)->get();
    }

    
    public function allLifespans()
    {
        return $this->lifespanJoins()
                        ->select('i.id as indi_id', 'i.gedcom_id', $this->dateDiff('e1', 'lifespan'),
                                $this->estDate('e1.estimate', 'e2.estimate', 'est_date'))
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
                        ->where('c.gedcom_id', $this->id);                
    }    

    public function parentalAges($parent)
    {
        return $this->parentalAgesJoins($parent)
                        ->select('c.fami_id as fami_id', 'e1.indi_id as par_id', 'e2.indi_id as chil_id', 
                                $this->estDate('e1.estimate', 'e2.estimate', 'est_date'), $this->sqlAge('par_age'))
                        ->get();
    }
      
    public function parentalAgeLargerThan($parent, $age)
    {
        return $this->parentalAgesJoins($parent)
                        ->select('i.id as indi_id', 'i.gedcom_key as gedcom_i_key', 
                                'i.first_name as par_fn', 'i.last_name as par_ln',  $this->sqlAge('age'))
                        ->having('age', '>=', $age)->get();
    }    

    
    public function parentalAgeLessThan($parent, $age)
    {
        return $this->parentalAgesJoins($parent)
                        ->select('i.id as indi_id', 'i.gedcom_key as gedcom_i_key', 
                                'i.first_name as par_fn', 'i.last_name as par_ln',  $this->sqlAge('age'))
                        ->having('age', '<=', $age)->get();
    }     
    

    /*
     * Marriage age
     */
    public function marriageAgesJoins()
    {
        return DB::table('families as f')
                        ->join('events as e2', 'e2.fami_id', '=', 'f.id')
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
                ->select('f.id as fami_id', 'e1.indi_id as indi_id_husb', 
                        'e0.indi_id as indi_id_wife', $this->dateDiff('e1', 'marr_age_husb'),
                        $this->dateDiff('e0', 'marr_age_wife'),
                        $this->estDate('e1.estimate','e2.estimate', 'est_date_age_husb'), 
                        $this->estDate('e0.estimate', 'e2.estimate', 'est_date_age_wife'))
                ->get();
    }
    
    
    
    /*
     * Spousal age gap
     * TODO: rename functions, becuase no actual marriage event involved
     */

    public function marriageAgeDiff()
    {
        return DB::table('families as f')
                        ->join('events as e1', 'e1.indi_id', '=', 'f.indi_id_husb')
                        ->join('events as e2', 'e2.indi_id', '=', 'f.indi_id_wife')
                        ->where('e1.event', 'BIRT')
                        ->where('e2.event', 'BIRT')
                        ->where('f.gedcom_id', $this->id);
    }

    public function marriageAgeDiffLargerThan($age)
    {
        return $this->marriageAgeDiff()
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
     * @param string $as possible alias for this column
     * @param string $birth_alias is birth event query alias
     * @return string
     */
    private function dateDiff($birth_alias, $as = NULL)
    {
        return DB::raw('IF(ISNULL(DATEDIFF(e2.date, ' . $birth_alias. '.date)), YEAR(e2.date)-YEAR(' . $birth_alias. '.date), '
                    . 'TRUNCATE(((DATEDIFF(e2.date, ' . $birth_alias. '.date))/365.25), 0))'
                    . ($as ? ' AS ' . $as : ''));
    }  
    
    /**
     * Check if either of two dates is estimated
     * @param $estimate1 and $estimate2 as SQL alias to events.estimate for each date
     * @param string $as possible alias for this column
     * @return boolean
     */
    private function estDate($estimate1, $estimate2, $as = NULL)
    {
        return DB::raw('IF(' . $estimate1 . '+' . $estimate2 . '= 0, 0, 1)'
                        . ($as ? ' AS ' . $as : ''));
    }  
    
 
    
}
