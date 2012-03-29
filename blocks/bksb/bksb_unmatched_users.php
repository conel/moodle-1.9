<?php 
	// Users entered incorrect IDs when they did their BKSB Initial Assessments
	// This page allows staff to update user details

    require_once('../../config.php');
    //require_once('block_ilp_lib.php');
	include('../ilp/access_context.php');

    include_once('BksbReporting.class.php');
    $bksb = new BksbReporting();

    global $GFG, $USER;

	$userid       	 = optional_param('userid', 0, PARAM_INT);
	$firstname       = optional_param('fname', '', PARAM_RAW);
	$lastname        = optional_param('lname', '', PARAM_RAW);
	$order_field	 = optional_param('order', '', PARAM_RAW);

    require_login();

	$sitecontext = get_context_instance(CONTEXT_SYSTEM);

    if (has_capability('moodle/site:doanything',$sitecontext) || $bksb->is_elt($USER->id)) {  // are we god or an ELT?
        $access_isgod = 1;
    } else {
		error('Not enough access to view this page');
	}

    // Print headers
	//$ass_type = $bksb->getAssTypeFromNo($assessment);
    $title = 'BKSB - Update Usernames';
	print_header($title, $title, "BKSB - Update Usernames", "", "", true, "&nbsp;", navmenu($course));
?>
<style type="text/css">
	@import url("js/colorbox.css");
</style>
<script type="text/javascript" src="js/jquery.colorbox-min.js"></script>
<script type="text/javascript" src="js/functions.js"></script>
<?php
	function ae_detect_ie() {
		if (isset($_SERVER['HTTP_USER_AGENT']) && (strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE') !== false)) {
			return true;
		} else {
			return false;
		}
	}
	
    echo "<h2>BKSB - Invalid Usernames</h2>";
	
	// Insert best viewed with Chrome or Firefox banner here.
	if (ae_detect_ie()) {
		echo '<div id="best_viewed"><img src="'.$CFG->wwwroot.'/blocks/bksb/js/images/best-viewed-with.png" alt="Best viewed with Chrome or Firefox" width="468" height="59" /></div>';
	}
	
	// We need to ability to search by first and lastname
	echo '<hr />';
	echo '<h4>Filter Invalid Users</h4>';
	echo '<form action="bksb_unmatched_users.php" method="get">
	<table>
		<tr><td>Firstname:</td><td><input type="text" name="fname" value="'.$firstname.'" /></td></tr>
		<tr><td>Lastname:</td><td><input type="text" name="lname" value="'.$lastname.'" /></td></tr>
		<tr><td>&nbsp;</td><td><input type="submit" value="Filter Users" /></td>
		<tr><td>&nbsp;</td><td><a href="bksb_unmatched_users.php">Clear Filters</a></td>
	</table>
	</form>';
	echo '<hr />';
	
	$invalid_users = $bksb->getInvalidBksbUsers($firstname, $lastname, $order_field);
	$no_invalids = count($invalid_users);
	
	if ($no_invalids > 0) {
		// nkowald - 2011-08-22 - This should NEVER be run during the day when there's a possibility
		// someone is doing an initial assessment: changing reference during assessment
		//$bksb->updateInvalidUsers($invalid_users);
	}
	
	$no_invalid_users = number_format(count($invalid_users));
	$username_txt = ($no_invalid_users > 1) ? 'usernames' : 'username';
	echo "<p><b>$no_invalid_users</b> invalid $username_txt found in BKSB</p>";
	
	// Pagination
	$per_page = 500;
	$no_pages = ceil($no_invalids / $per_page);
	$page_no = 1;
	
	echo '<div id="invalid_users">';
	
	// "Pagination" here. Really just javascript which shows/hides groups of 500
	// Only show pagination if there's more than 'per_page' results
	if ($no_invalids > $per_page) {
		$start_count = 1;
		$current_pp = $per_page;
		echo '<div id="pagination">';
		for ($p = 1; $p <= $no_pages; $p++) {
			if ($p == $no_pages) {
				$current_pp = $no_invalid_users;
			}
			$current_class = ($p == 1) ? ' class="show_table current"' : 'class="show_table"';
			echo '<a href="#" '.$current_class.' name="'.$p.'" id="link_'.$p.'">'.$start_count.' &ndash; '.$current_pp.'</a>';
			$start_count += $per_page;
			$current_pp += $per_page;
		}
		echo '<br class="clear_both" />';
		echo '</div>';
	}
	
	if (ae_detect_ie()) { echo "<br />"; }
	
	// Split invalid users into n ($no_pages) groups of ($per_page) items
	$c = 1;
	$p = 1;
	$page_limit = $per_page;

	foreach ($invalid_users as $user) {
		if ($c <= $page_limit) {
			$invalids[$p][] = $user;
			$c++;
		} else {
			$page_limit += $per_page;
			$p++;
			$c++;
			$invalids[$p][] = $user;
		}
	}
	
	// Print the table seven times.
	$i = 1;
	for ($j=1; $j <= $no_pages; $j++) {
		echo "<div id=\"invalid_users_$j\">";
			echo "<table>";
			echo "<tr>
				<th>No</th>
				<th><a href=\"bksb_unmatched_users.php?order=userName\" title=\"Order by Username\">Username</a></th>
				<th><a href=\"bksb_unmatched_users.php?order=FirstName\" title=\"Order by Firstname\">Firstname</a></th>
				<th><a href=\"bksb_unmatched_users.php?order=LastName\" title=\"Order by Lastname\">Lastname</a></th>
				<th>&nbsp;</th>
				<th>DOB</th>
				<th>Postcode</th>
				<th>Why invalid?</th><th>Action</th>
				</tr>";
			foreach ($invalids[$j] as $user) {
				echo "<tr id=\"user_$i\">
					<td style=\"text-align:center;\">$i</td>
					
					<td class=\"bksb_username\"><span>".$user['username']."</span></td>
					
					<td class=\"bksb_firstname\"><span>".$user['firstname']."</span> 
					<a href=\"".$CFG->wwwroot."/admin/user.php?firstname=".urlencode($user['firstname'])."\" target=\"_blank\" title=\"Search Firstname: ".$user['firstname']."\" class=\"lookup\" id=\"look_$i\"><img src=\"".$CFG->wwwroot."/blocks/bksb/js/images/search_icon.gif\" alt=\"\" width=\"16\" height=\"16\" class=\"user_search\" /></a></td>
					
					<td class=\"bksb_lastname\"><span>".$user['lastname']."</span> 
					<a href=\"".$CFG->wwwroot."/admin/user.php?lastname=".urlencode($user['lastname'])."\" target=\"_blank\" title=\"Search Lastname: ".$user['lastname']."\" class=\"lookup\" id=\"look_$i\"><img src=\"".$CFG->wwwroot."/blocks/bksb/js/images/search_icon.gif\" alt=\"\" width=\"16\" height=\"16\" class=\"user_search\" /></a></td>
					
					<td><a href=\"".$CFG->wwwroot."/admin/user.php?firstname=".urlencode($user['firstname'])."&amp;lastname=".urlencode($user['lastname'])."\" target=\"_blank\" title=\"Search Fullname: ".$user['firstname']." ".$user['lastname']."\" class=\"lookup\" id=\"lookboth_$i\"><img src=\"".$CFG->wwwroot."/blocks/bksb/js/images/search_icon.jpg\" alt=\"\" width=\"16\" height=\"16\" class=\"search_fullname\" /></a></td>
					
					<td><span>".$user['dob']."</span></td>
					<td><span>".$user['postcode']."</span></td>
					
					<td>".$user['reason']."</td>
					
					<td>&nbsp;<a href=\"/VLE/blocks/bksb/bksb_update.php?old_username=".urlencode($user['username'])."&amp;firstname=".urlencode($user['firstname'])."&amp;lastname=".urlencode($user['lastname'])."&amp;row=$i\" target=\"_blank\" class=\"update_user\" id=\"update_$i\">Update</a>&nbsp;</td>

					</tr>";
				$i++;
			}
			echo "</table>";
		echo "</div>";
	}
	
	echo '</div>';

    print_footer($course);

?>