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

//drop menu text
$string['resourcetypemrcutejr'] = "MrCUTE Jr. shared file or website";

//resource.class.php & forms/form_search_mrcutejr.php
$string['searchbutton']  = 'MrCUTE Jr. Repository Search';
$string['newurlbutton']  = 'New Shared URL';
$string['newfilebutton'] = 'New Shared File';
$string['searchheader']  = 'MrCUTE <em>Jr.</em> Repository Search';

//new.php
$string['newurlformtitle']  = 'MrCUTE <em>Jr.</em> - '.$string['newurlbutton'];
$string['newfileformtitle'] = 'MrCUTE <em>Jr.</em> - '.$string['newfilebutton'];

//forms/form_new_mrcutejr.php
$string['saveurl']              = 'Save '.$string['newurlbutton'];
$string['savefile']             = 'Save '.$string['newfilebutton'];
$string['newurl']               = $string['newurlbutton'];
$string['newfile']              = $string['newfilebutton'];
$string['selecturltype']        = 'Icon';//user selects "type" - affects icon used
$string['directory']            = 'Directory';
$string['defaultdirectory']     = 'miscellaneous';//make safe for directory name! (e.g. lowercase "0-9a-zA-Z_-")
$string['title']                = 'Title';
$string['description']          = 'Description';
$string['keywords']             = 'Keywords';

//forms/form_edit_mrcutejr.php
$string['editurlformtitle'] = 'MrCUTE <em>Jr.</em> - Edit Shared URL Resource';
$string['editfileformtitle']= 'MrCUTE <em>Jr.</em> - Edit Shared File Resource';
$string['update']           = 'Update';
$string['editingfile']      = 'Editing details for file';
$string['replacefile']      = 'replace file';

//edit.php
$string['modifieddate'] = 'Modified';

//search.php & forms/form_search_mrcutejr.php
$string['searchtip']     = "Search for '\$a' to find everything.";
$string['type']          = 'Type';
$string['resultsheader'] = "Results for: '\$a'";
$string['noresults']     = "No search results; please try a different search term or phrase.";
$string['searchbtn']     = 'MrCUTE Jr. Search';//like "Google Search"
$string['choose']        = 'choose';
$string['preview']       = 'preview';
$string['edit']          = 'edit';
$string['delete']        = 'delete';

//import.php
$string['importcsvheader']        = 'MrCUTE <em>Jr.</em> Repository Import Unregistered Files';
$string['importcsvformheader']    = 'Import Data Form';
$string['importcsvbutton']        = 'Import from CSV now!';
$string['selectcsvfile']          = 'CSV File';
$string['nothingtoimport']        = 'There are no unregistered files in the repository remaining to be imported.';
$string['xlsexplanation']         = "Please download \$a and use it to create a suitable csv file for re-import.";
$string['csvreaderror']           = "There was an error parsing the CSV file.";
$string['importresults']          = "Import Results";
$string['successfullyimported']   = "Successfully imported \$a records.";
$string['skippedexistingrecords'] = "There were \$a skipped records because they already existed.";
$string['failedtoimportrecords']  = "There were \$a records that failed to be written to the database.";
$string['errorfailedwrite']       = "failed write to database";
$string['errorrecordexists']      = "record exists";

//validation
$string['mrcutejr_fileexists'] = 
    "The file '\$a' already exists; please use the existing shared file. If the new file is really different than " .
    "the existing one, then perhaps rename your version. Hints: if you searched for the resource but couldn't find " .
    "it, try typing the filename into the search box. Besides this, if the filename is highly desirable, perhaps " .
    "the file should be in a different directory" ;

$string['mrcutejr_requireallfields'] =
    'Please fill in all the fields; they are all required.';

$string['mrcutejr_failedusercheck'] = 
    "Failed to get user details; you are probably not authorized; please talk to your admin to get access.";

$string['mrcutejr_couldnotsaveto'] =
    "Could not save file to '\$a'.";

$string['mrcutejr_nofileorurl'] =
    "No file or url was specified.";

$string['mrcutejr_failedwritetodatabase'] = 
    "Failed to add the shared URL or File to the database table (sorry, the reason is uknown - perhaps the Moodle " .
    "logs might reveal some details).";

$string['mrcutejr_duplicaterecorderror'] = 
    "Failed to write do the database because there is already an identical \$a in the repository; please close this " .
    "window and use the search button instead. Hint: try typing the \$a into the search box.";

$string['mrcutejr_nosuchid'] = "Error: no such id; has the mrcutejr database table been edited/deleted?";

$string['mrcutejr_errorretrievingrecord'] = 
    "Error trying to retrieve the mrcutejr record; has the mrcutejr database table been edited/deleted?";

$string['mrcutejr_nosearchterm'] =
    "Nothing of substance was specified in the search field.";

$string['mrcutejr_noeditrecordid'] =
    "No edit record ID was provided.";

$string['mrcutejr_filedoesnotexist'] =
    "File does not exist.";

$string['mrcutejr_couldnotcreaterepositordirectory'] =
    "The repository directory does not exist and we could not create it; please create the directory and ensure " .
    "moodle php scripts have read/write access to it.";

?>