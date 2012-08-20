<?php  

    require_once("../../config.php");
    require_once("lib.php");

    global $CFG, $USER;
		

	$courseid = optional_param('courseid', PARAM_INT); 
	$userid   = required_param('userid', PARAM_INT);
	$tutor   = optional_param('tutor', 0, PARAM_INT);

	require_login();

    $user = get_record('user', 'id', $userid);

    if ($user->id == $USER->id) {
        $context = get_context_instance(CONTEXT_SYSTEM);
    } else {
        $context = get_context_instance(CONTEXT_USER, $user->id);
    }

    require_once($CFG->dirroot . '/blocks/ilp/AttendancePunctuality.class.php');
	$attpunc = new AttendancePunctuality();

    $navigation = "<a href=\"../../blocks/ilp/view.php?id=$user->id\">ILP</a>";

    $reports = '<a href="'.$CFG->wwwroot.'/mod/ilpconcern/view_students.php?courseid='.$courseid.'">Reports</a>';
    print_header("Targets Overview", "Targets", "$navigation -> ".$reports." -> ".fullname($user)."", "", "", true, "", "");

    echo '<div id="subject_targets">';
    echo '<h2>Targets for <a href="'.$CFG->wwwroot.'/user/view.php?id='.$userid.'&course='.$courseid.'" title="View '.fullname($user).'\'s profile">'.fullname($user).'</a></h2>';

    // Get target data for this user
    $data = $attpunc->getModuleDetails($user->idnumber, $attpunc->current_term_no);
	
    $table = "<table>\n";
    $table .= "<tr><th>Module code</th><th>Module Name</th><th>Tutor</th><th>Complete</th></tr>\n";
	$c = 1;
    foreach ($data as $datum) {
		// uncomment this check after running import script
		if ($datum['tutor'] != '') {
			$row_class = ($c % 2 == 0) ? ' class="r1"' : ' class="r0"';
			$table .= '<tr'.$row_class.'><td><span title="'.$c.'">'.$datum['module_code'] . '</span></td>';
			$table .= '<td>'.$datum['module_desc'] . '</td>';
			$table .= '<td><a href="'.$CFG->wwwroot.'/mod/ilpconcern/subject_targets.php?courseid='.$courseid.'&userid='.$userid.'&tutor='.$datum['tutor_id'].'#tutor" title="View Targets for '.$datum['tutor'].'">'.$datum['tutor'] . '</a></td>';
			$complete_img = ($datum['complete'] == 0) ? $CFG->wwwroot .'/theme/standard/pix/i/cross_red_big.gif' : $CFG->wwwroot .'/theme/standard/pix/i/tick_green_big.gif';
			$table .= '<td class="center"><img src="'.$complete_img . '" alt="" width="16" height="16" /></td></tr>';
			$c++;
		}
    }
    $table .= '</table>';

    echo $table;
	
	echo '<br />';
	echo '<p>[<a href="'.$CFG->wwwroot.'/blocks/lpr/actions/new.php?course_id='.$courseid.'&ilp=1&learner_id='.$userid.'">Add Target for '.fullname($user).'</a>]</p>';
	
	echo '<br />';
	
	// Get list of distinct tutors for this term and academic year
	$query = sprintf("SELECT DISTINCT tutor_name, mdl_tutor_id FROM mdl_module_complete WHERE term = %1d AND academic_year = %4d ORDER BY tutor_name ASC",
		$attpunc->current_term_no,
		$attpunc->academic_year_4digit
	);
	
	if ($tutors = get_records_sql($query)) {
		$tutor_list = array();
		foreach ($tutors as $tut) {
			$tutor_list[$tut->mdl_tutor_id] = $tut->tutor_name;
		}
	}
	if ($tutor != 0 && array_key_exists($tutor, $tutor_list)) {
		$tutor_name = $tutor_list[$tutor];
		echo '<h2>Targets for <a href="'.$CFG->wwwroot.'/user/view.php?id='.$tutor.'&course=1" title="View '.$tutor_name.'\'s profile">'.$tutor_name.'</a></h2>';
	} else {
		echo '<h2>Targets for Tutor</h2>';
	}
	echo '<a name="tutor"></a>';
	
	$form_action = $CFG->wwwroot . '/mod/ilpconcern/subject_targets.php#tutor';
	echo '<form action="'.$form_action.'" method="get">';
	echo '<b>Tutor:</b> <select name="tutor" onchange="this.form.submit()">';
	echo '<option value="">--Select Tutor--</option>';
	foreach ($tutor_list as $key => $value) {
		if ($tutor != 0 && $key == $tutor) {
			echo '<option value="'.$key.'" selected="selected">'.$value.'</option>';
		} else {
			echo '<option value="'.$key.'">'.$value.'</option>';
		}
	}
	echo '</select>';
	echo '<input type="hidden" name="courseid" value="'.$courseid.'" />';
	echo '<input type="hidden" name="userid" value="'.$userid.'" />';
	echo '</form>';
	
	echo '<br />';
	
	// If no tutor has been selected show message.
	if ($tutor == 0) {
		echo '<p><b>Select a Tutor to view their targets.</b></p>';
	} else {
		// Get target completion details for teacher
		$tutor_name = (isset($tutor_list[$tutor])) ? $tutor_list[$tutor] : '';
		if ($tutor_name != '') {	
				
			$query = sprintf("SELECT DISTINCT module_code FROM mdl_module_complete WHERE tutor_name = '%s' AND term = %1d AND academic_year = %4d AND module_code != 'Tutorial' ORDER BY module_code",
				$tutor_list[$tutor],
				$attpunc->current_term_no,
				$attpunc->academic_year_4digit
			);
			
			if ($results = get_records_sql($query)) {
				$tutor_modules = array();
				$i = 0;
				foreach ($results as $result) {
					$tutor_modules[$i]['module'] = $result->module_code;
					// Annoying query, going to slow things down :( - We need to get the module description
					$tutor_modules[$i]['description'] = $attpunc->getModuleDesc($result->module_code);
					
					// If description blank it doesn't exist in EBS to unset it from the array
					if ($tutor_modules[$i]['description'] == '') {
						unset($tutor_modules[$i]);
					}
					$i++;
				}

				foreach ($tutor_modules as $key => $value) {
					$query = sprintf("SELECT SUM(complete) AS complete, COUNT(complete)AS total FROM mdl_module_complete WHERE term = %1d AND academic_year = %4d AND module_code = '%s'", 
                        $attpunc->current_term_no,
                        $attpunc->academic_year_4digit,
                        $tutor_modules[$key]['module']
                    );
					$counts = get_records_sql($query);
					foreach ($counts as $count) {
						$complete = $count->complete;
						$total = $count->total;
					}
					$tutor_modules[$key]['complete'] = $complete;
					$tutor_modules[$key]['total'] = $total;
				}
			}
			// If tutor_modules not blank: show in table
			if (count($tutor_modules) > 0) {
				$table = "<table id=\"teacher_targets\">\n";
				$table .= "<tr><th>Module code</th><th>Module Name</th><th class=\"center\">Complete</th><th class=\"center\">&nbsp;</th></tr>\n";
				$total_complete = 0;
				$total_total = 0;
				$n = 1;
				foreach ($tutor_modules as $datum) {
					$row_class = ($n % 2 == 0) ? ' class="r1"' : ' class="r0"';
					$table .= "<tr$row_class>\n";
					$table .= '<td><span title="'.$n.'">'.$datum['module'] . '</span></td>';
					$table .= '<td>'.$datum['description'] . '</td>';
					//$table .= '<td>'.$tutor_list[$tutor]. '</td>';
					$table .= '<td class="center">'.$datum['complete'] . '/'.$datum['total'] . '</td>';
					// Calculate percent complete
					$perc = round((($datum['complete'] / $datum['total']) * 100),0);
					$table .= '<td class="center">'.$perc.'%</td>';
					$table .= "</tr>\n";
					$total_complete += $datum['complete'];
					$total_total += $datum['total'];
					$n++;
				}
				$tot_perc = round((($total_complete / $total_total) * 100),0);
				$table .= '<tr><th>&nbsp;</th><th>&nbsp;</th><th class="center">'.$total_complete.'/'.$total_total.'</th><th class="center">'.$tot_perc.'%</th></tr>';
				$table .= '</table>';

				echo $table;
			}
			
		} else {
			echo '<p><b>Selected tutor does not exist</b></p>';
		}
	}
	
    echo '</div>';

    $footer = '';

	if ($USER->id != $user->id){
		require_capability('mod/ilpconcern:view', $context);
	}
	
    print_footer($footer);	

?>
