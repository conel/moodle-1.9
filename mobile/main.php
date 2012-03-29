<?php
	/*
	 **************************************************************************
	 * Visit us at http://www.mpage.hk *
	 * The Friendly Open mPage for iPhone/iPod Touch *
	 * Visit us at http://www.mbooks.hk *
	 * The Friendly Open mBooks for iPad *
	 **************************************************************************
	 **************************************************************************
	 * NOTICE OF COPYRIGHT *
	 * *
	 * Copyright (C) 2010 MassMedia.hk *
	 * *
	 * This plugin is free; you can redistribute it and/or modify *
	 * it under the terms of the GNU General Public License as *
	 * published by the Free Software Foundation; either version*
	 * 2 of the License, or (at your option) any later version. *
	 * *
	 * This program is distributed in the hope that it will be useful, *
	 * but WITHOUT ANY WARRANTY; without even the implied warranty of *
	 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the *
	 * GNU General Public License for more details: *
	 * *
	 * http://www.gnu.org/copyleft/gpl.html *
	 * *
	 * *
	 * *
	 **************************************************************************
	 */
	
	require_once('../config.php');
    require_once($CFG->dirroot .'/course/lib.php');
    require_once($CFG->dirroot .'/lib/blocklib.php');
    require_once($CFG->dirroot .'/lib/moodlelib.php');
    require_once($CFG->dirroot .'/lib/pagelib.php');
    require_once($CFG->dirroot .'/mod/forum/lib.php');
    require_once($CFG->dirroot .'/mod/glossary/lib.php');
    require_once($CFG->dirroot .'/mod/assignment/lib.php');
    require_once($CFG->dirroot .'/mod/assignment/type/uploadsingle/assignment.class.php');
    require_once('lib.php');
    
    $update_all = isset($_GET['all_data']);
    
    $server_files_version = 1;
	
	$json_output = array();
	
	if(isset($_POST['username']) && isset($_POST['password'])) {
		$userdata = FALSE;
		$userdata = authenticate_user_login($_POST['username'], $_POST['password']);
		if($userdata) {		
			require_once($CFG->dirroot .'/lib/moodlelib.php');
			set_moodle_cookie($USER->username);
			if(function_exists(complete_user_login)) {
				complete_user_login($userdata);
			}
			else {
				// For old Moodle Servers
				$USER = $userdata;
				set_login_session_preferences();
				if(function_exists(load_all_capabilities)) {
					load_all_capabilities();
				}
			}
			
		}
	}
	
	if (isset($_GET['version']) || $update_all) {
		$json_output["version"] = $server_files_version;
	}
	
	if (isset($_GET['admin'])) {
		//if(is_siteadmin($USER->id)) {
		require_once($CFG->libdir.'/adminlib.php');
		
		if(isset($_POST['s__lang'])) {
			admin_write_settings((object)array("s__lang" => optional_param('s__lang')));
		}
		else if(isset($_POST['s__fullname'])) {
			admin_write_settings((object)array("s__fullname" => optional_param('s__fullname')));
		}
		else if(isset($_POST['s__shortname'])) {
			admin_write_settings((object)array("s__shortname" => optional_param('s__shortname')));
		}
		else if(isset($_POST['s__summary'])) {
			admin_write_settings((object)array("s__summary" => optional_param('s__summary')));
		}
		//}
	}
	
	if (isset($_GET['server_info']) || $update_all) {
		
		$register_users_enabled = TRUE;
		
		if((int)$CFG->version < 2007092000) {
			if($CFG->auth == "none") {
				$register_users_enabled = FALSE;
			}
			else if($CFG->auth != "email") {
				if (!function_exists('auth_user_login')) {
                    require_once("../auth/$CFG->auth/lib.php");
                }
                if (empty($CFG->auth_user_create) || !function_exists('auth_user_create')){
					$register_users_enabled = FALSE;
                }
			}
		}
		else if (empty($CFG->registerauth)) {
     		$register_users_enabled = FALSE;
    	}
    	else {
	    	$authplugin = get_auth_plugin($CFG->registerauth);
			
			if($authplugin) {
				if (!$authplugin->can_signup()) {
					$register_users_enabled = FALSE;
	    		}
			}
			else {
				$register_users_enabled = TRUE;
			}
			
	    	
    	}
    	
    	
		
		$json_output["version"] = $server_files_version;
		$json_output["server_language"] = $CFG->lang;
		$json_output["language_list"] = get_list_of_languages();
		$json_output["user_registration_enabled"] = $register_users_enabled;
		$json_output["guest_login_enabled"] = !empty($CFG->guestloginbutton);
		$json_output["https_login_required"] = $CFG->loginhttps;
		
		$json_output["sitefullname"] = $SITE->fullname;
		$json_output["siteshortname"] = $SITE->shortname;
		$json_output["sitesummary"] = $SITE->summary;
		$frontpagelayout = $CFG->frontpageloggedin;
		
		
		foreach (explode(',',$frontpagelayout) as $v) {
			switch($v) {
				case FRONTPAGENEWS:
					$json_output["frontpage_news"] = TRUE;
					break;
					
				case FRONTPAGECOURSELIST:
					$json_output["frontpage_courselist"] = TRUE;
					break;
					
				case FRONTPAGECATEGORYNAMES:
					$json_output["frontpage_categorylist"] = TRUE;
					break;
					
				case FRONTPAGECATEGORYCOMBO:
					$json_output["frontpage_categorycombo"] = TRUE;
					break;
			}
		}
		
		if(!$json_output["frontpage_news"]) {
			$json_output["frontpage_news"] = FALSE;
		}
		if(!$json_output["frontpage_courselist"]) {
			$json_output["frontpage_courselist"] = FALSE;
		}
		if(!$json_output["frontpage_categorylist"]) {
			$json_output["frontpage_categorylist"] = FALSE;
		}
		if(!$json_output["frontpage_categorycombo"]) {
			$json_output["frontpage_categorycombo"] = FALSE;
		}
		
		//if(isset($DB)) {
		// Moodle 2
		
		//$context = get_context_instance(CONTEXT_COURSE, $SITE->id);
		
		//var_dump($context);
		//$block_instances = $DB->get_records('block_instances', array('pagetypepattern' => 'site-index'));
		//$block_instances = $DB->get_records('block_instances', array('pagetypepattern' => 'site-index'));
		
		/*
		 $json_output["site_blocks"] = array_for_blocks($blocks);
		 foreach ($blocks as $block) {
		 if($block->blockname == "site_main_menu") {
		 $json_output["main_menu_visible"] = $block;
		 break;
		 }
		 }
		 */
		
		//var_dump($DB->get_recordset_sql("SELECT id, blockname FROM $DB->prefix_block_instances WHERE pagetypepattern = 'site-index'"));
		
		//}
		//else {
		
		$block_types = blocks_get_record();
		
		$blocks = get_records_select('block_instance', "pageid = '1' AND pagetype = 'course-view'");
		$pinned_blocks = get_records_select('block_pinned', "pagetype = 'course-view'");
		
		foreach ($pinned_blocks as $pinned_block) {
			$blocks[] = $pinned_block;
		}
		
		$json_output["site_blocks"] = array_for_blocks($blocks);
		
		$site_menu_id = NULL;
		foreach ($block_types as $block_type) {
			if($block_type->name == "site_main_menu") {
				$site_menu_id = $block_type->id;
			}
		}
		
		$main_menu_block = get_records_select('block_instance', "pageid = '1' AND pagetype = 'course-view' AND blockid = '" . $site_menu_id . "'", 'position, weight');
		$main_menu_block = $main_menu_block[1];
		
		if($main_menu_block->visible) {
			$json_output["main_menu_visible"] = TRUE;
		}
		else {
			$json_output["main_menu_visible"] = FALSE;
		}
		//}
		
		
		
		$site_sections = get_all_sections(1);
		$sections_array = array();
		
		$json_output["site_overview_visible"] = ($SITE->numsections == "1");
		
		//$modinfo =& get_fast_modinfo($SITE);
		get_all_mods($SITE->id, $mods, $modnames, $modnamesplural, $modnamesused);
		
		foreach ($site_sections as $section) {
			
			$show_section = FALSE;
			
			$show_section = ($section->visible && ($section->section !== 0 || $json_output["main_menu_visible"]) && ($section->section == 0 || $json_output["site_overview_visible"]));
			
			if($show_section) {
				$new_section = array();
				
				$new_section["id"] = $section->id;
				$new_section["summary"] = $section->summary;
				$new_section["sequence"] = $section->sequence;
				$new_section["section"] = $section->section;
				$new_section["visible"] = $section->visible;
				
				$section_modules = array();
				foreach ($mods as $mod) {
					if($mod->section == $section->id) {
						$section_modules[] = $mod;
					}
				}
				$new_section["modules"] = array_for_modules($section_modules);
				
				$sections_array[] = $new_section;
			}
		}
		
		$json_output["site_sections"] = $sections_array;
		
		if($CFG->rolesactive) {
			$json_output["site_admin"] = has_capability('moodle/site:config', get_context_instance(CONTEXT_SYSTEM));
		}
		else {
			$json_output["site_admin"] = isadmin();
		}
		
		$json_output["auto_login_guests"] = $CFG->autologinguests == 1;
		
	}
	
	if (isset($_GET['check_user']) || ($update_all && isset($_POST['username']) && isset($_POST['password']))) {
		$json_output["login_valid"] = (authenticate_user_login($_POST['username'], $_POST['password']) != FALSE);
	}
	
	if(isset($_GET['course_categories']) || $update_all) {
		include_once($CFG->dirroot . '/course/lib.php');
		$categories = array();
		$given_categories = get_categories();
		foreach($given_categories as $i => $each_category) {
			
			$is_admin = FALSE;
			if($CFG->rolesactive) {
				
			} else {
				$is_admin = isadmin();
			}
			
			$show_category = $each_category->visible or $is_admin;
			
			if($each_category->visible) {
				$new_category = array();
				$new_category["id"] = $each_category->id;
				$new_category["name"] = $each_category->name;
				$new_category["description"] = $each_category->description;
				$new_category["parent"] = $each_category->parent;
				$new_category["course_count"] = $each_category->coursecount;
				$new_category["depth"] = $each_category->depth;
				$categories[] = $new_category;
			}
		}
		$json_output["course_categories"] = $categories;
	}
	
	if(isset($_GET['course_list']) || $update_all) {
		
		$course_list = array();
		
		include_once('../lib/datalib.php');
		
		$courses = get_courses("all","c.sortorder ASC", "c.id, c.fullname, c.shortname, c.summary, c.category, c.format, c.startdate, c.enrollable, c.enrolstartdate, c.enrolenddate, c.visible, c.password, c.guest, c.cost, c.currency");
		
		foreach ($courses as $course) {
			
			$is_admin = FALSE;
			if($CFG->rolesactive) {
				
			} else {
				$is_admin = isadmin();
			}
			
			$show_course = $course->visible or $is_admin;
			
			
			if($course->visible) {
				
				if(function_exists(course_setup)) {
					// Doesn't exist in older versions of Moodle.
					course_setup($course->id);
				}
				
				$new_course = array();
				$new_course["id"] = $course->id;
				$new_course["fullname"] = $course->fullname;
				$new_course["shortname"] = $course->shortname;
				$new_course["summary"] = $course->summary;
				$new_course["category"] = $course->category;
				$new_course["format"] = $course->format;
				$new_course["startdate"] = $course->startdate;
				$new_course["enrollable"] = $course->enrollable;
				$new_course["enrolstartdate"] = $course->enrolstartdate;
				$new_course["enrolenddate"] = $course->enrolenddate;
				$new_course["teacher"] = $course->teacher;
				$new_course["teachers"] = $course->teachers;
				$new_course["student"] = $course->student;
				$new_course["students"] = $course->students;
				$new_course["enrollmentkey"] = !empty($course->password);
				$new_course["numsections"] = $course->numsections;
				$new_course["marker"] = $course->marker;
				$new_course["cost"] = $course->cost;
				$new_course["currency"] = $course->currency;
 				$new_course["allows_guests_without_key"] = $course->guest == 1;
				$new_course["allows_guests_with_key"] = $course->guest == 2;
				
				$res = mysql_query("SELECT u.firstname, u.lastname
								   FROM mdl_user u, mdl_role_assignments r, mdl_context cx, mdl_course c
								   WHERE u.id = r.userid
								   AND r.contextid = cx.id
								   AND cx.instanceid = c.id
								   AND r.roleid =3
								   AND cx.contextlevel =50
								   AND c.id = ".$course->id);
				while($row = mysql_fetch_assoc($res))
					$new_course["teacher_name"] .= $row['firstname'].' '.$row['lastname'].', ';
				
				$new_course["teacher_name"] = substr($new_course["teacher_name"], 0, -2);
				
				$course_list[] = $new_course;
			}
		}
		
		$json_output["course_list"] = $course_list;
		
	}
	
	if(isset($_GET['enrol'])) {
		/*
		 if(isset($DB)) {
		 $courses = $DB->get_records('course', array('id' => $_GET['enrol']));
		 $course = $courses[$_GET['enrol']];
		 }
		 else {
		 
		 }
		 */
		$course = get_record('course', 'id', optional_param('enrol'));
		
		if(!$course->password || ($_POST['enrolmentKey'] == $course->password)) {
			
			$is_guest = FALSE;
			if(function_exists(isguestuser)) {
				if(isguestuser()) {
					$is_guest = TRUE;
				}
			}
			else if(function_exists(isguest)) {
				if(isguest()) {
					$is_guest = TRUE;
				}
			}
			
			if($is_guest) {
				$json_output["enrol"] = true;
			}
			else {
				require_once("$CFG->dirroot/enrol/manual/enrol.php");
				
				if(function_exists(enrol_into_course)) {
					if (enrol_into_course($course, $USER, 'manual')) {
			            unset($USER->mycourses);
			            $json_output["enrol"] = true;
		        	}
		        	else {
						$json_output["errors"][] = "Error enrolling student into course.";
					}
				} else {
					// For older versions of Moodle.
					$timestart = time();
	                $timeend   = $timestart + $course->enrolperiod;
					
					if(enrol_student($USER->id, $course->id, $timestart, $timeend, 'manual')) {
						$json_output["enrol"] = true;
						unset($USER->mycourses);
					}
					else {
						$json_output["errors"][] = "Error enrolling student into course.";
					}
				}
			}
			
        }
        else {
        	$json_output["errors"][] = "Enrolment key is incorrect.";
        }
		
		
	}
	
	if(isset($_GET['my_courses']) || $update_all) {
		
		if (!empty($USER->id)) {
			// Return my classes.
			
			$course_ids = array();
			$course_permissions_ids = array();
			$courses = get_my_courses($USER->id);
			
			if($courses) {
				foreach ($courses as $course) {
					
					if($CFG->rolesactive) {
						$context = get_context_instance(CONTEXT_COURSE, $course->id);
						
						$role = 6;
						if(has_capability('moodle/legacy:student', $context))
							$role = 5;
						
						if(has_capability('moodle/legacy:teacher', $context))
							$role = 4;	
						
						if(has_capability('moodle/legacy:editingteacher', $context))
							$role = 3;	
						
						if(has_capability('moodle/legacy:coursecreator', $context))
							$role = 2;	
						
						if(has_capability('moodle/legacy:admin', $context))
							$role = 1;	
						
						$course_ids[$course->id] = array("can_edit" => has_capability('moodle/course:manageactivities', $context));
						$course_permissions_ids[$course->id] = $role;
					}
					else {
						
						if($course->id != 1) {
							
							$role = 6;
							if(isstudent($course->id, $USER->id, true))
								$role = 5;
							
							if(isteacher($course->id, $USER->id, true))
								$role = 4;
							
							if(isteacheredit($course->id, $USER->id, true))
								$role = 3;
							
							if(iscreator($course->id, $USER->id, true))
								$role = 2;
							
							if(isadmin($course->id, $USER->id, true))
								$role = 1;	
							
							$course_ids[$course->id] = array("can_edit" => isteacher($course->id, $USER->id, true));
							$course_permissions_ids[$course->id] = $role;
							
						}
					}
				}
			}
			
			/*
			 $courses = get_my_remotecourses();
			 if($courses) {
			 foreach ($courses as $course) {
			 $course_sections = get_all_sections($course->id);
			 $sections_array = array();
			 
			 $course_ids[$course->id] = $sections_array;
			 }
			 }
			 */
			$json_output["my_courses_permissions"] = $course_permissions_ids;
			$json_output["my_courses"] = $course_ids;
		}
	}
	
	if(isset($_GET['course']) && isset($_GET['id'])) {
		/*
		 if(isset($DB)) {
		 $courses = $DB->get_records('course', array('id' => $_GET['id']));
		 $course = $courses[$_GET['id']];
		 }
		 else {
		 */
		$course = get_record('course', 'id', optional_param('id'));
		//}
		
		$new_course = array();
		$new_course["id"] = $course->id;
		$new_course["fullname"] = $course->fullname;
		$new_course["shortname"] = $course->shortname;
		$new_course["summary"] = $course->summary;
		$new_course["category"] = $course->category;
		$new_course["format"] = $course->format;
		$new_course["startdate"] = $course->startdate;
		$new_course["enrollable"] = $course->enrollable;
		$new_course["enrolstartdate"] = $course->enrolstartdate;
		$new_course["enrolenddate"] = $course->enrolenddate;
		$new_course["teacher"] = $course->teacher;
		$new_course["teachers"] = $course->teachers;
		$new_course["student"] = $course->student;
		$new_course["students"] = $course->students;
		$new_course["enrollmentkey"] = !empty($course->password);
		$new_course["numsections"] = $course->numsections;
		$new_course["marker"] = $course->marker;
		$new_course["allows_guests_without_key"] = $course->guest == 1;
		$new_course["allows_guests_with_key"] = $course->guest == 2;
		
		if($CFG->rolesactive) {
			$context = get_context_instance(CONTEXT_COURSE, $course->id);
			$new_course["can_edit"] = has_capability('moodle/course:manageactivities', $context);
		}
		else {
			
			if($course->id != 1) {
				$new_course["can_edit"] = isteacher($course->id, $USER->id, true);
			}
		}
		
		$course_sections = get_all_sections($course->id, 'fullname ASC', 0, 1);
		$sections_array = array();
		
		get_all_mods($course->id, $mods, $modnames, $modnamesplural, $modnamesused);
		
		foreach ($course_sections as $section) {
			
			$show_hidden_sections = FALSE;
			
			if($CFG->rolesactive) {
				$context = get_context_instance(CONTEXT_COURSE, $course->id);
				$show_hidden_sections = has_capability('moodle/course:viewhiddensections', $context);
			}
			else {
				$show_hidden_sections = isteacher($course->id, $USER->id, true);
			}
			
			$showsection = ($section->visible or $show_hidden_sections);
			
			
			$new_section = array();
			
			$new_section[" id"] = $section->id;
			$new_section["sequence"] = $section->sequence;
			$new_section["section"] = $section->section;
			$new_section["visible"] = $section->visible;
			
			if($showsection) {
				$new_section["summary"] = $section->summary;
				$section_modules = array();
				foreach ($mods as $mod) {
					if($mod->section == $section->id or $mod->sectionnum == $section->id) {
						$section_modules[] = $mod;
					}
				}
				$new_section["modules"] = array_for_modules($section_modules);
			}
			
			$sections_array[] = $new_section;
			
		}
		
		$new_course["sections"] = $sections_array;
		
		//if(isset($DB)) {
		//	$blocks = array();
		//	$pinned_blocks = array();
		//}
		//else {
		$blocks = get_records_select('block_instance', "pageid = '$course->id' AND pagetype = 'course-view'");
		$pinned_blocks = get_records_select('block_pinned', "pagetype = 'course-view'");
		//}
		
		foreach ($pinned_blocks as $pinned_block) {
			$blocks[] = $pinned_block;
		}
		$blocks_array = array_for_blocks($blocks);
		$new_course["blocks"] = $blocks_array;
		
		$json_output["course"] = $new_course;
		
		
	}
	
	if(isset($_GET['update_profile']) && isset($USER)) {
		require_once($CFG->dirroot .'/user/editlib.php');
		/*
		 var_dump($USER);
		 exit();
		 */
		if(isset($_POST['country'])) {
			$USER->country = $_POST['country'];
		}
		if(isset($_POST['firstname'])) {
			$USER->firstname = $_POST['firstname'];
		}
		if(isset($_POST['lastname'])) {
			$USER->lastname = $_POST['lastname'];
		}
		if(isset($_POST['city'])) {
			$USER->city = $_POST['city'];
		}
		$json_output["update_profile"] = update_record('user', $USER);
	}
	
	if (isset($_GET['user_info']) || $update_all) {
		
		// Get user info details.
		
		
		if($USER) {
			$json_output["user_id"] = $USER->id;
			$json_output["firstname"] = $USER->firstname;
			$json_output["lastname"] = $USER->lastname;
			$json_output["email"] = $USER->email;
			$json_output["city"] = $USER->city;
			$json_output["country"] = $USER->country;
			$json_output["institution"] = $USER->institution;
			$json_output["department"] = $USER->department;
			$json_output["lang"] = $USER->lang;
			
			// Time Zone will be in "Seconds from GMT"
			
			$user_timezone = intval($USER->timezone);
			$server_timezone = intval($CFG->timezone);
			if($user_timezone == 99) {
				// This is code for "use the server's default time zone."
				if($server_timezone == 99) {
					// This is code for "use the PHP server's default time zone."
					$json_output["user_timezone"] = intval(substr(date("O"), 0, 3))*3600;
				}
				else {
					// The server's default time zone differs from PHP's time zone.
					// This is in "hours from GMT".
					$json_output["user_timezone"] = ($server_timezone*3600);
				}
				
			}
			else {
				// This is in "hours from GMT".
				$json_output["user_timezone"] = ($user_timezone*3600);
			}
			
			$json_output["user_picture"] = $USER->picture;
			
			$access_lib = $CFG->dirroot .'/lib/accesslib.php';
			if(file_exists($access_lib)) {
				require_once($access_lib);
				if(function_exists(is_siteadmin)) {
					$json_output["site_admin"] = is_siteadmin($USER->id);
				}
			}
			
		}
	}
	
	if (isset($_GET['calendar_data'])) {
		$courseshown = $_POST['course'];
		$from_date = $_POST['from'];
		$to_date = $_POST['to'];
		
		if($courseshown == "" or $courseshown == 0 or $courseshown == NULL) {
			$courseshown = 1;
		}		
		calendar_session_vars();
		
		/*
		 if ($courseshown == SITEID) {
		 // Being displayed at site level. This will cause the filter to fall back to auto-detecting
		 // the list of courses it will be grabbing events from.
		 $filtercourse    = NULL;
		 $groupeventsfrom = NULL;
		 $SESSION->cal_courses_shown = calendar_get_default_courses(true);
		 calendar_set_referring_course(0);
		 } else {
		 // Forcibly filter events to include only those from the particular course we are in.
		 $filtercourse    = array($courseshown => $COURSE);
		 $groupeventsfrom = array($courseshown => 1);
		 }
		 */
	    
		calendar_set_referring_course($courseshown);
		
	    // Be VERY careful with the format for default courses arguments!
	    // Correct formatting is [courseid] => 1 to be concise with moodlelib.php functions.
		
	    calendar_set_filters($courses, $group, $user, $filtercourse, $groupeventsfrom, false);
	    
	    $restrictions = '';
	    $restrictions .= 'timestart + timeduration >= ' . $from_date;
	    $restrictions .= ' AND timestart <= ' . $to_date;
	    $restrictions .= ' AND ( (userid = ' . $USER->id . ' AND courseid = 0 AND groupid = 0)';
	    $restrictions .= ' OR (groupid = 0 AND courseid IN (1,' . $courseshown . ')))';
	    $restrictions .= ' AND visible = 1';
	    
	    $events = get_records_select('event', $restrictions, 'timestart');
	    
	    $return_events = array();
	    foreach($events as $event) {
	    	
	    	if(function_exists(calendar_add_event_metadata)) {
	    		calendar_add_event_metadata($event);
	    	}
			
	    	$new_event = array();
	    	$new_event["id"] = $event->id;
	    	$new_event["name"] = $event->name;
	    	$new_event["description"] = $event->description;
	    	//$new_event["format"] = $event->format;
	    	$new_event["modulename"] = $event->modulename;
	    	$new_event["instance"] = $event->instance;
	    	$new_event["eventtype"] = $event->eventtype;
	    	$new_event["timestart"] = $event->timestart;
	    	$new_event["timeduration"] = $event->timeduration;
	    	$new_event["user_id"] = $event->userid;
	    	$new_event["group_id"] = $event->groupid;
	    	$new_event["course_id"] = $event->courseid;
	    	$new_event["visible"] = $event->visible;
	    	$new_event["cmid"] = $event->cmid;
	    	$return_events[] = $new_event;
	    	
	    }
	    
	    $json_output["events"] = $return_events;
	}
	
	if (isset($_GET['calendar_event'])) {
		$existing_id = optional_param('id');
		
		if (!empty($USER->id)) {
			
			if(isset($existing_id)) {
				// Update existing event.
				
				if(isset($_POST['name'])) {
					set_field('event', 'name', optional_param('name'), 'id', $existing_id);
				}
				if(isset($_POST['description'])) {
					set_field('event', 'description', optional_param('description'), 'id', $existing_id);
				}
				if(isset($_POST['timestart'])) {
					set_field('event', 'timestart', optional_param('timestart'), 'id', $existing_id);
				}
				if(isset($_POST['timeduration'])) {
					set_field('event', 'timeduration', optional_param('timeduration'), 'id', $existing_id);
				}
			}
			else {
				// Add new event.
				$form = (object)$_POST;
				$eventid = insert_record('event', $form, true);
				
				$json_output["event_id"] = $eventid;
				
				// Use the event id as the repeatid to link repeat entries together
				if ($form->repeat) {
					$form->repeatid = $form->id = $eventid;
					update_record('event', $form);         // update the row, to set its repeatid
					
					for($i = 1; $i < $form->repeats; $i++) {
						// What's the DST offset for the previous repeat?	
						$form->timestart += WEEKSECS;
						
						/// Get the event id for the log record.
						$eventid = insert_record('event', $form, true);
					}
				}
			}		
			
		}
	}
	
	if(isset($_GET['signup'])) {
		
		$username = $_POST['newusername'];
		$password = $_POST['newpassword'];
		$email = $_POST['email'];
		$email2 = $_POST['email'];
		$firstname = $_POST['firstname'];
		$lastname = $_POST['lastname'];
		$city = $_POST['city'];
		$country = $_POST['country'];
		
		if(isset($username) && isset($password) && isset($email) && isset($email2) && isset($firstname) && isset($lastname) && isset($city) && isset($country)) {
			
			$user_registration = (object)array();
			$user_registration->username = $username;
			$user_registration->password = $password;
			$user_registration->email = $email;
			$user_registration->email2 = $email2;
			$user_registration->firstname = $firstname;
			$user_registration->lastname = $lastname;
			$user_registration->city = $city;
			$user_registration->country = $country;
			$user_registration->submitbutton = "Create my new account";
			$user_registration->confirmed = 0;
			$user_registration->lang = current_language();
			$user_registration->firstaccess = time();
			$user_registration->secret = random_string(15);
			
			
			if(function_exists(get_auth_plugin)) {
				
				$user_registration->mnethostid = $CFG->mnet_localhost_id;
				$user_registration->auth = $CFG->registerauth;
				
				$authplugin = get_auth_plugin($CFG->registerauth);
				$success = $authplugin->user_signup($user_registration, false);
				if($success) {
					$json_output["user_created"] = true;
				}
				else {
					$json_output["errors"][] = "Error creating user.";
				}
			}
			else {
				
				$user_registration->auth = $CFG->auth;
				$plainpass = $user_registration->password;
				$user_registration->password = hash_internal_user_password($plainpass);
				
				if($CFG->auth == "email") {
					if(insert_record("user", $user_registration)) {
						$json_output["user_created"] = true;
					}
					else {
						$json_output["errors"][] = "Error creating user.";
					}
				}
				else {
					require_once("../auth/$CFG->auth/lib.php");
					
					if(function_exists(auth_user_create)) {
						if(auth_user_create($user_registration,$plainpass)) {
							$json_output["user_created"] = true;
						}
						else {
							$json_output["errors"][] = "Error creating user.";
						}
					}
					else {
						$json_output["errors"][] = "Missing user creation function.";
					}
				}
				
			}
			
			
		}
		else {
			$json_output["errors"][] = "All fields are required.";
		}
	}
	
	if(isset($_GET['forgot_password'])) {
		
		$email = $_POST['email'];
		$username = $_POST['forgot_username'];
		
		if (!empty($username)) {
	        $user = get_complete_user_data('username', $username);
	    } else {
			
	        $user = get_complete_user_data('email', $email);
	    }
		
	    if ($user and !empty($user->confirmed)) {
			
			if(function_exists(get_auth_plugin)) {
				$userauth = get_auth_plugin($user->auth);
		        if (has_capability('moodle/user:changeownpassword', $systemcontext, $user->id)) {
		            // send email (make sure mail block is off)
		            $user->emailstop = 0;
		        }
				
		        if ($userauth->can_reset_password() and is_enabled_auth($user->auth)
					and has_capability('moodle/user:changeownpassword', $systemcontext, $user->id)) {
		            // send reset password confirmation
					
		            // set 'secret' string
		            $user->secret = random_string(15);
		            if (!set_field('user', 'secret', $user->secret, 'id', $user->id)) {
		                $json_output["errors"][] = "Error setting user secret string.";
		            }
					
		            if (!send_password_change_confirmation_email($user)) {
		                $json_output["errors"][] = "Error sending password change confirmation email.";
		            }
					
		        } else {
		            if (send_password_change_info($user)) {
		                $json_output["forgot_password"] = true;
		            }
		            else {
		            	$json_output["errors"][] = "Error sending password change confirmation email.";
		            }
		        }
			}
			else {
				
				$authmethod = $user->auth;
	            if (is_internal_auth( $authmethod ) or !empty($CFG->{'auth_'.$authmethod.'_stdchangepassword'})) {
	                // handle internal authentication
	                
	                // set 'secret' string
	                $user->secret = random_string( 15 );
	                if (!set_field('user','secret',$user->secret,'id',$user->id)) {
	                    $json_output["errors"][] = "Error setting user secret string.";
	                }
					
	                // send email (make sure mail block is off)
	                $user->mailstop = 0;
	                if (@send_password_change_confirmation_email($user)) {
	                	$json_output["forgot_password"] = true;
	                }
	                else {
	                    $json_output["errors"][] = "Error sending password change confirmation email.";
	                }
	            }
	            else {
	            	$json_output["errors"][] = "This server does not support mPage's password recovery.";
	            }
				
	    	}
	    } else {
	    	if($username) {
	    		$json_output["errors"][] = "A user with the username \"$username\" was not found.";
	    	} else {
	    		$json_output["errors"][] = "A user with the e-mail address \"$email\" was not found.";
	    	}
	    	
	    }
	    
	}
	
	
	if(isset($_GET['forum'])) {
		/*
		 $module_instances = get_records_select('course_modules', "id = " . $_POST['id'], 'id');
		 $instance = $module_instances[$_POST['id']];
		 */
		$forums = get_records_select('forum', "id = " . optional_param('id'), 'id, type, intro');
		$forum = $forums[$_POST['id']];
		
		$discussions = get_records_select('forum_discussions', "forum = " . $forum->id);
		
		$discussions_array = array();
		foreach ($discussions as $discussion) {
			$original_post = get_record_select('forum_posts', "parent = 0 AND discussion = " . $discussion->id);
			$discussions_array[] = recursive_get_posts($original_post);
		}
		
		$return_array = array();
		$return_array["id"] = $_POST['id'];
		$return_array["discussions"] = $discussions_array;
		$return_array["type"] = $forum->type;
		$return_array["intro"] = $forum->intro;
		
		$json_output["forum"] = $return_array;
	}
	
	
	if(isset($_GET['my_forums'])  || $update_all)
	{
		$courses = get_my_courses($USER->id);
		//$courses = get_my_courses(6);
		$return_array = array();
		
		foreach($courses as $course)
		{
			$id = $course->id;
			
			if($id==1) continue;
			//echo "Course: ".$course->fullname."<br/>";	
			$forums = get_records_select('forum', "course = " . $id, 'id, name');
			
			foreach($forums as $forum)
			{
				//echo "&nbsp;&nbsp;&nbsp;Forum: ".$forum->name."<br/>";	
				
				$forums_array = get_object_vars($forum);
				
				$discussions = get_records_select('forum_discussions', "forum = ".$forum->id." AND course = " . $id, 'id, name, forum, course, firstpost');
				
				foreach($discussions as $diss)
				{
					//echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Discussion: ".$diss->name."<br/>";	
					
					
					$diss_array = get_object_vars($diss);
					
					$posts = get_records_select('forum_posts', "discussion = ".$diss->id, 'id');
					
					$new_posts =  array();
					foreach($posts as $post)
					{
						$forums_array['posts'][] = get_object_vars($post);						
						
					}							
				}
				$return_array[] = $forums_array;	
			}
			
		}
		//echo "COUNT: ".count($return_array);
		$json_output["my_forums"] = $return_array;
	}
	
	if(isset($_GET['my_forums_news'])  || $update_all)
	{
		//$courses = get_my_courses($USER->id);
		//$courses = get_my_courses(6);
		$return_array = array();
		
		//foreach($courses as $course)
		//{
		$id = 1;
		
		//echo "Course: ".$course->fullname."<br/>";	
		$forums = get_records_select('forum', "course = " . $id, 'id, name');
		
		foreach($forums as $forum)
		{
			//echo "&nbsp;&nbsp;&nbsp;Forum: ".$forum->name."<br/>";	
			
			$forums_array = get_object_vars($forum);
			
			$discussions = get_records_select('forum_discussions', "forum = ".$forum->id." AND course = " . $id, 'id, name, forum, course, firstpost');
			
			foreach($discussions as $diss)
			{
				//echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Discussion: ".$diss->name."<br/>";	
				
				
				$diss_array = get_object_vars($diss);
				
				$posts = get_records_select('forum_posts', "discussion = ".$diss->id, 'id');
				
				$new_posts =  array();
				foreach($posts as $post)
				{
					$forums_array['posts'][] = get_object_vars($post);						
					
				}							
			}
			$return_array[] = $forums_array;	
		}
		
		//}
		//echo "COUNT: ".count($return_array);
		$json_output["my_forums_news"] = $return_array;
	}
	
	
	if(isset($_GET['newPost']))
	{
		
		if (!empty($USER->id)) {
			
			if($_GET['discussion_id'] > 0){
				$diss = $_GET['discussion_id'];
			}else{
				$full_diss = get_record_select('forum_discussions', "forum = ".optional_param('forum_id')." AND firstpost = " . optional_param('first_post_id'));
				$diss = $full_diss->id;
			}
			
			$obj = (object) array(
								  'subject' => optional_param('subject'),
								  'name' => optional_param('subject'),
								  'intro' => optional_param('message'),
								  'message' => optional_param('message'),
								  'course' => optional_param('course_id') ? optional_param('course_id') : 1,
								  'forum' => optional_param('forum_id'),
								  'discussion' => $diss,
								  'reply' => optional_param('post_id'),
								  'parent' => optional_param('post_id'),
								  'user_id' => $USER->id,
								  'MAX_FILE_SIZE' => 134217728,
								  'subscribe' => 1,
								  'format' => 0,
								  'mailnow' => 0
								  );
			
			$message = '';
			if($diss > 0)
			{				
				$id = forum_add_new_post($obj, $message);
			}else{
				$id = forum_add_discussion($obj, $message);
			}	
			
		}
		
	}
	
	if(isset($_GET['searchForums']))
	{
		$search_results = array();
		$ref = '';
		if($posts = forum_search_posts(array(optional_param('key')), optional_param('course_id'), 0, 50, $ref))
		{
			foreach($posts as $post)
			{
				$search_results[] = get_object_vars($post);
			}
		}
		
		$json_output['search_forums'] = $search_results;
	}
	
	
	
	if(isset($_GET['database']))
	{
		$results = array();
		
		$sql = mysql_query("SELECT * FROM mdl_data_fields WHERE dataid = '".optional_param('database')."'");
		while($field = mysql_fetch_assoc($sql))
		{
			$sql2 = mysql_query("SELECT * FROM mdl_data_content WHERE fieldid = '".$field['id']."'");
			while($content = mysql_fetch_assoc($sql2))
			{
				switch ($field['type'])
				{
					case 'latlong':
						if($content['content'] != '' && $content['content1'] != '')
							$results[$content['recordid']][$field['name']] = array('lat'=>$content['content'], 'lon'=>$content['content1']);
						break;
					default:
						if($content['content'] != '')
							$results[$content['recordid']][$field['name']] = strip_tags(str_replace("<br />","\n",$content['content']));
						break;
				}
			}
		}
		$new_res = array();
		
		foreach($results as $item)
		$new_res[] = $item;
		
		$json_output['database'] = $new_res;
	}
	
	if(isset($_GET['locations_database']) || $update_all)
	{
		$dbid = 13;
		
		$results = array();
		
		$sql = mysql_query("SELECT * FROM mdl_data_fields WHERE dataid = '".$dbid."'");
		while($field = mysql_fetch_assoc($sql))
		{
			$sql2 = mysql_query("SELECT * FROM mdl_data_content WHERE fieldid = '".$field['id']."'");
			while($content = mysql_fetch_assoc($sql2))
			{
				switch ($field['type'])
				{
					case 'latlong':
						if($content['content'] != '' && $content['content1'] != '')
							$results[$content['recordid']][$field['name']] = array('lat'=>$content['content'], 'lon'=>$content['content1']);
						break;
					default:
						if($content['content'] != '')
							$results[$content['recordid']][$field['name']] = strip_tags(str_replace("<br />","\n",$content['content']));
						break;
				}
			}
		}
		$new_res = array();
		
		foreach($results as $item)
		$new_res[] = $item;
		
		$json_output['locations_database'] = $new_res;
	}
	
	if(isset($_GET['numbers_database']) || $update_all)
	{
		$dbid = 14;
		
		$results = array();
		
		$sql = mysql_query("SELECT * FROM mdl_data_fields WHERE dataid = '".$dbid."'");
		while($field = mysql_fetch_assoc($sql))
		{
			$sql2 = mysql_query("SELECT * FROM mdl_data_content WHERE fieldid = '".$field['id']."'");
			while($content = mysql_fetch_assoc($sql2))
			{
				switch ($field['type'])
				{
					case 'latlong':
						if($content['content'] != '' && $content['content1'] != '')
							$results[$content['recordid']][$field['name']] = array('lat'=>$content['content'], 'lon'=>$content['content1']);
						break;
					default:
						if($content['content'] != '')
							$results[$content['recordid']][$field['name']] = strip_tags(str_replace("<br />","\n",$content['content']));
						break;
				}
			}
		}
		$new_res = array();
		
		foreach($results as $item)
		$new_res[] = $item;
		
		$json_output['numbers_database'] = $new_res;
	}
	
	if(isset($_GET['glossary']))
	{
	
		$gid = optional_param('glossary');
		
		$sql = "SELECT * FROM mdl_glossary WHERE id='".$gid."' LIMIT 1";
		$res = mysql_query($sql) or die(mysql_error());
		$row = mysql_fetch_assoc($res);
		
		$sql2 = "SELECT * FROM mdl_glossary_entries WHERE glossaryid='".$gid."' ORDER BY concept";
		$res2 = mysql_query($sql2) or die(mysql_Error());
		while($row2 = mysql_fetch_assoc($res2))
		{
			$ratings = glossary_get_ratings_mean($row2['id'],$row['scale']);
			$row2['ratings'] = $ratings;
			
			$sql3 = "SELECT * FROM mdl_glossary_comments WHERE entryid='".$row2['id']."' ORDER BY timemodified ASC";
			$res3 = mysql_query($sql3) or die(mysql_query());
			while($row3 = mysql_fetch_assoc($res3))
			{
				$sql4 = "SELECT username FROM mdl_user WHERE id='".$row3['userid']."' LIMIT 1";
				$res4 = mysql_query($sql4) or die(mysql_error());
				$row4 = mysql_fetch_assoc($res4);
				
				$row3['username'] = $row4['username'];
				
				$row2['comments'][] = $row3;
			}
			
			
			$row['entries'][] = $row2;
		}
		$json_output['glossary'] = $row;
	}
	
	
	if(isset($_GET['glossaryComment']))
	{
	
		if (!empty($USER->id)) {
		$commentText = optional_param('comment');
		$entryId = optional_param('entryId');
		
		$sql = "INSERT INTO mdl_glossary_comments SET entryid='".$entryId."', userid='".$USER->id."', entrycomment='".$commentText."', timemodified='".date()."'";
		
		mysql_query($sql) or die(mysql_error());
		
		die('1');
		exit;
	
		}else{
		die('0');
		exit;
		}
	
	}
	
	if(isset($_GET['journal']))
	{
			$jid = optional_param('journal');
			
			$sql = "SELECT * FROM mdl_journal WHERE id='".$jid."'";
			$res = mysql_query($sql) or die(mysql_error());
			$row = mysql_fetch_assoc($res);
			
			$sql2 = "SELECT * FROM mdl_journal_entries WHERE journal='".$jid."'";
			$res2 = mysql_query($sql2) or die(mysql_error());
			while($row2 = mysql_fetch_assoc($res2))
			{
				if(!$row2['entrycomment'] || $row2['entrycomment']==null) $row2['entrycomment'] = "";
				$row['entries'][] = $row2;
			}
			
			$row['intro'] = strip_tags($row['intro']);
			
		$json_output['journal'] = $row;
	}
	
	if(isset($_GET['journalEntry']))
	{
		if (!empty($USER->id)) {
	
			$jid = optional_param('journalEntry');
			$text = optional_param('entrytext');
			
			$sql = "SELECT * FROM mdl_journal_entries WHERE journal='".$jid."' AND userid='".$USER->id."'";
			if(mysql_num_rows(mysql_query($sql)) > 0)
			{
				$entrySql = "UPDATE mdl_journal_entries SET text='".$text."' WHERE journal='".$jid."' AND userid='".$USER->id."'";
				mysql_query($entrySql) or die(mysql_error());
			}else{
				$entrySql = "INSERT INTO mdl_journal_entries SET journal='".$jid."', userid='".$USER->id."', text='".$text."'";
				mysql_query($entrySql) or die(mysql_error());
			}
			die('1');
			exit;
		}else{
		die('0');
		exit;
		}
	
	}
	
	if(isset($_GET['newAssignment']))
	{
		if (!empty($USER->id)) {
		
			$a = new assignment_base();
			$ass = $a->prepare_new_submission($USER->id);
			$ass->assignment = optional_param('assignment_id');
			$ass->data1 = optional_param('message') ? nl2br(optional_param('message')): '';
			$ass->data2 = 'submitted';
			$ass->format=0;
			$ass->timemodified = time();
			$ass->numfiles=1;
			$sql = "SELECT * FROM mdl_assignment_submissions WHERE assignment='".optional_param('assignment_id')."' AND userid='".$USER->id."'";

			if(mysql_num_rows(mysql_query($sql)) > 0)
			{

				$sql = "UPDATE mdl_assignment_submissions SET data1='".optional_param('message')."' WHERE assignment='".optional_param('assignment_id')."' AND userid='".$USER->id."'";
				mysql_query($sql);
			}else{		

				insert_record("assignment_submissions", $ass);
			}

			$id = optional_param('module_id', 0, PARAM_INT);  // Course module ID
    		$a  = optional_param('assignment_id', 0, PARAM_INT);   // Assignment ID
			$cm = get_coursemodule_from_id('assignment', $id);
			$assignment = get_record("assignment", "id", $cm->instance);
			$course = get_record("course", "id", $assignment->course);
			$as = new assignment_uploadsingle($cm->id, $assignment, $cm, $course);
			$as->upload();
		}
	}
	
	if(isset($_GET['getAssignment']))
	{
		if (!empty($USER->id)) {

			$sql = "SELECT * FROM mdl_assignment_submissions WHERE assignment='".optional_param('getAssignment')."' AND userid='".$USER->id."' LIMIT 1";
			$row = mysql_fetch_assoc(mysql_query($sql));
			$row['submissioncomment'] = strip_tags(str_replace(array('<br/>','<br>','<br />'), "\n", $row['submissioncomment']));
			$json_output['assignment'] = $row;
		}
	}
	
	echo json_encode($json_output);
	
	?>