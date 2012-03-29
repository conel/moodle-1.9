<?php 

//  Users entered incorrect IDs when they used BKSB
//  This page allows staff to update user details

    require_once('../../config.php');
    //require_once('block_ilp_lib.php');
	include('../ilp/access_context.php');

    include_once('BksbReporting.class.php');
    $bksb = new BksbReporting();

    global $GFG, $USER;

	$userid       = optional_param('userid', 0, PARAM_INT);
	$group        = optional_param('group', '', PARAM_RAW);

    require_login();

	$sitecontext = get_context_instance(CONTEXT_SYSTEM);

	$sitecontext = get_context_instance(CONTEXT_SYSTEM);
    if (has_capability('mod/ilpconcern:viewbksbstats',$sitecontext) || has_capability('moodle/site:doanything',$sitecontext)) {  // are we god ?
        $access_isgod = 1 ;
    } else {
		error('You do not have permission to view this page', $CFG->wwwroot);
	}
	
    // Print headers
	//$ass_type = $bksb->getAssTypeFromNo($assessment);
    $title = 'BKSB - Diagnostic Overview Stats';
	print_header($title, $title, "Diagnostic Overview Stats", "", "", true, "&nbsp;", navmenu($course));

	// Get all BKSB groups
	$groups = $bksb->getBksbGroups();
	
    echo "<h2>BKSB - Diagnostic Overviews</h2>";
	echo "<p>Stats are from <b>complete</b> diagnostic overviews</p>";
	echo '<ul><li><a href="bksb_stats_initial_assessments.php">BKSB - Inital Assessment Stats</a></li></ul>';
	echo '<hr />';
	echo '<div id="bksb_stats">';

	echo '<form action="bksb_stats_diagnostics.php" method="get">';
	echo '<p><b>Group:</b>&nbsp;';
	echo '<select name="group" onchange="javascript:this.form.submit()">';
	echo '<option value="">-- Select Group --</option>';
	foreach ($groups as $group_name) {
		if ($group_name == $group) {
			echo '<option value="'.$group_name.'" selected="selected">'.$group_name.'</option>';
		} else {
			echo '<option value="'.$group_name.'">'.$group_name.'</option>';
		}
	}
	echo '</select></p>';
	echo '</form>';

	// Set up array keys to ignore
	$ignore_keys = array('total_literacy_e2', 'total_literacy_e3', 'total_literacy_l1', 'total_literacy_l2', 'total_literacy_l3', 'total_numeracy_e2', 'total_numeracy_e3', 'total_numeracy_l1', 'total_numeracy_l2', 'total_numeracy_l3');
	
	if ($group != '') {
	
		$users = $bksb->getDiagnosticOverviewsForGroup($group);
		$user_count = 0;
		foreach ($users as $key => $value) {
			if (!in_array($key, $ignore_keys) && $key != '') {
				$user_count++;
			}
		}
		
		echo '<h2>'.$group.'</h2>';
		
		echo '<b>Complete Diagnostic Overviews:</b> '.$user_count.'<br /><br />';
		echo '<b>English Entry 2:</b> '.$users['total_literacy_e2'].'<br />';
		echo '<b>English Entry 3:</b> '.$users['total_literacy_e3'].'<br />';
		echo '<b>English Level 1:</b> '.$users['total_literacy_l1'].'<br />';
		echo '<b>English Level 2:</b> '.$users['total_literacy_l2'].'<br />';
		echo '<b>English Level 3:</b> '.$users['total_literacy_l3'].'<br />';
		echo '<br />';
		echo '<b>Maths Entry 2:</b> '.$users['total_numeracy_e2'].'<br />';
		echo '<b>Maths Entry 3:</b> '.$users['total_numeracy_e3'].'<br />';
		echo '<b>Maths Level 1:</b> '.$users['total_numeracy_l1'].'<br />';
		echo '<b>Maths Level 2:</b> '.$users['total_numeracy_l2'].'<br />';
		echo '<b>Maths Level 3:</b> '.$users['total_numeracy_l3'].'<br />';
		echo '<br />';
		
		echo '<table cellspacing="3">';
		echo '<tr><th>Username</th><th>English E2</th><th>English E3</th><th>English L1</th><th>English L2</th><th>English L3</th><th>Maths E2</th><th>Maths E3</th><th>Maths L1</th><th>Maths L2</th><th>Maths L3</th></tr>';
		foreach ($users as $key => $user) {
			if (!in_array($key, $ignore_keys) && $key != '') {
				echo '<tr>';
				echo "<td>".$user['user_name']."</td><td>".$user['literacy_e2']."</td><td>".$user['literacy_e3']."</td><td>".$user['literacy_l1']."</td><td>".$user['literacy_l2']."</td><td>".$user['literacy_l3']."</td><td>".$user['numeracy_e2']."</td><td>".$user['numeracy_e3']."</td><td>".$user['numeracy_l1']."</td><td>".$user['numeracy_l2']."</td><td>".$user['numeracy_l3']."</td>";
				echo '</tr>';
			}
		}
		echo '<tr class="totals"><td><b>Totals</b></td>
		<td>'.$users['total_literacy_e2'].'</td><td>'.$users['total_literacy_e3'].'</td><td>'.$users['total_literacy_l1'].'</td><td>'.$users['total_literacy_l2'].'</td><td>'.$users['total_literacy_l3'].'</td><td>'.$users['total_numeracy_e2'].'</td><td>'.$users['total_numeracy_e3'].'</td><td>'.$users['total_numeracy_l1'].'</td><td>'.$users['total_numeracy_l2'].'</td><td>'.$users['total_numeracy_l3'].'</td></tr>';
		echo '</table>';

	}
	
	echo '</div>';
	
    print_footer($course);

?>

