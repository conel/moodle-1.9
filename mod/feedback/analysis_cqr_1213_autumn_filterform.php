<?php

$search_course_names = optional_param('search_course_names', '', PARAM_CLEAN);
$search_levels = optional_param('search_levels', '', PARAM_CLEAN);
$search_curriculum_areas = optional_param('multichoice_1458', '', PARAM_INT);
$search_schools = optional_param('multichoice_1457', '', PARAM_INT);
$search_directorates = optional_param('multichoice_1456', 0, PARAM_INT);

?>	

<table width="100%" class="generalbox" <?php echo $hide_filterform?'style="display:none"':''; ?>><form name="faszfejanko" method="POST">
<input type="hidden" name="sesskey" value="<?php echo $USER->sesskey; ?>" /><input type="hidden" name="id" value="<?php echo $id; ?>" />
<tr><td>Name of Course</td><td><input type="text" maxlength="100" size="100" name="search_course_names" value="<?php echo $search_course_names; ?>"></td></tr>
<tr><td>Course Code / Level</td><td><input type="text" maxlength="80" size="80" name="search_levels" value="<?php echo $search_levels; ?>"></td></tr>
<tr><?php print_feedback_item('Curriculum Area', 4, $search_curriculum_areas, $feedback);?></tr>
<tr><?php print_feedback_item('School', 5, $search_schools, $feedback);?></tr>			
<tr><?php print_feedback_item('Directorate', 6, $search_directorates, $feedback);?></tr>
<tr><td colspan="2"><div class="form-buttons"><div class="singlebutton"><input type="submit" onclick="this.form.action='analysis_cqr_1213_autumn.php';this.form.submit();return false;" value="Filter"/></div></div></td></tr>
<tr><td colspan="2"><div class="form-buttons"><div class="singlebutton"><input type="submit" onclick="this.form.action='analysis_to_excel_cqr.php';this.form.submit();return false;" value="<?php echo get_string('export_to_excel', 'feedback'); ?>"/></div></div></td></tr>
</form></table>

<?php

if($filterform_nofilter) {}
else {
	if($search_course_names!='') {
		$cp = get_records_select('feedback_value', "item = 1410 AND value = '$search_course_names'", "completed", "completed");
		if(is_array($cp)) $cp = array_keys($cp);
		else $cp = array();
	}

	if($search_levels!='') {
		$cp1 = get_records_select('feedback_value', "item = 1455 AND value = '$search_levels'", "completed", "completed");
		if(is_array($cp1)) $cp1 = array_keys($cp1);
		else $cp1 = array();
		if(isset($cp)) $cp = array_intersect($cp, $cp1);
		else $cp = $cp1;
	}

	if($search_curriculum_areas!='') {	
		$cp2 = get_records_select('feedback_value', "item = 1458 AND value = '$search_curriculum_areas'", "completed", "completed");
		if(is_array($cp2)) $cp2 = array_keys($cp2);
		else $cp2 = array();
		if(isset($cp)) $cp = array_intersect($cp, $cp2);
		else $cp = $cp2;
		
	}

	if($search_schools!='') {	
		$cp3 = get_records_select('feedback_value', "item = 1457 AND value = '$search_schools'", "completed", "completed");
		if(is_array($cp3)) $cp3 = array_keys($cp3);
		else $cp3 = array();
		if(isset($cp)) $cp = array_intersect($cp, $cp3);
		else $cp = $cp3;
	}

	if($search_directorates!='') {
		$cp4 = get_records_select('feedback_value', "item = 1456 AND value = '$search_directorates'", "completed", "completed");
		if(is_array($cp4)) $cp4 = array_keys($cp4);
		else $cp4 = array();
		if(isset($cp)) $cp = array_intersect($cp, $cp4);
		else $cp = $cp4;
	}
}

?>
