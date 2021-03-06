<?php

/**
 * Saves the assessments that an asessor made on a candidates outcomes. Called from the ajax
 * requests and the form action of view_submissions.php
 *
 * @copyright &copy; 2009-2010 University of London Computer Centre
 * @author http://www.ulcc.ac.uk, http://moodle.ulcc.ac.uk
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package AssMgr
 * @version 2.0
 */

// include moodle config
//require_once(dirname(__FILE__).'/../../../config.php');

// remove this when testing is complete
$path_to_config = dirname($_SERVER['SCRIPT_FILENAME']).'/../../../config.php';
while (($collapsed = preg_replace('|/[^/]+/\.\./|','/',$path_to_config,1)) !== $path_to_config) {
    $path_to_config = $collapsed;
}

require_once('../../../config.php');

global $USER, $CFG, $PARSER;

// Meta includes
require_once($CFG->dirroot.'/blocks/assmgr/actions_includes.php');

if (!$access_isassessor) {
    print_error('nopageaccess', 'block_assmgr');
}

// get the id of the course and candidate
$course_id    = $PARSER->required_param('course_id',    PARAM_INT);
$candidate_id = $PARSER->required_param('candidate_id', PARAM_INT);

// Lock portfolio if possible
check_portfolio($candidate_id, $course_id);

// get the outcomes
$outcomes  = $PARSER->required_param('outcomes', PARAM_ARRAY);
$ajax      = $PARSER->optional_param('ajax', false, PARAM_BOOL);
// for the AJAX save operation only
$ajaxsave  = $PARSER->optional_param('ajaxsave', false, PARAM_BOOL);
$formid    = $PARSER->optional_param('formid', 'error', PARAM_ALPHA);

$dbc = new assmgr_db();
$return_message = '';
// process the portfolio outcomes
if(!empty($outcomes)) {

    // Makes sure all outcomes have grade items
    $dbc->create_portfolio_grade_items($course_id);

    $grades = array();

    // step through each outcome the portfolio achieved
    foreach ($outcomes as $outcome_id => $grade) {
        // check if the outcome was awarded an actual grade
        $grades[$outcome_id] = (!empty($grade)) ? $grade : null;
    }

    $course = $dbc->get_course($course_id);
    $candidate = $dbc->get_user($candidate_id);

    //MOODLE LOG outcomes assessed
    $log_action = get_string('logoutcomesassessed', 'block_assmgr');
    $logstrings = new stdClass;
    $logstrings->name = fullname($candidate);
    $logstrings->course = $course->shortname;
    $log_info = get_string('logoutcomesassessedinfo', 'block_assmgr', $logstrings);
    assmgr_add_to_log($course_id, $log_action, null, $log_info);

    // insert the portfolio outcome grades into the gradebook
    //n.b. this never returns false due to moodle's grade_update_outcomes() not returning anything
    $success = $dbc->set_portfolio_outcomes($course_id, $candidate_id, $grades);

    // TODO $success wass always null
    // Now that MDL-23577 is fixed, once the core moodle is updated in assmoodle1, all this can be
    // uncommented for proper errors

    //if ($success) {
        $return_message = get_string('outcomeassessmentssaved', 'block_assmgr');
   // } else {
   //     $return_message = get_string('outcomeassessmentsnotsaved', 'block_assmgr');
   // }
}

if ($ajax) {

    // TODO - same on success and failure. needs error

    // there will only be one outcome - its an ajax call from the dropdowns
    // return the new HTML to display
    // using foreach because it's an array
   // if ($success) {
        foreach ($outcomes as $outcome_id => $outcomescaleitem) {
            $outcome = $dbc->get_outcomes($course_id, $outcome_id);
            $scale = $dbc->get_scale($outcome->scaleid, $outcome->gradepass);
            $portfolio = $dbc->get_portfolio($candidate_id, $course_id);

            $grades = $dbc->get_portfolio_outcome_grades($portfolio->id, $outcome_id);
            $scale_item = !empty($grades) ? array_pop($grades)->scale_item : null;

            echo $scale->render_scale_item($scale_item);
        }
   // } else {
   //     echo get_string('error', 'block_assmgr');
   // }

} else {
    redirect("{$CFG->wwwroot}/blocks/assmgr/actions/edit_portfolio.php?course_id={$course_id}&candidate_id={$candidate_id}#submittedevidence", $return_message, REDIRECT_DELAY);
}
?>