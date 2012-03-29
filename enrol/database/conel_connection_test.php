<?php

/***
a. ebs.conel.ac.uk (host server name)
b. fs1 (Oracle service name - SID)
c. ebsmoodle
d. 82814710
e. FES.CURRENT_ENROLMENTS (a view in schema FES)
MOODLE_PEOPLE
MOODLE_CURRENT_ENROLMENTS
***/

    require_once('../../config.php');
    global $CFG, $USER;

    print_header($SITE->fullname, $SITE->fullname, 'home', '','', true, '', user_login_string($SITE));

    require_login();
    require_capability('moodle/site:config', get_context_instance(CONTEXT_SYSTEM));

    require_once ($CFG->dirroot.'/lib/adodb/adodb.inc.php');

    //$mis = NewADOConnection('oci8');
    $mis = &ADONewConnection('oci8');
    //$mis->debug=true;
    //$mis->NLS_DATE_FORMAT =  'DD-MON-YYYY';

    //print_heading("CONEL MIS Connection");
    $mis->Connect('ebs.conel.ac.uk', 'ebsmoodle', '82814710', 'fs1');

    //print_heading("CONEL MIS Response");
    $mis->SetFetchMode(ADODB_FETCH_ASSOC);

    $result2 = $mis->SelectLimit("SELECT * FROM FES.MOODLE_CURRENT_ENROLMENTS",10,0);
	$course->idnumber = 'NV2MHRA5_8EA21A';
	$learner->idnumber = '306437';
	/*$result2= $mis->Execute("SELECT AVG(MARKS_PRESENT/MARKS_TOTAL) AS attendance, AVG(ATT_POSITIVE/PUNCT_TOTAL) AS punctuality FROM FES.MOODLE_ATTENDANCE_PUNCTUALITY WHERE COURSE_CODE = '{$course->idnumber}' AND STUDENT_ID = '{$learner->idnumber}'");
	//$result2= $mis->Execute("SELECT AVG(MARKS_PRESENT/MARKS_TOTAL) AS attendance, AVG(ATT_POSITIVE/PUNCT_TOTAL) AS punctuality FROM FES.MOODLE_ATTENDANCE_PUNCTUALITY WHERE STUDENT_ID = '{$learner->idnumber}' AND ACADEMIC_YEAR = 'Academic Year 2009/2010' AND ");
	//$result2= $mis->Execute("SELECT * FROM FES.MOODLE_ATTENDANCE_PUNCTUALITY WHERE COURSE_CODE = '{$course->idnumber}' AND STUDENT_ID = '{$learner->idnumber}'");
	//$result2= $mis->Execute(
        "SELECT AVG(MARKS_PRESENT/MARKS_TOTAL) AS attendance
             FROM FES.MOODLE_ATTENDANCE_PUNCTUALITY
             WHERE STUDENT_ID = '306437'
               AND COURSE_CODE = 'NV2MHRA5_8EA21A'
               AND MARKS_TOTAL > 0");*/

    if (!$result2) {
        print $mis->ErrorMsg(); // Displays the error message if no results could be returned
    }
    else {
        echo '<table>' ;
        echo '<tr>' ;
        foreach (array_keys($result2->fields) as $key) {
            echo "<th>$key</th>" ;
        }
        echo '</tr>' ;
        while (!$result2->EOF) {
            echo '<tr>' ;
            foreach (array_keys($result2->fields) as $key) {
                echo "<td>".$result2->fields[$key]."</td>" ;
            }
            $result2->MoveNext();
            echo '</tr>' ;
        }
        echo '</table>' ;
    }

    print_footer();
  
?>