<?php
/**
 * Edits a Learner Progress Review
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

// include the LPR library
require_once("{$CFG->dirroot}/blocks/lpr/block_lpr_lib.php");

// fetch the mandatory id paramater
$id = required_param('id', PARAM_INT);

// fetch the optional ilp param
$ilp = optional_param('ilp', 0, PARAM_INT);

// fetch the optional errors param
$errors = optional_param('errors', array());

if(!empty($errors)) {
    $errors = unserialize(base64_decode($errors));
}

// instantiate the lpr db wrapper
$lpr_db = new block_lpr_db();

// fetch the LPR, or fail if the id is wrong
if (empty($id) || ($lpr = $lpr_db->get_lpr($id)) == false) {
    error("LPR ID is incorrect");
}

// fetch the course, or fail if the id is wrong (unlikely, as the id comes from the db)
if (empty($lpr->course_id) || ($course = get_record('course', 'id', $lpr->course_id)) == false) {
    error("Course ID is incorrect");
}

// fetch the learner's user, or fail if the id is wrong (unlikely, as the id comes from the db)
if (empty($lpr->learner_id) || ($learner = get_record('user', 'id', $lpr->learner_id)) == false) {
    error("Learner ID is incorrect");
}

// fetch the tutor's user
$tutor = $lpr_db->get_tutor($lpr->learner_id);

// fetch the lecturer's user, or fail if the id is wrong (unlikely, as the id comes from the db)
if (!empty($lpr->lecturer_id) && ($lecturer = get_record('user', 'id', $lpr->lecturer_id)) == false) {
    error("Lecturer ID is incorrect");
}

// get the optional print param
$print = optional_param('print', 0, PARAM_INT);

// setup the navlinks
$navlinks = array();

if ($course->id != $SITE->id) {
    $navlinks[] = array(
        'name' => $course->shortname,
        'link' => $CFG->wwwroot.'/course/view.php?id='.$course->id
    );
}

if(!$ilp) {
    $navlinks[] = array(
        'name' => get_string("participants"),
        'link' => $CFG->wwwroot."/user/index.php?id=".$course->id
    );
} else {
    $navlinks[] = array(
        'name' => get_string("ilp", 'block_ilp'),
        'link' => $CFG->wwwroot."/blocks/ilp/view.php?id={$learner->id}&amp;courseid=$course->id"
    );
}

$navlinks[] = array(
    'name' => fullname($learner),
    'link' => $CFG->wwwroot.'/user/view.php?id='.$learner->id.'&amp;course='.$course->id
);

$navlinks[] = array(
    'name' => get_string('lprs','block_lpr'),
    'link' => $CFG->wwwroot.'/blocks/lpr/actions/list.php?course_id='.$course->id.'&amp;learner_id='.$learner->id.'&amp;ilp='.$ilp
);

$navlinks[] = array(
    'name' => $lpr->name,
    'link' => null
);

$navlinks = build_navigation($navlinks);

$heading = $course->shortname.' : '.$lpr->name.' - '.fullname($learner);

// get the indicators
//$indicators = $lpr_db->get_indicators();
$indicators = NULL;

// get the indicator answers
$answers = $lpr_db->get_indicator_answers($id);

// get the attendance data
$atten = $lpr_db->get_attendance($id);

// get the course category
$category = get_record("course_categories", "id", $course->category);

// get the modules
$modules = $lpr_db->get_modules($lpr->id);

// get the list of everyone with teacher capabilities in this context
$course_context = get_context_instance(CONTEXT_COURSE, $lpr->course_id);
$teachers = get_users_by_capability($course_context, 'block/lpr:write', 'u.id, CONCAT_WS(" ", u.firstname, u.lastname) AS name');

// strip out do anything roles
foreach($teachers as $id => $teacher) {
    if($teacher->roleid == '1') {
        unset($teachers[$id]);
    }
}

// get the list of tutors
$tutors = records_to_menu($teachers, 'id', 'name');

// get the list of lecturers
$lecturers = records_to_menu($teachers, 'id', 'name');

// print the theme's header
print_header($heading, $heading, $navlinks, '', '', true, '&nbsp;', navmenu($course));

// print the page heading
print_heading($heading);

// we need to tell view.html not to show editing functionality
$editable = true;

// now that we've got all the data we need, let's display it
require_once("{$CFG->dirroot}/blocks/lpr/views/view.html");

// print the page footer
print_footer();
?>