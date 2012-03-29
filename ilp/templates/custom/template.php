<?php
include ('access_context.php');
require_once($CFG->dirroot.'/blocks/ilp/templates/custom/dbconnect.php');

// include the connection code for CONEL's MIS db
require_once($CFG->dirroot.'/blocks/lpr/models/block_lpr_conel_mis_db.php');
$conel_db = new block_lpr_conel_mis_db();

echo '<div class="generalbox" id="ilp-profile-overview">';
echo '<h1>';
echo '<a href="'.$CFG->wwwroot.'/user/view.php?'.(($courseid)?'courseid='.$courseid.'&' : '').'id='.$id.'">';
echo fullname($user);
echo '</a>';

if($CFG->ilpconcern_status_per_student == 1){
    echo '<span class="main astatus-'.$studentstatusnum.'" style="margin-left:20px">'.(($access_isuser)? get_string('mystudentstatus', 'ilpconcern'):get_string('studentstatus', 'ilpconcern')).': '.$thisstudentstatus.'</span>';
}

echo '</h1>';
echo '<a href="'.$CFG->wwwroot.'/blocks/lpr/actions/export.php?learner_id='.$user->id.'&amp;single=1" style="float:right">Print to PDF</a>';

?>
<table class="generalbox">
    <tr>
        <td rowspan="3" width="125">
            <?php print_user_picture($user, (($courseid)?$courseid : 1), $user->picture,100); ?>
        </td>
        <td><!-- Progress --></td>
        <td>
			<?php
			/*
			//include the portfolio class
			require_once("{$CFG->dirroot}/blocks/assessmgr/actions/class/block_assessmgr_portfolio.class.php");
			//include the table name mappings
			require_once("{$CFG->dirroot}/blocks/assessmgr/actions/class/tableslib.php");
			// get the assignment manager
			$port =	new block_assessmgr_portfolio("1.9",$STATIC_TABLES);
			//get the overall progress in all courses for this student
			$overuserprog = $port->overall_user_progress_unit($id);
			// convert the fraction to a percentage
			$assmgr_progress = round($overuserprog['totalpercentage']*100);
			?>
			<div class="lpr_progress_bar" title="<?php echo $assmgr_progress.'% Progress';?>">
                <div class="progress_avg" style="width: <?php echo $assmgr_progress;?>%" />
            </div>	
			<?php
			*/
			 ?>			
		</td>
        <td rowspan="8" class="block_lpr_ilp_container">
            <?php display_ilp_lpr_averages($id, $courseid); ?>
        </td>
        <td rowspan="8">
            <table>
                <tr>
                    <th colspan="2">Assessment</th>
                </tr>
                <tr>
                    <td>Initial Assessment</td>
                    <td>&nbsp;</td>
                </tr>
                <tr>
                    <td>Learning Style</td>
                    <td>&nbsp;</td>
                </tr>
                <tr>
                    <td>Literacy DA level</td>
                    <td>&nbsp;</td>
                </tr>
                <tr>
                    <td>Numeracy DA level</td>
                    <td>&nbsp;</td>
                </tr>
                <tr>
                    <td>ICT DA level</td>
                    <td>&nbsp;</td>
                </tr>
            </table>
        </td>
    </tr>
    <tr>
        <td><?php echo get_string('attendance', 'block_lpr'); ?></td>
        <td>
            <?php
			
            $atten = $conel_db->get_attendance_avg($user->idnumber);
            $att_prec = round($atten->ATTENDANCE*100,2);
			echo $att_prec.' %';
            ?>
			<div class="lpr_progress_bar" title="<?php echo $att_prec.'% '.get_string('attendance', 'block_lpr');?>">
            <div class="attendance_avg" style="width: <?php echo $att_prec;?>%" /></div>
        </td>
    </tr>
    <tr>
        <td><?php echo get_string('punctuality', 'block_lpr'); ?></td>
        <td>
            <?php
			
            $punc = $conel_db->get_punctuality_avg($user->idnumber);
            $punc_prec = round($punc->PUNCTUALITY*100, 2);
			echo $punc_prec.' %'
            ?>
            <div class="lpr_progress_bar" title="<?php echo $punc_prec.'% '.get_string('punctuality', 'block_lpr');?>">
            <div class="punctuality_avg" style="width: <?php echo $punc_prec;?>%" /></div>
        </td>
    </tr>
    <tr>
        <td colspan="3" rowspan="2">
            <?php display_ilp_student_info($id,$courseid,FALSE); ?>
        </td>
    </tr>
    <tr>
    </tr>
    <tr>
        <td colspan="3">&raquo;&nbsp;Text Student</td>
    </tr>
    <tr>
        <td colspan="3">&raquo;&nbsp;<?php link_to_popup_window("/blocks/ilp/templates/custom/key.php", $name='Key', 'Key to Scoring',$height=550, $width=750, $title='Key',$options='none', $return=false); ?></td>
    </tr>
</table>

<?php
echo '<div class="generalbox" style="width:720px; text-align:center">';
echo '<table class="generalbox" cellspacing="5" cellpadding="5" border="1" style="text-align:center; border: 1px solid #ccc">';
echo '<tr><th style="text-align:center">1</th><th style="text-align:center">2</th><th style="text-align:center">3</th><th style="text-align:center">4</th><th style="text-align:center">5</th><th style="text-align:center">6</th><th style="text-align:center">7</th><th style="text-align:center">8</th><th style="text-align:center">9</th><th style="text-align:center">10</th></tr>';
echo '<tr><td>Very Poor</td><td>Poor</td><td>Inadequate
</td><td>Weak</td><td>O.K.</td><td>Quite good</td><td>Good</td><td>Very good</td><td>Excellent</td><td>Outstanding</td></tr>';
echo '</table>';
echo '</div>';

if ($attendance = $mis->Execute(
	"select MODULE_CODE 
	from FES.MOODLE_ATTENDANCE_PUNCTUALITY 
	where STUDENT_ID = '".$user->idnumber."' 
	AND ACADEMIC_YEAR = '$academicYear3' 
	GROUP BY MODULE_CODE")) {

echo '<div class="generalbox" id="ilp-attendance-overview">';
echo '<h2>Attendance</h2>';

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
	$course_att = $mis->Execute(
		"SELECT SUM(MARKS_PRESENT) AS MARKS_PRESENT, 
				SUM(MARKS_TOTAL) AS MARKS_TOTAL, 
				SUM(MARKS_TOTAL)-SUM(MARKS_PRESENT) AS MARKS_ABSENT,
				SUM(PUNCT_POSITIVE) AS PUNCT_POSITIVE,
				SUM(MARKS_PRESENT)-SUM(PUNCT_POSITIVE) AS PUNCT_NEGATIVE
			FROM 
				FES.MOODLE_ATTENDANCE_PUNCTUALITY 
			WHERE 
				STUDENT_ID = '".$user->username."' and 
				MODULE_CODE = '$coursedesc' AND 
				MARKS_TOTAL > 0");
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

if(round($totalcourse,0) >= 93) {
    echo '<td class="attendance-green">&nbsp;</td>';
}elseif(round($totalcourse,0) >= 90 && round($totalcourse,0) < 93){
    echo '<td class="attendance-amber">&nbsp;</td>';
}elseif(round($totalcourse,0) <= 90) {
    echo '<td class="attendance-red">&nbsp;</td>';
}else{
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


if($config->ilp_show_student_info == 1) {
    echo '<div class="generalbox" id="ilp-student_info-overview">';

    echo '</div>';
}

if($config->ilp_show_targets == 1) {
    echo '<div class="generalbox" id="ilp-target-overview">';
    display_ilptarget ($id,$courseid);
    echo '</div>';
}

if($config->ilp_show_concerns == 1) {
    $i = 1;
    while ($i <= 4){
        if(eval('return $CFG->ilpconcern_report'.$i.';') == 1) {
        echo '<div class="generalbox" id="ilp-concerns-overview">';
        display_ilpconcern ($id,$courseid,$i);
        echo '</div>';
        }
        $i++;
    }
}

if($config->ilp_show_lprs == 1) {
    echo '<div class="generalbox" id="ilp-lprs-overview">';
    display_ilp_lprs ($id,$courseid);
echo '</div>';
}
?>