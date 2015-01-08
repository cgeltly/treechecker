<?php
// Gedcom Place functionality.
//
// webtrees: Web based Family History software
// Copyright (C) 2014 webtrees development team.
//
// This program is free software; you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation; either version 2 of the License, or
// (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA

if (!defined('WT_WEBTREES')) {
	header('HTTP/1.0 403 Forbidden');
	exit;
}

class WT_Place {
	const GEDCOM_SEPARATOR = ', ';
	private $gedcom_place;  // e.g. array("Westminster", "London", "England")
	private $gedcom_id;     // We may have the same place in different trees

	public function __construct($gedcom_place, $gedcom_id) {
		if ($gedcom_place) {
			$this->gedcom_place=explode(self::GEDCOM_SEPARATOR, $gedcom_place);
		} else {
			// Empty => "Top Level"
			$this->gedcom_place=array();
			$this->place_id=0;
		}
		$this->gedcom_id=$gedcom_id;
	}

 	public function getNumericLatitude() {
		return new WT_Place(implode(self::GEDCOM_SEPARATOR, array_slice($this->gedcom_place, 1)), $this->gedcom_id);
	} 
        
	public function getParentPlace() {
		return new WT_Place(implode(self::GEDCOM_SEPARATOR, array_slice($this->gedcom_place, 1)), $this->gedcom_id);
	}

	public function getGedcomName() {
		return implode(self::GEDCOM_SEPARATOR, $this->gedcom_place);
	}

        public function getCoords() {
		return $this->gedcom_place;
	}        

	public function getPlaceName() {
		$place=reset($this->gedcom_place);
		return $place ? '<span dir="auto">'.WT_Filter::escapeHtml($place).'</span>' : WT_I18N::translate('unknown');
	}

	public function isEmpty() {
		return empty($this->gedcom_place);
	}

	public function getFullName() {
		// If a place hierarchy is a single entity
		return '<span dir="auto">' . WT_Filter::escapeHtml(implode(WT_I18N::$list_separator, $this->gedcom_place)) . '</span>';
		// If a place hierarchy is a list of distinct items
		$tmp=array();
		foreach ($this->gedcom_place as $place) {
			$tmp[]='<span dir="auto">' . WT_Filter::escapeHtml($place) . '</span>';
		}
		return implode(WT_I18N::$list_separator, $tmp);
	}


}