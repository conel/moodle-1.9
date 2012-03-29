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
$data->lecturer_id = $USER->id;
$data->sequence = $sequence->next;
$data->timecreated = time();
$data->timemodified = time();
// nkowald - 2010-11-23 - Needed term_id as it's a required field
$data->term_id = 0;

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
	$lpr_db->save_module($data);
}

// redirect to the edit page
header("Location: {$CFG->wwwroot}/blocks/lpr/actions/edit.php?id={$id}&ilp={$ilp}");

?>