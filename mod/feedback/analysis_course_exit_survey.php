<?php

/***
*  Shows an analysed view of a feedback on the mainsite
*
*  @version $Id: analysis_course.php,v 1.5.2.3 2008/07/18 14:54:43 agrabs Exp $
*  @author Andreas Grabs
*  @license http://www.gnu.org/copyleft/gpl.html GNU Public License
*  @package feedback
*  @modified    nkowald     2012-04-02
*
***/

   require("../../config.php");
   require("lib.php");

   $id = required_param('id', PARAM_INT);  //the POST dominated the GET
   if ($id) {
       if (!$cm = get_coursemodule_from_id('feedback', $id)) {
           error("Course Module ID was incorrect");
       }
       if (!$course = get_record("course", "id", $cm->course)) {
           error("Course is misconfigured");
       }
       if (!$feedback = get_record("feedback", "id", $cm->instance)) {
           error("Course module is incorrect");
       }
   }

   require_login($course->id, true, $cm);

   $capabilities = feedback_load_capabilities($cm->id);
   if(!((intval($feedback->publish_stats) == 1) || $capabilities->viewreports)) {
       error(get_string('error'));
   }

   /* ---------------- Settings --------------------- 
    * If you're adding the same filters on new surveys
    * these are the only settings you'll need to change
   */
	require('FeedbackFilters.class.php');

	// set academic year of this survey
	$this_ac_year = '0910';
	$ff = new FeedbackFilters($feedback, $this_ac_year);

	// each survey requires a unique sess key
	$uniq_sess_name = 'ces'; 

	// does the survey have the site filter?
	$has_site = false;

   /* ---------------------------------------------- */

    require('analysis_body.php');

?>
