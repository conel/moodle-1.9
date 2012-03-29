<?php // $Id: delete.php,v 1.29.2.1 2008-04-02 06:09:59 dongsheng Exp $
      // Admin-only code to delete a course utterly

    require_once('../../config.php');
	global $CFG;
	print_header($SITE->fullname, $SITE->fullname, 'home', '',
                 '', true, '', user_login_string($SITE));

    require_login();
require_capability('moodle/site:config', get_context_instance(CONTEXT_SYSTEM));

	$result = get_records('course', 'category', 64);
	
	while (!$result->EOF) {

		foreach ($result as $course) {
			delete_course($course->id)
    	      	fix_course_sortorder(); //update course count in catagories
			echo 'deleted:'.$course->id.'<br />'.$course->shortname;
		}

            $result->MoveNext(); 

        }


?>