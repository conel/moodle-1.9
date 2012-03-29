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
		unset($_SESSION['ces']);
	} else {
		// nkowald - 24-08-2010 - If coming from 'Show Responses' tab, fill last used filters
		if (isset($_POST) && count($_POST) > 0) {
			$_SESSION['ces']['filters_post'] = $_POST;
		}
		if ($_SESSION['ces']['filters_post'] != '') {
			foreach ($_SESSION['ces']['filters_post'] as $key => $value) {
				$_POST[$key] = $value;
			}
		}
		// nkowald - 24-08-2010
	}

    // $SESSION->feedback->current_tab = 'analysis';

    $current_tab = 'analysis';
 
    $id = required_param('id', PARAM_INT);  //the POST dominated the GET
    $coursefilter = optional_param('coursefilter', 0, PARAM_INT);
    $courseitemfilter = optional_param('courseitemfilter', 0, PARAM_INT);
    $courseitemfiltertyp = optional_param('courseitemfiltertyp', '0', PARAM_ALPHANUM);
    $searchcourse = optional_param('searchcourse', '', PARAM_RAW);
    $courseid = optional_param('courseid', false, PARAM_INT);
	$search_directorates = optional_param('directorates', 0, PARAM_INT);
	$search_schools = optional_param('schools', 0, PARAM_INT);
	$search_levels = optional_param('levels', '', PARAM_RAW);
	$search_curriculum_areas = optional_param('curriculum_areas', '', PARAM_RAW);
	// nkowald - 2011-06-02 - Change search based on level of category
    $search_subcat = optional_param('subcategory', '', PARAM_RAW);
	$filter_choice = optional_param('filter_choice', 'filter_course', PARAM_RAW);
	$filter_gender = optional_param('filter_gender', '', PARAM_INT);
	$filter_age = optional_param('filter_age', '', PARAM_INT);
	$filter_ethnicity = optional_param('filter_ethnicity','', PARAM_RAW);
	$filter_attendance = optional_param('filter_attendance','', PARAM_RAW);
	$filter_learning_difficulty = optional_param('filter_learning_difficulty','', PARAM_RAW);
	$filter_learning_disability = optional_param('filter_learning_disability','', PARAM_RAW);
	$where_clause = '';

    if (($searchcourse OR $courseitemfilter OR $coursefilter) AND !confirm_sesskey()) {
        error('no sesskey defined');
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

    /// Print the page header
    $strfeedbacks = get_string("modulenameplural", "feedback");
    $strfeedback  = get_string("modulename", "feedback");
    $buttontext   = update_module_button($cm->id, $course->id, $strfeedback);
    
    $navlinks = array();
    $navlinks[] = array('name' => $strfeedbacks, 'link' => "index.php?id=$course->id", 'type' => 'activity');
    $navlinks[] = array('name' => format_string($feedback->name), 'link' => "", 'type' => 'activityinstance');
    
    $navigation = build_navigation($navlinks);
    
    print_header_simple(format_string($feedback->name), "", $navigation, "", "", true, $buttontext, navmenu($course, $cm));

    /// print the tabs
    include('tabs.php');

	// Don't treat these course ids as 'valid'
	$invalid_cids = array('',0,1);

    //print the analysed items
    print_box_start('generalbox boxaligncenter boxwidthwide');
	
	if (isset($_GET['noresponses']) && $_GET['noresponses'] == 1) {

		/* nkowald - 2009-12-09 - Get all courses that have the feedback block on but no responses. */

		// Get list of all course ids that feature the feedback block.
		$sql = "SELECT DISTINCT pageid FROM ".$CFG->prefix."block_instance WHERE blockid = 37 AND visible = 1 ORDER BY pageid ASC";	
		$feedbacked_courses = array();
		if ($feedback_cids = get_records_sql($sql)) {
			foreach($feedback_cids as $cid) {
				$feedbacked_courses[] = $cid->pageid;
			}
		}

		// Get list of all course ids from submitted feedback values
		$sql = "SELECT DISTINCT course_id FROM ".$CFG->prefix."feedback_value WHERE course_id <> 0 ORDER BY course_id ASC";
		$submitted_courses = array();
		if ($submitted_cids = get_records_sql($sql)) {
			foreach($submitted_cids as $cid) {
				$submitted_courses[] = $cid->course_id;
			}
		}

		// Now get all courses that we collected that have no submitted values against them.
		$unanswered = array_diff($feedbacked_courses,$submitted_courses);

		// Get course ids and names to display to user
		$not_submitted = implode(',',$unanswered);
		$sql = "SELECT id, fullname, shortname, idnumber FROM mdl_course WHERE id IN ($not_submitted)";
		$no_results = array();
		if ($unanswered_results = get_records_sql($sql)) {
			// Build HTML from results
			foreach($unanswered_results as $result) {
				if ($result->id != 1) {
					$id_is = ($result->idnumber != '') ? $result->idnumber : $result->shortname;
					$no_results[] = '<a href="/course/view.php?id='.$result->id.'">'.$id_is.' - '.$result->fullname.'</a>';
				}
			}
		}

		// get the items of the feedback
		$items = get_records_select('feedback_item', 'feedback = '. $feedback->id . ' AND hasvalue = 1', 'position');

		if (is_array($items)) {
			//echo '<a href="analysis_course.php?id=' . $id . '&amp;courseid=0">'.get_string('show_all', 'feedback').' courses</a>';
			// nkowald - 2010-11-12 - Added reset param
            echo '<a href="analysis_course.php?id=' . $id . '&courseid=0&amp;reset=1">'.get_string('show_all', 'feedback').'</a>';
		}

		if (count($no_results) > 0) {
			echo '<br /><br /><hr style="height:1px;" />';
			echo "<b>Empty Feedback: ".count($no_results)."</b><br />";
			echo '<br /><hr style="height:1px;" />';
			echo '<p><b>The following courses contain the feedback link but no responses submitted against them</b></p>';
			echo '<ol class="non_results">';
			foreach($no_results as $link) {
				echo "<li>$link</li>";	
			}
			echo '</ul>';
		}


	} else {

		// Include JavaScript functions needed to reset values and hide options when selecting filters
		// If you're adding filter options - you will need to edit this file
		echo '<script type="text/javascript" src="feedback_functions.js"></script>';

		// get the items of the feedback
		$items = get_records_select('feedback_item', 'feedback = '. $feedback->id . ' AND hasvalue = 1', 'position');

		//show the count
		if(is_array($items)){
			echo '<a href="analysis_course.php?id=' . $id . '&amp;courseid=0">'.get_string('show_all', 'feedback').' courses</a>';
		} else {
			$items=array();
		}


		echo '<br /><a href="analysis_course.php?id=' . $id . '&amp;courseid='.$courseid.'&amp;noresponses=1">Show courses with no feedback responses</a><br /><br />';

		/*
		// nkowald - uncomment this to view POST variables and debug
		echo '<pre>';
		var_dump($_POST);
		echo '</pre>';
		*/
		
		echo '<form name="report" method="post">';
		echo '<table width="600" cellpadding="10" id="filter_table">';

		if ($courseitemfilter > 0) {
			$avgvalue = 'avg(value)';
			if ($CFG->dbtype == 'postgres7') {
				 $avgvalue = 'avg(cast (value as integer))';
			}
			
			$query = 'SELECT fv.course_id, c.shortname, '.$avgvalue.' as avgvalue '.
						  'FROM '.$CFG->prefix.'feedback_value fv, '.$CFG->prefix.'course c, '.
						  $CFG->prefix.'feedback_item fi '.
						  'WHERE fv.course_id = c.id 
						  AND fi.id = fv.item 
						  AND fi.typ = \''.$courseitemfiltertyp.'\' and fv.item = \''.
						  $courseitemfilter.'\' '.
						  'GROUP BY course_id, shortname ORDER BY avgvalue DESC';

			if ($courses = get_records_sql ($query)) {
				 $item = get_record('feedback_item', 'id', $courseitemfilter);
				 echo '<tr><th colspan="2">'.$item->name.'</th></tr>';
				 echo '<tr><td><table align="left">';
				 echo '<tr><th>Course</th><th>Average</th></tr>';
				 $sep_dec = get_string('separator_decimal', 'feedback');
				 $sep_thous = get_string('separator_thousand', 'feedback');

				 foreach ($courses as $c) {
					  echo '<tr><td>'.$c->shortname.'</td><td align="right">'.number_format(($c->avgvalue), 2, $sep_dec, $sep_thous).'</td></tr>';
				 }
				 echo '</table></td></tr>';
			} else {
				 echo '<tr><td>'.get_string('noresults').'</td></tr>';
			}

		} else {

			echo '<tr><td class="centered">';
			echo get_string('search_course', 'feedback') . ': ';
			echo '<input type="text" name="searchcourse" value="'.s($searchcourse).'"/> <input type="submit" value="'.get_string('search').'"/>';
			echo '<input type="hidden" name="sesskey" value="' . $USER->sesskey . '" />';
			echo '<input type="hidden" name="id" value="'.$id.'" />';
			echo '<input type="hidden" name="courseitemfilter" value="'.$courseitemfilter.'" />';
			echo '<input type="hidden" name="courseitemfiltertyp" value="'.$courseitemfiltertyp.'" />';
			echo '<input type="hidden" name="courseid" value="'.$courseid.'" />';
			echo '</td></tr>';

			// Here add radio buttons for filter choices
			echo "<tr><td class=\"centered\">\n";
			echo '<br />Filter Results: ';
			$filter_course_checked = ((isset($filter_choice) && ($filter_choice == 'filter_course')) || $filter_choice == '') ? ' checked="checked"' : '';
			$filter_directorate_checked = (isset($filter_choice) && ($filter_choice == 'filter_directorate')) ? ' checked="checked"' : '';
			$filter_school_checked = (isset($filter_choice) && ($filter_choice == 'filter_school')) ? ' checked="checked"' : '';
			$filter_level_checked = (isset($filter_choice) && ($filter_choice == 'filter_level')) ? ' checked="checked"' : '';
			$filter_curric_checked = (isset($filter_choice) && ($filter_choice == 'filter_curriculum_area')) ? ' checked="checked"' : '';
			
			echo '<input type="radio" name="filter_choice" id="filter_course" value="filter_course" '.$filter_course_checked.' onClick="showSelection(this)" /><label for="filter_course">Course</label>&nbsp;';
			echo '<input type="radio" name="filter_choice" id="filter_directorate" value="filter_directorate" '.$filter_directorate_checked.' onClick="showSelection(this)" /><label for="filter_directorate">Directorate</label>&nbsp;';
			echo '<input type="radio" name="filter_choice" id="filter_school" value="filter_school" '.$filter_school_checked.' onClick="showSelection(this)" /><label for="filter_school">School</label>&nbsp;';
			echo '<input type="radio" name="filter_choice" id="filter_level" value="filter_level" '.$filter_level_checked.' onClick="showSelection(this)" /><label for="filter_level">Level</label>&nbsp;';
			echo '<input type="radio" name="filter_choice" id="filter_curriculum_area" value="filter_curriculum_area" '.$filter_curric_checked.' onClick="showSelection(this)" /><label for="filter_curriculum_area">Curriculum Area</label>';
			echo '<br /><br class="clear_both" />';
			
			$show_directorates = ($filter_choice == "filter_directorate") ? 'style="display:block;" ' : '';
			echo '<div id="directorates_holder"'.$show_directorates.'>';
			
			// nkowald - 2009-09-29 - Get Directorate values
			$sql = "SELECT id, name FROM ".$CFG->prefix."course_categories ".
										  "WHERE parent = 0 ".
										  "AND name LIKE '%Directorate%' ".
										  "ORDER BY sortorder ASC";
					
			
			
			if ($directorates = get_records_sql_menu($sql)) {
				echo'<div class="filter_directorates">Directorates: ';
				choose_from_menu($directorates, 'directorates', $search_directorates, 'choose', 'this.form.submit()');
				echo '</div>';
			}

			// Check if any filters have been added
			if ($search_directorates > 0 && $filter_choice == 'filter_directorate') {

				$get_cats = "SELECT DISTINCT c.id, c.shortname, c.category, cia.year_0910
								FROM ".$CFG->prefix."course c, ".$CFG->prefix."feedback_value fv, ".$CFG->prefix."feedback_item fi, ".$CFG->prefix."course_idnumber_archive cia
								WHERE c.id = fv.course_id
								AND fi.id = fv.item
								AND c.id = cia.course_id
								AND fi.feedback = '$feedback->id' 
								AND (c.shortname LIKE '%$searchcourse%' OR c.fullname LIKE '%$searchcourse%' OR cia.year_0910 LIKE '%$searchcourse%')
								ORDER BY c.shortname";
			
				// If we found categories
				$direct_courses = array();

				if ($courses_cat = get_records_sql($get_cats)) {
					foreach ($courses_cat as $cat) {
						$query = "SELECT path FROM ".$CFG->prefix."course_categories WHERE id = '".$cat->category."'";
						$get_path = get_record_sql($query);
						// explode path values into an array for accurate comparison
						$path_ids = explode('/',$get_path->path);

						if (in_array($search_directorates,$path_ids)) {
							$direct_courses[] = $cat->id;
						}
					}

					// If courses found under the directorates category: display them.	
					foreach ($direct_courses as $course) {

						if ($course_found = get_record('course_idnumber_archive', 'course_id', $course)) {
							$name = $course_found->year_0910;
						} else {
							// This course doesn't exist in mdl_course_idnumber_archive (some don't)
							$course_found = get_record('course', 'id', $course);
							$name = $course_found->shortname;
						}
						$directorate_courses[$course] = $name;
					}

					echo '<div class="filter_directorates_course">Course: ';
					$in_charge = ($coursefilter == "0" && (!in_array($courseid, $invalid_cids)) && $filter_choice == 'filter_course') ? $courseid : $coursefilter;
					choose_from_menu($directorate_courses, 'coursefilter', $in_charge, 'choose', 'this.form.submit()');
					echo '</div>';
				} else {
					echo '<p><b>No courses found</b></p>';
				}
			}
			
			echo '<br class="clear_both" /></div>';

			$show_school = ($filter_choice == "filter_school") ? 'style="display:block;" ' : '';
			echo '<div id="school_holder"'.$show_school.'>';
			
			// nkowald - 2009-11-19 - Get Schools
			$sql = "SELECT * FROM mdl_course_categories WHERE name LIKE ('School of%') ORDER BY sortorder";
			
			if ($schools = get_records_sql_menu($sql)) {
				echo'<div class="filter_schools">School: ';
				choose_from_menu($schools, 'schools', $search_schools, 'choose', 'this.form.submit()');
				echo '</div>';
			}
			
			// Need to check if any school filters have been added
			if ($search_schools > 0 && $filter_choice == 'filter_school') {

				$get_cats = "SELECT DISTINCT c.id, c.shortname, c.category, cia.year_0910
					FROM ".$CFG->prefix."course c, ".$CFG->prefix."feedback_value fv, ".$CFG->prefix."feedback_item fi, ".$CFG->prefix."course_idnumber_archive cia
					WHERE c.id = fv.course_id
					AND fi.id = fv.item
					AND c.id = cia.course_id
					AND fi.feedback = '$feedback->id' 
					AND (c.shortname LIKE '%$searchcourse%' OR c.fullname LIKE '%$searchcourse%' OR cia.year_0910 LIKE '%$searchcourse%')
					ORDER BY c.shortname";
				
				// If we found categories
				$school_courses = array();
				
				if ($courses_cat = get_records_sql($get_cats)) {
					foreach ($courses_cat as $cat) {
						$query = "SELECT path FROM ".$CFG->prefix."course_categories WHERE id = '".$cat->category."'";
						$get_path = get_record_sql($query);

						// explode path values into an array for accurate comparison
						$path_ids = explode('/',$get_path->path);
						if (in_array($search_schools,$path_ids)) {
							$school_courses[] = $cat->id;
						}
					}
					
					// If courses found under the directorates category: display them.	
					foreach ($school_courses as $course) {

						if ($course_found = get_record('course_idnumber_archive', 'course_id', $course)) {
							$name = $course_found->year_0910;
						} else {
							// This course doesn't exist in mdl_course_idnumber_archive (some don't)
							$course_found = get_record('course', 'id', $course);
							$name = $course_found->shortname;
						}
						$school_courses_are[$course] = $name;
					}
				
					echo '<div class="filter_schools_course">Course: ';
					$in_charge = ($coursefilter == "0" && (!in_array($courseid, $invalid_cids)) && $filter_choice == 'filter_course') ? $courseid : $coursefilter;
					choose_from_menu($school_courses_are, 'coursefilter', $in_charge, 'choose', 'this.form.submit()');
					echo '</div>';
				} else {
					echo '<p><b>No courses found</b></p>';
				}
			}
			
			echo '<br class="clear_both" /></div>';
			
			$show_level = ($filter_choice == "filter_level") ? 'style="display:block;" ' : '';
			echo '<div id="levels_holder"'.$show_level.'>';
			
			// nkowald - 2009-11-19 - Build levels drop-down
			
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
		
			$sql = 'SELECT DISTINCT c.id, cia.year_0910 FROM 
						'.$CFG->prefix.'course c, '. $CFG->prefix.'feedback_value fv, '.$CFG->prefix.'feedback_item fi, '. $CFG->prefix.'course_idnumber_archive cia 
						  WHERE c.id = fv.course_id 
						  AND fi.id = fv.item 
						  AND fi.feedback = '.$feedback->id.' 
						  AND c.id = cia.course_id 
						  AND (c.shortname '.sql_ilike().' \'%'.$searchcourse.'%\' OR c.fullname '.sql_ilike().' \'%'.$searchcourse.'%\' OR cia.year_0910 LIKE \'%'.$searchcourse.'%\')
						  AND (c.shortname LIKE \'__'.$search_levels.'%\') 
						  ORDER BY c.shortname';

			// If we found categories
			if ($courses = get_records_sql($sql)) {
			
				// nkowald - 2010-10-12 - Need to show 0910 academic year idnumbers instead
				foreach ($courses as $cid) {
					if ($course_found = get_record('course_idnumber_archive', 'course_id', $cid->id)) {
						$name = $course_found->year_0910;
					} else {
						// This course doesn't exist in mdl_course_idnumber_archive (some don't)
						$course_found = get_record('course', 'id', $cid->id);
						$name = $course_found->shortname;
					}
					$leveled_courses[$cid->id] = $name;
				}
				
				echo '<div class="filter_levels_course">Course: ';
				$in_charge = ($coursefilter == "0" && (!in_array($courseid, $invalid_cids)) && $filter_choice == 'filter_course') ? $courseid : $coursefilter;
				choose_from_menu($leveled_courses, 'coursefilter', $in_charge, 'choose', 'this.form.submit()');
				echo '</div>';
				
				$level_courses = array();
				// Add course ids to array to show results
				foreach ($courses as $cid => $c_value) {
					$level_courses[] = $cid;
				}
			} else {
				echo '<p><b>No courses found</b></p>';
			}
			
		}	
			
			echo '<br class="clear_both" /></div>';


			// Curriculum area filter	
			$show_curric = ($filter_choice == "filter_curriculum_area") ? 'style="display:block;" ' : '';
			echo '<div id="curriculum_area_holder"'.$show_curric.'>';

			// nkowald - 2010-06-21 - They changed how the curriculum areas should be defined, now defining manually
			$valid_curric_ids = "385, 122, 399, 45, 39, 22, 562, 124, 24, 61, 40, 3, 306, 4, 205, 42, 117, 118, 119, 123, 400, 386, 46, 63, 59, 41, 50, 333, 49, 398, 120, 121, 43, 401, 29, 402, 335, 125, 263, 30, 51, 334, 31, 393, 16, 33, 44, 394";
			$query = "SELECT id, name FROM ".$CFG->prefix."course_categories
										WHERE id IN (".$valid_curric_ids.") 
										ORDER BY name ASC";
			
			if ($curriculum_areas = get_records_sql_menu($query)) {
				echo'<div class="filter_curriculum_areas">Curriculum Area: ';
				choose_from_menu($curriculum_areas, 'curriculum_areas', $search_curriculum_areas, 'choose', 'this.form.submit()');
				echo '</div>';
			}

			// Need to check if any filters have been added
			if ($search_curriculum_areas > 0 && $filter_choice == 'filter_curriculum_area') {
								
				$get_cats = "SELECT DISTINCT c.id, c.shortname, c.category, cia.year_0910
					FROM ".$CFG->prefix."course c, ".$CFG->prefix."feedback_value fv, ".$CFG->prefix."feedback_item fi, ".$CFG->prefix."course_idnumber_archive cia
					WHERE c.id = fv.course_id
					AND fi.id = fv.item
					AND c.id = cia.course_id
					AND fi.feedback = '$feedback->id' 
					AND (c.shortname LIKE '%$searchcourse%' OR c.fullname LIKE '%$searchcourse%' OR cia.year_0910 LIKE '%$searchcourse%')
					ORDER BY c.shortname";
			
				// nkowald - 2011-06-02 - Added subcategory
                $query = "SELECT id, name FROM ".$CFG->prefix."course_categories WHERE parent = $search_curriculum_areas AND visible = 1";
                if ($subcats = get_records_sql_menu($query)) {
                    echo'<div class="filter_subcat" style="margin-top:8px;">Subcategory: ';
                    choose_from_menu($subcats, 'subcategory', $search_subcat, 'choose', 'this.form.submit()');
                    echo '</div>';
                }
			
				// If we found categories
				$curric_courses = array();
				
				if ($courses_cat = get_records_sql($get_cats)) {
					foreach ($courses_cat as $cat) {

						$query = "SELECT path FROM ".$CFG->prefix."course_categories WHERE id = '".$cat->category."'";
						$get_path = get_record_sql($query);
						// explode path values into an array for accurate comparison
						$path_ids = explode('/',$get_path->path);

						// nkowald - 2011-06-02 - Change search based on level of category
                        if ($search_subcat != 0) {
                            if (in_array($search_subcat,$path_ids)) {
                                $curric_courses[] = $cat->id;
                            }
                        } else {
                            if (in_array($search_curriculum_areas,$path_ids)) {
                                $curric_courses[] = $cat->id;
                            }
                        }
					}

					// If courses found under the directorates category: display them.	
					foreach ($curric_courses as $course) {

						if ($course_found = get_record('course_idnumber_archive', 'course_id', $course)) {
							$name = $course_found->year_0910;
						} else {
							// This course doesn't exist in mdl_course_idnumber_archive (some don't)
							$course_found = get_record('course', 'id', $course);
							$name = $course_found->shortname;
						}
						$curric_courses_are[$course] = $name;
					}
				
					echo '<div class="filter_curriculum_course">Course: ';
					$in_charge = ($coursefilter == "0" && (!in_array($courseid, $invalid_cids)) && $filter_choice == 'filter_course') ? $courseid : $coursefilter;
					choose_from_menu($curric_courses_are, 'coursefilter', $in_charge, 'choose', 'this.form.submit()');
					echo '</div>';
					
				} else {
					echo '<p><b>No courses found</b></p>';
				}
				
			}
			
			
			echo '<br class="clear_both" /></div>';
			


			if ($filter_choice == "" || $filter_choice == "filter_course") {
				
				$show_course = ($filter_choice == "filter_course") ? 'style="display:block;" ' : '';
				echo '<div id="course_holder"'.$show_course.'>';

				$sql = "SELECT DISTINCT c.id, cia.year_0910
					FROM ".$CFG->prefix."course c, ".$CFG->prefix."feedback_value fv, ".$CFG->prefix."feedback_item fi, ".$CFG->prefix."course_idnumber_archive cia
					WHERE c.id = fv.course_id
					AND fi.id = fv.item
					AND c.id = cia.course_id
					AND fi.feedback = '$feedback->id' 
					AND (c.shortname LIKE '%$searchcourse%' OR c.fullname LIKE '%$searchcourse%' OR cia.year_0910 LIKE '%$searchcourse%')
					ORDER BY c.shortname";
				
				if ($courses = get_records_sql($sql)) {
					// If courses found under the directorates category: display them.	
					foreach ($courses as $course) {

						if ($course_found = get_record('course_idnumber_archive', 'course_id', $course->id)) {
							$name = $course_found->year_0910;
						} else {
							// This course doesn't exist in mdl_course_idnumber_archive (some don't)
							$course_found = get_record('course', 'id', $course->id);
							$name = $course_found->shortname;
						}
						$found_courses[$course->id] = $name;
					}

					 echo get_string('filter_by_course', 'feedback') . ': ';
					 $in_charge = ($coursefilter == "0" && (!in_array($courseid, $invalid_cids)) && $filter_choice == 'filter_course') ? $courseid : $coursefilter;
					 choose_from_menu($found_courses, 'coursefilter', $in_charge, 'choose', 'this.form.submit()');
					 echo '<br /><br />';
				} else {
					echo '<p><b>No courses found</b></p>';
				}

				
			} else {
				echo '<div id="loading_msg"></div>';
			}
			
			echo '</div>';
			
			$filter_gender_both = (isset($filter_gender) && ($filter_gender == '')) ? ' checked="checked"' : '';
			$filter_gender_male = (isset($filter_gender) && ($filter_gender == '1')) ? ' checked="checked"' : '';
			$filter_gender_female = (isset($filter_gender) && ($filter_gender == '2')) ? ' checked="checked"' : '';
			echo '
			<table id="secondary_filter">
				<tr>
					<td>
				';
			echo 'Gender:</td><td> 
			<input type="radio" name="filter_gender" id="filter_gender_both" value="" '.$filter_gender_both.' onClick="this.form.submit()" /><label for="filter_gender_both">Both</label>
			<input type="radio" name="filter_gender" id="filter_gender_male" value="1" '.$filter_gender_male.' onClick="this.form.submit()" /><label for="filter_gender_male">Male</label>';
			echo '&nbsp;';
			echo '<input type="radio" name="filter_gender" id="filter_gender_female" value="2" '.$filter_gender_female.' onClick="this.form.submit()" /><label for="filter_gender_female">Female</label>
				</td></tr>';

			// Age filter
			$filter_age_all = (isset($filter_age) && ($filter_age == '')) ? ' checked="checked"' : '';
			$filter_age_14_16 = (isset($filter_age) && ($filter_age == '1')) ? ' checked="checked"' : '';
			$filter_age_16_19 = (isset($filter_age) && ($filter_age == '2')) ? ' checked="checked"' : '';
			$filter_age_20_25 = (isset($filter_age) && ($filter_age == '3')) ? ' checked="checked"' : '';
			$filter_age_26 = (isset($filter_age) && ($filter_age == '4')) ? ' checked="checked"' : '';
			
			echo '<tr><td>Age:</td><td>
			<input type="radio" name="filter_age" id="filter_age_all" value="" '.$filter_age_all.' onClick="this.form.submit()" /><label for="filter_age_all">All Ages</label>
			<input type="radio" name="filter_age" id="filter_age_14_16" value="1" '.$filter_age_14_16.' onClick="this.form.submit()" /><label for="filter_age_14_16">14-16</label>
			<input type="radio" name="filter_age" id="filter_age_16_19" value="2" '.$filter_age_16_19.' onClick="this.form.submit()" /><label for="filter_age_16_19">16-19</label>
			<input type="radio" name="filter_age" id="filter_age_20_25" value="3" '.$filter_age_20_25.' onClick="this.form.submit()" /><label for="filter_age_20_25">20-25</label>
			<input type="radio" name="filter_age" id="filter_age_26" value="4" '.$filter_age_26.' onClick="this.form.submit()" /><label for="filter_age_26">26+</label>
			</td></tr>';
			
			// Attendance filter
			
			$filter_attend = (isset($filter_attendance) && ($filter_attendance == '')) ? ' checked="checked"' : '';
			$filter_attend_ft = (isset($filter_attendance) && ($filter_attendance == '1')) ? ' checked="checked"' : '';
			$filter_attend_pt = (isset($filter_attendance) && ($filter_attendance == '2')) ? ' checked="checked"' : '';
			
			echo '<tr><td>Attendance:</td>
				<td>
			<input type="radio" name="filter_attendance" value="" '.$filter_attend.' id="filter_attendance_all" onClick="this.form.submit()" /><label for="filter_attendance_all">All</label>
			<input type="radio" name="filter_attendance" value="1" '.$filter_attend_ft.' id="filter_attendance_full_time" onClick="this.form.submit()" /><label for="filter_attendance_full_time">Full Time</label>
			<input type="radio" name="filter_attendance" value="2" '.$filter_attend_pt.' id="filter_attendance_part_time" onClick="this.form.submit()" /><label for="filter_attendance_part_time">Part Time Day</label></td></tr>';
			
			$filter_ethnic = (isset($filter_ethnicity) && ($filter_ethnicity == '')) ? ' selected="selected"' : '';
			$filter_ethnic_1 = (isset($filter_ethnicity) && ($filter_ethnicity == 1)) ? ' selected="selected"' : '';
			$filter_ethnic_2 = (isset($filter_ethnicity) && ($filter_ethnicity == 2)) ? ' selected="selected"' : '';
			$filter_ethnic_3 = (isset($filter_ethnicity) && ($filter_ethnicity == 3)) ? ' selected="selected"' : '';
			$filter_ethnic_4 = (isset($filter_ethnicity) && ($filter_ethnicity == 4)) ? ' selected="selected"' : '';
			$filter_ethnic_5 = (isset($filter_ethnicity) && ($filter_ethnicity == 5)) ? ' selected="selected"' : '';
			$filter_ethnic_6 = (isset($filter_ethnicity) && ($filter_ethnicity == 6)) ? ' selected="selected"' : '';
			$filter_ethnic_7 = (isset($filter_ethnicity) && ($filter_ethnicity == 7)) ? ' selected="selected"' : '';
			$filter_ethnic_8 = (isset($filter_ethnicity) && ($filter_ethnicity == 8)) ? ' selected="selected"' : '';
			$filter_ethnic_9 = (isset($filter_ethnicity) && ($filter_ethnicity == 9)) ? ' selected="selected"' : '';
			$filter_ethnic_10 = (isset($filter_ethnicity) && ($filter_ethnicity == 10)) ? ' selected="selected"' : '';
			$filter_ethnic_11 = (isset($filter_ethnicity) && ($filter_ethnicity == 11)) ? ' selected="selected"' : '';
			$filter_ethnic_12 = (isset($filter_ethnicity) && ($filter_ethnicity == 12)) ? ' selected="selected"' : '';
			$filter_ethnic_13 = (isset($filter_ethnicity) && ($filter_ethnicity == 13)) ? ' selected="selected"' : '';
			$filter_ethnic_14 = (isset($filter_ethnicity) && ($filter_ethnicity == 14)) ? ' selected="selected"' : '';
			$filter_ethnic_15 = (isset($filter_ethnicity) && ($filter_ethnicity == 15)) ? ' selected="selected"' : '';
			$filter_ethnic_16 = (isset($filter_ethnicity) && ($filter_ethnicity == 16)) ? ' selected="selected"' : '';
			
			// Ethnicity Filter
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
			$filter_ld_checked = (isset($filter_learning_difficulty) && ($filter_learning_difficulty == '')) ? ' checked="checked"' : '';
			$filter_ld_checked_yes = (isset($filter_learning_difficulty) && ($filter_learning_difficulty == '1')) ? ' checked="checked"' : '';
			$filter_ld_checked_no = (isset($filter_learning_difficulty) && ($filter_learning_difficulty == '2')) ? ' checked="checked"' : '';
			
			echo '<tr><td>
			Declared Learning Difficulty:</td><td>
			<input type="radio" name="filter_learning_difficulty" value="" id="filter_ld" '.$filter_ld_checked.' onClick="this.form.submit()" /><label for="filter_ld">All</label>
			<input type="radio" name="filter_learning_difficulty" value="1" id="filter_ld_yes" '.$filter_ld_checked_yes.' onClick="this.form.submit()" /><label for="filter_ld_yes">Yes</label>
			<input type="radio" name="filter_learning_difficulty" value="2" id="filter_ld_no" '.$filter_ld_checked_no.' onClick="this.form.submit()" /><label for="filter_ld_no">No</label></td></tr>';
			
			// Declared Learning Difficulty
			$filter_ldb_checked = (isset($filter_learning_disability) && ($filter_learning_disability == '')) ? ' checked="checked"' : '';
			$filter_ldb_checked_yes = (isset($filter_learning_disability) && ($filter_learning_disability == '1')) ? ' checked="checked"' : '';
			$filter_ldb_checked_no = (isset($filter_learning_disability) && ($filter_learning_disability == '2')) ? ' checked="checked"' : '';
			
			echo '<tr><td>
			Declared Disability:</td><td>
			<input type="radio" name="filter_learning_disability" value="" id="filter_ldb" '.$filter_ldb_checked.' onClick="this.form.submit()" /><label for="filter_ldb">All</label>
			<input type="radio" name="filter_learning_disability" value="1" id="filter_ldb_yes" '.$filter_ldb_checked_yes.' onClick="this.form.submit()" /><label for="filter_ldb_yes">Yes</label>
			<input type="radio" name="filter_learning_disability" value="2" id="filter_ldb_no" '.$filter_ldb_checked_no.' onClick="this.form.submit()" /><label for="filter_ldb_no">No</label>
			</td></tr></table>
			</td></tr>	
			</table>';
			
			$itemnr = 0;
			//print the items in an analysed form

			// get the number of the item for the current feedback survey
			$query = "SELECT id FROM ".$CFG->prefix."feedback_item WHERE feedback = ".$feedback->id." AND name = 'Gender'";
			$id_of_gender = get_records_sql($query);
			foreach ($id_of_gender as $gender) {
				$gender_id = $gender->id;
			}
			
			// get the number of the item for the current feedback survey
			$query = "SELECT id FROM ".$CFG->prefix."feedback_item WHERE feedback = ".$feedback->id." AND name = 'Age'";
			$id_of_age = get_records_sql($query);
			foreach ($id_of_age as $age) {
				$age_id = $age->id;
			}
			
			// get the number of the item for the current feedback survey
			$query = "SELECT id FROM ".$CFG->prefix."feedback_item WHERE feedback = ".$feedback->id." AND name LIKE ('%ethnic%')";
			$id_of_ethnic = get_records_sql($query);
			foreach ($id_of_ethnic as $ethnic) {
				$ethnic_id = $ethnic->id;
			}

			// get the number of the item for the current feedback survey
			$query = "SELECT id FROM ".$CFG->prefix."feedback_item WHERE feedback = ".$feedback->id." AND name = 'Attendance'";
			$id_of_attendance = get_records_sql($query);
			foreach ($id_of_attendance as $attendance) {
				$attendance_id = $attendance->id;
			}

			// get the number of the item for the current feedback survey
			$query = "SELECT id FROM ".$CFG->prefix."feedback_item WHERE feedback = ".$feedback->id." AND name LIKE ('%Learning Difficulty%')";
			$id_of_dld = get_records_sql($query);
			foreach ($id_of_dld as $dld) {
				$dld_id = $dld->id;
			}

			// get the number of the item for the current feedback survey
			$query = "SELECT id FROM ".$CFG->prefix."feedback_item WHERE feedback = ".$feedback->id." AND name LIKE ('%Disability%')";
			$id_of_dldb = get_records_sql($query);
			foreach ($id_of_dldb as $dldb) {
				$dldb_id = $dldb->id;
			}

			
			// Gender Filter
			$gender = ($filter_gender != '') ? $filter_gender : '';
			$ubg = array(); // users by gender array

			// Age Filter
			$age = ($filter_age != '') ? $filter_age  : '';
			$uba = array(); // users by age array

			// Ethnicity Filter
			$ethnic = ($filter_ethnicity != '') ? $filter_ethnicity : '';
			$ube = array(); // users by ethnicity array

			// Attendance Filter
			$attend = ($filter_attendance != '') ? $filter_attendance : '';
			$ubat = array();

			// Declared Learning Difficulty Fitler
			$dld = ($filter_learning_difficulty != '') ? $filter_learning_difficulty : '';
			$ubdld = array();

			// Declared Disability Fitler
			$dldb = ($filter_learning_disability != '') ? $filter_learning_disability : '';
			$ubdldb = array();

			$completed_ids_to_search = array();
			
			if (($gender != '' && $gender_id != '')) {
				// Gender ONLY
				$query = "SELECT completed FROM ".$CFG->prefix."feedback_value WHERE item = ".$gender_id." and value = ".$gender."";
				$users_by_gender = get_records_sql($query);
				foreach ($users_by_gender as $user) {
					$ubg[] = $user->completed;
				}	
				$completed_ids_to_search = $ubg;
			}
			if (($age != '' && $age_id != '')) {
				// Age ONLY
				$query = "SELECT completed FROM ".$CFG->prefix."feedback_value WHERE item = ".$age_id." and value = ".$age."";
				$users_by_age = get_records_sql($query);
				foreach ($users_by_age as $user) {
					$uba[] = $user->completed;
				}
				$completed_ids_to_search = $uba;
			}
			if (($ethnic != '' && $ethnic_id != '')) {
				// Ethnicity ONLY
				$query = "SELECT completed FROM ".$CFG->prefix."feedback_value WHERE item = ".$ethnic_id." and value = ".$ethnic."";
				$users_by_ethnic = get_records_sql($query);
				foreach ($users_by_ethnic as $user) {
					$ube[] = $user->completed;
				}
				$completed_ids_to_search = $ube;
			}
			if (($attend != '' && $attendance_id != '')) {
				// Attendance ONLY	
				$query = "SELECT completed FROM ".$CFG->prefix."feedback_value WHERE item = ".$attendance_id." and value = ".$attend."";
				$users_by_attendance = get_records_sql($query);
				foreach ($users_by_attendance as $user) {
					$ubat[] = $user->completed;
				}
				$completed_ids_to_search = $ubat;
			}
			if (($dld != '' && $dld_id != '')) {
				// Declared Learning Difficulty ONLY	
				$query = "SELECT completed FROM ".$CFG->prefix."feedback_value WHERE item = ".$dld_id." and value = ".$dld."";
				$users_by_dld = get_records_sql($query);
				foreach ($users_by_dld as $user) {
					$ubdld[] = $user->completed;
				}
				$completed_ids_to_search = $ubdld;
			}
			if (($dldb != '' && $dldb_id != '')) {
				// Declared Disability ONLY	
				$query = "SELECT completed FROM ".$CFG->prefix."feedback_value WHERE item = ".$dldb_id." and value = ".$dldb."";
				$users_by_dldb = get_records_sql($query);
				foreach ($users_by_dldb as $user) {
					$ubdldb[] = $user->completed;
				}
				$completed_ids_to_search = $ubdldb;
			}

			// This needs to intersect all non-empty arrays top-down
			$filter_count = 0;
			$to_intersect = array();

			if (count($ubg) > 0) {
				$filter_count++;
				$to_intersect[] = $ubg;
			}
			if (count($uba) > 0) {
				$filter_count++;
				$to_intersect[] = $uba;
			}
			if (count($ube) > 0) {
				$filter_count++;
				$to_intersect[] = $ube;
			}
			if (count($ubat) > 0) {
				$filter_count++;
				$to_intersect[] = $ubat;
			}
			if (count($ubdld) > 0) {
				$filter_count++;
				$to_intersect[] = $ubdld;
			}
			if (count($ubdldb) > 0) {
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

			}


			// Create and clause based on filters selected
			$and_clause = '';

			$in_charge = ($coursefilter == "0" && (!in_array($courseid, $invalid_cids)) && $filter_choice == 'filter_course') ? $courseid : $coursefilter;
			if ($in_charge != 0 && $in_charge != '') {
				$and_clause .= ' AND fbv.course_id = '.$in_charge.' ';
			}

			// Build completed and clause if filters set
			if ($gender != '' || $age != '' || $ethnic != '' || $attend != '' || $dld != '') {
				$completed_ids = implode(',', $completed_ids_to_search); // convert array to csv format
				$and_clause .= ' AND fbv.completed IN ('.$completed_ids.')';
			}

			if ($search_directorates > 0) {
		
				$courses_to_search = implode(',', $direct_courses);
				$and_clause .= ' AND fbv.course_id IN ('.$courses_to_search.')';
			
			} elseif ($search_schools > 0) {
			
				$courses_to_search = implode(',',$school_courses);
				$and_clause .= ' AND fbv.course_id IN ('.$courses_to_search.')';
				
			} elseif ($search_levels != '0' && $search_levels != '') {
				
				$courses_to_search = implode(',',$level_courses);
				$and_clause .= ' AND fbv.course_id IN ('.$courses_to_search.')';
				
			} elseif ($search_curriculum_areas > 0 && count($curric_courses) > 0) {

				$courses_to_search = implode(',',$curric_courses);
				$and_clause .= ' AND fbv.course_id IN ('.$courses_to_search.')';
			}

			$_SESSION['and_clause'] = $and_clause;

			if(is_array($items)){
				echo '<b>'.get_string('questions', 'feedback').': ' .sizeof($items). ' </b><br />';
			}
			//get the groupid
			//lstgroupid is the choosen id
			$mygroupid = false;

			$and_clause = (isset($_SESSION['and_clause'])) ? $_SESSION['and_clause'] : '';
			$completedscount = feedback_get_completeds_group_count($feedback, $mygroupid, $coursefilter, $and_clause);

			$completedscount_from_values = 0;
			if ($values = get_records('feedback_value', 'item', 194)){
				$completedscount_from_values = sizeof($values);
			}
			
			//show the count
			echo '<b>'.get_string('completed_feedbacks', 'feedback').': '.$completedscount. '</b><br />';

			echo '</form>';

			// nkowald - 2009-12-08 - Had to move export button below filters as required filter code for export
			if ($capabilities->viewreports) {
				// export to excel button
				echo '<div style="text-align:center;" class="bogus">';
				$export_button_link = 'analysis_to_excel.php';
				$in_charge = ($coursefilter == "0" && (!in_array($courseid, $invalid_cids)) && $filter_choice == 'filter_course') ? $courseid : $coursefilter;
				$export_button_options = array('sesskey'=>$USER->sesskey, 'id'=>$id, 'coursefilter'=>$in_charge, 'and_clause'=>$and_clause);
				$export_button_label = get_string('export_to_excel', 'feedback');
				print_single_button($export_button_link, $export_button_options, $export_button_label, 'post');
			}


			echo '<hr />';
			
			$in_charge = ($coursefilter == "0" && (!in_array($courseid, $invalid_cids)) && $filter_choice == 'filter_course') ? $courseid : $coursefilter;
			
			$no_feedback_error = 'No feedback exists for this course.';

			// Build completed where clause if age or gender filters set
			if ($gender != '' || $age != '' || $ethnic != '' || $attend != '' || $dld != '') {
				$completed_ids = implode(',', $completed_ids_to_search); // convert array to csv format
				$where_clause .= ' AND completed IN ('.$completed_ids.')';
				$no_feedback_error = 'No results match this level of filtering, please redefine your filter options.';
			}

			if ($in_charge != 0 && $in_charge != '') { 
				$where_clause .= ' AND course_id = '.$in_charge.'';
			}

			if ($search_directorates > 0) {
		
				if ($in_charge == "0") {
					$courses_to_search = implode(',', $direct_courses);
					$where_clause .= ' AND course_id IN ('.$courses_to_search.')';
				}
			
			} elseif ($search_schools > 0) {
			
				if ($in_charge == "0") {
					$courses_to_search = implode(',',$school_courses);
					$where_clause .= ' AND course_id IN ('.$courses_to_search.')';
				}
				
			} elseif ($search_levels != '0' && $search_levels != '') {
				
				if ($in_charge == "0") {
					$courses_to_search = implode(',',$level_courses);
					$where_clause .= ' AND course_id IN ('.$courses_to_search.')';
				}
				
			} elseif ($search_curriculum_areas > 0) {

				if ($in_charge == "0") {
					$courses_to_search = implode(',',$curric_courses);
					$where_clause .= ' AND course_id IN ('.$courses_to_search.')';
				}
			}

			// nkowald - 24-08-2010 - Add where clause into session for when coming back to page from 'show responses' tab.
			// nkowald - 2010-11-11 - This was messing things up, uncommented
			/*
            if (isset($_SESSION['ces']['where_clause']) && $where_clause == '') {
                $where_clause = $_SESSION['ces']['where_clause'];
            } else if ($where_clause != '') {
                $_SESSION['ces']['where_clause'] = $where_clause;
            }
			*/
            // nkowald - 24-08-2010

			// work out if this course has any feedback against it
			$sql = "SELECT * FROM ".$CFG->prefix."feedback_value WHERE 1=1 $where_clause";

			$has_feedback = FALSE;
			if ($results = get_records_sql($sql)) {
				if (count($has_feedback) > 0) {
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
					
					$where_clause_to_use = '1=1 '. $where_clause .' AND item = '.$item->id.'';

					// this will handle everything now - yay, less lines of code.
					$itemobj->print_analysed($item, $printnr, $mygroupid, $in_charge, $where_clause_to_use);

					if (eregi('rated$', $item->typ)) {
						 echo '<tr><td colspan="2"><a href="#" onclick="setcourseitemfilter('.$item->id.',\''.$item->typ.'\'); return false;">'.
							get_string('sort_by_course', 'feedback').'</a></td></tr>'; 
					}

					echo '</table>';
				}

				/*
				echo '</td></tr>';
				echo '</table></div>';
				 */

			} else {
				echo "<p><b>$no_feedback_error</b></p>";
			}

		}
	}

	echo '</div>';

    print_box_end();
    
    print_footer($course);

?>
