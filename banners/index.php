<?php

    require_once('../config.php');
    require_login(); 

    if (!isadmin()) {
        error("Only the administrator can access this page!", $CFG->wwwroot);
    }
	
	$role = optional_param('role', 1, PARAM_INT);

    $title = "Banners";

    $navlinks = array();
    $navlinks[] = array('name' => 'Administration', 'link' => $CFG->wwwroot . '/admin/', 'type' => 'misc');
    $navlinks[] = array('name' => 'Front Page Settings', 'link' => $CFG->wwwroot . '/admin/settings.php?section=frontpagesettings', 'type' => 'misc');
    $navlinks[] = array('name' => 'Front Page', 'link' => $CFG->wwwroot . '/index.php?home=1', 'type' => 'misc');
    $navlinks[] = array('name' => 'Banners', 'link' => '', 'type' => 'misc');
    $navigation = build_navigation($navlinks);
    print_header($title, $title, $navigation, '', '', true, '&nbsp;');

	// Get a list of tables we should be able to export data from
	$query = sprintf("SELECT id, position, link, img_url, active, author FROM mdl_banners WHERE role = %d ORDER BY position ASC", $role);

	$banners = array();
	$banners_exist = FALSE;
	$position = 1;
	$active_banners = 0;
	if ($fpbanners = get_records_sql($query)) {
		$banners_exist = TRUE;
		$c = 0;
        foreach($fpbanners as $ban) {
			$banners[$c]['id']			= $ban->id;
			$banners[$c]['position']	= $ban->position;
			$banners[$c]['link']		= $ban->link;
			$banners[$c]['img_url']		= $ban->img_url;
			$banners[$c]['author']		= $ban->author;
			$banners[$c]['active']		= $ban->active;
			$position					= $ban->position;
			$c++;
			if ($ban->active) $active_banners++;
		}
		$position++;
	}

?>
<link href="<?php echo $CFG->wwwroot; ?>/banners/banners.css" rel="stylesheet" type="text/css" media="screen" />
<link href="<?php echo $CFG->wwwroot; ?>/banners/colorbox/colorbox.css" rel="stylesheet" type="text/css" media="screen" />
<script src="<?php echo $CFG->wwwroot; ?>/banners/colorbox/jquery.colorbox-min.js"></script>
<script type="text/javascript">
jQuery(document).ready(function(){
	//Examples of how to assign the ColorBox event to elements
	jQuery(".add_banner").colorbox({
		onOpen:function() { jQuery('#banner_add_form').fadeIn(); },
		onCleanup:function() { jQuery('#banner_add_form').hide(); },
			width:"600px", 
			inline:true, 
			href:"#banner_add_form"
	});
	jQuery(".delete").click(function(event) {
		event.preventDefault();
		var clicked_link = jQuery(this).attr('href');
		var clicked_no = jQuery(this).find('span');
		var banner_no = clicked_no.attr('class');
		var answer = confirm('Are you sure you want to delete banner #' + banner_no + '?');
		if (answer) {
			// delete
			window.location.href = clicked_link;
			return false;
		}
	});
	jQuery('.edit').colorbox();
});
</script>
</head>
<body> 
<div id="holder">
	<a href="#" class="add_banner"><span class="add">Add Banner</span></a>
    <p><b>Banner size:</b> 495 x 185 pixels<br />

<?php
	if (!$banners_exist) {
		echo '<p>No banners exist</p>';
	}
?>

<div id="banners_holder">

<?php
	if (is_array($banners) && count($banners) > 0) {
		$c = 1;
		foreach ($banners as $banner) {
			
			$active = $banner['active'];
			$active_class = ($banner['active'] == 0) ? ' inactive' : '';
			echo '	
			<!-- banner '.$c.' -->
			<div class="banner'.$active_class.'">
			<div class="position">
				<div class="moveup">';
			if ($c > 1 && $active == 1) {
				echo '<a href="banners_upload.php?action=moveup&amp;pos='.$c.'&amp;role='.$role.'" title="Move Up"><img src="img/icon-moveup.png" /></a>';
			}
			echo '</div>
				<div class="count">'.$c.'</div>
				<div class="movedown">';
			if ($c != $active_banners && $active == 1) {
				echo '<a href="banners_upload.php?action=movedown&amp;pos='.$c.'&amp;role='.$role.'" title="Move Down"><img src="img/icon-movedown.png" /></a>';
			}
			echo '</div>
			</div>
				<div class="banner_details">
					<img src="'.$CFG->wwwroot . $banner['img_url'].'" height="155" width="425" alt="" /><br />
					<div class="actions">
						<a href="banner_edit.php?pos='.$c.'&amp;role='.$role.'" class="edit"><span class="'.$c.'">Edit</span></a>
						<a href="banners_upload.php?action=delete&amp;pos='.$c.'&amp;role='.$role.'" class="delete"><span class="'.$c.'">Delete</span></a>
					';	
					if ($active) {
						echo '<a href="banners_upload.php?action=disable&amp;pos='.$c.'&amp;role='.$role.'" class="disable"><span class="'.$c.'">Disable</span></a>';
					} else {
						echo '<a href="banners_upload.php?action=enable&amp;pos='.$c.'&amp;role='.$role.'" class="enable"><span class="'.$c.'">Enable</span></a>';
					}
					// Reduce size of banner link if over a certain amount of characters
					$banner_link_title = $banner['link'];
					$max_chars = 25;
					if (strlen($banner_link_title) > $max_chars) {
						$banner_link_title = substr($banner_link_title, 0, $max_chars) . '...';
					}
					echo '</div>
					<div class="link">
						<strong>Link:</strong> <a href="'.$banner['link'].'" target="_blank" title="'.$banner['link'].'">'.$banner_link_title.'</a>
					</div>
					<br class="clear_both" />
				</div>
			</div>
			<!-- //banner '.$c.' -->
			';

			$c++;
		}
	}
?>
</div>

<!-- add banner -->
<div id="banner_add_form">
<h3>Add Banner</h3>
	<form enctype="multipart/form-data" action="banners_upload.php" method="POST">
		<table>
			<tr>
				<td><label for="upload_link">Link:</label></td>
				<td><input type="text" name="banner_link" class="field" id="upload_link" /></td>
			</tr>
			<tr>
				<td valign="top"><label for="upload_banner">Banner:</label></td>
				<td>
					<input type="file" name="banner_img" id="upload_banner" />
					<input type="hidden" name="MAX_FILE_SIZE" value="500000" />
					<input type="hidden" name="action" value="upload" />
					<input type="hidden" name="role" value="<?php echo $role; ?>" />
                    <p class="note">Banner size: 495 pixels wide, 185 pixels height</p>
				</td>
			</tr>
			<tr>
				<td colspan="2">
				<input type="hidden" name="position" value="0" />
					<br /><input type="submit" value="Add Banner" />
				</td>
			</tr>
		</table>
	</form>
</div>
<!-- //add banner -->

</div>
<?php
    print_footer();
?>
