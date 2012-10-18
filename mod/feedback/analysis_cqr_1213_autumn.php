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

include('tabs.php');

print_box_start('generalbox boxaligncenter boxwidthwide');

$search_course_names = optional_param('search_course_names', '', PARAM_CLEAN);
$search_levels = optional_param('search_levels', '', PARAM_CLEAN);
$search_curriculum_areas = optional_param('multichoice_1458', '', PARAM_INT);
$search_schools = optional_param('multichoice_1457', '', PARAM_INT);
$search_directorates = optional_param('multichoice_1456', 0, PARAM_INT);

?>	
<table width="100%" class="generalbox">	<form method="POST">
<tr><td>Name of Course</td><td><input type="text" maxlength="100" size="100" name="search_course_names" value="<?php echo $search_course_names; ?>"></td></tr>
<tr><td>Course Code / Level</td><td><input type="text" maxlength="80" size="80" name="search_levels" value="<?php echo $search_levels; ?>"></td></tr>
<tr><?php print_feedback_item('Curriculum Area', 4, $search_curriculum_areas, $feedback);?></tr>
<tr><?php print_feedback_item('School', 5, $search_schools, $feedback);?></tr>			
<tr><?php print_feedback_item('Directorate', 6, $search_directorates, $feedback);?></tr>
<tr><td colspan="2"><div class="form-buttons"><div class="singlebutton"><input type="submit" value="Filter"/></div></div></td></tr>
</form><tr><td colspan="2">
<?php   
	echo '<div class="form-buttons">';
	$export_button_link = 'analysis_to_excel.php';
	$export_button_options = array('sesskey'=>$USER->sesskey, 'id'=>$id);
	$export_button_label = get_string('export_to_excel', 'feedback');
	print_single_button($export_button_link, $export_button_options, $export_button_label, 'post');
	echo '</div>';
?>
</td></tr>
</table>	
<?php

//build filter 

if($search_course_names!='') {
	$cp = get_records_select('feedback_value', "item = 1410 AND value = '$search_course_names'", "completed", "completed");
	if(is_array($cp)) $cp = array_keys($cp);
	else $cp = array();
}

if($search_levels!='') {
	$cp1 = (array) get_records_select('feedback_value', "item = 1455 AND value = '$search_levels'", "completed", "completed");
	if(is_array($cp1)) $cp1 = array_keys($cp1);
	else $cp1 = array();
	if(isset($cp)) $cp = array_intersect($cp, $cp1);
	else $cp = $cp1;
}

if($search_curriculum_areas!='') {	
	$cp2 = get_records_select('feedback_value', "item = 1458 AND value = '$search_curriculum_areas'", "completed", "completed");
	//print_object($cp2);	
	if(is_array($cp2)) $cp2 = array_keys($cp2);
	else $cp2 = array();
	if(isset($cp)) $cp = array_intersect($cp, $cp2);
	else $cp = $cp2;
	
}

if($search_schools!='') {	
	$cp3 = get_records_select('feedback_value', "item = 1457 AND value = '$search_schools'", "completed", "completed");
	if(is_array($cp3)) $cp3 = array_keys($cp3);
	else $cp3 = array();
	if(isset($cp)) $cp = array_intersect($cp, $cp3);
	else $cp = $cp3;
}

if($search_directorates!='') {
	$cp4 = (array) get_records_select('feedback_value', "item = 1456 AND value = '$search_directorates'", "completed", "completed");
	if(is_array($cp4)) $cp4 = array_keys($cp4);
	else $cp4 = array();
	if(isset($cp)) $cp = array_intersect($cp, $cp4);
	else $cp = $cp4;
}

if(isset($cp)) {	
	$completedscount = count($cp);
	if(count($cp)<1) $cp = array(0);
	$where = "completed IN (".implode(',',$cp).")";
} else {
	//get completed feedbacks
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

function print_feedback_item($label, $position, $value, $feedback) {	
	$item = get_record('feedback_item', 'feedback', $feedback->id, 'position', $position);
	$item->name = $label;		
	$item->required = 0;	
	$itemclass = 'feedback_item_'.$item->typ;
	$itemobj = new $itemclass();
	$itemobj->print_item($item, $value, false, false, false);
}

?>
