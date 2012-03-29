<?php
/**
 * Displays a table of Learner Progress Reviews, filtered by optional parameters
 * course_id and learner_id
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

// include the LPR databse library
require_once("{$CFG->dirroot}/blocks/lpr/models/block_lpr_db.php");

// instantiate the lpr db wrapper
$lpr_db = new block_lpr_db();

// fetch the optional filter params
$course_id = optional_param('course_id', null, PARAM_INT);
$learner_id = optional_param('learner_id', null, PARAM_INT);
$category_id = optional_param('category_id', 0, PARAM_INT);

// fetch the optional ilp param
$ilp = optional_param('ilp', 0, PARAM_INT);

// fetch the optional risk param
$risk = optional_param('risk', 0, PARAM_INT);

// fetch the optional start and end dates
$start_date = optional_param('start_date', null);
$end_date = optional_param('end_date', null);

// convert into american style date to resolve the unix-timestamp
$date = explode('/', $start_date);
$start_time = !empty($date[2]) ? mktime(0, 0, 0, $date[1], $date[0], $date[2]) : null;
$date = explode('/', $end_date);
// N.B. we need to add one to the day field because the cut-off should be the end of that day
$end_time = !empty($date[2]) ? mktime(0, 0, 0, $date[1], $date[0]+1, $date[2]) : null;

// if there is a course_id: fetch the course, or fail if the id is wrong
if (!empty($course_id) && ($course = get_record('course', 'id', $course_id)) == false) {
    error("Course ID is incorrect");
}

// if there is a learner_id: fetch the learner user, or fail if the id is wrong
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
$where = array();
$params = array();

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
    $where[] = 'lpr.course_id = '.$course->id;
    $params[] = 'course_id='.$course->id;
}

if (!empty($learner)) {
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

    $params[] = 'ilp='.$ilp;

    $navlinks[] = array(
        'name' => fullname($learner),
        'link' => $CFG->wwwroot.'/user/view.php?id='.$learner->id . (empty($course->id) ? null : '&amp;course='.$course->id)
    );
    $heading[] = fullname($learner);
    $where[] = 'lpr.learner_id = '.$learner->id;
    $params[] = 'learner_id='.$learner->id;
}

$navlinks[] = array(
    'name' => get_string('lprs','block_lpr'),
    'link' => null
);

$navlinks = build_navigation($navlinks);

if(empty($heading)) {
    $heading[] = get_string('all', 'block_lpr');
}

if(!empty($risk)) {
    $heading = implode(' - ', $heading).' : '.get_string('atrisk', 'block_lpr');

    if(!empty($start_date) && !empty($end_date)) {
        $heading .= " - From {$start_date} to {$end_date}";
    }
    if(empty($start_date) && !empty($end_date)) {
        $heading .= " - Before {$end_date}";
    }
    if(!empty($start_date) && empty($end_date)) {
        $heading .= " - After {$start_date}";
    }

} else {
    $heading = implode(' - ', $heading).' : '.get_string('lprs', 'block_lpr');
}

// print the theme's header
if (empty($course)) {
    print_header($heading, $heading, $navlinks);
} else {
    // filtering by a course should also display the course navigation menu
    print_header($heading, $heading, $navlinks, '', '', true, '&nbsp;', navmenu($course));
}

// print the page heading
print_heading($heading);

// fetch the update preferences flag
$updatepref = optional_param('updatepref', -1, PARAM_INT);

// check for updated preferences
if ($updatepref > 0){
    $perpage = optional_param('perpage', 10, PARAM_INT);
    $perpage = ($perpage < 1) ? 10 : $perpage ;
    set_user_preference('target_perpage', $perpage);
}

// fetch the perpage limit
$perpage = get_user_preferences('target_perpage', 10);

// check what page we're on now
$pages = optional_param('page', 0, PARAM_INT);

// fetch the table library
require_once("{$CFG->libdir}/tablelib.php");

// set up the flexible table for displaying the LPRs
$table = new flexible_table('lprs_table');

$table->define_columns(
    array(
        'userpic',
        'fullname',
        'course_name',
        'lpr_name',
        'reporter_firstname',
        'timemodified',
        'actions'
    )
);

$table->define_headers(
    array(
        get_string('userpicture', 'block_lpr'),
        '',
        get_string('course'),
        get_string('lpr', 'block_lpr'),
        get_string('lecturer', 'block_lpr'),
        get_string('date'),
        get_string('actions')
    )
);

// make the table sortable
$table->sortable(true, 'timemodified DESC');
$table->no_sorting('actions');
// if the learner is not defined then show learner name filters
if(empty($learner_id)) {
    $table->initialbars(true);
}

$table->collapsible(true);

$table->set_attribute('id', 'lprs');
$table->set_attribute('cellspacing', '0');
$table->set_attribute('class', 'generaltable generalbox block_lpr_center');

$table->setup();

if($risk) {
    // get all the sub-categories for the given category
    $categories = array_keys(get_categories((empty($category_id) ? 'none' : $category_id), null, false));
    // get the list of at risk LPRs
    $lprs = $lpr_db->get_lpr_risks($categories, $learner_id, $course_id, $table, $start_time, $end_time);
} else {
    // get the list of LPRs
    $lprs = $lpr_db->list_lprs($learner_id, $course_id, $table);
}

// if we have results
if(!empty($lprs)) {
    // iterate through the result set
    foreach ($lprs as $lpr) {
        // grab the learner's photo
        $picture = print_user_picture($lpr->learner_id, $lpr->course_id, $lpr->learner_picture, false, true);

        // make a link to the learner's profile
        $profilelink = '<strong><a href="'.$CFG->wwwroot
            .'/user/view.php?id='.$lpr->learner_id.
            (empty($course->id) ? null : '&amp;course='.$lpr->course_id).'">'.
            $lpr->firstname.' '.$lpr->lastname.'</a></strong>';

        // make a link to the course view page
        $courselink = "<a href='{$CFG->wwwroot}/course/view.php?id={$lpr->course_id}'>{$lpr->course_name}</a>";


        // make the link to view the LPR
        $actions  = "<a href='{$CFG->wwwroot}/blocks/lpr/actions/view.php?id={$lpr->lpr_id}&amp;ilp={$ilp}'>".get_string('view', 'block_lpr')."</a>";

        // show an edit link if the user has that capability
        if($can_write) {
            $actions  .= " / <a href='{$CFG->wwwroot}/blocks/lpr/actions/edit.php?id={$lpr->lpr_id}&amp;ilp={$ilp}'>".get_string('edit', 'block_lpr')."</a>";
        }

        // add the row to the table
        $table->add_data(
            array(
                $picture,
                $profilelink,
                $courselink,
                $lpr->lpr_name,
                $lpr->reporter_firstname.' '.$lpr->reporter_lastname,
                date('d/m/y', $lpr->timemodified),
                $actions
            )
        );
    }
}

// print the table
$table->print_html();

// now that we've got all the data we need, display the HTML
require_once("{$CFG->dirroot}/blocks/lpr/views/list.html");

// print the footer
print_footer();
?>