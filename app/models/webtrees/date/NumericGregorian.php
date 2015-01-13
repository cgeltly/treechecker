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
 * 
 * Class description:
 *
 * Webtrees does not deal with dates in the format d(d)?m(m)?(yyyy) or m(m)?d(d)?y(yyy), 
 * e.g. 12-08-1886, although these can be found, e.g. in GEDCOM files exported from 
 * MacFamilyTree software. This is an additional date class to deal with this issue.
 */

class NumericGregorian
{
 
public function __construct($date) {
		
		if (is_array($date)) 
                {
			$this->d = (int)$date[2];
			$this->m = (int)$date[1];
			$this->y = (int)$date[0];

		}                
}
}
