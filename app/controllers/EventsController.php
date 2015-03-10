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
        $source = 'events/data';
        $count = $this->getCount();
        $title = Lang::get('gedcom/events/title.events_search');
        $subtitle = Lang::get('gedcom/events/subtitle.result_multiple_trees');        
        $this->layout->content = View::make('gedcom/events/index', compact('source', 'count', 'title', 'subtitle'));        
        
        
    }

    /**
     * Show a list of all the GedcomEvents formatted for Datatables.
     * @return Datatables JSON
     */
    public function getData()
    {
        $user = Auth::user();
        
        $events = GedcomEvent::leftJoin('gedcoms AS g', 'events.gedcom_id', '=', 'g.id')
                ->leftJoin('individuals', 'individuals.id', '=', 'events.indi_id')
                ->leftJoin('families', 'families.id', '=', 'events.fami_id')
                ->select(array('individuals.gedcom_key AS indi', 
                'families.gedcom_key AS fami', 
                'events.event', 'events.date', 'events.place',
                'events.indi_id', 'events.fami_id'));
        
                $events->take(100);        
        
                if ($user->role != 'admin')
                {
                    $events->where('g.user_id', $user->id);
                }

        return Datatables::of($events)
                        ->edit_column('indi', '{{ $indi_id ? HTML::link("individuals/show/" . $indi_id, $indi) : "" }}')
                        ->edit_column('fami', '{{ $fami_id ? HTML::link("families/show/" . $fami_id, $fami) : "" }}')
                        ->remove_column('indi_id')
                        ->remove_column('fami_id')
                        ->make();
    }
    
    /**
     * Count the number of events in all of users files
     * @param int $id
     * @return int
     */
    public function getCount()
    {
        $user = Auth::user();
        
        $events = User::leftJoin('gedcoms', 'users.id', '=', 'gedcoms.user_id')
                ->leftJoin('events', 'gedcoms.id', '=', 'events.gedcom_id');
        
                //admin can see all files, other users see their own only
                if ($user->role != 'admin')
                {
                    $events->where('users.id', $user->id);
                }
                else
                {
                    $events->where('users.id', 'LIKE', '%');
                }    
        
        return $events->count();
    }    
    

}
