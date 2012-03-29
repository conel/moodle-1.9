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
 * Form for editing a MrCUTE Jr. shared URL or File resource database record.
 */
class form_edit_mrcutejr extends moodleform {
    function definition() {
        global $CFG;
        $mform = &$this->_form;
        $resource = $this->_customdata;
        $mform->addElement('hidden', 'rid', $resource->rid);
        $blockmode = optional_param('blockmode', 0, PARAM_BOOL);
        if($blockmode) {
            $mform->addElement('hidden', 'id_blockmode', $blockmode);
        }
        //get language strings
        if($resource->isfile) {
            $strformtitle     = lang('editfileformtitle');
            $strnewfile       = lang('newfile');
            $strdirectory     = lang('directory');
            $streditfile      = lang('editingfile');
            $strreplacefile   = lang('replacefile');
        } else {
            $strformtitle     = lang('editurlformtitle');
            $strnewurl        = lang('newurl');
            $strselecturltype = lang('selecturltype');
        }
        $strsave              = lang('update');
        $strtitle             = lang('title');
        $strdesc              = lang('description');
        $strkewords           = lang('keywords');
        
        //form heading
        $mform->addElement('header', '', $strformtitle);
        //url or file
        if($resource->isfile) {//file
            $mform->addElement('hidden', 'addfile', 1);
            if($resource->replacefileid) {
                $mform->addElement(
                    'file', 
                    'id_file', 
                    $strnewfile, 
                    array('size'=>'30','class'=>'w')
                );
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
                $mform->addRule('id_file', null, 'required', null, 'client');
                $mform->addRule('directory', null, 'required', null, 'client');
            } else {
                //just show what file we're editing details for, w/ replace link
                $mform->addElement('hidden', 'keepsamefile', $resource->id);
                $previewbaseurl = $CFG->wwwroot.'/mod/resource/type/mrcutejr/preview.php?file=';
                $previewhref = $previewbaseurl.$resource->realreference;
                $qry = '?replacefile='.$resource->rid."&blockmode=$blockmode";
                $editbutton = '<input type="button" name="'.$strreplacefile.
                    '" value="'.$strreplacefile.
                    '" onclick="window.location.href=\'edit.php'.$qry.'\'" />';
                $mform->addElement(
                    'html', 
                    '<p class="editingfile">'.$streditfile.': <code><a href="'.
                        $previewhref.'" target="_blank">'.
                        $resource->realreference.'</a></code> &nbsp;'.
                        $editbutton.'</p>'
                );
            }
        } else {//url
            $mform->addElement('hidden', 'addurl', 1);
            $mform->addElement(
                'text', 
                'id_url', 
                $strnewurl, 
                array('size'=>'50', 'class'=>'w',
                    'value'=>$resource->realreference)
            );
            $mform->addRule('id_url', null, 'required', null, 'client');
            //NOTE: in future we could pull this list from admin (but let's see how this works for a while first...)
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
            $p = pathinfo($resource->reference);
            $mform->setDefault('custicon', $p['extension']);
            $mform->addRule('custicon', null, 'required', null, 'client');
        }
        //title
        $mform->addElement(
            'text', 
            'title', 
            $strtitle, 
            array('size'=>'50','class'=>'w',
                'value'=>$resource->title)
        );
        $mform->addRule('title', null, 'required', null, 'client');
        //description
        $mform->addElement(
            'textarea', 
            'description', 
            $strdesc, 
            array('cols'=>'43','rows'=>'5','class'=>'w')
        );
        $mform->setDefault('description', $resource->description);
        $mform->addRule('description', null, 'required', null, 'client');
        //keywords
        $mform->addElement(
            'textarea', 
            'keywords', 
            $strkewords, 
            array('cols'=>'43','rows'=>'2','class'=>'w',
                'value'=>$resource->keywords)
        );
        $mform->setDefault('keywords', $resource->keywords);
        $mform->addRule('keywords', null, 'required', null, 'client');
        //submit button
        $mform->addElement('group', '', ' ');//borderless space rather than nxt:
        //$this->add_action_buttons(false, $strsave);//this causes formatting...
        $mform->addElement('submit', 'submit', $strsave);
    }
}//END CLASS

?>