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

class EventsController extends BaseController
{

    protected $layout = "layouts.main";

    public function __construct()
    {
        parent::__construct();
        
        //prevent access to controller methods without login
        $this->beforeFilter('auth');
    }    
    
    /**
     * Show a list of all the GedcomEvents.
     */
    public function getIndex()
    {
        $this->layout->content = View::make('gedcom/events/index');
    }

    /**
     * Show a list of all the GedcomEvents formatted for Datatables.
     * @return Datatables JSON
     */
    public function getData()
    {
        $events = GedcomEvent::leftJoin('individuals', 'individuals.id', '=', 'events.indi_id')
                ->leftJoin('families', 'families.id', '=', 'events.fami_id')
                ->select(array('individuals.gedcom_key AS indi', 
                    'families.gedcom_key AS fami', 
                    'events.event', 'events.date', 'events.place',
                    'events.indi_id', 'events.fami_id'));

        return Datatables::of($events)
                        ->edit_column('indi', '{{ $indi_id ? HTML::link("individuals/show/" . $indi_id, $indi) : "" }}')
                        ->edit_column('fami', '{{ $fami_id ? HTML::link("families/show/" . $fami_id, $fami) : "" }}')
                        ->remove_column('indi_id')
                        ->remove_column('fami_id')
                        ->make();
    }

}
