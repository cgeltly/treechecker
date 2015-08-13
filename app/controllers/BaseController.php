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

class BaseController extends Controller
{

    /**
     * Initializer.
     *
     * @access   public
     * @return \BaseController
     */
    public function __construct()
    {
        $this->beforeFilter('csrf', array('on' => 'post'));
    }

    /**
     * Setup the layout used by the controller.
     *
     * @return void
     */
    protected function setupLayout()
    {
        if (!is_null($this->layout))
        {
            $this->layout = View::make($this->layout);
        }
    }

    /**
     * Allow access only where the data belongs to the user, or user is admin 
     * @param int $user_id
     * @return true|false 
     */
    protected function allowedAccess($user_id)
    {
        return $user_id == Auth::user()->id || Auth::user()->role == 'admin';
    }

    /**
     * Turns an array into XML. 
     * Copied from https://github.com/pyrello/laravel-xml/blob/master/src/Pyrello/LaravelXml/XmlTools.php
     * @param array $arr
     * @param SimpleXMLElement $xml
     * @return string
     */
    protected function array_to_xml($arr, $xml)
    {
        foreach ($arr as $key => $item)
        {
            if (is_array($item))
            {
                // If the $key is numeric, we convert it to the singular form
                // of the element name it is contained in
                if (is_numeric($key))
                {
                    $key = str_singular($xml->getName());
                }
                $node = $xml->addChild($key);
                $this->array_to_xml($item, $node);
            }
            else
            {
                // If the item is a boolean, convert it to a string, so that it shows up
                if (is_bool($item))
                {
                    $item = ($item) ? 'true' : 'false';
                }
                // We use the $xml->{$key} form to add the item because this causes
                // conversion of '&' => '&amp;
                $xml->{$key} = $item;
            }
        }

        return $xml->asXml();
    }

}
