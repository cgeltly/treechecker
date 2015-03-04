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
* Returns the GedcomGeocodes belonging to this Gedcom.
* @return Illuminate\Database\Eloquent\Collection
*/
public function geocodes()
{
return $this->hasMany('GedcomGeocode', 'gedcom_id');
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
->select('i.id', 'i.gedcom_key', 'e2.event')
->join('events as e1', 'e1.indi_id', '=', 'i.id')
->join('events as e2', 'e2.indi_id', '=', 'i.id')
->where('e1.event', 'BIRT')
->where('e2.event', '!=', 'BIRT')
->WhereRaw('((e2.date < e1.date) AND (MONTH(e2.date) != 00) '
. 'AND (DAY(e2.date) != 00))')
->where('i.gedcom_id', $this->id);
//compare event dates only on years
$compare_years = DB::table('individuals as i')
->select('i.id', 'i.gedcom_key', 'e2.event')
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
public function lifespan()
{
return DB::table('individuals as i')
->join('events as e1', 'e1.indi_id', '=', 'i.id')
->join('events as e2', 'e2.indi_id', '=', 'i.id')
->where('e1.event', 'BIRT')
->where('e2.event', 'DEAT')
->where('i.gedcom_id', $this->id);
}
public function avg_lifespan()
{
return $this->lifespan()->avg($this->sqlAge());
}
public function max_lifespan()
{
return $this->lifespan()->max($this->sqlAge());
}
public function min_lifespan()
{
return $this->lifespan()->min($this->sqlAge());
}
public function lifespan_larger_than($lifespan)
{
return $this->lifespan()
->select('i.id', 'i.gedcom_key', $this->sqlAge('age'))
->having('age', '>=', $lifespan)->get();
}
public function lifespan_less_than($lifespan = 0)
{
return $this->lifespan()
->select('i.id', 'i.gedcom_key', $this->sqlAge('age'))
->having('age', '<', $lifespan)->get();
}
/*
* Parental age
*/
public function parentalAge($gender)
{
return DB::table('individuals as i')
->join('families as f', 'f.indi_id_' . $gender, '=', 'i.id')
->join('children as c', 'c.fami_id', '=', 'f.id')
->join('individuals as ci', 'c.indi_id', '=', 'ci.id')
->join('events as e1', 'e1.indi_id', '=', 'i.id')
->join('events as e2', 'e2.indi_id', '=', 'c.indi_id')
->where('e1.event', 'BIRT')
->where('e2.event', 'BIRT')
->where('i.gedcom_id', $this->id);
}
public function parentalAgeLargerThan($gender, $age)
{
return $this->parentalAge($gender)
->select('i.id', 'i.gedcom_key', $this->sqlAge('age'))
->having('age', '>=', $age)->get();
}
public function parentalAgeLessThan($gender, $age)
{
return $this->parentalAge($gender)
->select('i.id', 'i.gedcom_key', $this->sqlAge('age'))
->having('age', '<=', $age)->get();
}
public function bornBeforeParent($gender)
{
return $this->parentalAge($gender)
->select('i.id AS parent_id', 'i.gedcom_key AS parent_key', 'ci.id AS child_id', 'ci.gedcom_key AS child_key', $this->sqlAge('age'))
->whereRaw('e2.date < e1.date')->get();
}
/*
* Marriage age difference
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
* Helpers
*/
/**
* Returns the raw SQL for calculating the age in years, given two events.
* Do note we don't use TIMESTAMPDIFF, as this doesn't work for incomplete days.
* @param string $as A possible alias for this column
* @return string
*/
private function sqlAge($as = NULL)
{
return DB::raw('YEAR(e2.date) - YEAR(e1.date) - (RIGHT(e1.date, 5) > RIGHT(e2.date, 5))'
. ($as ? ' AS ' . $as : ''));
}
}