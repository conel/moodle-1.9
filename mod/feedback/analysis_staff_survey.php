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
		unset($_SESSION['ss']);
	} else {
		// nkowald - 24-08-2010 - If coming from 'Show Responses' tab, fill last used filters
		if (isset($_POST) && count($_POST) > 0) {
			$_SESSION['ss']['filters_post'] = $_POST;
		}
		if ($_SESSION['ss']['filters_post'] != '') {
			foreach ($_SESSION['ss']['filters_post'] as $key => $value) {
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
	$search_sectors = optional_param('sectors', 0, PARAM_INT);
	$search_levels = optional_param('levels', '', PARAM_RAW);
	$search_curriculum_areas = optional_param('curriculum_areas', '', PARAM_RAW);
	// nkowald - 2011-06-02 - Change search based on level of category
    $search_subcat = optional_param('subcategory', '', PARAM_RAW);

	$filter_job = optional_param('filter_job', '', PARAM_INT);
	$filter_qualifications = optional_param('filter_qualifications', '', PARAM_INT);
	$filter_teaching_qualifications = optional_param('filter_teaching_qualifications','', PARAM_RAW);
	$filter_teaching_experience = optional_param('filter_teaching_experience','', PARAM_RAW);
    $filter_management_experience = optional_param('filter_management_experience','', PARAM_RAW);
    $filter_support_experience = optional_param('filter_support_experience','', PARAM_RAW);
	$filter_awarding_bodies = optional_param('filter_awarding_bodies','', PARAM_RAW);

	$filter_sw_word = optional_param('filter_sw_word','', PARAM_RAW);
	$filter_sw_excel = optional_param('filter_sw_excel','', PARAM_RAW);
	$filter_sw_access = optional_param('filter_sw_access','', PARAM_RAW);
	$filter_sw_powerpoint = optional_param('filter_sw_powerpoint','', PARAM_RAW);
	$filter_sw_outlook = optional_param('filter_sw_outlook','', PARAM_RAW);
	$filter_internet = optional_param('filter_internet','', PARAM_RAW);
	$filter_intranets = optional_param('filter_intranets','', PARAM_RAW);
	$filter_vles = optional_param('filter_vles','', PARAM_RAW);
	$filter_iwboards = optional_param('filter_iwboards','', PARAM_RAW);
	$filter_elearn_res = optional_param('filter_elearn_res','', PARAM_RAW);

	$filter_os_sm = optional_param('filter_os_sm','', PARAM_RAW);
	$filter_os_course_mgmt = optional_param('filter_os_course_mgmt','', PARAM_RAW);
	$filter_os_budget_mgmt = optional_param('filter_os_budget_mgmt','', PARAM_RAW);
	$filter_os_project_mgmt = optional_param('filter_os_project_mgmt','', PARAM_RAW);
	$filter_os_coach_ment = optional_param('filter_os_coach_ment','', PARAM_RAW);
	$filter_os_presentation = optional_param('filter_os_presentation','', PARAM_RAW);
	$filter_os_saag = optional_param('filter_os_saag','', PARAM_RAW);
	$filter_os_td = optional_param('filter_os_td','', PARAM_RAW);
	$filter_os_rws = optional_param('filter_os_rws','', PARAM_RAW);
	$filter_os_rk = optional_param('filter_os_rk','', PARAM_RAW);

	$filter_ok_hs = optional_param('filter_ok_hs','', PARAM_RAW);
	$filter_ok_eo = optional_param('filter_ok_eo','', PARAM_RAW);
	$filter_ok_div = optional_param('filter_ok_div','', PARAM_RAW);
	$filter_ok_firstaid = optional_param('filter_ok_firstaid','', PARAM_RAW);
	$filter_ok_qas = optional_param('filter_ok_qas','', PARAM_RAW);
	$filter_ok_safeg = optional_param('filter_ok_safeg','', PARAM_RAW);
	$filter_ok_oif = optional_param('filter_ok_oif','', PARAM_RAW);
	$filter_ok_ffe = optional_param('filter_ok_ffe','', PARAM_RAW);

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
		echo '<script type="text/javascript" src="feedback_functions_staff.js"></script>';

		//get the groupid
		//lstgroupid is the choosen id
		$mygroupid = false;

		// get the items of the feedback
		$items = get_records_select('feedback_item', 'feedback = '. $feedback->id . ' AND hasvalue = 1', 'position');

		//show the count
		if(is_array($items)){
			//echo '<b>'.get_string('questions', 'feedback').': ' .sizeof($items). ' </b>';
			//echo '<a href="analysis_course.php?id=' . $id . '&amp;courseid=0">'.get_string('show_all', 'feedback').' courses</a>';
		} else {
			$items=array();
		}


		/*
		// nkowald - uncomment this to view POST variables and debug
		echo '<pre>';
		var_dump($_POST);
		echo '</pre>';
		*/
		
		echo '<form name="report" method="post">';
		echo '<table width="800" cellpadding="10" id="filter_table">';

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
			echo '<input type="hidden" name="sesskey" value="' . $USER->sesskey . '" />';
			echo '<input type="hidden" name="id" value="'.$id.'" />';
			echo '<input type="hidden" name="courseitemfilter" value="'.$courseitemfilter.'" />';
			echo '<input type="hidden" name="courseitemfiltertyp" value="'.$courseitemfiltertyp.'" />';
			echo '<input type="hidden" name="courseid" value="'.$courseid.'" />';
			echo '</td></tr>';

			echo "<tr><td>\n";
			echo '<h4>Filter Results by:</h4> ';

			$filter_job_all = (isset($filter_job) && ($filter_job == '')) ? ' checked="checked"' : '';
			$filter_job_teaching = (isset($filter_job) && ($filter_job == '1')) ? ' checked="checked"' : '';
			$filter_job_management = (isset($filter_job) && ($filter_job == '2')) ? ' checked="checked"' : '';
			$filter_job_support = (isset($filter_job) && ($filter_job == '3')) ? ' checked="checked"' : '';
			echo '
			<table id="secondary_filter">
				<tr>
					<td style="width:350px;"><b>Job:</b></td><td> 
			<input type="radio" name="filter_job" id="filter_job_all" value="" '.$filter_job_all.' onClick="this.form.submit()" /><label for="filter_job_all">All</label>
			<input type="radio" name="filter_job" id="filter_job_teaching" value="1" '.$filter_job_teaching.' onClick="this.form.submit()" /><label for="filter_job_teaching">Teaching</label>';
			echo '&nbsp;';
			echo '<input type="radio" name="filter_job" id="filter_job_management" value="2" '.$filter_job_management.' onClick="this.form.submit()" /><label for="filter_job_management">Management</label>';
			echo '&nbsp;';
			echo '<input type="radio" name="filter_job" id="filter_job_support" value="3" '.$filter_job_support.' onClick="this.form.submit()" /><label for="filter_job_support">Support</label>
				</td></tr>';

			// Professional Qualifications filter
			$filter_qualifications_all = (isset($filter_qualifications) && ($filter_qualifications == '')) ? ' selected="selected"' : '';
			$filter_qualifications_no = (isset($filter_qualifications) && ($filter_qualifications == '1')) ? ' selected="selected"' : '';
			$filter_qualifications_degree = (isset($filter_qualifications) && ($filter_qualifications == '2')) ? ' selected="selected"' : '';
			$filter_qualifications_hnc = (isset($filter_qualifications) && ($filter_qualifications == '3')) ? ' selected="selected"' : '';
			$filter_qualifications_professional = (isset($filter_qualifications) && ($filter_qualifications == '4')) ? ' selected="selected"' : '';
			
			echo '<tr><td><label for="filter_qualifications_select"><b>Professional Qualifications:</b></label></td><td>
                <select name="filter_qualifications" id="filter_qualifications_select" onChange="this.form.submit()">
                    <option value="" '.$filter_qualifications_all.'>Choose...</option>
                    <option value="1" '.$filter_qualifications_no.'>No</option>
                    <option value="2" '.$filter_qualifications_degree.'>Degree</option>
                    <option value="3" '.$filter_qualifications_hnc.'>Higher National Certificate</option>
                    <option value="4" '.$filter_qualifications_professional.'>Professional Qualification</option>
                </select>
			</td></tr>';
			
			// Teaching Qualifications
			$filter_tq = (isset($filter_teaching_qualifications) && ($filter_teaching_qualifications == '')) ? ' selected="selected"' : '';
            for ($i=1; $i <=11; $i++) {
                $filter_tq = sprintf('filter_tq_%d', $i);
                $$filter_tq = (isset($filter_teaching_qualifications) && ($filter_teaching_qualifications == $i)) ? ' selected="selected"' : '';
            }
			
			echo '<tr><td><label for="filter_teaching_qualifications_select"><b>Teaching Qualifications:</b></label></td>
			<td>
			<select name="filter_teaching_qualifications" id="filter_teaching_qualifications_select" onChange="this.form.submit()">
				<option value="" '.$filter_tq.'>Choose...</option>
				<option value="1" '.$filter_tq_1.'>PGCE</option>
				<option value="2" '.$filter_tq_2.'>Cert Ed</option>
				<option value="3" '.$filter_tq_3.'>C&G 7307 stage 1</option>
				<option value="4" '.$filter_tq_4.'>C&G 7307 stage 2</option>
				<option value="5" '.$filter_tq_5.'>C&G 7407 stage 1</option>
				<option value="6" '.$filter_tq_6.'>C&G 7407 stage 2</option>
				<option value="7" '.$filter_tq_7.'>C&G 7407 stage 3</option>
				<option value="8" '.$filter_tq_8.'>Dtlls</option>
				<option value="9" '.$filter_tq_9.'>Ptlls</option>
				<option value="10" '.$filter_tq_10.'>Celta</option>
				<option value="11" '.$filter_tq_11.'>Delta</option>
			</select>
            </td></tr>';


            // Years of Teaching Experience
            for ($i=0; $i<=21; $i++) {
                $filter_te = sprintf('filter_te_%d', $i);
                $$filter_te = (isset($filter_teaching_experience) && ($filter_teaching_experience == $i)) ? ' selected="selected"' : '';
            }

			$filter_tex = (isset($filter_teaching_experience) && ($filter_teaching_experience == '')) ? ' selected="selected"' : '';
			echo '<tr><td><label for="filter_teaching_experience_select"><b>Teaching Experience:</b></label></td>
			<td>
			<select name="filter_teaching_experience" id="filter_teaching_experience_select" onChange="this.form.submit()">
				<option value="" '.$filter_tex.'>Choose...</option>';

            for ($i=0; $i<=20; $i++) {
                $c = $i + 1;
                $filter_selected = sprintf('filter_te_%d', $c);
                echo "<option value=\"$c\" ".$$filter_selected.">$i</option>";
            }

                $filter_te_more20 = (isset($filter_teaching_experience) && ($filter_teaching_experience == '22')) ? ' selected="selected"' : '';
                echo '<option value="22"'.$filter_te_more20.'>more than 20 years</opion>';

            echo '</select> years
            </td></tr>';


            // Years of Management experience
            for ($i=0; $i<=21; $i++) {
                $filter_me = sprintf('filter_me_%d', $i);
                $$filter_me = (isset($filter_management_experience) && ($filter_management_experience == $i)) ? ' selected="selected"' : '';
            }

			$filter_mex = (isset($filter_management_experience) && ($filter_management_experience == '')) ? ' selected="selected"' : '';
			echo '<tr><td><label for="filter_management_experience_select"><b>Management Experience:</b></label></td>
			<td>
			<select name="filter_management_experience" id="filter_management_experience_select" onChange="this.form.submit()">
				<option value="" '.$filter_mex.'>Choose...</option>';

            for ($i=0; $i<=20; $i++) {
                $c = $i + 1;
                $filter_selected = sprintf('filter_me_%d', $c);
                echo "<option value=\"$c\" ".$$filter_selected.">$i</option>";
            }

                $filter_me_more20 = (isset($filter_management_experience) && ($filter_management_experience == '22')) ? ' selected="selected"' : '';
                echo '<option value="22"'.$filter_me_more20.'>more than 20 years</opion>';

            echo '</select> years
            </td></tr>';


            // Years of Support/Services/Administration experience
            for ($i=0; $i<=21; $i++) {
                $filter_se = sprintf('filter_se_%d', $i);
                $$filter_se = (isset($filter_support_experience) && ($filter_support_experience == $i)) ? ' selected="selected"' : '';
            }

			$filter_sel = (isset($filter_support_experience) && ($filter_support_experience == '')) ? ' selected="selected"' : '';
			echo '<tr><td><label for="filter_support_experience_select"><b>Support/Services/Administration Experience:</b></label></td>
			<td>
			<select name="filter_support_experience" id="filter_support_experience_select" onChange="this.form.submit()">
				<option value="" '.$filter_sel.'>Choose...</option>';

            for ($i=0; $i<=20; $i++) {
                $c = $i + 1;
                $filter_selected = sprintf('filter_se_%d', $c);
                echo "<option value=\"$c\" ".$$filter_selected.">$i</option>";
            }
                $filter_sel_more20 = (isset($filter_support_experience) && ($filter_support_experience == '22')) ? ' selected="selected"' : '';
                echo '<option value="22"'.$filter_sel_more20.'>more than 20 years</opion>';

            echo '</select> years
            </td></tr>';


			// Knowledge of examination and accreditation awarding bodies
			$filter_ab_checked = (isset($filter_awarding_bodies) && ($filter_awarding_bodies == '')) ? ' checked="checked"' : '';
			$filter_ab_checked_no = (isset($filter_awarding_bodies) && ($filter_awarding_bodies == '1')) ? ' checked="checked"' : '';
			$filter_ab_checked_yes = (isset($filter_awarding_bodies) && ($filter_awarding_bodies == '2')) ? ' checked="checked"' : '';
			
			echo '<tr><td><b>Knowledge of examination and accreditation awarding bodies:</b></td><td>
			<input type="radio" name="filter_awarding_bodies" value="" id="filter_ab" '.$filter_ab_checked.' onClick="this.form.submit()" /><label for="filter_ab">All</label>
			<input type="radio" name="filter_awarding_bodies" value="1" id="filter_ab_no" '.$filter_ab_checked_no.' onClick="this.form.submit()" /><label for="filter_ab_no">No</label>
			<input type="radio" name="filter_awarding_bodies" value="2" id="filter_ab_yes" '.$filter_ab_checked_yes.' onClick="this.form.submit()" /><label for="filter_ab_yes">Yes</label>
            </td></tr>';

            echo '<tr><td valign="top" colspan="2"><b>Level of skills for software/ICT equipment:</b>';
            echo '<table style="margin-top:10px; width:95%;">';

            // MS Office Word
			$filter_sw_word_all = (isset($filter_sw_word) && ($filter_sw_word == '')) ? ' selected="selected"' : '';
			$filter_sw_word_none = (isset($filter_sw_word) && ($filter_sw_word == '1')) ? ' selected="selected"' : '';
			$filter_sw_word_beginner = (isset($filter_sw_word) && ($filter_sw_word == '2')) ? ' selected="selected"' : '';
			$filter_sw_word_intermediate = (isset($filter_sw_word) && ($filter_sw_word == '3')) ? ' selected="selected"' : '';
			$filter_sw_word_advanced = (isset($filter_sw_word) && ($filter_sw_word == '4')) ? ' selected="selected"' : '';
            
            echo '<tr><td><label for="filter_sw_word">MS Office Word:</label></td><td>';
            echo '<select name="filter_sw_word" id="filter_sw_word" onChange="this.form.submit()">
                    <option value=""'.$filter_sw_word_all.'>Choose...</option>
                    <option value="1"'.$filter_sw_word_none.'>None</option>
                    <option value="2"'.$filter_sw_word_beginner.'>Beginner</option>
                    <option value="3"'.$filter_sw_word_intermediate.'>Intermediate</option>
                    <option value="4"'.$filter_sw_word_advanced.'>Advanced</option>
                  </select>';
            echo '</td>';

            // MS Office Excel
			$filter_sw_excel_all = (isset($filter_sw_excel) && ($filter_sw_excel == '')) ? ' selected="selected"' : '';
			$filter_sw_excel_none = (isset($filter_sw_excel) && ($filter_sw_excel == '1')) ? ' selected="selected"' : '';
			$filter_sw_excel_beginner = (isset($filter_sw_excel) && ($filter_sw_excel == '2')) ? ' selected="selected"' : '';
			$filter_sw_excel_intermediate = (isset($filter_sw_excel) && ($filter_sw_excel == '3')) ? ' selected="selected"' : '';
			$filter_sw_excel_advanced = (isset($filter_sw_excel) && ($filter_sw_excel == '4')) ? ' selected="selected"' : '';
            
            echo '<td><label for="filter_sw_excel">MS Office Excel:</label></td><td>';
            echo '<select name="filter_sw_excel" id="filter_sw_excel" onChange="this.form.submit()">
                    <option value=""'.$filter_sw_excel_all.'>Choose...</option>
                    <option value="1"'.$filter_sw_excel_none.'>None</option>
                    <option value="2"'.$filter_sw_excel_beginner.'>Beginner</option>
                    <option value="3"'.$filter_sw_excel_intermediate.'>Intermediate</option>
                    <option value="4"'.$filter_sw_excel_advanced.'>Advanced</option>
                  </select>';
            echo '</td></tr>';

            // MS Office Access
			$filter_sw_access_all = (isset($filter_sw_access) && ($filter_sw_access == '')) ? ' selected="selected"' : '';
			$filter_sw_access_none = (isset($filter_sw_access) && ($filter_sw_access == '1')) ? ' selected="selected"' : '';
			$filter_sw_access_beginner = (isset($filter_sw_access) && ($filter_sw_access == '2')) ? ' selected="selected"' : '';
			$filter_sw_access_intermediate = (isset($filter_sw_access) && ($filter_sw_access == '3')) ? ' selected="selected"' : '';
			$filter_sw_access_advanced = (isset($filter_sw_access) && ($filter_sw_access == '4')) ? ' selected="selected"' : '';
            
            echo '<tr><td><label for="filter_sw_access">MS Office Access:</label></td><td>';
            echo '<select name="filter_sw_access" id="filter_sw_access" onChange="this.form.submit()">
                    <option value=""'.$filter_sw_access_all.'>Choose...</option>
                    <option value="1"'.$filter_sw_access_none.'>None</option>
                    <option value="2"'.$filter_sw_access_beginner.'>Beginner</option>
                    <option value="3"'.$filter_sw_access_intermediate.'>Intermediate</option>
                    <option value="4"'.$filter_sw_access_advanced.'>Advanced</option>
                  </select>';
            echo '</td>';

            // MS Office Powerpoint
			$filter_sw_powerpoint_all = (isset($filter_sw_powerpoint) && ($filter_sw_powerpoint == '')) ? ' selected="selected"' : '';
			$filter_sw_powerpoint_none = (isset($filter_sw_powerpoint) && ($filter_sw_powerpoint == '1')) ? ' selected="selected"' : '';
			$filter_sw_powerpoint_beginner = (isset($filter_sw_powerpoint) && ($filter_sw_powerpoint == '2')) ? ' selected="selected"' : '';
			$filter_sw_powerpoint_intermediate = (isset($filter_sw_powerpoint) && ($filter_sw_powerpoint == '3')) ? ' selected="selected"' : '';
			$filter_sw_powerpoint_advanced = (isset($filter_sw_powerpoint) && ($filter_sw_powerpoint == '4')) ? ' selected="selected"' : '';
            
            echo '<td><label for="filter_sw_powerpoint">MS Office Powerpoint:</label></td><td>';
            echo '<select name="filter_sw_powerpoint" id="filter_sw_powerpoint" onChange="this.form.submit()">
                    <option value=""'.$filter_sw_powerpoint_all.'>Choose...</option>
                    <option value="1"'.$filter_sw_powerpoint_none.'>None</option>
                    <option value="2"'.$filter_sw_powerpoint_beginner.'>Beginner</option>
                    <option value="3"'.$filter_sw_powerpoint_intermediate.'>Intermediate</option>
                    <option value="4"'.$filter_sw_powerpoint_advanced.'>Advanced</option>
                  </select>';
            echo '</td></tr>';

            // MS Office Outlook
			$filter_sw_outlook_all = (isset($filter_sw_outlook) && ($filter_sw_outlook == '')) ? ' selected="selected"' : '';
			$filter_sw_outlook_none = (isset($filter_sw_outlook) && ($filter_sw_outlook == '1')) ? ' selected="selected"' : '';
			$filter_sw_outlook_beginner = (isset($filter_sw_outlook) && ($filter_sw_outlook == '2')) ? ' selected="selected"' : '';
			$filter_sw_outlook_intermediate = (isset($filter_sw_outlook) && ($filter_sw_outlook == '3')) ? ' selected="selected"' : '';
			$filter_sw_outlook_advanced = (isset($filter_sw_outlook) && ($filter_sw_outlook == '4')) ? ' selected="selected"' : '';
            
            echo '<tr><td><label for="filter_sw_outlook">MS Office Outlook:</label></td><td>';
            echo '<select name="filter_sw_outlook" id="filter_sw_outlook" onChange="this.form.submit()">
                    <option value=""'.$filter_sw_outlook_all.'>Choose...</option>
                    <option value="1"'.$filter_sw_outlook_none.'>None</option>
                    <option value="2"'.$filter_sw_outlook_beginner.'>Beginner</option>
                    <option value="3"'.$filter_sw_outlook_intermediate.'>Intermediate</option>
                    <option value="4"'.$filter_sw_outlook_advanced.'>Advanced</option>
                  </select>';
            echo '</td>';

            // Internet
			$filter_internet_all = (isset($filter_internet) && ($filter_internet == '')) ? ' selected="selected"' : '';
			$filter_internet_none = (isset($filter_internet) && ($filter_internet == '1')) ? ' selected="selected"' : '';
			$filter_internet_beginner = (isset($filter_internet) && ($filter_internet == '2')) ? ' selected="selected"' : '';
			$filter_internet_intermediate = (isset($filter_internet) && ($filter_internet == '3')) ? ' selected="selected"' : '';
			$filter_internet_advanced = (isset($filter_internet) && ($filter_internet == '4')) ? ' selected="selected"' : '';
            
            echo '<td><label for="filter_internet">Internet:</label></td><td>';
            echo '<select name="filter_internet" id="filter_internet" onChange="this.form.submit()">
                    <option value=""'.$filter_internet_all.'>Choose...</option>
                    <option value="1"'.$filter_internet_none.'>None</option>
                    <option value="2"'.$filter_internet_beginner.'>Beginner</option>
                    <option value="3"'.$filter_internet_intermediate.'>Intermediate</option>
                    <option value="4"'.$filter_internet_advanced.'>Advanced</option>
                  </select>';
            echo '</td></tr>';

            // Intranets
			$filter_intranets_all = (isset($filter_intranets) && ($filter_intranets == '')) ? ' selected="selected"' : '';
			$filter_intranets_none = (isset($filter_intranets) && ($filter_intranets == '1')) ? ' selected="selected"' : '';
			$filter_intranets_beginner = (isset($filter_intranets) && ($filter_intranets == '2')) ? ' selected="selected"' : '';
			$filter_intranets_intermediate = (isset($filter_intranets) && ($filter_intranets == '3')) ? ' selected="selected"' : '';
			$filter_intranets_advanced = (isset($filter_intranets) && ($filter_intranets == '4')) ? ' selected="selected"' : '';
            
            echo '<tr><td><label for="filter_intranets">Intranets:</label></td><td>';
            echo '<select name="filter_intranets" id="filter_intranets" onChange="this.form.submit()">
                    <option value=""'.$filter_intranets_all.'>Choose...</option>
                    <option value="1"'.$filter_intranets_none.'>None</option>
                    <option value="2"'.$filter_intranets_beginner.'>Beginner</option>
                    <option value="3"'.$filter_intranets_intermediate.'>Intermediate</option>
                    <option value="4"'.$filter_intranets_advanced.'>Advanced</option>
                  </select>';
            echo '</td>';

            // Virtual Learning Environments
			$filter_vles_all = (isset($filter_vles) && ($filter_vles == '')) ? ' selected="selected"' : '';
			$filter_vles_none = (isset($filter_vles) && ($filter_vles == '1')) ? ' selected="selected"' : '';
			$filter_vles_beginner = (isset($filter_vles) && ($filter_vles == '2')) ? ' selected="selected"' : '';
			$filter_vles_intermediate = (isset($filter_vles) && ($filter_vles == '3')) ? ' selected="selected"' : '';
			$filter_vles_advanced = (isset($filter_vles) && ($filter_vles == '4')) ? ' selected="selected"' : '';
            
            echo '<td><label for="filter_vles">Virtual Learning Environments:</label></td><td>';
            echo '<select name="filter_vles" id="filter_vles" onChange="this.form.submit()">
                    <option value=""'.$filter_vles_all.'>Choose...</option>
                    <option value="1"'.$filter_vles_none.'>None</option>
                    <option value="2"'.$filter_vles_beginner.'>Beginner</option>
                    <option value="3"'.$filter_vles_intermediate.'>Intermediate</option>
                    <option value="4"'.$filter_vles_advanced.'>Advanced</option>
                  </select>';
            echo '</td></tr>';

            // Interactive Whiteboards
			$filter_iwboards_all = (isset($filter_iwboards) && ($filter_iwboards == '')) ? ' selected="selected"' : '';
			$filter_iwboards_none = (isset($filter_iwboards) && ($filter_iwboards == '1')) ? ' selected="selected"' : '';
			$filter_iwboards_beginner = (isset($filter_iwboards) && ($filter_iwboards == '2')) ? ' selected="selected"' : '';
			$filter_iwboards_intermediate = (isset($filter_iwboards) && ($filter_iwboards == '3')) ? ' selected="selected"' : '';
			$filter_iwboards_advanced = (isset($filter_iwboards) && ($filter_iwboards == '4')) ? ' selected="selected"' : '';
            
            echo '<tr><td><label for="filter_iwboards">Interactive Whiteboards:</label></td><td>';
            echo '<select name="filter_iwboards" id="filter_iwboards" onChange="this.form.submit()">
                    <option value=""'.$filter_iwboards_all.'>Choose...</option>
                    <option value="1"'.$filter_iwboards_none.'>None</option>
                    <option value="2"'.$filter_iwboards_beginner.'>Beginner</option>
                    <option value="3"'.$filter_iwboards_intermediate.'>Intermediate</option>
                    <option value="4"'.$filter_iwboards_advanced.'>Advanced</option>
                  </select>';
            echo '</td>';

            // E-learning Resources
			$filter_elearn_res_all = (isset($filter_elearn_res) && ($filter_elearn_res == '')) ? ' selected="selected"' : '';
			$filter_elearn_res_none = (isset($filter_elearn_res) && ($filter_elearn_res == '1')) ? ' selected="selected"' : '';
			$filter_elearn_res_beginner = (isset($filter_elearn_res) && ($filter_elearn_res == '2')) ? ' selected="selected"' : '';
			$filter_elearn_res_intermediate = (isset($filter_elearn_res) && ($filter_elearn_res == '3')) ? ' selected="selected"' : '';
			$filter_elearn_res_advanced = (isset($filter_elearn_res) && ($filter_elearn_res == '4')) ? ' selected="selected"' : '';
            
            echo '<td><label for="filter_elearn_res">E-learning Resources:</label></td><td>';
            echo '<select name="filter_elearn_res" id="filter_elearn_res" onChange="this.form.submit()">
                    <option value=""'.$filter_elearn_res_all.'>Choose...</option>
                    <option value="1"'.$filter_elearn_res_none.'>None</option>
                    <option value="2"'.$filter_elearn_res_beginner.'>Beginner</option>
                    <option value="3"'.$filter_elearn_res_intermediate.'>Intermediate</option>
                    <option value="4"'.$filter_elearn_res_advanced.'>Advanced</option>
                  </select>';
            echo '</td></tr>';

            echo '
            </table>

			</td></tr>';

            // Other Skills 
            echo '<tr><td valign="top" colspan="2"><b>Other Skills:</b>';

            echo '<table style="margin-top:10px; width:95%;">';

            // Staff management
			$filter_os_sm_all = (isset($filter_os_sm) && ($filter_os_sm == '')) ? ' selected="selected"' : '';
			$filter_os_sm_none = (isset($filter_os_sm) && ($filter_os_sm == '1')) ? ' selected="selected"' : '';
			$filter_os_sm_beginner = (isset($filter_os_sm) && ($filter_os_sm == '2')) ? ' selected="selected"' : '';
			$filter_os_sm_intermediate = (isset($filter_os_sm) && ($filter_os_sm == '3')) ? ' selected="selected"' : '';
			$filter_os_sm_advanced = (isset($filter_os_sm) && ($filter_os_sm == '4')) ? ' selected="selected"' : '';
            
            echo '<tr><td><label for="filter_os_sm">Staff Management:</label></td><td>';
            echo '<select name="filter_os_sm" id="filter_os_sm" onChange="this.form.submit()">
                    <option value=""'.$filter_os_sm_all.'>Choose...</option>
                    <option value="1"'.$filter_os_sm_none.'>Never</option>
                    <option value="2"'.$filter_os_sm_beginner.'>Seldom</option>
                    <option value="3"'.$filter_os_sm_intermediate.'>Occasionally</option>
                    <option value="4"'.$filter_os_sm_advanced.'>Regularly</option>
                  </select>';
            echo '</td>';

            // Course management
			$filter_os_course_mgmt_all = (isset($filter_os_course_mgmt) && ($filter_os_course_mgmt == '')) ? ' selected="selected"' : '';
			$filter_os_course_mgmt_none = (isset($filter_os_course_mgmt) && ($filter_os_course_mgmt == '1')) ? ' selected="selected"' : '';
			$filter_os_course_mgmt_beginner = (isset($filter_os_course_mgmt) && ($filter_os_course_mgmt == '2')) ? ' selected="selected"' : '';
			$filter_os_course_mgmt_intermediate = (isset($filter_os_course_mgmt) && ($filter_os_course_mgmt == '3')) ? ' selected="selected"' : '';
			$filter_os_course_mgmt_advanced = (isset($filter_os_course_mgmt) && ($filter_os_course_mgmt == '4')) ? ' selected="selected"' : '';
            
            echo '<td><label for="filter_os_course_mgmt">Course Management:</label></td><td>';
            echo '<select name="filter_os_course_mgmt" id="filter_os_course_mgmt" onChange="this.form.submit()">
                    <option value=""'.$filter_os_course_mgmt_all.'>Choose...</option>
                    <option value="1"'.$filter_os_course_mgmt_none.'>Never</option>
                    <option value="2"'.$filter_os_course_mgmt_beginner.'>Seldom</option>
                    <option value="3"'.$filter_os_course_mgmt_intermediate.'>Occasionally</option>
                    <option value="4"'.$filter_os_course_mgmt_advanced.'>Regularly</option>
                  </select>';
            echo '</td></tr>';

            // Budget Management
			$filter_os_budget_mgmt_all = (isset($filter_os_budget_mgmt) && ($filter_os_budget_mgmt == '')) ? ' selected="selected"' : '';
			$filter_os_budget_mgmt_none = (isset($filter_os_budget_mgmt) && ($filter_os_budget_mgmt == '1')) ? ' selected="selected"' : '';
			$filter_os_budget_mgmt_beginner = (isset($filter_os_budget_mgmt) && ($filter_os_budget_mgmt == '2')) ? ' selected="selected"' : '';
			$filter_os_budget_mgmt_intermediate = (isset($filter_os_budget_mgmt) && ($filter_os_budget_mgmt == '3')) ? ' selected="selected"' : '';
			$filter_os_budget_mgmt_advanced = (isset($filter_os_budget_mgmt) && ($filter_os_budget_mgmt == '4')) ? ' selected="selected"' : '';
            
            echo '<tr><td><label for="filter_os_budget_mgmt">Budget Management:</label></td><td>';
            echo '<select name="filter_os_budget_mgmt" id="filter_os_budget_mgmt" onChange="this.form.submit()">
                    <option value=""'.$filter_os_budget_mgmt_all.'>Choose...</option>
                    <option value="1"'.$filter_os_budget_mgmt_none.'>Never</option>
                    <option value="2"'.$filter_os_budget_mgmt_beginner.'>Seldom</option>
                    <option value="3"'.$filter_os_budget_mgmt_intermediate.'>Occasionally</option>
                    <option value="4"'.$filter_os_budget_mgmt_advanced.'>Regularly</option>
                  </select>';
            echo '</td>';

            // Project Management
			$filter_os_project_mgmt_all = (isset($filter_os_project_mgmt) && ($filter_os_project_mgmt == '')) ? ' selected="selected"' : '';
			$filter_os_project_mgmt_none = (isset($filter_os_project_mgmt) && ($filter_os_project_mgmt == '1')) ? ' selected="selected"' : '';
			$filter_os_project_mgmt_beginner = (isset($filter_os_project_mgmt) && ($filter_os_project_mgmt == '2')) ? ' selected="selected"' : '';
			$filter_os_project_mgmt_intermediate = (isset($filter_os_project_mgmt) && ($filter_os_project_mgmt == '3')) ? ' selected="selected"' : '';
			$filter_os_project_mgmt_advanced = (isset($filter_os_project_mgmt) && ($filter_os_project_mgmt == '4')) ? ' selected="selected"' : '';
            
            echo '<td><label for="filter_os_project_mgmt">Project Management:</label></td><td>';
            echo '<select name="filter_os_project_mgmt" id="filter_os_project_mgmt" onChange="this.form.submit()">
                    <option value=""'.$filter_os_project_mgmt_all.'>Choose...</option>
                    <option value="1"'.$filter_os_project_mgmt_none.'>Never</option>
                    <option value="2"'.$filter_os_project_mgmt_beginner.'>Seldom</option>
                    <option value="3"'.$filter_os_project_mgmt_intermediate.'>Occasionally</option>
                    <option value="4"'.$filter_os_project_mgmt_advanced.'>Regularly</option>
                  </select>';
            echo '</td></tr>';

            // Coaching/Mentoring
			$filter_os_coach_ment_all = (isset($filter_os_coach_ment) && ($filter_os_coach_ment == '')) ? ' selected="selected"' : '';
			$filter_os_coach_ment_none = (isset($filter_os_coach_ment) && ($filter_os_coach_ment == '1')) ? ' selected="selected"' : '';
			$filter_os_coach_ment_beginner = (isset($filter_os_coach_ment) && ($filter_os_coach_ment == '2')) ? ' selected="selected"' : '';
			$filter_os_coach_ment_intermediate = (isset($filter_os_coach_ment) && ($filter_os_coach_ment == '3')) ? ' selected="selected"' : '';
			$filter_os_coach_ment_advanced = (isset($filter_os_coach_ment) && ($filter_os_coach_ment == '4')) ? ' selected="selected"' : '';
            
            echo '<tr><td><label for="filter_os_coach_ment">Coaching/Mentoring:</label></td><td>';
            echo '<select name="filter_os_coach_ment" id="filter_os_coach_ment" onChange="this.form.submit()">
                    <option value=""'.$filter_os_coach_ment_all.'>Choose...</option>
                    <option value="1"'.$filter_os_coach_ment_none.'>Never</option>
                    <option value="2"'.$filter_os_coach_ment_beginner.'>Seldom</option>
                    <option value="3"'.$filter_os_coach_ment_intermediate.'>Occasionally</option>
                    <option value="4"'.$filter_os_coach_ment_advanced.'>Regularly</option>
                  </select>';
            echo '</td>';

            // Presentation Skills
			$filter_os_presentation_all = (isset($filter_os_presentation) && ($filter_os_presentation == '')) ? ' selected="selected"' : '';
			$filter_os_presentation_none = (isset($filter_os_presentation) && ($filter_os_presentation == '1')) ? ' selected="selected"' : '';
			$filter_os_presentation_beginner = (isset($filter_os_presentation) && ($filter_os_presentation == '2')) ? ' selected="selected"' : '';
			$filter_os_presentation_intermediate = (isset($filter_os_presentation) && ($filter_os_presentation == '3')) ? ' selected="selected"' : '';
			$filter_os_presentation_advanced = (isset($filter_os_presentation) && ($filter_os_presentation == '4')) ? ' selected="selected"' : '';
            
            echo '<td><label for="filter_os_presentation">Presentation Skills:</label></td><td>';
            echo '<select name="filter_os_presentation" id="filter_os_presentation" onChange="this.form.submit()">
                    <option value=""'.$filter_os_presentation_all.'>Choose...</option>
                    <option value="1"'.$filter_os_presentation_none.'>Never</option>
                    <option value="2"'.$filter_os_presentation_beginner.'>Seldom</option>
                    <option value="3"'.$filter_os_presentation_intermediate.'>Occasionally</option>
                    <option value="4"'.$filter_os_presentation_advanced.'>Regularly</option>
                  </select>';
            echo '</td></tr>';


            // Student Advice and Guidance
			$filter_os_saag_all = (isset($filter_os_saag) && ($filter_os_saag == '')) ? ' selected="selected"' : '';
			$filter_os_saag_none = (isset($filter_os_saag) && ($filter_os_saag == '1')) ? ' selected="selected"' : '';
			$filter_os_saag_beginner = (isset($filter_os_saag) && ($filter_os_saag == '2')) ? ' selected="selected"' : '';
			$filter_os_saag_intermediate = (isset($filter_os_saag) && ($filter_os_saag == '3')) ? ' selected="selected"' : '';
			$filter_os_saag_advanced = (isset($filter_os_saag) && ($filter_os_saag == '4')) ? ' selected="selected"' : '';
            
            echo '<tr><td><label for="filter_os_saag">Student Advice and Guidance:</label></td><td>';
            echo '<select name="filter_os_saag" id="filter_os_saag" onChange="this.form.submit()">
                    <option value=""'.$filter_os_saag_all.'>Choose...</option>
                    <option value="1"'.$filter_os_saag_none.'>Never</option>
                    <option value="2"'.$filter_os_saag_beginner.'>Seldom</option>
                    <option value="3"'.$filter_os_saag_intermediate.'>Occasionally</option>
                    <option value="4"'.$filter_os_saag_advanced.'>Regularly</option>
                  </select>';
            echo '</td>';

            // Training Delivery
			$filter_os_td_all = (isset($filter_os_td) && ($filter_os_td == '')) ? ' selected="selected"' : '';
			$filter_os_td_none = (isset($filter_os_td) && ($filter_os_td == '1')) ? ' selected="selected"' : '';
			$filter_os_td_beginner = (isset($filter_os_td) && ($filter_os_td == '2')) ? ' selected="selected"' : '';
			$filter_os_td_intermediate = (isset($filter_os_td) && ($filter_os_td == '3')) ? ' selected="selected"' : '';
			$filter_os_td_advanced = (isset($filter_os_td) && ($filter_os_td == '4')) ? ' selected="selected"' : '';
            
            echo '<td><label for="filter_os_td">Training Delivery:</label></td><td>';
            echo '<select name="filter_os_td" id="filter_os_td" onChange="this.form.submit()">
                    <option value=""'.$filter_os_td_all.'>Choose...</option>
                    <option value="1"'.$filter_os_td_none.'>Never</option>
                    <option value="2"'.$filter_os_td_beginner.'>Seldom</option>
                    <option value="3"'.$filter_os_td_intermediate.'>Occasionally</option>
                    <option value="4"'.$filter_os_td_advanced.'>Regularly</option>
                  </select>';
            echo '</td></tr>';

            // Report Writing Skills
			$filter_os_rws_all = (isset($filter_os_rws) && ($filter_os_rws == '')) ? ' selected="selected"' : '';
			$filter_os_rws_none = (isset($filter_os_rws) && ($filter_os_rws == '1')) ? ' selected="selected"' : '';
			$filter_os_rws_beginner = (isset($filter_os_rws) && ($filter_os_rws == '2')) ? ' selected="selected"' : '';
			$filter_os_rws_intermediate = (isset($filter_os_rws) && ($filter_os_rws == '3')) ? ' selected="selected"' : '';
			$filter_os_rws_advanced = (isset($filter_os_rws) && ($filter_os_rws == '4')) ? ' selected="selected"' : '';
            
            echo '<tr><td><label for="filter_os_rws">Report Writing Skills:</label></td><td>';
            echo '<select name="filter_os_rws" id="filter_os_rws" onChange="this.form.submit()">
                    <option value=""'.$filter_os_rws_all.'>Choose...</option>
                    <option value="1"'.$filter_os_rws_none.'>Never</option>
                    <option value="2"'.$filter_os_rws_beginner.'>Seldom</option>
                    <option value="3"'.$filter_os_rws_intermediate.'>Occasionally</option>
                    <option value="4"'.$filter_os_rws_advanced.'>Regularly</option>
                  </select>';
            echo '</td>';


            // Report Writing Skills
			$filter_os_rk_all = (isset($filter_os_rk) && ($filter_os_rk == '')) ? ' selected="selected"' : '';
			$filter_os_rk_none = (isset($filter_os_rk) && ($filter_os_rk == '1')) ? ' selected="selected"' : '';
			$filter_os_rk_beginner = (isset($filter_os_rk) && ($filter_os_rk == '2')) ? ' selected="selected"' : '';
			$filter_os_rk_intermediate = (isset($filter_os_rk) && ($filter_os_rk == '3')) ? ' selected="selected"' : '';
			$filter_os_rk_advanced = (isset($filter_os_rk) && ($filter_os_rk == '4')) ? ' selected="selected"' : '';
            
            echo '<td><label for="filter_os_rk">Record Keeping:</label></td><td>';
            echo '<select name="filter_os_rk" id="filter_os_rk" onChange="this.form.submit()">
                    <option value=""'.$filter_os_rk_all.'>Choose...</option>
                    <option value="1"'.$filter_os_rk_none.'>Never</option>
                    <option value="2"'.$filter_os_rk_beginner.'>Seldom</option>
                    <option value="3"'.$filter_os_rk_intermediate.'>Occasionally</option>
                    <option value="4"'.$filter_os_rk_advanced.'>Regularly</option>
                  </select>';
            echo '</td></tr>';

            echo '
            </table>

			</td></tr>';
            
            // Other Areas of Knowledge
            echo '<tr><td valign="top" colspan="2"><b>Other Areas of Knowledge</b>:';

            echo '<table style="margin-top:10px; width:95%;">';

            // Health and Safety
			$filter_ok_hs_all = (isset($filter_ok_hs) && ($filter_ok_hs == '')) ? ' selected="selected"' : '';
			$filter_ok_hs_none = (isset($filter_ok_hs) && ($filter_ok_hs == '1')) ? ' selected="selected"' : '';
			$filter_ok_hs_beginner = (isset($filter_ok_hs) && ($filter_ok_hs == '2')) ? ' selected="selected"' : '';
			$filter_ok_hs_intermediate = (isset($filter_ok_hs) && ($filter_ok_hs == '3')) ? ' selected="selected"' : '';
			$filter_ok_hs_advanced = (isset($filter_ok_hs) && ($filter_ok_hs == '4')) ? ' selected="selected"' : '';
            
            echo '<tr><td><label for="filter_ok_hs">Health and Safety:</label></td><td>';
            echo '<select name="filter_ok_hs" id="filter_ok_hs" onChange="this.form.submit()">
                    <option value=""'.$filter_ok_hs_all.'>Choose...</option>
                    <option value="1"'.$filter_ok_hs_none.'>No</option>
                    <option value="2"'.$filter_ok_hs_beginner.'>Beginner</option>
                    <option value="3"'.$filter_ok_hs_intermediate.'>Intermediate</option>
                    <option value="4"'.$filter_ok_hs_advanced.'>Advanced</option>
                  </select>';
            echo '</td>';

            // Equal Opportunities
			$filter_ok_eo_all = (isset($filter_ok_eo) && ($filter_ok_eo == '')) ? ' selected="selected"' : '';
			$filter_ok_eo_none = (isset($filter_ok_eo) && ($filter_ok_eo == '1')) ? ' selected="selected"' : '';
			$filter_ok_eo_beginner = (isset($filter_ok_eo) && ($filter_ok_eo == '2')) ? ' selected="selected"' : '';
			$filter_ok_eo_intermediate = (isset($filter_ok_eo) && ($filter_ok_eo == '3')) ? ' selected="selected"' : '';
			$filter_ok_eo_advanced = (isset($filter_ok_eo) && ($filter_ok_eo == '4')) ? ' selected="selected"' : '';
            
            echo '<td><label for="filter_ok_eo">Equal Opportunities:</label></td><td>';
            echo '<select name="filter_ok_eo" id="filter_ok_eo" onChange="this.form.submit()">
                    <option value=""'.$filter_ok_eo_all.'>Choose...</option>
                    <option value="1"'.$filter_ok_eo_none.'>No</option>
                    <option value="2"'.$filter_ok_eo_beginner.'>Beginner</option>
                    <option value="3"'.$filter_ok_eo_intermediate.'>Intermediate</option>
                    <option value="4"'.$filter_ok_eo_advanced.'>Advanced</option>
                  </select>';
            echo '</td></tr>';

            // Diversity
			$filter_ok_div_all = (isset($filter_ok_div) && ($filter_ok_div == '')) ? ' selected="selected"' : '';
			$filter_ok_div_none = (isset($filter_ok_div) && ($filter_ok_div == '1')) ? ' selected="selected"' : '';
			$filter_ok_div_beginner = (isset($filter_ok_div) && ($filter_ok_div == '2')) ? ' selected="selected"' : '';
			$filter_ok_div_intermediate = (isset($filter_ok_div) && ($filter_ok_div == '3')) ? ' selected="selected"' : '';
			$filter_ok_div_advanced = (isset($filter_ok_div) && ($filter_ok_div == '4')) ? ' selected="selected"' : '';
            
            echo '<tr><td><label for="filter_ok_div">Diversity:</label></td><td>';
            echo '<select name="filter_ok_div" id="filter_ok_div" onChange="this.form.submit()">
                    <option value=""'.$filter_ok_div_all.'>Choose...</option>
                    <option value="1"'.$filter_ok_div_none.'>No</option>
                    <option value="2"'.$filter_ok_div_beginner.'>Beginner</option>
                    <option value="3"'.$filter_ok_div_intermediate.'>Intermediate</option>
                    <option value="4"'.$filter_ok_div_advanced.'>Advanced</option>
                  </select>';
            echo '</td>';

            // First Aid
			$filter_ok_firstaid_all = (isset($filter_ok_firstaid) && ($filter_ok_firstaid == '')) ? ' selected="selected"' : '';
			$filter_ok_firstaid_none = (isset($filter_ok_firstaid) && ($filter_ok_firstaid == '1')) ? ' selected="selected"' : '';
			$filter_ok_firstaid_beginner = (isset($filter_ok_firstaid) && ($filter_ok_firstaid == '2')) ? ' selected="selected"' : '';
			$filter_ok_firstaid_intermediate = (isset($filter_ok_firstaid) && ($filter_ok_firstaid == '3')) ? ' selected="selected"' : '';
			$filter_ok_firstaid_advanced = (isset($filter_ok_firstaid) && ($filter_ok_firstaid == '4')) ? ' selected="selected"' : '';
            
            echo '<td><label for="filter_ok_firstaid">First Aid:</label></td><td>';
            echo '<select name="filter_ok_firstaid" id="filter_ok_firstaid" onChange="this.form.submit()">
                    <option value=""'.$filter_ok_firstaid_all.'>Choose...</option>
                    <option value="1"'.$filter_ok_firstaid_none.'>No</option>
                    <option value="2"'.$filter_ok_firstaid_beginner.'>Beginner</option>
                    <option value="3"'.$filter_ok_firstaid_intermediate.'>Intermediate</option>
                    <option value="4"'.$filter_ok_firstaid_advanced.'>Advanced</option>
                  </select>';
            echo '</td></tr>';

            // Quality Assurance/Standards
			$filter_ok_qas_all = (isset($filter_ok_qas) && ($filter_ok_qas == '')) ? ' selected="selected"' : '';
			$filter_ok_qas_none = (isset($filter_ok_qas) && ($filter_ok_qas == '1')) ? ' selected="selected"' : '';
			$filter_ok_qas_beginner = (isset($filter_ok_qas) && ($filter_ok_qas == '2')) ? ' selected="selected"' : '';
			$filter_ok_qas_intermediate = (isset($filter_ok_qas) && ($filter_ok_qas == '3')) ? ' selected="selected"' : '';
			$filter_ok_qas_advanced = (isset($filter_ok_qas) && ($filter_ok_qas == '4')) ? ' selected="selected"' : '';
            
            echo '<tr><td><label for="filter_ok_qas">Quality Assurance/Standards:</label></td><td>';
            echo '<select name="filter_ok_qas" id="filter_ok_qas" onChange="this.form.submit()">
                    <option value=""'.$filter_ok_qas_all.'>Choose...</option>
                    <option value="1"'.$filter_ok_qas_none.'>No</option>
                    <option value="2"'.$filter_ok_qas_beginner.'>Beginner</option>
                    <option value="3"'.$filter_ok_qas_intermediate.'>Intermediate</option>
                    <option value="4"'.$filter_ok_qas_advanced.'>Advanced</option>
                  </select>';
            echo '</td>';

            // Safeguarding
			$filter_ok_safeg_all = (isset($filter_ok_safeg) && ($filter_ok_safeg == '')) ? ' selected="selected"' : '';
			$filter_ok_safeg_none = (isset($filter_ok_safeg) && ($filter_ok_safeg == '1')) ? ' selected="selected"' : '';
			$filter_ok_safeg_beginner = (isset($filter_ok_safeg) && ($filter_ok_safeg == '2')) ? ' selected="selected"' : '';
			$filter_ok_safeg_intermediate = (isset($filter_ok_safeg) && ($filter_ok_safeg == '3')) ? ' selected="selected"' : '';
			$filter_ok_safeg_advanced = (isset($filter_ok_safeg) && ($filter_ok_safeg == '4')) ? ' selected="selected"' : '';
            
            echo '<td><label for="filter_ok_safeg">Safeguarding:</label></td><td>';
            echo '<select name="filter_ok_safeg" id="filter_ok_safeg" onChange="this.form.submit()">
                    <option value=""'.$filter_ok_safeg_all.'>Choose...</option>
                    <option value="1"'.$filter_ok_safeg_none.'>No</option>
                    <option value="2"'.$filter_ok_safeg_beginner.'>Beginner</option>
                    <option value="3"'.$filter_ok_safeg_intermediate.'>Intermediate</option>
                    <option value="4"'.$filter_ok_safeg_advanced.'>Advanced</option>
                  </select>';
            echo '</td></tr>';

            // Ofsted Inspection Framework
			$filter_ok_oif_all = (isset($filter_ok_oif) && ($filter_ok_oif == '')) ? ' selected="selected"' : '';
			$filter_ok_oif_none = (isset($filter_ok_oif) && ($filter_ok_oif == '1')) ? ' selected="selected"' : '';
			$filter_ok_oif_beginner = (isset($filter_ok_oif) && ($filter_ok_oif == '2')) ? ' selected="selected"' : '';
			$filter_ok_oif_intermediate = (isset($filter_ok_oif) && ($filter_ok_oif == '3')) ? ' selected="selected"' : '';
			$filter_ok_oif_advanced = (isset($filter_ok_oif) && ($filter_ok_oif == '4')) ? ' selected="selected"' : '';
            
            echo '<tr><td><label for="filter_ok_oif">Ofsted Inspection Framework:</label></td><td>';
            echo '<select name="filter_ok_oif" id="filter_ok_oif" onChange="this.form.submit()">
                    <option value=""'.$filter_ok_oif_all.'>Choose...</option>
                    <option value="1"'.$filter_ok_oif_none.'>No</option>
                    <option value="2"'.$filter_ok_oif_beginner.'>Beginner</option>
                    <option value="3"'.$filter_ok_oif_intermediate.'>Intermediate</option>
                    <option value="4"'.$filter_ok_oif_advanced.'>Advanced</option>
                  </select>';
            echo '</td>';

            // Framework for Excellence
			$filter_ok_ffe_all = (isset($filter_ok_ffe) && ($filter_ok_ffe == '')) ? ' selected="selected"' : '';
			$filter_ok_ffe_none = (isset($filter_ok_ffe) && ($filter_ok_ffe == '1')) ? ' selected="selected"' : '';
			$filter_ok_ffe_beginner = (isset($filter_ok_ffe) && ($filter_ok_ffe == '2')) ? ' selected="selected"' : '';
			$filter_ok_ffe_intermediate = (isset($filter_ok_ffe) && ($filter_ok_ffe == '3')) ? ' selected="selected"' : '';
			$filter_ok_ffe_advanced = (isset($filter_ok_ffe) && ($filter_ok_ffe == '4')) ? ' selected="selected"' : '';
            
            echo '<td><label for="filter_ok_ffe">Framework for Excellence:</label></td><td>';
            echo '<select name="filter_ok_ffe" id="filter_ok_ffe" onChange="this.form.submit()">
                    <option value=""'.$filter_ok_ffe_all.'>Choose...</option>
                    <option value="1"'.$filter_ok_ffe_none.'>No</option>
                    <option value="2"'.$filter_ok_ffe_beginner.'>Beginner</option>
                    <option value="3"'.$filter_ok_ffe_intermediate.'>Intermediate</option>
                    <option value="4"'.$filter_ok_ffe_advanced.'>Advanced</option>
                  </select>';
            echo '</td></tr>';

            echo '
            </table>

			</td></tr>	
			</table>';

			$itemnr = 0;
			//print the items in an analysed form
			
			$completed_ids_to_search = array();

            /* Job Filter */
            $job_id = 257;
			$ubj = array(); // users by job

			if (($filter_job != '' && $job_id != '')) {
				// Job ONLY
				$query = "SELECT completed FROM ".$CFG->prefix."feedback_value WHERE item = ".$job_id." and value = ".$filter_job."";
				$users_by_job = get_records_sql($query);
				foreach ($users_by_job as $user) {
					$ubj[] = $user->completed;
				}	
				$completed_ids_to_search = $ubj;
			}

            /* Professional Qualifications Filter */
            $prof_qual_id = 261;
			$ubpq = array(); // users by age array

			if (($filter_qualifications != '' && $prof_qual_id != '')) {
				// Prof Qual. ONLY
				$query = "SELECT completed FROM ".$CFG->prefix."feedback_value WHERE item = ".$prof_qual_id." and value = ".$filter_qualifications."";
				$users_by_prof_qual = get_records_sql($query);
				foreach ($users_by_prof_qual as $user) {
					$ubpq[] = $user->completed;
				}
				$completed_ids_to_search = $ubpq;
			}

            /* Teaching Qualifications Filter */
            $teach_qual_id = 263;
			$ubtq = array(); // users by teacher qualifications array

			if (($filter_teaching_qualifications != '' && $teach_qual_id != '')) {
				// Teach Qual. ONLY
				$query = "SELECT completed FROM ".$CFG->prefix."feedback_value WHERE item = ".$teach_qual_id." and value = ".$filter_teaching_qualifications."";
				$users_by_teach_qual = get_records_sql($query);
				foreach ($users_by_teach_qual as $user) {
					$ubtq[] = $user->completed;
				}
				$completed_ids_to_search = $ubtq;
			}

            /* Teaching Experience Filter */
            $teach_exp_id = 267;
			$ubte = array(); // users by teacher experience array

			if (($filter_teaching_experience != '' && $teach_exp_id != '')) {
				// Teach Experience ONLY
				$query = "SELECT completed FROM ".$CFG->prefix."feedback_value WHERE item = ".$teach_exp_id." and value = ".$filter_teaching_experience."";
				$users_by_teach_exp = get_records_sql($query);
				foreach ($users_by_teach_exp as $user) {
					$ubte[] = $user->completed;
				}
				$completed_ids_to_search = $ubte;
			}

            /* Management Experience Filter */
            $mgmt_exp_id = 269;
			$ubme = array(); // users by management experience array

			if (($filter_management_experience != '' && $mgmt_exp_id != '')) {
				// Management Experience ONLY
				$query = "SELECT completed FROM ".$CFG->prefix."feedback_value WHERE item = ".$mgmt_exp_id." and value = ".$filter_management_experience."";
				$users_by_mgmt_exp = get_records_sql($query);
				foreach ($users_by_mgmt_exp as $user) {
					$ubme[] = $user->completed;
				}
				$completed_ids_to_search = $ubme;
			}

            /* Support/Services/Administration Experience */
            $supp_exp_id = 271;
			$ubse = array(); // users by support experience array

			if (($filter_support_experience != '' && $supp_exp_id != '')) {
				// Support Experience ONLY
				$query = "SELECT completed FROM ".$CFG->prefix."feedback_value WHERE item = ".$supp_exp_id." and value = ".$filter_support_experience."";
				$users_by_supp_exp = get_records_sql($query);
				foreach ($users_by_supp_exp as $user) {
					$ubse[] = $user->completed;
				}
				$completed_ids_to_search = $ubse;
			}

            /* Knowledge of examination and accreditation awarding bodies */
            $exam_accred_id = 279;
			$ubab = array(); // users by knowledge of awarding bodies array

			if (($filter_awarding_bodies != '' && $exam_accred_id != '')) {
				// Knowledge of Exam.... ONLY
				$query = "SELECT completed FROM ".$CFG->prefix."feedback_value WHERE item = ".$exam_accred_id." and value = ".$filter_awarding_bodies."";
				$users_by_exam_accred = get_records_sql($query);
				foreach ($users_by_exam_accred as $user) {
					$ubab[] = $user->completed;
				}
				$completed_ids_to_search = $ubab;
			}

            // Level of skills for software/ICT equipment

            /* MS Office Word */
            $sw_word_id = 283;
			$ubsw_word = array();

			if (($filter_sw_word != '' && $sw_word_id != '')) {
				$query = "SELECT completed FROM ".$CFG->prefix."feedback_value WHERE item = ".$sw_word_id." and value = ".$filter_sw_word."";
				$users_by_sw_word = get_records_sql($query);
				foreach ($users_by_sw_word as $user) {
					$ubsw_word[] = $user->completed;
				}
				$completed_ids_to_search = $ubsw_word;
			}

            /* MS Office Excel */
            $sw_excel_id = 284;
			$ubsw_excel = array();

			if (($filter_sw_excel != '' && $sw_excel_id != '')) {
				$query = "SELECT completed FROM ".$CFG->prefix."feedback_value WHERE item = ".$sw_excel_id." and value = ".$filter_sw_excel."";
				$users_by_sw_excel = get_records_sql($query);
				foreach ($users_by_sw_excel as $user) {
					$ubsw_excel[] = $user->completed;
				}
				$completed_ids_to_search = $ubsw_excel;
			}

            /* MS Office Access */
            $sw_access_id = 285;
			$ubsw_access = array();

			if (($filter_sw_access != '' && $sw_access_id != '')) {
				$query = "SELECT completed FROM ".$CFG->prefix."feedback_value WHERE item = ".$sw_access_id." and value = ".$filter_sw_access."";
				$users_by_sw_access = get_records_sql($query);
				foreach ($users_by_sw_access as $user) {
					$ubsw_access[] = $user->completed;
				}
				$completed_ids_to_search = $ubsw_access;
			}

            /* MS Office Powerpoint */
            $sw_powerpoint_id = 286;
			$ubsw_powerpoint = array();

			if (($filter_sw_powerpoint != '' && $sw_powerpoint_id != '')) {
				$query = "SELECT completed FROM ".$CFG->prefix."feedback_value WHERE item = ".$sw_powerpoint_id." and value = ".$filter_sw_powerpoint."";
				$users_by_sw_powerpoint = get_records_sql($query);
				foreach ($users_by_sw_powerpoint as $user) {
					$ubsw_powerpoint[] = $user->completed;
				}
				$completed_ids_to_search = $ubsw_powerpoint;
			}

            /* MS Office Outlook */
            $sw_outlook_id = 287; // MS Office Outlook	
			$ubsw_outlook = array();

			if (($filter_sw_outlook != '' && $sw_accesw_id != '')) {
				$query = "SELECT completed FROM ".$CFG->prefix."feedback_value WHERE item = ".$sw_outlook_id." and value = ".$filter_sw_outlook."";
				$users_by_sw_outlook = get_records_sql($query);
				foreach ($users_by_sw_outlook as $user) {
					$ubsw_outlook[] = $user->completed;
				}
				$completed_ids_to_search = $ubsw_outlook;
			}

            /* Internet */
            $sw_internet_id = 288;
			$ubsw_internet = array();

			if (($filter_internet != '' && $sw_internet_id != '')) {
				$query = "SELECT completed FROM ".$CFG->prefix."feedback_value WHERE item = ".$sw_internet_id." and value = ".$filter_internet."";
				$users_by_sw_internet = get_records_sql($query);
				foreach ($users_by_sw_internet as $user) {
					$ubsw_internet[] = $user->completed;
				}
				$completed_ids_to_search = $ubsw_internet;
			}

            /* Intranets */
            $sw_intranets_id = 289;
			$ubsw_intranets = array();

			if (($filter_intranets != '' && $sw_intranets_id != '')) {
				$query = "SELECT completed FROM ".$CFG->prefix."feedback_value WHERE item = ".$sw_intranets_id." and value = ".$filter_intranets."";
				$users_by_sw_intranets = get_records_sql($query);
				foreach ($users_by_sw_intranets as $user) {
					$ubsw_intranets[] = $user->completed;
				}
				$completed_ids_to_search = $ubsw_intranets;
			}

            /* Virtual Learning Environments */
            $sw_vle_id = 290; // Virtual Learning Environments	
			$ubsw_vle = array();

			if (($filter_vles != '' && $sw_vle_id != '')) {
				$query = "SELECT completed FROM ".$CFG->prefix."feedback_value WHERE item = ".$sw_vle_id." and value = ".$filter_vles."";
				$users_by_sw_vle = get_records_sql($query);
				foreach ($users_by_sw_vle as $user) {
					$ubsw_vle[] = $user->completed;
				}
				$completed_ids_to_search = $ubsw_vle;
			}

            /* Interactive Whiteboards */
            $sw_whiteboards_id = 291; // Interactive Whiteboards	
			$ubsw_whiteboards = array();

			if (($filter_iwboards != '' && $sw_whiteboards_id != '')) {
				$query = "SELECT completed FROM ".$CFG->prefix."feedback_value WHERE item = ".$sw_whiteboards_id." and value = ".$filter_iwboards."";
				$users_by_sw_whiteboards = get_records_sql($query);
				foreach ($users_by_sw_whiteboards as $user) {
					$ubsw_whiteboards[] = $user->completed;
				}
				$completed_ids_to_search = $ubsw_whiteboards;
			}

            /* E-learning Resources */
            $sw_elr_id = 292;
			$ubsw_elr = array();

			if (($filter_elearn_res != '' && $sw_elr_id != '')) {
				$query = "SELECT completed FROM ".$CFG->prefix."feedback_value WHERE item = ".$sw_elr_id." and value = ".$filter_elearn_res."";
				$users_by_sw_elr = get_records_sql($query);
				foreach ($users_by_sw_elr as $user) {
					$ubsw_elr[] = $user->completed;
				}
				$completed_ids_to_search = $ubsw_elr;
			}


            // Other Skills

            /* Staff Management */
            $os_staffm_id = 295;
			$ubos_staffm = array();

			if (($filter_os_sm != '' && $os_staffm_id != '')) {
				$query = "SELECT completed FROM ".$CFG->prefix."feedback_value WHERE item = ".$os_staffm_id." and value = ".$filter_os_sm."";
				$users_by_os_staffm = get_records_sql($query);
				foreach ($users_by_os_staffm as $user) {
					$ubos_staffm[] = $user->completed;
				}
				$completed_ids_to_search = $ubos_staffm;
			}

            /* Course Management */
            $os_coursem_id = 296;
			$ubos_coursem = array();

			if (($filter_course_mgmt != '' && $os_coursem_id != '')) {
				$query = "SELECT completed FROM ".$CFG->prefix."feedback_value WHERE item = ".$os_coursem_id." and value = ".$filter_course_mgmt."";
				$users_by_os_coursem = get_records_sql($query);
				foreach ($users_by_os_coursem as $user) {
					$ubos_coursem[] = $user->completed;
				}
				$completed_ids_to_search = $ubos_coursem;
			}

            /* Budget Management */
            $os_budgetm_id = 297; // Budget Management	
			$ubos_budgetm = array();

			if (($filter_budget_mgmt != '' && $os_budgetm_id != '')) {
				$query = "SELECT completed FROM ".$CFG->prefix."feedback_value WHERE item = ".$os_budgetm_id." and value = ".$filter_budget_mgmt."";
				$users_by_os_budgetm = get_records_sql($query);
				foreach ($users_by_os_budgetm as $user) {
					$ubos_budgetm[] = $user->completed;
				}
				$completed_ids_to_search = $ubos_budgetm;
			}

            /* Project Management */
            $os_projectm_id = 298; // Project Management	
			$ubos_projectm = array();

			if (($filter_os_project_mgmt != '' && $os_projectm_id != '')) {
				$query = "SELECT completed FROM ".$CFG->prefix."feedback_value WHERE item = ".$os_projectm_id." and value = ".$filter_os_project_mgmt."";
				$users_by_os_projectm = get_records_sql($query);
				foreach ($users_by_os_projectm as $user) {
					$ubos_projectm[] = $user->completed;
				}
				$completed_ids_to_search = $ubos_projectm;
			}

            /* Coaching/Mentoring */
            $os_coachm_id = 299; // Coaching/Mentoring	
			$ubos_coachm = array();

			if (($filter_os_coach_ment != '' && $os_coachm_id != '')) {
				$query = "SELECT completed FROM ".$CFG->prefix."feedback_value WHERE item = ".$os_coachm_id." and value = ".$filter_os_coach_ment."";
				$users_by_os_coachm = get_records_sql($query);
				foreach ($users_by_os_coachm as $user) {
					$ubos_coachm[] = $user->completed;
				}
				$completed_ids_to_search = $ubos_coachm;
			}

            /* Presentation Skills */
            $os_pressk_id = 300; // Presentation Skills	
			$ubos_pressk = array();

			if (($filter_os_presentation != '' && $os_pressk_id != '')) {
				$query = "SELECT completed FROM ".$CFG->prefix."feedback_value WHERE item = ".$os_pressk_id." and value = ".$filter_os_presentation."";
				$users_by_os_pressk = get_records_sql($query);
				foreach ($users_by_os_pressk as $user) {
					$ubos_pressk[] = $user->completed;
				}
				$completed_ids_to_search = $ubos_pressk;
			}

            /* Student Advice and Guidance */
            $os_saag_id = 301; // Student Advice and Guidance	
			$ubos_saag = array();

			if (($filter_os_saag != '' && $os_saag_id != '')) {
				$query = "SELECT completed FROM ".$CFG->prefix."feedback_value WHERE item = ".$os_saag_id." and value = ".$filter_os_saag."";
				$users_by_os_saag = get_records_sql($query);
				foreach ($users_by_os_saag as $user) {
					$ubos_saag[] = $user->completed;
				}
				$completed_ids_to_search = $ubos_saag;
			}

            /* Training Delivery */
            $os_td_id = 302;
			$ubos_td = array();

			if (($filter_os_td != '' && $os_td_id != '')) {
				$query = "SELECT completed FROM ".$CFG->prefix."feedback_value WHERE item = ".$os_td_id." and value = ".$filter_os_td."";
				$users_by_os_td = get_records_sql($query);
				foreach ($users_by_os_td as $user) {
					$ubos_td[] = $user->completed;
				}
				$completed_ids_to_search = $ubos_td;
			}


            /* Report Writing Skills */
            $os_rws_id = 303; // Report Writing Skills	
			$ubos_rws = array();

			if (($filter_os_rws != '' && $os_rws_id != '')) {
				$query = "SELECT completed FROM ".$CFG->prefix."feedback_value WHERE item = ".$os_rws_id." and value = ".$filter_os_rws."";
				$users_by_os_rws = get_records_sql($query);
				foreach ($users_by_os_rws as $user) {
					$ubos_rws[] = $user->completed;
				}
				$completed_ids_to_search = $ubos_rws;
			}

            /* Record Keeping */
            $os_rk_id = 304; // Record Keeping
			$ubos_rk = array();

			if (($filter_os_rk != '' && $os_rk_id != '')) {
				$query = "SELECT completed FROM ".$CFG->prefix."feedback_value WHERE item = ".$os_rk_id." and value = ".$filter_os_rk."";
				$users_by_os_rk = get_records_sql($query);
				foreach ($users_by_os_rk as $user) {
					$ubos_rk[] = $user->completed;
				}
				$completed_ids_to_search = $ubos_rk;
			}


            // Other Areas of Knowledge

            /* Health and Safety */
            $ok_hs_id = 308;
			$ubok_hs = array();

			if (($filter_ok_hs != '' && $ok_hs_id != '')) {
				$query = "SELECT completed FROM ".$CFG->prefix."feedback_value WHERE item = ".$ok_hs_id." and value = ".$filter_ok_hs."";
				$users_by_ok_hs = get_records_sql($query);
				foreach ($users_by_ok_hs as $user) {
					$ubok_hs[] = $user->completed;
				}
				$completed_ids_to_search = $ubok_hs;
			}

            /* Equal Opportunities */
            $ok_eo_id = 309; // Equal Opportunities	
			$ubok_eo = array();

			if (($filter_ok_eo != '' && $ok_eo_id != '')) {
				$query = "SELECT completed FROM ".$CFG->prefix."feedback_value WHERE item = ".$ok_eo_id." and value = ".$filter_ok_eo."";
				$users_by_ok_eo = get_records_sql($query);
				foreach ($users_by_ok_eo as $user) {
					$ubok_eo[] = $user->completed;
				}
				$completed_ids_to_search = $ubok_eo;
			}

            /* Diversity */
            $ok_div_id = 310; // Diversity
			$ubok_div = array();

			if (($filter_ok_div != '' && $ok_div_id != '')) {
				$query = "SELECT completed FROM ".$CFG->prefix."feedback_value WHERE item = ".$ok_div_id." and value = ".$filter_ok_div."";
				$users_by_ok_div = get_records_sql($query);
				foreach ($users_by_ok_div as $user) {
					$ubok_div[] = $user->completed;
				}
				$completed_ids_to_search = $ubok_div;
			}

            /* First Aid */
            $ok_fa_id = 311;
			$ubok_fa = array();

			if (($filter_ok_firstaid != '' && $ok_fa_id != '')) {
				$query = "SELECT completed FROM ".$CFG->prefix."feedback_value WHERE item = ".$ok_fa_id." and value = ".$filter_ok_firstaid."";
				$users_by_ok_fa = get_records_sql($query);
				foreach ($users_by_ok_fa as $user) {
					$ubok_fa[] = $user->completed;
				}
				$completed_ids_to_search = $ubok_fa;
			}

            /* Quality Assurance / Standards */
            $ok_qas_id = 312;
			$ubok_qas = array();

			if (($filter_ok_qas != '' && $ok_qas_id != '')) {
				$query = "SELECT completed FROM ".$CFG->prefix."feedback_value WHERE item = ".$ok_qas_id." and value = ".$filter_ok_qas."";
				$users_by_ok_qas = get_records_sql($query);
				foreach ($users_by_ok_qas as $user) {
					$ubok_qas[] = $user->completed;
				}
				$completed_ids_to_search = $ubok_qas;
			}

            /* Safeguarding */
            $ok_sg_id = 313; // Safeguarding	
			$ubok_sg = array();

			if (($filter_ok_safeg != '' && $ok_sg_id != '')) {
				$query = "SELECT completed FROM ".$CFG->prefix."feedback_value WHERE item = ".$ok_sg_id." and value = ".$filter_ok_safeg."";
				$users_by_ok_sg = get_records_sql($query);
				foreach ($users_by_ok_sg as $user) {
					$ubok_sg[] = $user->completed;
				}
				$completed_ids_to_search = $ubok_sg;
			}

            /* Ofsted Inspection Framework */
            $ok_oif_id = 314;
			$ubok_oif = array();

			if (($filter_ok_oif != '' && $ok_oif_id != '')) {
				$query = "SELECT completed FROM ".$CFG->prefix."feedback_value WHERE item = ".$ok_oif_id." and value = ".$filter_ok_oif."";
				$users_by_ok_oif = get_records_sql($query);
				foreach ($users_by_ok_oif as $user) {
					$ubok_oif[] = $user->completed;
				}
				$completed_ids_to_search = $ubok_oif;
			}

            /* Framework for Excellence */
            $ok_ffe_id = 315; // Framework for Excellence
			$ubok_ffe = array();

			if (($filter_ok_ffe != '' && $ok_ffe_id != '')) {
				$query = "SELECT completed FROM ".$CFG->prefix."feedback_value WHERE item = ".$ok_ffe_id." and value = ".$filter_ok_ffe."";
				$users_by_ok_ffe = get_records_sql($query);
				foreach ($users_by_ok_ffe as $user) {
					$ubok_ffe[] = $user->completed;
				}
				$completed_ids_to_search = $ubok_ffe;
			}


			// This needs to intersect all non-empty arrays top-down
			$filter_count = 0;
			$to_intersect = array();

            $filters = array( 'ubj', 'ubpq', 'ubtq', 'ubte', 'ubme', 'ubse', 'ubab', 'ubsw_word', 'ubsw_excel', 'ubsw_access', 'ubsw_powerpoint', 'ubsw_outlook', 'ubsw_internet', 'ubsw_intranets', 'ubsw_vle', 'ubsw_whiteboards', 'ubsw_elr', 'ubos_staffm', 'ubos_coursem', 'ubos_budgetm', 'ubos_projectm', 'ubos_coachm', 'ubos_pressk', 'ubos_saag', 'ubos_td', 'ubos_rws', 'ubos_rk', 'ubok_hs', 'ubok_eo', 'ubok_div', 'ubok_fa', 'ubok_qas', 'ubok_sg', 'ubok_oif', 'ubokk_ffe');

            foreach ($filters as $filter) {
                if (count($$filter) > 0) { $filter_count++; $to_intersect[] = $$filter; }
            }

			// If two or more filters active: intersect these arrays

            for($i=0; $i <= $filter_count; $i++) {
                if ($i == 0 || $i == 1) {
                  // do nothing      
                } else if ($i == 2) {
                    $completed_ids_to_search = array_intersect($to_intersect[0],$to_intersect[1]);
                } else {
                    $count = $i - 1;
                    $completed_ids_to_search = array_intersect($completed_ids_to_search,$to_intersect[$count]);
                }
            }
			// Create and clause based on filters selected
			$and_clause = '';

			$in_charge = ($coursefilter == "0" && (!in_array($courseid, $invalid_cids)) && $filter_choice == 'filter_course') ? $courseid : $coursefilter;
			if ($in_charge != 0 && $in_charge != '') {
				$and_clause .= ' AND fbv.course_id = '.$in_charge.' ';
			}

			// Build completed and clause if filters set
			if ($filter_job != '' || $filter_qualifications != '' || $filter_teaching_qualifications != '' || $filter_teaching_experience != '' || $filter_management_experience != '' || $filter_support_experience != '' || $filter_awarding_bodies != '' || $filter_sw_word != '' || $filter_sw_excel != '' || $filter_sw_access != '' || $filter_sw_powerpoint != '' || $filter_sw_outlook != '' || $filter_internet != '' || $filter_intranets != '' || $filter_vles != '' || $filter_iwboards != '' || $filter_elearn_res != '' || $filter_os_sm != '' || $filter_os_course_mgmt != '' || $filter_os_budget_mgmt != '' || $filter_os_project_mgmt != '' || $filter_os_coach_ment != '' || $filter_os_presentation != '' || $filter_os_saag != '' || $filter_os_td != '' || $filter_os_rws != '' || $filter_os_rk != '' || $filter_ok_hs != '' || $filter_ok_eo != '' || $filter_ok_div != '' || $filter_ok_firstaid != '' || $filter_ok_qas != '' || $filter_ok_safeg != '' || $filter_ok_oif != '' || $filter_ok_ffe != '') {

				$completed_ids = implode(',', $completed_ids_to_search); // convert array to csv format
				$and_clause .= ' AND fbv.completed IN ('.$completed_ids.')';
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
			if ($filter_job != '' || $filter_qualifications != '' || $filter_teaching_qualifications != '' || $filter_teaching_experience != '' || $filter_management_experience != '' || $filter_support_experience != '' || $filter_awarding_bodies != '' || $filter_sw_word != '' || $filter_sw_excel != '' || $filter_sw_access != '' || $filter_sw_powerpoint != '' || $filter_sw_outlook != '' || $filter_internet != '' || $filter_intranets != '' || $filter_vles != '' || $filter_iwboards != '' || $filter_elearn_res != '' || $filter_os_sm != '' || $filter_os_course_mgmt != '' || $filter_os_budget_mgmt != '' || $filter_os_project_mgmt != '' || $filter_os_coach_ment != '' || $filter_os_presentation != '' || $filter_os_saag != '' || $filter_os_td != '' || $filter_os_rws != '' || $filter_os_rk != '' || $filter_ok_hs != '' || $filter_ok_eo != '' || $filter_ok_div != '' || $filter_ok_firstaid != '' || $filter_ok_qas != '' || $filter_ok_safeg != '' || $filter_ok_oif != '' || $filter_ok_ffe != '') {
				$completed_ids = implode(',', $completed_ids_to_search); // convert array to csv format
				$where_clause .= ' AND completed IN ('.$completed_ids.')';
				$no_feedback_error = 'No results match this level of filtering, please redefine your filter options.';
			}

			if ($in_charge != 0 && $in_charge != '') { 
				$where_clause .= ' AND course_id = '.$in_charge.'';
			}

			// nkowald - 24-08-2010 - Add where clause into session for when coming back to page from 'show responses' tab.
			// nkowald - 2010-11-11 - This was messing things up, uncommented
			/*
            if (isset($_SESSION['ss']['where_clause']) && $where_clause == '') {
                $where_clause = $_SESSION['ss']['where_clause'];
            } else if ($where_clause != '') {
                $_SESSION['ss']['where_clause'] = $where_clause;
            }
			*/
            // nkowald - 24-08-2010
            
			// work out if this course has any feedback against it
			$sql = "SELECT * FROM ".$CFG->prefix."feedback_value WHERE 1=1 $where_clause";
			$has_feedback = FALSE;
			if ($results = get_records_sql($sql)) {
				if (count($results) > 0) {
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
