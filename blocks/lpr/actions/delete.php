<?php
/**
 * Deletes a Learner Progress Review.
 *
 * Can only be called by hitting 'cancel' on the summmary page.
 *
 * @copyright &copy; 2009 University of London Computer Centre
 * @author http://www.ulcc.ac.uk, http://moodle.ulcc.ac.uk
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package LPR
 * @version 1.0
 */

// initialise moodle
require_once('../../../config.php');

// using these globals
global $SITE, $CFG, $USER;

// include the permissions check
require_once("{$CFG->dirroot}/blocks/lpr/access_content.php");

if(!$can_view) {
    error("You do not have permission to view LPRs");
}

if(!$can_write) {
    error("You do not have permission to edit LPRs");
}

// include the LPR databse library
require_once("{$CFG->dirroot}/blocks/lpr/models/block_lpr_db.php");

// fetch the mandatory LPR id
$id = required_param('id', PARAM_INT);

// fetch the optional url param
$url = optional_param('url', $CFG->wwwroot);

// instantiate the lpr db wrapper
$lpr_db = new block_lpr_db();

// nkowald - 2011-10-11 - We also need to update mdl_module_complete if deleting module means no longer complete
if ($lprs = get_records('block_lpr', 'id', $id)) {
	foreach ($lprs as $lpr) {
		$learner_id = $lpr->learner_id;
		$term_id = $lpr->term_id;
	}
	$term = $lpr_db->get_term_by_id($term_id);
	
	// now get modules
	$modules = get_records('block_lpr_mis_modules', 'lpr_id', $id);
	foreach ($modules as $module) {
		$module_code = $module->module_code;
		if ($module_code != '') {
			// Finally update mdl_module_complete resetting completeness
			$lpr_db->update_module_complete($module_code, $term->term_code, $term->ac_year_code, $learner_id, '0');
		}
	}
}

// delete the lpr record
$lpr_db->delete_lpr($id);

// because Moodle doesn't enforce foreign keys, we can't cascade the delete, so
// we need to delete from the related tables manually
$lpr_db->delete_attendances($id);
$lpr_db->delete_indicator_answers($id);

// Now check if any modules are actually complete for this user and complete them.
$query = "SELECT  m.id as mcid
			FROM  mdl_terms AS t,
				  mdl_module_complete AS m,
				  mdl_block_lpr_mis_modules AS lpm,
				  mdl_block_lpr AS lp
        WHERE m.mdl_student_id = ".$learner_id."
		    AND m.mdl_student_id = lp.learner_id
			AND t.ac_year_code = m.academic_year
			AND lp.id = lpm.lpr_id
			AND	lp.term_id = t.id
			AND lpm.module_code = m.module_code
			AND t.term_code = m.term
			AND	lpm.selected = 1
			AND t.ac_year_code = '".$term->ac_year_code."'
			AND m.complete = 0
			GROUP BY mcid";
			
if ($non_completes = get_records_sql($query)) {
	$mcids = array();
	foreach ($non_completes as $mcid) {
		$mcids[] = $mcid->mcid;
	}
	if (count($mcids) > 0) {
		$mcid_list = implode(',', $mcids);
		$query = "UPDATE mdl_module_complete SET complete = 1 WHERE id IN ($mcid_list)";
		$success = execute_sql($query);
	}
}

// redirect back to the webroot
header("Location: {$url}");
?>