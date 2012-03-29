<?php
// initialise moodle

// nkowald - 2011-02-18 - running from cron we need to use this
require_once(dirname(dirname(dirname(dirname(__FILE__)))).'/config.php'); // global moodle config file.
//require_once('../../config.php');

global $CFG;

// include the necessary DB library
require_once ($CFG->dirroot.'/lib/adodb/adodb.inc.php');

function get_non_updated_records() {
	global $CFG;
	
	// nkowald - 2011-10-04 - Cutting this down to academic year, adding complete = 0 so it doesn't have to loop through everything
	$year = date('y');
	$td_year = $year . sprintf('%2d', ($year + 1));
	
	$sql = "SELECT  m.id as mcid
			FROM 	mdl_terms AS t,
					mdl_module_complete AS m,
					mdl_block_lpr_mis_modules AS lpm,
					mdl_block_lpr AS lp 
		    WHERE m.mdl_student_id = lp.learner_id 
			AND t.ac_year_code = m.academic_year
			AND lp.id = lpm.lpr_id
			AND	lp.term_id = t.id
			AND lpm.module_code = m.module_code
			AND t.term_code = m.term
			AND	lpm.selected = 1
			AND t.ac_year_code = '$td_year' 
			AND m.complete = 0 
			GROUP BY mcid";

	return get_records_sql($sql);
}

function update_module_records($module_ids) {
	global $CFG;
	
	$sql 	= 	"UPDATE mdl_module_complete 
				 SET 	complete = 1 
				 WHERE 	id IN  ({$module_ids})";

	execute_sql($sql);
}

$records = get_non_updated_records();

if (!empty($records)) {
	$mcids = array();
	foreach ($records	as $r) {
		$mcids[]	=	$r->mcid;
	}
	$module_ids	=	implode(', ',$mcids);
	update_module_records($module_ids);
} else  {
	echo "No records to update";
}
?>