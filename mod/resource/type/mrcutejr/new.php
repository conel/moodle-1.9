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
 * Renders the form used to insert new records & upload new files to the shared URL/File repository
 */
require_once('config.php');//our own config file includes Moodle config too

//must be an authorized user
require_authorized_mrcutejr_editor();//defined in our config.php

//add custom css and javascript files into standard header
$CFG->stylesheets[] = 'mrcutejr.css';
require_js('mrcutejr.js');//used to move data from popup to parent window

//get required variables
$addurl    = optional_param('addurl',        0, PARAM_BOOL);
$addfile   = optional_param('addfile',       0, PARAM_BOOL);
if(!$addurl && !$addfile) {
    //because "?addfile=1" is not set after posting, we detect file field post
    $addfile = isset($_FILES['id_file']);
}
if($addfile) {
    $strformtitle = lang('newfileformtitle');
    $pdirectory = optional_param('directory', '', PARAM_PATH);
} else {
    $strformtitle = lang('newurlformtitle');
    $purl  = optional_param('id_url',       '', PARAM_NOTAGS);
    $picon = optional_param('custicon', 'html', PARAM_TEXT);
}
$ptitle    = optional_param('title',        '', PARAM_TEXT);
$pdesc     = optional_param('description',  '', PARAM_TEXT);
$pkeywds   = optional_param('keywords',     '', PARAM_TEXT);

//creating a new shared resource object
$resource = new object();

//check if this is a form submission
if($_SERVER['REQUEST_METHOD']=='POST') {
    //verify fields filled in
    $missingfields = ($ptitle=='' || $pdesc=='' || $pkeywds=='');
    if(!$missingfields) {
        if($addfile) {//directory is only required for files
            $missingfields = ($pdirectory=="" || $_FILES['id_file']['name']=="");
        } else {
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
    if($addfile) {//handle file
        //add slashes to our directory
        if($pdirectory!="/") {
            $pdirectory .= "/";
        }
        //get new file details
        $filename = sanitize_filename(basename($_FILES['id_file']['name']));
        $fileto = $CFG->block_mrcutejr_repository.$pdirectory.$filename;
        //make sure the file doesn't exist
        if(file_exists($fileto)) {
            err('mrcutejr_fileexists', $pdirectory.$filename);
        }
        // nkowald - 2010-02-10 - A big problem with teacher uploads is duplicate files.
        // We are going to use file hashes to check for duplicate files before saving/adding to database
        //echo hash_file('md5', $fileto);

        //copy the tmp file
        if(!move_uploaded_file($_FILES['id_file']['tmp_name'], $fileto)) {
            err('mrcutejr_couldnotsaveto', $pdirectory.$filename);
        }

        $resource->reference = $filename;//trimmed a little later if too long
        $resource->realreference = $pdirectory.$filename;//the repo resource
        $resource->isfile=1;
    } else if(!empty($purl)) {
        //handle URL
        if(!strpos($purl,"//") && substr($purl,0,1)!="/") {
            //is relative url, but not root relative, so assume moodle wwwroot
            $purl = $CFG->wwwroot.'/'.$purl;//cuz relative url unpredictable!
        }
        $resource->reference = "webpage.".$picon;//ext determines icon type
        $resource->realreference = $purl;//the real repository resource
        $resource->isfile=0;//false
    } else {
        err('mrcutejr_nofileorurl');
    }
    //ensure our reference field never exceeds db field of 50 chars
    if(strlen($resource->reference)>50) {//db field is 50 in total 
        $resource->reference = substr(
            $resource->reference, 
            strlen($resource->reference)-50 //that's one long filename!!!
        );
    }
    //insert resource
    if(!$newrid = insert_record("resource_mrcutejr", $resource)) {
        //insert failed, so checking if exists as possible reason for failure
        $rslt = get_record(
            'resource_mrcutejr',
            'reference',
            $resource->reference
        );
        if($rslt) {
            //yup, it already exists
            err('mrcutejr_duplicaterecorderror', ($isfile? "filename" : "URL"));
        } else {
            //else generic error; should be rare - e.g. "db server is down"
            err('mrcutejr_failedwritetodatabase');
        }
    }
    //the $j* vars are used in javascript
    $jtitle = inline_javascript_encode($resource->title);
    $jdesc = inline_javascript_encode($resource->description);
    $jref = "MrCuteJr".$newrid."_".$resource->reference;//important
    
    //print standard html head - set title var to show current theme header html
    print_header($strformtitle,'','','','',false);//titled window, no cache
    ?>
    <!-- this text not lang files as it should never been seen (acceptable?) -->
    <p><em>New Shared Resource Created!</em></p>
    <script type="text/javascript">
        //<![CDATA[
        //we will set values in parent window & close this popup window
        set_value(
            '<?php echo $jref;   ?>', 
            '<?php echo $jtitle; ?>', 
            '<?php echo $jdesc;  ?>'
        );
        function doClose() {
            set_value(
                '<?php echo $jref;   ?>',
                '<?php echo $jtitle; ?>',
                '<?php echo $jdesc;  ?>'
            );
        }
        //]]>
    </script>
    <p>This window should close automatically.  If it doesn't, try 
    <a href="javascript:doClose();">clicking here</a>; otherwise please copy
    and paste <strong><code><?php echo $jref; ?></code></strong> into
    the "Location" field of your main browser window and 
    close this window.</p>
    <?php
} else {
    //make sure the "repository" directory exists
    if(!file_exists($CFG->block_mrcutejr_repository)) {
        if(!mkdir($CFG->block_mrcutejr_repository, 0776, true)) {//"drwxrwxrw-"
            err("mrcutejr_couldnotcreaterepositordirectory");
            exit;
        }
    }
    //show form
    require_once('forms/form_new_mrcutejr.php');
    $mform = new form_new_mrcutejr(
        null, 
        $addfile,
        'post',
        '',
        array('enctype'=>"multipart/form-data")
    );
    //print standard html head - set title var to show current theme header html
    print_header($strformtitle,'','',$mform->focus(),'',false);//title, no cache
    $mform->display();
}

//output html footer - exclude 'empty' to show current theme footer html
print_footer('empty');

?>
