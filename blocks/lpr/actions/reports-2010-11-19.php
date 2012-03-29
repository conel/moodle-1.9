<?php
/**
 * Displays course categories and courses along with a statistical breakdown of
 * the performance of their learners based on the LPRs.
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

// include the LPR library
require_once("{$CFG->dirroot}/blocks/lpr/block_lpr_lib.php");

// include the LPR databse library
require_once("{$CFG->dirroot}/blocks/lpr/models/block_lpr_db.php");

// instantiate the lpr db wrapper
$lpr_db = new block_lpr_db();

// fetch the optional filter params
$category_id = optional_param('category_id', null, PARAM_INT);
$course_id = optional_param('course_id', null, PARAM_INT);
$learner_id = optional_param('learner_id', null, PARAM_INT);
$start_date = optional_param('start_date', null);
$end_date = optional_param('end_date', null);

// convert into american style date to resolve the unix-timestamp
$date = explode('/', $start_date);
$start_time = !empty($date[2]) ? mktime(0, 0, 0, $date[1], $date[0], $date[2]) : null;
$date = explode('/', $end_date);
$end_time = !empty($date[2]) ? mktime(0, 0, 0, $date[1], $date[0]+1, $date[2]) : null;

// if there is a course_id: fetch the course, or fail if the id is wrong
if (!empty($course_id) && ($course = get_record('course', 'id', $course_id)) == false) {
    error("Course ID is incorrect");
}

// if there is a learner_id: fetch the learner, or fail if the id is wrong
if (!empty($learner_id) && ($learner = get_record('user', 'id', $learner_id)) == false) {
    error("Learner ID is incorrect");
}

// if there is a category_id: fetch the category, or fail if the id is wrong
if (!empty($category_id) && ($category = get_record('course_categories', 'id', $category_id)) == false) {
    error("Category ID is incorrect");
}

// setup the navlinks, page heading and where conditions
$navlinks = array();
$heading = array();

if (!empty($category)) {

    $navlinks[] = array(
        'name' => get_string('categories'),
        'link' => $CFG->wwwroot.'/course/index.php'
    );

    $navlinks[] = array(
        'name' => $category->name,
        'link' => $CFG->wwwroot.'/course/category.php?id='.$category->id
    );
    $heading[] = $category->name;
}

if (!empty($course) && $course->id != $SITE->id) {
    $navlinks[] = array(
        'name' => $course->shortname,
        'link' => $CFG->wwwroot.'/course/view.php?id='.$course->id
    );
    $heading[] = $course->shortname;
}

if (!empty($learner)) {
    $navlinks[] = array(
        'name' => get_string("participants"),
        'link' => $CFG->wwwroot."/user/index.php?id=" . (empty($course->id) ? $SITE->id : $course->id)
    );

    $navlinks[] = array(
        'name' => fullname($learner),
        'link' => $CFG->wwwroot.'/user/view.php?id='.$learner->id . (empty($course->id) ? null : '&amp;course='.$course->id)
    );
    $heading[] = fullname($learner);
}

$navlinks[] = array(
    'name' => get_string('lprreports','block_lpr'),
    'link' => null
);

$navlinks = build_navigation($navlinks);

if(empty($heading)) {
    $heading[] = $SITE->shortname;
}

$heading = implode(' - ', $heading).' : '.get_string('lprreports', 'block_lpr');

// print the theme's header
if (empty($course)) {
    print_header($heading, $heading, $navlinks);
} else {
    // filtering by a course should also display the course navigation menu
    print_header($heading, $heading, $navlinks, '', '', true, '&nbsp;', navmenu($course));
}

// print the page heading
print_heading($heading);

// fetch the list of all categories and their ancestory
$categories = get_categories_array();

// get the indicators
$indicators = $lpr_db->get_indicators();

// compute the statistics, depending on what level of report is selected
if(!empty($learner_id)) {
    // get the averaged attendance data
    $atten = $lpr_db->get_attendance_avg($learner_id, $course_id, $start_time, $end_time);
    $atten_learner = $lpr_db->get_attendance_avg($learner_id, null, $start_time, $end_time);

    // get the averaged indicator data
    $answers = $lpr_db->get_indicator_answers_avg($learner_id, $course_id, $start_time, $end_time);
    $answers_learner = $lpr_db->get_indicator_answers_avg($learner_id, null, $start_time, $end_time);

    // count the various elements being averaged across
    $lpr_count = $lpr_db->count_lprs($learner_id, $course_id, $start_time, $end_time);
    $lpr_count_learner = $lpr_db->count_lprs($learner_id, null, $start_time, $end_time);
    $lpr_risks = $lpr_db->count_lpr_risks($learner_id, $course_id, $start_time, $end_time);
    $lpr_risks_all = $lpr_db->count_lpr_risks($learner_id, null, $start_time, $end_time);

} elseif(!empty($course_id)) {
    // get the averaged attendance data
    $atten = $lpr_db->get_attendance_avg(null, $course_id, $start_time, $end_time);

    // get the averaged indicator data
    $answers = $lpr_db->get_indicator_answers_avg(null, $course_id, $start_time, $end_time);

    // get the students in this course
    $students = $lpr_db->get_students($course->id);

    // count the various elements being averaged across
    $learner_count = count($students);
    $lpr_count = $lpr_db->count_lprs(null, $course_id, $start_time, $end_time);
    $lpr_learners = $lpr_db->count_lpr_learners($course_id, $start_time, $end_time);
    $lpr_risks = $lpr_db->count_lpr_risks(null, $course_id, $start_time, $end_time);

} else {

    // get the averaged attendance data
    $atten = $lpr_db->get_cat_attendance_avg($category_id, $start_time, $end_time);

    // get the averaged indicator data
    $answers = $lpr_db->get_cat_indicator_avg($category_id, $start_time, $end_time);

    // get all the sub-categories for the given category
    $cats = array_keys(get_categories((empty($category_id) ? 'none' : $category_id), null, false));

    // get the students in this category
    $learners = $lpr_db->get_students_by_cat($category_id);

    // count the the students and the unfinished ILPs
    $countof = $lpr_db->count_incomplete_ilps($learners, $start_time, $end_time);

    $learner_count = $countof->students;
    $unfinished_count = $countof->incomplete;

    // count the various elements being averaged across
    $cat_count = count($cats);

    $course_count = $lpr_db->count_courses_by_cat($cats, $start_time, $end_time);

    $lpr_count = $lpr_db->count_lprs_by_cat($cats, $start_time, $end_time);
    $lpr_learners = $lpr_db->count_lpr_learners_by_cat($cats, $start_time, $end_time);
    $lpr_courses = $lpr_db->count_lpr_courses_by_cat($cats, $start_time, $end_time);
    $lpr_risks = $lpr_db->count_lpr_risks_by_cat($cats, $start_time, $end_time);


    if($category_id) {
        // get all the child courses
        $courses = get_records('course', 'category', $category_id);
    }
}

// now that we've got all the data we need, display the HTML
require_once("{$CFG->dirroot}/blocks/lpr/views/reports.html");

// print the footer
print_footer();
?>