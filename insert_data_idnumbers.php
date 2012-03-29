<?php
	
	require_once('config.php');

	echo '<b>Getting id numbers for 1011...</b><br />';
    $query = "SELECT id, idnumber FROM mdl_course WHERE idnumber != '' ORDER BY id ASC";
    $ids = get_records_sql($query);

    $id_data = array();
    foreach ($ids as $id) {
        $id_data[$id->id] = $id->idnumber;
    }

    /*
    echo '<pre>';
    var_dump($id_data);
    echo '</pre>';
    exit;
    */

    // Update 1011 idnumbers with what is in course id if different.
    foreach($id_data as $key => $value) {
        // Get record from mdl_course_idnumber_archive table
        if ($archive = get_record('course_idnumber_archive', 'course_id', $key)) {
            // record found 
            if ($archive->year_1011 != $value) {
                $archive->year_1011 = $value;
                // update archive table
                if (!update_record('course_idnumber_archive', $archive)) {
					echo 'could not update record ' . $key;
				}
            }
        } else {
            // Course must not exist in table, add it
            $query = "INSERT INTO mdl_course_idnumber_archive VALUES(0, $key, NULL, NULL, '$value')";
            if (!execute_sql($query, false)) {
				echo 'could not insert row into mdl_course_idnumber_archive with mdl_course id = ' . $key;
			}
        }
        
        // insert data
    }
	
    print_heading('Done!');
	echo '<b>Successfully finished inserting course ids!</b><br />';
	
?>
