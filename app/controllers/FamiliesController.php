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

class FamiliesController extends BaseController
{

    protected $layout = "layouts.main";

    public function __construct()
    {
        parent::__construct();

        //prevent access to controller methods without login
        $this->beforeFilter('auth');
    }

    /**
     * Show a list of all the GedcomFamilies.
     */
    public function getIndex()
    {
        $source = 'families/data';
        $count = $this->getCount();
        $title = Lang::get('gedcom/families/title.families_search');
        $subtitle = Lang::get('gedcom/families/subtitle.result_multiple_trees');
        $this->layout->content = View::make('gedcom/families/index', compact('source', 'count', 'title', 'subtitle'));
    }

    /**
     * Shows a single GedcomFamily. 
     * @param int $id
     */
    public function getShow($id)
    {
        $family = GedcomFamily::findOrFail($id);

        if ($this->allowedAccess($family->gc->user_id))
        {
            $husband = $family->husband;
            $wife = $family->wife;
            $this->layout->content = View::make('gedcom/families/detail', compact('family', 'husband', 'wife'));
        }
        else
        {
            return Response::make('Unauthorized', 401);
        }
    }

    /**
     * Show a list of all the GedcomFamilies formatted for Datatables.
     * @return Datatables JSON
     */
    public function getData()
    {
        $user = Auth::user();

        $families = GedcomFamily::leftJoin('gedcoms AS g', 'families.gedcom_id', '=', 'g.id')
                ->leftJoin('individuals AS h', 'families.indi_id_husb', '=', 'h.id')
                ->leftJoin('individuals AS w', 'families.indi_id_wife', '=', 'w.id')
                ->select(array('g.file_name',
            'families.gedcom_id AS gc_id', 'families.gedcom_key', 'families.id AS fa_id',
            'families.indi_id_husb AS hu_id', 'families.indi_id_wife AS wi_id',
            'h.gedcom_key AS hgk', DB::raw('CONCAT(h.first_name, " ", h.last_name) AS husb_name'),
            'w.gedcom_key AS wgk', DB::raw('CONCAT(w.first_name, " ", w.last_name) AS wife_name')));

        $families->take(100);

        if ($user->role != 'admin')
        {
            $families->where('g.user_id', $user->id);
        }

        return Datatables::of($families)
                        ->edit_column('file_name', '{{ HTML::link("gedcoms/show/" . $gc_id, $file_name) }}')
                        ->edit_column('gedcom_key', '{{ HTML::link("families/show/" . $fa_id, $gedcom_key) }}')
                        ->edit_column('hgk', '{{ $hu_id ? HTML::link("individuals/show/" . $hu_id, $hgk) : "" }}')
                        ->edit_column('wgk', '{{ $wi_id ? HTML::link("individuals/show/" . $wi_id, $wgk) : "" }}')
                        ->remove_column('fa_id')
                        ->remove_column('gc_id')
                        ->remove_column('hu_id')
                        ->remove_column('wi_id')
                        ->make();
    }

    /**
     * Show a list of all the GedcomEvents for the given GedcomFamily formatted for Datatables.
     * @return Datatables JSON
     */
    public function getEvents($id)
    {
        $user = Auth::user();

        $events = GedcomEvent::leftJoin('gedcoms', 'events.gedcom_id', '=', 'gedcoms.id')
                ->select(array('events.event', 'events.date', 'events.place'))
                ->where('fami_id', $id);

        if ($user->role != 'admin')
        {
            $events->where('gedcoms.user_id', $user->id);
        }

        return Datatables::of($events)->make();
    }

    public function getMarriages($id)
    {
        $gedcom = Gedcom::findOrFail($id);
        $this->layout->content = View::make('gedcom/families/marriages', compact('gedcom'));
    }

    public function getMarriageages($id)
    {
        $gedcom = Gedcom::findOrFail($id);
        return Response::json($gedcom->marriageAges());
    }

    /**
     * Count the number of families in all of users files
     * @param int $id
     * @return int
     */
    public function getCount()
    {
        $user = Auth::user();

        $families = User::leftJoin('gedcoms', 'users.id', '=', 'gedcoms.user_id')
                ->leftJoin('families', 'gedcoms.id', '=', 'families.gedcom_id');

        //admin can see all files, other users see their own only
        if ($user->role != 'admin')
        {
            $families->where('users.id', $user->id);
        }
        else
        {
            $families->where('users.id', 'LIKE', '%');
        }

        return $families->count();
    }

    /**
     * Serializes a GedcomFamily to JSON
     * @param integer $id
     * @return JSON
     */
    public function getJson($id)
    {
        $f = GedcomFamily::where('id', $id)
                ->with('events')
                ->with('events.notes')
                ->with('events.notes.sources')
                ->with('notes')
                ->with('notes.sources')
                ->with('sources')
                ->get();
        return Response::json($f);
    }

}
