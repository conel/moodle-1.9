<?php
include ($CFG->dirroot.'/blocks/ilp/access_context.php');
require_once($CFG->dirroot.'/blocks/ilp/templates/custom/dbconnect.php');
require_once("{$CFG->dirroot}/blocks/lpr/models/block_lpr_db.php"); // include the LPR databse library
$lpr_db = new block_lpr_db();

require_once($CFG->dirroot . '/blocks/ilp/AttendancePunctuality.class.php');
$attpunc = new AttendancePunctuality();

// nkowald - 2010-06-17 - Get overall progress number
$indicators = $lpr_db->get_indicators();
$avg_answers = $lpr_db->get_indicator_answers_avg($USER->id, $SITE->id);
$progress_val = ($avg_answers[1]->answer != '') ? $avg_answers[1]->answer : 0;

echo '<div id="ilp-profile-overview">';
$users_name = '<h2><a href="'.$CFG->wwwroot.'/user/view.php?'.(($courseid)?'courseid='.$courseid.'&' : '').'id='.$id.'">'. fullname($user) .'</a></h2>';

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
	// nkowald - 2010-09-06 - Scott got me to change the name of this
    //$status_html = '<div class="status">Your status is: <span class="main astatus-'.$studentstatusnum.' '.$lower_status.'">'.$thisstudentstatus .'&nbsp;'.$status_icon.'</span></div>';
    $status_html = '<div class="status">Your status is: '.$status_icon.'</span></div>';
}

$print_to_pdf = '<a href="'.$CFG->wwwroot.'/blocks/lpr/actions/export.php?learner_id='.$user->id.'&amp;single=1" id="print_to_pdf">Print to PDF</a>';
?>
<table id="ilp_main_stats">
	<tr>
		<td colspan="3">
			<table cellspacing="0" cellpadding="0" id="student_name" width="100%">
				<tr>
					<td width="272">
					<?php 
						echo '<h1><a href="'.$CFG->wwwroot.'/user/view.php?'.(($courseid)?'courseid='.$courseid.'&amp;' : '').'id='.$id.'">'. fullname($user) .'</a></h1>';
					?>
					</td>
					<td><?php if($CFG->ilpconcern_status_per_student == 1){ echo $status_html; } ?></td>
					<td>&nbsp;</td>
				</tr>
			</table>
		</td>
	</tr>
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
	</tr>
	<tr>
		<td style="vertical-align:top; border:1px solid #fff;" class="label" colspan="3">
			<div id="your_progress">
                <h2 id="mm_progress">Your Progress:</h2>
                <div class="target_grade_mm">
                    <?php 
                        $target_grade = (get_target_grade($user->id)) ? get_target_grade($user->id) : 'not set';
                        echo '<b>Target Grade:</b> '. $target_grade;
                    ?>
                </div>
                <br class="clear_both" />
                    <?php
                        display_ilp_your_progress($id, $courseid);		
                    ?>
			</div>
			<?php
				// nkowald - 2011-11-14 - The code that creates the iframe, auto-resizes iframe and makes all links open in parent is here: /blocks/assmgr/views/show_progress_ilp.html
                $html = get_unit_progress($user->id);
                echo $html
            ?>
		</td>
	</tr>
</table>