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


/**
 * Renders the form used to edit records &/or replace existing shared files in the MrCUTE Jr. URL/File repository
 */
require_once('config.php');//our own config file includes Moodle config too

//must be an authorized user
require_authorized_mrcutejr_editor();//defined in our config.php

//add custom css and javascript files into standard header
$CFG->stylesheets[] = 'mrcutejr.css';
require_js('mrcutejr.js');//used to move data from popup to parent window

//get required variables
$editid        = optional_param('edit',          0, PARAM_INT);//from GET
$replacefileid = optional_param('replacefile',   0, PARAM_INT);//from GET
$keepsamefileid= optional_param('keepsamefile',  0, PARAM_INT);//from GET
$rid           = optional_param('rid',           0, PARAM_INT);//from POST only!
$ptitle        = optional_param('title',        '', PARAM_TEXT);
$pdesc         = optional_param('description',  '', PARAM_TEXT);
$pkeywds       = optional_param('keywords',     '', PARAM_TEXT);
$isurl         = optional_param('addurl',        0, PARAM_BOOL);
$isfile        = optional_param('addfile',       0, PARAM_BOOL);
$blockmode     = optional_param('blockmode',     0, PARAM_BOOL);
if(!$blockmode) {
    $blockmode = optional_param('id_blockmode',  0, PARAM_BOOL);
}
if(!$rid) {
    $rid = ($editid)? $editid : $replacefileid;
}
if($rid) {
    if(!$resource = get_record('resource_mrcutejr', 'id', $rid)) {
        err('mrcutejr_errorretrievingrecord');
    }
} else {
    err('mrcutejr_noeditrecordid');
}
$resource->editid = $editid;
$resource->replacefileid = $replacefileid;
$resource->rid = $rid;
if($resource->isfile) {
    $strformtitle = strip_tags(lang('editfileformtitle'));
    $pdirectory = optional_param('directory', '', PARAM_PATH);
} else {
    $strformtitle = strip_tags(lang('editurlformtitle'));
    $purl  = optional_param('id_url',       '', PARAM_NOTAGS);
    $picon = optional_param('custicon', 'html', PARAM_TEXT);
}

//check if this is a form submission
if($_SERVER['REQUEST_METHOD']=='POST') {
    //verify fields filled in
    $missingfields = ($ptitle=='' || $pdesc=='' || $pkeywds=='');
    if(!$missingfields) {
        if($isfile && !$keepsamefileid) {//directory is only required for files
            $missingfields = ($pdirectory=="" || $_FILES['id_file']['name']=="");
        } else if($isurl) {
            $missingfields = ($purl=='' || $purl=='http://');
        }
    }
    if($missingfields) {
        err('mrcutejr_requireallfields');
    }
    //set the new resources' attributes
    $resource->title = $ptitle;
    $resource->description = $pdesc;
    $resource->keywords = str_replace(array("\r\n","\n","\r"),',',$pkeywds);
    $resource->modifieddate = time(); //unix timestamp
    if($isfile && !$keepsamefileid) {//handle file
        //keep our old file for later deletion!
        $oldfile = $resource->realreference;
        //add slashes to our directory
        if($pdirectory!="/") {
            $pdirectory .= "/";
        }
        //get new file details
        $filename = sanitize_filename(basename($_FILES['id_file']['name']));
        $fileto = $CFG->block_mrcutejr_repository.$pdirectory.$filename;
        //copy the tmp file
        if(!move_uploaded_file($_FILES['id_file']['tmp_name'], $fileto)) {
            err('mrcutejr_couldnotsaveto', $pdirectory.$filename);
        }
        $resource->reference = $filename;//trimmed a little later if too long
        $resource->realreference = $pdirectory.$filename;//the repo resource
        $resource->isfile=1;
        if($oldfile!==$resource->realreference) {
            //delete old file, errors ignored:
            //not a critical failure & unlikely at this point could add a js alert for this event to let user know...
            $deloldfailed = @unlink($CFG->block_mrcutejr_repository.$oldfile);
        }
    } else if(!empty($purl)) {
        //handle URL
        if(!strpos($purl,"//") && substr($purl,0,1)!="/") {
            //is relative url, but not root relative, so assume moodle wwwroot
            $purl = $CFG->wwwroot.'/'.$purl;//cuz relative url unpredictable!
        }
        $resource->reference = "webpage.".$picon;//ext determines icon type
        $resource->realreference = $purl;//the real repository resource
        $resource->isfile=0;//false
    }
    //ensure our reference field never exceeds db field of 50 chars
    if(strlen($resource->reference)>50) {//db field is 50 in total 
        $resource->reference = substr(
            $resource->reference, 
            strlen($resource->reference)-50 //that's one long filename!!!
        );
    }
    //insert resource
    if(!$newrid = update_record("resource_mrcutejr", $resource)) {
        //update failed
        err('mrcutejr_failedwritetodatabase');
    } else {
        //success!
        $href = $CFG->wwwroot."/mod/resource/type/mrcutejr/search.php";
        $href .= "?search=MrCuteJr$rid&blockmode=$blockmode";
        header("Location: $href");
    }
}

//show form
require_once('forms/form_edit_mrcutejr.php');
$mform = new form_edit_mrcutejr(
    null, 
    $resource,
    'post',
    '',
    array('enctype'=>"multipart/form-data")
);

//print standard html head - set title var to show current theme header html
print_header($strformtitle,'','',$mform->focus(),'',false);//title, no cache

//display form
$mform->display();

//output html footer - exclude 'empty' to show current theme footer html
print_footer('empty');

?>