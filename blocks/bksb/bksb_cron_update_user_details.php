<?php

	if ($_SERVER["REMOTE_ADDR"] != $_SERVER["SERVER_ADDR"]) die("Cron script can't be run directly. It only comes out at night.");
	
    require_once(dirname(dirname(dirname(__FILE__))).'/config.php'); // global moodle config file.
	include_once('BksbReporting.class.php');
	
    global $CFG, $USER;
	
    $bksb = new BksbReporting();

    // Time how long it takes to update and echo the output
    $time = microtime();
    $time = explode(' ', $time);
    $time = $time[1] + $time[0];
    $begintime = $time;

	// First part of cron is to sync Moodle user postcodes and dob with EBS
    require_once($CFG->dirroot.'/blocks/ilp/templates/custom/dbconnect.php'); // include the connection code for CONEL's MIS db
	/*
    $query = "SELECT STUDENT_ID, TO_CHAR(DATE_OF_BIRTH, 'DD/MM/YYYY') AS DOB, POST_CODE FROM FES.MOODLE_PEOPLE WHERE POST_CODE != 'ZZ99 ZZZ'";

    $ebs_users = array();
    if ($users = $mis->Execute($query)) {
        while (!$users->EOF) {
            $ebs_users[] = array('idnumber' => $users->fields['STUDENT_ID'], 'dob' => $users->fields['DOB'], 'postcode' => $users->fields['POST_CODE']);
            $users->moveNext();
        }
    }

    $users_updated = 0;
    foreach ($ebs_users as $user) {
        // Check if user exists in Moodle users table
        if ($exists = get_record('user', 'idnumber', $user['idnumber'])) {
            // Update user record with postcode and dob
            $exists->dob = $user['dob'];
            $exists->postcode = $user['postcode'];
            if (update_record('user', $exists)) {
                $users_updated++;
            }
        }
    }
	*/

	// Second part of cron is to find all invalid BKSB users and then update all matched users
	$invalid_users = $bksb->getInvalidBksbUsers();
	$no_invalids = count($invalid_users);
	
	if ($no_invalids > 0) {
		$bksb->updateInvalidUsers($invalid_users);
	}

    $time = microtime();
    $time = explode(" ", $time);
    $time = $time[1] + $time[0];
    $endtime = $time;
    $totaltime = round(($endtime - $begintime), 2);
    echo '<pre>It took ' .$totaltime. ' seconds to sync users.</pre>';

?>