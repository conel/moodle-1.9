<?php  // $Id: index.php,v 1.16.2.5 2009/04/07 07:46:25 skodak Exp $

    // this is the 'my moodle' page

    require_once('../config.php');
    require_once($CFG->libdir.'/blocklib.php');
    require_once($CFG->dirroot.'/course/lib.php');
    require_once('pagelib.php');
	require_once('../blocks/ilp/block_ilp_lib.php');
	//require_once($CFG->dirroot.'/blocks/lpr/models/block_lpr_conel_mis_db.php');
	//$conel_db = new block_lpr_conel_mis_db();
    
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

    $PAGE = page_create_instance('my-student',SITEID);
	// nkowald - We want the page object's id to be set as the logged in user's moodle id - so they can configure their own blocks
	//$PAGE = page_create_instance($USER->id);
	$PAGE->id = $USER->id;
	
    $pageblocks = blocks_setup($PAGE, BLOCKS_PINNED_BOTH);
	
	//echo '<pre>';
	//var_dump($pageblocks);
	//echo '</pre>';

	// Make it so user's can NEVER edit their my moodle page
    //$USER->editing = 0;
	if (($edit != -1) and $PAGE->user_allowed_editing()) {
        $USER->editing = $edit;
    }
	
	// defined in /lib/accesslib.php
	$role = get_role_staff_or_student($USER->id);
	// add GET url params if set
	$query_string = (isset($_SERVER['QUERY_STRING']) && $_SERVER['QUERY_STRING'] != '') ? '?' . $_SERVER['QUERY_STRING'] : '';
	
	if ($role == 3) {
		header('location: staff.php' . $query_string);
		exit;
	}

    $PAGE->print_header($mymoodlestr);
	
	echo '<style type="text/css">
	.userpicture, .picture user, .picture teacher  {
		height:100px;
		width:100px;
	}
	img.userpicture, img.grouppicture, #message-index img.userpicture {
		background: url(../theme/conel/styles_lib/pix/shadow_100.png) no-repeat scroll right bottom !important;
	}
	#print_to_pdf {
		display:none;
	}
	#student_headings {
		text-align:center;
	}
	#student_headings h2, #student_headings h3 {
		font-size:1.5em;
		color:#464646;
		margin-top:8px;
		text-transform:none;
	}
	#student_headings h2 span, #student_headings h3 span {
	}
	#your_progress div.generalbox {
		padding:8px;
		border-radius: 8px;
	}
	</style>';

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

			/*
			echo '<div id="student_headings">';
			echo '<h2>Hello <span>'.$USER->firstname.'</span></h2>';
			echo '<h3>Welcome to your Learning</h3>';
			echo '<br />';
			echo '</div>';
			*/
			
			// nkowald - 2012-02-10 - Adding banners here
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
<h2 class="headingblock header">Password Reset Software</h2>
<p>To make it easier for you, teachers now have the ability to reset your password. Passwords can also be reset in the following areas:</p>
<ul>
	<li>Reception desks at Enfield and Tottenham Centres</li>
	<li>All library areas</li>
	<li>E-learning team</li>
	<li>Teaching PCs in IT suites</li>
</ul>

<p>If you have any IT related issues regarding you network account please contact ICT services.</p>

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
    }
			
			block_ilp_report_mm_student($USER->id, SITEID);
			
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
