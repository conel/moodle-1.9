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
$category_id = optional_param('category_id', 0, PARAM_INT);
$start_timestamp = optional_param('start_date', null);
$end_timestamp = optional_param('end_date', null);

// N.B. we need to add one to the day field because the cut-off should be the end of that day
$end_timestamp = !empty($end_timestamp) ? $end_timestamp+1 : null;

// if there is a course_id: fetch the course, or fail if the id is wrong
if (!empty($course_id) && ($course = get_record('course', 'id', $course_id)) == false) {
    error("Course ID is incorrect");
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
    $params[] = 'course_id='.$course->id;
}

$navlinks[] = array(
    'name' => get_string('incomplete','block_lpr'),
    'link' => null
);

$navlinks = build_navigation($navlinks);

if(empty($heading)) {
    $heading[] = get_string('all', 'block_lpr');
}


$heading = implode(' - ', $heading).' : '.get_string('incomplete', 'block_lpr');

$start_d	=	date('d/m/Y',$start_timestamp);
$end_d		=	date('d/m/Y',$end_timestamp);

if(!empty($start_d) && !empty($end_d)) {
    $heading .= " - From {$start_d} to {$end_d}";
}
if(empty($start_d) && !empty($end_d)) {
    $heading .= " - Before {$end_d}";
}
if(!empty($start_d) && empty($end_d)) {
    $heading .= " - After {$start_d}";
}

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
$table = new flexible_table('ilps_table');

$table->define_columns(
    array(
        'userpic',
        'fullname',
		'status',
		'attendance',
		'punctuality',
		'targets',
		'review',
		'performance',
		'concern',
		'progress',
        'actions'
    )
);

$table->define_headers(
    array(
        get_string('userpicture', 'block_lpr'),
        '',
		get_string('status', 'block_lpr'),
		get_string('attendanceabr', 'block_lpr'),
		get_string('punctualityabr', 'block_lpr'),
		get_string('targets', 'block_lpr'),
		get_string('tutorreviews', 'block_lpr'),
		get_string('goodperformance', 'block_lpr'),
		get_string('causeforconcern', 'block_lpr'),
		get_string('studentprogress', 'block_lpr'),
        get_string('actions')
    )
);

// make the table sortable
$table->sortable(true, 'timemodified DESC');
$table->no_sorting('actions');
$table->no_sorting('status');
$table->no_sorting('attendance');
$table->no_sorting('punctuality');
$table->no_sorting('targets');
$table->no_sorting('review');
$table->no_sorting('performance');
$table->no_sorting('concern');
$table->no_sorting('progress');


$table->initialbars(true);
$table->collapsible(true);

$table->set_attribute('id', 'ilps');
$table->set_attribute('cellspacing', '0');
$table->set_attribute('class', 'generaltable generalbox block_lpr_center');

$table->setup();

// get the list of at risk LPRs
$ilps = $lpr_db->get_incomplete_ilps($category_id, $course_id, $table, $start_timestamp, $end_timestamp);

// if we have results
if(!empty($ilps)) {

	

    // iterate through the result set
    foreach ($ilps as $user) {
        // grab the learner's photo
        $picture = print_user_picture(
            $user->id,
            ((empty($course->id) ? $SITE->id : $course->id)),
            $user->picture,
            false,
            true
        );

        // make a link to the learner's profile
        $profilelink = '<strong><a href="'.$CFG->wwwroot
            .'/user/view.php?id='.$user->id.
            (empty($course->id) ? null : '&amp;course='.$course->id).'">'.
            $user->firstname.' '.$user->lastname.'</a></strong>';
			
			$student_status	=	$lpr_db->get_student_status($user->id,$start_timestamp, $end_timestamp);
			
			switch ($student_status->status) {
				case 2: 
					$s_status = '<span style="color: red;">RED</span>';
					break;
				case 1: 
					$s_status = '<span style="color: orange;">AMBER</span>';
					break;
				case 0: 
					$s_status = '<span style="color: green;">GREEN</span>';
					break;
			}
			

			$target_set =	$lpr_db->count_targets(array("$user->id"=>$user->id),TARGETS_SET,$category_id,$course_id,$start_timestamp, $end_timestamp);
			$target_complete =	$lpr_db->count_targets(array("$user->id"=>$user->id),TARGETS_COMPLETE,$category_id,$course_id,$start_timestamp, $end_timestamp);
			
			$targets	= "{$target_complete}/{$target_set}";
		
			$tutor_carried_out		=	$lpr_db->count_concern_posts(array("$user->id"=>$user->id),TUTOR_REVIEW,$category_id,$course_id,$start_timestamp, $end_timestamp,false);
					
			$attendance	=		$lpr_db->get_attendance_learner($user->id,$category_id,$course_id,$start_timestamp, $end_timestamp);
		
			$performance_post	=	$lpr_db->count_concern_posts(array("$user->id"=>$user->id),POSTS_PERFORMANCE,$category_id,$course_id,$start_timestamp, $end_timestamp);
			$concern_posts		=	$lpr_db->count_concern_posts(array("$user->id"=>$user->id),POSTS_CONCERNS,$category_id,$course_id,$start_timestamp, $end_timestamp);
			$progress_posts		=	$lpr_db->count_concern_posts(array("$user->id"=>$user->id),POSTS_PROGRESS,$category_id,$course_id,$start_timestamp, $end_timestamp);
			
        // make the link to view the ILP

		
			
		
        $actions  = "<a href='{$CFG->wwwroot}/blocks/ilp/view.php?id={$user->id}&amp;course={$course_id}'>".get_string('viewilp', 'block_lpr')."</a>";

        // add the row to the table
        $table->add_data(
            array(
                $picture,
                $profilelink,
				$s_status,
				number_format($attendance->attendance,0),
				number_format($attendance->punctuality,0),
				$targets,
				$tutor_carried_out,
				$performance_post,
				$concern_posts,
				$progress_posts	,
                $actions
            )
        );
    }
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

// now that we've got all the data we need, display the HTML
require_once("{$CFG->dirroot}/blocks/lpr/views/list_incomplete.html");

// print the footer
print_footer();
?>