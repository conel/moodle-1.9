<?php  // $Id: staff.php,v 1.16.2.5 2009/04/07 07:46:25 skodak Exp $

    // this is the 'my moodle' page
    require_once('../config.php');
    require_once($CFG->libdir.'/blocklib.php');
    require_once($CFG->dirroot.'/course/lib.php');
    require_once('pagelib.php');
	require_once('../blocks/ilp/block_ilp_lib.php');
	require_once($CFG->dirroot.'/blocks/lpr/models/block_lpr_conel_mis_db.php');
	$conel_db = new block_lpr_conel_mis_db();

    require_login();

    $mymoodlestr = get_string('mymoodle','my');

    if (isguest()) {
        $wwwroot = $CFG->wwwroot.'/login/index.php';
        if (!empty($CFG->loginhttps)) {
            $wwwroot = str_replace('http:','https:', $wwwroot);
        }

        print_header($mymoodlestr);
        notice_yesno(get_string('noguest', 'my').'<br /><br />'.get_string('liketologin'),
                     $wwwroot, $CFG->wwwroot);
        print_footer();
        die();
    }


    $edit        = optional_param('edit', -1, PARAM_BOOL);
    $blockaction = optional_param('blockaction', '', PARAM_ALPHA);

    $PAGE = page_create_instance('my-staff', SITEID);
	// nkowald - We want the page object's id to be set as the logged in user's moodle id - so they can configure their own blocks
	//$PAGE = page_create_instance($USER->id);
	$PAGE->id = $USER->id;
	
    $pageblocks = blocks_setup($PAGE, BLOCKS_PINNED_BOTH);
	
	/*
	echo '<pre>';
	var_dump($pageblocks);
	echo '</pre>';
	*/

	// Make it so user's can NEVER edit their my moodle page
    //$USER->editing = 0;
    if (($edit != -1) and $PAGE->user_allowed_editing()) {
        $USER->editing = $edit;
    }
	
	// defined in /lib/accesslib.php
	$role = get_role_staff_or_student($USER->id);
	// add GET url params if set
	$query_string = (isset($_SERVER['QUERY_STRING']) && $_SERVER['QUERY_STRING'] != '') ? '?' . $_SERVER['QUERY_STRING'] : '';
	
	if ($role == 5) {
		header('location: student.php'. $query_string);
		exit;
	}
	
    $PAGE->print_header($mymoodlestr);

    echo '<table id="layout-table">';
    echo '<tr valign="top">';


    $lt = (empty($THEME->layouttable)) ? array('left', 'middle', 'right') : $THEME->layouttable;
    foreach ($lt as $column) {
        switch ($column) {
            case 'left':
			
			

    $blocks_preferred_width = bounded_number(180, blocks_preferred_width($pageblocks[BLOCK_POS_LEFT]), 210);
	
    if(blocks_have_content($pageblocks, BLOCK_POS_LEFT) || $PAGE->user_is_editing()) {
        echo '<td style="vertical-align: top; width: '.$blocks_preferred_width.'px;" id="left-column">';
        print_container_start();
        blocks_print_group($PAGE, $pageblocks, BLOCK_POS_LEFT);
        print_container_end();
        echo '</td>';
    }
    
            break;
            case 'middle':
    
    echo '<td valign="top" id="middle-column">';
    print_container_start(TRUE);

	// nkowald - 2012-02-10 - Adding banners here
	$query = "SELECT link, img_url FROM mdl_banners WHERE active = 1 AND role = 2 ORDER BY position ASC";
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

<h2 class="headingblock header">Charging for Printing</h2>
<p>Charging for printing will start from Monday the 16th of April. Every learner will be given a free balance of &pound;10.00 per term for printing. Learners can top up at any time using one of the PCounter stations located outside the LRC at Tottenham and Enfield, and at Tottenham Green centre Reception corridor.</p>
<p>Any available free credit will be deducted first. When the free credit is finished, deductions will be made against any top ups that have been applied.</p>
<p><b>Print/Copy charges are as follows:</b></p>
<table style="color:#000;">
	<tr>
		<td>Black and white A4</td><td>5p</td>
	</tr>
	<tr>
		<td>Colour A4</td><td>25p</td>
	</tr>
	<tr>
		<td>Black and white A3</td><td>10p</td>
	</tr>
	<tr>
		<td>Colour A3</td><td>50p</td>
	</tr>
	<tr>
		<td colspan="2">Double sided is double the cost above.</td>
</tr>
</table>

<p>All teaching staff are reminded to inform learners to top up their credit if they cannot print because they have run out of credit.</p>

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
	<br class="clear_both" />
<?php
    } else {
		echo '
    <div id="home_image_holder">
		<noscript>
		<img src="'.$CFG->themewww.'/'. current_theme(). '/pix/home-image1.jpg" id="welcome_image" alt="Welcome to the E-ZONE at the College of Haringey, Enfield and North East London" height="269" width="440" />
		</noscript>
	</div>';
	}
	
	if (isadmin()) {
        echo '<p style="text-align:right;"><a href="'.$CFG->wwwroot.'/banners/index.php?role=2">Edit Banners</a></p>';
    }

?>
<h2 class="headingblock header">Staff Links</h2>
    <table id="staff_links" border="0" cellspacing="0" cellpadding="0">
        <tr>
            <td><a href="http://ebs4agent.conel.ac.uk/" target="_blank"><img src="<?php echo $CFG->wwwroot; ?>/theme/standard/pix/staff/icon-ebs4.png" width="128" height="80" alt="EBS4" /><br />ebs4 Agent</a></td>
            <td><a href="https://clg.conel.ac.uk/" target="_blank"><img src="<?php echo $CFG->wwwroot; ?>/theme/standard/pix/staff/icon-connect.png" width="128" height="80" alt="Connect" /><br />Connect</a></td>
            <td><a href="https://clg.conel.ac.uk/email" target="_blank"><img src="<?php echo $CFG->wwwroot; ?>/theme/standard/pix/staff/icon-email.png" width="129" height="80" alt="Email" /><br />Email</a></td>
            <td><a href="http://rm.conel.ac.uk/index.asp" target="_blank"><img src="<?php echo $CFG->wwwroot; ?>/theme/standard/pix/staff/icon-timetabling.png" width="117" height="80" alt="Timetabling" /><br />Timetabling</a></td>
        </tr>
        <tr>
            <td><a href="http://www.google.co.uk/" target="_blank"><img src="<?php echo $CFG->wwwroot; ?>/theme/standard/pix/staff/icon-google.png" width="128" height="74" alt="Google" /><br />Google</a></td>
            <td><a href="http://www.conel.ac.uk/" target="_blank"><img src="<?php echo $CFG->wwwroot; ?>/theme/standard/pix/staff/icon-conel.png" width="120" height="74" alt="College Website" /><br />College Website</a></td>
            <td><a href="/course/category.php?id=51" target="_blank"><img src="<?php echo $CFG->wwwroot; ?>/theme/standard/pix/staff/icon-good-teaching.png" width="129" height="74" alt="Good Teaching and Learning" /><br />Good Teaching<br /> &amp; Learning</a></td>
            <td><a href="/course/category.php?id=16" target="_blank"><img src="<?php echo $CFG->wwwroot; ?>/theme/standard/pix/staff/icon-staff-training2.png" width="117" height="74" alt="Staff Training Tutorials" /><br />Staff Training Tutorials</a></td>
        </tr>
    </table>
<?php

    // The main overview in the middle of the page
	/*
    $courses_limit = 21;
    if (!empty($CFG->mycoursesperpage)) {
        $courses_limit = $CFG->mycoursesperpage;
    }
    $courses = get_my_courses($USER->id, 'visible DESC,sortorder ASC', '*', false, $courses_limit);
    $site = get_site();
    $course = $site; //just in case we need the old global $course hack

    if (array_key_exists($site->id,$courses)) {
        unset($courses[$site->id]);
    }

    foreach ($courses as $c) {
        if (isset($USER->lastcourseaccess[$c->id])) {
            $courses[$c->id]->lastaccess = $USER->lastcourseaccess[$c->id];
        } else {
            $courses[$c->id]->lastaccess = 0;
        }
    }
    
    if (empty($courses)) {
        print_simple_box(get_string('nocourses','my'),'center');
    } else {
        print_overview($courses);
    }
    
    // if more than 20 courses
    if (count($courses) > 20) {
        echo '<br />...';  
    }
	*/
    
    print_container_end();
    echo '</td>';
    
            break;
            case 'right':
            
    $blocks_preferred_width = bounded_number(180, blocks_preferred_width($pageblocks[BLOCK_POS_RIGHT]), 210);

    if (blocks_have_content($pageblocks, BLOCK_POS_RIGHT) || $PAGE->user_is_editing()) {
        echo '<td style="vertical-align: top; width: '.$blocks_preferred_width.'px;" id="right-column">';
        print_container_start();
        blocks_print_group($PAGE, $pageblocks, BLOCK_POS_RIGHT);
        print_container_end();
        echo '</td>';
    }
            break;
        }
    }

    /// Finish the page
    echo '</tr></table>';

    print_footer();

?>
