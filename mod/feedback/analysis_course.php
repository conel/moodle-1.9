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
    }
	
    $coursefilter = optional_param('coursefilter', '0', PARAM_INT);
    $courseitemfilter = optional_param('courseitemfilter', '0', PARAM_INT);
    $courseitemfiltertyp = optional_param('courseitemfiltertyp', '0', PARAM_ALPHANUM);
    // $searchcourse = optional_param('searchcourse', '', PARAM_ALPHAEXT);
    $searchcourse = optional_param('searchcourse', '', PARAM_RAW);
    $courseid = optional_param('courseid', false, PARAM_INT);

	$where_clause = '';

	/*
    if (($searchcourse OR $courseitemfilter OR $coursefilter) AND !confirm_sesskey()) {
        error('no sesskey defined');
    }
	*/
    
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

     print_box_start('generalbox boxaligncenter boxwidthwide');

        if( $capabilities->viewreports ) {
            //button "export to excel"
            echo '<div align="center">';
            $export_button_link = 'analysis_to_excel.php';
            $export_button_options = array('sesskey'=>$USER->sesskey, 'id'=>$id, 'coursefilter'=>$coursefilter);
            $export_button_label = get_string('export_to_excel', 'feedback');
            print_single_button($export_button_link, $export_button_options, $export_button_label, 'post');
            echo '</div>';
        }
        
        //get the groupid
        //lstgroupid is the choosen id
        $mygroupid = false;
        //get completed feedbacks
        $completedscount = feedback_get_completeds_group_count($feedback, $mygroupid, $coursefilter);
        
        //show the count
        echo '<b>'.get_string('completed_feedbacks', 'feedback').': '.$completedscount. '</b><br />';
        
        // get the items of the feedback
        $items = get_records_select('feedback_item', 'feedback = '. $feedback->id . ' AND hasvalue = 1', 'position');
        //show the count
        if(is_array($items)){
            echo '<b>'.get_string('questions', 'feedback').': ' .sizeof($items). ' </b><hr />';
            //echo '<a href="analysis_course.php?id=' . $id . '&courseid='.$courseid.'">'.get_string('show_all', 'feedback').'</a>';
			// nkowald - 2010-11-12 - Added reset param
            echo '<a href="analysis_course.php?id=' . $id . '&courseid=0&amp;reset=1">'.get_string('show_all', 'feedback').'</a>';
        } else {
            $items=array();
        }

        echo '<form name="report" method="post">';
        echo '<div align="center"><table width="80%" cellpadding="10">';
        if ($courseitemfilter > 0) {
            $avgvalue = 'avg(value)';
            if ($CFG->dbtype == 'postgres7') {
                 $avgvalue = 'avg(cast (value as integer))';
            }
            if ($courses = get_records_sql ('select fv.course_id, c.shortname, '.$avgvalue.' as avgvalue '.
                                                      'from '.$CFG->prefix.'feedback_value fv, '.$CFG->prefix.'course c, '.
                                                      $CFG->prefix.'feedback_item fi '.
                                                      'where fv.course_id = c.id '.
                                                      'and fi.id = fv.item and fi.typ = \''.$courseitemfiltertyp.'\' and fv.item = \''.
                                                      $courseitemfilter.'\' '.
                                                      'group by course_id, shortname order by avgvalue desc')) {
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

            echo get_string('search_course', 'feedback') . ': ';
            echo '<input type="text" name="searchcourse" value="'.s($searchcourse).'"/> <input type="submit" value="'.get_string('search').'"/>';
            echo '<input type="hidden" name="sesskey" value="' . $USER->sesskey . '" />';
            echo '<input type="hidden" name="id" value="'.$id.'" />';
            echo '<input type="hidden" name="courseitemfilter" value="'.$courseitemfilter.'" />';
            echo '<input type="hidden" name="courseitemfiltertyp" value="'.$courseitemfiltertyp.'" />';
            echo '<input type="hidden" name="courseid" value="'.$courseid.'" />';
            echo '<script language="javascript" type="text/javascript">
                    <!--
                    function setcourseitemfilter(item, item_typ) {
                        document.report.courseitemfilter.value = item;
                        document.report.courseitemfiltertyp.value = item_typ;
                        document.report.submit();
                    }
                    -->
                    </script>';


            $sql = 'select c.id, c.shortname from '.$CFG->prefix.'course c, '.
                                                  $CFG->prefix.'feedback_value fv, '.$CFG->prefix.'feedback_item fi '.
                                                  'where c.id = fv.course_id and fv.item = fi.id '.
                                                  'and fi.feedback = '.$feedback->id.' '.
                                                  'and 
                                                  (c.shortname '.sql_ilike().' \'%'.$searchcourse.'%\'
                                                  OR c.fullname '.sql_ilike().' \'%'.$searchcourse.'%\')';
            
            if ($courses = get_records_sql_menu($sql)) {

                 echo ' ' . get_string('filter_by_course', 'feedback') . ': ';
                 choose_from_menu ($courses, 'coursefilter', $coursefilter, 'choose', 'this.form.submit()');
            }
            echo '<br /><br />';
            echo '<hr />';


        echo '</center>';
        
        $no_feedback_error = 'No feedback exists for this course.';

        // Create and clause based on filters selected
        $and_clause = '';
        if ($courseid != 0 && $courseid != '') {
            $and_clause .= ' AND fbv.course_id = '.$courseid.' ';
            $_SESSION['and_clause'] = $and_clause;
        } else {
            $_SESSION['and_clause'] = '';
        }

        if ($courseid != 0 && $courseid != '') {
            $where_clause .= ' AND course_id = '.$courseid.'';
        }

		// nkowald - 24-08-2010 - Add where clause into session for when coming back to page from 'show responses' tab.
		// nkowald - 2010-11-11 - This was messing things up, uncommented
		/*
            if (isset($_SESSION['c']['where_clause']) && $where_clause == '') {
                $where_clause = $_SESSION['c']['where_clause'];
            } else if ($where_clause != '') {
                $_SESSION['c']['where_clause'] = $where_clause;
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


        $itemnr = 0;

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
                $itemobj->print_analysed($item, $printnr, $mygroupid, $courseid, $where_clause_to_use);

                if (eregi('rated$', $item->typ)) {
                     echo '<tr><td colspan="2"><a href="#" onclick="setcourseitemfilter('.$item->id.',\''.$item->typ.'\'); return false;">'.
                    get_string('sort_by_course', 'feedback').'</a></td></tr>'; 
                }

                echo '</table>';
            }

            echo '</td></tr>';
            echo '</table></div>';
            echo '</form>';

        } else {
            echo "<p><b>$no_feedback_error</b></p>";
        }

    }

	echo '</div>';

    print_box_end();
    
    print_footer($course);

?>
