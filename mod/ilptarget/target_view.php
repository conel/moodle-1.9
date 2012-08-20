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
    require_once($CFG->dirroot.'/blocks/ilp/block_ilp_lib.php');
    global $CFG, $USER;

    $id = optional_param('id', 0, PARAM_INT); // Course Module ID, or
    $a  = optional_param('a', 0, PARAM_INT);  // target ID
	$userid = optional_param('userid', 0, PARAM_INT); //User's targets we wish to view
    $courseid     = optional_param('courseid', 0, PARAM_INT);  
	$status = optional_param('status', 0, PARAM_INT);
	$action = optional_param('action',NULL, PARAM_CLEAN);
	$targetpost = optional_param('targetpost', -1, PARAM_INT);

	require_login();
	$this_page = ($_SERVER['REQUEST_URI'] != '') ? $_SERVER['REQUEST_URI'] : 'target_view.php';
    add_to_log($courseid, "ilp", "view target", $this_page, $userid);

    $sitecontext = get_context_instance(CONTEXT_SYSTEM);

	// Print the main part of the page

	if ($userid > 0){
		$user = get_record('user', 'id', ''.$userid.'');
	}else{
		$user = $USER;
	}

	//$strtargets = get_string("modulenameplural", "ilptarget");
	$strtargets = 'Personal Targets';
    //$strtarget  = get_string("modulename", "ilptarget");
    $strtarget  = 'Personal Target';

    $strilp = get_string("ilp", "block_ilp");
    $stredit = get_string("edit");
    $strdelete = get_string("delete");
    $strcomments = get_string("comments", "ilptarget");

	if($id != 0){ //module is accessed through a course module use course context 

		if (! $cm = get_record("course_modules", "id", $id)) {
            error("Course Module ID was incorrect");
        }
    
        if (! $course = get_record("course", "id", $cm->course)) {
            error("Course is misconfigured");
        }

        if (! $target = get_record("ilptarget", "id", $cm->instance)) {
            error("Course module is incorrect");
        }

		$context = get_context_instance(CONTEXT_MODULE, $cm->id);
		$link_values = '?id='.$cm->id.'&amp;userid='.$user->id;
		$navigation = "<a href=\"../../course/view.php?id=$course->id\">$course->shortname</a> -> <a href=\"../../blocks/ilp/view.php?courseid=$course->id&amp;id=$user->id\">$strilp</a>";
		print_header("$strtargets: ".fullname($user)."", "$course->fullname",
                 "$navigation -> ".$strtargets." -> ".fullname($user)."", 
                  "", "", true, update_module_button($cm->id, $course->id, $strtarget), 
                  navmenu($course, $cm));

		
		$baseurl = $CFG->wwwroot.'/mod/ilptarget/view.php?id='.$id.'&amp;userid='.$user->id;
		$footer = $course;

    } elseif ($courseid != 0) { //module is accessed via report from within course 
		$course = $course = get_record('course', 'id', $courseid);
		$context = get_context_instance(CONTEXT_COURSE, $course->id);
		$link_values = '?courseid='.$course->id.'&amp;userid='.$user->id;
		$navigation = "<a href=\"../../course/view.php?id=$course->id\">$course->shortname</a> -> <a href=\"../../blocks/ilp/view.php?courseid=$course->id&amp;id=$user->id\">$strilp</a>";		
		print_header("$strtargets: ".fullname($user)."", "$course->fullname",
                 "$navigation -> ".$strtargets." -> ".fullname($user)."", 
                  "", "", true, "", 
                  navmenu($course));

		$baseurl = $CFG->wwwroot.'/mod/ilptarget/view.php?id='.$id.'&amp;userid='.$user->id;
		$footer = $course;

	} else {  //module is accessed independent of a course use user context
		
		if($user->id == $USER->id) {
			$context = get_context_instance(CONTEXT_SYSTEM);
		} else {
			$context = get_context_instance(CONTEXT_USER, $user->id);
		}


		$link_values = '?userid='.$user->id;
		if ($courseid > 1) {
			$navigation = "<a href=\"../../blocks/ilp/view.php?id=$user->id&amp;courseid=$courseid\">$strilp</a>";
		} else {
			$navigation = "<a href=\"../../blocks/ilp/view.php?id=$user->id\">$strilp</a>";
		}		

		print_header("$strtargets: ".fullname($user)."", "", "$navigation -> ".$strtargets." -> ".fullname($user)."", "", "", true, "", "");		

		$baseurl = $CFG->wwwroot.'/mod/ilptarget/view.php?userid='.$user->id;
		$footer = '';
	}

	
	//Allow users to see their own profile, but prevent others
	if (has_capability('moodle/legacy:guest', $context, NULL, false)) {
        error("You are logged in as Guest.");
       } 

if ($action == 'updatestatus') {

	$report = get_record('ilptarget_posts', 'id', $targetpost);  // Get or make one
	$report->status      = $status;      
	$report->timemodified   = time();
		
	update_record('ilptarget_posts', addslashes_object($report));
		
	if($CFG->ilptarget_send_target_message == 1){
	
			switch($status) {
				case "0":
					$thistargetstatus = get_string('outstanding', 'ilptarget');
					break;
				case "1":
					$thistargetstatus = get_string('achieved', 'ilptarget');
					break;
				//case "2":
					//$thistargetstatus = get_string('notachieved', 'ilptarget');
					//break;
				case "3":
					$thistargetstatus = get_string('withdrawn', 'ilptarget');
					break;
			}
				
			$updatedstatus = get_string('statusupdate', 'ilptarget', $thistargetstatus);
			
			//Sets message details for Targets
			$messagefrom = get_record('user', 'id', $USER->id);
			$messageto = get_record('user', 'id', $userid);
			$targeturl = $CFG->wwwroot.'/mod/ilptarget/target_view.php?'.(($courseid)?'courseid='.$courseid.'&amp;' : '').'userid='.$id.'">';
			$updatedstatus = get_string('statusupdate', 'ilptarget', $thistargetstatus);
			
			$message = '<p>'.$updatedstatus.'<br /><a href="'.$targeturl.'&amp;status='.$status.'">'.$targetview.'</a></p>';
			// This function fails
			//message_post_message($messagefrom, $messageto, $message, FORMAT_HTML, 'direct');
		}        
}

$targeturl = $CFG->wwwroot.'/mod/ilptarget/target_view.php?'.(($courseid)?'courseid='.$courseid.'&' : '').'userid='.$userid;
$mform = new ilptarget_updatetarget_form($targeturl, array('userid' => $user->id, 'id' => $id, 'courseid' => $courseid, 'targetpost' => $targetpost, 'linkvalues' => $link_values));

if ($mform->is_cancelled()){
}
if($fromform = $mform->get_data()){        
	$mform->process_data($fromform);
}
if($action == 'delete'){ //Check to see if we are deleting a comment
	$report = get_record('ilptarget_posts', 'id', $targetpost);
    delete_records('ilptarget_posts', 'id', $report->id);
    delete_records('ilptarget_comments', 'targetpost', $report->id, 'userid', $user->id);
    delete_records('event', 'name', $report->name, 'instance', $report->id, 'userid', $user->id);
}
if($action == 'updatetarget'){
	print_heading(get_string('add','ilptarget'));
	$mform->display();
}else{  
	if($USER->id != $user->id){
		require_capability('mod/ilptarget:view', $context);
		print_heading(get_string('targetreports', 'ilptarget', fullname($user)));
	}else{
		print_heading(get_string('mytargets', 'ilptarget'));
	}	

	$tabs = array();
	$tabrows = array();
	
	$tabrows[] = new tabobject('0', "$link_values&amp;status=0", get_string('outstanding', 'ilptarget'));
	$tabrows[] = new tabobject('1', "$link_values&amp;status=1", get_string('achieved', 'ilptarget'));
	//$tabrows[] = new tabobject('2', "$link_values&amp;status=2", get_string('notachieved', 'ilptarget'));
	$tabrows[] = new tabobject('3', "$link_values&amp;status=3", get_string('withdrawn', 'ilptarget'));
	$tabs[] = $tabrows;
	
	print_tabs($tabs, $status);
	
	// nkowald - 2011-10-10 - Scott got me to move the add new target button above the targets
	if(has_capability('mod/ilptarget:addtarget', $context) || ($USER->id == $user->id)) {
		echo '<div class="addbox"><a href="'.$link_values.'&amp;action=updatetarget">'.get_string('add', 'ilptarget').'</a></div><br /><br />';
	}

	display_ilptarget($user->id,$courseid,TRUE,FALSE,FALSE,$sortorder='DESC',0,$status, FALSE, FALSE, TRUE, FALSE);
	
	//if(has_capability('mod/ilptarget:addtarget', $context) || ($USER->id == $user->id && has_capability('mod/ilptarget:addowntarget', $context, $user->id))) {
	if(has_capability('mod/ilptarget:addtarget', $context) || ($USER->id == $user->id)) {

	// nkowald - 2011-08-08 - Adding ILP Navigator
    $saved = (isset($_POST['saved'])) ? $_POST['saved'] : 0;
	
    if (isset($_POST) && $saved == 1 && (get_role_staff_or_student($USER->id) == 3)) {

    echo '<div id="ilp_navigation">';
        echo '<h3>ILP Navigator</h3>';
        echo '<a href="#" class="ilp_nav_minimise"><span>Minimise</span></a>';
        echo '<div id="ilp_nav_content">';
        echo '
        <div class="note">
            <p><span>This is the personal target you just added/edited</span><br /><span>Options:</span> Add personal target for the next user, view the ILP list.</p>
        </div>';

            if ($next_student = get_next_user_in_course($courseid, $user->id)) {
                $user_pic = print_user_picture($next_student, $courseid, $next_student->picture, false, true);
                $learner_name = fullname($next_student);
                echo '<h5>Next Learner</h5>';
                echo "$user_pic $learner_name";

                // limit learner's name to 40 characters
                $learner_name = (strlen($learner_name) > 16) ? substr($learner_name, 0, 17) . '...' : $learner_name;
                echo '<br /><input class="ilpn_button" type="button" onclick="javascript:window.location = \''.$CFG->wwwroot .'/mod/ilptarget/target_view.php?courseid='.$courseid.'&userid='.$next_student->id.'&action=updatetarget\'" name="personal_target" value="Add Personal Target for '.$learner_name.'" />';
                echo '<br /><br />';
            }

            echo '<h5>This Course</h5>';

            // Print button to get to class list
            echo '<input class="ilpn_button" type="button" onclick="javascript:window.location = \''. $CFG->wwwroot .'/blocks/ilp/list.php?courseid='.$courseid.'\'" name="ILP_list" value="View ILP List" />';
            
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
    // Finish the page
    print_footer($footer);

?>
