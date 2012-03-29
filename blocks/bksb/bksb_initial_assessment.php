<?php 

/*
 * @copyright &copy; 2007 University of London Computer Centre
 * @author http://www.ulcc.ac.uk, http://moodle.ulcc.ac.uk
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package ILP
 * @version 1.0
 */

//  Lists the student info texts relevant to the student.
//  with links to edit for those who can. 

    require_once('../../config.php');
    //require_once('block_ilp_lib.php');
	include('../ilp/access_context.php');

    include_once('BksbReporting.class.php');
    $bksb = new BksbReporting();

    global $GFG, $USER;

    $contextid    = optional_param('contextid', 0, PARAM_INT);               // one of this or
    $courseid     = optional_param('courseid', SITEID, PARAM_INT);                  // this are required
	$group        = optional_param('group', -1, PARAM_INT);
	$updatepref   = optional_param('updatepref', -1, PARAM_INT);
    $userid       = optional_param('userid', 0, PARAM_INT);

    //$coursecontext ;
    if ($contextid) {
        if (! $coursecontext = get_context_instance_by_id($contextid)) {
            error("Context ID is incorrect");
        }
        if (! $course = get_record('course', 'id', $coursecontext->instanceid)) {
            error("Course ID is incorrect");
        }
    } else if ($courseid) {
        if (! $course = get_record('course', 'id', $courseid)) {
            error("Course ID is incorrect");
        }
        if (! $coursecontext = get_context_instance(CONTEXT_COURSE, $course->id)) {
            error("Context ID is incorrect");
        }
    }

    require_login($course);

    $sitecontext = get_context_instance(CONTEXT_SYSTEM);

    if (has_capability('moodle/site:doanything',$sitecontext)) {  // are we god ?
        $access_isgod = 1 ;
    }
    if (has_capability('block/ilp:viewclass',$coursecontext)) { // are we the teacher on the course ?
        $access_isteacher = 1 ;
    }
	
	// nkowald - 2012-01-10 - Define $baseurl here, needs to keep all get distinct params
	$get_urls = $_GET;
	$params = array();
	// This will keep ONLY distinct paramaters
	foreach ($get_urls as $key => $value) {
		if ($key != 'page') {
			$params[$key] = $value;
		}
	}
	// Build the param of the URL
	$param = '';	
	if (count($params) > 0) {
		$c = 0;
		foreach ($params as $k => $v) {
			// Only use a question mark for the first get param
			$param .= ($c == 0) ? '?' . $k . '=' . $v : '&' . $k . '=' . $v;
			$c++;
		}
	}
	$baseurl = $CFG->wwwroot.'/blocks/bksb/bksb_diagnostic_overview.php' . $param;
	

    // Print headers
	$title = 'BKSB Initial Assessment Overviews';

    if ($course->id != $SITE->id) {
        print_header($title, $course->fullname,
         "<a href=\"../../course/view.php?id=$course->id\">$course->shortname</a> -> ".
         get_string('ilp','block_ilp'), "", "", true, "&nbsp;", navmenu($course));
    } else {
        print_header($title, $course->fullname,
         get_string('ilp','block_ilp'), "", "", true, "&nbsp;", navmenu($course));
    }

    if ($userid != 0) {

        $query = "SELECT * FROM mdl_user where id = $userid";
        if ($ausers = get_records_sql($query)) {
            foreach ($ausers as $auser) {
                $picture = $CFG->wwwroot . "/user/pix.php/".$userid."/f1.jpg";
                $fullname = fullname($auser);
                $conel_id = $auser->idnumber;
            }
        }

        echo '<div id="diag_user_header">';
        echo "<h2>BKSB Initial Assessment Overview for <span>$fullname</span></h2>";
		$profile_link = $CFG->wwwroot . "/user/view.php?courseid=$courseid&amp;id=$userid";
        echo '<a href="'.$profile_link.'" title="View Profile"><img src="'.$picture.'" alt="'.$fullname.' width="100" height="100" id="bksb_userpic" /></a>';
        echo '</div>';

        // Get BKSB Result categories
        $cats = $bksb->ass_cats;
        $tablecolumns = $cats;
        $tableheaders = $cats;
        //$baseurl = $CFG->wwwroot.'/blocks/bksb/bksb_initial_assessment.php?userid='.$userid;

        require_once($CFG->libdir.'/tablelib.php');
        $table = new flexible_table('mod-targets');
                        
        $table->define_columns($tablecolumns);
        $table->define_headers($tableheaders);
        $table->define_baseurl($baseurl);
        $table->collapsible(false);
        $table->initialbars(false);
        $table->column_suppress('picture');	
        $table->column_class('picture', 'picture');
        $table->column_class('fullname', 'fullname');
        $table->set_attribute('cellspacing', '0');
        $table->set_attribute('id', 'bksb_results_group');
        $table->set_attribute('class', 'bksb_results');
        $table->set_attribute('width', '90%');
        $table->set_attribute('align', 'center');
        foreach($cats as $cat) {
            $table->no_sorting($cat);
        }
            
        // Start working -- this is necessary as soon as the niceties are over
        $table->setup();

        $bksb_results = $bksb->getResults($conel_id);
        $row = $bksb_results;
        $table->add_data($row);

        $table->print_html();  /// Print the whole table

    } else {
        if ($courseid and $access_isteacher and $course->id != $SITE->id) {
        $context = get_context_instance(CONTEXT_COURSE, $course->id);
        //$baseurl = $CFG->wwwroot.'/blocks/bksb/bksb_initial_assessment.php?courseid='.$courseid;
        if ($updatepref > 0){
            $perpage = optional_param('perpage', 10, PARAM_INT);
            $perpage = ($perpage <= 0) ? 10 : $perpage ;
            set_user_preference('target_perpage', $perpage);
        }

        /* next we get perpage and from database */

        $perpage = get_user_preferences('target_perpage', 10);
        $teacherattempts = false; /// Temporary measure
        $page    = optional_param('page', 0, PARAM_INT);
        /// Check to see if groups are being used in this course
        /// and if so, set $currentgroup to reflect the current group
        $groupmode    = groups_get_course_groupmode($course);   // Groups are being used
        $currentgroup = groups_get_course_group($course, true);

        if (!$currentgroup) {      // To make some other functions work better later
            $currentgroup  = NULL;
        }

        $isseparategroups = ($course->groupmode == SEPARATEGROUPS and $course->groupmodeforce and
                             !has_capability('moodle/site:accessallgroups', $context));	
        /// Get all teachers and students
        //$users = get_users_by_capability($context, 'mod/ilptarget:view'); // everyone with this capability set to non-prohibit
        print_heading('Initial Assessment Overview ('.$course->shortname.')');
        //print_heading(get_string("students")." (".$course->shortname.")");
        echo '<br />';
        groups_print_course_menu($course, $baseurl); 
        $doanythingroles = get_roles_with_capability('moodle/site:doanything', CAP_ALLOW, $sitecontext);
        /*if ($roles = get_roles_used_in_context($context)) {
            // We should exclude "admin" users (those with "doanything" at site level) because 
            // Otherwise they appear in every participant list

            foreach ($roles as $role) {
                if (isset($doanythingroles[$role->id])) {   // Avoid this role (ie admin)
                    unset($roles[$role->id]);
                    continue;
                }
                $rolenames[$role->id] = strip_tags(format_string($role->name));   // Used in menus etc later on
            }
        }*/


        // Get BKSB Result categories
        $cols = array('picture', 'fullname');
        $cols_header = array('', 'Name');
        $cats = $bksb->ass_cats;
        $tablecolumns = array_merge($cols, $cats);
        $tableheaders = array_merge($cols_header, $cats);

        require_once($CFG->libdir.'/tablelib.php');
        $table = new flexible_table('mod-targets');
                        
        $table->define_columns($tablecolumns);
        $table->define_headers($tableheaders);

        $table->use_initials = false;
        $table->define_baseurl($baseurl);
        //$table->sortable(true, 'lastname');
        $table->sortable(false);
        $table->collapsible(false);
        $table->initialbars(true);
        $table->column_suppress('picture');	
        $table->column_class('picture', 'picture');
        $table->column_class('fullname', 'fullname');
        $table->set_attribute('cellspacing', '0');
        $table->set_attribute('id', 'bksb_results_group');
        $table->set_attribute('class', 'bksb_results');
        $table->set_attribute('width', '90%');
        $table->set_attribute('align', 'center');
        foreach($cats as $cat) {
            $table->no_sorting($cat);
        }
            
        // Start working -- this is necessary as soon as the niceties are over

        $table->setup();


        // we are looking for all users with this role assigned in this context or higher
        if ($usercontexts = get_parent_contexts($context)) {
            $listofcontexts = '('.implode(',', $usercontexts).')';
        } else {
            $sitecontext = get_context_instance(CONTEXT_SYSTEM, SITEID);
            $listofcontexts = '('.$sitecontext->id.')'; // must be site
        }

        //if (empty($users)) {
            //print_heading(get_string('noattempts','assignment'));
            //return true;
        //}


        $select = 'SELECT u.id, u.firstname, u.lastname, u.picture, u.idnumber ';
        $from = 'FROM '.$CFG->prefix.'user u INNER JOIN
        '.$CFG->prefix.'role_assignments ra on u.id=ra.userid LEFT OUTER JOIN
        '.$CFG->prefix.'user_lastaccess ul on (ra.userid=ul.userid and ul.courseid = '.$course->id.') LEFT OUTER JOIN
        '.$CFG->prefix.'role r on ra.roleid = r.id '; 
        
        // excluse users with these admin role assignments
        if ($doanythingroles) {
            $adminroles = 'AND ra.roleid NOT IN (';
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
        $where  = "WHERE (ra.contextid = $context->id OR ra.contextid in $listofcontexts)
            AND u.deleted = 0
            AND (ul.courseid = $course->id OR ul.courseid IS NULL)
            AND u.username <> 'guest' 
            AND ra.roleid = 5
            AND r.id = 5
            $adminroles";
            $wheresearch = '';

        if ($currentgroup) {    // Displaying a group by choice
            // FIX: TODO: This will not work if $currentgroup == 0, i.e. "those not in a group"
            $from  .= 'LEFT JOIN '.$CFG->prefix.'groups_members gm ON u.id = gm.userid ';
            $where .= ' AND gm.groupid = '.$currentgroup;
        }	
        if ($table->get_sql_where()) {
            $where .= ' AND '.$table->get_sql_where();
        }

        if ($table->get_sql_sort()) {
            $sort = ' ORDER BY u.firstname';
        } else {
            $sort = '';
        }

        $nousers = get_records_sql($select.$from.$where.$wheresearch.$sort);
        $table->pagesize($perpage, count($nousers));
        ///offset used to calculate index of student in that particular query, needed for the pop up to know who's next
        $offset = $page * $perpage;

        $query = $select . $from . $where . $wheresearch . $sort;
		$records_found = false;
		
        if (($ausers = get_records_sql($select.$from.$where.$wheresearch.$sort, $table->get_page_start(), $table->get_page_size())) !== false) {
                foreach ($ausers as $auser) {
                    $bksb_results = $bksb->getResults($auser->idnumber);
					
					/*
					$results_found = false;
					foreach ($bksb_results as $result) {
						if ($result != '&mdash;') {
							$results_found = true;
						}
					}
					*/

                    //if ($results_found) {
					$user_ids[] = $auser->idnumber;
					$picture = print_user_picture($auser->id, $course->id, $auser->picture, false, true);
					$update  = '<a href="view.php?courseid='.$courseid.'&amp;id='.$auser->id.'">'.get_string('viewilp', 'block_ilp').'</a>';
					$name_html = '<a href="'.$CFG->wwwroot.'/blocks/bksb/bksb_initial_assessment.php?courseid='.$courseid.'&amp;userid='.$auser->id.'">'.fullname($auser).'</a>';
					$col_row = array($picture, $name_html);
					$row = array_merge($col_row, $bksb_results);
					$table->add_data($row);
					$records_found = true;
					//}
                }
                
            }
			
			if ($records_found == false) {
				echo '<center><p><b>No Initial Assessment results for these students</b></p></center>';
			}

            $table->print_html();  /// Print the whole table
            echo '<br />';
            echo '<form name="options" action="bksb_initial_assessment.php?courseid='.$courseid.'" method="post">';
            echo '<input type="hidden" id="updatepref" name="updatepref" value="1" />';
            echo '<table id="optiontable" align="center">';
            echo '<tr align="right"><td>';
            echo '<label for="perpage">'.get_string('pagesize','ilptarget').'</label>';
            echo ':</td>';
            echo '<td align="left">';
            echo '<input type="text" id="perpage" name="perpage" size="1" value="'.$perpage.'" />';
            helpbutton('pagesize', get_string('pagesize','ilptarget'), 'target');
            echo '</td></tr>';
            echo '<tr>';
            echo '<td colspan="2" align="right">';
            echo '<input type="submit" value="'.get_string('savepreferences').'" />';
            echo '</td></tr></table>';
            echo '</form>';


        } else {
            $courses = get_my_courses($USER->id); // should be courses i can teach in
            $courses = get_records_sql("SELECT course.* 
                                        FROM {$CFG->prefix}role_assignments ra,
                                             {$CFG->prefix}role_capabilities rc,
                                             {$CFG->prefix}context c,
                                             {$CFG->prefix}course course
                                        WHERE ra.userid = $USER->id
                                        AND   ra.contextid = c.id
                                        AND   ra.roleid = rc.roleid
                                        AND   rc.capability = 'block/ilp:viewclass'
                                        AND   c.instanceid = course.id
                                        AND   c.contextlevel = ".CONTEXT_COURSE);
                                        
            //$baseurl = $CFG->wwwroot.'/blocks/bksb/bksb_initial_assessment.php?courseid='.$courseid;							
                    
            require_once($CFG->libdir.'/tablelib.php');
            $table = new flexible_table('ilp-mentee-list');
            
            if ($updatepref > 0){
                $perpage = optional_param('perpage', 10, PARAM_INT);
                $perpage = ($perpage <= 0) ? 10 : $perpage ;
                set_user_preference('ilp_personal_tutor_perpage', $perpage);
            }
        
            /* next we get perpage and from database */
            $perpage = get_user_preferences('ilp_personal_tutor_perpage', 10);
            $page    = optional_param('page', 0, PARAM_INT);	
                
            $tablecolumns = array('picture', 'fullname');
            $tableheaders = array('', get_string('fullname'));
            
            $tablecolumns[] .= 'ilp';
            $tableheaders[] .= '';

            $table->define_columns($tablecolumns);
            $table->define_headers($tableheaders);
            $table->define_baseurl($baseurl);

            $table->sortable(true, 'lastname');
            $table->collapsible(false);
            $table->initialbars(false);
        
            $table->column_suppress('picture');	
            $table->column_class('picture', 'picture');
            $table->column_class('fullname', 'fullname');

            $table->set_attribute('cellspacing', '0');
            $table->set_attribute('id', 'attempts');
            $table->set_attribute('class', 'generalbox');
            $table->set_attribute('width', '90%');
            $table->set_attribute('align', 'center');

        // Start working -- this is necessary as soon as the niceties are over

            $table->setup();
            

        ///offset used to calculate index of student in that particular query, needed for the pop up to know who's next
            $offset = $page * $perpage;            
            
            $mentee_select = "SELECT u.* ";
            $mentee_from = "FROM {$CFG->prefix}role_assignments ra, {$CFG->prefix}context c, {$CFG->prefix}user u ";
            if($CFG->ilpconcern_status_per_student = 1) {	
                $mentee_select .= ", s.status ";	
                $mentee_from .= "LEFT JOIN {$CFG->prefix}ilpconcern_status s ON u.id = s.userid ";
            }
            $mentee_where = "WHERE ra.userid = $USER->id AND ra.contextid = c.id AND c.instanceid = u.id AND c.contextlevel = ".CONTEXT_USER;
            if ($table->get_sql_where()) {
                $mentee_where .= ' AND '.$table->get_sql_where();
            }
            if ($table->get_sql_sort()) {
                $mentee_sort = ' ORDER BY '.$table->get_sql_sort();
            }else{
                $mentee_sort = '';
            }
        
            $nomentees = get_records_sql($mentee_select.$mentee_from.$mentee_where.$mentee_sort);
            $table->pagesize($perpage, count($nomentees));

            if (($mentees = get_records_sql($mentee_select.$mentee_from.$mentee_where.$mentee_sort, $table->get_page_start(), $table->get_page_size())) !== FALSE) {	

            print_heading(get_string('blockname', 'block_mentees'));	
            foreach ($mentees as $mentee) {
                $picture = print_user_picture($mentee->id, $course->id, $mentee->picture, false, true);
                $row = array($picture, fullname($mentee));
                if($CFG->ilpconcern_status_per_student == 1){
                    $studentstatus = $mentee->status;

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
                    
                    $row[] .= '<span class="status-'.$studentstatus.'">'.$thisstudentstatus.'</span>';
                }	
                
                $row[] .= $update;
                $table->add_data($row);
            }

            $table->print_html();  /// Print the whole table
            

            // Mini form for setting user preference
            echo '<br />';
            echo '<form name="options" action="bksb_initial_assessment.php?courseid='.$courseid.'" method="post">';
            echo '<input type="hidden" id="updatepref" name="updatepref" value="1" />';
            echo '<table id="optiontable" align="center">';
            echo '<tr align="right"><td>';
            echo '<label for="perpage">'.get_string('pagesize','ilptarget').'</label>';
            echo ':</td>';
            echo '<td align="left">';
            echo '<input type="text" id="perpage" name="perpage" size="1" value="'.$perpage.'" />';
            helpbutton('pagesize', get_string('pagesize','ilptarget'), 'target');
            echo '</td></tr>';
            echo '<tr>';
            echo '<td colspan="2" align="right">';
            echo '<input type="submit" value="'.get_string('savepreferences').'" />';
            echo '</td></tr></table>';
            echo '</form>';
            }

	 if ($courses) {
            print_heading(get_string('courses'));
			echo '<div class="generalbox" style="width:90%; margin:auto">';
            foreach ($courses as $course) {
    ?>
        <a href="bksb_initial_assessment.php?courseid=<?php echo $course->id ?>"><?php echo $course->fullname ?></a><br />

    <?php
            }
			echo '</div>';
        }
        if (!($mentees or $courses)) {
            $get_params = ($courseid != 1) ? '?courseid='.$courseid.'&amp;userid='.$_SESSION['USER']->id : '?userid='.$_SESSION['USER']->id;
            redirect("bksb_initial_assessment.php$get_params", 'You are being directed to your own initial assessment results',0);
        }
    }
    }

    print_footer($course);

?>

