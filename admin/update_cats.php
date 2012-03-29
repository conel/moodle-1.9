<?php

    require_once('../config.php');

    require_login(); 

    $sitecontext = get_context_instance(CONTEXT_SYSTEM);
    if (has_capability('mod/data:viewsitestats',$sitecontext) || has_capability('moodle/site:doanything',$sitecontext)) {  // are we god ?
        $access_isgod = 1 ;
    } else {
		error('You do not have permission to view this page', $CFG->wwwroot);
	}
        
	// Get all category ids
	$query = "SELECT id FROM mdl_course_categories";
	$cat_ids = get_records_sql($query);
	
	$cids = array();
	foreach ($cat_ids as $id) {
		$cids[] = $id->id;
	}
	
	foreach($cids as $cid) {
		fix_course_sortorder($cid);
	}
	
	echo 'updated!';
	
?>