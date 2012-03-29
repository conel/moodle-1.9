<?PHP // $Id: stickyblocks.php,v 1.18.2.2 2008/05/02 04:07:28 dongsheng Exp $

    require_once('../config.php');
    require_once($CFG->dirroot.'/my/pagelib.php');
    require_once($CFG->dirroot.'/lib/pagelib.php');
    require_once($CFG->dirroot.'/lib/blocklib.php');

    $pt  = optional_param('pt', null, PARAM_SAFEDIR); //alhanumeric and -

    $pagetypes = array(PAGE_MY_MOODLE => array('id' => PAGE_MY_MOODLE,
                                              'lib' => '/my/pagelib.php',
                                              'name' => get_string('mymoodle','admin')),
											  
					  PAGE_MY_MOODLE_STAFF => array('id' => PAGE_MY_MOODLE_STAFF,
											  'lib' => '/my/pagelib.php',
											  'name' => 'My Moodle - Staff'),
											  
					  PAGE_MY_MOODLE_STUDENT => array('id' => PAGE_MY_MOODLE_STUDENT,
											  'lib' => '/my/pagelib.php',
											  'name' => 'My Moodle - Student'),
											  
                       PAGE_COURSE_VIEW => array('id' => PAGE_COURSE_VIEW,
                                              'lib' => '/lib/pagelib.php',
                                              'name' => get_string('stickyblockscourseview','admin'))
                       // ... more?
                       );

    // for choose_from_menu
    $options = array();
    foreach ($pagetypes as $p) {
        $options[$p['id']] = $p['name'];
    }

	// nkowald - 2010-09-28 - Added this to make it so that students can't add blocks to my moodle, only admins.
    //require_login(0, false);
	require_login();
	
	require_capability('moodle/site:manageblocks', get_context_instance(CONTEXT_SYSTEM));
	/*
    if (!require_capability('moodle/site:manageblocks', get_context_instance(CONTEXT_SYSTEM))) {
		header('location:/VLE/my/');
		exit;
	}
	*/

    // first thing to do is print the dropdown menu

    $strtitle = get_string('stickyblocks','admin');
    $strheading = get_string('adminhelpstickyblocks');

    if (!empty($pt)) {
		
        require_once($CFG->dirroot.$pagetypes[$pt]['lib']);

        define('ADMIN_STICKYBLOCKS',$pt);
		
		$PAGE = page_create_object($pt, SITEID);
		
		if ($pt == 'my-student') {
			$PAGE->id = 'my-student';
		} else if ($pt == 'my-staff') {
			$PAGE->id = 'my-staff';
		}
		
        $blocks = blocks_setup($PAGE, BLOCKS_PINNED_TRUE);	
        $blocks_preferred_width = bounded_number(180, blocks_preferred_width($blocks[BLOCK_POS_LEFT]), 210);

        $navlinks = array(array('name' => get_string('administration'),
                                'link' => "$CFG->wwwroot/$CFG->admin/index.php",
                                'type' => 'misc'));
        $navlinks[] = array('name' => $strtitle, 'link' => null, 'type' => 'misc');
        $navigation = build_navigation($navlinks);
        print_header($strtitle,$strtitle,$navigation);

        echo '<table border="0" cellpadding="3" cellspacing="0" width="100%" id="layout-table">';
        echo '<tr valign="top">';

        echo '<td valign="top" style="width: '.$blocks_preferred_width.'px;" id="left-column">';
        print_container_start();
		
        blocks_print_group($PAGE, $blocks, BLOCK_POS_LEFT);
        print_container_end();
        echo '</td>';
        echo '<td valign="top" id="middle-column">';
        print_container_start();

    } else {
        require_once($CFG->libdir.'/adminlib.php');
        admin_externalpage_setup('stickyblocks');
        admin_externalpage_print_header();
    }


    print_box_start();
    print_heading($strheading);
    popup_form("$CFG->wwwroot/$CFG->admin/stickyblocks.php?pt=", $options, 'selecttype', $pt, 'choose', '', '', false, 'self', get_string('stickyblockspagetype','admin').': ');
    echo '<p>'.get_string('stickyblocksduplicatenotice','admin').'</p>';
    print_box_end();


    if (!empty($pt)) {
        print_container_end();
        echo '</td>';
        echo '<td valign="top" style="width: '.$blocks_preferred_width.'px;" id="right-column">';
        print_container_start();
        blocks_print_group($PAGE, $blocks, BLOCK_POS_RIGHT);
        print_container_end();
        echo '</td>';
        echo '</tr></table>';
        print_footer();
    } else {
        admin_externalpage_print_footer();
    }

?>
