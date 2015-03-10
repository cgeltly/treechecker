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

class ErrorsController extends BaseController
{

    protected $layout = "layouts.main";
    
    public function __construct()
    {
        parent::__construct();
        
        //prevent access to controller methods without login
        $this->beforeFilter('auth');
    }

    /**
     * Show a list of all the GedcomErrors.
     */
    public function getIndex()
    {
        $this->layout->content = View::make('gedcom/errors/index');
    }

    /**
     * Shows the GedcomErrors per Gedcom. 
     * @param int $gedcom_id
     */
    public function getGedcom($gedcom_id)
    {
        $gedcom = Gedcom::findOrFail($gedcom_id);

        
        if ($this->allowedAccess($gedcom->user_id)) 
        {        
        
        // Discern between parse and non-parse errors.
        $errors = GedcomError::where('gedcom_id', $gedcom_id)
                ->where('stage', '!=', 'parsing')
                ->orderBy('indi_id')
                ->orderBy('fami_id')
                ->get();
        $parse_errors = GedcomError::where('gedcom_id', $gedcom_id)
                ->where('stage', '=', 'parsing')
                ->orderBy('indi_id')
                ->orderBy('fami_id')
                ->get();

        $this->layout->content = View::make('gedcom/errors/detail', compact('gedcom', 'errors', 'parse_errors'));

        }
        else 
        {
            return Response::make('Unauthorized', 401);
        }
    }

    /**
     * Show a list of all the GedcomErrors formatted for Datatables.
     * @return Datatables JSON
     */
    public function getData()
    {
        $user = Auth::user();
        
        $errors = GedcomError::leftJoin('gedcoms', 'gedcoms.id', '=', 'errors.gedcom_id')
                ->leftJoin('individuals', 'individuals.id', '=', 'errors.indi_id')
                ->leftJoin('families', 'families.id', '=', 'errors.fami_id')
                ->select(array('gedcoms.file_name AS gedc',
                'individuals.gedcom_key AS indi',
                'families.gedcom_key AS fami',
                'errors.classification', 'errors.severity', 'errors.message',
                'errors.gedcom_id', 'errors.indi_id', 'errors.fami_id'));
                if ($user->role != 'admin')
                {
                    $errors->where('gedcoms.user_id', $user->id);
                }
        

        return Datatables::of($errors)
                        ->edit_column('gedc', '{{ HTML::link("errors/gedcom/" . $gedcom_id, $gedc) }}')
                        ->edit_column('indi', '{{ $indi_id ? HTML::link("individuals/show/" . $indi_id, $indi) : "" }}')
                        ->edit_column('fami', '{{ $fami_id ? HTML::link("families/show/" . $fami_id, $fami) : "" }}')
                        ->remove_column('gedcom_id')
                        ->remove_column('indi_id')
                        ->remove_column('fami_id')
                        ->make();
    }

    /**
     * Count the number of errors in all of users files
     * @param int $id
     * @return int
     */
    public function getCount()
    {
        $user = Auth::user();
        
        $errors = User::leftJoin('gedcoms', 'users.id', '=', 'gedcoms.user_id')
                ->leftJoin('$errors', 'gedcoms.id', '=', '$errors.gedcom_id');
        
                //admin can see all files, other users see their own only
                if ($user->role != 'admin')
                {
                    $errors->where('users.id', $user->id);
                }
                else
                {
                    $errors->where('users.id', 'LIKE', '%');
                }    
        
        return $errors->count();
    }    
    
}
