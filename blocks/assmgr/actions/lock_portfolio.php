<?php
/**
 *  This page renew a lock (from an ajax call)
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

$path_to_config = dirname($_SERVER['SCRIPT_FILENAME']).'/../../../config.php';
while (($collapsed = preg_replace('|/[^/]+/\.\./|','/',$path_to_config,1)) !== $path_to_config) {
    $path_to_config = $collapsed;
}
require_once('../../../config.php');

global $CFG, $USER, $PARSER;



// Meta includes
require_once($CFG->dirroot.'/blocks/assmgr/actions_includes.php');

//include static constants file
require_once($CFG->dirroot.'/blocks/assmgr/constants.php');

//get the id of the course that is currently being used
$course_id = $PARSER->required_param('course_id', PARAM_INT);
$candidate_id = $PARSER->optional_param('candidate_id', $USER->id, PARAM_INT);

// you must be either a candidate or an assessor to edit a portfolio
if(!$access_iscandidate && !$access_isassessor) {
    // do nothing
    return;

}

if($access_iscandidate && $USER->id != $candidate_id) {
    // candidates can't edit someone else's portfolio
    return;
}

if($access_isassessor) {
    // assessors can't assess their own portfolio
    if($USER->id == $candidate_id) {
        // do nothing
        return;
    }

    // make sure the candidate is actually a candidate in this context
    $iscandidate = has_capability('block/assmgr:creddelevidenceforself', $coursecontext, $candidate_id, false);
    if(!$iscandidate) {
        // do nothing
        return;

    }
}

$dbc = new assmgr_db();

// get the portfolio
$portfolio = $dbc->get_portfolio($candidate_id, $course_id);

// is the current portfolio locked?
if($dbc->lock_exists($portfolio->id)) {
    // renew the lock if it belongs to the current user
    if($dbc->lock_exists($portfolio->id, $USER->id)) {
        $dbc->renew_lock($portfolio->id, $USER->id, get_config('block_assmgr', 'ajaxexpirytime'));
    } else {
        // do nothing
        return;

    }
} else {
    // create a new lock
    $dbc->create_lock($portfolio->id, $USER->id);
}
?>