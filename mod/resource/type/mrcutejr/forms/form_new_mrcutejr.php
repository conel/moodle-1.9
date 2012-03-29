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


require_once ($CFG->libdir.'/formslib.php');

/***
 * Form for adding a new MrCUTE Jr. shared URL or File resource.
 */
class form_new_mrcutejr extends moodleform {
    function definition() {
        global $CFG;
        $mform = &$this->_form;
        $addfile = ($this->_customdata===1);//"0" for false, "1" for true
        //get language strings
        if($addfile) {
            $strformtitle     = lang('newfileformtitle');
            $strsave          = lang('savefile');
            $strnewfile       = lang('newfile');
            $strdirectory     = lang('directory');
        } else {
            $strformtitle     = lang('newurlformtitle');
            $strsave          = lang('saveurl');
            $strnewurl        = lang('newurl');
            $strselecturltype = lang('selecturltype');
        }
        $strtitle             = lang('title');
        $strdesc              = lang('description');
        $strkewords           = lang('keywords');
        
        //form heading
        $mform->addElement('header', '', $strformtitle);
        //url or file
        if($addfile) {//file
            //add file field
            $mform->addElement(
                'file', 
                'id_file', 
                $strnewfile, 
                array('size'=>'30','class'=>'w')
            );
            $mform->addRule('id_file', null, 'required', null, 'client');
            //select file directory
            $path = $CFG->block_mrcutejr_repository;
            $directories = array();
            $directories['/']='/';//default
            $repositorydirectories = directory_to_array($path, true);//recursive listing
            if(!empty($repositorydirectories)) {
                $startpos = strlen($CFG->block_mrcutejr_repository);
                foreach($repositorydirectories as $v) {
                    $v = substr($v, $startpos);
                    $directories[$v] = $v;
                }
            }
            $mform->addElement('select', 'directory', $strdirectory, $directories);
            $mform->addRule('directory', null, 'required', null, 'client');
        } else {//url
            //field to type in the url
            $mform->addElement(
                'text', 
                'id_url', 
                $strnewurl, 
                array('size'=>'50','class'=>'w', 'value'=>'http://')
            );
            $mform->addRule('id_url', null, 'required', null, 'client');
            //select url "type" (url file extension, e.g. ".gif")
            //NOTE: in the future we could pull this list from admin (let's see
            //how well this works for a while first...)
            $mform->addElement(
                'select',
                'custicon',
                $strselecturltype,
                array(
                    'html'=>'html',//value=>displayed text
                    'flv'=>'flv',
                    'mp3'=>'mp3',
                    'ppt'=>'ppt',
                    'pdf'=>'pdf',
                    'swf'=>'swf',
                    'gif'=>'gif',
                    'jpg'=>'jpg',
                    'png'=>'png',
                    'txt'=>'txt'
                )
            );
            $mform->addRule('custicon', null, 'required', null, 'client');
        }
        //title
        $mform->addElement(
            'text', 
            'title', 
            $strtitle, 
            array('size'=>'50','class'=>'w')
        );
        $mform->addRule('title', null, 'required', null, 'client');
        //description
        $mform->addElement(
            'textarea', 
            'description', 
            $strdesc, 
            array('cols'=>'43','rows'=>'5','class'=>'w')
        );
        $mform->addRule('description', null, 'required', null, 'client');
        //keywords
        $mform->addElement(
            'textarea', 
            'keywords', 
            $strkewords, 
            array('cols'=>'43','rows'=>'2','class'=>'w')
        );
        $mform->addRule('keywords', null, 'required', null, 'client');
        //submit button
        $mform->addElement('group', '', ' ');//borderless space rather than nxt:
        //$this->add_action_buttons(false, $strsave);//this causes formatting...
        $mform->addElement('submit', 'submit', $strsave);
    }
}//END CLASS

?>