<?php
/**
 * Saves the POST data sent by edit.php, to update a Learner Progress Review
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

// fetch the mandatory id paramater
$id = required_param('id', PARAM_INT);

// fetch the optional ilp param
$ilp = optional_param('ilp', 0, PARAM_INT);

// instantiate the lpr db wrapper
$lpr_db = new block_lpr_db();

// fetch the LPR, or fail if the id is wrong
if (empty($id) || ($lpr = $lpr_db->get_lpr($id)) == false) {
    error("LPR ID is incorrect");
}

// initialise the errors array
$errors = array();

// update the lecturer
$lpr->lecturer_id = required_param('lecturer_id', PARAM_INT);

if(empty($lpr->lecturer_id)) {
    $errors[] = 'lecturer_id';
}

// update the lecturer
$lpr->term_id = required_param('term_id', PARAM_INT);

if(empty($lpr->lecturer_id)) {
    $errors[] = 'term_id';
}

// update the LPR comments
$lpr->comments = required_param('comments');

// update the LPR unit_desc
$lpr->unit_desc = required_param('unit_desc');

// update the timemodified stamp
$lpr->timemodified = time();

// save it to the database
$lpr_db->set_lpr($lpr);

// get the new indicator answers
$modules = optional_param('modules');

// get previous values - only used posted if the form was in edit mode
$pre_modules_value = optional_param('pre_modules_value',NULL);


// get the new indicator answers
// nkowald - 2010-07-07 - Indicators no longer used.
/*
$indicators = required_param('ind');



// fetch any existing indicator answers from the database
$db_answers = $lpr_db->get_indicator_answers($lpr->id);

// delete any existing answers from the database
$lpr_db->delete_indicator_answers($lpr->id);

// process the post data
foreach($indicators as $ind_id => $value) {
    if(!empty($value)) {
        // is this an update
        if(!empty($db_answers[$ind_id])) {
            $ans = $db_answers[$ind_id];
            // has the answer changed
            if($ans->answer != $value) {
                // override the answer and update the timestamp
                $ans->answer = $value;
                $ans->timemodified = time();
            }
        } else {
            // we build a new indicator answer
            $ans = new stdClass();
            $ans->lpr_id = $lpr->id;
            $ans->indicator_id = $ind_id;
            $ans->answer = $value;
            $ans->timecreated = time();
            $ans->timemodified = time();
        }
        // insert into the database
        $lpr_db->create_indicator_answer($ans);
    } else {
        // they haven't answered all the questions!
        $errors[] = $ind_id;
    }
}
*/

// unmark all modules
//set_field('block_lpr_mis_modules', 'selected', 0, 'lpr_id', $id);


// mark as selected the choosen module
if(!empty($modules)){
	foreach($modules as $module_id => $choosen) {
		set_field('block_lpr_mis_modules', 'selected', $choosen, 'id', $module_id);
		$lpr_mod = $lpr_db->get_module($module_id);
		$term 	 = $lpr_db->get_term_by_id($lpr->term_id);
		
		if (!empty($pre_modules_value) && $pre_modules_value[$module_id] != $choosen) {
			$lpr_db->update_module_complete($lpr_mod->module_code,$term->term_code,$term->ac_year_code,$lpr->learner_id,$choosen);
		}
		
		/*
		
		$mis_mod = $lpr_db->get_module_complete($lpr_mod->module_code,$lpr->term_id,$term->ac_year_code,$lpr->learner_id);
		if (!empty($mis_mod)) {
			$mis_mod->complete = $choosen;
			
		}
		*/
		//use module code to update record in the mdl_module_complete table
	}
}

// work out the average attendance and punctuality 
$avg = $lpr_db->get_average_selected($id);

// delete any existing attendacne records
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

if(empty($errors)) {
    // then redirect us to the next page
    header("Location: {$CFG->wwwroot}/blocks/lpr/actions/view.php?id={$lpr->id}&ilp={$ilp}");
} else {
    $errors = base64_encode(serialize($errors));
    // then bounce us back to the edit page
    header("Location: {$CFG->wwwroot}/blocks/lpr/actions/edit.php?id={$lpr->id}&ilp={$ilp}&errors={$errors}");
}