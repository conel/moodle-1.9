<?php // $Id: analysis_course.php,v 1.5.2.3 2008/07/18 14:54:43 agrabs Exp $

	/***
	*  Shows an analysed view of a feedback on the mainsite
	*
	*  @version $Id: analysis_course.php,v 1.5.2.3 2008/07/18 14:54:43 agrabs Exp $
	*  @author Andreas Grabs
	*  @license http://www.gnu.org/copyleft/gpl.html GNU Public License
	*  @package feedback
	*  @modified	nkowald		2009-11-20
	*
	***/


    require_once("../../config.php");
    require_once("lib.php");

	if (isset($_GET['reset']) && $_GET['reset'] == 1 && (isset($_SERVER['HTTP_REFERER']) && !(strpos($_SERVER['HTTP_REFERER'], '&reset=1'))) ) {
		unset($_SESSION['c']);
	} else {
		// nkowald - 24-08-2010 - If coming from 'Show Responses' tab, fill last used filters
		if (isset($_POST) && count($_POST) > 0) {
			$_SESSION['c']['filters_post'] = $_POST;
		}
		if (isset($_SESSION['c']['filters_post']) && $_SESSION['c']['filters_post'] != '') {
			foreach ($_SESSION['c']['filters_post'] as $key => $value) {
				$_POST[$key] = $value;
			}
		}
		// nkowald - 24-08-2010
	}

    // $SESSION->feedback->current_tab = 'analysis';
    $current_tab = 'analysis';
 
    $id = required_param('id', PARAM_INT);  //the POST dominated the GET

    // if the learner survey feedback, redirect to specific file
    // get params
    $query_string = $_SERVER['QUERY_STRING'];

    if ($id == 41205) {
        header('Location: analysis_course_learner_survey.php?'.$query_string);
		exit;
    } else if ($id == 57969) {
		header('Location: analysis_course_exit_survey.php?'.$query_string);
		exit;
	} else if ($id == 71228) {
		header('Location: analysis_course_learner_survey_1011.php?'.$query_string);
		exit;
	} else if ($id == 71229) {
		header('Location: analysis_course_exit_survey_1011.php?'.$query_string);
		exit;
	} else if ($id == 197341) {
		header('Location: analysis_course_learner_survey_1112.php?'.$query_string);
		exit;
    } else if ($id == 216601) {
        header('Location: analysis_course_teaching_and_learner_survey_1112.php?'.$query_string);
        exit;
    } else if ($id == 218909) {
        header('Location: analysis_course_exit_survey_1112.php?'.$query_string);
        exit;
    } else if ($id == 228782) {
        header('Location: analysis_course_learner_survey_1213.php?'.$query_string);
        exit;	
    } else if ($id == 228783) {
        header('Location: analysis_course_learner_record_1213.php?'.$query_string);
        exit;
    }
    
    if ($id) {
        if (! $cm = get_coursemodule_from_id('feedback', $id)) {
            error("Course Module ID was incorrect");
        }
     
        if (! $course = get_record("course", "id", $cm->course)) {
            error("Course is misconfigured");
        }
     
        if (! $feedback = get_record("feedback", "id", $cm->instance)) {
            error("Course module is incorrect");
        }
    }
    $capabilities = feedback_load_capabilities($cm->id);

    require_login($course->id, true, $cm);
    
    if(!((intval($feedback->publish_stats) == 1) || $capabilities->viewreports)) {
        error(get_string('error'));
    }

   /* ---------------- Settings --------------------- 
    * If you're adding the same filters on new surveys
    * these are the only settings you'll need to change
   */
	require('FeedbackFilters.class.php');

	// set academic year of this survey
	$this_ac_year = '1213';
	$ff = new FeedbackFilters($feedback, $this_ac_year);

	// each survey requires a unique sess key
	$uniq_sess_name = 'idc1213'; 

	// does the survey have the site filter?
	$has_site = true;

   /* ---------------------------------------------- */
   
    require('analysis_body.php');
    
?>
