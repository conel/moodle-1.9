<?php  //$Id: pagelib.php,v 1.11.2.3 2008/09/15 11:29:24 mudrd8mz Exp $

require_once($CFG->libdir.'/pagelib.php');
define('PAGE_MY_MOODLE',   			'my-index');
define('PAGE_MY_MOODLE_STAFF',   	'my-staff');
define('PAGE_MY_MOODLE_STUDENT',   	'my-student');
define('MY_MOODLE_FORMAT', 'my'); //doing this so we don't run into problems with applicable formats.

// define MOODLE_PAGE
switch($_SERVER['SCRIPT_NAME']) {
	case '/my/index.php':
	define('MOODLE_PAGE', PAGE_MY_MOODLE);
	break;
	
	case '/my/staff.php':
	define('MOODLE_PAGE', PAGE_MY_MOODLE_STAFF);
	break;
	
	case '/my/student.php':
	define('MOODLE_PAGE', PAGE_MY_MOODLE_STUDENT);
	break;
	
	case '/admin/stickyblocks.php':
	$pt_val = (isset($_GET['pt']) && $_GET['pt'] != '') ? $_GET['pt'] : PAGE_MY_MOODLE;
	define('MOODLE_PAGE', $pt_val);
	break;
	
	default:
	define('MOODLE_PAGE', PAGE_MY_MOODLE);
}

class page_my_moodle extends page_base {
		
    function get_type() {
		return MOODLE_PAGE;
    }

    function user_allowed_editing() {
        page_id_and_class($id,$class);
		// nkowald - 2010-09-28 - Users were able to edit blocks - disallow!
		
        if ($id == MOODLE_PAGE) {
            return true;
        } else if (has_capability('moodle/my:manageblocks', get_context_instance(CONTEXT_SYSTEM)) && defined('ADMIN_STICKYBLOCKS')) {
            return true;
        } else {
			return false;
		}
    }

    function user_is_editing() {
        global $USER;
        if (has_capability('moodle/my:manageblocks', get_context_instance(CONTEXT_SYSTEM)) && defined('ADMIN_STICKYBLOCKS')) {
            return true;
        }
        return (!empty($USER->editing));
    }

    function print_header($title) {

        global $USER, $CFG;

        $replacements = array(
                              '%fullname%' => get_string('mymoodle','my')
        );
        foreach($replacements as $search => $replace) {
            $title = str_replace($search, $replace, $title);
        }

        $site = get_site();

		// nkowald - 2010-09-28 - Don't want any Moodle user to edit their My Moodle pages
        //$button = '';
		$button = update_mymoodle_icon($USER->id);
        $nav = get_string('mymoodle','my');
        $header = $site->shortname.': '.$nav;
        $navlinks = array(array('name' => $nav, 'link' => '', 'type' => 'misc'));
        $navigation = build_navigation($navlinks);
        
        $loggedinas = user_login_string($site);

        if (empty($CFG->langmenu)) {
            $langmenu = '';
        } else {
            $currlang = current_language();
            $langs = get_list_of_languages();
            $langlabel = get_accesshide(get_string('language'));
            $langmenu = popup_form($CFG->wwwroot .'/my/index.php?lang=', $langs, 'chooselang', $currlang, '', '', '', true, 'self', $langlabel);
        }

        print_header($title, $header,$navigation,'','',true, $button, $loggedinas.$langmenu);

    }
    
    function url_get_path() {
        global $CFG;
        page_id_and_class($id,$class);
        if ($id == MOODLE_PAGE) {
			if (MOODLE_PAGE == 'my-student') {
				return $CFG->wwwroot.'/my/student.php';
			} else if (MOODLE_PAGE == 'my-staff') {
				return $CFG->wwwroot.'/my/staff.php';
			} else {
				return $CFG->wwwroot.'/my/index.php';
			}
        } elseif (defined('ADMIN_STICKYBLOCKS')){
            return $CFG->wwwroot.'/'.$CFG->admin.'/stickyblocks.php';
        }
    }

    function url_get_parameters() {
        if (defined('ADMIN_STICKYBLOCKS')) {
            return array('pt' => ADMIN_STICKYBLOCKS);
        } else {
            return array();
        }
    }
       
    function blocks_default_position() {
        return BLOCK_POS_LEFT;
    }

    function blocks_get_positions() {
        return array(BLOCK_POS_LEFT, BLOCK_POS_RIGHT);
    }

    function blocks_move_position(&$instance, $move) {
        if($instance->position == BLOCK_POS_LEFT && $move == BLOCK_MOVE_RIGHT) {
            return BLOCK_POS_RIGHT;
        } else if ($instance->position == BLOCK_POS_RIGHT && $move == BLOCK_MOVE_LEFT) {
            return BLOCK_POS_LEFT;
        }
        return $instance->position;
    }

    function get_format_name() {
        return MY_MOODLE_FORMAT;
    }
	
}

page_map_class(MOODLE_PAGE, 'page_my_moodle');

?>