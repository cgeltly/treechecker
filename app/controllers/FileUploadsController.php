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

class FileUploadsController extends BaseController
{

    public function __construct()
    {
        parent::__construct();

        //prevent access to controller methods without login
        $this->beforeFilter('auth');
    }

    /**
     * Uploads the Gedcom to the file server, splits it in chunks for faster processing.
     * @return Response the index page if validation passes, else the upload page.
     */
    public function postUpload()
    {
        foreach (Input::file('uploads') as $file)
        {
            $validator = Validator::make(Input::all(), Gedcom::$rules);
            if ($validator->passes())
            {
                $orig_file_name = $file->getClientOriginalName();

                // Create a file storage directory based on user id and hex of file name
                $abs_storage_dir = Config::get('app.upload_dir') . '/' . Auth::id() . '/' . bin2hex($orig_file_name);
                $rel_storage_dir = '/' . Auth::id() . '/' . bin2hex($orig_file_name);

                // Check if the storage location already exists, i.e. if same file uploaded before
                if (file_exists($abs_storage_dir))
                {
                    // TODO: ouput error to a view
                    // TODO: eventually allow and deal with resubmissions here
                    echo 'Error: you have already uploaded this file';
                    dd($orig_file_name);
                }
                else
                {
                    mkdir($abs_storage_dir, 0775, true);
                }

                $new_file_name = 'original.ged';
                $file->move($abs_storage_dir, $new_file_name);

                // Split original file into separate files each of approx 10,000 lines
                // Each file starting with a /^0 /, i.e. top-level record
                // Place in storage location numbered consecutively

                chdir($abs_storage_dir);

                // Either use awk or the chunkFile method below
                //exec("awk 'BEGIN{out=1} NR>1 && ++i>10000 && /^0 / {++out; i=0} {print > out}' $new_file_name");
                $this->chunkFile($new_file_name);

                // Save to database
                $gedcom = new Gedcom();
                $gedcom->user_id = Auth::user()->id;
                $gedcom->file_name = $orig_file_name;
                $gedcom->path = $rel_storage_dir;
                $gedcom->tree_name = Input::get('tree_name');
                $gedcom->source = Input::get('source');
                $gedcom->notes = Input::get('notes');
                $gedcom->save();
            }
            else
            {
                return Redirect::to('gedcoms/upload')->withErrors($validator)->withInput();
            }
        }
        return Redirect::to('gedcoms/index')->with('message', 'The file(s) were uploaded. '
                        . 'Press <span class="glyphicon glyphicon-save"></span> to parse the file(s).');
    }

    /**
     * Chunk a file into smaller files.
     * @author webtrees
     * @param string $file
     */
    private function chunkFile($file)
    {
        $file_data = '';
        $fp = fopen($file, 'rb');
        $i = 1;

        while (!feof($fp))
        {
            $file_data.=fread($fp, 65536);
            // There is no strrpos() function that searches for substrings :-(
            for ($pos = strlen($file_data) - 1; $pos > 0; --$pos)
            {
                if ($file_data[$pos] == '0' && ($file_data[$pos - 1] == "\n" || $file_data[$pos - 1] == "\r"))
                {
                    // We've found the last record boundary in this chunk of data
                    break;
                }
            }
            if ($pos)
            {
                $output = fopen("$i", "w");
                fwrite($output, substr($file_data, 0, $pos));
                fclose($output);
                $i++;
                $file_data = substr($file_data, $pos);
            }
        }
        $output = fopen("$i", "w");
        fwrite($output, $file_data);
        fclose($output);
        fclose($fp);
    }

}
