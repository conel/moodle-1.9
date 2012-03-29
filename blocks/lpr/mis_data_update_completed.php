<?php
// initialise moodle
require_once('../../config.php');

global $CFG;

// include the necessary DB library
require_once ($CFG->dirroot.'/lib/adodb/adodb.inc.php');

function get_non_updated_records() {
	global $CFG;

	$sql = "SELECT  m.id as user_id
			FROM 	mdl_terms AS t,
					mdl_module_complete AS m,
					mdl_block_lpr_mis_modules AS lpm,
					mdl_block_lpr AS lp
			WHERE 	lp.id = lpm.lpr_id
			AND		lp.term_id = t.id
			AND 	lpm.module_code = m.module_code
			AND 	t.term_code = m.term
			AND 	t.ac_year_code = m.academic_year
			AND		lpm.selected = 1
			GROUP BY user_id";

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

	$userid = array();
	
	foreach ($records	as $r) {
		$userid[]	=	$r->user_id;
	}
	
	$module_ids	=	implode(', ',$userid);
	
	update_module_records($module_ids);
	
	
} else  {
	echo "No records to update";
}






?>