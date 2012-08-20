<?php
/**
 * Displays a Learner Progress Review, in a read-only format.
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

// include the LPR database library
require_once("{$CFG->dirroot}/blocks/lpr/models/block_lpr_db.php");

// include the LPR library
require_once("{$CFG->dirroot}/blocks/lpr/block_lpr_lib.php");

// fetch the mandatory id paramater
$id = required_param('id', PARAM_INT);

// fetch the optional ilp param
$ilp = optional_param('ilp', 0, PARAM_INT);

// instantiate the lpr db wrapper
$lpr_db = new block_lpr_db();

//get the block config
$config = get_config('project/lpr');

$current_term = NULL;

// fetch the LPR, or fail if the id is wrong
if (empty($id) || ($lpr = $lpr_db->get_lpr($id)) == false) {
    error("LPR ID is incorrect");
}

// fetch the course, or fail if the id is wrong (unlikely, as the id comes from the db)
// nkowald - 2010-12-06 - Old LPRs were failing due to courses being deleted, allow deleted courses
if (empty($lpr->course_id) || ($course = get_record('course', 'id', $lpr->course_id)) == false) {
   error("Course ID is incorrect");
}

// fetch the learner's user, or fail if the id is wrong (unlikely, as the id comes from the db)
if (empty($lpr->learner_id) || ($learner = get_record('user', 'id', $lpr->learner_id)) == false) {
    error("Learner ID is incorrect");
}

// fetch the tutor's user
$tutor=$lpr_db->get_tutor($lpr->learner_id);

// fetch the lecturer's user, or fail if the id is wrong (unlikely, as the id comes from the db)
if (!empty($lpr->lecturer_id) && ($lecturer = get_record('user', 'id', $lpr->lecturer_id)) == false) {
    error("Lecturer ID is incorrect");
}

//if the term_id has not been set get the current term and set it as the default term

if (!empty($lpr->term_id))  {
	$lpr_term	=	get_record('terms','id',$lpr->term_id);
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
        'link' => $CFG->wwwroot."/user/index.php?id=" . (empty($course->id) ? $SITE->id : $course->id)
    );
} else {
    $navlinks[] = array(
        'name' => get_string("ilp", 'block_ilp'),
        'link' => $CFG->wwwroot."/blocks/ilp/view.php?id={$learner->id}".(empty($course->id) ? '' : "&amp;courseid=$course->id")
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
    'name' => str_replace('LPR', 'Target ', $lpr->name),
    'link' => null
);

$navlinks = build_navigation($navlinks);
$heading = $course->shortname.' : '.str_replace('LPR', 'Target ', $lpr->name).' - '.fullname($learner);

// get the indicators
//$indicators = $lpr_db->get_indicators();
$indicators = NULL;

// get the indicator answers

$answers = NULL;

// get the attendance data
$atten = $lpr_db->get_attendance($id);

// get the course category
$category = get_record("course_categories", "id", $course->category);

// get the modules
$modules = $lpr_db->get_modules($lpr->id, true);

// print the theme's header
print_header($heading, $heading, $navlinks, '', '', true, '&nbsp;', navmenu($course));

// print the page heading
print_heading($heading);

// we need to tell view.html not to show editing functionality
$editable = false;

// now that we've got all the data we need, let's display it
require_once("{$CFG->dirroot}/blocks/lpr/views/view.html");

// print the page footer
print_footer();
?>
