<?php

	require_once('../../config.php');
	require_once('block_ilp_lib.php');
	include('access_context.php');

	require_once($CFG->dirroot.'/blocks/ilp/templates/custom/dbconnect.php');
	require_once($CFG->dirroot.'/blocks/lpr/models/block_lpr_conel_mis_db.php'); // include the connection code for CONEL's MIS db
	$conel_db = new block_lpr_conel_mis_db();

    require_once('AttendancePunctuality.class.php');
    $attpunc = new AttendancePunctuality();

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

	//$sql = "SELECT MODULE_CODE FROM FES.MOODLE_ATTENDANCE_PUNCTUALITY WHERE STUDENT_ID = '".$user->idnumber."' AND ACADEMIC_YEAR = '$academicYear3' GROUP BY MODULE_CODE";
	// $sql = "select * from FES.MOODLE_PEOPLE where STUDENT_ID = 304056"; - works

?>
<script type="text/javascript">
jQuery(document).ready(function(){  
    jQuery('#show_key').click(function(event) {
        event.preventDefault();
        if (jQuery('#show_key').html() == 'Show key') {
            jQuery('#show_key').html('Hide key');
        } else {
            jQuery('#show_key').html('Show key');
        }
        jQuery('#register_key').slideToggle();
    });
});
</script>
<?php
	echo '<div class="generalbox" id="ilp-attendance-overview">';
	echo '<h1>Attendance</h1><br />';

	$att_shown = FALSE;

	echo '<a href="#" id="show_key">Show key</a>';
	echo '<div id="register_key">';
	echo '<strong style="font-weight:bold;">Register Marks Key</strong><br />';
	echo '<table id="att_key"><tr>';
	ksort($attpunc->marks_key);
	foreach ($attpunc->marks_key as $key => $value) {
		echo '<th class="key">'. $key . '</th>';
	}
	echo '</tr><tr>';
	foreach ($attpunc->marks_key as $key => $value) {
		echo '<td>'. $value . '</td>';
	}
	echo '</tr>';
	echo '</table>';
	echo '</div>';

for ($i=1; $i <= 1; $i++) {
    
    if ($att_punct = $attpunc->getAttendancePunctuality($user->idnumber, $i)) {

        $att_shown = TRUE;
        $term = $i;
        $reg_weeks = $attpunc->getRegisterWeeks($user->idnumber, $term);
        $no_weeks = count($reg_weeks);
        
        // Get term dates of current term
        $term_dates = $attpunc->getCurrentTermDates();
        $t_start = date('d/m/Y', $term_dates[$i]['start']);
        $t_end = date('d/m/Y', $term_dates[$i]['end']);
        echo "<h2>Term $i: &nbsp; $t_start - $t_end</h2>";

        echo '<table class="attendance">';
        echo '<tr>';
        echo '<th colspan="11">&nbsp;</th>';
        echo '<th colspan="'.$no_weeks.'" class="ws">Week Starting</th>';
        echo '</tr>';
        echo '<tr class="colheaders">';
        echo '<th scope="col" colspan="2">Module - Description</th>';
        echo '<th scope="col">Day</th>';
        echo '<th scope="col">Start</th>';
        echo '<th scope="col">End</th>';
        echo '<th scope="col">Attendance</th>';
        echo '<th scope="col">Present</th>';
        echo '<th scope="col">Absent</th>';
        echo '<th scope="col">Punctuality</th>';
        echo '<th scope="col">On time</th>';
        echo '<th scope="col">Late</th>';
        if ($no_weeks > 0) {
            foreach ($reg_weeks as $date) {
                $form_date = substr($date, 0, 5);
                //echo '<th>'.$form_date.'</th>';
                echo '<th><img src="vertical-date.php?text='.$date.'" width="10" height="45" alt="'.$date.'" /></th>';
            }
        }
        echo '</tr>';

        $c = 1;
        foreach($att_punct as $key => $atp) {
            echo '<tr>';

            $attendance = ($atp['attendance'] != '') ? round($atp['attendance'], 2) * 100 : '';
            $punctuality = ($atp['punctuality'] != '') ? round($atp['punctuality'], 2) * 100 : '';

			$att_class = '';
			
             if ($attendance >= 91) {
                echo '<td class="attendance-green">&nbsp;</td>';
                $att_class = ' green';
            } else if ($attendance >= 84 && $attendance < 91){
                echo '<td class="attendance-amber">&nbsp;</td>';
                $att_class = ' amber';
            } else if ($attendance < 84 && is_numeric($attendance)) {
                echo '<td class="attendance-red">&nbsp;</td>';
                $att_class = ' red';
            } else {
                echo '<td class="attendance">&nbsp;</td>';
            }

            $punc_class = '';
			
			if ($punctuality >= 91) {
				$punc_class = ' green';
            } else if ($punctuality >= 84 && $punctuality < 91){
				$punc_class = ' amber';
            } else if ($punctuality < 84 && is_numeric($punctuality)) {
				$punc_class = ' red';
            }

            $attendance = (is_numeric($attendance)) ? $attendance . "%" : '';
            $punctuality = (is_numeric($punctuality)) ? $punctuality . "%" : '';

            $module_code = $atp['module_code'];

            echo '<td style="white-space:nowrap; "><span style="color:#000;">'.$key.'</span><br />'.$atp['module_desc'].'</td>';
            //$short_day = substr($atp['day'], 0, 3);
            echo '<td class="center">'.$atp['day'].'</td>';
            echo '<td class="center">'.$atp['start_time'].'</td>';
            echo '<td class="center">'.$atp['end_time'].'</td>';
            echo '<td class="center'.$att_class.'">'.$attendance.'</td>';
            echo '<td class="center">'.$atp["sessions_present"].'</td>';
            echo '<td class="center">'.$atp["sessions_absent"].'</td>';
            echo '<td class="center'.$punc_class.'">'.$punctuality.'</td>';
            echo '<td class="center">'.$atp["sessions_on_time"].'</td>';
            echo '<td class="center">'.$atp["sessions_late"].'</td>';

            // Now grab register data for this module
            // Params
            foreach ($reg_weeks as $week) {
                $module_code = $atp['module_code'];
                $start = $atp['start_time'];
                $end = $atp['end_time'];
                $day_num = $atp['day_num'];

                $week_parts = explode('/', $week);
                $wk_day = $week_parts[0];
                $wk_month = $week_parts[1];
                $wk_year = $week_parts[2];
                $week_start = mktime(0,0,0, $wk_month, $wk_day, $wk_year, 0);

                if ($day_num > 1) {
                    // Day num holds the (1-7, Mon being 1, Tues 2 etc.)
                    // We find the number of days to add to week start by using $day_num - 1
                    $day = $day_num - 1;
                    $unixdate = strtotime("+$day days", $week_start);
                    // convert to dd/mm/yyyy format for the method
                    $date = date('d/m/Y', $unixdate);
                } else {
                    $date = $week;
                    $unixdate = $week_start;
                }

                // What's the unixtime now?
                $unixtime_now = time();
                if (($unixtime_now > $unixdate) && $mark = $attpunc->getMarkForModuleSlot($user->idnumber, $term, $module_code, $date, $start, $end)) {
                    // Check for mark key and if found wrap it in a span with title for on hovers
                    if (isset($attpunc->marks_key[$mark])) {
                        echo "<td class=\"center\"><span class=\"hover\" title=\"".$attpunc->marks_key[$mark]."\">$mark</span></td>"; 
                    } else {
                        echo "<td class=\"center\">$mark</td>"; 
                    }
                } else {
                    echo "<td>&nbsp;</td>"; 
                }
            }
            $c++;
            echo '</tr>';
        }
        echo '</table>';
    }
}

if (!$att_shown) {
 echo '<p>No attendance data exists for this user.</p>';
}

echo '</div>';
$performance = $attpunc->stop_timer();
echo "<!-- $performance -->";

print_footer($course);

?>
