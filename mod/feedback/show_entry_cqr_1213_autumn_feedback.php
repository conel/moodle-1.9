<?php

require_once("../../config.php");
require_once("lib.php");

$id = required_param('id', PARAM_INT);
$userid = required_param('userid', PARAM_INT);

$current_tab = 'showentries';

if ($id) {
	if (! $cm = get_coursemodule_from_id('feedback', $id)) {
		error("Course Module ID was incorrect");
	}
	if (! $course = get_record("course", "id", $cm->course)) {
		error("Course is misconfigured");
	}
	if (! $feedback = get_record("feedback", "id", $cm->instance)) {
		error("Course module is incorrect");
	}
}

$capabilities = feedback_load_capabilities($cm->id);

if($course->id == SITEID) {
	require_login($course->id, true);
} else {
	require_login($course->id, true, $cm);
}

if ( !( (intval($feedback->publish_stats) == 1) || $capabilities->viewreports)) {
	error(get_string('error'));
}

/// Print the page header
$strfeedbacks = get_string("modulenameplural", "feedback");
$strfeedback  = get_string("modulename", "feedback");
$buttontext = update_module_button($cm->id, $course->id, $strfeedback);

$navlinks = array();
$navlinks[] = array('name' => $strfeedbacks, 'link' => "index.php?id=$course->id", 'type' => 'activity');
$navlinks[] = array('name' => format_string($feedback->name), 'link' => "", 'type' => 'activityinstance');

$navigation = build_navigation($navlinks);

print_header_simple(format_string($feedback->name), "", $navigation, "", "", true, $buttontext, navmenu($course, $cm));

include('tabs.php');

$hide_filterform = 1;
$filterform_nofilter = 1;
include('analysis_cqr_1213_autumn_feedback_filterform.php');

$usr = get_record('user', 'id', $userid);
$feedbackitems = get_records('feedback_item', 'feedback', $feedback->id, 'position');
$feedbackcompleted = get_record_select('feedback_completed','feedback='.$feedback->id.' AND userid='.$usr->id);

print_box_start('generalbox boxaligncenter boxwidthnormal');

echo '<table width="100%">';

$itemnr = 0;

foreach($feedbackitems as $feedbackitem){
	//get the values
	$value = get_record_select('feedback_value','completed ='.$feedbackcompleted->id.' AND item='.$feedbackitem->id);
	echo '<tr>';
	if($feedbackitem->hasvalue == 1 AND $feedback->autonumbering) {
		$itemnr++;
		echo '<td valign="top">' . $itemnr . '.&nbsp;</td>';
	} else {
		echo '<td>&nbsp;</td>';
	}
	
	if($feedbackitem->typ != 'pagebreak') {
		if(isset($value->value)) {
			feedback_print_item($feedbackitem, $value->value, true);
		}else {
			feedback_print_item($feedbackitem, false, true);
		}
	}else {
		echo '<td><hr /></td>';
	}
	echo '</tr>';
}
echo '<tr><td colspan="2" align="center">';
echo '</td></tr>';
echo '</table>';
echo '</form>';

// print_simple_box_end();
print_box_end();

?>	

<div class="continuebutton">
	<div class="singlebutton">
		<input type="submit" value="Continue" onclick="document.forms['faszfejanko'].action='show_entries_cqr_1213_autumn_feedback.php';document.forms['faszfejanko'].submit();return false;">
	</div>
</div>
