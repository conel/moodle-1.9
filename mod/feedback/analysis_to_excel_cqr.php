<?php // $Id: analysis_to_excel.php,v 1.5.2.4 2009/06/13 13:07:15 agrabs Exp $
/**
* prints an analysed excel-spreadsheet of the feedback
*
* @version $Id: analysis_to_excel.php,v 1.5.2.4 2009/06/13 13:07:15 agrabs Exp $
* @author Andreas Grabs
* @license http://www.gnu.org/copyleft/gpl.html GNU Public License
* @package feedback
*/

require_once("../../config.php");
require_once("lib.php");
require_once('easy_excel.php');

$id = required_param('id', PARAM_INT);

$coursefilter = optional_param('coursefilter', '0', PARAM_INT);    
$and_clause = optional_param('and_clause', '', PARAM_RAW);    
    
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

$mygroupid = groups_get_activity_group($cm);
    
ob_start();
$fstring = new object();
$fstring->bold = get_string('bold', 'feedback');
$fstring->page = get_string('page', 'feedback');
$fstring->of = get_string('of', 'feedback');
$fstring->modulenameplural = get_string('modulenameplural', 'feedback');
$fstring->questions = get_string('questions', 'feedback');
$fstring->question = get_string('question', 'feedback');
$fstring->responses = get_string('responses', 'feedback');
$fstring->idnumber = get_string('idnumber');
$fstring->username = get_string('username');
$fstring->fullname = get_string('fullname');
$fstring->courseid = get_string('courseid', 'feedback');
$fstring->course = get_string('course');
$fstring->anonymous_user = get_string('anonymous_user','feedback');
ob_end_clean();
    
$filename = "feedback.xls";

// Creating a workbook
$workbook = new EasyWorkbook("-");
$workbook->setTempDir($CFG->dataroot.'/temp');
$workbook->send($filename);
$workbook->setVersion(8);

// Creating the worksheets
$sheetname = clean_param($feedback->name, PARAM_ALPHANUM);
error_reporting(0);
$worksheet1 =& $workbook->addWorksheet(substr($sheetname, 0, 31));
$worksheet1->set_workbook($workbook);
$worksheet2 =& $workbook->addWorksheet('detailed');
$worksheet2->set_workbook($workbook);
error_reporting($CFG->debug);
$worksheet1->setPortrait();
$worksheet1->setPaper(9);
$worksheet1->centerHorizontally();
$worksheet1->hideGridlines();

//$worksheet1->setHeader("&\"Arial," . $fstring->bold . "\"&14".$feedback->name);
// nkowald - 2010-12-01 - Changed this as it was showing massive number when printing
$worksheet1->setHeader("&\"Arial," . $fstring->bold . "\"&11 ".$feedback->name);
$worksheet1->setFooter($fstring->page." &P " . $fstring->of . " &N");
$worksheet1->setColumn(0, 0, 30);
$worksheet1->setColumn(1, 20, 15);
$worksheet1->setMargins_LR(0.10);

$worksheet2->setLandscape();
$worksheet2->setPaper(9);
$worksheet2->centerHorizontally();

//writing the table header
$rowOffset1 = 0;
$worksheet1->setFormat("<f>",12,false);
$worksheet1->write_string($rowOffset1, 0, UserDate(time()));

////////////////////////////////////////////////////////////////////////
//print the analysed sheet
////////////////////////////////////////////////////////////////////////
 
$search_course_names = optional_param('search_course_names', '', PARAM_CLEAN);
$search_levels = optional_param('search_levels', '', PARAM_CLEAN);
$search_curriculum_areas = optional_param('multichoice_1458', '', PARAM_INT);
$search_schools = optional_param('multichoice_1457', '', PARAM_INT);
$search_directorates = optional_param('multichoice_1456', 0, PARAM_INT); 

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

if($completedscount > 0){
	//write the count of completeds
	$rowOffset1++;
	$worksheet1->write_string($rowOffset1, 0, $fstring->modulenameplural.': '.strval($completedscount));
}

//get the questions (item-names)
$items = get_records_select('feedback_item', 'feedback = '. $feedback->id . ' AND hasvalue = 1', 'position');
          
if(is_array($items)){
	$rowOffset1++;
	$worksheet1->write_string($rowOffset1, 0, $fstring->questions.': '. strval(sizeof($items)));
}

$rowOffset1 += 2;
$worksheet1->write_string($rowOffset1, 0, $fstring->question);
$worksheet1->write_string($rowOffset1, 1, $fstring->responses);
$rowOffset1++ ;

if (empty($items)) {
	 $items=array();
}

foreach($items as $item) {
	
	$itemclass = 'feedback_item_'.$item->typ;
	$itemobj = new $itemclass();
	$where2 = '';
	
	if(isset($cp)) {
		$where2 = $where." AND item='".$item->id."'";
	}
	
	$rowOffset1 = $itemobj->excelprint_item($worksheet1, $rowOffset1, $item, null, false, $where2);
}

////////////////////////////////////////////////////////////////////////
//print the detailed sheet
////////////////////////////////////////////////////////////////////////
//get the completeds

$completeds = feedback_get_completeds_group($feedback, $mygroupid, $coursefilter, $and_clause);
//important: for each completed you have to print each item, even if it is not filled out!!!
//therefor for each completed we have to iterate over all items of the feedback
//this is done by feedback_excelprint_detailed_items

$rowOffset2 = 0;
//first we print the table-header
$rowOffset2 = feedback_excelprint_detailed_head($worksheet2, $items, $rowOffset2);


if(is_array($completeds)){
	foreach($completeds as $completed) {
		$rowOffset2 = feedback_excelprint_detailed_items($worksheet2, $completed, $items, $rowOffset2);
	}
}
    
$workbook->close();
exit;

////////////////////////////////////////////////////////////////////////////////    
////////////////////////////////////////////////////////////////////////////////    
//functions
////////////////////////////////////////////////////////////////////////////////    


function feedback_excelprint_detailed_head(&$worksheet, $items, $rowOffset) {
	global $fstring, $feedback;
	
	if(!$items) return;
	$colOffset = 0;
	
	$worksheet->setFormat('<l><f><ru2>');

	$worksheet->write_string($rowOffset, $colOffset, $fstring->idnumber);
	$colOffset++;

	$worksheet->write_string($rowOffset, $colOffset, $fstring->username);
	$colOffset++;

	$worksheet->write_string($rowOffset, $colOffset, $fstring->fullname);
	$colOffset++;
	
	foreach($items as $item) {
		$worksheet->setFormat('<l><f><ru2>');
		$worksheet->write_string($rowOffset, $colOffset, stripslashes_safe($item->name));
		$colOffset++;
	}

	$worksheet->setFormat('<l><f><ru2>');
	$worksheet->write_string($rowOffset, $colOffset, $fstring->courseid);
	$colOffset++;

	$worksheet->setFormat('<l><f><ru2>');
	$worksheet->write_string($rowOffset, $colOffset, $fstring->course);
	$colOffset++;

	return $rowOffset + 1;
}

function feedback_excelprint_detailed_items(&$worksheet, $completed, $items, $rowOffset) {
	global $fstring;
	
	if(!$items) return;
	$colOffset = 0;
	$courseid = 0;
	
	$feedback = get_record('feedback', 'id', $completed->feedback);
	//get the username
	//anonymous users are separated automatically because the userid in the completed is "0"
	$worksheet->setFormat('<l><f><ru2>');
	if($user = get_record('user', 'id', $completed->userid)) {
		if ($completed->anonymous_response == FEEDBACK_ANONYMOUS_NO) {
			$worksheet->write_string($rowOffset, $colOffset, $user->idnumber);
			$colOffset++;
			$userfullname = fullname($user);
			$worksheet->write_string($rowOffset, $colOffset, $user->username);
			$colOffset++;
		} else {
			$userfullname = $fstring->anonymous_user;
			$worksheet->write_string($rowOffset, $colOffset, '-');
			$colOffset++;
			$worksheet->write_string($rowOffset, $colOffset, '-');
			$colOffset++;
		}
	}else {
		$userfullname = $fstring->anonymous_user;
		$worksheet->write_string($rowOffset, $colOffset, '-');
		$colOffset++;
		$worksheet->write_string($rowOffset, $colOffset, '-');
		$colOffset++;
	}
	
	$worksheet->write_string($rowOffset, $colOffset, $userfullname);
	
	$colOffset++;
	foreach($items as $item) {
		$value = get_record('feedback_value', 'item', $item->id, 'completed', $completed->id);
		
		$itemclass = 'feedback_item_'.$item->typ;
		$itemobj = new $itemclass();
		$printval = $itemobj->get_printval($item, $value);

		$worksheet->setFormat('<l><vo>');
		if(is_numeric($printval)) {
			$worksheet->write_number($rowOffset, $colOffset, trim($printval));
		} else {
			$worksheet->write_string($rowOffset, $colOffset, trim($printval));
		}
		$printval = '';
		$colOffset++;
		$courseid = isset($value->course_id) ? $value->course_id : 0;
		if($courseid == 0) $courseid = $feedback->course;
	}
	$worksheet->write_number($rowOffset, $colOffset, $courseid);
	$colOffset++;
	if(isset($courseid) AND $course = get_record('course', 'id', $courseid)){
		$worksheet->write_string($rowOffset, $colOffset, $course->shortname);
	}
	return $rowOffset + 1;
}

?>
