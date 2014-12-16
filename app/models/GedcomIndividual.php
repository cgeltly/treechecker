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
class GedcomIndividual extends Eloquent
{
/**
* The database table used by the model.
* @var string
*/
protected $table = 'individuals';
/**
* Returns the Gedcom to which this GedcomFamily belongs.
* @return Gedcom
*/
public function gc()
{
return $this->belongsTo('Gedcom', 'gedcom_id');
}
/**
* Returns the GedcomEvents belonging to this GedcomIndividual.
* @return Illuminate\Database\Eloquent\Collection
*/
public function events()
{
return $this->hasMany('GedcomEvent', 'indi_id');
}
/**
* Returns the birth event for this GedcomIndividual.
* @return GedcomEvent
*/
public function birth()
{
return $this->events()->whereEvent('BIRT')->first();
}
/**
* Returns the death event for this GedcomIndividual.
* @return GedcomEvent
*/
public function death()
{
return $this->events()->whereEvent('DEAT')->first();
}
/**
* Returns the adoption event for this GedcomIndividual.
* @return GedcomEvent
*/
public function isAdopted()
{
return $this->events()->whereEvent('ADOP')->first();
}
/**
* Calculates the age of a GedcomIndividual, based on BIRT and DEAT GedcomEvents.
* @return int
*/
public function age()
{
return DB::table('events as e1')
->join('events as e2', 'e1.indi_id', '=', 'e2.indi_id')
->where('e1.event', 'BIRT')
->where('e2.event', 'DEAT')
->where('e1.indi_id', $this->id)
->select(DB::raw('YEAR(e2.date) - YEAR(e1.date) - (RIGHT(e1.date, 5) > RIGHT(e2.date, 5)) AS age'))
->pluck('age');
}
/**
* Finds all individuals for the given Gedcom ID and key.
* @param Illuminate\Database\Eloquent\Builder $query
* @param int $gedcom_id
* @param string $gedcom_key
* @return Illuminate\Database\Eloquent\Builder
*/
public function scopeGedcomKey($query, $gedcom_id, $gedcom_key)
{
return $query->where('gedcom_id', $gedcom_id)->where('gedcom_key', $gedcom_key);
}
/**
* Finds all GedcomIndividuals of a certain sex.
* @param Illuminate\Database\Eloquent\Builder $query
* @param string $sex
* @return Illuminate\Database\Eloquent\Builder
*/
public function scopeSex($query, $sex)
{
return $query->whereSex($sex);
}
/**
* Returns the GedcomFamilies this GedcomIndividual is a child of.
* @return Illuminate\Database\Eloquent\Collection
*/
public function families()
{
return $this->belongsToMany('GedcomFamily', 'children', 'indi_id', 'fami_id');
}
/**
* Returns the father of the current GedcomIndividual.
* TODO: what if we have multiple fathers?
* @return GedcomIndividual
*/
public function father()
{
$family = $this->families()->first();
return $family ? $family->husband() : NULL;
}
/**
* Returns the mother of the current GedcomIndividual.
* TODO: what if we have multiple mothers?
* @return GedcomIndividual
*/
public function mother()
{
$family = $this->families()->first();
return $family ? $family->wife() : NULL;
}
/**
* Returns the GedcomFamilies this GedcomIndividual is a husband of.
* @return Illuminate\Database\Eloquent\Collection
*/
public function familiesAsHusband()
{
return $this->hasMany('GedcomFamily', 'indi_id_husb');
}
/**
* Returns the GedcomFamilies this GedcomIndividual is a wife of.
* @return Illuminate\Database\Eloquent\Collection
*/
public function familiesAsWife()
{
return $this->hasMany('GedcomFamily', 'indi_id_wife');
}
}