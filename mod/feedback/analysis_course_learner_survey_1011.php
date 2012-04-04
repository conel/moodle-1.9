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

   require_once("../../config.php");
   require_once("lib.php");

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
		require_once('FeedbackFilters.class.php');

		// set academic year of this survey
		$this_ac_year = 1011;
		$ff = new FeedbackFilters($feedback, $this_ac_year);

		// each survey requires a unique sess key
		$uniq_sess_name = 'cls1011'; 

   /* ---------------------------------------------- */


   if (isset($_GET['reset']) && $_GET['reset'] == 1 && (isset($_SERVER['HTTP_REFERER']) && !(strpos($_SERVER['HTTP_REFERER'], 'reset=1'))) ) {
       unset($_SESSION[$uniq_sess_name]);
   } else {
       // nkowald - 24-08-2010 - If coming from 'Show Responses' tab, fill last used filters
       if (isset($_POST) && count($_POST) > 0) {
           $_SESSION[$uniq_sess_name]['filters_post'] = $_POST;
       }
       if ($_SESSION[$uniq_sess_name]['filters_post'] != '') {
           foreach ($_SESSION[$uniq_sess_name]['filters_post'] as $key => $value) {
               $_POST[$key] = $value;
           }
       }
   }

   $coursefilter               = optional_param('coursefilter', 0, PARAM_INT);
   $courseitemfilter           = optional_param('courseitemfilter', 0, PARAM_INT);
   $courseitemfiltertyp        = optional_param('courseitemfiltertyp', '', PARAM_ALPHANUM);
   $searchcourse               = optional_param('searchcourse', '', PARAM_RAW);
   $courseid                   = optional_param('courseid', 0, PARAM_INT);
   $search_directorates        = optional_param('directorates', 0, PARAM_INT);
   $search_schools             = optional_param('schools', 0, PARAM_INT);
   $search_levels              = optional_param('levels', '', PARAM_RAW);
   $search_curriculum_areas    = optional_param('curriculum_areas', '', PARAM_RAW);
   $search_subcat              = optional_param('subcategory', '', PARAM_RAW);
   $filter_choice              = optional_param('filter_choice', 'filter_course', PARAM_RAW);
   $filter_gender              = optional_param('filter_gender', '', PARAM_INT);
   $filter_age                 = optional_param('filter_age', '', PARAM_INT);
   $filter_ethnicity           = optional_param('filter_ethnicity','', PARAM_RAW);
   $filter_attendance          = optional_param('filter_attendance','', PARAM_RAW);
   $filter_learning_difficulty = optional_param('filter_learning_difficulty','', PARAM_RAW);
   $filter_learning_disability = optional_param('filter_learning_disability','', PARAM_RAW);
   $filter_site                = optional_param('site','', PARAM_RAW);


   if (($searchcourse OR $courseitemfilter OR $coursefilter) AND !confirm_sesskey()) {
       // error('no sesskey defined');
   }

   // Print the page header
   $strfeedbacks = get_string("modulenameplural", "feedback");
   $strfeedback  = get_string("modulename", "feedback");
   $buttontext   = update_module_button($cm->id, $course->id, $strfeedback);

   $navlinks = array();
   $navlinks[] = array('name' => $strfeedbacks, 'link' => "index.php?id=$course->id", 'type' => 'activity');
   $navlinks[] = array('name' => format_string($feedback->name), 'link' => "", 'type' => 'activityinstance');
   $navigation = build_navigation($navlinks);

   print_header_simple(format_string($feedback->name), "", $navigation, "", "", true, $buttontext, navmenu($course, $cm));

	// print the tabs
	$current_tab = 'analysis';
	$current_page = $_SERVER['PHP_SELF'];
	include('tabs.php');

   // Don't treat these course ids as 'valid'
   $invalid_cids = array('',0,1);

   // get the items of the feedback
   $items = $ff->getFeedbackItems();

   //print the analysed items
   print_box_start('generalbox boxaligncenter boxwidthwide');

   /* Show Courses with no feedback responses */
   if (isset($_GET['noresponses']) && $_GET['noresponses'] == 1) {

       echo '<a href="'.$current_page.'?id=' . $id . '&courseid=0&amp;reset=1">'.get_string('show_all', 'feedback').'</a>';

       // nkowald - 2009-12-09 - Get courses with the feedback block but no responses.
       $no_responses = $ff->getCoursesWithNoFeedback();
       if (count($no_responses) > 0) {
           echo '<br /><br /><hr style="height:1px;" />';
           echo "<b>Empty Feedback: ".count($no_responses)."</b><br />";
           echo '<br /><hr style="height:1px;" />';
           echo '<p><b>The following courses contain the feedback link but no responses submitted against them</b></p>';
           echo '<ol class="non_results">';
           foreach($no_responses as $link) {
               echo "<li>$link</li>";	
           }
           echo '</ul>';
       }


   } else {

       // Include JavaScript functions needed to reset values and hide options when selecting filters
       // If you're adding filter options - you will need to edit this file
       echo '<script type="text/javascript" src="feedback_functions.js"></script>';

       echo '<a href="'.$current_page.'?id=' . $id . '&amp;courseid=0&amp;reset=1">'.get_string('show_all', 'feedback').' courses</a>';

       echo '<br /><a href="'.$current_page.'?id=' . $id . '&amp;courseid='.$courseid.'&amp;noresponses=1">Show courses with no feedback responses</a><br /><br />';


		// build action to remove reset param
	   $form_action = $current_page . '?id='.$id.'&amp;courseid='.$courseid;
       echo '<form name="report" method="post" action="'.$form_action.'">';
       echo '<table width="600" cellpadding="10" id="filter_table">';

       // User clicked to view averages for a particular question
       if ($courseitemfilter > 0 && $courseitemfiltertyp != '') {

           $question_html = '';
           $question_averages = $ff->getCourseAvgForQuestion($courseitemfilter, $courseitemfiltertyp);

           if ($question_averages !== false) {

               $item = get_record('feedback_item', 'id', $courseitemfilter);

               $sep_dec   = get_string('separator_decimal', 'feedback');
               $sep_thous = get_string('separator_thousand', 'feedback');

               $question_html .= '<tr><th colspan="2">'.$item->name.'</th></tr>';
               $question_html .= '<tr><td>';
               $question_html .= '<table align="left">';
               $question_html .= '<tr><th>Course</th><th>Average</th></tr>';

               foreach ($question_averages as $c) {
                   $question_html .= '<tr>
                    <td>'.$c->shortname.'</td>
                    <td align="right">'. number_format(($c->avgvalue), 2, $sep_dec, $sep_thous).'</td>
                   </tr>';
               }
               $question_html .= '</table></td></tr>';

           } else {
               $question_html = '<tr><td>'.get_string('noresults').'</td></tr>';
           }
           echo $question_html;

       } else {

           /* Show ALL THE responses */

           echo '<tr><td class="centered">';
           echo get_string('search_course', 'feedback') . ': ';
           echo '<input type="text" name="searchcourse" value="'.s($searchcourse).'"/> <input type="submit" value="'.get_string('search').'"/>';
           echo '<input type="hidden" name="sesskey" value="' . $USER->sesskey . '" />';
           echo '<input type="hidden" name="id" value="'.$id.'" />';
           echo '<input type="hidden" name="courseitemfilter" value="'.$courseitemfilter.'" />';
           echo '<input type="hidden" name="courseitemfiltertyp" value="'.$courseitemfiltertyp.'" />';
           echo '<input type="hidden" name="courseid" value="'.$courseid.'" />';
           echo '</td></tr>';

           // Here add radio buttons for filter choices: course, directorate, school, level, curric area
           echo "<tr><td class=\"centered\">\n";
           echo '<br />Filter Results: ';
           $filter_course_checked		= ((isset($filter_choice) && ($filter_choice == 'filter_course')) || $filter_choice == '') ? ' checked="checked"' : '';
           $filter_directorate_checked	= (isset($filter_choice) && ($filter_choice == 'filter_directorate')) ? ' checked="checked"' : '';
           $filter_school_checked		= (isset($filter_choice) && ($filter_choice == 'filter_school')) ? ' checked="checked"' : '';
           $filter_level_checked		= (isset($filter_choice) && ($filter_choice == 'filter_level')) ? ' checked="checked"' : '';
           $filter_curric_checked		= (isset($filter_choice) && ($filter_choice == 'filter_curriculum_area')) ? ' checked="checked"' : '';

           echo '<input type="radio" name="filter_choice" id="filter_course" value="filter_course" '.$filter_course_checked.' onClick="showSelection(this)" /><label for="filter_course">Course</label>&nbsp;';
           echo '<input type="radio" name="filter_choice" id="filter_directorate" value="filter_directorate" '.$filter_directorate_checked.' onClick="showSelection(this)" /><label for="filter_directorate">Directorate</label>&nbsp;';
           echo '<input type="radio" name="filter_choice" id="filter_school" value="filter_school" '.$filter_school_checked.' onClick="showSelection(this)" /><label for="filter_school">School</label>&nbsp;';
           echo '<input type="radio" name="filter_choice" id="filter_level" value="filter_level" '.$filter_level_checked.' onClick="showSelection(this)" /><label for="filter_level">Level</label>&nbsp;';
           echo '<input type="radio" name="filter_choice" id="filter_curriculum_area" value="filter_curriculum_area" '.$filter_curric_checked.' onClick="showSelection(this)" /><label for="filter_curriculum_area">Curriculum Area</label>';
           echo '<br /><br class="clear_both" />';


           /* SHOW NEXT LEVEL FILTERS: courses, directorates, schools, levels, curric areas */
           $in_charge = ($coursefilter == "0" && (!in_array($courseid, $invalid_cids)) && $filter_choice == 'filter_course') ? $courseid : $coursefilter;

           /* DIRECTORATES */

           $show_directorates = ($filter_choice == "filter_directorate") ? 'style="display:block;" ' : '';
           echo '<div id="directorates_holder"'.$show_directorates.'>';

           $directorates = $ff->getDirectoratesMenu();
           if ($directorates !== false) {
               echo'<div class="filter_directorates">Directorates: ';
               choose_from_menu($directorates, 'directorates', $search_directorates, 'choose', 'this.form.submit()');
               echo '</div>';
           }

           if ($search_directorates > 0 && $filter_choice == 'filter_directorate') {
               $directorate_courses = $ff->getCoursesForFilter($search_directorates, $searchcourse);
               if ($directorate_courses !== false) {
                   echo '<div class="filter_directorates_course">Course: ';
                   choose_from_menu($directorate_courses, 'coursefilter', $in_charge, 'choose', 'this.form.submit()');
                   echo '</div>';
               } else {
                   echo '<p><b>No courses found</b></p>';
               }
           }
           echo '<br class="clear_both" /></div>';


           /* SCHOOLS */

           $show_school = ($filter_choice == "filter_school") ? 'style="display:block;" ' : '';
           echo '<div id="school_holder"'.$show_school.'>';

           $schools = $ff->getSchoolsMenu();

           if ($schools !== false) {
               echo'<div class="filter_schools">School: ';
               choose_from_menu($schools, 'schools', $search_schools, 'choose', 'this.form.submit()');
               echo '</div>';
           }

           // Need to check if any school filters have been added
           if ($search_schools > 0 && $filter_choice == 'filter_school') {
               $school_courses = $ff->getCoursesForFilter($search_schools, $searchcourse);
               if ($school_courses !== false) {
                   echo '<div class="filter_schools_course">Course: ';
                   choose_from_menu($school_courses, 'coursefilter', $in_charge, 'choose', 'this.form.submit()');
                   echo '</div>';
               } else {
                   echo '<p><b>No courses found</b></p>';
               }
           }
           echo '<br class="clear_both" /></div>';



        /* LEVELS */

        $show_level = ($filter_choice == "filter_level") ? 'style="display:block;" ' : '';
        echo '<div id="levels_holder"'.$show_level.'>';

        $levels_arr = array(
            '1' => 'Level 1',
            '2' => 'Level 2',
            '3' => 'Level 3',
            '4' => 'Level 4',
            'G' => 'Entry 1',
            'F' => 'Entry 2',
            'E' => 'Entry 3',
            'H' => 'PreEntry'
        );

        echo'<div class="filter_levels">Level: ';
        choose_from_menu($levels_arr, 'levels', $search_levels, 'choose', 'this.form.submit()');
        echo '</div>';

        if ($search_levels != '0' && $filter_choice == 'filter_level') {

            $level_courses = $ff->getCoursesForLevelFilter($search_levels);

            if ($level_courses !== false) {
                echo '<div class="filter_levels_course">Course: ';
                choose_from_menu($level_courses, 'coursefilter', $in_charge, 'choose', 'this.form.submit()');
                echo '</div>';
            } else {
                echo '<p><b>No courses found</b></p>';
            }
        }
        echo '<br class="clear_both" /></div>';



        /* CURRICULUM AREAS */

        $show_curric = ($filter_choice == "filter_curriculum_area") ? 'style="display:block;" ' : '';
        echo '<div id="curriculum_area_holder"'.$show_curric.'>';

        $curriculum_areas = $ff->getCurriculumMenu();

        if ($curriculum_areas !== false) {
            echo'<div class="filter_curriculum_areas">Curriculum Area: ';
            choose_from_menu($curriculum_areas, 'curriculum_areas', $search_curriculum_areas, 'choose', 'this.form.submit()');
            echo '</div>';
        }

        // Need to check if any curriculum filters have been added
        if ($search_curriculum_areas > 0 && $filter_choice == 'filter_curriculum_area') {

		   $query = "SELECT id, name FROM ".$CFG->prefix."course_categories WHERE parent = $search_curriculum_areas AND visible = 1 ORDER BY name ASC";
		   if ($subcats = get_records_sql_menu($query)) {
			   echo'<div class="filter_subcat" style="margin-top:8px;">Subcategory: ';
			   choose_from_menu($subcats, 'subcategory', $search_subcat, 'choose', 'this.form.submit()');
			   echo '</div>';
		   }

		   $courses_cat = $ff->getCoursesWithSurveyAnswers($searchcourse);

            // If we found categories
            $curric_courses = array();

            if ($courses_cat !== false) {

                foreach ($courses_cat as $cat) {
                    $query = "SELECT path FROM ".$CFG->prefix."course_categories WHERE id = '".$cat->category."'";
                    $get_path = get_record_sql($query);
                    $path_ids = explode('/', $get_path->path);

                       if ($search_subcat != 0) {
                           if (in_array($search_subcat, $path_ids)) {
                               $curric_courses[] = $cat;
                           }
                       } else {
                           if (in_array($search_curriculum_areas, $path_ids)) {
                               $curric_courses[] = $cat;
                           }
                       }
                }

                // If courses found under the directorates category: display them.	
                $curric_courses_are = $ff->getCoursesFromIDs($curric_courses);

                echo '<div class="filter_curriculum_course">Course: ';
                choose_from_menu($curric_courses_are, 'coursefilter', $in_charge, 'choose', 'this.form.submit()');
                echo '</div>';

            } else {
                echo '<p><b>No courses found</b></p>';
            }

        }
		echo '<br class="clear_both" />';
        echo '</div>';


           /* COURSES */

        if ($filter_choice == "" || $filter_choice == "filter_course") {

            $show_course = ($filter_choice == "filter_course") ? 'style="display:block;" ' : '';
            echo '<div id="course_holder"'.$show_course.'>';

            $courses_found = $ff->getCoursesWithSurveyAnswers($searchcourse);
            if ($courses_found !== false) {
				$found_courses = $ff->getCoursesFromIDs($courses_found);
				echo get_string('filter_by_course', 'feedback') . ': ';
				choose_from_menu($found_courses, 'coursefilter', $in_charge, 'choose', 'this.form.submit()');
				echo'<br /><br />';
            } else {
                echo '<p><b>No courses found</b></p>';
            }

        } else {
            echo '<div id="loading_msg"></div>';
        }

        echo '</div>';



        echo '
        <table id="secondary_filter">';

            $filter_site_both       = $ff->getSelected($filter_site, '', 'select');
            $filter_site_tottenham  = $ff->getSelected($filter_site, 1, 'select');
            $filter_site_enfield    = $ff->getSelected($filter_site, 2, 'select');

        echo '<tr><td>Site:</td>
            <td>
                <select name="site" onchange="this.form.submit()">
                    <option value=""'.$filter_site_both.'>Choose...</option>
                    <option value="1"'.$filter_site_tottenham.'>Tottenham</option>
                    <option value="2"'.$filter_site_enfield.'>Enfield</option>
                </select>
            </td>
        ';

            $filter_gender_both     = $ff->getSelected($filter_gender, '', 'radio');
            $filter_gender_male     = $ff->getSelected($filter_gender, 1, 'radio');
            $filter_gender_female   = $ff->getSelected($filter_gender, 2, 'radio');

        echo '<tr><td>Gender:</td><td> 
        <input type="radio" name="filter_gender" id="filter_gender_both" value="" '.$filter_gender_both.' onClick="this.form.submit()" /><label for="filter_gender_both">Both</label>
        <input type="radio" name="filter_gender" id="filter_gender_male" value="1" '.$filter_gender_male.' onClick="this.form.submit()" /><label for="filter_gender_male">Male</label>';
        echo '&nbsp;';
        echo '<input type="radio" name="filter_gender" id="filter_gender_female" value="2" '.$filter_gender_female.' onClick="this.form.submit()" /><label for="filter_gender_female">Female</label>
        </td></tr>';

           $filter_age_all     = $ff->getSelected($filter_age, '', 'radio');
           $filter_age_14_16   = $ff->getSelected($filter_age, 1, 'radio');
           $filter_age_16_19   = $ff->getSelected($filter_age, 2, 'radio');
           $filter_age_20_25   = $ff->getSelected($filter_age, 3, 'radio');
           $filter_age_26      = $ff->getSelected($filter_age, 4, 'radio');

        echo '<tr><td>Age:</td><td>
        <input type="radio" name="filter_age" id="filter_age_all" value="" '.$filter_age_all.' onClick="this.form.submit()" /><label for="filter_age_all">All Ages</label>
        <input type="radio" name="filter_age" id="filter_age_14_16" value="1" '.$filter_age_14_16.' onClick="this.form.submit()" /><label for="filter_age_14_16">14-16</label>
        <input type="radio" name="filter_age" id="filter_age_16_19" value="2" '.$filter_age_16_19.' onClick="this.form.submit()" /><label for="filter_age_16_19">16-19</label>
        <input type="radio" name="filter_age" id="filter_age_20_25" value="3" '.$filter_age_20_25.' onClick="this.form.submit()" /><label for="filter_age_20_25">20-25</label>
        <input type="radio" name="filter_age" id="filter_age_26" value="4" '.$filter_age_26.' onClick="this.form.submit()" /><label for="filter_age_26">26+</label>
        </td></tr>';

            $filter_attend      = $ff->getSelected($filter_attendance, '', 'radio');
            $filter_attend_ft   = $ff->getSelected($filter_attendance, 1, 'radio');
            $filter_attend_pt   = $ff->getSelected($filter_attendance, 2, 'radio');

        echo '<tr><td>Attendance:</td>
            <td>
        <input type="radio" name="filter_attendance" value="" '.$filter_attend.' id="filter_attendance_all" onClick="this.form.submit()" /><label for="filter_attendance_all">All</label>
        <input type="radio" name="filter_attendance" value="1" '.$filter_attend_ft.' id="filter_attendance_full_time" onClick="this.form.submit()" /><label for="filter_attendance_full_time">Full Time</label>
        <input type="radio" name="filter_attendance" value="2" '.$filter_attend_pt.' id="filter_attendance_part_time" onClick="this.form.submit()" /><label for="filter_attendance_part_time">Part Time Day</label></td></tr>';

            $filter_enthnic     = $ff->getSelected($filter_ethnicity, '', 'select');
            $filter_enthnic_1   = $ff->getSelected($filter_ethnicity, 1, 'select');
            $filter_enthnic_2   = $ff->getSelected($filter_ethnicity, 2, 'select');
            $filter_enthnic_3   = $ff->getSelected($filter_ethnicity, 3, 'select');
            $filter_enthnic_4   = $ff->getSelected($filter_ethnicity, 4, 'select');
            $filter_enthnic_5   = $ff->getSelected($filter_ethnicity, 5, 'select');
            $filter_enthnic_6   = $ff->getSelected($filter_ethnicity, 6, 'select');
            $filter_enthnic_7   = $ff->getSelected($filter_ethnicity, 7, 'select');
            $filter_enthnic_8   = $ff->getSelected($filter_ethnicity, 8, 'select');
            $filter_enthnic_9   = $ff->getSelected($filter_ethnicity, 9, 'select');
            $filter_enthnic_10  = $ff->getSelected($filter_ethnicity, 10, 'select');
            $filter_enthnic_11  = $ff->getSelected($filter_ethnicity, 11, 'select');
            $filter_enthnic_12  = $ff->getSelected($filter_ethnicity, 12, 'select');
            $filter_enthnic_13  = $ff->getSelected($filter_ethnicity, 13, 'select');
            $filter_enthnic_14  = $ff->getSelected($filter_ethnicity, 14, 'select');
            $filter_enthnic_15  = $ff->getSelected($filter_ethnicity, 15, 'select');
            $filter_enthnic_16  = $ff->getSelected($filter_ethnicity, 16, 'select');

        echo '<tr><td>Ethnic Origin:</td>
        <td>
        <select name="filter_ethnicity" onChange="this.form.submit()">
            <option value="" '.$filter_ethnic.'>Choose...</option>
            <option value="1" '.$filter_ethnic_1.'>Asian or Asian British - Bangladeshi</option>
            <option value="2" '.$filter_ethnic_2.'>Asian or Asian British - Indian</option>
            <option value="3" '.$filter_ethnic_3.'>Asian or Asian British - Pakistani</option>
            <option value="4" '.$filter_ethnic_4.'>Asian or Asian British - any other background</option>
            <option value="5" '.$filter_ethnic_5.'>Black or Black British - African</option>
            <option value="6" '.$filter_ethnic_6.'>Black or Black British - Caribbean</option>
            <option value="7" '.$filter_ethnic_7.'>Black or Black British - any other background</option>
            <option value="8" '.$filter_ethnic_8.'>Chinese</option>
            <option value="9" '.$filter_ethnic_9.'>Mixed - White & Asian</option>
            <option value="10" '.$filter_ethnic_10.'>Mixed - White & Black African</option>
            <option value="11" '.$filter_ethnic_11.'>Mixed - White & Black Caribbean</option>
            <option value="12" '.$filter_ethnic_12.'>Mixed - Any Other Mixed Background</option>
            <option value="13" '.$filter_ethnic_13.'>White - British</option>
            <option value="14" '.$filter_ethnic_14.'>White - Irish</option>
            <option value="15" '.$filter_ethnic_15.'>White - Any Other Background</option>
            <option value="16" '.$filter_ethnic_16.'>Other - Any Other</option>
        </select>
            </td></tr>';

            // Declared Learning Difficulty
            $filter_ld_checked      = $ff->getSelected($filter_learning_difficulty, '', 'radio');
            $filter_ld_checked_yes  = $ff->getSelected($filter_learning_difficulty, 1, 'radio');
            $filter_ld_checked_no   = $ff->getSelected($filter_learning_difficulty, 2, 'radio');

        echo '<tr><td>
        Declared Learning Difficulty:</td><td>
        <input type="radio" name="filter_learning_difficulty" value="" id="filter_ld" '.$filter_ld_checked.' onClick="this.form.submit()" /><label for="filter_ld">All</label>
        <input type="radio" name="filter_learning_difficulty" value="1" id="filter_ld_yes" '.$filter_ld_checked_yes.' onClick="this.form.submit()" /><label for="filter_ld_yes">Yes</label>
        <input type="radio" name="filter_learning_difficulty" value="2" id="filter_ld_no" '.$filter_ld_checked_no.' onClick="this.form.submit()" /><label for="filter_ld_no">No</label></td></tr>';

        // Declared Learning Disability
        $filter_ldb_checked     = $ff->getSelected($filter_learning_disability, '', 'radio');
        $filter_ldb_checked_yes = $ff->getSelected($filter_learning_disability, 1, 'radio');
        $filter_ldb_checked_no  = $ff->getSelected($filter_learning_disability, 2, 'radio');

        echo '<tr><td>
        Declared Disability:</td><td>
        <input type="radio" name="filter_learning_disability" value="" id="filter_ldb" '.$filter_ldb_checked.' onClick="this.form.submit()" /><label for="filter_ldb">All</label>
        <input type="radio" name="filter_learning_disability" value="1" id="filter_ldb_yes" '.$filter_ldb_checked_yes.' onClick="this.form.submit()" /><label for="filter_ldb_yes">Yes</label>
        <input type="radio" name="filter_learning_disability" value="2" id="filter_ldb_no" '.$filter_ldb_checked_no.' onClick="this.form.submit()" /><label for="filter_ldb_no">No</label>
        </td></tr></table>
        </td></tr>	
        </table>';

           $mappings = $ff->getFilterIDsAndValues();
           // Create variables from mappings
           // $site_id, $gender_id, $age_id, $ethnic_id, $attendance_id, $dld_id, $dldb_id
           extract($mappings, EXTR_OVERWRITE);

        $itemnr = 0;
        //print the items in an analysed form

        $ubs = array(); // users by gender site
        $ubg = array(); // users by gender array
        $uba = array(); // users by age array
        $ube = array(); // users by ethnicity array
        $ubat = array(); // users by attendance
        $ubdld = array(); // users by declared learning difficulty
        $ubdldb = array(); // users by declared disability

        $completed_ids_to_search = array();

        // Site
        if (($filter_site != '' && $site_id != '')) {
               $completed_ids_to_search = $ubs = 
               $ff->getUsersByQandA($site_id, $filter_site);
        }
           // Gender
        if (($filter_gender != '' && $gender_id != '')) {
               $completed_ids_to_search = $ubg = 
               $ff->getUsersByQandA($gender_id, $filter_gender);
        }
           // Age
        if (($filter_age != '' && $age_id != '')) {
               $completed_ids_to_search = $uba = 
               $ff->getUsersByQandA($age_id, $filter_age);
        }
           // Ethnicity
        if (($filter_ethnicity != '' && $ethnic_id != '')) {
               $completed_ids_to_search = $ube = 
               $ff->getUsersByQandA($ethnic_id, $filter_ethnicity);
        }
           // Attendance
        if (($filter_attendance != '' && $attendance_id != '')) {
               $completed_ids_to_search = $ubat = 
               $ff->getUsersByQandA($attendace_id, $filter_attendance);
        }
        // Declared Learning Difficulty
        if (($filter_learning_difficulty != '' && $dld_id != '')) {
               $completed_ids_to_search = $ubdld = 
               $ff->getUsersByQandA($dld_id, $filter_learning_difficulty);
        }
        // Declared Disability
        if (($filter_learning_disability != '' && $dldb_id != '')) {
               $completed_ids_to_search = $ubdldb = 
               $ff->getUsersByQandA($dldb_id, $filter_learning_disability);
        }

        // This needs to intersect all non-empty arrays top-down
        $filter_count = 0;
        $to_intersect = array();

        if ($ubs !== false && count($ubs) > 0) {
            $filter_count++;
            $to_intersect[] = $ubs;
        }
        if ($ubg !== false && count($ubg) > 0) {
            $filter_count++;
            $to_intersect[] = $ubg;
        }
        if ($ubg !== false && count($uba) > 0) {
            $filter_count++;
            $to_intersect[] = $uba;
        }
        if ($ube !== false && count($ube) > 0) {
            $filter_count++;
            $to_intersect[] = $ube;
        }
        if ($ubat !== false && count($ubat) > 0) {
            $filter_count++;
            $to_intersect[] = $ubat;
        }
        if ($ubdld !== false && count($ubdld) > 0) {
            $filter_count++;
            $to_intersect[] = $ubdld;
        }
        if ($ubdldb !== false && count($ubdldb) > 0) {
            $filter_count++;
            $to_intersect[] = $ubdldb;
        }

        // If two or more filters active: intersect these arrays
        switch ($filter_count) {

            case 0:
            // do nothing
            break;	

            case 1:
            // do nothing
            break;

            case 2:
            $completed_ids_to_search = array_intersect($to_intersect[0],$to_intersect[1]);
            break;

            case 3:
            $completed_ids_to_search = array_intersect($to_intersect[0],$to_intersect[1]);
            $completed_ids_to_search = array_intersect($completed_ids_to_search,$to_intersect[2]);
            break;

            case 4:
            $completed_ids_to_search = array_intersect($to_intersect[0],$to_intersect[1]);
            $completed_ids_to_search = array_intersect($completed_ids_to_search,$to_intersect[2]);
            $completed_ids_to_search = array_intersect($completed_ids_to_search,$to_intersect[3]);
            break;

            case 5:
            $completed_ids_to_search = array_intersect($to_intersect[0],$to_intersect[1]);
            $completed_ids_to_search = array_intersect($completed_ids_to_search,$to_intersect[2]);
            $completed_ids_to_search = array_intersect($completed_ids_to_search,$to_intersect[3]);
            $completed_ids_to_search = array_intersect($completed_ids_to_search,$to_intersect[4]);
            break;

            case 6:
            $completed_ids_to_search = array_intersect($to_intersect[0],$to_intersect[1]);
            $completed_ids_to_search = array_intersect($completed_ids_to_search,$to_intersect[2]);
            $completed_ids_to_search = array_intersect($completed_ids_to_search,$to_intersect[3]);
            $completed_ids_to_search = array_intersect($completed_ids_to_search,$to_intersect[4]);
            $completed_ids_to_search = array_intersect($completed_ids_to_search,$to_intersect[5]);
            break;

            case 7:
            $completed_ids_to_search = array_intersect($to_intersect[0],$to_intersect[1]);
            $completed_ids_to_search = array_intersect($completed_ids_to_search,$to_intersect[2]);
            $completed_ids_to_search = array_intersect($completed_ids_to_search,$to_intersect[3]);
            $completed_ids_to_search = array_intersect($completed_ids_to_search,$to_intersect[4]);
            $completed_ids_to_search = array_intersect($completed_ids_to_search,$to_intersect[5]);
            $completed_ids_to_search = array_intersect($completed_ids_to_search,$to_intersect[6]);
            break;

        }

        $no_feedback_error = 'No feedback exists for this course.';

        // Create and clause based on filters selected
        $and_clause = '';

        if ($in_charge != 0 && $in_charge != '') {
            $and_clause .= ' AND fbv.course_id = '.$in_charge.' ';
        }

        // Build completed and clause if filters set
        if ($filter_site != '' || $filter_gender != '' || $filter_age != '' || $filter_ethnicity != '' || $filter_attendance != '' || $filter_learning_difficulty != '') {
            $completed_ids = implode(',', $completed_ids_to_search); // convert array to csv format
            $and_clause .= ' AND fbv.completed IN ('.$completed_ids.')';
            $no_feedback_error = 'No results match this level of filtering, please redefine your filter options.';
        }

        if ($search_directorates > 0) {
            if ($to_search = $ff->getCSVIDsFromCourse($directorate_courses)) {
				$and_clause .= ' AND fbv.course_id IN ('.$to_search.')';
			}
        } elseif ($search_schools > 0) {
            if ($to_search = $ff->getCSVIDsFromCourse($school_courses)) {
				$and_clause .= ' AND fbv.course_id IN ('.$to_search.')';
			}
        } elseif ($search_levels != 0 && $search_levels != '') {
            if ($to_search = $ff->getCSVIDsFromCourse($level_courses)) {
				$and_clause .= ' AND fbv.course_id IN ('.$to_search.')';
			}
        } elseif ($search_curriculum_areas > 0 && count($curric_courses_are) > 0) {
            if ($to_search = $ff->getCSVIDsFromCourse($curric_courses_are)) {
				$and_clause .= ' AND fbv.course_id IN ('.$to_search.')';
			}
        }

        $_SESSION['and_clause'] = $and_clause;

        if (is_array($items)){
            echo '<b>'.get_string('questions', 'feedback').': ' .sizeof($items). ' </b><br />';
        }

        $mygroupid = false;
		$total_complete = feedback_get_completeds_group_count($feedback, $mygroupid, $coursefilter, '');
        $completed_count = feedback_get_completeds_group_count($feedback, $mygroupid, $coursefilter, $and_clause);
        $completed_count = ($completed_count === false) ? 0 : $completed_count;

		echo '<b>'.get_string('completed_feedbacks', 'feedback').': '.$completed_count. '</b>';
		if ($total_complete != $completed_count) { echo ' of '.$total_complete; }
		echo '<br />';

        echo '</form>';

        // nkowald - 2009-12-08 - Had to move export button below filters as required filter code for export
        if ($capabilities->viewreports) {
            echo '<div style="text-align:center;" class="bogus">';
                $export_button_options = array(
                   'sesskey'=>$USER->sesskey, 
                   'id'=>$id, 
                   'coursefilter'=>$in_charge, 
                   'and_clause'=>$and_clause
               );
            $export_button_label = get_string('export_to_excel', 'feedback');
            print_single_button('analysis_to_excel.php', $export_button_options, $export_button_label, 'post');
        }


        echo '<hr />';

        $has_feedback = FALSE;

        // work out if this course has any feedback against it
        if ($and_clause == '') {
           $has_feedback = $ff->feedbackHasResponses();
        } else {
			if ($completed_count > 0) {
				$has_feedback = TRUE;
			}
        }
        if ($has_feedback) {

            foreach($items as $item) {

                if ($item->hasvalue == 0) continue;
                echo '<table width="100%" class="generalbox">';	
                //get the class from item-typ
                $itemclass = 'feedback_item_'.$item->typ;
                //get the instance of the item-class
                $itemobj = new $itemclass();
                $itemnr++;
                $printnr = ($feedback->autonumbering) ? $itemnr.'.' : '';

                $where_clause_to_use = '1=1 '. $and_clause .' AND item = '.$item->id.'';

                // this will handle everything now - yay, less lines of code.
                $itemobj->print_analysed($item, $printnr, $mygroupid, $in_charge, $where_clause_to_use);

                if (eregi('rated$', $item->typ)) {
                        echo '<tr><td colspan="2"><a href="#" onclick="setcourseitemfilter('.$item->id.',\''.$item->typ.'\'); return false;">'.
                        get_string('sort_by_course', 'feedback').'</a></td></tr>'; 
                }

                echo '</table>';
            }

        } else {
            echo "<p><b>$no_feedback_error</b></p>";
        }

    }
}

echo '</div>';

   print_box_end();
   print_footer($course);

?>
