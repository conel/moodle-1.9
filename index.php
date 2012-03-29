<?php  // $Id: index.php,v 1.201.2.10 2009/04/25 21:18:24 stronk7 Exp $
       // index.php - the front page.

///////////////////////////////////////////////////////////////////////////
//                                                                       //
// NOTICE OF COPYRIGHT                                                   //
//                                                                       //
// Moodle - Modular Object-Oriented Dynamic Learning Environment         //
//          http://moodle.org                                            //
//                                                                       //
// Copyright (C) 1999 onwards  Martin Dougiamas  http://moodle.com       //
//                                                                       //
// This program is free software; you can redistribute it and/or modify  //
// it under the terms of the GNU General Public License as published by  //
// the Free Software Foundation; either version 2 of the License, or     //
// (at your option) any later version.                                   //
//                                                                       //
// This program is distributed in the hope that it will be useful,       //
// but WITHOUT ANY WARRANTY; without even the implied warranty of        //
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the         //
// GNU General Public License for more details:                          //
//                                                                       //
//          http://www.gnu.org/copyleft/gpl.html                         //
//                                                                       //
///////////////////////////////////////////////////////////////////////////


    if (!file_exists('./config.php')) {
        header('Location: install.php');
        die;
    }

    require_once('config.php');
    require_once($CFG->dirroot .'/course/lib.php');
    require_once($CFG->dirroot .'/lib/blocklib.php');

    if (empty($SITE)) {
        redirect($CFG->wwwroot .'/'. $CFG->admin .'/index.php');
    }

    // Bounds for block widths
    // more flexible for theme designers taken from theme config.php
    $lmin = (empty($THEME->block_l_min_width)) ? 100 : $THEME->block_l_min_width;
    $lmax = (empty($THEME->block_l_max_width)) ? 210 : $THEME->block_l_max_width;
    $rmin = (empty($THEME->block_r_min_width)) ? 100 : $THEME->block_r_min_width;
    $rmax = (empty($THEME->block_r_max_width)) ? 210 : $THEME->block_r_max_width;

    define('BLOCK_L_MIN_WIDTH', $lmin);
    define('BLOCK_L_MAX_WIDTH', $lmax);
    define('BLOCK_R_MIN_WIDTH', $rmin);
    define('BLOCK_R_MAX_WIDTH', $rmax);

    // check if major upgrade needed - also present in login/index.php
    if ((int)$CFG->version < 2006101100) { //1.7 or older
        @require_logout();
        redirect("$CFG->wwwroot/$CFG->admin/");
    }
    // Trigger 1.9 accesslib upgrade?
    if ((int)$CFG->version < 2007092000 
        && isset($USER->id) 
        && is_siteadmin($USER->id)) { // this test is expensive, but is only triggered during the upgrade
        redirect("$CFG->wwwroot/$CFG->admin/");
    }

    if ($CFG->forcelogin) {
        require_login();
    } else {
        user_accesstime_log();
    }

    if ($CFG->rolesactive) { // if already using roles system
        if (has_capability('moodle/site:config', get_context_instance(CONTEXT_SYSTEM))) {
            if (moodle_needs_upgrading()) {
                redirect($CFG->wwwroot .'/'. $CFG->admin .'/index.php');
            }
        } else if (!empty($CFG->mymoodleredirect)) {    // Redirect logged-in users to My Moodle overview if required
            if (isloggedin() && $USER->username != 'guest') {
				$home = (isset($_GET['home']) && $_GET['home'] != '') ? $_GET['home'] : '';
				// nkowald - homepage should NOT redirect if viewing calendar
				$home = (strpos($_SERVER['QUERY_STRING'], 'cal_m=') === FALSE) ? $home : 1;
				if ($home != 1) {
					redirect($CFG->wwwroot .'/my/index.php');
				}
            }
        }
    } else { // if upgrading from 1.6 or below
        if (isadmin() && moodle_needs_upgrading()) {
            redirect($CFG->wwwroot .'/'. $CFG->admin .'/index.php');
        }
    }

    if (get_moodle_cookie() == '') {
        set_moodle_cookie('nobody');   // To help search for cookies on login page
    }

    if (!empty($USER->id)) {
        add_to_log(SITEID, 'course', 'view', 'view.php?id='.SITEID, SITEID);
    }

    if (empty($CFG->langmenu)) {
        $langmenu = '';
    } else {
        $currlang = current_language();
        $langs = get_list_of_languages();
        $langlabel = get_accesshide(get_string('language'));
        $langmenu = popup_form($CFG->wwwroot .'/index.php?lang=', $langs, 'chooselang', $currlang, '', '', '', true, 'self', $langlabel);
    }

    $PAGE       = page_create_object(PAGE_COURSE_VIEW, SITEID);
    $pageblocks = blocks_setup($PAGE);
    $editing    = $PAGE->user_is_editing();
    $preferred_width_left  = bounded_number(BLOCK_L_MIN_WIDTH, blocks_preferred_width($pageblocks[BLOCK_POS_LEFT]),
                                            BLOCK_L_MAX_WIDTH);
    $preferred_width_right = bounded_number(BLOCK_R_MIN_WIDTH, blocks_preferred_width($pageblocks[BLOCK_POS_RIGHT]),
                                            BLOCK_R_MAX_WIDTH);
    print_header($SITE->fullname, $SITE->fullname, 'home', '',
                 '<meta name="description" content="'. strip_tags(format_text($SITE->summary, FORMAT_HTML)) .'" />',
                 true, '', user_login_string($SITE).$langmenu);

?>

<table id="layout-table" summary="layout">
  <?php
    $lt = (empty($THEME->layouttable)) ? array('left', 'middle', 'right') : $THEME->layouttable;
    foreach ($lt as $column) {
        switch ($column) {
            case 'left':
				if (blocks_have_content($pageblocks, BLOCK_POS_LEFT) || $editing) {
					// nkowald - 2008-08-26 - Removed inline style from page - now set in styles
					//echo '<td style="width: '.$preferred_width_left.'px;" id="left-column">';
					echo '<td id="left-column">';
					print_container_start();
					blocks_print_group($PAGE, $pageblocks, BLOCK_POS_LEFT);
					print_container_end();
					echo '</td>';
				}
            break;
			
            case 'middle':
    echo '<td id="middle-column">'. skip_main_destination();

	// nkowald - 2012-01-20 - Get Banners from mdl_banners table
    $query = "SELECT link, img_url FROM mdl_banners WHERE active = 1 AND role = 1 ORDER BY position ASC";
    $banners = array();
    $i = 0;
    if ($banns = get_records_sql($query)) {
        foreach ($banns as $banner) {
            $banners[$i]['link'] = $banner->link;
            $banners[$i]['img_url'] = $banner->img_url;
            $i++;
        }
    }

    if (count($banners) > 0) {
	
	echo '<link rel="stylesheet" type="text/css" href="'.$CFG->wwwroot.'/banners/rotator/wt-rotator.css"/>
	<script type="text/javascript" src="'.$CFG->wwwroot.'/banners/rotator/js/jquery.easing.1.3.min.js"></script>
	<script type="text/javascript" src="'.$CFG->wwwroot.'/banners/rotator/js/jquery.wt-rotator.min.js"></script>';
?>

<script type="text/javascript">
    jQuery(document).ready(	
        function() {
            jQuery(".container").wtRotator({
                width:495, height:185, button_width:24, button_height:24, button_margin:5, auto_start:true, delay:6500, play_once:false, transition:"fade",
                transition_speed:800, auto_center:true, easing:"", cpanel_position:"inside", cpanel_align:"BR", timer_align:"top", display_thumbs:true,
                display_dbuttons:true, display_playbutton:true, display_numbers:true, display_timer:true, mouseover_pause:true, cpanel_mouseover:false,
                text_mouseover:false, text_effect:"fade", text_sync:true, tooltip_type:"image", lock_tooltip:true, shuffle:false, block_size:75,
                vert_size:55, horz_size:50, block_delay:25, vstripe_delay:75, hstripe_delay:180			
        });
    });
</script>    

<h2 class="headingblock header">News</h2>

<div class="container">
	<div class="wt-rotator">
    	<div class="screen"><noscript><img src="<?php echo $CFG->wwwroot . $banners[0]['img_url']; ?>" alt="" /></noscript></div>
        <div class="c-panel">
      		<div class="buttons">
            	<div class="prev-btn"></div>
                <div class="play-btn"></div>    
            	<div class="next-btn"></div>               
            </div>
      		<div class="thumbnails">
                <ul>
<?php
    foreach ($banners as $ban) {
        echo "\t<li><a href=\"". $CFG->wwwroot . $ban['img_url']."\" title=\"\"><img src=\"". $CFG->wwwroot . $ban['img_url']."\" /></a>\n";
        if ($ban['link'] != '') {
            echo "\t<a href=\"".$ban['link']."\" target=\"_blank\"></a>";
        }
        echo "</li>\n";
    }
?>
              	</ul>
          </div>     
      </div>
    </div>
<?php
    }
	
    // Only Admins can edit banners
    if (isadmin()) {
        echo '<p style="text-align:right;"><a href="'.$CFG->wwwroot.'/banners/index.php?role=1">Edit Banners</a></p>';
    }
?>

	<link rel="stylesheet" type="text/css" href="<?php echo $CFG->wwwroot; ?>/theme/standard/pix/get-help/styles.css"/>
	<h2 class="headingblock header">Get Help</h2>
	<ul id="get_help">
		<li class="gh1"><a href="<?php echo $CFG->wwwroot; ?>/course/view.php?id=803">Anti-bullying</a></li>
		<li class="gh2"><a href="<?php echo $CFG->wwwroot; ?>/course/view.php?id=21569">Careers</a></li>
		<li class="gh3"><a href="<?php echo $CFG->wwwroot; ?>/course/view.php?id=21333">E-Learning<br />&amp; ICT Support</a></li>
		<li class="gh4"><a href="<?php echo $CFG->wwwroot; ?>/course/view.php?id=18917">E-safety</a></li>
		<li class="gh5"><a href="<?php echo $CFG->wwwroot; ?>/course/view.php?id=21541">Learner Guidance<br />&amp; Policies</a></li>
		<li class="gh6"><a href="<?php echo $CFG->wwwroot; ?>/course/view.php?id=21583">Learner Support</a></li>
		<li class="gh7"><a href="<?php echo $CFG->wwwroot; ?>/course/view.php?id=21584">Mentoring</a></li>
		<li class="gh8"><a href="<?php echo $CFG->wwwroot; ?>/course/view.php?id=21682">Recruit Direct</a></li>
		<li class="gh9"><a href="<?php echo $CFG->wwwroot; ?>/course/view.php?id=18783">Safeguarding</a></li>
		<li class="gh10"><a href="http://conel.thesharpsystem.com/" target="_blank">Student Help Reporting System</a></li>
		<li class="gh11"><a href="<?php echo $CFG->wwwroot; ?>/course/view.php?id=25916">Student Success Stories</a></li>
		<li class="gh12"><a href="<?php echo $CFG->wwwroot; ?>/course/view.php?id=21571">Welfare</a></li>
	</ul>
	<br class="clear_both" />

<?php
	print_container_start();
	
		// nkowald - 2008-08-27 - had to put this bit in here as the editable content editor of the home page does not allow a noscript tag 
		// Added this for users that have JavaScript disabled - so the home page still displays a banner image 
?>
	<!--
	<div id="home_image_holder">
		<noscript>
		<img src="<?php //echo $CFG->themewww .'/'. current_theme() ?>/pix/home-image1.jpg" id="welcome_image" alt="Welcome to the E-ZONE at the College of North East London" height="269" width="440" />
		</noscript>
	</div>
	-->
	<?php
	/// Print Section
    if ($SITE->numsections > 0) {

        if (!$section = get_record('course_sections', 'course', $SITE->id, 'section', 1)) {
            delete_records('course_sections', 'course', $SITE->id, 'section', 1); // Just in case
            $section->course = $SITE->id;
            $section->section = 1;
            $section->summary = '';
            $section->sequence = '';
            $section->visible = 1;
            $section->id = insert_record('course_sections', $section);
        }

        if (!empty($section->sequence) or !empty($section->summary) or $editing) {
            print_box_start('generalbox sitetopic');

            /// If currently moving a file then show the current clipboard
            if (ismoving($SITE->id)) {
                $stractivityclipboard = strip_tags(get_string('activityclipboard', '', addslashes($USER->activitycopyname)));
                echo '<p><font size="2">';
                echo "$stractivityclipboard&nbsp;&nbsp;(<a href=\"course/mod.php?cancelcopy=true&amp;sesskey=$USER->sesskey\">". get_string('cancel') .'</a>)';
                echo '</font></p>';
            }

            $options = NULL;
            $options->noclean = true;
            echo format_text($section->summary, FORMAT_HTML, $options);

            if ($editing) {
                $streditsummary = get_string('editsummary');
                echo "<a title=\"$streditsummary\" ".
                     " href=\"course/editsection.php?id=$section->id\"><img src=\"$CFG->pixpath/t/edit.gif\" ".
                     " class=\"iconsmall\" alt=\"$streditsummary\" /></a><br /><br />";
            }

            get_all_mods($SITE->id, $mods, $modnames, $modnamesplural, $modnamesused);
            print_section($SITE, $section, $mods, $modnamesused, true);

            if ($editing) {
                print_section_add_menus($SITE, $section->section, $modnames);
            }
            print_box_end();
        }
    }

    if (isloggedin() and !isguest() and isset($CFG->frontpageloggedin)) {
        $frontpagelayout = $CFG->frontpageloggedin;
    } else {
        $frontpagelayout = $CFG->frontpage;
    }

    foreach (explode(',',$frontpagelayout) as $v) {
        switch ($v) {     /// Display the main part of the front page.
            case FRONTPAGENEWS:
                if ($SITE->newsitems) { // Print forums only when needed
                    require_once($CFG->dirroot .'/mod/forum/lib.php');

                    if (! $newsforum = forum_get_course_forum($SITE->id, 'news')) {
                        error('Could not find or create a main news forum for the site');
                    }

                    if (!empty($USER->id)) {
                        $SESSION->fromdiscussion = $CFG->wwwroot;
                        $subtext = '';
                        if (forum_is_subscribed($USER->id, $newsforum)) {
                            if (!forum_is_forcesubscribed($newsforum)) {
                                $subtext = get_string('unsubscribe', 'forum');
                            }
                        } else {
                            $subtext = get_string('subscribe', 'forum');
                        }
                        print_heading_block($newsforum->name);
                        echo '<div class="subscribelink"><a href="mod/forum/subscribe.php?id='.$newsforum->id.'">'.$subtext.'</a></div>';
                    } else {
                        print_heading_block($newsforum->name);
                    }

                    forum_print_latest_discussions($SITE, $newsforum, $SITE->newsitems, 'plain', 'p.modified DESC');
                }
            break;

            case FRONTPAGECOURSELIST:

                if (isloggedin() and !has_capability('moodle/site:config', get_context_instance(CONTEXT_SYSTEM)) and !isguest() and empty($CFG->disablemycourses)) {
                    print_heading_block(get_string('mycourses'));
                    print_my_moodle();
                } else if ((!has_capability('moodle/site:config', get_context_instance(CONTEXT_SYSTEM)) and !isguest()) or (count_records('course') <= FRONTPAGECOURSELIMIT)) {
                    // admin should not see list of courses when there are too many of them
                    print_heading_block(get_string('availablecourses'));
                    print_courses(0);
                }
            break;

            case FRONTPAGECATEGORYNAMES:

                print_heading_block(get_string('categories'));
                print_box_start('generalbox categorybox');
                print_whole_category_list(NULL, NULL, NULL, -1, false);
                print_box_end();
                print_course_search('', false, 'short');
            break;

            case FRONTPAGECATEGORYCOMBO:

                print_heading_block(get_string('categories'));
                print_box_start('generalbox categorybox');
                print_whole_category_list(NULL, NULL, NULL, -1, true);
                print_box_end();
                print_course_search('', false, 'short');
            break;

            case FRONTPAGETOPICONLY:    // Do nothing!!  :-)
            break;

        }
        echo '<br />';
    }

    print_container_end();

    echo '</td>';
            break;
            case 'right':
				// The right column
				if (blocks_have_content($pageblocks, BLOCK_POS_RIGHT) || $editing || $PAGE->user_allowed_editing()) {
					// nkowald - 2008-08-26 - Removed inline style from page - now set in styles
					//echo '<td style="width: '.$preferred_width_right.'px;" id="right-column">';
					echo '<td id="right-column">';
					// nkowald - 2008-08-25 - added login box to RHS menu
					echo '<div class="headermenu">'. $GLOBALS['THEME']->menu .'</div>';
					print_container_start();
					if ($PAGE->user_allowed_editing()) {
						echo '<div style="text-align:center">'.update_course_icon($SITE->id).'</div>';
						echo '<br />';
					}
					blocks_print_group($PAGE, $pageblocks, BLOCK_POS_RIGHT);
					print_container_end();
					echo '</td>';
				}
            break;
        }
    }
?>

  </tr>
</table>

<?php
    print_footer('home');     // Please do not modify this line
?>
