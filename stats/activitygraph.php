<?php

    require_once('../config.php');
    require_once("../course/lib.php");
	require_once($CFG->dirroot.'/lib/graphlib.php');

	$timestamp = required_param('ts', PARAM_INT);

    require_login(); 
	
    $graph = new graph(750,400);
	$graph->parameter['legend'] = 'outside-bottom'; 
    // Possible values for legend: 'top-left', 'top-right', 'bottom-left', 'bottom-right', 
    //                              'outside-top', 'outside-bottom', 'outside-left', or 'outside-right'
	$graph->parameter['legend_size'] = 10;
	$graph->parameter['x_axis_angle'] = 0;
	$graph->parameter['title'] = false; // moodle will do a nicer job.
	$graph->y_tick_labels = null;
	
	$colors = array('green', 'blue', 'red', 'purple', 'yellow', 'olive', 'navy', 'maroon', 'gray', 'ltred', 'ltltred', 'ltgreen', 'ltltgreen', 'orange', 'ltorange', 'ltltorange', 'lime', 'ltblue', 'ltltblue', 'fuchsia', 'aqua', 'grayF0', 'grayEE', 'grayDD', 'grayCC', 'gray33', 'gray66', 'gray99');
	$colorindex = 0;
	
	//$timestamp_day_end = strtotime('+8 hours', $timestamp);

	$graph->y_order = array('logins', 'course_views', 'quiz', 'feedback');
	$graph->y_format['logins'] = array('colour' => 'red', 'line' => 'line', 'legend' => 'User Logins');
	$graph->y_format['course_views'] = array('colour' => 'blue', 'line' => 'line', 'legend' => 'Course Activity');
	$graph->y_format['quiz'] = array('colour' => 'gray', 'line' => 'line', 'legend' => 'Quiz Activity');
	$graph->y_format['feedback'] = array('colour' => 'green', 'line' => 'line', 'legend' => 'Feedback');

    $timestamp -= 1800;
	
	for ($i=0; $i <= 16; $i++) {

		$end_timestamp = strtotime('+30 minutes', $timestamp);

        $time_now = time();
        // If timestamp is today and half hour not completed: don't show
        if ($time_now - $timestamp <= 86400 && $end_timestamp > ($time_now + 1800)) {
            break;
        }
		$graph->x_data[] = date('H:i', $timestamp + 1800);

		// Get number of distinct user logins for this time period
		$query = sprintf("SELECT COUNT(DISTINCT userid) AS no_logins  FROM ".$CFG->prefix."log WHERE time > %d AND time < %d and module = 'user' and action ='login'", 
			$timestamp,
			$end_timestamp
		);
        $no_logins = 0;
		if ($user_logins = get_records_sql($query)) {
			foreach($user_logins as $login) {
				$no_logins = $login->no_logins;
			}
		}
		// Get number of course views
		$query = sprintf("SELECT COUNT(id) AS course_views FROM ".$CFG->prefix."log WHERE time > %d AND time < %d AND module = 'course'",
			$timestamp,
			$end_timestamp
		);
		$c_views = 0;
		if ($course_views = get_records_sql($query)) {
			foreach($course_views as $course) {
				$c_views = $course->course_views;
			}
		}
		
		// Get number of feedback views
		$query = sprintf("SELECT COUNT(DISTINCT id) AS feedback FROM ".$CFG->prefix."log WHERE time > %d AND time < %d and module = 'feedback'", 
			$timestamp,
			$end_timestamp
		);
		$feedback_stats = 0;
		if ($feedbacks = get_records_sql($query)) {
			foreach($feedbacks as $feedback) {
				$feedback_stats = $feedback->feedback;
			}
		}
		
		// Get number of quiz views
		
		$query = sprintf("SELECT COUNT(DISTINCT(id)) AS quiz_activity FROM ".$CFG->prefix."log WHERE time > %d AND time < %d and module = 'quiz' AND action IN ('view', 'attempt', 'continue attemp', 'close attempt', 'report', 'view all', 'review')",
			$timestamp,
			$end_timestamp
		);
		$quiz_stats = 0;
		if ($quizzes = get_records_sql($query)) {
			foreach($quizzes as $quiz) {
				$quiz_stats = $quiz->quiz_activity;
			}
		}
		
		$graph->y_data['logins'][] = $no_logins;
		$graph->y_data['course_views'][] = $c_views;
		$graph->y_data['quiz'][] = $quiz_stats;
		$graph->y_data['feedback'][] = $feedback_stats;
		

		$timestamp = $end_timestamp;
	}

	$graph->draw_stack();
	
?>
