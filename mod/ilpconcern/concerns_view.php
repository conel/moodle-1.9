<?php  

/*
 * @copyright &copy; 2007 University of London Computer Centre
 * @author http://www.ulcc.ac.uk, http://moodle.ulcc.ac.uk
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package ILP
 * @version 1.0
 */

    require_once("../../config.php");
	require_once($CFG->dirroot.'/blocks/ilp/block_ilp_lib.php');
    require_once("lib.php");

    global $CFG, $USER;

    $id = optional_param('id', 0, PARAM_INT); // Course Module ID, or
    $a  = optional_param('a', 0, PARAM_INT);  // concerns ID
	$userid = optional_param('userid', 0, PARAM_INT); // User's concerns we wish to view
	$courseid     = optional_param('courseid', 0, PARAM_INT); 
	$status = optional_param('status', 0, PARAM_INT);
	$studentstatus = optional_param('studentstatus', 0, PARAM_INT);
	$concernspost = optional_param('concernspost', 0, PARAM_INT);
	$action = optional_param('action',NULL, PARAM_CLEAN);
	$template = optional_param('template',0,PARAM_INT);
	// nkowald - 2010-11-30 - Added required param for target grade change
	$g = optional_param('g', '', PARAM_RAW);
	
	require_login();
    //add_to_log($userid, "concerns", "view", "view.php", "$userid");
	// nkowald - 2011-10-25 - Updated this to use it as it's supposed to be used and change 'concerns' to 'ilp' as module level more useful (and correct)
	$this_page = ($_SERVER['REQUEST_URI'] != '') ? $_SERVER['REQUEST_URI'] : 'concerns_view.php';
    add_to_log($courseid, "ilp", "view concerns", $this_page, $userid);

	// Print the main part of the page
	if ($userid > 0){
		$user = get_record('user', 'id', ''.$userid.'');
	} else {
		$user = $USER;
	}

	$strconcerns = get_string("modulenameplural", "ilpconcern");
    $strconcern  = get_string("modulename", "ilpconcern");
    $strilp = get_string("ilp", "block_ilp");
    $stredit = get_string("edit");
    $strdelete = get_string("delete");
    $strcomments = get_string("comments", "ilpconcern");

	if($id > 0){ //module is accessed through a course module use course context 

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
		$link_values = '?id='.$cm->id.'&amp;userid='.$user->id;

		$navigation = "<a href=\"../../course/view.php?id=$course->id\">$course->shortname</a> -> <a href=\"../../blocks/ilp/view.php?courseid=$course->id&amp;id=$user->id\">$strilp</a>";		
		print_header("$strconcerns: ".fullname($user)."", "$course->fullname",
                 "$navigation -> ".$strconcerns." -> ".fullname($user)."", 
                  "", "", true, update_module_button($cm->id, $course->id, $strconcerns), 
                  navmenu($course, $cm));

		
		$baseurl = $CFG->wwwroot.'/mod/ilpconcern/view.php?id='.$id.'&amp;userid='.$user->id;
		$footer = $course;

    }elseif ($courseid > 0) { //module is accessed via report from within course 


	$course = $course = get_record('course', 'id', $courseid);
		$context = get_context_instance(CONTEXT_COURSE, $course->id);
		$link_values = '?courseid='.$course->id.'&amp;userid='.$user->id;
		$navigation = "<a href=\"../../course/view.php?id=$course->id\">$course->shortname</a> -> <a href=\"../../blocks/ilp/view.php?courseid=$course->id&amp;id=$user->id\">$strilp</a>";	
		print_header("$strconcerns: ".fullname($user)."", "$course->fullname",
                 "$navigation -> ".$strconcerns." -> ".fullname($user)."", 
                  "", "", true, "", "");

		$baseurl = $CFG->wwwroot.'/mod/ilptarget/view.php?id='.$id.'&amp;userid='.$user->id;
		$footer = $course;

	} else { //module is accessed independent of a course use user context

		if($user->id == $USER->id) {
			$context = get_context_instance(CONTEXT_SYSTEM);
		}else{
			$context = get_context_instance(CONTEXT_USER, $user->id);
		}

		$link_values = '?userid='.$user->id;
		$navigation = "<a href=\"../../blocks/ilp/view.php?id=$user->id\">$strilp</a>";

		print_header("$strconcerns: ".fullname($user)."", "", "$navigation -> ".$strconcerns." -> ".fullname($user)."", "", "", true, "", "");	

		$baseurl = $CFG->wwwroot.'/mod/ilpconcern/view.php?userid='.$user->id;
		$footer = '';
	}

	//Allow users to see their own profile, but prevent others

	if (has_capability('moodle/legacy:guest', $context, NULL, false)) {
        error("You are logged in as Guest.");
       } 

	// nkowald - 2010-11-30 - Adding required JavaScript function
    echo '<script type="text/javascript" src="'.$CFG->wwwroot.'/blocks/lpr/config_functions.js"></script>';

if ($action == 'updatestatus') {

//Sets message details for Reports
$messagefrom = get_record('user', 'id', $USER->id);
$messageto = get_record('user', 'id', $userid);
$plpurl = $CFG->wwwroot.'/blocks/ilp/view.php'.$link_values;

// nkowald - 2010-09-07 - Added two new columns to save historical data - modified query to get current 'live' status
	if($report = get_record('ilpconcern_status', 'userid', $userid, 'live', 1)) {
        // nkowald - 2010-09-07 - First update the current record - making live value 0
        $history = $report->history;
		$report->live = 0;
		update_record('ilpconcern_status', $report);

        // now add a new record
		$new_report = new Object; 
		$new_report->userid = $userid;
		$new_report->created  = time();
		$new_report->modified = time();
		$new_report->modifiedbyuser = $USER->id;
		$new_report->status = $studentstatus;
		$new_report->history = ($history + 1);
		$new_report->live = 1;
		insert_record('ilpconcern_status', $new_report, true);
	} else {
		$report = new Object; 
		$report->userid = $userid;
		$report->created  = time();
		$report->modified = time();
		$report->modifiedbyuser = $USER->id;
		$report->status = $studentstatus;			
		$report->history = 1;
		$report->live = 1;
		insert_record('ilpconcern_status', $report, true);
	}
	
	if($CFG->ilpconcern_send_concern_message == 1){
		
		switch($studentstatus) {
			case "0":
				$thisconcernstatus = get_string('green', 'ilpconcern');	
				break;
			case "1":
				$thisconcernstatus = get_string('amber', 'ilpconcern');
				break;
			case "2":
				$thisconcernstatus = get_string('red', 'ilpconcern');
					break;
			case "3":
				$thisconcernstatus = get_string('withdrawn', 'ilpconcern');
				break;
			}
				
			$updatedstatus = get_string('statusupdate', 'ilpconcern', $thisconcernstatus);
		
			$message = '<p>'.$updatedstatus.'<br /><a href="'.$plpurl.'">'.$concernview.'</a></p>';				
			message_post_message($messagefrom, $messageto, $message, FORMAT_HTML, 'direct');
	}  		        

// nkowald - 2011-02-07 - Should accept '0' (not set).
//} else if ($action == 'updatetarget' && $g != 0) {
} else if ($action == 'updatetarget') {
    $result = set_target_grade($userid, $g);
    if (!$result) {
        echo 'Error setting target grade';
    }
}

		//Determine report type 		
		switch($status) {
			case "0":
				$thisreporttype = get_string('report1', 'ilpconcern');
				break;
			case "1":
				$thisreporttype = get_string('report2', 'ilpconcern');
				break;
			case "2":
				$thisreporttype = get_string('report3', 'ilpconcern');
				break;
			case "3":
				// TODO: nkowald - Find where get_string 'Communication Record' is located and use this instead
				$thisreporttype = 'Student Progress';
				break;
		}

// nkowald - Form action wasn't set and was messing up student status changing
$form_action = "concerns_view.php?courseid=$courseid&userid=$userid&status=$status";
$mform = new ilpconcern_updateconcern_form($form_action, array('userid' => $user->id, 'id' => $id, 'courseid' => $courseid, 'concernspost' => $concernspost, 'status' => $status, 'reporttype' => $thisreporttype, 'template' => $template));

if ($mform->is_cancelled()){
}
if($fromform = $mform->get_data()){        
	$mform->process_data($fromform);
}
if($action == 'delete'){ //Check to see if we are deleting a comment
	delete_records('ilpconcern_posts', 'id', $concernspost);
}
if($action == 'updateconcern'){
	
	if ($status == 3) {
		print_heading('Add Student Progress');
	} else {
		print_heading(get_string('add','ilpconcern'));
	}
		
	if($CFG->ilpconcern_use_template == 1){
		$select = "module = 'ilpconcern' AND status = $status";
		$no_templates = count_records_select('ilp_module_template',$select);
		
		if($no_templates > 1){	
		 	$templates = get_records_select('ilp_module_template',$select,'name');
			
			$options = array();
    		foreach ($templates as $templateoption) {
				$options[$templateoption->id] = $templateoption->name;
			}
			echo '<div class="ilpcenter">';		
			popup_form ($CFG->wwwroot.'/mod/ilpconcern/concerns_view.php?'.(($courseid > 1)?'courseid='.$courseid.'&amp;' : '').'userid='.$userid.'&amp;action=updateconcern&amp;status='.$status.'&amp;template=', $options, "choosetemplate", $template, get_string('select').'...', "", "", false, 'self', get_string('template','ilpconcern'));
			echo '</div>';
		}
	}
	
	$mform->display();
}else{ 
	if($CFG->ilpconcern_status_per_student == 1){

	if($studentstatus = get_record('ilpconcern_status', 'userid', $user->id, 'live', 1)){

		switch ($studentstatus->status) {
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
	}else{
		$studentstatus->status = 0;
		$thisstudentstatus = get_string('green', 'ilpconcern');
	}

	if(has_capability('mod/ilpconcern:updatestudentstatus', $context)){

		// nkowald - 2010-09-07 - Scott got me to change the text to be Student Progress
		//print_heading(get_string('studentstatus', 'ilpconcern').': '.$thisstudentstatus, '', '2', $class='main status-'.$studentstatus->status.'');
		print_heading('Student Progress: '.$thisstudentstatus, '', '2', $class='main status-'.$studentstatus->status.'');

		echo '<div class="ilpcenter">';
		update_student_status_menu($user->id,$courseid);
	}else{
		print_heading(get_string('mystudentstatus', 'ilpconcern').': '.$thisstudentstatus, '', '2', $class='main status-'.$studentstatus->status.'');
	}

    // nkowald - 2010-11-29 - Use this role to see if can add target
	if(has_capability('mod/ilpconcern:updatestudentstatus', $context)){
        echo '&nbsp;&nbsp; Update Target Grade &nbsp;';
        display_target_grade_box($userid);
        echo '</div>';
    }
}else{
	if($USER->id != $user->id){
		require_capability('mod/ilpconcern:view', $context);
		print_heading(get_string('concernsreports', 'ilpconcern', fullname($user)));
	}else{
		print_heading(get_string('myconcerns', 'ilpconcern'));
	}
} 

	$tabs = array();
   	$tabrows = array();

		if($CFG->ilpconcern_report1 == 1){
		$tabrows[] = new tabobject('0', "$link_values&amp;status=0", get_string('report1', 'ilpconcern'));
		}
		if($CFG->ilpconcern_report2 == 1){
    	$tabrows[] = new tabobject('1', "$link_values&amp;status=1", get_string('report2', 'ilpconcern'));
		}
		if($CFG->ilpconcern_report3 == 1){
    	$tabrows[] = new tabobject('2', "$link_values&amp;status=2", get_string('report3', 'ilpconcern'));
		}
		if($CFG->ilpconcern_report4 == 1){
    	//$tabrows[] = new tabobject('3', "$link_values&amp;status=3", get_string('report4', 'ilpconcern'));
		$tabrows[] = new tabobject('3', "$link_values&amp;status=3", "Student Progress");
		}
		// nkowald - 2010-07-01 - Added Subject Reviews to this page
		$tabrows[] = new tabobject('4', "$link_values&amp;status=4", "Subject Targets");

		$tabs[] = $tabrows;

    	print_tabs($tabs, $status);
		
		// nkowald - 2011-07-22 - Moved this above content to make it easier to add these
		echo '<div class="addbox">';

			if($CFG->ilpconcern_report1 == 1 && (has_capability('mod/ilpconcern:addreport1', $context) || ($USER->id == $user->id && has_capability('mod/ilpconcern:addownreport1', $context)))) {
				echo '<a href="'.$link_values.'&amp;action=updateconcern&amp;status=0"><span></span>'.get_string('addconcern', 'ilpconcern', get_string('report1', 'ilpconcern')).'</a>';
			}
			if($CFG->ilpconcern_report2 == 1 && (has_capability('mod/ilpconcern:addreport2', $context) || ($USER->id == $user->id && has_capability('mod/ilpconcern:addownreport2', $context)))) {
				echo '<a href="'.$link_values.'&amp;action=updateconcern&amp;status=1"><span></span>'.get_string('addconcern', 'ilpconcern', get_string('report2', 'ilpconcern')).'</a>';
			}
			if($CFG->ilpconcern_report3 == 1 && (has_capability('mod/ilpconcern:addreport3', $context) || ($USER->id == $user->id && has_capability('mod/ilpconcern:addownreport3', $context)))) {
				echo '<a href="'.$link_values.'&amp;action=updateconcern&amp;status=2"><span></span>'.get_string('addconcern', 'ilpconcern', get_string('report3', 'ilpconcern')).'</a>';
			}
			if($CFG->ilpconcern_report4 == 1 && (has_capability('mod/ilpconcern:addreport4', $context) || ($USER->id == $user->id && has_capability('mod/ilpconcern:addownreport4', $context)))) {
				echo '<a href="'.$link_values.'&amp;action=updateconcern&amp;status=3"><span></span>'.get_string('addconcern', 'ilpconcern', 'Student Progress').'</a>';
			}
			if($CFG->ilpconcern_report4 == 1 && (has_capability('mod/ilpconcern:addreport4', $context) || ($USER->id == $user->id && has_capability('mod/ilpconcern:addownreport4', $context)))) {
				echo '<a href="'.$CFG->wwwroot.'/blocks/lpr/actions/new.php?course_id='.$courseid.'&amp;ilp=1&amp;learner_id='.$user->id.'"><span></span>Add Subject Target</a>';
			}

		echo '<br class="clear_both" />';
		echo '</div><br class="clear_both" />';

    	$i = $status + 1;
		display_ilpconcern($user->id, $courseid, $i, TRUE, FALSE, FALSE, $sortorder='DESC', 0);
		
		// nkowald - 2010-07-01 - Added Subject Reviews to this page
		if ($status == 4) {
			display_ilp_lprs($user->id, $courseid, TRUE, TRUE, TRUE, 'DESC', 10);
		}

		$saved = (isset($_POST['saved'])) ? $_POST['saved'] : 0;
		$valid_stati = array(0,3);
        if (isset($_POST) && $saved == 1 && in_array($status, $valid_stati)) {

        echo '<div id="ilp_navigation">';
        echo '<h3>ILP Navigator</h3>';
        echo '<a href="#" class="ilp_nav_minimise"><span>Minimise</span></a>';
        echo '<div id="ilp_nav_content">';

			echo '<div class="note">';
            if ($status == 0) {
				// Tutor Reviews
				echo '<p><span>This is the tutor review you just added/edited</span><br />
				<span>Options:</span> Add subject target for this user, add tutor review for the next user, view the ILP list.</p>';
            } else if ($status == 3) {
				// Subject Target
				echo '<p><span>This is the student progress you just added/edited</span><br />
				<span>Options:</span> Add subject target for this user, add student progress for the next user, view the ILP list.</p>';
			}
			echo '</div>';

            if (!$editable) {

				$learner_name = fullname($user);
				$user_pic = print_user_picture($user, $courseid, $user->picture, false, true);
				echo "<h5>This Learner</h5>$user_pic $learner_name";
				$learner_name = (strlen($learner_name) > 16) ? substr($learner_name, 0, 17) . '...' : $learner_name;
				
				if ($status == 0) {
					// Tutor Reviews
					// Add button to add a subject target for student	
					echo '<br /><input class="ilpn_button" type="button" onclick="javascript:window.location = \''. $CFG->wwwroot .'/blocks/lpr/actions/new.php?course_id='.$courseid.'&ilp=1&learner_id='.$user->id.'\'" name="subject_target" value="Add Subject Target for '.$learner_name.'" />';
					
					// nkowald - added a way to show next learner based on chosen group
					// get_next_user_in_course defined in /lib/weblib.php
					$group_id = (isset($_SESSION['chosen_group']['course']) && $_SESSION['chosen_group']['course'] == $course->id && isset($_SESSION['chosen_group']['group'])) ? $_SESSION['chosen_group']['group'] : '';
					if ($next_student = get_next_user_in_course($courseid, $user->id, $group_id)) {
						echo '<br /><br />';
						$user_pic = print_user_picture($next_student, $courseid, $next_student->picture, false, true);
						$learner_name = fullname($next_student);
						echo '<h5>Next Learner</h5>';
						echo "$user_pic $learner_name";
						$learner_name = (strlen($learner_name) > 16) ? substr($learner_name, 0, 17) . '...' : $learner_name;
						echo '<br /><input class="ilpn_button" type="button" onclick="javascript:window.location = \''. $CFG->wwwroot .'/mod/ilpconcern/concerns_view.php?courseid='.$courseid.'&userid='.$next_student->id.'&action=updateconcern&status=0\'" name="tutor_review" value="Add Tutor Review for '.$learner_name.'" />';
						echo '<br /><br />';
					}
				
				} else if ($status == 3) {
					// Student Progress
					// Add button to add a for student
					echo '<br /><input class="ilpn_button" type="button" onclick="javascript:window.location = \''. $CFG->wwwroot .'/blocks/lpr/actions/new.php?course_id='.$courseid.'&ilp=1&learner_id='.$user->id.'\'" name="subject_target" value="Add Subject Target for '.$learner_name.'" />';
					
					// nkowald - added a way to show next learner based on chosen group
					// get_next_user_in_course defined in /lib/weblib.php
					$group_id = (isset($_SESSION['chosen_group']['course']) && $_SESSION['chosen_group']['course'] == $course->id && isset($_SESSION['chosen_group']['group'])) ? $_SESSION['chosen_group']['group'] : '';
					if ($next_student = get_next_user_in_course($courseid, $user->id, $group_id)) {
						echo '<br /><br />';
						$user_pic = print_user_picture($next_student, $courseid, $next_student->picture, false, true);
						$learner_name = fullname($next_student);
						echo '<h5>Next Learner</h5>';
						echo "$user_pic $learner_name";
						$learner_name = (strlen($learner_name) > 16) ? substr($learner_name, 0, 17) . '...' : $learner_name;
						echo '<br /><input class="ilpn_button" type="button" onclick="javascript:window.location = \''. $CFG->wwwroot .'/mod/ilpconcern/concerns_view.php?courseid='.$courseid.'&userid='.$next_student->id.'&action=updateconcern&status=3\'" name="student_progress" value="Add Student Progress for '.$learner_name.'" />';
						echo '<br /><br />';
					}
				}
				
				echo '<h5>This Course</h5>';
				// Print button to get to class list
				echo '<input type="button" onclick="javascript:window.location = \''. $CFG->wwwroot .'/blocks/ilp/list.php?courseid='.$courseid.'\'" name="ILP_list" value="View ILP List" />';

				
            echo '</div>';
        echo '</div>';

?>
<script type="text/javascript" src="<?php echo $CFG->wwwroot; ?>/theme/conel/jquery-ui-1.7.1.custom.min.js"></script>
<script type="text/javascript">
//<![CDATA[
jQuery(document).ready(function() {

    jQuery(function() {
		jQuery( "#ilp_navigation" ).draggable();
	});

    jQuery('.ilp_nav_minimise').click(function(event){
        event.preventDefault();
        jQuery('#ilp_nav_content').slideToggle();
        if (jQuery('.maximise').length > 0) {
            jQuery('.ilp_nav_minimise').removeClass('maximise');
        } else {
            jQuery('.ilp_nav_minimise').addClass('maximise');
        }
    });
	// jQuery form checking
	jQuery("#ilp_form").submit(function(e) {
	
		var lecturer = jQuery("#menulecturer_id").val();
		if (lecturer == 0) {
			alert('Lecturer is a required field');
			jQuery("#menulecturer_id").focus();
			return false;
		}
		if (term == 0) {
		var term = jQuery("#menuterm_id").val();
			alert('Term is a required field');
			jQuery("#menuterm_id").focus();
			return false;
		}
		if (jQuery("#ilp_form input[type=checkbox]:checked").length < 1) {
			alert("Module is a required field.\nPlease select a module to report on.");
			return false;
		}
		
	});
});
//]]>
</script>

<?php 

        }

	}

}
		
/// Finish the page

    print_footer($footer);

?>