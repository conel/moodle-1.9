<?php 
	
    require_once(dirname(__FILE__) . '/config.php');
	
	//$db->debug = true;

	$c = optional_param('c', null, PARAM_SAFEDIR);
			
	$course = array_pop(get_records_select("course", "shortname='$c'", '', 'id', 0, 1));
    
    if(!empty($course)) {
		header('Location: https://vle.conel.ac.uk/course/view.php?id=' . $course->id);
	} else {
		echo 'Course not exists.';
	}
    
    //get_mailer('close');
    
?> 
