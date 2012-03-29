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
 * This script presents a page allowing an admin to "import" unregistered files from the mrcutejr repository directory
 * into the repository database table, so they are searchable and can be used from the mrcutejr repository plugin.
 *
 * This script may only be used by an admin; permissions are hard-coded for now (pending future updates, or possible
 * obsoletion of everything mrcutejr with Moodle 2.0, we'll see...).
 *
 * @author Leo Thiessen at www.bydistance.com
 */


require_once('config.php');//mrcutejr/config.php also includes moodle config.php

//security check
if(!is_mrcutejr_admin()) {//simply show an error screen
    print_error('mrcutejr_failedusercheck', 'resource_mrcutejr', 'javascript:window.close()');
}

//check if we even have a repository to deal with
if(!file_exists($CFG->block_mrcutejr_repository)) {
    err('nothingtoimport');
}

//some reuseable vars for clarity
$col_realreference = 0;
$col_title = 1;
$col_description = 2;
$col_keywords = 3;

//check for mrcutejr.xls download request
$requestedxls = optional_param('getunregisteredfilesxls', '', PARAM_TEXT);

if(!empty($requestedxls) && urldecode($requestedxls)==$USER->username.$USER->id) {
    //RETURN A .XLS that contains "unregistered" files that exist in repo dir but are not in the db table
    require_once($CFG->dirroot.'/lib/excellib.class.php');//for new MoodleExcelWorkbook()
    require_once($CFG->dirroot.'/lib/filelib.php');//for mimetype
    //determine file name
    $downloadfilename = "mrcutejr.xls";
    //create a workbook
    $workbook = new MoodleExcelWorkbook("-");
    //add a worksheet
    $sheet =& $workbook->add_worksheet("mrcutejr");
    $row = 0;
    //print names of all the fields to the first row (row zero) -- write_string(row, col, stringdata)
    $boldformat = $workbook->add_format(array('bold'=>1));
    $sheet->write_string($row, $col_realreference, "realreference", $boldformat);
    $sheet->write_string($row, $col_title,         "title",         $boldformat);
    $sheet->write_string($row, $col_description,   "description",   $boldformat);
    $sheet->write_string($row, $col_keywords,      "keywords",      $boldformat);
    //add data
    $files = directory_to_array($CFG->block_mrcutejr_repository, true, "files");
    $nothingimported = true;
    $strstartpos = strlen($CFG->block_mrcutejr_repository);
    // need to cut down array if in '/streaming_videos/' directory, should only add *.ism files and ignore the rest
    $i = 0;
    foreach($files as $file) {
        $realreference = substr($file, $strstartpos);
        if (strpos($realreference, '/streaming_videos/') === 0) {
            // if file does NOT end in .ism: remove it from the files array
            $ext = pathinfo($realreference, PATHINFO_EXTENSION);
            if ($ext != 'ism') {
                unset($files[$i-1]);
            }
        }
        $i++;
    }

    foreach($files as $path) {
        // Need to make ism files into URL
        // http://moodle-backup/smedia/index.php?video=
        $realreference = substr($path, $strstartpos);

        if (strpos($realreference, '/streaming_videos/') === 0) {
            // if file does NOT end in .ism: remove it from the files array
            $ext = pathinfo($realreference, PATHINFO_EXTENSION);
            if ($ext == 'ism') {
                // Get the name of the video from the file reference
                preg_match('/\/streaming_videos\/(.*)\.ism$/', $realreference, $names);
                $video_name = $names[1];
                $url = $CFG->wwwroot . '/smedia/index.php?video=' . $video_name;
                $realreference = $url;
            }
        }

        if(!record_exists('resource_mrcutejr', 'realreference', $realreference)) {
            $nothingimported = false;
            //we'll make some semi-intelligent guesses for title's & such, to *try* to lessen the pain of data entry...
            $directories = dirname($realreference);
            $fileparts   = split("\.", basename($realreference));
            $filename    = $fileparts[0];//this wouldn't work with a ".dotfile" -> filtered out in directory_to_array()
            $iconparts = split("\.", trim(mimeinfo("icon", $realreference)));
            $regxfind = '/(\B[A-Z])(?=[a-z])|(?<=[a-z])([A-Z])/sm';
            $regxreplace = ' $1$2';
            if ($ext == 'ism') {
                $ftitle = $video_name;
                $fdesc = $video_name;
                $video_name_lowercase = strtolower($video_name);
                $fkeywords = 'streaming_video, '. $video_name_lowercase;
            } else {
                $ftitle = ucwords(preg_replace($regxfind, $regxreplace, str_replace(array("-","_"), " ", $filename)));
                $fdesc = ucfirst(strtolower($ftitle));
                $fkeywordsarray = split("/", substr($directories,1));
                $fkeywordsfiltered = array();
                $fkeywordsfiltered[] = $iconparts[0];//get name of icon without extension
                foreach($fkeywordsarray as $v) {
                    if(strlen($v)>2) {
                        $fkeywordsfiltered[] = $v;
                    }
                }
                $fkeywords = implode(",",array_unique($fkeywordsfiltered));
            }
            //add data to the sheet with our guesses for values
            $row++;
            $sheet->write_string($row,$col_realreference, $realreference);//this value is not a guess, however!
            $sheet->write_string($row,$col_title,         $ftitle);       //maybe good if filenameing convention works
            $sheet->write_string($row,$col_description,   $fdesc);        //probably not too good
            $sheet->write_string($row,$col_keywords,      $fkeywords);    //maybe ok, but not really good enough...
        }
    }
    if($nothingimported) {
        err('nothingtoimport');//kills execution
    }
    //send HTTP headers
    $workbook->send($downloadfilename);
    //close the workbook & we're done!
    $workbook->close();//could be a worsheet with only headings in it - that's ok though...
    exit;
}

//check for POST submission
$receivedfile = isset($_FILES['id_csvfile']);//we could radomize this field with user data, I suppose
$importattempted = false;
if($_SERVER['REQUEST_METHOD']=='POST') {//try to do the import with the posted data
    //parse our csv
    //$filename = $CFG->dataroot.'/temp/gradeimport/cvs/'.$USER->id.'/'.$importcode;
    //$fp = fopen($filename, "r");
    //$header = split($csv_delimiter, fgets($fp,GRADE_CSV_LINE_LENGTH), PARAM_RAW);
    $csvdata = array();
    //line endings differ on different os
    $auto_detect_line_endings = ini_get('auto_detect_line_endings');
    ini_set('auto_detect_line_endings', true);
    $handle = fopen($_FILES['id_csvfile']['tmp_name'], "r");
    //note: define('GRADE_CSV_LINE_LENGTH', 4096) in grade, so used same value here (seems quite high - no probs?)
    while (($rowdata = fgetcsv($handle, 4096, isset($CFG->CSV_DELIMITER) ? isset($CFG->CSV_DELIMITER) : ",")) !== false) {
        $csvdata[] = $rowdata;
    }
    fclose($handle);
    if(!$auto_detect_line_endings) {
        ini_set('auto_detect_line_endings', false);//restore it to former value
    }
    //check our array structure to verify parsing went reasonably well
    $recordcount = count($csvdata);
    if(
        $recordcount < 2 ||
        count($csvdata[0]) != 4 ||
        count($csvdata[1]) != 4 ||
        $csvdata[0][$col_realreference] != "realreference" ||
        $csvdata[0][$col_title]         != "title"         ||
        $csvdata[0][$col_description]   != "description"   ||
        $csvdata[0][$col_keywords]      != "keywords"
    ) {
        err('csvreaderror');
    }
    $countexists = 0;
    $countnew = 0;
    $countfail = 0;
    $resource = new object();
    $importattempted = true;
    $results = array();
    for($i=1; $i < $recordcount; $i++) {
        //check for exactly 4 fields
        if(count($csvdata[1])!=4) {
            $countfail++;
            continue;//invalid record
        }
        //check if exists
        if(!record_exists('resource_mrcutejr', 'realreference', $csvdata[$i][$col_realreference])) {
            //compile our data
            $resource->realreference = trim($csvdata[$i][$col_realreference]);
            $resource->title         = trim($csvdata[$i][$col_title]);
            $resource->description   = trim($csvdata[$i][$col_description]);
            $resource->keywords      = trim(str_replace(array("\r\n","\n","\r"),',',$csvdata[$i][$col_keywords]));
            // nkowald - 2010-11-03 - If it's a streaming video we need to set isfile to 0
            if (strstr($resource->realreference, 'index.php?video=')) {
                $resource->isfile        = 0;
            } else {
                $resource->isfile        = 1;
            }
            $resource->modifieddate  = time(); //unix timestamp
            $resource->reference = basename($resource->realreference);
            //ensure our reference field never exceeds db field of 50 chars
            if(strlen($resource->reference)>50) {//db field is 50 in total
                $resource->reference = substr($resource->reference, strlen($resource->reference)-50);
            }
            //ensure we have data in all fields
            $havedata = !empty($resource->realreference) &&
                        !empty($resource->title) &&
                        !empty($resource->description) &&
                        !empty($resource->keywords);
            //insert
            if($havedata && insert_record("resource_mrcutejr", $resource)) {
                $countnew++;
            } else {
                $countfail++;
                $results['faildbwrite'][$i+1] = $resource->realreference;
            }
        } else {
            $countexists++;
            $results['recordexists'][$i+1] = $csvdata[$i][$col_realreference];
        }
    }
}

//create our link to allow the user to download the xls file
$downloadxlshref = $CFG->wwwroot .
    '/mod/resource/type/mrcutejr/import.php?getunregisteredfilesxls=' .
    urlencode($USER->username.$USER->id);

//get lang strings
$strtitle          = lang('importcsvheader');
$strxlsexplanation = lang('xlsexplanation', '<a href="'.$downloadxlshref.'">mrcute.xls</a>');

//send output to the browser
$CFG->stylesheets[] = 'mrcutejr.css';
require_js('mrcutejr.js');//used to move data from popup to parent window
print_header($strtitle,'','','','',false);//titled window, no cache - to show theme header set title
if($importattempted) {
    //display a little feedback on import results
    $strimporttitle = lang('importresults');
    echo '<h1 class="mrcutejr mrcutejrimport">'.$strimporttitle.'</h1>' .
        '<p class="mrcutejr mrcutejrimport">'.lang('successfullyimported', $countnew).'</p>' .
        '<p class="mrcutejr mrcutejrimport">'.lang('skippedexistingrecords', $countexists).'</p>' .
        '<p class="mrcutejr mrcutejrimport">'.lang('failedtoimportrecords', $countfail).'</p>';
    if(!empty($results)) {
        echo '<table class="mrcutejr mrcutejrimport"><tr><td>' .
                    $strimporttitle . '</td><td>#</td><td>realreference</td></tr>';
        if(!empty($results['faildbwrite'])) {
            $strerrorfailedwrite = lang('errorfailedwrite');
            foreach($results['faildbwrite'] as $k=>$v) {
                echo "<tr><td>".$strerrorfailedwrite."</td><td>$k</td><td>$v</td></tr>";
            }
        }
        if(!empty($results['recordexists'])) {
            $strerrorrecordexists = lang('errorrecordexists');
            foreach($results['recordexists'] as $k=>$v) {
                echo "<tr><td>".$strerrorrecordexists."</td><td>$k</td><td>$v</td></tr>";
            }
        }
        echo "</table>";
    }
} else {
    echo '<h1 class="mrcutejr mrcutejrimport">'.$strtitle.'</h1>';
    //show our download xls link
    echo '<p class="mrcutejr mrcutejrimport text">'.$strxlsexplanation.'</p>';
    //show the import csv form
    require_once('forms/form_import_csv_mrcutejr.php');
    $mform = new form_import_csv_mrcutejr(null, null, 'post', '', array('enctype'=>"multipart/form-data"));
    $mform->display();
}
echo '<p class="mrcutejr mrcutejrimport"><a href="javascript:window.close()">' . get_string('closewindow').'</a></p>';
print_footer('empty');//basic footer - to show the current theme footer html, remove the 'empty' string

?>
