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
 * This script provides configuration specific to the MrCUTE Jr. files - should not be called elsewhere in Moodle; this
 * script is called by the MrCUTE Jr. files as required.
 */

//prevent direct access to this script for security purposes
if(strpos($_SERVER['REQUEST_URI'], basename(__FILE__))) {
    header("HTTP/1.0 404 Not Found");
    die();
}

//incude moodle config.php
require_once(dirname(__FILE__).'/../../../../config.php');

//ensure slashes on our global settings
//$CFG->block_mrcutejr_repository
if(empty($CFG->block_mrcutejr_repository)) {
    $CFG->block_mrcutejr_repository = $CFG->dataroot.'/mrcutejr';//e.g. /moodledata/mrcutejr
} else {
    // Why the FUCK! are you doing this?
    /*
    if(substr($CFG->block_mrcutejr_repository, 0, 1) != "/") {
        //$CFG->block_mrcutejr_repository = "/".$CFG->block_mrcutejr_repository;              //prefix leading slash "/"
    }
    */
    if(substr($CFG->block_mrcutejr_repository,-1,1)=="/") {
        $CFG->block_mrcutejr_repository = substr($CFG->block_mrcutejr_repository, 0, -1);   //strip trailing slash "/"
    }
}



/* FUNCTIONS ARE SORTED ALPHABETICALLY
 ____________________________________________________________________________________________________________________*/



/**
 * Returns an array of directories and/or files.
 * Source/credit:  http://snippets.dzone.com/posts/show/155  (tweaked version here)
 * @param string $directory - absolute path to directory
 * @param boolean $recursive - should we recurse into sub-directories
 * @param boolean $returntypes - one of "directories", "files", "both"; default is "directories"
 * @return array - may be empty if error (or there is simply no directories/files)!
 */
function directory_to_array($directory, $recursive=false, $returntypes="directories") {
    $array_items = array();
    $handle = opendir($directory);
    if($handle) {
        while(false !== ($file = readdir($handle))) {
            if(substr($file,0,1) != ".") {//NO dot files...
                if(is_dir($directory. "/" . $file)) {
                    if($recursive) {
                        $array_items = array_merge(
                            $array_items,
                            directory_to_array($directory."/".$file, $recursive, $returntypes)
                        );
                    }
                    if($returntypes=="directories" || $returntypes=="both") {
                        //add the directory
                        $file = $directory . "/" . $file;
                        $array_items[] = preg_replace("/\/\//si", "/", $file);
                    }
                } else if(($returntypes=="files" || $returntypes=="both")  && is_file($directory. "/" . $file)) {
                    $array_items[] = $directory. "/" . $file;
                }
            }
        }
        closedir($handle);
    }
    asort($array_items);
    return $array_items;
}

/**
 * Shorthand version of print_error(), specific to the mrcutejr module only.
 * @param String $str the lang variable name for the language string desired
 * @param String $a optional - the string substitution
 * @param String $jsgoback optional, if true then the error pages' "continue" button has a js onclick=...history.go(-1)
 * @return execution stops after this method is called (print_error() behavior)
 */
function err($mrcutejr_lang_term, $a=null, $jsgoback=true) {
    if($jsgoback) {
        print_error(
            $mrcutejr_lang_term,
            'resource_mrcutejr',
            'javascript:history.go(-1)',
            $a
        );
    } else {
        print_error($mrcutejr_lang_term, 'resource_mrcutejr', '', $a);
    }
}

/**
 * Used to prep PHP string to a format safe for inline javascript inside of html.
 * @param object $var the string to be prepped
 * @return urlencoded string with "+" converted back spaces again... (for inline js generated by php - spaces are ok!)
 */
function inline_javascript_encode($var) {
    $var = urlencode(stripslashes(trim($var)));
    return str_replace('+', ' ', $var);//we want to allow "+"
}

// nkowald - 2011-01-26 - Other users need access to this
function is_allowed_user() {
	global $CFG, $USER;
	// allowed roles: Administrator, Manager, Teacher, course creator, E-Learning Technologist
	
	// Get role numbers by name (case insensitive) (ids could change as they did when Scott did it)
	$allowed = FALSE;
	
	$query = "SELECT id FROM mdl_role WHERE name in ('Administrator', 'Manager', 'Teacher', 'course creator', 'E-Learning Technologist')";
	$valid_ids = array();
	if ($allowed_ids = get_records_sql($query)) {
		foreach ($allowed_ids as $id) {
			$valid_ids[] = $id->id;
		}
	}
	
	foreach ($valid_ids as $role) {
		if (record_exists('role_assignments', 'roleid', $role, 'userid', $USER->id)) {
			$allowed = TRUE;
		}
	}
	return $allowed;
}

/**
 * Checks to see if user is admin or if user's username is in the whitelist for allowed editors.
 * @return boolean - true if user is admin or username in whitelist
 */
function is_authorized_mrcutejr_editor() {
    global $CFG, $USER;
    //check if admin
	
    if(is_mrcutejr_admin() || is_allowed_user()) {//calls require_login() & requires accesslib.php for us
        return true;
    }
    //
    //check capability - we don't always have same context + couldn't get my hands on $context or $roleid without more
    //db queries, so did it this way (advice anyone? code samples for site-wide/multi-context implementation?)
    //TODO: I'd like zero setup to be an option, with customizations possible; currently there is no customizations
    //possible with this approach.
    //
    /// If this user is assigned as an editing teacher anywhere then return true
    $roles = get_roles_with_capability('moodle/legacy:editingteacher', CAP_ALLOW);
    if($roles) {
        foreach ($roles as $role) {
            if (record_exists('role_assignments', 'roleid', $role->id, 'userid', $USER->id)) {
                return true;//authorized
            }
        }
    }
}


/**
 * Currently this checks moodle config capability of user to determine "admin" status.
 * @return boolean - true if user has "mrcutejr config" capability
 */
function is_mrcutejr_admin() {
    global $CFG, $USER;
    require_once($CFG->libdir.'/accesslib.php');//for has_capability()
    //require user to be logged in
    if(empty($USER->id)) {
        require_login();
    }
    $usrcontext = get_context_instance(CONTEXT_SYSTEM, SITEID);
    //determine if user is moodle admin by checking site-config capability
    if(has_capability('moodle/site:config', $usrcontext)) {
        return true;//authorized
    }
    return false;
}

/**
 * Shorthand version of get_string(), specific to the mrcutejr module only.
 * @param String $str the lang variable name for the language string desired
 * @param String $a optional - the string substitution
 * @return the MrCUTE Jr. lang string
 */
function lang($mrcutejr_lang_string, $a=null) {
    return get_string($mrcutejr_lang_string,'resource_mrcutejr', $a);
}

/**
 * Sends the passed in file to the browser.
 * @param string $path - absolute path to file including the file name, e.g. "/abs/path/file.pdf"
 */
function mrcutejr_send_file($path) {
    global $CFG;
    if(!empty($path) && file_exists($path)) {
        //let's use moodle code where possible:
        require_once($CFG->libdir."/filelib.php");
        //"borrowed" some code straight out of moodle/file.php here
        if (!isset($CFG->filelifetime)) {
            $lifetime = 86400;              //seconds for files to remain in caches
        } else {
            $lifetime = $CFG->filelifetime;
        }
        $pathiscontentnotpathname = false;  //? see filelib.php around line #648
        $forcedownload = optional_param('forcedownload', 0, PARAM_BOOL);
        send_file(
            $path,
            basename($path),
            $lifetime,
            $CFG->filteruploadedfiles,
            $pathiscontentnotpathname,
            $forcedownload
        );
    } else {
        err('mrcutejr_filedoesnotexist');//give some feedback
    }
}

/**
 * Checks if the current user is authorized to add/edit a resource based on requiring a login and checking the role of
 * user against admin block settings. An assumption is made that the user role would be hard to fake; if you need
 * tighter security, please feel free to modify this method to your needs.
 * @return nothing - shows moodle error screen on fail, with history.go(-1) set on the "continue" button.
 */
function require_authorized_mrcutejr_editor() {
    global $CFG, $USER;
    if(is_authorized_mrcutejr_editor()) {
        return;//OK
    }
    //else FAIL
    print_error(
        'mrcutejr_failedusercheck',
        'resource_mrcutejr',
        'javascript:window.close()'
    );
}

/**
 * Clean filename string to something that should work on various server file systems.
 * Source/Credit: http://forums.codecharge.com/posts.php?post_id=75694
 * @param object $filename
 * @return 
 */
function sanitize_filename($filename, $forceextension=""){
    /*
     * 1. Remove leading and trailing dots
     * 2. Remove dodgy characters from filename, incl. spaces & dots except last.
     * 3. Force extension if specified
     */
    $defaultfilename = "none";
    $accept ="[^0-9a-zA-Z_-]";//allow alphanum,underscore,parentheses and hyphen
    $filename = preg_replace("/^[.]*/","",$filename); // lose any leading dots
    $filename = preg_replace("/[.]*$/","",$filename); // lose any trailing dots
    $filename = $filename?$filename:$defaultfilename; // if filename is blank...
    $lastdotpos=strrpos($filename, "."); // save last dot position
    $filename = preg_replace("/$accept/","_",$filename); //filter to accepted
    $afterdot = "";
    if ($lastdotpos !== false) { // Split into name and extension, if any.
        $beforedot = substr($filename, 0, $lastdotpos);
        if ($lastdotpos < (strlen($filename) - 1)) {
            $afterdot = substr($filename, $lastdotpos + 1);
        }
    } else {
        // no extension
        $beforedot = $filename;
    }
    if ($forceextension) {
        $filename = $beforedot . "." . $forceextension;
    } else if($afterdot) {
        $filename = $beforedot . "." . $afterdot;
    } else {
        $filename = $beforedot;
    }
    return $filename;
}

?>
