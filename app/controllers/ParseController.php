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

abstract class ParseController extends BaseController
{

    public function __construct()
    {
        parent::__construct();

        // Prevent access to controller methods without login
        $this->beforeFilter('auth');
    }

    /**
     * Parses a Gedcom and creates parse errors when necessary.
     * @param integer $gedcom_id
     * @return void (if finished)
     */
    public function getParse($gedcom_id)
    {
        // Get the GEDCOM
        $gedcom = Gedcom::findOrFail($gedcom_id);

        if ($this->allowedAccess($gedcom->user_id))
        {
            Session::put('progress', 1);
            Session::save();

            // Delete the related individuals, families and errors
            $gedcom->individuals()->delete();
            $gedcom->families()->delete();
            $gedcom->geocodes()->delete();
            $gedcom->errors()->delete();
            $gedcom->notes()->delete();

            Session::put('progress', 2);
            Session::save();

            // Retrieve the file folder
            $abs_storage_dir = Config::get('app.upload_dir') . $gedcom->path;
            chdir($abs_storage_dir);

            // Do before actions
            $this->doBeforeParse($gedcom_id);

            $filecount = 0;
            $files = glob("*");
            if ($files)
            {
                $filecount = count($files);
            }

            // Loop through the chunked files and parse/import them
            for ($i = 1; file_exists($i); ++$i)
            {
                $this->importRecords($i, $gedcom_id);
                Session::put('progress', min(floor(($i / $filecount) * 100), 99));
                Session::save();
            }

            // Do after actions
            $this->doAfterParse($gedcom_id);

            // Set the file as parsed, but not checked for errors
            $gedcom->parsed = true;
            $gedcom->error_checked = false;
            $gedcom->save();

            Session::put('progress', 100);
            Session::save();

            return;
        }
        else
        {
            return Response::make('Unauthorized', 401);
        }
    }

    abstract protected function doBeforeParse($gedcom_id);

    abstract protected function doAfterParse($gedcom_id);

    /**
     * Returns the progress of the current parse session. 
     * @return Response the progress in JSON format
     */
    public function getProgress()
    {
        $progress = Session::has('progress') ? Session::get('progress') : 0;
        return Response::json(array($progress));
    }

    /**
     * Imports all records of a file
     * @param string $file
     * @param integer $gedcom_id
     */
    protected function importRecords($file, $gedcom_id)
    {
        // Retrieve the contents of the file
        $gedcom = file_get_contents($file);

        // Allow 3 minutes of execution time
        set_time_limit(180);

        // Query log is save in memory, so we need to disable it for large file parsing
        DB::connection()->disableQueryLog();

        // Start the transaction
        DB::beginTransaction();

        // Start the import
        $this->doImport($gedcom_id, $gedcom);

        // End the transaction
        DB::commit();
    }

    /**
     * Starts the file-specific (GEDCOM, JSON) import.
     * @param integer $gedcom_id
     * @param string $gedcom
     */
    abstract protected function doImport($gedcom_id, $gedcom);

    /**
     * Looks up a GedcomIndividual by key. 
     * Creates a GedcomError if not found. 
     * @param integer $gedcom_id
     * @param string $gedcom_key
     * @return GedcomIndividual
     */
    protected function retrieveIndividual($gedcom_id, $gedcom_key)
    {
        if (!$gedcom_key)
        {
            return NULL;
        }

        $ind = GedcomIndividual::GedcomKey($gedcom_id, $gedcom_key)->first();

        if (!$ind)
        {
            $error = new GedcomError();
            $error->gedcom_id = $gedcom_id;
            $error->stage = 'parsing';
            $error->type_broad = 'missing';
            $error->type_specific = 'individual missing';
            $error->eval_broad = 'error';
            $error->eval_specific = '';
            $error->message = sprintf('No individual found for %s', $gedcom_key);
            $error->save();
        }

        return $ind;
    }

}
