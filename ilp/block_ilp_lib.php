<?PHP // $Id: block_ilp_lib.php,v 1.5.2.11 2009/09/06 14:49:37 ulcc Exp $

//  given userid spews out users ilp report
//  this bit just queries db then hands massive assoc array to the template.

function block_ilp_report($id,$courseid) {

    global $CFG, $USER;

	$module = 'project/ilp';
	$config = get_config($module);

    $user = get_record('user','id',$id);

    if (!$user) {
      error("bad user $id");
    }

	if($CFG->ilpconcern_status_per_student == 1){
		if($studentstatus = get_record('ilpconcern_status', 'userid', $id)){
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
			$studentstatusnum = $studentstatus->status;
		}else{
			$studentstatusnum = 0;
			$thisstudentstatus = get_string('green', 'ilpconcern');
		}
	}

    if (file_exists('templates/custom/template.php')) {
      include('templates/custom/template.php');
    }elseif (file_exists('template.php')) {
      include('template.php');
    }else{
      error("missing template \"$template\"") ;
    }

}


function get_my_ilp_courses($userid) {
    global $CFG, $USER;

	$module = 'project/ilp';
	$config = get_config($module);

	$courses = get_my_courses($userid);

	if($config->ilp_limit_categories == '1') {
		$ilp_categories = $config->ilp_categories;
		$allowed_categories = explode(',', $ilp_categories);

		foreach ($courses as $course){
			if(in_array($course->category,$allowed_categories)){
				$ilpcourses[] = $course;
			}
		}
	}else{
		$ilpcourses = $courses;
	}
	return $ilpcourses;
}

function print_row($left, $right) {
    echo "$left $right<br />";
}



function display_custom_profile_fields($userid) {
    global $CFG, $USER;

    if ($categories = get_records_select('user_info_category', '', 'sortorder ASC')) {
        foreach ($categories as $category) {
            if ($fields = get_records_select('user_info_field', "categoryid=$category->id", 'sortorder ASC')) {
                foreach ($fields as $field) {
                    require_once($CFG->dirroot.'/user/profile/field/'.$field->datatype.'/field.class.php');
                    $newfield = 'profile_field_'.$field->datatype;
                    $formfield = new $newfield($field->id, $userid);
                    if (!$formfield->is_empty()) {
                        print_row(s($formfield->field->name.':'), $formfield->display_data());
                    }
                }
            }
        }
    }
}

/**
     * Displays the Student Info summary to the ILP
     *
     * @param id   			userid fed from ILP page (required)
     * @param courseid   	courseid fed from ILP page (required)
     * @param full   		display a full report or just a title link - for layout and navigation
     * @param title  		display default title - turn off to add customised title to template
	 * @param icon   		display an icon with the title
	 * @param teachertext   display the teacher text section
	 * @param studenttext   display the student text section
	 * @param sharedtext   	display the shared text section
*/

function display_ilp_student_info ($id,$courseid,$full=TRUE,$title=TRUE,$icon=TRUE,$teachertext=TRUE,$studenttext=TRUE,$sharedtext=TRUE) {

	global $CFG,$USER;
	require_once("../ilp_student_info/block_ilp_student_info_lib.php");
	include ('access_context.php');

	$module = 'project/ilp';
    $config = get_config($module);

	$user = get_record('user','id',$id);

	if($title == TRUE) {
		echo '<h2>';

		if ($icon == TRUE) {
			if (file_exists('templates/custom/pix/student_info.gif')) {
				echo '<img src="'.$CFG->wwwroot.'/blocks/ilp/templates/custom/pix/student_info.gif" alt="" />';
			}else{
      			echo '<img src="'.$CFG->wwwroot.'/blocks/ilp/pix/student_info.gif" alt="" />';
			}
		}

		echo '<a href="'.$CFG->wwwroot.'/blocks/ilp_student_info/view.php?id='.$id.(($courseid)?'&courseid='.$courseid:'').'&amp;view=info">'.(($access_isuser)?get_string('viewmyilp_student_info','block_ilp_student_info'):get_string('ilp_student_info', 'block_ilp_student_info')).'</a></h2>';
	}

	if($full == TRUE) {

		if($config->block_ilp_student_info_allow_per_student_teacher_text == 1 && $teachertext == TRUE) {

			$text = block_ilp_student_info_get_text($user->id,0,0,'student','teacher') ;
			echo '<div class="block_ilp_student_info_text">'.stripslashes($text->text).'</div>';

			if($access_isteacher or $access_istutor or $access_isgod) {
				echo block_ilp_student_info_edit_button($user->id,0,(($courseid)? $courseid : 0),'student','teacher',$text->id) ;
			}
		}

		if($config->block_ilp_student_info_allow_per_student_student_text == 1 && $studenttext == TRUE) {

			$text = block_ilp_student_info_get_text($user->id,0,0,'student','student') ;
			echo '<div class="block_ilp_student_info_text">'.stripslashes($text->text).'</div>';

			if($access_isuser or $access_isgod) {
				echo block_ilp_student_info_edit_button($user->id,0,(($courseid)? $courseid : 0),'student','student',$text->id) ;
			}
		}

		if($config->block_ilp_student_info_allow_per_student_shared_text == 1 && $sharedtext == TRUE) {
			$text = block_ilp_student_info_get_text($user->id,0,0,'student','shared') ;
			echo '<div class="block_ilp_student_info_text">'.stripslashes($text->text).'</div>';

			if($access_isuser or $access_isteacher or $access_istutor or $access_isgod) {
				echo block_ilp_student_info_edit_button($user->id,0,(($courseid)? $courseid : 0),'student','shared',$text->id);
			}
		}
	}
}

/**
     * Displays the ilptarget summary to the ILP
     *
     * @param id   			userid fed from ILP page (required)
     * @param courseid   	courseid fed from ILP page (required)
     * @param full   		display a full report or just a title link - for layout and navigation
     * @param title  		display default title - turn off to add customised title to template
	 * @param icon   		display an icon with the deafult title
	 * @param sortorder     DESC or ASC - to sort on deadline dates
	 * @param limit		    limit the number of targets shown on the page
	 * @param status	    -1 means all otherwise a particular status can be entered
	 * @param tutorsetonly 	display tutor set targets only
	 * @param studentsetonly display student set targets only
*/

function display_ilptarget ($id,$courseid,$full=TRUE,$title=TRUE,$icon=TRUE,$sortorder='ASC',$limit=0,$status=-1,$tutorsetonly=FALSE,$studentsetonly=FALSE) {

	global $CFG,$USER;
	require_once("$CFG->dirroot/blocks/ilp_student_info/block_ilp_student_info_lib.php");
	require_once("$CFG->dirroot/mod/ilptarget/lib.php");
	include ('access_context.php');

	$module = 'project/ilp';
    $config = get_config($module);

	$user = get_record('user','id',$id);

	$select = "SELECT {$CFG->prefix}ilptarget_posts.*, up.username ";
	$from = "FROM {$CFG->prefix}ilptarget_posts, {$CFG->prefix}user up ";
	$where = "WHERE up.id = setbyuserid AND setforuserid = $id ";

	if($status != -1) {
		$where .= "AND status = $status ";
	}elseif($config->ilp_show_achieved_targets == 1){
    	$where .= "AND status != 3 ";
	}else{
    	$where .= "AND status = 0 ";
	}

	if($CFG->ilptarget_course_specific == 1 && $courseid != 0){
		$where .= "AND course = $courseid ";
	}

	if($tutorsetonly == TRUE && $studentsetonly == FALSE) {
		$where .= "AND setforuserid != setbyuserid ";
	}

	if($studentsetonly == TRUE && $tutorsetonly == FALSE) {
		$where .= "AND setforuserid = setbyuserid ";
	}

	$order = "ORDER BY deadline $sortorder ";

    $target_posts = get_records_sql($select.$from.$where.$order,0,$limit);

	if($title == TRUE) {
		echo '<h2';
		if($full == FALSE) {
			echo ' style="display:inline"';
		}
		echo '>';

		if ($icon == TRUE) {
			if (file_exists('templates/custom/pix/target.gif')) {
				echo '<img src="'.$CFG->wwwroot.'/blocks/ilp/templates/custom/pix/target.gif" alt="" />';
			}else{
      			echo '<img src="'.$CFG->wwwroot.'/blocks/ilp/pix/target.gif" alt="" />';
			}
		}

		echo '<a href="'.$CFG->wwwroot.'/mod/ilptarget/target_view.php?'.(($courseid > 1)?'courseid='.$courseid.'&amp;' : '').'userid='.$id.'">'.(($access_isuser)? get_string("mytargets", "ilptarget"):get_string("modulenameplural", "ilptarget")).'</a></h2>';
	}

	if($full == FALSE) {
		$targettotal = count_records_sql('SELECT COUNT(*) FROM '.$CFG->prefix.'ilptarget_posts WHERE setforuserid = '.$user->id.' AND status != "3"' );

		$targetcomplete = count_records_sql('SELECT COUNT(*) FROM '.$CFG->prefix.'ilptarget_posts WHERE setforuserid = '.$user->id.' AND status = "1"');

		echo '<p style="display:inline; margin-left: 5px">'.$targetcomplete.'/'.$targettotal.' '.get_string('complete', 'ilptarget').'</p>';
	}

	if($full == TRUE) {
		echo '<div class="block_ilp_ilptarget">';

		if($target_posts) {
			foreach($target_posts as $post) {
				$posttutor = get_record('user','id',$post->setbyuserid);

				echo '<div class="ilp_post yui-t4">';
				   echo '<div class="bd" role="main">';
					echo '<div class="yui-main">';
					echo '<div class="yui-b"><div class="yui-gd">';
					echo '<div class="yui-u first">';
					echo get_string('name', 'ilptarget');
					echo '</div>';
					echo '<div class="yui-u">';
					echo $post->name;
					echo '</div>';
				echo '</div>';
				echo '<div class="yui-gd">';
					echo '<div class="yui-u first">';
					echo '<p>'.get_string('targetagreed', 'ilptarget').'</p>';
						echo '</div>';
					echo '<div class="yui-u">';
					echo '<p>'.$post->targetset.'</p>';
						echo '</div>';
				echo '</div>';
				echo '</div>';
					echo '</div>';
					echo '<div class="yui-b">';
					echo '<ul>';
					echo '<li>'.get_string('setby', 'ilptarget').': '.fullname($posttutor);
					if($post->courserelated == 1){
						$targetcourse = get_record('course','id',$post->targetcourse);
						echo '<li>'.get_string('course').': '.$targetcourse->shortname.'</li>';
					}
					echo '<li>'.get_string('set', 'ilptarget').': '.userdate($post->timecreated, get_string('strftimedateshort'));
					echo '<li>'.get_string('deadline', 'ilptarget').': '.userdate($post->deadline, get_string('strftimedateshort'));
					echo '</ul>';

					$commentcount = count_records('ilptarget_comments', 'targetpost', $post->id);

					echo '<div class="commands"><a href="'.$CFG->wwwroot.'/mod/ilptarget/target_comments.php?'.(($courseid > 1)?'courseid='.$courseid.'&amp;' : '').'userid='.$id.'&amp;targetpost='.$post->id.'">'.$commentcount.' '.get_string("comments", "ilptarget").'</a> ';

					if($post->status == 0 || has_capability('moodle/site:doanything', $context)){
						echo ilptarget_update_status_menu($post->id,$context);
					}
					echo '</div>';

					if($post->status == 1){
						echo '<img class="achieved" src="'.$CFG->pixpath.'/mod/ilptarget/achieved.gif" alt="" />';
					}
					echo '</div>';
					echo '</div>';
				echo '</div>';
			}
		}
		echo '</div>';
	}
}

/**
     * Displays the ilptarget summary to the ILP
     *
     * @param id   			userid fed from ILP page (required)
     * @param courseid   	courseid fed from ILP page (required)
	 * @param report	   	report number from ILP page (required)
     * @param full   		display a full report or just a title link - for layout and navigation
     * @param title  		display default title - turn off to add customised title to template
	 * @param icon   		display an icon with the deafult title
	 * @param sortorder     DESC or ASC - to sort on deadline dates
	 * @param limit		    limit the number of targets shown on the page
	 * @param status	    -1 means all otherwise a particular status can be entered
*/

function display_ilpconcern ($id,$courseid,$report,$full=TRUE,$title=TRUE,$icon=TRUE,$sortorder='DESC',$limit=0) {

	global $CFG,$USER;
	require_once("$CFG->dirroot/blocks/ilp_student_info/block_ilp_student_info_lib.php");
	require_once("$CFG->dirroot/mod/ilpconcern/lib.php");
	include ('access_context.php');

	$module = 'project/ilp';
    $config = get_config($module);

	$user = get_record('user','id',$id);

	$status = $report - 1;

	$select = "SELECT {$CFG->prefix}ilpconcern_posts.*, up.username ";
	$from = "FROM {$CFG->prefix}ilpconcern_posts, {$CFG->prefix}user up ";
	$where = "WHERE up.id = setbyuserid AND status = $status AND setforuserid = $id ";

	if($CFG->ilpconcern_course_specific == 1 && $courseid != 0){
		$where .= 'AND course = '.$courseid.' ';
	}

    $order = "ORDER BY deadline $sortorder ";

    $concerns_posts = get_records_sql($select.$from.$where.$order,0,$limit);

	if($title == TRUE) {
		echo '<h2>';

		if ($icon == TRUE) {
			if (file_exists('templates/custom/pix/report'.$report.'.gif')) {
				echo '<img src="'.$CFG->wwwroot.'/blocks/ilp/templates/custom/pix/report'.$report.'.gif" alt="" />';
			}else{
      			echo '<img src="'.$CFG->wwwroot.'/blocks/ilp/pix/report'.$report.'.gif" alt="" />';
			}
		}

		echo '<a href="'.$CFG->wwwroot.'/mod/ilpconcern/concerns_view.php?'.(($courseid > 1)?'courseid='.$courseid.'&amp;' : '').'userid='.$id.'&amp;status='.$status.'">'.(($access_isuser)? get_string('report'.$report.'plural','ilpconcern'):get_string('report'.$report.'plural','ilpconcern')).'</a></h2>';
	}

	if($full == TRUE) {
		echo '<div class="block_ilp_ilpconcern">';

		if($concerns_posts) {
			foreach($concerns_posts as $post) {
				$posttutor = get_record('user','id',$post->setbyuserid);

				echo '<div class="ilp_post yui-t4">';
				   echo '<div class="bd" role="main">';
					echo '<div class="yui-main">';
					echo '<div class="yui-b">';
					if(isset($post->name)){
						echo '<div class="yui-gd">';
						echo '<div class="yui-u first">';
						echo get_string('name', 'ilpconcern');
						echo '</div>';
						echo '<div class="yui-u">';
						echo $post->name;
						echo '</div>';
					echo '</div>';
					}
				echo '<div class="yui-gd">';
					echo '<div class="yui-u first">';
					echo '<p>'.get_string('report'.$report,'ilpconcern').'</p>';
						echo '</div>';
					echo '<div class="yui-u">';
					echo '<p>'.$post->concernset.'</p>';
						echo '</div>';
				echo '</div>';
				echo '</div>';
					echo '</div>';
					echo '<div class="yui-b">';
					echo '<ul>';
					echo '<li>'.get_string('setby', 'ilpconcern').': '.fullname($posttutor);
					if($post->courserelated == 1){
						$targetcourse = get_record('course','id',$post->targetcourse);
						echo '<li>'.get_string('course').': '.$targetcourse->shortname.'</li>';
					}
					echo '<li>'.get_string('deadline', 'ilpconcern').': '.userdate($post->deadline, get_string('strftimedateshort'));
					echo '</ul>';

					$commentcount = count_records('ilpconcern_comments', 'concernspost', $post->id);

					echo '<div class="commands"><a href="'.$CFG->wwwroot.'/mod/ilpconcern/concerns_comments.php?'.(($courseid > 1)?'courseid='.$courseid.'&amp;' : '').'userid='.$id.'&amp;concernspost='.$post->id.'">'.$commentcount.' '.get_string("comments", "ilpconcern").'</a>';

					echo ilpconcern_update_menu($post->id,$context);

					echo '</div>';

					echo '</div>';
					echo '</div>';
				echo '</div>';
			}
		}
		echo '</div>';
	}
}

/**
     * Displays the Personal report summary to the ILP
     *
     * @param id   			userid fed from ILP page
     * @param courseid   	courseid fed from ILP page
     * @param full   		display a full report or just a title link - for layout and navigation
     * @param title  		display default title - turn off to add customised title to template
	 * @param icon   		display an icon with the title
	 * @param teachertext   display the teacher text section
	 * @param studenttext   display the student text section
	 * @param sharedtext   	display the shared text section
*/

function display_ilp_personal_report ($id,$courseid,$full=TRUE,$title=TRUE,$icon=TRUE,$teachertext=TRUE,$studenttext=TRUE,$sharedtext=TRUE) {

	global $CFG,$USER;
	require_once("../ilp_student_info/block_ilp_student_info_lib.php");
	include ('access_context.php');

	$module = 'project/ilp';
    $config = get_config($module);

	$user = get_record('user','id',$id);

	if($title == TRUE) {
		echo '<h2>';

		if ($icon == TRUE) {
			if (file_exists('templates/custom/pix/personal_report.gif')) {
				echo '<img src="'.$CFG->wwwroot.'/blocks/ilp/templates/custom/pix/personal_report.gif" alt="" />';
			}else{
      			echo '<img src="'.$CFG->wwwroot.'/blocks/ilp/pix/personal_report.gif" alt="" />';
			}
		}

		echo '<a href="'.$CFG->wwwroot.'/blocks/ilp_student_info/view.php?id='.$id.(($courseid)?'&courseid='.$courseid:'').'&amp;view=personal">'.get_string('personal_report', 'block_ilp').'</a></h2>';
	}

	if($full == TRUE) {

    	$context = get_context_instance(CONTEXT_USER, $user->id);
    	$tutors = get_users_by_capability($context, 'moodle/user:viewuseractivitiesreport', 'u.*', 'u.lastname ASC', '', '', '', '', false);

    	if ($tutors) {

			foreach ($tutors as $tutor) {
				if (count_records('ilp_student_info_per_tutor','teacher_userid',$tutor->id, 'student_userid', $user->id) != 0){
					echo '<table style="text-align:left; margin:5px;" class="generalbox"><tbody><tr><th colspan="3">'.fullname($tutor).'<th></tr>';

					if($config->block_ilp_student_info_allow_per_tutor_teacher_text == 1 && $teachertext == TRUE) {
						$text = block_ilp_student_info_get_text($user->id,$tutor->id,$course->id,'tutor','teacher');

						echo '<tr><td>'.get_string('tutor_comment','block_ilp_student_info').':</td></tr><tr><td><div class="block_ilp_student_info_text">'.stripslashes($text->text).'</div></td>';

						if($tutor->id == $USER->id or $access_isgod) {
							echo '<td>'.block_ilp_student_info_edit_button($user->id,$tutor->id,$course->id,'tutor','teacher',$text->id).'</td>';
						}else{
							echo '<td></td></tr>';
						}
					}

					if($config->block_ilp_student_info_allow_per_tutor_student_text == 1 && $studenttext == TRUE) {
						$text = block_ilp_student_info_get_text($user->id,$tutor->id,$course->id,'tutor','student');

						echo '<tr><td>'.get_string('student_response','block_ilp_student_info').':</td></tr><tr><td><div class="block_ilp_student_info_text">'.stripslashes($text->text).'</div></td>';

						if($access_isuser || $access_isgod) {
							echo '<td>'.block_ilp_student_info_edit_button($user->id,$tutor->id,$course->id,'tutor','student',$text->id).'</td></tr>';
						}else{
							echo '<td></td></tr>';
						}
					}

					if($config->block_ilp_student_info_allow_per_tutor_shared_text == 1 && $sharedtext == TRUE) {
						$text = block_ilp_student_info_get_text($user->id,$tutor->id,$course->id,'tutor','shared') ;

						echo '<tr><td>'.get_string('shared_text','block_ilp_student_info').':</td></tr><tr><td><div class="block_ilp_student_info_text">'.stripslashes($text->text).'</div></td>';

						if($access_isuser or $tutor->id == $USER->id or $access_isgod) {
							echo '<td>'.block_ilp_student_info_edit_button($user->id,$tutor->id,$course->id,'tutor','shared',$text->id).'</td></tr>';
						}else{
							echo '<td></td></tr>';
						}
					}
				}elseif($tutor->id == $USER->id){

					if($config->block_ilp_student_info_allow_per_tutor_teacher_text == 1 && $teachertext == TRUE) {
						$text = block_ilp_student_info_get_text($user->id,$tutor->id,$course->id,'tutor','teacher') ;
						echo '<tr><td>'.get_string('notextteacher','block_ilp').':'.block_ilp_student_info_edit_button($user->id,$tutor->id,$course->id,'tutor','teacher',$text->id).'</td></tr>';
					}

					if($config->block_ilp_student_info_allow_per_tutor_shared_text == 1 && $sharedtext == TRUE) {
						$text = block_ilp_student_info_get_text($user->id,$tutor->id,$course->id,'tutor','shared') ;
						echo '<tr><td>'.get_string('notextshared','block_ilp').':'.block_ilp_student_info_edit_button($user->id,$tutor->id,$course->id,'tutor','shared',$text->id).'</td></tr>';
					}
				}
			}
		}
    	unset($tutors);
		echo '</tbody></table>';
	}
}

/**
     * Displays the Personal report summary to the ILP
     *
     * @param id   			userid fed from ILP page
     * @param courseid   	courseid fed from ILP page
     * @param full   		display a full report or just a title link - for layout and navigation
     * @param title  		display default title - turn off to add customised title to template
	 * @param icon   		display an icon with the title
	 * @param teachertext   display the teacher text section
	 * @param studenttext   display the student text section
	 * @param sharedtext   	display the shared text section
*/

function display_ilp_subject_report ($id,$courseid,$full=TRUE,$title=TRUE,$icon=TRUE,$teachertext=TRUE,$studenttext=TRUE,$sharedtext=TRUE) {

	global $CFG,$USER;
	require_once("../ilp_student_info/block_ilp_student_info_lib.php");
	include ('access_context.php');

	$module = 'project/ilp';
    $config = get_config($module);

	$user = get_record('user','id',$id);

	if($title == TRUE) {
		echo '<h2>';

		if ($icon == TRUE) {
			if (file_exists('templates/custom/pix/subject_report.gif')) {
				echo '<img src="'.$CFG->wwwroot.'/blocks/ilp/templates/custom/pix/subject_report.gif" alt="" />';
			}else{
      			echo '<img src="'.$CFG->wwwroot.'/blocks/ilp/pix/subject_report.gif" alt="" />';
			}
		}

		echo '<a href="'.$CFG->wwwroot.'/blocks/ilp_student_info/view.php?id='.$id.(($courseid)?'&courseid='.$courseid:'').'&amp;view=subject">'.get_string('subject_report', 'block_ilp').'</a></h2>';
	}

	if($full == TRUE) {

		$ilpcourses = get_my_ilp_courses($user->id);

    	foreach ($ilpcourses as $course) {
        	print_heading("$course->fullname ($course->shortname)", "left", "3");

        	// who teachers with it ?
	        $context = get_context_instance(CONTEXT_COURSE, $course->id);

			$teachers = get_users_by_capability($context, 'moodle/course:update', 'u.id,u.firstname,u.lastname', 'u.lastname ASC', '', '', '', '', false);

			echo '<table style="text-align:left; margin:5px;" class="generalbox"><tbody>';

			foreach($teachers as $teacher) {
				if (count_records('ilp_student_info_per_teacher','teacher_userid',$teacher->id, 'courseid', $course->id, 'student_userid', $user->id) != 0){

					echo '<tr><th colspan="3">'.fullname($teacher).'<th></tr>';

					if($config->block_ilp_student_info_allow_per_teacher_teacher_text == 1 && $teachertext == TRUE) {
						$text = block_ilp_student_info_get_text($user->id,$teacher->id,$course->id,'teacher','teacher');
						echo '<tr><td>'.get_string('tutor_comment','block_ilp_student_info').':</td></tr><tr><td><div class="block_ilp_student_info_text">'.stripslashes($text->text).'</div></td>';

						if($teacher->id == $USER->id or $access_isgod) {
							echo '<td>'.block_ilp_student_info_edit_button($user->id,$teacher->id,$course->id,'teacher','teacher',$text->id).'</td></tr>' ;
						}else{
							echo '<td></td></tr>';
				  		}
					}

					if($config->block_ilp_student_info_allow_per_teacher_student_text == 1 && $studenttext == TRUE) {
						$text = block_ilp_student_info_get_text($user->id,$teacher->id,$course->id,'teacher','student');
						echo'<tr><td>'.get_string('student_response','block_ilp_student_info').':</td></tr><tr><td><div class="block_ilp_student_info_text">'.stripslashes($text->text).'</div></td>';

						if($access_isuser or $access_isgod) {
							echo '<td>'.block_ilp_student_info_edit_button($user->id,$teacher->id,$course->id,'teacher','student',$text->id).'</td></tr>' ;
						}else{
							echo '<td></td></tr>';
				  		}
					}

					if($config->block_ilp_student_info_allow_per_teacher_shared_text == 1 && $sharedtext == TRUE) {
						$text = block_ilp_student_info_get_text($user->id,$teacher->id,$course->id,'teacher','shared');
						echo '<tr><td>'.get_string('shared_text','block_ilp_student_info').':</td></tr><tr><td><div class="block_ilp_student_info_text">'.stripslashes($text->text).'</div></td>';

						if($access_isuser or $teacher->id == $USER->id or $access_isgod) {
							echo '<td>'.block_ilp_student_info_edit_button($user->id,$teacher->id,$course->id,'teacher','shared',$text->id).'</td></tr>' ;
						}else{
							echo '<td></td></tr>';
				  		}
					}
					echo '<tr><td colspan="3"><hr /></td></tr>';
				}elseif($teacher->id == $USER->id){

					if($config->block_ilp_student_info_allow_per_teacher_teacher_text == 1) {
						$text = block_ilp_student_info_get_text($user->id,$teacher->id,$course->id,'teacher','teacher') ;
						echo '<tr><td>'.get_string('notextteacher','block_ilp').':'.block_ilp_student_info_edit_button($user->id,$teacher->id,$course->id,'teacher','teacher',$text->id).'</td></tr>';
					}

					if($config->block_ilp_student_info_allow_per_teacher_shared_text == 1) {
						$text = block_ilp_student_info_get_text($user->id,$teacher->id,$course->id,'teacher','shared') ;
						echo '<tr><td>'.get_string('notextshared','block_ilp').':'.block_ilp_student_info_edit_button($user->id,$teacher->id,$course->id,'teacher','shared',$text->id).'</td></tr>';
					}
				}
			}
			unset($teachers);
			echo '</tbody></table>';
		}
	}
}

/**
     * Displays the LPR summary to the ILP
     *
     * @param id            userid fed from ILP page (required)
     * @param courseid      courseid fed from ILP page (required)
     * @param full          display a full report or just a title link - for layout and navigation
     * @param title         display default title - turn off to add customised title to template
     * @param icon          display an icon with the deafult title
     * @param sortorder     DESC or ASC - to sort on deadline dates
     * @param limit         limit the number of LPRs shown on the page
*/

function display_ilp_lprs ($id,$courseid,$full=TRUE,$title=TRUE,$icon=TRUE,$sortorder='ASC',$limit=0) {

    global $CFG, $USER;
    include ('access_context.php');

    $module = 'project/ilp';
    $config = get_config($module);

    $user = get_record('user','id',$id);

    // include the LPR databse library
    require_once("{$CFG->dirroot}/blocks/lpr/models/block_lpr_db.php");

	// include the LPR library
    require_once("{$CFG->dirroot}/blocks/lpr/block_lpr_lib.php");

    // include the LPR permissions check
    require_once("{$CFG->dirroot}/blocks/lpr/access_content.php");

    // instantiate the lpr db wrapper
    $lpr_db = new block_lpr_db();

    // get all the LPRs
    if(!empty($config->ilp_lprs_course_specific) && ($courseid > 1)){
        $lprs = $lpr_db->get_lprs($id, $courseid, $sortorder, $limit);
    } else {
        $lprs = $lpr_db->get_lprs($id, null, $sortorder, $limit);
    }

    if($title == TRUE) {
        echo '<h2';
        if($full == FALSE) {
            echo ' style="display:inline"';
        }
        echo '>';

        if ($icon == TRUE) {
            if (file_exists('templates/custom/pix/target.gif')) {
                echo '<img src="'.$CFG->wwwroot.'/blocks/ilp/templates/custom/pix/lpr.gif" alt="LPR" />';
            }else{
                echo '<img src="'.$CFG->wwwroot.'/blocks/ilp/pix/lpr.gif" alt="LPR" />';
            }
        }
        echo '<a href="'.$CFG->wwwroot.'/blocks/lpr/actions/list.php?'.(($courseid > 1)?'course_id='.$courseid.'&amp;' : '').'learner_id='.$id.'&amp;ilp=1">'.(($access_isuser)? get_string("mylprs", "block_lpr"):get_string("modulenameplural", "block_lpr")).'</a></h2>';
    }

    if($full == FALSE) {
        $lpr_count = count_records_sql(
            'SELECT COUNT(*)
            FROM '.$CFG->prefix.'block_lpr
            WHERE learner_id = '.$user->id .
                ((!empty($config->ilp_lprs_course_specific) && !empty($courseid)) ? 'course_id='.$courseid : '')
        );

        echo '<p style="display:inline; margin-left: 5px">'.$lpr_count.' '.get_string('reviews', 'block_lpr').'</p>';
    }

    if($full == TRUE) {
        echo '<div class="block_ilp_lprs">';

        if(!empty($lprs)) {
            foreach($lprs as $lpr) {
                $lecturer = get_record('user','id',$lpr->lecturer_id);
                $course = get_record('course','id',$lpr->course_id);
				$modules = $lpr_db->get_modules($lpr->id, true);
                $indicators = $lpr_db->get_indicators();
                $answers = $lpr_db->get_indicator_answers($lpr->id);
                $atten = $lpr_db->get_attendance($lpr->id);
                $url = urlencode("{$CFG->wwwroot}/blocks/ilp/view.php?id={$id}" . ((!empty($courseid)) ? "&courseid={$courseid}" : ''));

                echo '<div class="ilp_post yui-t4">';
                echo '<div class="bd" role="main">';
                echo '<div class="yui-main">';
                echo '<div class="yui-b">';

                echo '<div class="yui-gd">';
                    echo '<div class="yui-u first">';
                        echo get_string('attendance', 'block_lpr');
                    echo '</div>';
                    echo '<div class="yui-u second">';
                        if(!empty($atten->attendance)) {
                            echo round($atten->attendance, 2).'% ('.map_attendance($atten->attendance).')';
                        }
                    echo '</div>';
                    echo '<div class="yui-u first">';
                        echo get_string('punctuality', 'block_lpr');
                    echo '</div>';
                    echo '<div class="yui-u second">';
                        if(!empty($atten->punctuality)) {
                            echo round($atten->punctuality, 2).'% ('.map_attendance($atten->punctuality).')';
                        }
                    echo '</div>';
                echo '</div>';

                $first = true;
                foreach($indicators as $ind) {
                    echo ($first) ? '<div class="yui-gd">' : '';
                        echo '<div class="yui-u first">';
                            echo $ind->indicator;
                        echo '</div>';
                        echo '<div class="yui-u second">';
                            echo !empty($answers[$ind->id]) ? $answers[$ind->id]->answer : null;
                        echo '</div>';
                    echo (!$first) ? '</div>' : '';
                    $first = ($first) ? false : true;
                }

                echo (!$first) ? '</div>' : '';

                echo '<div class="yui-gd">';
                    echo '<div class="yui-u first">';
                        echo get_string('comments', 'block_lpr');
                    echo '</div>';
                    echo '<div class="yui-u">';
                        echo $lpr->comments;
                    echo '</div>';
                echo '</div>';

                echo '</div>';
                    echo '</div>';
                    echo '<div class="yui-b">';
                    echo '<ul>';
                    echo '<li>'.get_string('lecturer', 'block_lpr').': '.fullname($lecturer);
                    echo '<li>'.get_string('course').': '.$course->shortname.'</li>';
					echo '<li>'.get_string('modules', 'block_lpr').':<ul>';
					if(!empty($modules)) {
						foreach ($modules as $module) {
							echo '<li>'.$module->module_code.' '.$module->module_desc.'</li>';
						}
					}
					echo '</ul></li>';
                    echo '<li>'.get_string('set', 'ilptarget').': '.userdate($lpr->timecreated, get_string('strftimedateshort'));
                    echo '</ul>';

                    echo '<div class="commands">';
                    if($can_view) {
                        echo '<a href="'.$CFG->wwwroot.'/blocks/lpr/actions/view.php?id='.$lpr->id.'&amp;ilp=1" title="'.get_string('view').'"><img alt="'.get_string('view').'" src="'.$CFG->wwwroot.'/theme/conel/pix/t/preview.gif"/> '.get_string('view').'</a> | ';
                    }
                    if($can_write) {
                        echo '<a href="'.$CFG->wwwroot.'/blocks/lpr/actions/edit.php?id='.$lpr->id.'&amp;ilp=1" title="'.get_string('edit').'"><img alt="'.get_string('edit').'" src="'.$CFG->wwwroot.'/theme/conel/pix/t/edit.gif"/> '.get_string('edit').'</a> | ';
                        echo '<a href="'.$CFG->wwwroot.'/blocks/lpr/actions/delete.php?id='.$lpr->id.'&amp;url='.$url.'" title="'.get_string('delete').'"><img alt="'.get_string('delete').'" src="'.$CFG->wwwroot.'/theme/conel/pix/t/delete.gif"/> '.get_string('delete').'</a> | ';
                    }
                    echo '</div>';
                    echo '</div>';
                    echo '</div>';
                echo '</div>';
            }
        }
        echo '</div>';
    }
    //if(!empty($courseid)) { // you can't create an LPR without a learner and a course
    //    echo '<a href="'.$CFG->wwwroot.'/blocks/lpr/actions/create.php?learner_id='.$id.'&amp;course_id='.$courseid.'">'.get_string('createnew', 'block_lpr').'</a>';
    //}
}

function display_ilp_lpr_averages($learner_id, $course_id) {

    global $CFG, $USER, $SITE;
    include ('access_context.php');

    $module = 'project/ilp';
    $config = get_config($module);

	// include the LPR library
    require_once("{$CFG->dirroot}/blocks/lpr/block_lpr_lib.php");

    // include the LPR databse library
    require_once("{$CFG->dirroot}/blocks/lpr/models/block_lpr_db.php");

    // instantiate the lpr db wrapper
    $lpr_db = new block_lpr_db();

    // should we filter by course?
    if(empty($config->ilp_lprs_course_specific) || ($course_id == $SITE->id)){
        $course_id = null;
    }

    // get the averaged data
    //$avg_atten = $lpr_db->get_attendance_avg($learner_id, $course_id);
    $indicators = $lpr_db->get_indicators();
    $avg_answers = $lpr_db->get_indicator_answers_avg($learner_id, $course_id);
    ?>
    <table class="fit">
        <tr>
            <th colspan="2"><?php echo get_string('modulenameplural', 'block_lpr'); ?></th>
        </tr>
        <!--<tr>
            <td>
                <?php /* echo get_string('attendance', 'block_lpr'); ?>
                (<?php echo get_string('avg', 'block_lpr'); ?>)
            </td>
            <td>
                <?php
                if(!empty($avg_atten->attendance)) {
                    echo round($avg_atten->attendance, 2).'% ('.map_attendance($avg_atten->attendance).')';
                } ?>
            </td>
        </tr>
        <tr>
            <td>
                <?php echo get_string('punctuality', 'block_lpr'); ?>
                (<?php echo get_string('avg', 'block_lpr'); ?>)
            </td>
            <td>
                <?php
                if(!empty($avg_atten->punctuality)) {
					echo round($avg_atten->punctuality, 2).'% ('.map_attendance($avg_atten->punctuality).')';
                } */ ?>
            </td>
        </tr>-->
        <?php
        foreach($indicators as $ind) { ?>
            <tr>
                <td>
                    <?php echo $ind->indicator; ?>
                    (<?php echo get_string('avg', 'block_lpr'); ?>)
                </td>
                <td>
                    <?php echo !empty($avg_answers[$ind->id]) ? round($avg_answers[$ind->id]->answer, 2) : null; ?>
                </td>
            </tr>
            <?php
        } ?>
    </table>
    <?php
} ?>