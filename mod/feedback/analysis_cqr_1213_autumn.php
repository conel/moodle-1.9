<?php

require_once("../../config.php");
require_once("lib.php");

$current_tab = 'analysis';

$id = required_param('id', PARAM_INT);

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

$tabs2_show_response_link = 'show_entries_cqr_1213_autumn.php';
include('tabs2.php');

print_box_start('generalbox boxaligncenter boxwidthwide');

include('analysis_cqr_1213_autumn_filterform.php');

if(isset($cp)) {	
	$completedscount = count($cp);
	if(count($cp)<1) $cp = array(0);
	$where = "completed IN (".implode(',',$cp).")";
} else {
	$completedscount = feedback_get_completeds_group_count($feedback);
}

//show the count
echo '<b>'.get_string('completed_feedbacks', 'feedback').': '.$completedscount. '</b><br />';

// get the items of the feedback
$items = get_records_select('feedback_item', 'feedback = '. $feedback->id . ' AND hasvalue = 1', 'position');

//show the count
if(is_array($items)){
	echo '<b>'.get_string('questions', 'feedback').': ' .sizeof($items). ' </b><hr />';
} else {
	$items=array();
}

$check_anonymously = true;

echo '<div><table width="80%" cellpadding="10"><tr><td>';

$itemnr = 0;

//print the items in an analysed form
foreach($items as $item) {
	
	if($item->hasvalue == 0) continue;
	
	echo '<table width="100%" class="generalbox">';
	
	$itemclass = 'feedback_item_'.$item->typ;
	
	$itemobj = new $itemclass();
	$itemnr++;
	
	if($feedback->autonumbering) {
		$printnr = $itemnr.'.';
	} else {
		$printnr = '';
	}
	
	$where2 = '';
	
	if(isset($cp)) {
		$where2 = $where." AND item='".$item->id."'";
	}
	
	$itemobj->print_analysed($item, $printnr, null, false, $where2);
	
	echo '</table>';
}

echo '</td></tr></table></div>';

print_box_end();

print_footer($course);

?>
