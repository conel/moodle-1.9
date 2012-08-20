<?php
/**
 * Form to create a new Learner Progress Review
 *
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
    error("You do not have permission to create LPRs");
}

// include the LPR databse library
require_once("{$CFG->dirroot}/blocks/lpr/models/block_lpr_db.php");

// nkowald - 2011-10-13 - Adding My Attendance Punctuality Class because these targets need to come from the new views
require_once($CFG->dirroot . '/blocks/ilp/AttendancePunctuality.class.php');

// include the LPR library
require_once("{$CFG->dirroot}/blocks/lpr/block_lpr_lib.php");

// fetch the learner ID
$learner_id = required_param('learner_id', PARAM_INT);
$course_id = required_param('course_id', PARAM_INT);
$ilp = optional_param('ilp', 0, PARAM_INT); // fetch the optional ilp param
$errors = optional_param('errors', array()); // fetch the optional errors param

if(!empty($errors)) {
    $errors = unserialize(base64_decode($errors));
}

// instantiate the lpr db wrapper
$lpr_db = new block_lpr_db();

// instantiate the attendancepunctuality class
$attpun = new AttendancePunctuality();

// fetch the LPR, or fail if the id is wrong
/*
if (empty($id) || ($lpr = $lpr_db->get_lpr($id)) == false) {
    error("LPR ID is incorrect");
}
*/

// fetch the course, or fail if the id is wrong (unlikely, as the id comes from the db)
if (empty($course_id) || ($course = get_record('course', 'id', $course_id)) == false) {
    error("Course ID is incorrect");
}
// fetch the learner's user, or fail if the id is wrong (unlikely, as the id comes from the db)
if (empty($learner_id) || ($learner = get_record('user', 'id', $learner_id)) == false) {
    error("Learner ID is incorrect");
}

// fetch the tutor's user
$tutor = $lpr_db->get_tutor($learner_id);

// fetch the lecturer's user, or fail if the id is wrong (unlikely, as the id comes from the db)
if (!empty($USER->id) && ($lecturer = get_record('user', 'id', $USER->id)) == false) {
    error("Lecturer ID is incorrect");
}

//get the block config
$config = get_config('project/lpr');

$current_term = NULL;

//if the term_id has not been set get the current term and set it as the default term
$c_term	=	$lpr_db->get_current_term($config->academicyear, time());

if (!empty($c_term)) {
	$current_term = $c_term->id;
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
    'name' => 'LPR' . $ilp,
    'link' => null
);

$navlinks = build_navigation($navlinks);

$heading = $course->shortname.' : LPR'.$ilp.' - '.fullname($learner);

// get the indicators
// nkowald - 2010-07-05 - Not needed anymore
//$indicators = $lpr_db->get_indicators();
//$indicators = NULL;

// get the indicator answers
//$answers = $lpr_db->get_indicator_answers($id);


// get the course category
$category = get_record("course_categories", "id", $course->category);

// get the modules
// nkowald - 2011-10-13 - Get modules the new way
$modules = $attpun->getModules($learner->idnumber);
// We want to get the total averages for A&P here. The combined slots averages don't match what's shown on the ILP homepage.
$att = $attpun->get_attendance_avg($learner->idnumber);
$avg_att = round(($att->ATTENDANCE * 100), 2);
$punc = $attpun->get_punctuality_avg($learner->idnumber);
$avg_punc = round(($punc->PUNCTUALITY * 100), 2);

// get the list of everyone with teacher capabilities in this context
$course_context = get_context_instance(CONTEXT_COURSE, $course_id);
$teachers = get_users_by_capability($course_context, 'block/lpr:write', 'u.id, CONCAT_WS(" ", u.firstname, u.lastname) AS name', 'u.firstname');

// strip out do anything roles
foreach($teachers as $teach_id => $teacher) {
    if($teacher->roleid == '1') {
        unset($teachers[$teach_id]);
    }
}

// get the list of tutors
$tutors = records_to_menu($teachers, 'id', 'name');

// get the list of lecturers

$lecturers = records_to_menu($teachers, 'id', 'name');
 
$termrecords	=	$lpr_db->get_current_terms($config->academicyear,time());

// get the list of terms
$terms = records_to_menu($termrecords, 'id', 'name');

// print the theme's header
print_header($heading, $heading, $navlinks, '', '', true, '&nbsp;', navmenu($course));

// print the page heading
print_heading($heading);

// we need to tell add.html not to show editing functionality
$editable = true;

// now that we've got all the data we need, let's display it
require_once("{$CFG->dirroot}/blocks/lpr/views/new.html");

// print the page footer
print_footer();
?>
