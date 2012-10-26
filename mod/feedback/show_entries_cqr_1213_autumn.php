<?php

require_once("../../config.php");
require_once("lib.php");

$id = required_param('id', PARAM_INT);

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


$delete = optional_param('delete', 0, PARAM_INT);
$completedid = optional_param('completedid', 0, PARAM_INT);
    
if($delete && $completedid && $completed = get_record('feedback_completed', 'id', $completedid)) {
	feedback_delete_completed($completedid);
	add_to_log($course->id, 'feedback', 'delete', 'view.php?id='.$cm->id, $feedback->id,$cm->id);
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

$hide_filterform = 1;
include('analysis_cqr_1213_autumn_filterform.php');

if(isset($cp)) {	
	if(count($cp)<1) $cp = array(0);
	$feedback_items = implode(',',$cp);
	$sql = "SELECT distinct(c.userid),c.id as completedid,u.*
			FROM mdl_feedback_completed c,mdl_user u
			WHERE c.id IN (".$feedback_items.")
			AND u.id = c.userid";
	
	
} else {
	$sql = "SELECT distinct(c.userid),c.id as completedid, u.*
			FROM mdl_feedback_value v, mdl_feedback_completed c,mdl_user u
			WHERE v.item IN (SELECT i.id FROM mdl_feedback_item i WHERE i.feedback='".(int)$feedback->id."')
			AND c.id = v.completed
			AND u.id = c.userid";
}

$users = get_records_sql($sql)

?>

<div align="center">
	<a href="" onclick="document.forms['faszfejanko'].action='analysis_cqr_1213_autumn.php';document.forms['faszfejanko'].submit();return false;">
		Course Analysis (Submitted answers: <?php echo is_array($users)?count($users):'0'; ?>)
	</a>
</div>

<?php

if($users) {		

?>

<table><tbody><tr><td width="400">non anonymous entries (<?php echo count($users); ?>)<hr>
<table width="100%">

<?php

	foreach($users as $student) {
?>
<tr>
	<td align="left">
		<?php echo print_user_picture($student->id, $id, $student->picture, false, true);?>
	</td>
	<td align="left">
		<?php echo fullname($student);?>
	</td>
	<td align="right">
		<div class="singlebutton">
			<input type="submit" value="Show responses" onclick="document.forms['faszfejanko'].action='show_entry_cqr_1213_autumn.php?userid=<?php echo $student->id; ?>';document.forms['faszfejanko'].submit();return false;">
		</div>
	</td>
	<?php
		if($capabilities->deletesubmissions) {
	?>
		<td align="right">
			<div class="singlebutton">
				<input type="submit" value="Delete entry" onclick="if(confirm('Are you sure you want to delete this entry?'))document.forms['faszfejanko'].action='show_entries_cqr_1213_autumn.php?delete=1&completedid=<?php echo $student->completedid; ?>&userid=<?php echo $student->id; ?>';document.forms['faszfejanko'].submit();return false;">
			</div>				
		<?php
		/*
			$delete_button_link = 'delete_completed.php';
			$delete_button_options = array('sesskey'=>$USER->sesskey, 'completedid'=>$student->completedid, 'do_show'=>'showoneentry', 'id'=>$id);
			$delete_button_label = get_string('delete_entry', 'feedback');
			print_single_button($delete_button_link, $delete_button_options, $delete_button_label, 'post');
		*/
		?>
		</td>
	<?php
		}
	?>			
</tr>	
<?php
	}
}

?>
</table>
</table></tbody></tr></td>	
<?php

exit;



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
