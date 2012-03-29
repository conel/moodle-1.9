<?php

    require_once('../config.php');

    require_login(); 

    $sitecontext = get_context_instance(CONTEXT_SYSTEM);
    if (has_capability('mod/data:viewsitestats',$sitecontext) || has_capability('moodle/site:doanything',$sitecontext)) {  // are we god ?
        $access_isgod = 1 ;
    } else {
		error('You do not have permission to view this page', $CFG->wwwroot);
	}
        
	// Get all course ids
	$query = "SELECT id, shortname, idnumber FROM mdl_course WHERE visible = 1 and idnumber != ''";
	$courses = get_records_sql($query);
	
	$cids = array();
	foreach ($courses as $id) {
		$cids[$id->id] = $id->idnumber;
	}
	
    foreach($cids as $key => $value) {
        if (!record_exists('course_idnumber_archive', 'course_id', $key)) {
            // Insert new record
            $data = new stdClass();
            $data->course_id = $key;
            $data->year_1112 = $value;
            insert_record('course_idnumber_archive', $data, false);
        } else {
            // Record exists, update year_1112 column with idnumber
            // Get existing record
            if ($exists = get_record('course_idnumber_archive', 'course_id', $key)) {
                // Update record with new idnumber
                $exists->year_1112 = $value;
                update_record('course_idnumber_archive', $exists, false);

            } else {
                echo "Can't find record for course $key <br />";
            }
        }
    }

    echo 'Updated mdl_course_idnumber_archive!';
	
?>
