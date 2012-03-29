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

// include the permissions check
require_once("{$CFG->dirroot}/lib/moodlelib.php");

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

// nkowald - 2011-10-20 - Adding page load time to logs
$time = microtime();
$time = explode(' ', $time);
$time = $time[1] + $time[0];
$timer_start = $time;

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

$config = get_config('project/lpr');
// nkowald - 2011-09-17 - academic year should NOT be stored in the database, overwrite it here
// Get four digit academic year code
$now = time();
$query = "SELECT ac_year_code FROM mdl_terms WHERE term_start_date < $now and term_end_date > $now";
if ($ac_years = get_records_sql($query)) {
	foreach($ac_years as $year) {
	   $ac_year = $year->ac_year_code; 
	}
	$config->academicyear = $ac_year;
}

if (isset($config->academicyear)) {
	$config->academicyear = $ac_year;
}

$tutor_review_count = array();
$report_review_count = array();

$incomplete_tutor_review = array();
$incomplete_report_review = array();


//arrays to hold target information
$target_set = array();
$target_complete = array();
$targets_avg	=	array();
$target_outstanding = array();

//arrays to hold student status information
$red_status = array();
$amber_status = array();
$green_status = array();

//arrays to hold concern_post table information
$performance_post = array();
$concern_posts = array();
$progress_posts = array();

//arrays to hold subject report information
$subject_reports = array();
$subject_reports_complete = array();
$subject_reports_out = array();
$subject_avg = array();

$unfinished_count = array();
$finsihed_count = array();

$tutor_carried_out = array();
$tutor_reviews_out = array();


$term_start_time	=	array();
$term_end_time		=	array();


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

} else	{


	// Only load items if category id is set
    if ($category_id !== NULL) {
	
	for($i=0;$i < $lpr_db->count_terms($config->academicyear);$i++) {
	
		$term = $lpr_db->get_terms($config->academicyear,$i+1);
		
		if(!empty($course_id)) {

			// get the students in this course
			$students = $lpr_db->get_students($course_id);

			//count incomplete ilps
			$countof = $lpr_db->count_incomplete_ilps($students,$term->term_start_date, $term->term_end_date);
				
			$learner_count[$i] = $countof->students;
			$unfinished_count[$i] = $countof->incomplete;
			
			$number_students	=	$learner_count[$i];
			
			$finished_count[$i]	= $learner_count[$i] - $unfinished_count[$i];
			
			$finished_avg[$i]	=	($unfinished_count[$i] / $learner_count[$i]) * 100;
			
			$finished_avg[$i]	= number_format($finished_avg[$i],0);
			
			//count the various student status
			$red_status[$i]			=		$lpr_db->count_concern_status($students,CONCERN_RED,$term->term_start_date, $term->term_end_date);
			$amber_status[$i]		=		$lpr_db->count_concern_status($students,CONCERN_AMBER,$term->term_start_date, $term->term_end_date);
			$green_status[$i]		=		$learner_count[$i] - ($red_status[$i]	+ $amber_status[$i]);
			//changed at the request of CONEL 
			//$green_status[$i]		=		$lpr_db->count_concern_status($learners,CONCERN_GREEN,$term->term_start_date, $term->term_end_date);
			
		
			//count targets
			
			// nkowald - 2011-09-29 - These should NOT be course based
			//$target_set[$i] =	$lpr_db->count_targets($students,TARGETS_SET,null,$course_idcourse_id,$term->term_start_date, $term->term_end_date);
			//$target_complete[$i] =	$lpr_db->count_targets($students,TARGETS_COMPLETE,null,$course_id,$term->term_start_date, $term->term_end_date);
			
			$target_set[$i] =	$lpr_db->count_targets($students,TARGETS_SET,null,null,$term->term_start_date, $term->term_end_date);
			$target_complete[$i] =	$lpr_db->count_targets($students,TARGETS_COMPLETE,null,null,$term->term_start_date, $term->term_end_date);
			
			$targets_avg[$i] = ($target_complete[$i] / $target_set[$i]) * 100;
			$targets_avg[$i]	= number_format($targets_avg[$i],0);
			//count the various concern status
			// nkowald - 2011-06-03 - Teachers may get confused that these don't line up with the page they link to, they show ALL however link shows learner view
			$performance_post[$i]	=	$lpr_db->count_concern_posts($students,POSTS_PERFORMANCE,null,$course_id,$term->term_start_date, $term->term_end_date,false);
			$concern_posts[$i]		=	$lpr_db->count_concern_posts($students,POSTS_CONCERNS,null,$course_id,$term->term_start_date, $term->term_end_date,false);
			$progress_posts[$i]		=	$lpr_db->count_concern_posts($students,POSTS_PROGRESS,null,$course_id,$term->term_start_date, $term->term_end_date,false);
			// nkowald - 2010-12-21 - Added distinct paramater to the end
			$tutor_carried_out[$i]	=	$lpr_db->count_concern_posts($students,TUTOR_REVIEW,$category_id,null,$term->term_start_date, $term->term_end_date, true);
			$tutor_reviews_out[$i] = ($learner_count[$i] - $tutor_carried_out[$i]);

			$review_avg[$i]	=	($tutor_reviews_out[$i] / $learner_count[$i] ) * 100;
			$review_avg[$i]	= 	number_format($review_avg[$i],0);
			
			$subject_reports[$i]		=	$lpr_db->count_subject_reports($students,SUBJECT_REPORTS,$term->term_start_date, $term->term_end_date);
			$subject_reports_complete[$i]	=	$lpr_db->count_subject_reports($students,SUBJECT_REPORTS_COMPLETE,$term->term_start_date, $term->term_end_date);
			
			$subject_reports_out[$i]	=	$subject_reports[$i]	-	$subject_reports_complete[$i];
			
			$subject_avg[$i] = ($subject_reports_out[$i]/$subject_reports[$i])  * 100;
			
			$subject_avg[$i]	= number_format($subject_avg[$i],0);
			
			    // count the various elements being averaged across
			$number_students = count($students);
			$lpr_count = $lpr_db->count_lprs(null, $course_id, $start_time, $end_time);
			$lpr_learners = $lpr_db->count_lpr_learners($course_id, $start_time, $end_time);
			$lpr_risks = $lpr_db->count_lpr_risks(null, $course_id, $start_time, $end_time);
			
			
		} else {

			// get the students in this category
			$learners = $lpr_db->get_students_by_cat($category_id);

			//count incomplete ilps
			$countof = $lpr_db->count_incomplete_ilps($learners,$term->term_start_date, $term->term_end_date);

			$learner_count[$i] = $countof->students;
			$unfinished_count[$i] = $countof->incomplete;
			
			$number_students	=	$learner_count[$i];
			
			$finished_count[$i]	= $learner_count[$i] - $unfinished_count[$i];
			
			$finished_avg[$i]	=	($unfinished_count[$i] / $learner_count[$i]) * 100;
			
			$finished_avg[$i]	= number_format($finished_avg[$i],0);
			//count the various student status
			$red_status[$i]			=		$lpr_db->count_concern_status($learners,CONCERN_RED,$term->term_start_date, $term->term_end_date);
			$amber_status[$i]		=		$lpr_db->count_concern_status($learners,CONCERN_AMBER,$term->term_start_date, $term->term_end_date);
			
			$green_status[$i]		=		$learner_count[$i] - ($red_status[$i]	+ $amber_status[$i]);
			//changed at the request of CONEL 
			//$green_status[$i]		=		$lpr_db->count_concern_status($learners,CONCERN_GREEN,$term->term_start_date, $term->term_end_date);
		
			//count targets
			$target_set[$i] 	=		$lpr_db->count_targets($learners,TARGETS_SET,$category_id,null,$term->term_start_date, $term->term_end_date);
			$target_complete[$i] =		$lpr_db->count_targets($learners,TARGETS_COMPLETE,$category_id,null,$term->term_start_date, $term->term_end_date);
			
			$target_outstanding[$i] = $target_set[$i] - $target_complete[$i];
			
			$targets_avg[$i] = ($target_complete[$i] / $target_set[$i]) * 100;
		
			$targets_avg[$i]	= number_format($targets_avg[$i],0);
			//count the various concern status
			$performance_post[$i]	=	$lpr_db->count_concern_posts($learners,POSTS_PERFORMANCE,$category_id,null,$term->term_start_date, $term->term_end_date,false);
			$concern_posts[$i]		=	$lpr_db->count_concern_posts($learners,POSTS_CONCERNS,$category_id,null,$term->term_start_date, $term->term_end_date,false);
			$progress_posts	[$i]	=	$lpr_db->count_concern_posts($learners,POSTS_PROGRESS,$category_id,null,$term->term_start_date, $term->term_end_date,false);
			$tutor_carried_out[$i]		=	$lpr_db->count_concern_posts($learners,TUTOR_REVIEW,$category_id,null,$term->term_start_date, $term->term_end_date,true);
			
			$tutor_reviews_out[$i] = ($learner_count[$i] - $tutor_carried_out[$i]);
			
			$review_avg[$i]	=	($tutor_reviews_out[$i] / $learner_count[$i] ) * 100;
			$review_avg[$i]	= number_format($review_avg[$i],0);
			
			
			$subject_reports[$i]		=	$lpr_db->count_subject_reports($learners,SUBJECT_REPORTS,$term->term_start_date, $term->term_end_date);
			$subject_reports_complete[$i]	=	$lpr_db->count_subject_reports($learners,SUBJECT_REPORTS_COMPLETE,$term->term_start_date, $term->term_end_date);

			$subject_reports_out[$i]	=	$subject_reports[$i]	-	$subject_reports_complete[$i];
			
			$subject_avg[$i] = ($subject_reports_out[$i] / $subject_reports[$i]) * 100;

			$subject_avg[$i]	= number_format($subject_avg[$i],0);
			// get all the sub-categories for the given category
			$cats = array_keys(get_categories((empty($category_id) ? 'none' : $category_id), null, false, true));

			// get the students in this category
			$learners = $lpr_db->get_students_by_cat($category_id);

			// count the the students and the unfinished ILPs
			$countof = $lpr_db->count_incomplete_ilps($learners, $start_time, $end_time);

			$course_count = $lpr_db->count_courses_by_cat($cats, $start_time, $end_time);

			$lpr_count = $lpr_db->count_lprs_by_cat($cats, $start_time, $end_time);
			$lpr_learners = $lpr_db->count_lpr_learners_by_cat($cats, $start_time, $end_time);
			$lpr_courses = $lpr_db->count_lpr_courses_by_cat($cats, $start_time, $end_time);
			$lpr_risks = $lpr_db->count_lpr_risks_by_cat($cats, $start_time, $end_time);
			
			
			if($category_id) {
				// get all the child courses
				$sql = "SELECT		*
						FROM		mdl_course
						WHERE		category = {$category_id}
						AND			visible		=	1
						ORDER BY	sortorder";
				$courses = get_records_sql($sql);
				
				//nd 21032011- removed this line in order to use above code which does not
				//show hidden courses and sorts the courses correctly
				//$courses = get_records('course', 'category', $category_id, "sortorder");
			}
			
		}
	}
		
	}

}

// now that we've got all the data we need, display the HTML
require_once("{$CFG->dirroot}/blocks/lpr/views/reports.html");

// nkowald - 2011-10-20 - Adding page load time to logs
$time = microtime();
$time = explode(' ', $time);
$time = $time[1] + $time[0];
$finish = $time;
$total_time = round(($finish - $timer_start), 4);
$info = "page loaded: $total_time secs";

$this_page = ($_SERVER['REQUEST_URI'] != '') ? $_SERVER['REQUEST_URI'] : 'reports.php';
$course_id = (isset($course->id)) ? $course->id : 1;
add_to_log($course_id, 'ilp', 'view e-ilp stats', $this_page, $info);

// print the footer
print_footer();
?>