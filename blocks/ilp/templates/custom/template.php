<link rel="stylesheet" type="text/css" media="screen" href="<?php echo $CFG->wwwroot; ?>/lib/yui/container/assets/container.css" />
<link rel="stylesheet" type="text/css" media="screen" href="<?php echo $CFG->wwwroot; ?>/lib/yui/calendar/assets/calendar.css" />
<?php 
require('access_context.php');
require_once($CFG->dirroot . '/blocks/ilp/AttendancePunctuality.class.php');
$attpunc = new AttendancePunctuality();

if($CFG->ilpconcern_status_per_student == 1){
	$lower_status = strtolower($thisstudentstatus);
	$status_icon = '';
	$status_html = '';
	
	switch ($studentstatusnum) {
		case 0:
			$status_icon = '<img src="'.$CFG->wwwroot.'/blocks/ilp/templates/custom/pix/status_green.jpg" alt="" width="30" height="30" />';
			break;	
		case 1:
			$status_icon = '<img src="'.$CFG->wwwroot.'/blocks/ilp/templates/custom/pix/status_amber.jpg" alt="" width="30" height="30" />';
			break;
		case 2:
			$status_icon = '<img src="'.$CFG->wwwroot.'/blocks/ilp/templates/custom/pix/status_red.jpg" alt="" width="30" height="30" />';
			break;
		case 3:
			$status_icon = '<img src="'.$CFG->wwwroot.'/blocks/ilp/templates/custom/pix/status_withdrawn.jpg" alt="" width="30" height="30" />';
			break;
	}
	// nkowald - 2010-09-07 - Scott got me to remove the names for the progess and just keep the icon
    //$status_html = '<div class="status">Your progress is: <span class="main astatus-'.$studentstatusnum.' '.$lower_status.'">'.$thisstudentstatus .'&nbsp;'.$status_icon.'</span></div>';
	// nkowald - 2011-08-09 - Name change
    $status_html = '<div class="status">Your status is: '.$status_icon.'</span></div>';
}

echo '<div class="generalbox" id="ilp-profile-overview">';

$print_to_pdf = '<a href="'.$CFG->wwwroot.'/blocks/lpr/actions/export.php?learner_id='.$user->id.'&amp;single=1" id="print_to_pdf">Print to PDF</a>';
?>
<table id="ilp_header">
    <tr>
        <td width="313">
        <?php 
            echo '<h1><a href="'.$CFG->wwwroot.'/user/view.php?'.(($courseid)?'courseid='.$courseid.'&amp;' : '').'id='.$id.'">'. fullname($user) .'</a></h1>';
        ?>
        </td>
        <td width="180" style="text-align:left;"><?php if($CFG->ilpconcern_status_per_student == 1){ echo $status_html; } ?></td>
        <td>
        <?php
            if ((has_capability('block/lpr:print', $context)) || (has_capability('moodle/site:doanything', $context))) { ?>
            <div class="add_button">
                <?php echo '<form method="post" action="'.$CFG->wwwroot.'/blocks/lpr/actions/export.php?learner_id='.$user->id.'&amp;single=1">';?>
                <input type="submit" value="Print Report" name="submit" /></form>
            </div>
        <?php } ?>
        <div class="target_grade">
        <?php 
                $target_grade = (get_target_grade($user->id)) ? get_target_grade($user->id) : 'not set';
                echo '<b>Target Grade:</b> '. $target_grade;
        ?>
        </div>
        </td>
    </tr>
</table>
<table id="ilp_main_stats">
    <tr>
        <td width="115" style="vertical-align:top;">
            <?php print_user_picture($user, (($courseid)?$courseid : 1), $user->picture, 100); ?>
			<div id="student_profile_icon"><?php display_ilp_student_info($id, $courseid, FALSE); ?></div>
        </td>
		<td style="vertical-align:top; padding:0 !important;" width="400">
			<table>
				<tr>
					<td style="vertical-align:top;" width="95" class="label" colspan="3"><?php echo get_string('attendance', 'block_lpr'); ?></td>
				</tr>
				<tr>
					<td width="105">College Target</td>
					<td>
						<div class="lpr_progress_bar" title="College Target">
						<div class="college_target" style="width: 100%;" /></div>
					</td>
					<td><div class="percent">100%</div></td>
				</tr>
				<tr>
					<td>You</td>
					<td>
						<?php
							$atten = $attpunc->get_attendance_avg($user->idnumber);
							$att_prec = round(($atten->ATTENDANCE * 100),2);
						?>
						<div class="lpr_progress_bar" title="<?php echo $att_prec.'% '.get_string('attendance', 'block_lpr');?>">
						<div class="attendance_avg" style="width: <?php echo $att_prec;?>%;" /></div>
					</td>
					<td><div class="percent"><?php echo $att_prec.' %'; ?></div></td>
				</tr>
				<tr>
					<td style="vertical-align:top;" class="label" colspan="3"><?php echo get_string('punctuality', 'block_lpr'); ?></td>
				</tr>
				<tr>
					<td width="105">College Target</td>
					<td>
						<div class="lpr_progress_bar" title="College Target">
						<div class="college_target" style="width: 100%;" /></div>
					</td>
					<td><div class="percent">100%</div></td>
				</tr>
				<tr>
					<td>You</td>
					<td><?php
						$punc = $attpunc->get_punctuality_avg($user->idnumber);
						$punc_prec = round(($punc->PUNCTUALITY * 100), 2);
						?>
						<div class="lpr_progress_bar" title="<?php echo $punc_prec.'% '.get_string('punctuality', 'block_lpr');?>">
						<div class="punctuality_avg" style="width: <?php echo $punc_prec;?>%;" /></div>
					</td>
					<td><div class="percent"><?php echo $punc_prec.' %'; ?></div></td>
				</tr>
			</table>
		</td>
		<td style="vertical-align:top;">
			<div id="attendance_icon">
					<?php
						$icon_green = '<img src="'.$CFG->wwwroot.'/blocks/ilp/templates/custom/pix/status_green.jpg" alt="" width="30" height="30" />';
						$icon_orange = '<img src="'.$CFG->wwwroot.'/blocks/ilp/templates/custom/pix/status_amber.jpg" alt="" width="30" height="30" />';
						$icon_red = '<img src="'.$CFG->wwwroot.'/blocks/ilp/templates/custom/pix/status_red.jpg" alt="" width="30" height="30" />';
						
						if ($att_prec < 87) {
							echo $icon_red;
						} else if ($att_prec > 87 && $att_prec < 92) {
							echo $icon_orange;
						} else {
							echo $icon_green;
						}
					?>
			</div>
			<div id="punctuality_icon">
					<?php
						if ($punc_prec < 87) {
							echo $icon_red;
						} else if ($punc_prec > 87 && $punc_prec < 92) {
							echo $icon_orange;
						} else {
							echo $icon_green;
						}
					?>
			</div>
		</td>
		<td style="vertical-align:top; border:1px solid #fff;" class="label">
			<div id="your_progress">
			<h2>Your Progress:</h2>
<?php 	
			display_ilp_your_progress($id, $courseid);
			if($CFG->ilpconcern_report4 == 1 && (has_capability('mod/ilpconcern:addreport4', $context) || ($USER->id == $user->id && has_capability('mod/ilpconcern:addownreport4', $context)))) {
				echo '<form method="post" action="'.$CFG->wwwroot.'/mod/ilpconcern/concerns_view.php?courseid='.$courseid.'&amp;userid='.$user->id.'&amp;action=updateconcern&amp;status=3">';
				echo '<input type="submit" value="Add Latest Progress" />';
				echo '</form>';
			}
?>
			</div>
		</td>
	</tr>
</table>

<?php 

//link_to_popup_window("/blocks/ilp/templates/custom/key.php", $name='Key', 'Key to Scoring',$height=550, $width=750, $title='Key',$options='none', $return=false); 

// nkowald - 2011-08-10 - Adding Course and Unit Progress via an iframe
// nkowald - 2011-11-14 - The code that creates the iframe, auto-resizes iframe and makes all links open in parent is here: /blocks/assmgr/views/show_progress_ilp.html
$html = get_unit_progress($user->id);
echo $html;

echo '<div class="generalbox" id="ilp-attendance-overview">';
if ($courseid != 1) {
	echo '<h2><img alt="" src="'.$CFG->wwwroot.'/blocks/ilp/templates/custom/pix/report1.gif" /> <a href="'.$CFG->wwwroot.'/blocks/ilp/attendance.php?courseid='.$courseid.'&amp;userid='.$user->id.'">Attendance</a></h2>';
} else {
	echo '<h2><img alt="" src="'.$CFG->wwwroot.'/blocks/ilp/templates/custom/pix/report1.gif" /> <a href="'.$CFG->wwwroot.'/blocks/ilp/attendance.php?userid='.$user->id.'">Attendance</a></h2>';
}
echo '</div>';

/*
if($config->ilp_show_student_info == 1) {
    echo '<div class="generalbox" id="ilp-student_info-overview">';
    echo '</div>';
}
*/

/*
if ($config->ilp_show_targets == 1) {
    echo '<div class="generalbox" id="ilp-target-overview">';

	//if(has_capability('mod/ilptarget:addtarget', $context) || ($USER->id == $user->id && has_capability('mod/ilptarget:addowntarget', $context))) {
	// nkowald - 2011-09-17 - If user's are viewing their ILPs without a course id they should still be able to see the add button
	if(has_capability('mod/ilptarget:addtarget', $context) || ($USER->id == $user->id)) {
		echo '<div class="add_button">';
		if ($courseid != 1) {
			echo '<form action="'.$CFG->wwwroot.'/mod/ilptarget/target_view.php?courseid='.$courseid.'&amp;userid='.$user->id.'&amp;action=updatetarget" method="post">';
		} else {
			echo '<form action="'.$CFG->wwwroot.'/mod/ilptarget/target_view.php?userid='.$user->id.'&amp;action=updatetarget" method="post">';
		}
		echo '<input type="submit" value="Add Personal Target" />';
		echo '</form>';
		echo '</div>';
	}

    display_ilptarget ($id, $courseid, TRUE, TRUE, TRUE, 'DESC', 3,-1, FALSE, FALSE, FALSE, TRUE);
    echo '</div>';
}
*/

if ($config->ilp_show_concerns == 1) {
	
	/*
    $i = 1;
    while ($i <= 4){
        if(eval('return $CFG->ilpconcern_report'.$i.';') == 1) {
        echo '<div class="generalbox" id="ilp-concerns-overview">';
        display_ilpconcern ($id,$courseid,$i, FALSE);
        echo '</div>';
        }
        $i++;
    }
	*/
	
	// Tutor Reviews
	$i = 1;
	
	
	if(eval('return $CFG->ilpconcern_report'.$i.';') == 1) {

        // nkowald - 2011-08-10 - Adding four new links to this section
		
		echo '<div class="generalbox" id="ilp-concerns-overview" style="margin-bottom:0 !important;">';
		
		if($CFG->ilpconcern_report1 == 1 && (has_capability('mod/ilpconcern:addreport1', $context) || ($USER->id == $user->id && has_capability('mod/ilpconcern:addownreport1', $context)))) {
			echo '<div class="add_button">';
			if ($courseid != 1) {
				echo '<form action="'.$CFG->wwwroot.'/mod/ilpconcern/concerns_view.php?courseid='.$courseid.'&amp;userid='.$user->id.'&amp;action=updateconcern&amp;status=0" method="post">';
			} else {
				echo '<form action="'.$CFG->wwwroot.'/mod/ilpconcern/concerns_view.php?userid='.$user->id.'&amp;action=updateconcern&amp;status=0" method="post">';
			}
			echo '<input type="submit" value="Add Tutor Review" />';
			echo '</form>';
			echo '</div>';
		}

		display_ilpconcern ($id,$courseid,$i, FALSE);
		
        echo '<ul>';
		if ($courseid != 1) {
			echo '<li><a href="'.$CFG->wwwroot.'/mod/ilpconcern/concerns_view.php?courseid='.$courseid.'&amp;userid='.$user->id.'&amp;status=1">Good performance records</a></li>';
			echo '<li><a href="'.$CFG->wwwroot.'/mod/ilpconcern/concerns_view.php?courseid='.$courseid.'&amp;userid='.$user->id.'&amp;status=2">Causes for concern</a></li>';
			echo '<li><a href="'.$CFG->wwwroot.'/mod/ilpconcern/concerns_view.php?courseid='.$courseid.'&amp;userid='.$user->id.'&amp;status=3">Student progress</a></li>';
		} else {
			echo '<li><a href="'.$CFG->wwwroot.'/mod/ilpconcern/concerns_view.php?userid='.$user->id.'&amp;status=1">Good performance records</a></li>';
			echo '<li><a href="'.$CFG->wwwroot.'/mod/ilpconcern/concerns_view.php?userid='.$user->id.'&amp;status=2">Causes for concern</a></li>';
			echo '<li><a href="'.$CFG->wwwroot.'/mod/ilpconcern/concerns_view.php?userid='.$user->id.'&amp;status=3">Student progress</a></li>';
		}
        echo '</ul>';

        echo '<br class="clear_both" />';
		echo '</div>';
		echo '<br />';
		
		//echo "<div class='generalbox'>";
		echo "<div>";
		display_ilpconcern($id, $courseid, 1, true, false, false, 'ASC', 1, false);
		//echo '</div>';
		
		echo '<br />';
	}
} 


if ($config->ilp_show_lprs == 1) {

	// include the permissions check
	require_once("{$CFG->dirroot}/blocks/lpr/access_content.php");

    echo '<div class="generalbox" id="ilp-lprs-overview">';
	
		if ($can_write) {
			echo '<div class="add_button">';
            // nkowald - 2011-08-04 - Changed to new.php
			//echo '<form method="post" action="'.$CFG->wwwroot.'/blocks/lpr/actions/create.php?course_id='.$courseid.'&amp;ilp=1&amp;learner_id='.$user->id.'">
			echo '<form method="post" action="'.$CFG->wwwroot.'/blocks/lpr/actions/new.php?course_id='.$courseid.'&amp;ilp=1&amp;learner_id='.$user->id.'">
			<div><input type="submit" value="Add Target" name="submit" /></div></form>';
			echo '</div>';
		}
	
		display_ilp_lprs($id, $courseid, FALSE);
		
		/*
		echo '&nbsp&nbsp;&nbsp;&nbsp;<span style="font-weight:bold;">';
		if ($courseid != 1) {
			echo '<a href="'.$CFG->wwwroot.'/blocks/lpr/actions/list.php?course_id='.$courseid.'&amp;learner_id='.$user->id.'&amp;ilp=1">Archived targets</a></span>';
		} else {			
			echo '<a href="'.$CFG->wwwroot.'/blocks/lpr/actions/list.php?learner_id='.$user->id.'&amp;ilp=1">Archived targets</a></span>';
		}
		*/
		
	echo '</div>';
	
	//display_ilp_lprs($id, $courseid, TRUE, FALSE, TRUE, 'DESC', 10);
	display_ilp_lprs($id, $courseid, TRUE, FALSE, TRUE, 'DESC', 10, TRUE, '', FALSE);
}
echo '</div>';
?>
