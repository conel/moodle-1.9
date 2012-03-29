<?php

/**
 * Upgrade class for the Assessment Manager.
 *
 * Sometimes, changes between versions involve alterations to database
 * structures and other major things that may break installations.
 *
 * The upgrade function in this file will attempt to perform all the necessary
 * actions to upgrade your older installtion to the current version.
 *
 * @copyright &copy; 2009-2010 University of London Computer Centre
 * @author http://www.ulcc.ac.uk, http://moodle.ulcc.ac.uk
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package AssMgr
 * @version 2.0
 */
ini_set('max_execution_time', 0);
ini_set('memory_limit', '2000M');

// remove this when testing is complete
$path_to_config = dirname($_SERVER['SCRIPT_FILENAME']).'/../../config.php';
while (($collapsed = preg_replace('|/[^/]+/\.\./|','/',$path_to_config,1)) !== $path_to_config) {
    $path_to_config = $collapsed;
}
require_once('../../../config.php');

global $USER, $CFG, $PARSER,$COURSE, $db;

// include the necessary libraries
require_once($CFG->libdir.'/ddllib.php');
require_once($CFG->libdir.'/grade/grade_object.php');
require_once($CFG->libdir.'/grade/grade_category.php');
require_once($CFG->libdir.'/grade/grade_item.php');
require_once($CFG->libdir.'/grade/grade_grade.php');
require_once($CFG->libdir.'/grade/grade_scale.php');
require_once($CFG->libdir.'/grade/grade_outcome.php');
require_once($CFG->libdir.'/gradelib.php');
require_once($CFG->libdir.'/moodlelib.php');


// include the dependent functions for this upgrade
require_once($CFG->dirroot.'/blocks/assmgr/upgrade_lib.php');
require_once($CFG->dirroot.'/blocks/assmgr/db/assmgr_db.php');
require_once($CFG->dirroot.'/blocks/assmgr/lib.php');

$results = array();

// get the current time for a performance metric
$current_time = time();

// instantiate the db
$dbc = new assmgr_db();

file_var_crap("start stage 4");

/********
 * Claim table
 ********/

// delete claims records where type = 2 - deletes all assessor outcome records
delete_records('block_assessmgr_claim', 'type', 2);

// delete records where evid_sub_id = 0 - deletes all assessor portfolio records
delete_records('block_assessmgr_claim', 'evid_sub_id', 0);

// finish off the conversion of the claim table
$claim_table = new XMLDBTable('block_assessmgr_claim');

$claim_table_id = new XMLDBField('id');
$claim_table_id->setAttributes(XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null, null, null);
$results[] = change_field_type($claim_table, $claim_table_id);

$claim_evid_sub_id = new XMLDBField('evid_sub_id');
$claim_evid_sub_id->setAttributes(XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, 0);
$results[] = change_field_type($claim_table, $claim_evid_sub_id);

$claim_outcome_id = new XMLDBField('outcome_id');
$claim_outcome_id->setAttributes(XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, 0);
$results[] = change_field_type($claim_table, $claim_outcome_id);

// create a time created field for the folder table
$claim_timecreated = new XMLDBField('timecreated');
$claim_timecreated->setAttributes(XMLDB_TYPE_INTEGER,10,XMLDB_UNSIGNED,XMLDB_NOTNULL,null,null,null,$current_time);
$results[] = add_field($claim_table, $claim_timecreated);

// create a time modified field for the folder table
$claim_timemodified = new XMLDBField('timemodified');
$claim_timemodified->setAttributes(XMLDB_TYPE_INTEGER,10,XMLDB_UNSIGNED,XMLDB_NOTNULL,null,null,null,$current_time);
$results[] = add_field($claim_table, $claim_timemodified);

// rename the evid_sub_id field to submission_id
$results[] = rename_field($claim_table, $claim_evid_sub_id, 'submission_id', false, false);

// drop the course_id field
$claim_table_sub = new XMLDBField('sub_id');
drop_field($claim_table, $claim_table_sub);

// drop the grade_id field
$claim_table_grade = new XMLDBField('grade_id');
drop_field($claim_table, $claim_table_grade);

// drop the grade_id field
$claim_table_type = new XMLDBField('type');
drop_field($claim_table, $claim_table_type);

// rename the claim table
$results[] = rename_table($claim_table, 'block_assmgr_claim', false, false);

file_var_crap('changed claim table');

// drop the sub_hist_table table
$sub_hist_table = new XMLDBTable('block_assessmgr_sub_hist');
$result[] = drop_table($sub_hist_table);

file_var_crap('dropped sub_hist table');

/************************
 * Change course bindings to new assmgr
 *************************/
// get the block id of assessmgr
$oldblock = get_record('block', 'name', 'assessmgr');
$newblock = get_record('block', 'name', 'assmgr');

if (!empty($newblock)) {
    execute_sql(
        "UPDATE mdl_block_instance
         SET blockid = {$newblock->id}, configdata = ''
         WHERE blockid = {$oldblock->id}"
    );
}

file_var_crap('changed block bindings');

/*************************************************
 * Tidy up bad data so constraints can be enforced
 ************************************************/

file_var_crap('starting delete of records that fail FK checks');

// delete records with bad foreign keys
execute_sql(
    "DELETE FROM mdl_block_assmgr_portfolio
     WHERE course_id NOT IN (SELECT id FROM mdl_course)"
);

execute_sql(
    "DELETE FROM mdl_block_assmgr_submission
     WHERE portfolio_id NOT IN (SELECT id FROM mdl_block_assmgr_portfolio)"
);

execute_sql(
    "DELETE FROM mdl_block_assmgr_submission
     WHERE evidence_id NOT IN (SELECT id FROM mdl_block_assmgr_evidence)"
);

execute_sql(
    "DELETE FROM mdl_block_assmgr_claim
     WHERE submission_id NOT IN (SELECT id FROM mdl_block_assmgr_submission)"
);

execute_sql(
    "DELETE FROM mdl_block_assmgr_claim
     WHERE outcome_id NOT IN (SELECT id FROM mdl_grade_outcomes)"
);

execute_sql(
    "DELETE FROM mdl_block_assmgr_evidence
     WHERE candidate_id NOT IN (SELECT id FROM mdl_user)"
);

execute_sql(
    "DELETE FROM mdl_block_assmgr_resource
     WHERE evidence_id NOT IN (SELECT id FROM mdl_block_assmgr_evidence)"
);

execute_sql(
    "DELETE FROM mdl_block_assmgr_folder
     WHERE candidate_id NOT IN (SELECT id FROM mdl_user)"
);

execute_sql(
    "DELETE FROM mdl_block_assmgr_folder
     WHERE candidate_id NOT IN (SELECT id FROM mdl_user)"
);

execute_sql(
    "DELETE FROM mdl_block_assmgr_sub_evid_type
     WHERE submission_id NOT IN (SELECT id FROM mdl_block_assmgr_submission)"
);

execute_sql(
    "DELETE FROM mdl_block_assmgr_sub_evid_type
     WHERE evidence_type_id NOT IN (SELECT id FROM mdl_block_assmgr_evidence_type)"
);

// fetch duplicate evidence names for the same candidate
$evid_duplicates = get_records_sql(
    "SELECT GROUP_CONCAT(id ORDER BY id ASC SEPARATOR ',') AS duplicates
     FROM mdl_block_assmgr_evidence
     GROUP BY name, candidate_id
     HAVING COUNT(id) > 1"
);

execute_sql("DELETE FROM mdl_block_assmgr_log");

file_var_crap('finished delete of bad records');

// increment the evidence names
if(!empty($evid_duplicates)) {
    foreach($evid_duplicates as $duplicates) {
        $duplicates = explode(',', $duplicates->duplicates);
        foreach($duplicates as $i => $evidence_id) {
            execute_sql(
                "UPDATE mdl_block_assmgr_evidence
                 SET name = CONCAT(name,' (".($i+1).")')
                 WHERE id = {$evidence_id}"
            );
        }
    }
}

file_var_crap('incremented evidence names');

// fetch duplicate folder names for the same candidate
$fold_duplicates = get_records_sql(
    "SELECT GROUP_CONCAT(id ORDER BY id ASC SEPARATOR ',') AS duplicates
     FROM mdl_block_assmgr_folder
     GROUP BY name, candidate_id
     HAVING COUNT(id) > 1"
);

// increment the evidence names
if(!empty($fold_duplicates)) {
    foreach($fold_duplicates as $duplicates) {
        $duplicates = explode(',', $duplicates->duplicates);
        foreach($duplicates as $i => $folder_id) {
            execute_sql(
                "UPDATE mdl_block_assmgr_folder
                 SET name = CONCAT(name,' (".($i+1).")')
                 WHERE id = {$folder_id}"
            );
        }
    }
}

file_var_crap('incremented folder names');

// get all the candidates with evidence in bad folders
$candidates = get_records_sql(
    "SELECT DISTINCT candidate_id
     FROM mdl_block_assmgr_evidence
     WHERE folder_id NOT IN (SELECT id FROM mdl_block_assmgr_folder)"
);

$folders = array();

// create default folders for all the candidates and move their evidence into it
if(!empty($candidates)) {
    foreach($candidates as $candidate) {
        // create a default folder
        $folder = new object();
        $folder->name = 'New Folder';
        $folder->candidate_id = $candidate->candidate_id;
        $folder_id = insert_record('block_assmgr_folder', $folder);

        // move all the evidence
        execute_sql(
            "UPDATE mdl_block_assmgr_evidence
             SET folder_id = {$folder_id}
             WHERE candidate_id = {$candidate->candidate_id}
               AND folder_id NOT IN (SELECT id FROM mdl_block_assmgr_folder)"
        );
    }
}

file_var_crap('moved all evidence into real folders');

// get all the duplicate submissions
$submissions = get_records_sql(
    "SELECT GROUP_CONCAT(id ORDER BY id ASC SEPARATOR ',') AS duplicates
     FROM mdl_block_assmgr_submission
     GROUP BY evidence_id, portfolio_id
     HAVING COUNT(id) > 1"
);

// delete all but the most recent one
if(!empty($submissions)) {
    foreach($submissions as $submission) {
        $duplicates = explode(',', $submission->duplicates);
        // exclude the most recent
        array_pop($duplicates);
        // delete the rest
        foreach($duplicates as $submission_id) {
            recursively_delete_submission($submission_id);
        }
    }
}

file_var_crap('resolved duplicate submissions');

// fix bad parent references for folders
execute_sql(
    "UPDATE mdl_block_assmgr_folder
     SET folder_id = NULL
     WHERE folder_id = 0"
);

// get all the orphaned grade items
$gradeitems = get_records_sql(
    "SELECT id
     FROM mdl_grade_items
     WHERE itemtype = 'mod'
       AND itemmodule = 'assessmgr'"
);

file_var_crap('found '.count($gradeitems).' orphaned old grade items');

$counter = 0;

// delete the orphaned item, item history, grade and grade history
if(!empty($gradeitems)) {
    foreach($gradeitems as $gradeitem) {
        execute_sql(
            "DELETE FROM mdl_grade_items
             WHERE id = {$gradeitem->id}"
        );
        execute_sql(
            "DELETE FROM mdl_grade_items_history
             WHERE oldid = {$gradeitem->id}"
        );
        execute_sql(
            "DELETE FROM mdl_grade_grades
             WHERE itemid = {$gradeitem->id}"
        );
        execute_sql(
            "DELETE FROM mdl_grade_grades_history
             WHERE itemid = {$gradeitem->id}"
        );

        $counter++;

        if($counter%1000 == 0) {
            file_var_crap("Removed $counter orphaned grade items...");
        }
    }
}

file_var_crap('deleted grade orphans');

/************************************************
 *  delete duplicate grades
 */

while ($dup_grades = find_duplicate_grades()) {
    file_var_crap(count($dup_grades), 'found duplicates');
    $duplicates_array = array();
     foreach ( $dup_grades as $dup) {
         array_push($duplicates_array,$dup->id);
     }
     delete_grades($duplicates_array);
}

file_var_crap('deleted grade duplicates');

/*******************************************************
 * Delete duplicate submissions
 *******************************************************/

while ($dup_submissions = find_duplicate_submissions()) {
 $duplicates_array = array();
 file_var_crap($dup_submissions);
 if (!empty($dup_submissions)) {
     foreach ( $dup_submissions as $sub) {
         array_push($duplicates_array,$sub->id);
     }
     delete_submisssion_grades($duplicates_array);
     delete_submisssion_claims($duplicates_array);
     delete_submisssion_sub_ev_type($duplicates_array);
     delete_submisssions($duplicates_array);
 }
}

file_var_crap('deleted duplicate submissions');

 /*******************************************************
  * Delete duplicate claims
  *******************************************************/
while ($dup_claims = find_duplicate_claims()) {
     $duplicates_array = array();
     if (!empty($dup_claims)) {
         foreach ( $dup_claims as $claim) {
             array_push($duplicates_array,$claim->id);
         }
         delete_claims($duplicates_array);
     }
 }

file_var_crap('deleted duplicate claims');

//delete grades without outcomes
execute_sql(
    "DELETE FROM mdl_block_assmgr_grade
     WHERE (outcome_id NOT IN (SELECT id FROM mdl_grade_outcomes)
     AND outcome_id IS NOT NULL) OR (outcome_id IS NULL AND feedback IS NULL)"
);

//update grades without users
execute_sql(
    "UPDATE  mdl_block_assmgr_grade
     SET creator_id = 2
     WHERE creator_id NOT IN (SELECT id FROM mdl_user)"
);

//update evidence without users
execute_sql(
    "UPDATE mdl_block_assmgr_evidence
     SET creator_id = candidate_id
     WHERE creator_id NOT IN (SELECT id FROM mdl_user)"
);

//update submissions without users
execute_sql(
    "UPDATE mdl_block_assmgr_submission as sub
     SET sub.creator_id = (SELECT candidate_id FROM mdl_block_assmgr_evidence WHERE id = sub.evidence_id)
     WHERE sub.creator_id NOT IN (SELECT id FROM mdl_user)"
);

//update sub_evid_types without creators
execute_sql(
    "UPDATE  mdl_block_assmgr_sub_evid_type
     SET creator_id = 2
     WHERE creator_id NOT IN (SELECT id FROM mdl_user)"
);

file_var_crap('fixed more FK errors');

/************************
 * Set table engine to innodb
 *************************/
$tables_list = get_records_sql("SHOW TABLE STATUS WHERE ENGINE != 'INNODB'");

file_var_crap('about to alter '.count($tables_list).' table engines');

foreach ($tables_list as $table => $tab_object) {
    execute_sql("ALTER TABLE {$table} ENGINE=INNODB");
}

file_var_crap('altered table engines');

$tables_list = get_records_sql("SHOW TABLES");

file_var_crap('about to alter '.count($tables_list).' character sets');

foreach ($tables_list as $table => $tab_object) {
    execute_sql("ALTER TABLE {$table} disable keys");
    execute_sql("ALTER TABLE {$table} CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci");
    execute_sql("ALTER TABLE {$table} enable keys");
}

file_var_crap('altered character sets');


/*************************************************
 * Foreign Keys and Unique Keys
 ************************************************/

file_var_crap("fk evidence table");
// evidence table
$evidence_table = new XMLDBTable("block_assmgr_evidence");

$key = new XMLDBKey("blocassmevid_namcan_uk");
$key->setAttributes(XMLDB_KEY_UNIQUE, array("name","candidate_id"));
add_key($evidence_table, $key, false);

$key = new XMLDBKey("blocassmevid_can_ix");
$key->setAttributes(XMLDB_KEY_FOREIGN, array("candidate_id"), "user", array("id"));
add_key($evidence_table, $key, false);

$key = new XMLDBKey("blocassmevid_cre_ix");
$key->setAttributes(XMLDB_KEY_FOREIGN, array("creator_id"), "user", array("id"));
add_key($evidence_table, $key, false);

$key = new XMLDBKey("blocassmevid_fol_ix");
$key->setAttributes(XMLDB_KEY_FOREIGN, array("folder_id"), "block_assmgr_folder", array("id"));
add_key($evidence_table, $key, false);

file_var_crap("fk assmgr table");
// assmgr table
$assmgr_table = new XMLDBTable("block_assmgr_submission");

$key = new XMLDBKey("blocassm_porevi_uk");
$key->setAttributes(XMLDB_KEY_UNIQUE, array("portfolio_id","evidence_id"));
add_key($assmgr_table, $key, false);

$key = new XMLDBKey("blocassm_por_ix");
$key->setAttributes(XMLDB_KEY_FOREIGN, array("portfolio_id"), "block_assmgr_portfolio", array("id"));
add_key($assmgr_table, $key, false);

$key = new XMLDBKey("blocassm_evi_ix");
$key->setAttributes(XMLDB_KEY_FOREIGN, array("evidence_id"), "block_assmgr_evidence", array("id"));
add_key($assmgr_table, $key, false);

$key = new XMLDBKey("blocassm_cre_ix");
$key->setAttributes(XMLDB_KEY_FOREIGN, array("creator_id"), "user", array("id"));
add_key($assmgr_table, $key, false);

file_var_crap("fk claim table");
// claim table
$claim_table = new XMLDBTable("block_assmgr_claim");

$key = new XMLDBKey("blocassm_porevi_uk");
$key->setAttributes(XMLDB_KEY_UNIQUE, array("submission_id","outcome_id"));
add_key($claim_table, $key, false);

$key = new XMLDBKey("blocassmclai_out_fk");
$key->setAttributes(XMLDB_KEY_FOREIGN, array("outcome_id"), "grade_outcomes", array("id"));
add_key($claim_table, $key, false);

$key = new XMLDBKey("blocassmclai_sub_fk");
$key->setAttributes(XMLDB_KEY_FOREIGN, array("submission_id"), "block_assmgr_submission", array("id"));
add_key($claim_table, $key, false);

file_var_crap("fk confirmation table");
// confirmation table
$confirmation_table = new XMLDBTable("block_assmgr_confirmation");

$key = new XMLDBKey("blocassmconf_evi_uk");
$key->setAttributes(XMLDB_KEY_UNIQUE, array("evidence_id"));
add_key($confirmation_table, $key, false);

$key = new XMLDBKey("blocassmconf_cre_fk");
$key->setAttributes(XMLDB_KEY_FOREIGN, array("creator_id"), "user", array("id"));
add_key($confirmation_table, $key, false);

$key = new XMLDBKey("blocassmconf_evi_fk");
$key->setAttributes(XMLDB_KEY_FOREIGN, array("evidence_id"), "block_assmgr_evidence", array("id"));
add_key($confirmation_table, $key, false);

file_var_crap("fk evidence type table");
// evidence type table
$evidence_t_table = new XMLDBTable("block_assmgr_evidence_type");

$key = new XMLDBKey("blocassmevidtype_nam_uk");
$key->setAttributes(XMLDB_KEY_UNIQUE, array("name"));
add_key($evidence_t_table, $key, false);

file_var_crap("fk feedback table");
// feedback table
$feedback_table = new XMLDBTable("block_assmgr_feedback");

$key = new XMLDBKey("blocassmfeed_sub_fk");
$key->setAttributes(XMLDB_KEY_FOREIGN, array("submission_id"), "block_assmgr_submission", array("id"));
add_key($feedback_table, $key, false);

file_var_crap("fk folder table");
// folder table
$folder_table = new XMLDBTable("block_assmgr_folder");

$key = new XMLDBKey("blocassmevidtype_nam_uk");
$key->setAttributes(XMLDB_KEY_UNIQUE, array("name","candidate_id"));
add_key($folder_table, $key, false);

file_var_crap('line 908');

$key = new XMLDBKey("blocassmfold_can_fk");
$key->setAttributes(XMLDB_KEY_FOREIGN, array("candidate_id"), "user", array("id"));
add_key($folder_table, $key, false);

$key = new XMLDBKey("blocassmfold_fol_fk");
$key->setAttributes(XMLDB_KEY_FOREIGN, array("folder_id"), "block_assmgr_folder", array("id"));
add_key($folder_table, $key, false);

file_var_crap("fk grade cat desc table");
// grade category descriptions
$grade_cat_desc_table = new XMLDBTable("block_assmgr_grade_cat_desc");

$key = new XMLDBKey("blocassmgradcatdesc_gra_fk");
$key->setAttributes(XMLDB_KEY_FOREIGN_UNIQUE, array("grade_category_id"), "grade_categories", array("id"));
add_key($grade_cat_desc_table, $key, false);

file_var_crap("fk lock table");
// lock table
$lock_table = new XMLDBTable("block_assmgr_lock");

$key = new XMLDBKey("blocassmgradcatdesc_gra_fk");
$key->setAttributes(XMLDB_KEY_FOREIGN_UNIQUE, array("portfolio_id"), "block_assmgr_portfolio", array("id"));
add_key($lock_table, $key, false);

$key = new XMLDBKey("blocassmlock_cre_fk");
$key->setAttributes(XMLDB_KEY_FOREIGN, array("creator_id"), "user", array("id"));
add_key($lock_table, $key, false);

file_var_crap("fk log table");
// log table
$log_table = new XMLDBTable("block_assmgr_log");

$key = new XMLDBKey("blocassmlog_can_fk");
$key->setAttributes(XMLDB_KEY_FOREIGN, array("candidate_id"), "user", array("id"));
add_key($log_table, $key, false);

$key = new XMLDBKey("blocassmlock_cre_fk");
$key->setAttributes(XMLDB_KEY_FOREIGN, array("creator_id"), "user", array("id"));
add_key($log_table, $key, false);

$key = new XMLDBKey("blocassmlog_cou_fk");
$key->setAttributes(XMLDB_KEY_FOREIGN, array("course_id"), "course", array("id"));
add_key($log_table, $key, false);

file_var_crap("fk portfolio table");
// portfolio table
$port_table = new XMLDBTable("block_assmgr_portfolio");

$key = new XMLDBKey("blocassmport_cancou_uk");
$key->setAttributes(XMLDB_KEY_UNIQUE, array("course_id","candidate_id"));
add_key($port_table, $key, false);

$key = new XMLDBKey("blocassmlog_can_fk");
$key->setAttributes(XMLDB_KEY_FOREIGN, array("candidate_id"), "user", array("id"));
add_key($port_table, $key, false);

$key = new XMLDBKey("blocassmlog_cou_fk");
$key->setAttributes(XMLDB_KEY_FOREIGN, array("course_id"), "course", array("id"));
add_key($port_table, $key, false);

file_var_crap("fk resource table");
// resource table
$res_table = new XMLDBTable("block_assmgr_resource");

$key = new XMLDBKey("blocassmreso_evi_fk");
$key->setAttributes(XMLDB_KEY_FOREIGN_UNIQUE, array("evidence_id"), "block_assmgr_evidence", array("id"));
add_key($res_table, $key, false);

$key = new XMLDBKey("blocassmreso_res_fk");
$key->setAttributes(XMLDB_KEY_FOREIGN, array("resource_type_id"), "block_assmgr_resource_type", array("id"));
add_key($res_table, $key, false);

file_var_crap("fk resource type table");
// resource type table
$res_type_table = new XMLDBTable("block_assmgr_resource_type");

$key = new XMLDBKey("blocassmevidtype_nam_uk");
$key->setAttributes(XMLDB_KEY_UNIQUE, array("name"));
add_key($res_type_table, $key, false);

file_var_crap("fk sub evid table");
// sub evid type table
$sub_evid_type_table = new XMLDBTable("block_assmgr_sub_evid_type");

$key = new XMLDBKey("blocassmsubevidtype_sub_uk");
$key->setAttributes(XMLDB_KEY_UNIQUE, array("submission_id","evidence_type_id","creator_id"));
add_key($sub_evid_type_table, $key, false);

$key = new XMLDBKey("blocassmsubevidtype_cre_fk");
$key->setAttributes(XMLDB_KEY_FOREIGN, array("creator_id"), "user", array("id"));
add_key($sub_evid_type_table, $key, false);

$key = new XMLDBKey("blocassmsubevidtype_evi_fk");
$key->setAttributes(XMLDB_KEY_FOREIGN, array("evidence_type_id"), "block_assmgr_evidence_type", array("id"));
add_key($sub_evid_type_table, $key, false);

$key = new XMLDBKey("blocassmsubevidtype_sub_fk");
$key->setAttributes(XMLDB_KEY_FOREIGN, array("submission_id"), "block_assmgr_submission", array("id"));
add_key($sub_evid_type_table, $key, false);

file_var_crap("fk verification table");
// verification table
$verification_table = new XMLDBTable("block_assmgr_verification");

$key = new XMLDBKey("blocassmveri_ass_fk");
$key->setAttributes(XMLDB_KEY_FOREIGN, array("assessor_id"), "user", array("id"));
add_key($verification_table, $key, false);

$key = new XMLDBKey("blocassmveri_cat_fk");
$key->setAttributes(XMLDB_KEY_FOREIGN, array("category_id"), "course_categories", array("id"));
add_key($verification_table, $key, false);

$key = new XMLDBKey("blocassmveri_cou_fk");
$key->setAttributes(XMLDB_KEY_FOREIGN, array("course_id"), "course", array("id"));
add_key($verification_table, $key, false);

$key = new XMLDBKey("blocassmveri_ver_fk");
$key->setAttributes(XMLDB_KEY_FOREIGN, array("verifier_id"), "user", array("id"));
add_key($verification_table, $key, false);

file_var_crap("fk verfiy table");
// verify form table
$verify_form_table = new XMLDBTable("block_assmgr_verify_form");

$key = new XMLDBKey("blocassmveriform_por_fk");
$key->setAttributes(XMLDB_KEY_FOREIGN, array("portfolio_id"), "block_assmgr_portfolio", array("id"));
add_key($verify_form_table, $key, false);

$key = new XMLDBKey("blocassmveriform_sub_fk");
$key->setAttributes(XMLDB_KEY_FOREIGN, array("submission_id"), "block_assmgr_submission", array("id"));
add_key($verify_form_table, $key, false);

$key = new XMLDBKey("blocassmveriform_ver_fk");
$key->setAttributes(XMLDB_KEY_FOREIGN, array("verification_id"), "block_assmgr_verification", array("id"));
add_key($verify_form_table, $key, false);


$calendar_event_table = new XMLDBTable("block_assmgr_calendar_event");

$key = new XMLDBKey("blocassmevent_course_fk");
$key->setAttributes(XMLDB_KEY_FOREIGN_UNIQUE, array("event_id"), "event", array("id"));
add_key($calendar_event_table, $key, false);

$key = new XMLDBKey("blocassmevent_course_fk");
$key->setAttributes(XMLDB_KEY_FOREIGN, array("course_id"), "course", array("id"));
add_key($calendar_event_table, $key, false);

$key = new XMLDBKey("blocassmevent_user_fk");
$key->setAttributes(XMLDB_KEY_FOREIGN, array("creator_id"), "user", array("id"));
add_key($calendar_event_table, $key, false);


$grade_table = new XMLDBTable("block_assmgr_grade");

$key = new XMLDBKey("submission_id_fk");
$key->setAttributes(XMLDB_KEY_FOREIGN, array("submission_id"), "block_assmgr_submission", array("id"));
add_key($grade_table, $key, false);

$key = new XMLDBKey("creator_id_fk");
$key->setAttributes(XMLDB_KEY_FOREIGN, array("creator_id"), "user", array("id"));
add_key($grade_table, $key, false);

$key = new XMLDBKey("singlegrade");
$key->setAttributes(XMLDB_KEY_UNIQUE, array("submission_id","outcome_id"));
add_key($grade_table, $key, false);

$key = new XMLDBKey("outcome_id_fk");
$key->setAttributes(XMLDB_KEY_FOREIGN, array("outcome_id"), "grade_outcomes", array("id"));
add_key($grade_table, $key, false);

/*************************************************
 * Tidy up grades so that all submission with outcome
 * grades have a grade scale of at least 1
 ************************************************/

file_var_crap('running get_unset_overall_outcome_grades()');

$ports = array();
$unset_outcomes = get_unset_overall_outcome_grades();
foreach ($unset_outcomes as $unset_out) {
    $ports[$unset_out->portfolio_id][] = $unset_out->outcome_id;
}

file_var_crap('found '.count($ports).' portfolios to update their outcomes');

$counter = 0;

foreach ($ports as $portfolio_id => $outcomes ) {
    $p = get_portfolio_by_id($portfolio_id);
    if (!empty($p)) {
        $old_grades = grade_get_grades(
            $p->course_id,
            'mod',
            'block_assmgr',
            null,
            $p->candidate_id
        );

        $grades = array();
        if (!empty($old_grades)) {
            foreach ($old_grades->outcomes as $o_grade) {
                $g = current($o_grade->grades);
                if (empty($g->grade) && in_array($o_grade->itemnumber, $outcomes)) {
                    $grades[$o_grade->itemnumber] = 1.0;
                } else {
                    $grades[$o_grade->itemnumber] = $g->grade;
                }
            }
            grade_update_outcomes(
                'blocks/assmgr',
                $p->course_id,
                'mod',
                'block_assmgr',
                null,
                $p->candidate_id,
                $grades
            );
        }
    }

    $counter++;

    if($counter%50 == 0) {
        file_var_crap("updated $counter portfolios...");
    }
}

file_var_crap('finsihed updating portfolio outcomes');

//sets the grade_pass field to 1.0 of all grade_items that use a scale
//that contains incomplete
update_grade_pass();

file_var_crap('updated gradepass values for all grade items');

/************************************************
 * Update the synchronise field on all automatically imported
 * evidence submissions
 */

$submissions = get_synchronised_submission();

file_var_crap('found '.count($submissions).' automatically added submissions');

if(!empty($submissions)) {
    $submssion_ids = array();

    foreach ($submissions as $sub) {
        array_push($submssion_ids,$sub->submission_id);
    }

    update_submission_field($submssion_ids);
}

file_var_crap('finsihed setting the sync field');

// get the current time for a performance metric
$end_time = time();
$totaltime = $end_time - $current_time;

file_var_crap($totaltime, "operation took...");

file_var_crap('FINISHED EVERYTHING');