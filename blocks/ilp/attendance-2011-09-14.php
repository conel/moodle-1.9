<?php

	/*
	 * @copyright &copy; 2007 University of London Computer Centre
	 * @author http://www.ulcc.ac.uk, http://moodle.ulcc.ac.uk
	 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
	 * @package ILP
	 * @version 1.0
	 */

	//  Lists the student info texts relevant to the student.
	//  with links to edit for those who can. 

 
	require_once('../../config.php');
	require_once('block_ilp_lib.php');
	include('access_context.php');

	require_once($CFG->dirroot.'/blocks/ilp/templates/custom/dbconnect.php');
	require_once($CFG->dirroot.'/blocks/lpr/models/block_lpr_conel_mis_db.php'); // include the connection code for CONEL's MIS db
	$conel_db = new block_lpr_conel_mis_db();

	global $GFG, $USER;

	$contextid    	= optional_param('contextid', 0, PARAM_INT);               // one of this or
	$courseid     	= optional_param('courseid', SITEID, PARAM_INT);          // this are required
	$group 			= optional_param('group', -1, PARAM_INT);
	$updatepref 	= optional_param('updatepref', -1, PARAM_INT);
	$userid 		= optional_param('userid', 0, PARAM_INT);
	$user 			= get_record('user', 'id',$userid);

	//$coursecontext ;
	if ($contextid) {
		if (! $coursecontext = get_context_instance_by_id($contextid)) {
			error("Context ID is incorrect");
		}
		if (! $course = get_record('course', 'id', $coursecontext->instanceid)) {
			error("Course ID is incorrect");
		}

	} else if ($courseid) {
		if (! $course = get_record('course', 'id', $courseid)) {
			error("Course ID is incorrect");
		}
		if (! $coursecontext = get_context_instance(CONTEXT_COURSE, $course->id)) {
			error("Context ID is incorrect");
		}
	}
	// nkowald - 2010-09-27 - Updated this call as "id" does not hold courseid, seems course does
	//if (!$cm = get_record("course_modules", "id", $courseid)) {
	if (!$cm = get_record("course_modules", "course", $courseid)) {
		error("Course Module ID was incorrect");
	}

	require_login($course);
	$sitecontext = get_context_instance(CONTEXT_SYSTEM);

	if (has_capability('moodle/site:doanything',$sitecontext)) {  // are we god ?
		$access_isgod = 1 ;
	}
	if (has_capability('block/ilp:viewclass',$coursecontext)) { // are we the teacher on the course ?
		$access_isteacher = 1 ;
	}
	
	$strilp = get_string("ilp", "block_ilp");
	$navigation = "<a href=\"../../course/view.php?id=$course->id\">$course->shortname</a> -> <a href=\"../../blocks/ilp/view.php?courseid=$course->id&amp;id=$user->id\">$strilp</a>";		
	print_header("Attendance: ".fullname($user)."", "$course->fullname",
		 "$navigation -> Attendance -> ".fullname($user)."", 
		  "", "", true, update_module_button($cm->id, $course->id, $strtarget), 
		  navmenu($course, $cm));
	
	$page    	= optional_param('page', 0, PARAM_INT);
	$groupmode    = groups_get_course_groupmode($course);   // Groups are being used
	$currentgroup = groups_get_course_group($course, true);

	if (!$currentgroup) {      // To make some other functions work better later
		$currentgroup  = NULL;
	}

	$isseparategroups = ($course->groupmode == SEPARATEGROUPS and $course->groupmodeforce and !has_capability('moodle/site:accessallgroups', $context));	
	$doanythingroles = get_roles_with_capability('moodle/site:doanything', CAP_ALLOW, $sitecontext);

	$sql = "SELECT MODULE_CODE FROM FES.MOODLE_ATTENDANCE_PUNCTUALITY WHERE STUDENT_ID = '".$user->idnumber."' AND ACADEMIC_YEAR = '$academicYear3' GROUP BY MODULE_CODE";
	
	// $sql = "select * from FES.MOODLE_PEOPLE where STUDENT_ID = 304056"; - works

	if ($attendance = $mis->Execute($sql)) {

	echo '<div class="generalbox" id="ilp-attendance-overview">';
	echo '<h1>Attendance</h1><br />';

	echo '<table border="1" class="generalbox" style="text-align: left;">';
	echo '<tr>';
	echo '<th scope="col" colspan="2">Module</th>';
	echo '<th scope="col">Description</th>';
	echo '<th scope="col">Attendance (%)</th>';
	echo '<th scope="col">Sessions Present</th>';
	echo '<th scope="col">Sessions Absent</th>';
	echo '<th scope="col">Punctuality (%)</th>';
	echo '<th scope="col">Sessions On time</th>';
	echo '<th scope="col">Sessions Late</th>';
	echo '</tr>';
	
	while (!$attendance->EOF) {

		$coursedesc =   $attendance->fields["MODULE_CODE"];
		$sql = "SELECT SUM(MARKS_PRESENT) AS MARKS_PRESENT, 
					SUM(MARKS_TOTAL) AS MARKS_TOTAL, 
					SUM(MARKS_TOTAL)-SUM(MARKS_PRESENT) AS MARKS_ABSENT,
					SUM(PUNCT_POSITIVE) AS PUNCT_POSITIVE,
					SUM(MARKS_PRESENT)-SUM(PUNCT_POSITIVE) AS PUNCT_NEGATIVE
				FROM 
					FES.MOODLE_ATTENDANCE_PUNCTUALITY 
				WHERE 
					STUDENT_ID = '".$user->username."' and 
					MODULE_CODE = '$coursedesc' AND 
					MARKS_TOTAL > 0";
		
		$course_att = $mis->Execute($sql);
		$totalcourse = ($course_att->fields["MARKS_PRESENT"]/$course_att->fields["MARKS_TOTAL"])*100;

		$title =$mis->Execute(
		"select MODULE_CODE, 
				MODULE_DESC 
			from 
				FES.MOODLE_ATTENDANCE_PUNCTUALITY 
			where 
				STUDENT_ID = '".$user->idnumber."' and 
				MODULE_CODE = '$coursedesc'");
		//$monthstats = $mis->Execute("select course_code, mth, MARKS_TOTAL, MARKS_PRESENT,(MARKS_PRESENT/MARKS_TOTAL) * 100 as month_attendance from FES.MOODLE_ATTENDANCE_PUNCTUALITY where STUDENT_ID = '".$user->username."' and COURSE_CODE = '$coursedesc' order by MonthOrder ASC");
		//$coursecode = trim($monthstats->fields["course_code"]);

	echo '<tr>';
	
	/*
	if(round($totalcourse,0) >= 93) {
		echo '<td class="attendance-green">&nbsp;</td>';
	}elseif(round($totalcourse,0) >= 90 && round($totalcourse,0) < 93){
		echo '<td class="attendance-amber">&nbsp;</td>';
	}elseif(round($totalcourse,0) <= 90) {
		echo '<td class="attendance-red">&nbsp;</td>';
	}else{
		echo '<td class="attendance">&nbsp;</td>';
	}
	*/
	// nkowald - 2011-03-16 - Changed calculations from Scott
	if(round($totalcourse,0) >= 91) {
		echo '<td class="attendance-green">&nbsp;</td>';
	} elseif (round($totalcourse,0) >= 84 && round($totalcourse,0) < 91){
		echo '<td class="attendance-amber">&nbsp;</td>';
	} elseif (round($totalcourse,0) <= 84) {
		echo '<td class="attendance-red">&nbsp;</td>';
	} else {
		echo '<td class="attendance">&nbsp;</td>';
	}

	echo '<td>'.$title->fields['MODULE_CODE'].'</td>';
	echo '<td>'.$title->fields['MODULE_DESC'].'</td>';
	echo '<td>'.round($totalcourse,0).'%'.'</td>';
	echo '<td>'.$course_att->fields["MARKS_PRESENT"].'</td>';
	echo '<td>'.$course_att->fields["MARKS_ABSENT"].'</td>';
	//echo '<td>'.$course_att->fields["MARKS_TOTAL"].'</td>';
	echo '<td>'.round(($course_att->fields["PUNCT_POSITIVE"]/$course_att->fields["MARKS_PRESENT"])*100,0).'%'.'</td>';
	echo '<td>'.$course_att->fields["PUNCT_POSITIVE"].'</td>';
	echo '<td>'.$course_att->fields["PUNCT_NEGATIVE"].'</td>';
	/*
		if (!$monthstats->EOF) {

		echo '<td>';
		if ($monthstats->fields["mth"] == 9) {
			link_to_popup_window("/blocks/ilp/templates/custom/monthly_attendance.php?userid=$user->id&amp;course=$coursecode&amp;month=9", $name='Sep', round($monthstats->fields["month_attendance"],0).'%',$height=550, $width=750, $title='Attendance',$options='none', $return=false);
		}
		$monthstats->moveNext();
		echo '</td>';
		echo '<td>';
		if ($monthstats->fields["mth"] == 10) {
			link_to_popup_window("/blocks/ilp/templates/custom/monthly_attendance.php?userid=$user->id&amp;course=$coursecode&amp;month=10", $name='Sep', round($monthstats->fields["month_attendance"],0).'%',$height=550, $width=750, $title='Attendance',$options='none', $return=false);
		}
		$monthstats->moveNext();
		echo '</td>';
		echo '<td>';
		if ($monthstats->fields["mth"] == 11) {
			link_to_popup_window("/blocks/ilp/templates/custom/monthly_attendance.php?userid=$user->id&amp;course=$coursecode&amp;month=11", $name='Sep', round($monthstats->fields["month_attendance"],0).'%',$height=550, $width=750, $title='Attendance',$options='none', $return=false);
		}
		$monthstats->moveNext();
		echo '</td>';
		echo '<td>';
		if ($monthstats->fields["mth"] == 12) {
			link_to_popup_window("/blocks/ilp/templates/custom/monthly_attendance.php?userid=$user->id&amp;course=$coursecode&amp;month=12", $name='Sep', round($monthstats->fields["month_attendance"],0).'%',$height=550, $width=750, $title='Attendance',$options='none', $return=false);
		}
		$monthstats->moveNext();
		echo '</td>';
		echo '<td>';
		if ($monthstats->fields["mth"] == 1) {
			link_to_popup_window("/blocks/ilp/templates/custom/monthly_attendance.php?userid=$user->id&amp;course=$coursecode&amp;month=01", $name='Sep', round($monthstats->fields["month_attendance"],0).'%',$height=550, $width=750, $title='Attendance',$options='none', $return=false);
		}
		$monthstats->moveNext();
		echo '</td>';
		echo '<td>';
		if ($monthstats->fields["mth"] == 2) {
			link_to_popup_window("/blocks/ilp/templates/custom/monthly_attendance.php?userid=$user->id&amp;course=$coursecode&amp;month=02", $name='Sep', round($monthstats->fields["month_attendance"],0).'%',$height=550, $width=750, $title='Attendance',$options='none', $return=false);
		}
		$monthstats->moveNext();
		echo '</td>';
		echo '<td>';
		if ($monthstats->fields["mth"] == 3) {
			link_to_popup_window("/blocks/ilp/templates/custom/monthly_attendance.php?userid=$user->id&amp;course=$coursecode&amp;month=03", $name='Sep', round($monthstats->fields["month_attendance"],0).'%',$height=550, $width=750, $title='Attendance',$options='none', $return=false);
		}
		$monthstats->moveNext();
		echo '</td>';
		echo '<td>';
		if ($monthstats->fields["mth"] == 4) {
			link_to_popup_window("/blocks/ilp/templates/custom/monthly_attendance.php?userid=$user->id&amp;course=$coursecode&amp;month=04", $name='Sep', round($monthstats->fields["month_attendance"],0).'%',$height=550, $width=750, $title='Attendance',$options='none', $return=false);
		}
		$monthstats->moveNext();
		echo '</td>';
		echo '<td>';
		if ($monthstats->fields["mth"] == 5) {
			link_to_popup_window("/blocks/ilp/templates/custom/monthly_attendance.php?userid=$user->id&amp;course=$coursecode&amp;month=05", $name='Sep', round($monthstats->fields["month_attendance"],0).'%',$height=550, $width=750, $title='Attendance',$options='none', $return=false);
		}
		$monthstats->moveNext();
		echo '</td>';
		echo '<td>';
		if ($monthstats->fields["mth"] == 6) {
			link_to_popup_window("/blocks/ilp/templates/custom/monthly_attendance.php?userid=$user->id&amp;course=$coursecode&amp;month=06", $name='Sep', round($monthstats->fields["month_attendance"],0).'%',$height=550, $width=750, $title='Attendance',$options='none', $return=false);
		}
		$monthstats->moveNext();
		echo '</td>';
		echo '<td>';
		if ($monthstats->fields["mth"] == 7) {
			link_to_popup_window("/blocks/ilp/templates/custom/monthly_attendance.php?userid=$user->id&amp;course=$coursecode&amp;month=07", $name='Sep', round($monthstats->fields["month_attendance"],0).'%',$height=550, $width=750, $title='Attendance',$options='none', $return=false);
		}
		$monthstats->moveNext();
		echo '</td>';
		echo '</tr>';
	}*/
		$attendance->moveNext();
	}
	}

	echo '</table></div>';


    print_footer($course);
?>