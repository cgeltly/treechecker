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

class LifespanController extends BaseController
{

    protected $layout = "layouts.main";

    public function __construct()
    {
        parent::__construct();

        //prevent access to controller methods without login
        $this->beforeFilter('auth');
    }
    
    public function getChart($id)
    {
        chdir(Config::get('app.upload_dir'));
        exec('Rscript "' . app_path() . '/scripts/lifespans.R" ' . $id . ' 2>&1');
        $svg = file_get_contents('lifespan' . $id . '.svg');
        $this->layout->content = View::make('gedcom/lifespan/chart', compact('svg'));
    }
}