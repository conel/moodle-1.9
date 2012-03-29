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
	$iatype       = optional_param('iatype', '', PARAM_RAW);
	
	$sm  = optional_param('sm', '', PARAM_INT);
	$sy  = optional_param('sy', '', PARAM_INT);
	$em  = optional_param('em', '', PARAM_INT);
	$ey  = optional_param('ey', '', PARAM_INT);
	
	require_login();
	
	$unix_start = '';
	$unix_end = '';

	// If valid start date, create unixtime
	if ( ($sm != '' && $sm <= 12) && ($sy != '' && (strlen($sy) == 4)) ) {
		// Make unixtime
		$unix_start = mktime(0, 0, 0, $sm, 1, $sy);
	}
	// If valid end date, create unixtime
	if ( ($em != '' && $em <= 12) && ($ey != '' && (strlen($ey) == 4)) ) {
		// Make unixtime
		$unix_end = mktime(0, 0, 0, $em, 1, $ey);
	}

	$sitecontext = get_context_instance(CONTEXT_SYSTEM);
    if (has_capability('mod/ilpconcern:viewbksbstats',$sitecontext) || has_capability('moodle/site:doanything',$sitecontext)) {  // are we god ?
        $access_isgod = 1 ;
    } else {
		error('You do not have permission to view this page', $CFG->wwwroot);
	}

    // Print headers
	//$ass_type = $bksb->getAssTypeFromNo($assessment);
    $title = 'BKSB - Initial Assessment Stats';
	print_header($title, $title, "Inital Assessment Stats", "", "", true, "&nbsp;", navmenu($course));
	
	
	// Connect to MIS to grab GCSE Results
	// For connecting to MIS
	require_once($CFG->dirroot.'/blocks/ilp/templates/custom/dbconnect.php');
	

	// Get all BKSB groups
	$groups = $bksb->getBksbGroups();
	
    echo "<h2>BKSB - Initial Assessments</h2>";
	echo '<ul><li><a href="bksb_stats_diagnostics.php">BKSB - Diagnostic Overviews</a></li></ul>';
	echo '<hr />';
	
	echo '<form action="bksb_stats_initial_assessments.php" method="get">';
	echo '<table><tr>';
	echo '<td style="text-align:right;"><b>Initial Assessment:</b></td>';
	echo '<td><select name="iatype">';
	echo '<option value="">-- Select Initial Assessment --</option>';
	if ($iatype == 'English') {
		echo '<option value="English" selected="selected">English</option>';
	} else {
		echo '<option value="English">English</option>';
	}
	if ($iatype == 'Mathematics') {
		echo '<option value="Mathematics" selected="selected">Mathematics</option>';
	} else {
		echo '<option value="Mathematics">Mathematics</option>';
	}
	if ($iatype == 'ICT') {
		echo '<option value="ICT" selected="selected">ICT</option>';
	} else {
		echo '<option value="ICT">ICT</option>';
	}
	
	echo '</select></td></tr>';
	echo '<tr><td style="text-align:right;"><b>Group:</b></td>';
	echo '<td><select name="group">';
	echo '<option value="">-- Select Group --</option>';
	foreach ($groups as $group_name) {
		if ($group_name == $group) {
			echo '<option value="'.$group_name.'" selected="selected">'.$group_name.'</option>';
		} else {
			echo '<option value="'.$group_name.'">'.$group_name.'</option>';
		}
	}
	
	echo '</select></td></tr>';
	
	// nkowald - 2012-01-16 - Scott wants to be able to search via month year
	
	$months = array (
		'1' => 'Jan',
		'2' => 'Feb',
		'3' => 'Mar',
		'4' => 'Apr',
		'5' => 'May',
		'6' => 'Jun',
		'7' => 'Jul',
		'8' => 'Aug',
		'9' => 'Sep',
		'10' => 'Oct',
		'11' => 'Nov',
		'12' => 'Dec'
	);
	
	$year_start = 2009;
	while ($year_start <= date('Y')) {
		$years[] = $year_start;
		$year_start++;
	}
	
	// Start Date
	echo "<tr><td style=\"text-align:right;\"><b>Date Start:</b></td>\n";
	echo "<td>\n";
	
		echo "<select name=\"sm\">\n";
				echo "\t<option value=\"\">&ndash;</option>\n";
			foreach ($months as $key => $val) {
				$selected = ($key == $sm) ? ' selected="selected"' : '';
				echo "\t<option value=\"$key\"$selected>$val</option>\n";
			}
		echo "</select>\n";
		
		echo "<select name=\"sy\">\n";
				echo "\t<option value=\"\">&ndash;</option>\n";
			foreach ($years as $year) {
				$selected = ($year == $sy) ? ' selected="selected"' : '';
				echo "\t<option value=\"$year\"$selected>$year</option>\n";
			}
		echo "</select>\n";
	
	echo "</td>\n";
	echo "</tr>\n";
	
	// End Date
	echo "<tr><td style=\"text-align:right;\"><b>Date End:</b></td>\n";
	echo "<td>\n";
	
		echo "<select name=\"em\">\n";
				echo "\t<option value=\"\">&ndash;</option>\n";
			foreach ($months as $key => $val) {
				$selected = ($key == $em) ? ' selected="selected"' : '';
				echo "\t<option value=\"$key\"$selected>$val</option>\n";
			}
		echo "</select>\n";
		
		echo "<select name=\"ey\">\n";
				echo "\t<option value=\"\">&ndash;</option>\n";
			foreach ($years as $year) {
				$selected = ($year == $ey) ? ' selected="selected"' : '';
				echo "\t<option value=\"$year\"$selected>$year</option>\n";
			}
		echo "</select>\n";
	
	echo "</td>\n";
	echo "</tr>\n";

	echo '<tr><td>&nbsp;</td><td><input type="submit" value="View Stats" /></td></tr></table>';
	echo '</form>';

	echo '<div id="bksb_stats">';
	
	// Set up array keys to ignore
	$ignore_keys = array('total_literacy_e2', 'total_literacy_e3', 'total_literacy_l1', 'total_literacy_l2', 'total_literacy_l3', 'total_numeracy_e2', 'total_numeracy_e3', 'total_numeracy_l1', 'total_numeracy_l2', 'total_numeracy_l3');
	
	if ($group != '' && $iatype != '') {
		$users = $bksb->getIAForGroup($group, $iatype, $unix_start, $unix_end);
		$user_count = 0;
		foreach ($users as $key => $value) {
			if (!in_array($key, $ignore_keys) && $key != '') {
				$user_count++;
			}
		}
		
		echo '<h2>'.$group.'</h2>';
		
			if ($iatype == 'English') {
			
				echo '<b>Initial Assessments Taken:</b> '.$user_count.'<br /><br />';
				$perc_1 = round((($users['total_literacy_e2'] / $user_count) * 100), 1);
				echo '<b>English Entry 2:</b> '.$users['total_literacy_e2'].' ['.$perc_1.'%]<br />';
				$perc_2 = round((($users['total_literacy_e3'] / $user_count) * 100), 1);
				echo '<b>English Entry 3:</b> '.$users['total_literacy_e3'].' ['.$perc_2.'%]<br />';
				$perc_3 = round((($users['total_literacy_l1'] / $user_count) * 100), 1);
				echo '<b>English Level 1:</b> '.$users['total_literacy_l1'].' ['.$perc_3.'%]<br />';
				$perc_4 = round((($users['total_literacy_l2'] / $user_count) * 100), 1);
				echo '<b>English Level 2:</b> '.$users['total_literacy_l2'].' ['.$perc_4.'%]<br />';
				$perc_5 = round((($users['total_literacy_l3'] / $user_count) * 100), 1);
				echo '<b>English Level 3:</b> '.$users['total_literacy_l3'].' ['.$perc_5.'%]<br />';
				echo '<br />';
				
				echo '<table cellspacing="3">';
				echo '<tr><th>Username</th><th>Name</th><th>English Entry 2</th><th>English Entry 3</th><th>English Level 1</th><th>English Level 2</th><th>English Level 3</th><th>GCSE</th></tr>';
				foreach ($users as $key => $user) {
					if (!in_array($key, $ignore_keys) && $key != '') {
						echo '<tr>';
						echo "<td>".$user['user_name']."</td>";
						// nkowald - 2012-01-20 - Get name from username
						$name = $bksb->getNameFromUsername($user['user_name']);
						echo "<td>".$name['firstname']. " " . $name['lastname'] . "</td>";
						echo "<td>".$user['literacy_e2']."</td><td>".$user['literacy_e3']."</td><td>".$user['literacy_l1']."</td><td>".$user['literacy_l2']."</td><td>".$user['literacy_l3']."</td>";
						
						// Get GCSE result for the student
						$query = sprintf("SELECT AWARD_TITLE, ACHIEVED_YEAR, GRADE, AWARDING_BODY, QUAL_TYPE, QUAL_DESC FROM FES.MOODLE_ATTAINMENTS WHERE STUDENT_ID = %d AND QUAL_TYPE = 'GCSE' AND AWARD_TITLE IN ('Maths', 'English')",
							$user['user_name']
						);
						$html = '';
						if ($quals = $mis->Execute($query)) {
					
							$user_quals = array();
							$i = 0;
							while (!$quals->EOF) {
								$user_quals[$i]['award_title'] = $quals->fields["AWARD_TITLE"];
								$user_quals[$i]['achieved_year'] = $quals->fields["ACHIEVED_YEAR"];
								$user_quals[$i]['grade'] = $quals->fields["GRADE"];
								$user_quals[$i]['awarding_body'] = ($quals->fields["AWARDING_BODY"] != '') ? $quals->fields["AWARDING_BODY"] : '' ;
								$user_quals[$i]['qual_type'] = $quals->fields["QUAL_TYPE"];
								$user_quals[$i]['qual_desc'] = $quals->fields["QUAL_DESC"];
								
								$quals->moveNext();
								$i++;
							}
							// If we have quals, put them into a nicely formatted bit of text to return
							$html = '';
							foreach ($user_quals as $qual) {
								$html .= $qual['award_title'] . "&nbsp;";
								$html .= $qual['qual_desc'];
								$html .= "&nbsp;". $qual['grade'] . "&nbsp;";
								if ($qual['awarding_body'] != '') {
									$html .= $qual['awarding_body'] . "&nbsp;";
								}
								$html .= "- " . $qual['achieved_year'] . "&nbsp;";
								$html .= '<br />';
								$i++;
							}
						}
						if ($html == '') { $html = '-'; }
						echo "<td>".$html."</td>";
						echo '</tr>';
					}
				}
				echo '<tr class="totals"><td><b>Totals</b></td>
				<td>&nbsp;</td><td>'.$users['total_literacy_e2'].'</td><td>'.$users['total_literacy_e3'].'</td><td>'.$users['total_literacy_l1'].'</td><td>'.$users['total_literacy_l2'].'</td><td>'.$users['total_literacy_l3'].'</td><td>&nbsp;</td></tr>';
				echo '</table>';
				
			} else if ($iatype == 'Mathematics') {
				
				echo '<b>Initial Assessments Taken:</b> '.$user_count.'<br /><br />';
				$perc_1 = round((($users['total_numeracy_e2'] / $user_count) * 100), 1);
				echo '<b>Maths Entry 2:</b> '.$users['total_numeracy_e2'].' ['.$perc_1.'%]<br />';
				$perc_2 = round((($users['total_numeracy_e3'] / $user_count) * 100), 1);
				echo '<b>Maths Entry 3:</b> '.$users['total_numeracy_e3'].' ['.$perc_2.'%]<br />';
				$perc_3 = round((($users['total_numeracy_l1'] / $user_count) * 100), 1);
				echo '<b>Maths Level 1:</b> '.$users['total_numeracy_l1'].' ['.$perc_3.'%]<br />';
				$perc_4 = round((($users['total_numeracy_l2'] / $user_count) * 100), 1);
				echo '<b>Maths Level 2:</b> '.$users['total_numeracy_l2'].' ['.$perc_4.'%]<br />';
				$perc_5 = round((($users['total_numeracy_l3'] / $user_count) * 100), 1);
				echo '<b>Maths Level 3:</b> '.$users['total_numeracy_l3'].' ['.$perc_5.'%]<br />';
				echo '<br />';
				
				echo '<table cellspacing="3">';
				echo '<tr><th>Username</th><th>Name</th><th>Maths Entry 2</th><th>Maths Entry 3</th><th>Maths Level 1</th><th>Maths Level 2</th><th>Maths Level 3</th><th>GCSE</th></tr>';
				foreach ($users as $key => $user) {

					if (!in_array($key, $ignore_keys) && $key != '') {
						echo '<tr>';
						echo "<td>".$user['user_name']."</td>";
						// nkowald - 2012-01-20 - Get name from username
						$name = $bksb->getNameFromUsername($user['user_name']);
						echo "<td>".$name['firstname']. " " . $name['lastname'] . "</td>";
						echo "<td>".$user['numeracy_e2']."</td><td>".$user['numeracy_e3']."</td><td>".$user['numeracy_l1']."</td><td>".$user['numeracy_l2']."</td><td>".$user['numeracy_l3']."</td>";
						// Get GCSE result for the student
						$query = sprintf("SELECT AWARD_TITLE, ACHIEVED_YEAR, GRADE, AWARDING_BODY, QUAL_TYPE, QUAL_DESC FROM FES.MOODLE_ATTAINMENTS WHERE STUDENT_ID = %d AND QUAL_TYPE = 'GCSE' AND AWARD_TITLE IN ('Maths', 'English')",
							$user['user_name']
						);
						$html = '';
						if ($quals = $mis->Execute($query)) {
					
							$user_quals = array();
							$i = 0;
							while (!$quals->EOF) {
								$user_quals[$i]['award_title'] = $quals->fields["AWARD_TITLE"];
								$user_quals[$i]['achieved_year'] = $quals->fields["ACHIEVED_YEAR"];
								$user_quals[$i]['grade'] = $quals->fields["GRADE"];
								$user_quals[$i]['awarding_body'] = ($quals->fields["AWARDING_BODY"] != '') ? $quals->fields["AWARDING_BODY"] : '' ;
								$user_quals[$i]['qual_type'] = $quals->fields["QUAL_TYPE"];
								$user_quals[$i]['qual_desc'] = $quals->fields["QUAL_DESC"];
								
								$quals->moveNext();
								$i++;
							}
							// If we have quals, put them into a nicely formatted bit of text to return
							$html = '';
							foreach ($user_quals as $qual) {
								$html .= $qual['award_title'] . "&nbsp;";
								$html .= $qual['qual_desc'];
								$html .= "&nbsp;". $qual['grade'] . "&nbsp;";
								if ($qual['awarding_body'] != '') {
									$html .= $qual['awarding_body'] . "&nbsp;";
								}
								$html .= "- " . $qual['achieved_year'] . "&nbsp;";
								$html .= '<br />';
								$i++;
							}
						}
						if ($html == '') { $html = '-'; }
						echo "<td>".$html."</td>";
						echo '</tr>';
					}
				}
				echo '<tr class="totals"><td><b>Totals</b></td>
				<td>&nbsp;</td><td>'.$users['total_numeracy_e2'].'</td><td>'.$users['total_numeracy_e3'].'</td><td>'.$users['total_numeracy_l1'].'</td><td>'.$users['total_numeracy_l2'].'</td><td>'.$users['total_numeracy_l3'].'</td><td>&nbsp;</td></tr>';
				echo '</table>';
				
			} else if ($iatype == 'ICT') {
			
				$valid_types = array('word_processing', 'spreadsheets', 'databases', 'desktop_publishing', 'presentation', 'email', 'general', 'internet');
				
				$user_count = 0;
				foreach ($users as $key => $value) {
					$user_count++;
				}
				
				echo '<b>ICT Initial Assessments:</b> '.$user_count.'<br /><br />';
				
				$totals = $bksb->getIctTotals($users);
				echo $totals;
				
				echo '<table cellspacing="3">';
				echo '<tr><th>Username</th><th>Name</th><th>Word Processing</th><th>Spreadsheets</th><th>Databases</th><th>Desktop Publishing</th><th>Presentation</th><th>Email</th><th>General</th><th>Internet</th><th>GCSE</th></tr>';
				
				foreach ($users as $user) {
					echo '<tr>';
					echo "<td>".$user['user_name']."</td>";
					// nkowald - 2012-01-20 - Get name from username
					$name = $bksb->getNameFromUsername($user['user_name']);
					echo "<td>".$name['firstname']. " " . $name['lastname'] . "</td>";
					foreach ($user['results'] as $type => $value) {
						if (in_array($type, $valid_types)) {
							echo "<td>".$value."</td>";
						}
					}
					// Get GCSE result for the student
						$query = sprintf("SELECT AWARD_TITLE, ACHIEVED_YEAR, GRADE, AWARDING_BODY, QUAL_TYPE, QUAL_DESC FROM FES.MOODLE_ATTAINMENTS WHERE STUDENT_ID = %d AND QUAL_TYPE = 'GCSE' AND AWARD_TITLE IN ('Maths', 'English')",
							$user['user_name']
						);
						$html = '';
						if ($quals = $mis->Execute($query)) {
					
							$user_quals = array();
							$i = 0;
							while (!$quals->EOF) {
								$user_quals[$i]['award_title'] = $quals->fields["AWARD_TITLE"];
								$user_quals[$i]['achieved_year'] = $quals->fields["ACHIEVED_YEAR"];
								$user_quals[$i]['grade'] = $quals->fields["GRADE"];
								$user_quals[$i]['awarding_body'] = ($quals->fields["AWARDING_BODY"] != '') ? $quals->fields["AWARDING_BODY"] : '' ;
								$user_quals[$i]['qual_type'] = $quals->fields["QUAL_TYPE"];
								$user_quals[$i]['qual_desc'] = $quals->fields["QUAL_DESC"];
								
								$quals->moveNext();
								$i++;
							}
							// If we have quals, put them into a nicely formatted bit of text to return
							$html = '';
							foreach ($user_quals as $qual) {
								$html .= $qual['award_title'] . "&nbsp;";
								$html .= $qual['qual_desc'];
								$html .= "&nbsp;". $qual['grade'] . "&nbsp;";
								if ($qual['awarding_body'] != '') {
									$html .= $qual['awarding_body'] . "&nbsp;";
								}
								$html .= "- " . $qual['achieved_year'] . "&nbsp;";
								$html .= '<br />';
								$i++;
							}
						}
						if ($html == '') { $html = '-'; }
						echo "<td>".$html."</td>";
					echo '</tr>';
				}
				//echo '<tr class="totals"><td><b>Totals</b></td>
				//<td>'.$users['total_numeracy_e2'].'</td><td>'.$users['total_numeracy_e3'].'</td><td>'.$users['total_numeracy_l1'].'</td><td>'.$users['total_numeracy_l2'].'</td><td>'.$users['total_numeracy_l3'].'</td></tr>';
				echo '</table>';
				
			}

	}
	echo '</div>';
	
    print_footer($course);

?>

