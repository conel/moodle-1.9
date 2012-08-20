<?php
/**
 * Creates a Learner Progress Review.
 *
 * @copyright &copy; 2009 University of London Computer Centre
 * @author http://www.ulcc.ac.uk, http://moodle.ulcc.ac.uk
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package LPR
 * @version 1.0
 */

// initialise moodle
require_once('../../../config.php');

// using these globals
global $SITE, $CFG, $USER;

// include the permissions check
require_once("{$CFG->dirroot}/blocks/lpr/access_content.php");

if(!$can_view) {
    error("You do not have permission to view LPRs");
}

if(!$can_write) {
    error("You do not have permission to edit LPRs");
}

// include the LPR databse library
require_once("{$CFG->dirroot}/blocks/lpr/models/block_lpr_db.php");

// include the connection code for CONEL's MIS db
require_once($CFG->dirroot.'/blocks/lpr/models/block_lpr_conel_mis_db.php');

// fetch the mandatory course id
$course_id = required_param('course_id', PARAM_INT);

// fetch the mandatory learner id
$learner_id = required_param('learner_id',PARAM_INT);

// fetch the optional ilp param
$ilp = optional_param('ilp', 0, PARAM_INT);
$term_id = required_param('term_id', PARAM_INT);
$lecturer_id = required_param('lecturer_id', PARAM_INT);
$unit_desc = optional_param('unit_desc', '', PARAM_RAW);
$comments = optional_param('comments', PARAM_CLEANHTML);
$modules = required_param('modules', PARAM_RAW);

$areaofdev = optional_param('areaofdev', '', PARAM_RAW);

// Modules come in array format

// fetch the course, or fail if the id is wrong
if (empty($course_id) || ($course = get_record('course', 'id', $course_id)) == false) {
    error("Course ID is incorrect");
}

// fetch the learner's user, or fail if the id is wrong
if (empty($learner_id) || ($learner = get_record('user', 'id', $learner_id)) == false) {
    error("Learner ID is incorrect");
}

// instantiate the lpr db wrapper
$lpr_db = new block_lpr_db();

// resolve the sequence number for this LPR
$sequence = $lpr_db->get_next_sequence($learner_id, $course_id);

// set up the data to insert for the base LPR record
$data = new stdClass;
$data->name = 'LPR'.$sequence->next;
$data->course_id = $course_id;
$data->learner_id = $learner_id;
$data->lecturer_id = $lecturer_id;
$data->sequence = $sequence->next;
$data->timecreated = time();
$data->timemodified = time();
$data->term_id = $term_id; // nkowald - 2010-11-23 - Needed term_id as it's a required field
$data->unit_desc = $unit_desc;
$data->comments = $comments;

$data->areaofdev = $areaofdev;

// set deadline
$dday = optional_param('dday', PARAM_INT); 
$dmonth = optional_param('dmonth', PARAM_INT); 
$dyear = optional_param('dyear', PARAM_INT);
 
if(! checkdate ($dmonth, $dday, $dyear)) {
    $errors[] = 'deadline';
} else {
	$lpr->deadline = strtotime("$dday-$dmonth-$dyear");
}

// insert the record
$id = $lpr_db->create_lpr($data);

if($id == false) {
    error("Could not create new LPR");
}

// instantiate the CONEL MIS db wrapper
$conel_db = new block_lpr_conel_mis_db();

// nkowald - 2011-03-17 - Added a new method containing my fix, and used this instead
//$allmodules = $conel_db->list_modules($learner->idnumber);
$allmodules = $conel_db->list_distinct_modules($learner->idnumber);

foreach ($allmodules as $module) {
	
	// nkowald - 2011-03-17 - Needed to add this here to get accurate data and not just the last row
	$sql = "SELECT SUM(MARKS_PRESENT) AS MARKS_PRESENT, 
				SUM(MARKS_TOTAL) AS MARKS_TOTAL, 
				SUM(MARKS_TOTAL)-SUM(MARKS_PRESENT) AS MARKS_ABSENT,
				SUM(PUNCT_POSITIVE) AS PUNCT_POSITIVE,
				SUM(MARKS_PRESENT)-SUM(PUNCT_POSITIVE) AS PUNCT_NEGATIVE
			FROM 
				FES.MOODLE_ATTENDANCE_PUNCTUALITY 
			WHERE 
				STUDENT_ID = '".$learner->idnumber."' AND 
				MODULE_CODE = '".$module->MODULE_CODE."' AND 
				MARKS_TOTAL > 0";
				
	$module_data = $conel_db->execute_query($sql);
	
	// nkowald - 2011-03-17 - Will only ever be one item in array, adding in a foreach because execute_array returns sql objects in array format so values can't be directly accessed without knowing key
	foreach ($module_data as $mod) {
		$data = new stdClass;
		$data->lpr_id = $id;
		$data->module_code = $module->MODULE_CODE;
		$data->module_desc = $module->MODULE_DESC;
		$data->punct_positive = $mod->PUNCT_POSITIVE;
		$data->marks_present = $mod->MARKS_PRESENT;
		$data->marks_total = $mod->MARKS_TOTAL;
	}
	// If we checked the box against this module, lets set it as selected
	$data->selected = $modules[$module->MODULE_CODE];
	$lpr_db->save_module($data);
	
	// Now update mdl_module_complete to update ilp stats table
	$term = $lpr_db->get_term_by_id($term_id);
	// Should only update modules completes that have been selected (otherwise it will set to incomplete even if complete in another target 1).
	if ($data->selected == 1) {
		$lpr_db->update_module_complete($data->module_code, $term->term_code, $term->ac_year_code, $learner_id, $data->selected);
	}
}
// nkowald - 2011-06-28 - Adding averages here as they were missing
$avg = $lpr_db->get_average_selected($id);

// delete any existing attendance records
$lpr_db->delete_attendances($id);

// insert the attendance record
$mod = new stdClass();
$mod->lpr_id = $id;
$mod->attendance = $avg->attendance*100;
$mod->punctuality = $avg->punctuality*100;
$mod->timecreated = time();
$mod->timemodified = time();
// insert the new attendance data
$lpr_db->create_attendance($mod);

// redirect to the edit page
//header("Location: {$CFG->wwwroot}/blocks/lpr/actions/edit.php?id={$id}&ilp={$ilp}");
header("Location: {$CFG->wwwroot}/blocks/lpr/actions/view.php?id={$id}&ilp={$ilp}&saved=1");
?>
