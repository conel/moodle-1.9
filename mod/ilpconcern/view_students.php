<?php  
/*
 * @copyright &copy; 2007 University of London Computer Centre
 * @author http://www.ulcc.ac.uk, http://moodle.ulcc.ac.uk
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package ILP
 * @version 1.0
 */

    require_once("../../config.php");
    require_once("lib.php");
    global $CFG, $USER, $db;
	
	// include the LPR databse library
    require_once("{$CFG->dirroot}/blocks/lpr/models/block_lpr_db.php");

    // instantiate the lpr db wrapper
    $lpr_db = new block_lpr_db();
	
	// nkowald - 2011-09-19 - Now using the new attpunc class
	//require_once($CFG->dirroot.'/blocks/lpr/models/block_lpr_conel_mis_db.php');
	//$conel_db = new block_lpr_conel_mis_db();
	
	require_once($CFG->dirroot . '/blocks/ilp/AttendancePunctuality.class.php');
	$attpunc = new AttendancePunctuality();
	
	$id 		  = optional_param('id', 0, PARAM_INT); // Course Module ID, or
	$courseid     = optional_param('courseid', 0, PARAM_INT); //Courseid
	$a  		  = optional_param('a', 0, PARAM_INT);  // concerns ID
	$mode  		  = optional_param('mode', 0, PARAM_INT);  // concerns mode
	$group 		  = optional_param('group', -1, PARAM_INT);
	$updatepref   = optional_param('updatepref', -1, PARAM_INT);	

	require_login();

	// Print the main part of the page
	$strconcerns = get_string("modulenameplural", "ilpconcern");
    $strconcern  = get_string("modulename", "ilpconcern");
    $stredit 	 = get_string("edit");
    $strdelete 	 = get_string("delete");
    $strcomments = get_string("comments", "ilpconcern");
	$strilp 	 = get_string("ilp", "block_ilp");
	
	// nkowald - 2011-10-20 - Adding page load time to logs
	$time = microtime();
	$time = explode(' ', $time);
	$time = $time[1] + $time[0];
	$timer_start = $time;
	
	// nkowald - 2011-11-18 - Adding the ability to 'get next learner' at a group level: if set
	unset($_SESSION['chosen_group']);

	if ($id != 0 || $courseid > 0){ //module is accessed through a course use course context 

		if ($id != 0) {
			if (! $cm = get_record("course_modules", "id", $id)) {
				error("Course Module ID was incorrect");
			}
			if (! $course = get_record("course", "id", $cm->course)) {
				error("Course is misconfigured");
			}
			if (! $concerns = get_record("ilpconcern", "id", $cm->instance)) {
				error("Course module is incorrect");
			}
			$context = get_context_instance(CONTEXT_MODULE, $cm->id);
			$link_values = '?id='.$cm->id;
			$navigation = "<a href=\"../../course/view.php?id=$course->id\">$course->shortname</a> -> <a href=\"../concerns/view.php$link_values\">$strconcerns</a> -> $strconcerns";
					
			print_header("$course->shortname: $concerns->name", "$course->fullname", "$navigation", "", "", true, update_module_button($cm->id, $course->id, $strconcerns), 
			navmenu($course, $cm));

			$baseurl = $CFG->wwwroot.'/mod/ilpconcern/view_students.php?id='.$id;
			
		} else {
			$course = $course = get_record('course', 'id', $courseid);
			$context = get_context_instance(CONTEXT_COURSE, $course->id);
			$link_values = '?courseid='.$course->id;
			$navigation = "<a href=\"../../course/view.php?id=$course->id\">$course->shortname</a> -> <a href=\"../../blocks/ilp/view.php?courseid=$course->id\">$strilp</a> -> $strconcerns";			
			print_header("$course->shortname: $strconcerns", "$course->fullname", "$navigation", "", "", true, "",  navmenu($course));
			$baseurl = $CFG->wwwroot.'/mod/ilpconcern/view_students.php?courseid='.$course->id;
		}
		/* first we check to see if the form has just been submitted
         * to request user_preference updates */

	require_capability('mod/ilpconcern:viewclass', $context);

	if ($updatepref > 0){
		$perpage = optional_param('perpage', 10, PARAM_INT);
		$perpage = ($perpage <= 0) ? 10 : $perpage ;
		set_user_preference('concerns_perpage', $perpage);
	}
	
	/* next we get perpage and from database */

	$perpage = get_user_preferences('concerns_perpage', 10);
	$teacherattempts = false; /// Temporary measure
	$page    = optional_param('page', 0, PARAM_INT);

	// Check to see if groups are being used in this course
	// and if so, set $currentgroup to reflect the current group

    $groupmode    = groups_get_course_groupmode($course);   // Groups are being used
    $currentgroup = groups_get_course_group($course, true);

    if (!$currentgroup) {      // To make some other functions work better later
        $currentgroup  = NULL;
    }
    $isseparategroups = ($course->groupmode == SEPARATEGROUPS and $course->groupmodeforce and
                         !has_capability('moodle/site:accessallgroups', $context));	
						 
    // Get all teachers and students
    $users = get_users_by_capability($context, 'mod/ilpconcern:view'); // everyone with this capability set to non-prohibit

	print_heading(get_string('reportsset', 'ilpconcern', $course->shortname));
    groups_print_course_menu($course, $baseurl); 
	
	if ($roles = get_roles_used_in_context($context)) {

        // We should exclude "admin" users (those with "doanything" at site level) because 
        // Otherwise they appear in every participant list

        $sitecontext = get_context_instance(CONTEXT_SYSTEM);
        $doanythingroles = get_roles_with_capability('moodle/site:doanything', CAP_ALLOW, $sitecontext);

        foreach ($roles as $role) {
            if (isset($doanythingroles[$role->id])) {   // Avoid this role (ie admin)
                unset($roles[$role->id]);
                continue;
            }
            $rolenames[$role->id] = strip_tags(format_string($role->name));   // Used in menus etc later on
        }
    }

	$tablecolumns = array('picture', 'fullname');
	$tableheaders = array('', 'Name', 'Status', '', '', '', '', '', '', '', '', '', '');
	
	if($CFG->ilpconcern_status_per_student == 1){
		$tablecolumns[] .= 'status';
	}
	$tablecolumns[] .= 'statuschanges';
	$tablecolumns[] .= 'attendance';
	$tablecolumns[] .= 'punctuality';
	$tablecolumns[] .= 'targetgrade';
	$tablecolumns[] .= 'targets';

	if($CFG->ilpconcern_report1 == 1){
		$tablecolumns[] .= 'report1';
	} 
	if($CFG->ilpconcern_report2 == 1){
		$tablecolumns[] .= 'report2';
	}
	if($CFG->ilpconcern_report3 == 1){
		$tablecolumns[] .= 'report3';
	}
	if($CFG->ilpconcern_report4 == 1){
		$tablecolumns[] .= 'report4';
	}
	$tablecolumns[] .= 'subjecttargetsoutstanding';
	$tablecolumns[] .= 'viewilp';

	require_once($CFG->libdir.'/tablelib.php');

	$table = new flexible_table('mod-ilpconcern-reports');					
	$table->define_columns($tablecolumns);
	$table->define_headers($tableheaders);
	$table->define_baseurl($baseurl);
	$table->collapsible(false);
	$table->initialbars(true);
	$table->column_suppress('picture');
	$table->column_class('picture', 'picture');
	$table->column_class('fullname', 'fullname');
	$table->set_attribute('cellspacing', '0');
	$table->set_attribute('id', 'attempts');
	$table->set_attribute('class', 'submissions');
	$table->set_attribute('width', '95%');
	$table->set_attribute('align', 'center');
	// Start working -- this is necessary as soon as the niceties are over
	$table->setup();

	// we are looking for all users with this role assigned in this context or higher
    if ($usercontexts = get_parent_contexts($context)) {
        $listofcontexts = '('.implode(',', $usercontexts).')';
    } else {
        $sitecontext = get_context_instance(CONTEXT_SYSTEM, SITEID);
        $listofcontexts = '('.$sitecontext->id.')'; // must be site
    }
	
	$select = 'SELECT u.id, u.firstname, u.lastname, u.picture, u.idnumber';
    $from = ' FROM '.$CFG->prefix.'user u INNER JOIN
    '.$CFG->prefix.'role_assignments r on u.id=r.userid LEFT OUTER JOIN
    '.$CFG->prefix.'user_lastaccess ul on (r.userid=ul.userid and ul.courseid = '.$course->id.')'; 
	// nkowald - 2011-10-24 - Worked when we only had one status row per student
	/*
    if($CFG->ilpconcern_status_per_student = 1) {	
		$select .= ', s.status';	
		$from .= 'LEFT JOIN '.$CFG->prefix.'ilpconcern_status s ON u.id = s.userid ';
	}
	*/
	
    // excluse users with these admin role assignments
    if ($doanythingroles) {
        $adminroles = 'AND r.roleid NOT IN (';

        foreach ($doanythingroles as $aroleid=>$role) {
            $adminroles .= "$aroleid,";
        }
        $adminroles = rtrim($adminroles,",");
        $adminroles .= ')';

    } else {
        $adminroles = '';
    }

	// join on 2 conditions
    // otherwise we run into the problem of having records in ul table, but not relevant course
    // and user record is not pulled out
    $where  = "WHERE (r.contextid = $context->id OR r.contextid in $listofcontexts)
        AND u.deleted = 0
        AND (ul.courseid = $course->id OR ul.courseid IS NULL)
        AND u.username <> 'guest' 
		AND r.roleid = 5
        $adminroles";

    $wheresearch = '';

	if ($currentgroup) {    // Displaying a group by choice
        // FIX: TODO: This will not work if $currentgroup == 0, i.e. "those not in a group"
        $from  .= 'LEFT JOIN '.$CFG->prefix.'groups_members gm ON u.id = gm.userid ';
        $where .= ' AND gm.groupid = '.$currentgroup.' ';
		
		// nkowald - 2011-11-18 - Adding the ability to 'get next learner' at a group level: if set
		$_SESSION['chosen_group'] = array('course' => $course->id, 'group' => $currentgroup);
    }

	if ($table->get_sql_where()) {
        $where .= ' AND '.$table->get_sql_where();
    }

	// nkowald - 2011-10-03 - Removing ability to sort
	/*
    if ($table->get_sql_sort()) {
        $sort = ' ORDER BY '.$table->get_sql_sort();
    } else {
      $sort = '';
    }
	*/
	$sort = 'ORDER BY u.firstname ASC';
	
	$nousers = get_records_sql($select.$from.$where.$wheresearch.$sort);
	$nousers = count($nousers);

	$table->pagesize($perpage, $nousers);
	///offset used to calculate index of student in that particular query, needed for the pop up to know who's next
    $offset = $page * $perpage;
	

	if (($ausers = get_records_sql($select.$from.$where.$wheresearch.$sort, $table->get_page_start(), $table->get_page_size())) !== false) {

			// nkowald - 2011-01-17 - Need to show only target overviews set this academic year
			$ts_now = time();
			$query = "SELECT ac_year_start_date, ac_year_end_date FROM mdl_academic_years WHERE ac_year_start_date < $ts_now AND ac_year_end_date > $ts_now";
			if ($current_ac_year = get_records_sql($query)) {
				foreach ($current_ac_year as $year) {
					$ts_year_start = $year->ac_year_start_date;
					$ts_year_end = $year->ac_year_end_date;
				}
			} else {
				// Should not get here but if so: find status from start of year onwards
				$ts_year_start = mktime(0, 0, 0, 1, 1, date('Y'));
			}
			
            foreach ($ausers as $auser) {
			
			$picture = print_user_picture($auser->id, $course->id, $auser->picture, false, true);
			$row = array($picture, fullname($auser));

			if ($CFG->ilpconcern_status_per_student == 1){

				//$studentstatus = $auser->status;
				$studentstatus = 0;
				if ($status = get_record('ilpconcern_status', 'userid', $auser->id, 'live', 1)) {
					$studentstatus = $status->status;
				}

				if ($studentstatus){
					switch ($studentstatus) {		
						case "0":		
							$thisstudentstatus = get_string('green', 'ilpconcern');		
							break;		
						case "1":		
							$thisstudentstatus = get_string('amber', 'ilpconcern');		
							break;		
						case "2":		
							$thisstudentstatus = get_string('red', 'ilpconcern');		
							break;		
						case "3":		
							$thisstudentstatus = get_string('withdrawn', 'ilpconcern');		
							break;		
					}
				} else {		
						$studentstatus = 0;
						$thisstudentstatus = get_string('green', 'ilpconcern');			
				}
				
			}

			// nkowald - 2010-09-07 - Get how many times this was changed
			$query = "SELECT COUNT(history) as times_changed FROM mdl_ilpconcern_status WHERE userid = ".$auser->id." AND created >= $ts_year_start";

			$no_changes = 0;
			if ($times_changed = get_records_sql($query)) {
				foreach ($times_changed as $change) {
					$no_changes = $change->times_changed;
				}
			} else {
				$no_changes = 0;
			}
			
			
			$row[] .= '<a href="'.$CFG->wwwroot.'/mod/ilpconcern/concerns_view.php?courseid='.$courseid.'&userid='.$auser->id.'&status=0" class="no_link"><span class="status-'.$studentstatus.'">'.$thisstudentstatus.'</span></a>';
			// change text
			//$change_txt = ($no_changes == 1) ? ' Status Change' : ' Status Changes';
			$change_txt = ($no_changes == 1) ? '  change' : ' changes';
			if ($no_changes == 0) {
				$row[] .= '<a href="'.$CFG->wwwroot.'/mod/ilpconcern/concerns_view.php?courseid='.$courseid.'&userid='.$auser->id.'&status=0" class="no_link">No changes</a>';
			} else {
				$row[] .= '<a href="'.$CFG->wwwroot.'/mod/ilpconcern/concerns_view.php?courseid='.$courseid.'&userid='.$auser->id.'&status=0">'.$no_changes . $change_txt .'</a>';
			}

            $atten = $attpunc->get_attendance_avg($auser->idnumber);
			$att_data = $attpunc->getAttPuncData($atten->ATTENDANCE);
			$att_perc = round($att_data['decimal'], 0);
			$att_class = ' class="att_'.$att_data['colour'].'"';
			

			$row[] .= '<a href="'.$CFG->wwwroot.'/blocks/ilp/attendance.php?courseid='.$courseid.'&amp;userid='.$auser->id.'"'.$att_class.'>' .$att_perc . '% Attendance</a>';

			$punc = $attpunc->get_punctuality_avg($auser->idnumber);
			$punc_data = $attpunc->getAttPuncData($punc->PUNCTUALITY);
			$punc_perc = round($punc_data['decimal'], 0);
			$punc_class = ' class="punc_'.$punc_data['colour'].'"';

			$row[] .= '<a href="'.$CFG->wwwroot.'/blocks/ilp/attendance.php?courseid='.$courseid.'&amp;userid='.$auser->id.'"'.$punc_class.'>' .$punc_perc . '% Punctuality</a>';


			// nkowald - 2011-06-16 - Added Target Grade
			$grade_name = 'Not Set';
			// Only get target grades added after this academic year
			/*
			$query = sprintf("SELECT mt.name as target_name FROM mdl_target_grades mtg 
				INNER JOIN mdl_targets mt ON mtg.target_grade_id = mt.id 
				WHERE mtg.mdl_user_id = %d 
				AND mtg.date_added > %d 
				AND mtg.live = 1", $auser->id, $ts_year_start);
			*/
			$query = sprintf("SELECT mt.name as target_name FROM mdl_target_grades mtg 
				INNER JOIN mdl_targets mt ON mtg.target_grade_id = mt.id 
				WHERE mtg.mdl_user_id = %d 
				AND mtg.live = 1", $auser->id);

			
			if ($grade_found = get_records_sql($query)) {
				foreach($grade_found as $grade) {
					$grade_name = $grade->target_name;
				}
			}
			
			$class = ($grade_name == 'Not Set') ? ' class="no_link"' : '';
			$row[] .= '<a href="'.$CFG->wwwroot.'/mod/ilpconcern/concerns_view.php?courseid='.$courseid.'&amp;userid='.$auser->id.'&amp;status=0"'.$class.'>Target Grade: ' . $grade_name . '</a>';

            // nkowald - 2011-05-19 - Added Targets - 
            // nkowald - 2012-04-06 - Commented this out as it was doubling the amount of queries that needed to be run
			//$target_set =	$lpr_db->count_targets(array("$auser->id"=>$auser->id),TARGETS_SET, NULL, NULL, $ts_year_start, $ts_year_end);
			//$target_complete =	$lpr_db->count_targets(array("$auser->id"=>$auser->id),TARGETS_COMPLETE, NULL, NULL, $ts_year_start, $ts_year_end);
            //$targets	= ($target_set == 0) ? 'No' : "{$target_complete}/{$target_set}";
            
            $targets = $lpr_db->get_target_totals($auser->id, $ts_year_start, $ts_year_end);
            
            $target_class = ($targets == 'No') ? ' class="no_link"' : '';
           
            $row[] .= '<a href="'.$CFG->wwwroot.'/mod/ilptarget/target_view.php?courseid='.$courseid.'&amp;userid='.$auser->id.'"'.$target_class.'>' . $targets . ' Targets</a>';

			// nkowald - 2010-11-09 - Adding number of student status changes for this year
			// nkowald - 2010-11-22 - Removed the course search, now shows all progresses for student.
			//$query = "SELECT * FROM mdl_ilpconcern_posts WHERE setforuserid = ".$auser->id." AND course = ".$courseid." AND status=3 AND timecreated > $ts_year_start";
			/*
			$query = "SELECT * FROM mdl_ilpconcern_posts WHERE setforuserid = ".$auser->id." AND status=3 AND timecreated >= $ts_year_start";

			$no_progresses = 0;
			if ($progresses = get_records_sql($query)) {
				foreach ($progresses as $progress) {
					$no_progresses++;
				}
			}
			*/
			/*
			if ($no_progresses == 0) {
				$row[] .= '<a href="'.$CFG->wwwroot.'/mod/ilpconcern/concerns_view.php?courseid='.$courseid.'&userid='.$auser->id.'&status=3" class="no_link">0 Student Progress</a>';
			} else {
				$progresses_txt = ($no_progresses == 1) ? ' Student Progress' : ' Student Progresses';
				$row[] .= '<a href="'.$CFG->wwwroot.'/mod/ilpconcern/concerns_view.php?courseid='.$courseid.'&userid='.$auser->id.'&status=3">'.$no_progresses. $progresses_txt .'</a>';
			}
			*/
			
			if ($CFG->ilpconcern_report1 == 1){
				//$report1total = count_records_sql('SELECT COUNT(*) FROM '.$CFG->prefix.'ilpconcern_posts WHERE setforuserid = '.$auser->id.' AND status = 0' );
				// nkowald - 2010-10-21 - Need to show only target overviews set this year
				$report1total = count_records_sql('SELECT COUNT(*) FROM '.$CFG->prefix.'ilpconcern_posts WHERE setforuserid = '.$auser->id.' AND status = 0 AND timecreated >= '.$ts_year_start.'' );
				if ($report1total == 0) {
					$report1text  = '<a href="concerns_view.php'.$link_values.'&amp;userid='.$auser->id.'&amp;status=0" class="no_link">No Tutor Reviews</a>';
				} else {
					$review_txt = ($report1total == 1) ? ' Tutor Review' : ' Tutor Reviews';
					$report1text  = '<a href="concerns_view.php'.$link_values.'&amp;userid='.$auser->id.'&amp;status=0">'.$report1total . $review_txt .'</a>';
				}
				$row[] .= $report1text;				
			}
			if ($CFG->ilpconcern_report2 == 1){
				//$report2total = count_records_sql('SELECT COUNT(*) FROM '.$CFG->prefix.'ilpconcern_posts WHERE setforuserid = '.$auser->id.' AND status = 1' );
				// nkowald - 2010-10-21 - Need to show only target overviews set this year
				$report2total = count_records_sql('SELECT COUNT(*) FROM '.$CFG->prefix.'ilpconcern_posts WHERE setforuserid = '.$auser->id.' AND status = 1 AND timecreated >= '.$ts_year_start.'' );
				if ($report2total == 0) {
					$report2text  = '<a href="concerns_view.php'.$link_values.'&amp;userid='.$auser->id.'&amp;status=1" class="no_link">No Good Performance Records</a>';
				} else {
					$good_perf_txt = ($report2total == 1) ? ' Good Performance Record' : ' Good Performance Records';
					$report2text  = '<a href="concerns_view.php'.$link_values.'&amp;userid='.$auser->id.'&amp;status=1">'.$report2total . $good_perf_txt . '</a>';
				}
				$row[] .= $report2text;				
			}
			if ($CFG->ilpconcern_report3 == 1){
				//$report3total = count_records_sql('SELECT COUNT(*) FROM '.$CFG->prefix.'ilpconcern_posts WHERE setforuserid = '.$auser->id.' AND status = 2' );
				// nkowald - 2010-10-21 - Need to show only target overviews set this year
				$report3total = count_records_sql('SELECT COUNT(*) FROM '.$CFG->prefix.'ilpconcern_posts WHERE setforuserid = '.$auser->id.' AND status = 2 AND timecreated >= '.$ts_year_start.'' );
				if ($report3total == 0) {
					$report3text  = '<a href="concerns_view.php'.$link_values.'&amp;userid='.$auser->id.'&amp;status=2" class="no_link">No Cause for Concerns</a>';
				} else {
					$cause_txt = ($report3total == 1) ? ' Cause for Concern' : ' Cause for Concerns';
					$report3text  = '<a href="concerns_view.php'.$link_values.'&amp;userid='.$auser->id.'&amp;status=2">'.$report3total . $cause_txt.'</a>';
				}
				$row[] .= $report3text;				
			}
			if ($CFG->ilpconcern_report4 == 1){
				//$report3total = count_records_sql('SELECT COUNT(*) FROM '.$CFG->prefix.'ilpconcern_posts WHERE setforuserid = '.$auser->id.' AND status = 3' );
				// nkowald - 2010-10-21 - Need to show only target overviews set this year
				$report4total = count_records_sql('SELECT COUNT(*) FROM '.$CFG->prefix.'ilpconcern_posts WHERE setforuserid = '.$auser->id.' AND status = 3 AND timecreated >= '.$ts_year_start.'' );
				if ($report4total == '0') $report4total = 'No';
				$report4text  = '<a href="concerns_view.php'.$link_values.'&amp;userid='.$auser->id.'&amp;status=3">'.$report4total.' Student Progress</a>';
				$row[] .= $report4text;				
			}

			// nkowald - 2011-10-18 - Adding subject targets oustanding
            $complete_modules_txt = $attpunc->getModuleCompletion($auser, $attpunc->current_term_no);
            $row[] .= '<a href="'.$CFG->wwwroot.'/mod/ilpconcern/subject_targets.php?courseid='.$courseid.'&userid='.$auser->id.'">'. $complete_modules_txt . ' Subject Targets</a>';
			
			// nkowald - 2011-05-19 - View ILP
            $row[] .= '<a href="'.$CFG->wwwroot.'/blocks/ilp/view.php?courseid='.$courseid.'&id='.$auser->id.'" class="view_ilp">View ILP</a>';
               $table->add_data($row);
            }
        }

        $table->print_html();  /// Print the whole table

		/// Mini form for setting user preference
        echo '<br />';
		if ($perpage > 10) {
			echo '<div class="tip"><b>Tip:</b> Set \'Students per page\' to 10 for faster loading reports pages.</div>';
		}
		if ($id != 0 ){
			echo '<form name="options" action="view_students.php?id='.$cm->id.'" method="post">';
		} elseif($courseid > 0){
			echo '<form name="options" action="view_students.php?courseid='.$course->id.'" method="post">';
		}
		
        echo '<input type="hidden" id="updatepref" name="updatepref" value="1" />';
        echo '<table id="optiontable" align="center">';
        echo '<tr align="right"><td>';
        echo '<label for="perpage">'.get_string('pagesize', 'ilpconcern').'</label>';
        echo ':</td>';
        echo '<td align="left">';
        echo '<input type="text" id="perpage" name="perpage" size="1" value="'.$perpage.'" />';
        helpbutton('pagesize', get_string('pagesize', 'ilpconcern'), 'ilpconcern');
        echo '</td></tr>';
        echo '<tr>';
        echo '<td colspan="2" align="right">';
        echo '<input type="submit" value="'.get_string('savepreferences').'" />';
        echo '</td></tr></table>';
        echo '</form>';
				
		$footer = $course;

    } else { //module is accessed independent of a course use user context

		//$context = get_context_instance(CONTEXT_USER, $user->id);
		$link_values = '';
		$navigation = "<a href=\"../concerns/view_students.php\">$strconcerns</a>";

		print_header("$strconcerns", "", "$navigation -> ".get_string('mystudents', 'ilpconcern')."", "", "", true, "", "");
		$baseurl = $CFG->wwwroot.'/mod/ilpconcern/view_students.php';

		if ($usercontexts = get_records_sql("SELECT c.instanceid, c.instanceid, u.firstname, u.lastname
                                         FROM {$CFG->prefix}role_assignments ra,
                                              {$CFG->prefix}context c,
                                              {$CFG->prefix}user u
                                         WHERE ra.userid = $USER->id
                                         AND   ra.contextid = c.id
                                         AND   c.instanceid = u.id
                                         AND   c.contextlevel = ".CONTEXT_USER)) {

		print_heading(get_string('mystudents', 'ilpconcern'));
		
		$tablecolumns = array('user', 'concerns', 'ilpconcern');
        $tableheaders = array('', '', '');

        require_once($CFG->libdir.'/tablelib.php');
        $table = new flexible_table('personal-tutor-students');
        $table->define_columns($tablecolumns);
        $table->define_headers($tableheaders);
        $table->define_baseurl($baseurl);
        $table->sortable(false);
        $table->collapsible(false);
        $table->initialbars(false);
        $table->column_class('user', 'user');
        $table->column_class('concerns', 'ilpconcern');
		$table->column_class('concerns', 'ilpconcern');
        $table->set_attribute('cellspacing', '5');
        $table->set_attribute('cellpadding', '5');
        $table->set_attribute('id', 'mystudents');
        // Start working -- this is necessary as soon as the niceties are over
        $table->setup();

        foreach ($usercontexts as $usercontext) {
			$user = '<a href="'.$CFG->wwwroot.'/user/view.php?id='.$usercontext->instanceid.'&amp;course=1">'.fullname($usercontext).'</a>';
			$concerns = '<a href="concerns_view.php?userid='.$usercontext->instanceid.'">'.get_string('concerns', 'ilpconcern').'</a>';
			$concern = 'Concern';
			$row = array($user, $concerns, $concern);
			$table->add_data($row);
        }

        $table->print_html();  /// Print the whole table
		
		} else{
			print_heading(get_string('nostudents', 'ilpconcern'));
		}

		$footer = '';
	}

	// nkowald - 2011-10-20 - Adding page load time to logs
	$time = microtime();
	$time = explode(' ', $time);
	$time = $time[1] + $time[0];
	$finish = $time;
	$total_time = round(($finish - $timer_start), 4);
	$info = "page loaded: $total_time secs";
	
	$this_page = ($_SERVER['REQUEST_URI'] != '') ? $_SERVER['REQUEST_URI'] : 'view_students.php';
	$course_id = (isset($course->id)) ? $course->id : 1;
	add_to_log($course_id, 'ilp', 'view reports overview', $this_page, $info);
	
	//Allow users to see their own profile, but prevent others	
	//require_capability('mod/ilpconcern:view', $context);
	
	print_footer($footer);	
        
?>