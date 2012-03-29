<?php

      // This script returns image from the dataroot directory with
      // a superimposed bull's eye for the image click question type.
      //
      // With the coordinates 0,0, the image is returned without the bull's eye.
      //
      // It is adapted from /file.php
      //
      // Syntax:      imagewithtarget.php/courseid/dir/dir/dir/filename.ext/x/y

    require_once('../../../config.php');
    require_once('../../../lib/filelib.php');

    if (empty($CFG->filelifetime)) {
        $lifetime = 86400;     // Seconds for files to remain in caches
    } else {
        $lifetime = $CFG->filelifetime;
    }

    // disable moodle specific debug messages
    disable_debugging();

    $relativepath = get_file_argument('file.php');
    $forcedownload = 0;

    // relative path must start with '/', because of backup/restore!!!
    if (!$relativepath) {
        error('No valid arguments supplied or incorrect server configuration');
    } else if ($relativepath{0} != '/') {
        error('No valid arguments supplied, path does not start with slash!');
    }

    // extract relative path components
    $args = explode('/', trim($relativepath, '/'));
    if (count($args) == 0) { // always at least courseid, may search for index.html in course root
        error('No valid arguments supplied');
    }

    $yoffset = array_pop($args);
    $xoffset = array_pop($args);

    $relativepath = implode('/', $args);
    $pathname = $CFG->dataroot.'/'.$relativepath;

    if (!is_numeric($xoffset) or !is_numeric($yoffset)) {
        error('Invalid offset');
    }

    // only allow course directories
    if (!is_numeric($args[0])) {
        error('Invalid course ID');
    }

    // security: limit access to existing course subdirectories
    if (!$course = get_record_sql("SELECT * FROM {$CFG->prefix}course WHERE id='".(int)$args[0]."'")) {
        error('Invalid course ID');
    }

    // security: prevent access to "000" or "1 something" directories
    // hack for blogs, needs proper security check too
    if ($args[0] != $course->id) {
        error('Invalid course ID');
    }

    // security: login to course if necessary
    if ($course->id != SITEID) {
        require_login($course->id);
    } else if ($CFG->forcelogin) {
        if (!empty($CFG->sitepolicy)
            and ($CFG->sitepolicy == $CFG->wwwroot.'/file.php'.$relativepath
                 or $CFG->sitepolicy == $CFG->wwwroot.'/file.php?file='.$relativepath)) {
            //do not require login for policy file
        } else {
            require_login();
        }
    }

    // security: only editing teachers can access backups
    if ((count($args) >= 2) and (strtolower($args[1]) == 'backupdata')) {
        if (!has_capability('moodle/site:backup', get_context_instance(CONTEXT_COURSE, $course->id))) {
            error('Access not allowed');
        } else {
            $lifetime = 0; //disable browser caching for backups
        }
    }

    if (is_dir($pathname)) {
        if (file_exists($pathname.'/index.html')) {
            $pathname = rtrim($pathname, '/').'/index.html';
            $args[] = 'index.html';
        } else if (file_exists($pathname.'/index.htm')) {
            $pathname = rtrim($pathname, '/').'/index.htm';
            $args[] = 'index.htm';
        } else if (file_exists($pathname.'/Default.htm')) {
            $pathname = rtrim($pathname, '/').'/Default.htm';
            $args[] = 'Default.htm';
        } else {
            // security: do not return directory node!
            not_found($course->id);
        }
    }

    // security: some protection of hidden resource files
    // warning: it may break backwards compatibility
    if ((!empty($CFG->preventaccesstohiddenfiles))
        and (count($args) >= 2)
        and (!(strtolower($args[1]) == 'moddata' and strtolower($args[2]) != 'resource')) // do not block files from other modules!
        and (!has_capability('moodle/course:viewhiddenactivities', get_context_instance(CONTEXT_COURSE, $course->id)))) {

        $rargs = $args;
        array_shift($rargs);
        $reference = implode('/', $rargs);

        $sql = "SELECT COUNT(r.id) " .
                 "FROM {$CFG->prefix}resource r, " .
                      "{$CFG->prefix}course_modules cm, " .
                      "{$CFG->prefix}modules m " .
                 "WHERE r.course    = '{$course->id}' " .
                   "AND m.name      = 'resource' " .
                   "AND cm.module   = m.id " .
                   "AND cm.instance = r.id " .
                   "AND cm.visible  = 0 " .
                   "AND r.type      = 'file' " .
                   "AND r.reference = '{$reference}'";
        if (count_records_sql($sql)) {
           error('Access not allowed');
        }
    }

    // check that file exists
    if (!file_exists($pathname)) {
        not_found($course->id);
    }

    // ========================================
    // output the image
    // ========================================
    session_write_close(); // unlock session during fileserving
    $filename = $args[count($args)-1];

    // determine the mimetype of the image
    $mimetype = mimeinfo('type', $filename);

    $mimetypeparts = explode('/', $mimetype);
    $imagetype = $mimetypeparts[1];


    if (function_exists('imagecreatefrom'.$imagetype) and $xoffset > 0 and $yoffset > 0) {
        // get the image
        $imagefunction = 'imagecreatefrom'.$imagetype;
        $qimage = $imagefunction($pathname);

        // get the bull's eye and info
        $beimage = imagecreatefrompng('bullseye.png');
        $bex = imagesx($beimage);
        $bey = imagesy($beimage);
        $bexoffset = (int) (imagesx($beimage) / 2);
        $beyoffset = (int) (imagesy($beimage) / 2);

        // superimpose the bull's eye
        imagecopy($qimage, $beimage, $xoffset - $bexoffset, $yoffset - $beyoffset, 0, 0, $bex, $bey);

        // display the new image as a jpeg
        header('content-type: image/jpeg');
        imagejpeg($qimage);

        // delete images
        imagedestroy($qimage);
        imagedestroy($beimage);
    } else {
        // on failure, send the original file
        send_file($pathname, $filename, $lifetime, $CFG->filteruploadedfiles, false, $forcedownload);
    }

    function not_found($courseid) {
        global $CFG;
        header('HTTP/1.0 404 not found');
        error(get_string('filenotfound', 'error'), $CFG->wwwroot.'/course/view.php?id='.$courseid); //this is not displayed on IIS??
    }
?>
