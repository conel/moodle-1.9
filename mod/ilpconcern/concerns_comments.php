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
    $a  = optional_param('a', 0, PARAM_INT);  // concern ID
	$lprid = optional_param('lprid', 0, PARAM_INT); //User's lpr 
	$userid = optional_param('userid', 0, PARAM_INT); //User's id 
	$commentid = optional_param('commentid', 0, PARAM_INT); //Comment
	$courseid = optional_param('courseid', 0, PARAM_INT); 
	$action = optional_param('action',NULL, PARAM_CLEAN);

	require_login();

	//$post = get_record('ilpconcern_posts', 'id', ''.$concernspost.'');
	//$post = get_record('block_lpr_comments', 'lprid', ''.$lprid.'');	
	//$user = get_record('user', 'id',$post->setforuserid);
	//$posttutor = get_record('user', 'id', ''.$post->setbyuserid.'');   

	$user = get_record('user', 'id',$userid);
	
    add_to_log($user->id, "lprcomment", "view", "view.php", "$concernspost");   

/// Print the main part of the page

	$strconcerns = get_string("modulenameplural", "ilpconcern");

    $strconcern  = get_string("modulename", "ilpconcern");

    $strilp = get_string("ilp", "block_ilp");

    $stredit = get_string("edit");

    $strdelete = get_string("delete");

    $strcomments = get_string("comments", "ilpconcern");


	if($id != 0){ //module is accessed through a course module use course context 

		if (! $cm = get_record("course_modules", "id", $id)) {

            error("Course Module ID was incorrect");
        }  

        if (! $course = get_record("course", "id", $cm->course)) {

            error("Course is misconfigured");
        }

        if (! $concern = get_record("ilpconcern", "id", $cm->instance)) {

            error("Course module is incorrect");
        }

		$context = get_context_instance(CONTEXT_MODULE, $cm->id);

		$link_values = '?id='.$cm->id.'&amp;concernspost='.$concernspost;

		$navigation = "<a href=\"../../course/view.php?id=$course->id\">$course->shortname</a> -> <a href=\"../../blocks/ilp/view.php?courseid=$course->id&amp;id=$user->id\">$strilp</a>";		

		print_header("$strconcerns: ".fullname($user)."", 
		             "$course->fullname",
		             "$navigation -> ".$strconcerns." -> ".fullname($user)."",
		             "", "", true, update_module_button($cm->id, $course->id, $strconcern),navmenu($course, $cm));
                 
		$footer = $course;

    } elseif ($courseid != 0) { //module is accessed via report from within course 

		$course = get_record('course', 'id', $courseid);

		$context = get_context_instance(CONTEXT_COURSE, $course->id);

		$link_values = '?courseid='.$course->id.'&amp;userid='.$user->id.'&amp;lprid='.$lprid;

		$navigation = "<a href=\"../../course/view.php?id=$course->id\">$course->shortname</a> -> <a href=\"../../blocks/ilp/view.php?courseid=$course->id&amp;id=$user->id\">$strilp</a>";		
		
		//print_header("$strconcerns: ".fullname($user)."", "$course->fullname","$navigation -> ".$strconcerns." -> ".fullname($user)."","", "", true, "",navmenu($course));		
		  print_header("$strconcerns: ".fullname($user)."", 
		               "$course->fullname",
		               "$navigation -> ".$strconcerns." -> ".fullname($user)."",
		               "", "", false, "", "");
                  
		display_ilp_lpr($lprid, $user->id, $courseid);
		
		$baseurl = $CFG->wwwroot.'/mod/ilpconcern/view.php?id='.$id.'&amp;userid='.$user->id;

		$footer = $course;

	}else{ //module is accessed independent of a course use user context

		if($user->id == $USER->id) {
			$context = get_context_instance(CONTEXT_SYSTEM);
		}else{
			$context = get_context_instance(CONTEXT_USER, $user->id);
		}

		$link_values = '?lprid='.$lprid;

		$navigation = "<a href=\"../../blocks/ilp/view.php?id=$user->id\">$strilp</a>";

		print_header("$strconcerns: ".fullname($user)."", "", "$navigation -> ".$strconcerns." -> ".fullname($user)."","", "", true, "", "");

		$footer = '';

	}

	//Allow users to see their own profile, but prevent others

	if (has_capability('moodle/legacy:guest', $context, NULL, false)) {

        error("You are logged in as Guest.");

       } 

	if($USER->id != $user->id){

		require_capability('mod/ilpconcern:view', $context);

	}
	
	//$mform = new ilpconcern_updatecomment_form('', array('userid' => $user->id, 'id' => $id, 'courseid' => $courseid, 'lprid' => $lprid, 'commentid' => $commentid));
	$mform = new lpr_updatecomment_form('', array('userid' => $user->id, 'id' => $id, 'courseid' => $courseid, 'lprid' => $lprid, 'commentid' => $commentid));

	if ($mform->is_cancelled()){
	}
	
	if($fromform = $mform->get_data()){        
		$mform->process_data($fromform);
	}
	
	if($action == 'delete'){ //Check to see if we are deleting a comment
		delete_records('block_lpr_comments', 'id', $commentid);
	}
	
	if($action == 'updatecomment'){
		print_heading(get_string('addcomment', 'ilpconcern'));
		$mform->display();
	}else{  

		print_heading(get_string('concerncomments', 'ilpconcern'));
		
		//$comments = get_records('ilpconcern_comments', 'concernspost',$concernspost);
		$comments = get_records('block_lpr_comments', 'lprid', $lprid);
		
        $stryes = get_string('complete', 'ilpconcern');
		$strdelete = get_string('delete');
		$stredit = get_string('edit');
        $strenter  = get_string('update');    

		echo '<div class="ilpcenter">';					    

		if ($comments !== false) {            

            foreach ($comments as $comment) {         
				
				$commentuser = get_record('user','id',$comment->userid);

				echo '<div class="forumpost ilpcomment boxaligncenter">'.format_text($comment->comment, $comment->format).'<div class="commands">'.fullname($commentuser).', '.userdate($comment->created, get_string('strftimedate')).'<br />'.lpr_update_comment_menu($comment->id,$context).'</div></div>';

            }

        }

		echo '</div>';

		if(has_capability('mod/ilpconcern:addcomment', $context) || ($USER->id == $user->id && has_capability('mod/ilpconcern:addowncomment', $context))) {

		echo '<div class="addbox">';

		echo '<a href="'.$link_values.'&amp;action=updatecomment">'.get_string('addcomment', 'ilpconcern').'</a></div>';

		}
	}
	

	//add_to_log($course->id, 'comment', 'view', 

         //  'view.php?id='.$cm->id.'&concernspost='.$concernspost.'&mode=student', fullname($USER), $cm->id);


/// Finish the page

    print_footer($footer);	

?>
