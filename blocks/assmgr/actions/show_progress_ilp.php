<?php
/**
 * This page displays all the submissions made in a portfolio, from either
 * the candidate's or the assessor's perspective, depending on the candidate_id
 * and the user's capabilities
 *
 * @copyright &copy; 2009-2010 University of London Computer Centre
 * @author http://www.ulcc.ac.uk, http://moodle.ulcc.ac.uk
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package AssMgr
 * @version 2.0
 */

//include moodle config
//require_once(dirname(__FILE__).'/../../../config.php');

// remove this when testing is complete
require_once($_SERVER['DOCUMENT_ROOT'] . '\config.php');

global $USER, $CFG, $PARSER;

// Meta includes
require_once($CFG->dirroot.'/blocks/assmgr/actions_includes.php');

// include the evidence resource class
require_once($CFG->dirroot.'/blocks/assmgr/classes/resources/assmgr_resource.php');

//get the id of the course that is currently being used
$course_id = $PARSER->required_param('course_id', PARAM_INT);

if ($course_id == SITEID) {
    print_error('errorinsitecourse','block_assmgr');
}

$candidate_id = $PARSER->optional_param('candidate_id', $USER->id, PARAM_INT);

// folder ID
$folder_id = $PARSER->optional_param('folder_id', 0, PARAM_INT);

//get the verificiation id if it exists
$verification_id = $PARSER->optional_param('verification_id', null, PARAM_INT);

// instantiate the db
$dbc = new assmgr_db();

if (!empty($verification_id)) {
    if (!$access_isverifier) {
        print_error('nosubmissionverify', 'block_assmgr');
    }
    $verification = $dbc->get_verification($verification_id);
} else {
    $access_isverifier = false;
}

// you must be either a candidate or an assessor to edit a portfolio
if(!$access_iscandidate && !$access_isassessor) {
    print_error('noeditportfoliopermission','block_assmgr');
}

// nkowald - 2011-06-23 - Instead of checking logged in user != candidate, use a role
//if($access_iscandidate && $USER->id != $candidate_id) {
if($access_iscandidate && !has_capability('block/assmgr:verifyportfolio', $coursecontext, $USER->id)) {
    // candidates can't edit someone else's portfolio
    print_error('noeditothersportfolio', 'block_assmgr');
}

if($access_isassessor) {
    // assessors can't assess their own portfolio
    if($USER->id == $candidate_id) {
        print_error('cantassessownportfolio', 'block_assmgr');
    }

    // make sure the candidate is actually a candidate in this context
    $iscandidate = has_capability('block/assmgr:creddelevidenceforself', $coursecontext, $candidate_id, false);

    if(!$iscandidate) {
        print_error('portfolionotincourse', 'block_assmgr');
    }
}

// get the candidate, course and category
$candidate = $dbc->get_user($candidate_id);
$course = $dbc->get_course($course_id);
$coursecat = $dbc->get_category($course->category);

// get the portfolio if it exists
$portfolio_id = check_portfolio($candidate_id, $course_id);

// get the configuration for this instance
$config = $dbc->get_instance_config($course_id);

// is the current portfolio locked?
if($dbc->lock_exists($portfolio_id)) {
    // renew the lock if it belongs to the current user
    if($dbc->lock_exists($portfolio_id, $USER->id)) {
        $dbc->renew_lock($portfolio_id, $USER->id);
    } else {
        // otherwise throw an error
        print_error('portfolioinuse', 'block_assmgr');
    }
} else {
    // create a new lock
    $dbc->create_lock($portfolio_id, $USER->id);
}

// update imported evidence
assmgr_resource::update_resources($course_id, $candidate_id, false);

// setup the page title and heading
$PAGE->title = $course->shortname.': '.get_string('blockname','block_assmgr');
$PAGE->set_url('/blocks/assmgr/actions/edit_portfolio.php', $PARSER->get_params());

if($folder_id == 0) {
    // if a folder is not specified,
    // I have to select the folder with the same name of the course
    // IF ANY
    $folder = $dbc->get_default_folder($course_id, $candidate_id);

    if($folder) {
        $folder_id = $folder->id;
    }
}

$param = $dbc->get_instance_config($course->id);

require_once($CFG->dirroot.'/blocks/assmgr/views/show_progress_ilp.html');

?>
