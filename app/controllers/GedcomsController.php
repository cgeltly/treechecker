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

class GedcomsController extends BaseController
{

    protected $layout = "layouts.main";

    public function __construct()
    {
        parent::__construct();

        //prevent access to controller methods without login
        $this->beforeFilter('auth');
    }

    /**
     * Show a list of all the gedcoms.
     */
    public function getIndex()
    {
        Session::forget('progress');
        $this->layout->content = View::make('gedcom/gedcoms/index');
    }

    /**
     * Show a list of all the unparsed gedcoms.
     */
    public function getUnparsed()
    {
        Session::forget('progress');
        $this->layout->content = View::make('gedcom/gedcoms/unparsed');
    }

    /*
     * Show a list of all unparsed gedcoms.
     */

    public function getUnchecked()
    {
        Session::forget('progress');
        $this->layout->content = View::make('gedcom/gedcoms/unchecked');
    }

    /**
     * Shows a single GEDCOM file, with some statistics. 
     * @param int $id the Gedcom ID
     */
    public function getShow($id)
    {
        $gedcom = Gedcom::findOrFail($id);

        if ($this->allowedAccess($gedcom->user_id))
        {
            if (!$gedcom->parsed)
            {
                // TODO: this is a bit too much of course
                App::abort(403, 'GEDCOM not parsed yet');
            }

            $individuals = $gedcom->individuals();
            $all_ind = $individuals->count();
            $males = $individuals->sex('m')->count();
            $females = $gedcom->individuals()->sex('f')->count();

            $indi_events = $gedcom->individualEvents()->count();
            $fami_events = $gedcom->familyEvents()->count();
            $births = $gedcom->individualEvents()->whereEvent('BIRT');
            $deaths = $gedcom->individualEvents()->whereEvent('DEAT');

            $fam_count = $gedcom->families()->count();

            $max_fam_size = $gedcom->childrenThroughFamily()
                    ->groupBy('fami_id')
                    ->get(array('fami_id', DB::raw('count(*) as count')))
                    ->max('count');

            $sum_fam_size = $gedcom->childrenThroughFamily()
                    ->groupBy('fami_id')
                    ->get(array('fami_id', DB::raw('count(*) as count')))
                    ->sum('count');

            //this gives number of families with children, whereas fam_count gives all families
            $num_fams_with_children = $gedcom->childrenThroughFamily()
                    ->groupBy('fami_id')
                    ->get(array('fami_id', DB::raw('count(*)')))
                    ->count();

            $avg_fam_size = $this->percentage($sum_fam_size, $num_fams_with_children, 1);

            $statistics = array(
                'all_ind' => $all_ind,
                'males' => sprintf('%d (%.2f%%)', $males, $this->percentage($males, $all_ind)),
                'females' => sprintf('%d (%.2f%%)', $females, $this->percentage($females, $all_ind)),
                'unknowns' => $gedcom->individuals()->sex('u')->count(),
                'sex_ratio' => $this->percentage($males, $males + $females, 1),
                'min_birth' => $births->min('date'),
                'max_birth' => $births->max('date'),
                'min_death' => $deaths->min('date'),
                'max_death' => $deaths->max('date'),
                'total_events' => $indi_events + $fami_events,
                'indi_events' => $indi_events,
                'fami_events' => $fami_events,
                'avg_age' => number_format($gedcom->avg_lifespan(), 2),
                'max_age' => $gedcom->max_lifespan(),
                'min_age' => $gedcom->min_lifespan(),
                // TODO: recalculate marriage age based on marriage_ages table
                'avg_marriage_age' => 0, //number_format($gedcom->avgMarriageAge(), 2),               
                'max_marriage_age' => 0, //$gedcom->maxMarriageAge(),
                'min_marriage_age' => 0, //$gedcom->minMarriageAge(),
                'all_fami' => $fam_count,
                'fams_with_children' => sprintf('%d (%.2f%%)', $num_fams_with_children, $this->percentage($num_fams_with_children, $fam_count)),
                'avg_fam_size' => $avg_fam_size,
                'max_fam_size' => $max_fam_size,
            );

            $this->layout->content = View::make('gedcom/gedcoms/detail', compact('gedcom', 'statistics'));
        }
        else
        {
            return Response::make('Unauthorized', 401);
        }
    }

    /**
     * Creates the upload form (post is handled in FileUploadsController)
     */
    public function getUpload()
    {
        $this->layout->content = View::make('gedcom/gedcoms/upload');
    }

    /**
     * Show the form for editing the specified Gedcom.
     * @param int $id
     * @return Response the edit page
     */
    public function getEdit($id)
    {
        $gedcom = Gedcom::findOrFail($id);

        if ($this->allowedAccess($gedcom->user_id))
        {
            $this->layout->content = View::make('gedcom/gedcoms/edit')->with('gedcom', $gedcom);
        }
        else
        {
            return Response::make('Unauthorized', 401);
        }
    }

    /**
     * Update the specified Gedcom.
     * @param int $id
     * @return Response the index page if validation passed, else the edit page
     */
    public function postUpdate($id)
    {
        $gedcom = Gedcom::findOrFail($id);

        if ($this->allowedAccess($gedcom->user_id))
        {
            $validator = Validator::make(Input::all(), Gedcom::$update_rules);

            if ($validator->passes())
            {
                $gedcom->tree_name = Input::get('tree_name');
                $gedcom->source = Input::get('source');
                $gedcom->notes = Input::get('notes');
                $gedcom->save();

                return Redirect::to('gedcoms/index')->with('message', 'The GEDCOM ' . $gedcom->file_name . ' has been updated.');
            }
            else
            {
                return Redirect::to('gedcoms/edit/' . $id)->withErrors($validator)->withInput();
            }
        }
        else
        {
            return Response::make('Unauthorized', 401);
        }
    }

    /**
     * Show a list of all the GedcomIndividuals for the specified Gedcom.
     */
    public function getIndividuals($id)
    {
        $gedcom = Gedcom::findOrFail($id);

        if ($this->allowedAccess($gedcom->user_id))
        {
            $source = 'gedcoms/indidata/' . $id;
            $title = $gedcom->tree_name;
            $subtitle = Lang::get('gedcom/individuals/subtitle.result_one_tree');
            $count = $gedcom->individuals()->count();
            $this->layout->content = View::make('gedcom/individuals/index', compact('source', 'title', 'subtitle', 'count'));
        }
        else
        {
            return Response::make('Unauthorized', 401);
        }
    }

    /**
     * Show a list of all the GedcomIndividuals for the specified Gedcom.
     */
    public function getFamilies($id)
    {
        $gedcom = Gedcom::findOrFail($id);

        if ($this->allowedAccess($gedcom->user_id))
        {
            $source = 'families/data/' . $id;
            $title = $gedcom->tree_name;
            $subtitle = Lang::get('gedcom/families/subtitle.result_one_tree');
            $count = $gedcom->families()->count();
            $this->layout->content = View::make('gedcom/families/index', compact('source', 'title', 'subtitle', 'count'));
        }
        else
        {
            return Response::make('Unauthorized', 401);
        }
    }

    /**
     * Remove the specified Gedcom (and the files).
     * @param int $id
     * @return the index page with a success message
     */
    public function getDelete($id)
    {
        $gedcom = Gedcom::findOrFail($id);

        if ($this->allowedAccess($gedcom->user_id))
        {
            //delete database entries
            $gedcom->delete();

            $user_dir = Config::get('app.upload_dir') . '/' . Auth::id() . '/';
            $files_dir = bin2hex($gedcom->file_name);

            chdir($user_dir);
            $this->removeDir($files_dir);

            return Redirect::to('gedcoms/index')->with('message', 'Gedcom successfully deleted');
        }
        else
        {
            return Response::make('Unauthorized', 401);
        }
    }

    public function getHistogram()
    {
        $this->layout->content = View::make('gedcom/gedcoms/histogram');
    }

    public function getHistodata()
    {
        $header = array('cols' => array(
                array('label' => 'Families', 'type' => 'string'),
                array('label' => 'Children', 'type' => 'number'),
        ));

        // FIXME: do this properly via the model.
        $raw = "select ucount, count(*) as count
        from (select count(children.id) as ucount 
        from `families` 
        left join `children` on `families`.`id` = `children`.`fami_id` 
        where `families`.`gedcom_id` = 11
        group by families.id) as x
        group by x.ucount
        order by x.ucount";

        $results = DB::select(DB::raw($raw));

        $table = array();
        foreach ($results AS $result)
        {
            array_push($table, array('c' => array(
                    array('v' => $result->ucount),
                    array('v' => $result->count),
            )));
        }

        $rows = array('rows' => $table);

        return Response::json(array_merge($header, $rows));
    }

    /**
     * Show a list of all the gedcoms formatted for Datatables.
     * @return Datatables JSON
     */
    public function getData()
    {
        $user = Auth::user();

        $gedcoms = Gedcom::select(array('file_name', 'tree_name', 'source', 'notes', 'parsed', 'id'));

        if ($user->role != 'admin')
        {
            $gedcoms->where('user_id', $user->id);
        }

        return Datatables::of($gedcoms)
                        ->edit_column('file_name', '{{ HTML::link("gedcoms/show/" . $id, $file_name) }}')
                        ->edit_column('notes', '{{{ Str::words($notes, 10) }}}')
                        ->add_column('actions', function($row)
                        {
                            return $this->actions($row);
                        })
                        ->remove_column('parsed')
                        ->remove_column('id')
                        ->make();
    }

    /**
     * Show a list of all the unparsed gedcoms formatted for Datatables.
     * @return Datatables JSON
     */
    public function getUnparseddata()
    {
        $user = Auth::user();

        $gedcoms = Gedcom::select(array('file_name', 'tree_name', 'source', 'notes', 'parsed', 'id'));
        $gedcoms->where('parsed', 0);
        if ($user->role != 'admin')
        {
            $gedcoms->where('user_id', $user->id);
        }

        return Datatables::of($gedcoms)
                        ->edit_column('file_name', '{{ HTML::link("gedcoms/show/" . $id, $file_name) }}')
                        ->edit_column('notes', '{{{ Str::words($notes, 10) }}}')
                        ->add_column('parse_url', '{{ HTML::link("parse/parse/" . $id, "Parse") }}')
                        ->remove_column('parsed')
                        ->remove_column('id')
                        ->make();
    }

    /**
     * Show a list of all the unchecked gedcoms formatted for Datatables.
     * @return Datatables JSON
     */
    public function getUncheckeddata()
    {
        $user = Auth::user();

        $gedcoms = Gedcom::select(array('file_name', 'tree_name', 'source', 'notes', 'error_checked', 'id'));
        $gedcoms->where('error_checked', 0);
        if ($user->role != 'admin')
        {
            $gedcoms->where('user_id', $user->id);
        }

        return Datatables::of($gedcoms)
                        ->edit_column('file_name', '{{ HTML::link("gedcoms/show/" . $id, $file_name) }}')
                        ->edit_column('notes', '{{{ Str::words($notes, 10) }}}')
                        ->add_column('check_url', '{{ HTML::link("errors/gedcom/" . $id, "Check") }}')
                        ->remove_column('error_checked')
                        ->remove_column('id')
                        ->make();
    }

    /**
     * Show a list of all the GedcomIndividuals formatted for Datatables.
     * @return Datatables JSON
     */
    public function getIndidata($id)
    {
        $user = Auth::user();

        $individuals = GedcomIndividual::leftJoin('gedcoms', 'gedcoms.id', '=', 'individuals.gedcom_id')
                ->select(array('individuals.gedcom_key', 'individuals.first_name', 'individuals.last_name', 'individuals.sex', 'individuals.id'));
        $individuals->where('gedcom_id', $id);
        if ($user->role != 'admin')
        {
            $individuals->where('gedcoms.user_id', $user->id);
        }

        return Datatables::of($individuals)
                        ->edit_column('gedcom_key', '{{ HTML::link("individuals/show/" . $id, $gedcom_key) }}')
                        ->remove_column('id')
                        ->make();
    }

    /**
     * Show a list of all the GedcomFamilies formatted for Datatables.
     * @return Datatables JSON
     */
    public function getFamidata($id)
    {
        $user = Auth::user();

        $families = GedcomFamily::leftJoin('gedcoms', 'gedcoms.id', '=', 'families.gedcom_id')
                ->select(array('families.gedcom_key', 'families.indi_id_husb', 'families.indi_id_wife', 'families.id'));
        $families->where('gedcom_id', $id);
        if ($user->role != 'admin')
        {
            $families->where('gedcoms.user_id', $user->id);
        }

        return Datatables::of($families)
                        ->edit_column('gedcom_key', '{{ HTML::link("families/show/" . $id, $gedcom_key) }}')
                        ->edit_column('indi_id_husb', '{{ $indi_id_husb ? HTML::link("individuals/show/" . $indi_id_husb, $indi_id_husb) : "" }}')
                        ->edit_column('indi_id_wife', '{{ $indi_id_wife ? HTML::link("individuals/show/" . $indi_id_wife, $indi_id_wife) : "" }}')
                        ->remove_column('id')
                        ->make();
    }

    /**
     * Returns the actions for a Gedcom data row
     * @param Gedcom $row
     * @return array
     */
    private function actions($row)
    {
        $show = HTML::link('gedcoms/show/' . $row->id, '', array(
                    'class' => 'glyphicon glyphicon-zoom-in',
                    'title' => Lang::get('common/actions.show')));
        $edit = HTML::link('gedcoms/edit/' . $row->id, '', array(
                    'class' => 'glyphicon glyphicon-pencil',
                    'title' => Lang::get('common/actions.edit')));
        $delete = HTML::link('gedcoms/delete/' . $row->id, '', array(
                    'class' => 'glyphicon glyphicon-trash',
                    'title' => Lang::get('common/actions.delete')));
        $parse = HTML::link('parse/parse/' . $row->id, '', array(
                    'class' => 'glyphicon glyphicon-save parse',
                    'title' => Lang::get('gedcom/gedcoms/actions.parse')));
        $errors = HTML::link('errors/gedcom/' . $row->id, '', array(
                    'class' => 'glyphicon glyphicon-warning-sign',
                    'title' => Lang::get('gedcom/errors/table.errors')));

        $result = array($edit, $delete, $parse);
        if ($row->parsed)
        {
            $result = array_merge(array($show), $result, array($errors));
        }
        return implode(' ', $result);
    }

    /**
     * Returns the division of two values, multiplied by a factor, rounded to two decimals
     * @param int $val1 the numerater
     * @param int $val2 the denominator
     * @param int $multiplier the multiplier (100 for percentages)
     * @return int the result
     */
    private function percentage($val1, $val2, $multiplier = 100)
    {
        if ($val2 == 0)
        {
            return 0;
        }
        return number_format(($val1 / $val2) * $multiplier, 2);
    }

    /**
     * Removes a directory and its contents, recursively.
     * @param string $directory
     */
    private function removeDir($directory)
    {
        foreach (glob("{$directory}/*") as $file)
        {
            if (is_dir($file))
            {
                $this->removeDir($file);
            }
            else
            {
                unlink($file);
            }
        }
        rmdir($directory);
    }

}
