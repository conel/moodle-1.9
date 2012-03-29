<?php
/*
 **************************************************************************
 *                                                                        *
 *                                                                        *
 *                            THIS SCRIPT                                 *
 *                                and                                     *
 *                         www.bydistance.com                             *
 *                 brought to you by Visions Encoded Inc.                 *
 *                                                                        *
 *                                                                        *
 *                                                                        *
 *             Visit us online at http://visionsencoded.com/              *
 *                You Bring The Vision, We Make It Happen                 *
 **************************************************************************
 **************************************************************************
 * NOTICE OF COPYRIGHT                                                    *
 *                                                                        *
 * Copyright (C) 2009                                                     *
 *                                                                        *
 * This program is free software; you can redistribute it and/or modify   *
 * it under the terms of the GNU General Public License as published by   *
 * the Free Software Foundation; either version 2 of the License, or      *
 * (at your option) any later version.                                    *
 *                                                                        *
 * This program is distributed in the hope that it will be useful,        *
 * but WITHOUT ANY WARRANTY; without even the implied warranty of         *
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the          *
 * GNU General Public License for more details:                           *
 *                                                                        *
 *                  http://www.gnu.org/copyleft/gpl.html                  *
 *                                                                        *
 *                                                                        *
 *                                                                        *
 **************************************************************************
 */

/***
 * This class creates the search block to search the repository.  This block
 * requires the installation of the mrcutejr resource type plugin in order to 
 * work; it only searches the mrcutejr repository.
 */
class block_mrcutejr extends block_base {
    function init() {
        $this->title = get_string('blocktitle', 'block_mrcutejr');
        $this->version = 2009081100;//yyyymmdd00
    }

    function get_content() {
        global $CFG, $USER;
        if ($this->content !== null) {
            return $this->content;
        }
        $strsearch = get_string('search', 'block_mrcutejr');
        $this->content = new stdClass();
        $this->content->footer = '';
        $url = "/mod/resource/type/mrcutejr/search.php?blockmode=1&search=";
        $ops = 'menubar=0,location=0,scrollbars,resizable,width=750,height=500';
        $fullscreen = 0;
        $btnattr = "return openpopup('$url'";
        $btnattr .= "+document.getElementById('id_searchmrcutejr').value, ";
        $btnattr .= "'mrcutejr', '$ops', $fullscreen);";
        $txt  = '<div class="searchform mrcutejr" style="text-align:center;">';
        $txt .= '<form style="display:inline" onsubmit="'.$btnattr;
        $txt .= '"><fieldset class="invisiblefieldset">';
        $txt .= '<label class="accesshide" for="searchform_search">';
        $txt .= $strsearch.'</label><input id="id_searchmrcutejr" ';
        $txt .= 'name="searchmrcutejr" type="text" size="16" />';
        $txt .= '<button id="searchform_button" type="submit" title="';
        $txt .= $strsearch.'">'.$strsearch.'</button>';
        $txt.= '</fieldset></form></div>';
        $this->content->text = $txt;
        return $this->content;
    }

    function has_config() {
        return true;
    }

    function config_save($data) {
        foreach ($data as $name => $value) {
            set_config($name, $value);
        }
        return true;
    }
}//END CLASS

?>