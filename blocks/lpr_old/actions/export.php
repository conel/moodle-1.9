<?php
/**
 * Displays the PDF printing options for the Learner Progress Review.
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

if(!$can_print) {
    error("You do not have permission to print LPRs");
}

// fetch the optional couse_id
$course_id = optional_param('course_id', null, PARAM_INT);

$learner_id = optional_param('learner_id', null, PARAM_INT);

// fetch the optional message
$msg = optional_param('msg', null);

$single = optional_param('single', 0, PARAM_BOOL);

// if there is a course_id: fetch the course, or fail if the id is wrong
if (!empty($course_id) && ($course = get_record('course', 'id', $course_id)) == false) {
    error("Course ID is incorrect");
}

// include the LPR databse library
require_once("{$CFG->dirroot}/blocks/lpr/models/block_lpr_db.php");

// instantiate the lpr db wrapper
$lpr_db = new block_lpr_db();

$categories = records_to_menu($lpr_db->get_categories(), 'id', 'name');
$learners = records_to_menu($lpr_db->get_learners(), 'id', 'name');

// setup the navlinks, page heading and where conditions
$navlinks = array();
$heading = array();
$params = array();

if (!empty($course) && $course->id != $SITE->id) {
    $navlinks[] = array(
        'name' => $course->shortname,
        'link' => $CFG->wwwroot.'/course/view.php?id='.$course->id
    );
    $params[] = 'course_id='.$course->id;
}

$navlinks[] = array(
    'name' => get_string('lprs','block_lpr'),
    'link' => $CFG->wwwroot.'/blocks/lpr/actions/list.php?'.(!empty($course_id) ? 'course_id='.$course->id : '')
);

$navlinks[] = array(
    'name' => get_string('pdfexport','block_lpr'),
    'link' => null
);

$navlinks = build_navigation($navlinks);

$heading = get_string('lprs', 'block_lpr').' : '.get_string('pdfexport','block_lpr');

// print the theme's header
if (empty($course)) {
    print_header($heading, $heading, $navlinks);
} else {
    // filtering by a course should also display the course navigation menu
    print_header($heading, $heading, $navlinks, '', '', true, '&nbsp;', navmenu($course));
}

// print the page heading
print_heading($heading);

// now that we've got all the data we need, display the HTML
require_once("{$CFG->dirroot}/blocks/lpr/views/export.html");

// print the footer
print_footer();
?>