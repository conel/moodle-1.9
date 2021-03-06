<?php
/**
 * This page allows editing of the portfolio grade and comments.
 * It is called as part of edit_portfolio.php but also on it's own as the form submission action.
 *
 * @copyright &copy; 2009-2010 University of London Computer Centre
 * @author http://www.ulcc.ac.uk, http://moodle.ulcc.ac.uk
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package AssMgr
 * @version 2.0
 */

//if (!defined('MOODLE_INTERNAL')) {
//    // this must be included from a Moodle page
//    die('Direct access to this script is forbidden.');
//}

//include moodle config
//require_once(dirname(__FILE__).'/../../../config.php');

// remove this when testing is complete
$path_to_config = dirname($_SERVER['SCRIPT_FILENAME']).'/../../../config.php';
while (($collapsed = preg_replace('|/[^/]+/\.\./|','/',$path_to_config,1)) !== $path_to_config) {
    $path_to_config = $collapsed;
}
require_once('../../../config.php');

global $CFG, $PAGE, $PARSER;

// Meta includes
require_once($CFG->dirroot.'/blocks/assmgr/actions_includes.php');

// inlcude the gradelib
require_once($CFG->libdir.'/gradelib.php');

// include the moodle form library
require_once($CFG->libdir.'/formslib.php');
require_once($CFG->dirroot.'/blocks/assmgr/classes/assmgr_formslib.php');

require_once($CFG->dirroot.'/blocks/assmgr/classes/forms/edit_portfolio_assessment_mform.php');

$dbc = new assmgr_db();
$PAGE->requires->js('/lib/yui/yahoo/yahoo-min.js');
$PAGE->requires->js('/lib/yui/event/event-min.js');
$PAGE->requires->js('/lib/yui/dom/dom-min.js');

if (empty($portfolio_id)) {
    $candidate_id = $PARSER->required_param('candidate_id', PARAM_INT);
    $course_id = $PARSER->required_param('course_id', PARAM_INT);
    $portfolio = $dbc->get_portfolio($candidate_id, $course_id);
    $portfolio_id = $portfolio->id;
}
// lock the portfolio if possible
check_portfolio(null, null, $portfolio_id);

// get the portfolio scale
$portfolio_scale = $dbc->get_portfolio_scale($portfolio_id);

// get the portfolio grade and comments
$portfolio_grade = $dbc->get_portfolio_grade($course_id, $candidate_id);

$portfolio_comments = $dbc->get_portfolio_comments($portfolio_id);

if(empty($course)) {
    $course = $dbc->get_course($course_id);
}

if (empty($candidate)) {
    $candidate = $dbc->get_user($candidate_id);
}

$portassessform = new edit_portfolio_assessment_mform($portfolio_scale, $course, $candidate, $portfolio_comments, $access_canviewuserdetails);

// this form has no cancal button
if($portassessform->is_submitted()) {
    // check the validation rules
    if($portassessform->is_validated()) {

        $data = $portassessform->get_data();

        // Possibly the grade is empty but there is a comment. Avoid 'unset variable error'
        if (empty($data->portfolio_grade)) {
            $data->portfolio_grade = null;
        }
        // Make sure there is something to submit. Sometimes both will be empty but this is still a change.
        if (!empty($data->portfolio_grade) || !empty($data->portfolio_comment) || ($data->portfolio_grade != $portfolio_grade)) {

            // process the data
            $success = $portassessform->process_data($data, $access_isassessor);
            // no need for error handling - the form data processing function throws an error if needed
            // and the redirect/OK message is handled from there too.
			
            if (!$success) {
                print_error('portfoliogradecouldnotbesaved', 'block_assmgr');
            }

            if ($data->ajaxsave == 'true') {
                echo $data->formid.' ok';
                die();
            } else {
                $return_message = (empty($portfolio_grade)) ? get_string('portassesssaved','block_assmgr') : get_string('portassessupdated','block_assmgr');
				redirect($CFG->wwwroot.'/blocks/assmgr/actions/list_portfolio_assessments.php?course_id='.$data->course_id, $return_message, 2);
            }
        }

    } else {
        die('form not validated');
    }

} else {

    if (!empty($portfolio_grade))  {

        $datamerge = array();
        $datamerge['portfolio_grade'] = (Int)$portfolio_grade->grade;

        // the checkbox for 'finished' need to be checked only if a grade has been recorded.
        // The qualifications don't really have an end date, so saying 'failed' is the same as
        // 'not finished'
        if (!is_null($portfolio_grade->grade)) {
            $datamerge['studentfinished'] = 'checked';
        }

        $portassessform->set_data($datamerge);
    }
}

require_once($CFG->dirroot.'/blocks/assmgr/views/edit_portfolio_assessment.html');

// NOTE: the html include is not here any longer because of the need to have this
// php file included early on in edit_portfolio.php so that the submitted form can be caught
// before the HTML page is constructed.