<?php

	require_once('../../config.php');

	global $GFG, $USER;
	
	echo 'This script sets all users target grades to not yet set.<br>';
	//exit;

	// Get students
	$query = "SELECT id, idnumber FROM mdl_user WHERE email LIKE ('%student.conel.ac.uk') and auth != 'nologin'";
	if ($students = get_records_sql($query)) {
		$student_ids = array();
		foreach ($students as $stud) {
			$student_ids[] = array($stud->id, $stud->idnumber);
		}
	}

	// Now we've got student ids, lets see if target grades have been set for them this academic year
	$ts_now = time();
	$query = "SELECT ac_year_start_date, ac_year_end_date FROM mdl_academic_years WHERE ac_year_start_date < $ts_now AND ac_year_end_date > $ts_now";
	if ($current_ac_year = get_records_sql($query)) {
		foreach ($current_ac_year as $year) {
			$ts_year_start = $year->ac_year_start_date;
			$ts_year_end = $year->ac_year_end_date;
		}
	}
	
	// For every student, check if a target grade has been added after the start of this academic year, if not: add it as pass
    if (count($student_ids) > 0) {
        // start timer

        $time = microtime();
        $time = explode(' ', $time);
        $time = $time[1] + $time[0];
        $timer_start = $time;

        $updated = 0;
        foreach($student_ids as $id) {
            // Does this student have a target grade that was set this year?
            $query = sprintf("SELECT id FROM mdl_target_grades where mdl_user_id = %d AND live = 1 and date_added > %d and date_added < %d", $id[0], $ts_year_start, $ts_year_end);
            if (!$exists = get_records_sql($query)) {
                // Target grade for this year does NOT exist, lets add it.
                // if there's any grades for this user: update all lives to 0
                if ($found = get_record('target_grades', 'mdl_user_id', $id[0], 'live', 1)) {
                    $found->live = 0;
                    update_record('target_grades', $found);
                }
                // Finally, add a new record for this user with a start grade of pass
                $tgrade = new stdClass();
                $tgrade->id = 0; 
                $tgrade->target_grade_id = 0; //13;
                $tgrade->mdl_user_id = $id[0];
                $tgrade->ebs_user_id = $id[1];
                $tgrade->date_added = time();
                $tgrade->live = 1;
                if ($added = insert_record('target_grades', $tgrade)) {
                    $updated++;
                }
            }
        } // foreach

        echo '<p>'.$updated.' Target grades added</p>';
        $time = microtime();
        $time = explode(' ', $time);
        $time = $time[1] + $time[0];
        $finish = $time;
        $total_time = round(($finish - $timer_start), 4);
        echo '<p>Updating took '.$total_time.' seconds</p>';
    }

?>
