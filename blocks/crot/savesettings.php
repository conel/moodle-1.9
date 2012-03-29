<?php 
    //This script is used to save the settings for thte course.

    require_once ("../../config.php");

	$id = optional_param( 'id' );       // course id
	$to = optional_param( 'to' ); // id of course to import into afterwards.
	$cancel = optional_param( 'cancel' );
	$launch = optional_param( 'launch' );
	$tblAssignments = $CFG->prefix."crot_assignments";
    //If cancel has been selected, go back to course main page (bug 2817)
    if ($cancel) {
        if ($id) {
            $redirecto = $CFG->wwwroot . '/course/view.php?id=' . $id; //Course page
        } else {
            $redirecto = $CFG->wwwroot.'/';
        }
        redirect ($redirecto, get_string('settings_cancelled', "block_crot")); //Site page
        exit;
    }


    //Do save
    if (isset($_REQUEST['assign'])) {
          $assign = $_REQUEST['assign'];
    } else {
        $assign = NULL;
    }
    if (isset($_REQUEST['locals'])) {
          $locals = $_REQUEST['locals'];
    } else {
        $locals = NULL;
    }
    if (isset($_REQUEST['globals'])) {
          $globals = $_REQUEST['globals'];
    } else {
        $globals = NULL;
    } 

	$i=0;
	foreach($assign as $row) {
		//remove record
		delete_records("crot_assignments", "assignment_id", $row);

		if (isset($locals[$i]) or (isset($globals[$i]))){
			//insert record
			if (isset($globals[$i])) {
				$glob=1;	
			} else {
				$glob=0;	
			};
			if (isset($locals[$i])) {
				$loc=1;	
			} else {
				$loc=0;	
			};

			$record->assignment_id = $row;
			$record->is_local = $loc; //isset($locals[$i]);
			$record->is_global = $glob; //isset($globals[$i]);
			$newrecord = insert_record("crot_assignments", addslashes_object($record));

		}
		$i++;
   	}



// redirect after saving settings
        if ($id) {
            $redirecto = $CFG->wwwroot . '/course/view.php?id=' . $id; //Course page
        } else {
            $redirecto = $CFG->wwwroot.'/';
        }
        redirect ($redirecto, get_string('settings_saved', "block_crot")); //Site page

?>
