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
// -----------------------------------------------------------------------------
// --                        CREATE NEW TABLES                                --
// -----------------------------------------------------------------------------

file_var_crap("Started stage 1");

file_var_crap("Creating new tables");

/*******************
 * log table
 *******************/

// create the log table
$log_table = new XMLDBTable('block_assmgr_log');
$log_table->addFieldInfo('id', XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null, null, null);
$log_table->addFieldInfo('creator_id',XMLDB_TYPE_INTEGER,10,XMLDB_UNSIGNED, XMLDB_NOTNULL,null,null,null);
$log_table->addFieldInfo('candidate_id',XMLDB_TYPE_INTEGER,10,XMLDB_UNSIGNED, XMLDB_NOTNULL,null,null,null);
$log_table->addFieldInfo('course_id',XMLDB_TYPE_INTEGER,10,XMLDB_UNSIGNED, null,null,null,null);
$log_table->addFieldInfo('type',XMLDB_TYPE_CHAR,255,null,XMLDB_NOTNULL, null,null,null,null);
$log_table->addFieldInfo('entity',XMLDB_TYPE_CHAR,255,null,XMLDB_NOTNULL, null,null,null,null);
$log_table->addFieldInfo('record_id',XMLDB_TYPE_INTEGER,10,XMLDB_UNSIGNED,XMLDB_NOTNULL, null,null,null,null);
$log_table->addFieldInfo('attribute',XMLDB_TYPE_CHAR,255,null,XMLDB_NOTNULL, null,null,null,null);
$log_table->addFieldInfo('old_value',XMLDB_TYPE_TEXT,'big',null, null,null,null,null);
$log_table->addFieldInfo('new_value',XMLDB_TYPE_TEXT,'big',null, null,null,null,null);
$log_table->addFieldInfo('timecreated',XMLDB_TYPE_INTEGER,10,XMLDB_UNSIGNED,XMLDB_NOTNULL,null,null,null);
$log_table->addFieldInfo('timemodified',XMLDB_TYPE_INTEGER,10,XMLDB_UNSIGNED,XMLDB_NOTNULL,null,null,null);
$log_table->addKeyInfo('primary', XMLDB_KEY_PRIMARY, array('id'));
$results[] = create_table($log_table);

/*******************
 * verification table
 *******************/

// create the verification table
$verification_table = new XMLDBTable('block_assmgr_verification');
$verification_table->addFieldInfo('id', XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null, null, null);
$verification_table->addFieldInfo('verifier_id', XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, null);
$verification_table->addFieldInfo('category_id', XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED, null, null, null, null, null);
$verification_table->addFieldInfo('assessor_id', XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED, null, null, null, null, null);
$verification_table->addFieldInfo('course_id', XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED, null, null, null, null, null);
$verification_table->addFieldInfo('complete', XMLDB_TYPE_INTEGER, 1, XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, 0);
$verification_table->addFieldInfo('timecreated',XMLDB_TYPE_INTEGER,10,XMLDB_UNSIGNED,XMLDB_NOTNULL,null,null,null);
$verification_table->addFieldInfo('timemodified',XMLDB_TYPE_INTEGER,10,XMLDB_UNSIGNED,XMLDB_NOTNULL,null,null,null);
$verification_table->addKeyInfo('primary', XMLDB_KEY_PRIMARY, array('id'));
$results[] = create_table($verification_table);

/*******************
 * verify table
 *******************/

// create the verify form table
$verify_form_table = new XMLDBTable('block_assmgr_verify_form');
$verify_form_table->addFieldInfo('id', XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null, null, null);
$verify_form_table->addFieldInfo('verification_id', XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, null);
$verify_form_table->addFieldInfo('portfolio_id', XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, null);
$verify_form_table->addFieldInfo('submission_id', XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED, null, null, null, null, null);
$verify_form_table->addFieldInfo('accurate', XMLDB_TYPE_INTEGER, 1, XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, null);
$verify_form_table->addFieldInfo('accurate_comment',XMLDB_TYPE_TEXT,'big',null, null,null,null,null);
$verify_form_table->addFieldInfo('constructive', XMLDB_TYPE_INTEGER, 1, XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, null);
$verify_form_table->addFieldInfo('constructive_comment',XMLDB_TYPE_TEXT,'big',null, null,null,null,null);
$verify_form_table->addFieldInfo('needs_amending', XMLDB_TYPE_INTEGER, 1, XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, null);
$verify_form_table->addFieldInfo('amendment_comment',XMLDB_TYPE_TEXT,'big',null, null,null,null,null);
$verify_form_table->addFieldInfo('actions',XMLDB_TYPE_TEXT,'big',null, null,null,null,null);
$verify_form_table->addFieldInfo('timecreated',XMLDB_TYPE_INTEGER,10,XMLDB_UNSIGNED,XMLDB_NOTNULL,null,null,null);
$verify_form_table->addFieldInfo('timemodified',XMLDB_TYPE_INTEGER,10,XMLDB_UNSIGNED,XMLDB_NOTNULL,null,null,null);
$verify_form_table->addKeyInfo('primary', XMLDB_KEY_PRIMARY, array('id'));
$results[] = create_table($verify_form_table);

/*******************
 * confirmation table
 *******************/

// create the confirmation table
$comfirmation_table = new XMLDBTable('block_assmgr_confirmation');
$comfirmation_table->addFieldInfo('id', XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null, null, null);
$comfirmation_table->addFieldInfo('evidence_id', XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, null);
$comfirmation_table->addFieldInfo('creator_id', XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, null);
$comfirmation_table->addFieldInfo('status', XMLDB_TYPE_INTEGER, 1, XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, null);
$comfirmation_table->addFieldInfo('feedback', XMLDB_TYPE_TEXT, 'big', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, null);
$comfirmation_table->addFieldInfo('timecreated',XMLDB_TYPE_INTEGER,10,XMLDB_UNSIGNED,XMLDB_NOTNULL,null,null,null);
$comfirmation_table->addFieldInfo('timemodified',XMLDB_TYPE_INTEGER,10,XMLDB_UNSIGNED,XMLDB_NOTNULL,null,null,null);
$comfirmation_table->addKeyInfo('primary', XMLDB_KEY_PRIMARY, array('id'));
$results[] = create_table($comfirmation_table);

/*******************
 * new submission grade table
 *******************/

// create the feedback table
$grade_table = new XMLDBTable('block_assmgr_grade');
$grade_table->addFieldInfo('id', XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null, null, null);
$grade_table->addFieldInfo('submission_id', XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, null);
$grade_table->addFieldInfo('outcome_id', XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, null);
$grade_table->addFieldInfo('grade', XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED, null, null, null, null, null);
$grade_table->addFieldInfo('feedback', XMLDB_TYPE_TEXT, 'big', null, null, null, null, null, null);
$grade_table->addFieldInfo('creator_id', XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, null);
$grade_table->addFieldInfo('timecreated',XMLDB_TYPE_INTEGER,10,XMLDB_UNSIGNED,XMLDB_NOTNULL,null,null,null);
$grade_table->addFieldInfo('timemodified',XMLDB_TYPE_INTEGER,10,XMLDB_UNSIGNED,XMLDB_NOTNULL,null,null,null);
$grade_table->addKeyInfo('primary', XMLDB_KEY_PRIMARY, array('id'));
$results[] = create_table($grade_table);

/*******************
 * feedback table
 *******************/

// create the feedback table
$feedback_table = new XMLDBTable('block_assmgr_feedback');
$feedback_table->addFieldInfo('id', XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null, null, null);
$feedback_table->addFieldInfo('submission_id', XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, null);
$feedback_table->addFieldInfo('filename',XMLDB_TYPE_CHAR,255,null,XMLDB_NOTNULL, null,null,null,null);
$feedback_table->addFieldInfo('timecreated',XMLDB_TYPE_INTEGER,10,XMLDB_UNSIGNED,XMLDB_NOTNULL,null,null,null);
$feedback_table->addFieldInfo('timemodified',XMLDB_TYPE_INTEGER,10,XMLDB_UNSIGNED,XMLDB_NOTNULL,null,null,null);
$feedback_table->addKeyInfo('primary', XMLDB_KEY_PRIMARY, array('id'));
$results[] = create_table($feedback_table);

/*******************
 * resource type table
 *******************/

// create the resource type table
$resource_type_table = new XMLDBTable('block_assmgr_resource_type');
$resource_type_table->addFieldInfo('id', XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null, null, null);
$resource_type_table->addFieldInfo('name',XMLDB_TYPE_CHAR,255,null,XMLDB_NOTNULL, null,null,null,null);
$resource_type_table->addFieldInfo('timecreated',XMLDB_TYPE_INTEGER,10,XMLDB_UNSIGNED,XMLDB_NOTNULL,null,null,null);
$resource_type_table->addFieldInfo('timemodified',XMLDB_TYPE_INTEGER,10,XMLDB_UNSIGNED,XMLDB_NOTNULL,null,null,null);
$resource_type_table->addKeyInfo('primary', XMLDB_KEY_PRIMARY, array('id'));
$results[] = create_table($resource_type_table);

$resource_types = array(
    'assmgr_resource_url'   => null,
    'assmgr_resource_file'  => null,
    'assmgr_resource_text'  => null,
    'assmgr_resource_moodle'=> null
);

// insert the resource types into the database
foreach ($resource_types as $res_name => $res_id) {
    $resource_t = new object();
    //$resource_t->id = $res_id;
    $resource_t->name = $res_name;
    $resource_t->timecreated = $current_time;
    $resource_t->timemodified = $current_time;
    $resource_types[$res_name] = insert_record('block_assmgr_resource_type',assmgr_encode($resource_t));
}

// drop the old locks table
$locks_table = new XMLDBTable('block_assessmgr_locks');
$result[] = drop_table($locks_table);
file_var_crap('create the new lock table');

// create the new lock table
$lock_table = new XMLDBTable('block_assmgr_lock');
$lock_table->addFieldInfo('id', XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null, null, null);
$lock_table->addFieldInfo('portfolio_id', XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, null);
$lock_table->addFieldInfo('creator_id', XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, null);
$lock_table->addFieldInfo('expire', XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, null);
$lock_table->addFieldInfo('timecreated',XMLDB_TYPE_INTEGER,10,XMLDB_UNSIGNED,XMLDB_NOTNULL,null,null,null);
$lock_table->addFieldInfo('timemodified',XMLDB_TYPE_INTEGER,10,XMLDB_UNSIGNED,XMLDB_NOTNULL,null,null,null);
$lock_table->addKeyInfo('primary', XMLDB_KEY_PRIMARY, array('id'));
$results[] = create_table($lock_table);

// create the grade_cat_desc table
$grade_cat_desc_table = new XMLDBTable('block_assmgr_grade_cat_desc');
$grade_cat_desc_table->addFieldInfo('id', XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null, null, null);
$grade_cat_desc_table->addFieldInfo('grade_category_id', XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, null);
$grade_cat_desc_table->addFieldInfo('description', XMLDB_TYPE_TEXT, 'big', null, null, null, null, null, null);
$grade_cat_desc_table->addKeyInfo('primary', XMLDB_KEY_PRIMARY, array('id'));
$results[] = create_table($grade_cat_desc_table);


// create the calendar event table
$calendar_event_table = new XMLDBTable('block_assmgr_calendar_event');
$calendar_event_table->addFieldInfo('id', XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null, null, null);
$calendar_event_table->addFieldInfo('event_id', XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, null);
$calendar_event_table->addFieldInfo('course_id', XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, null);
$calendar_event_table->addFieldInfo('creator_id', XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, null);
$calendar_event_table->addFieldInfo('timecreated',XMLDB_TYPE_INTEGER,10,XMLDB_UNSIGNED,XMLDB_NOTNULL,null,null,null);
$calendar_event_table->addFieldInfo('timemodified',XMLDB_TYPE_INTEGER,10,XMLDB_UNSIGNED,XMLDB_NOTNULL,null,null,null);
$calendar_event_table->addKeyInfo('primary', XMLDB_KEY_PRIMARY, array('id'));
$results[] = create_table($calendar_event_table);

/*******************
 * resource table
 *******************/
$resource_table = new XMLDBTable('block_assmgr_resource');
$resource_table->addFieldInfo('id', XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null, null, null);
$resource_table->addFieldInfo('evidence_id', XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, null);
$resource_table->addFieldInfo('resource_type_id', XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, null);
$resource_table->addFieldInfo('tablename',XMLDB_TYPE_CHAR,28,null,XMLDB_NOTNULL, null,null,null,null);
$resource_table->addFieldInfo('record_id', XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, null);
$resource_table->addFieldInfo('timecreated',XMLDB_TYPE_INTEGER,10,XMLDB_UNSIGNED,XMLDB_NOTNULL,null,null,null);
$resource_table->addFieldInfo('timemodified',XMLDB_TYPE_INTEGER,10,XMLDB_UNSIGNED,XMLDB_NOTNULL,null,null,null);
$resource_table->addKeyInfo('primary', XMLDB_KEY_PRIMARY, array('id'));
$results[] = create_table($resource_table);


/*******************
 * resource file table
 *******************/
$resource_file_table = new XMLDBTable('block_assmgr_res_file');
$resource_file_table->addFieldInfo('id', XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null, null, null);
$resource_file_table->addFieldInfo('filename',XMLDB_TYPE_CHAR,255,null,XMLDB_NOTNULL, null,null,null,null);
$resource_file_table->addFieldInfo('timecreated',XMLDB_TYPE_INTEGER,10,XMLDB_UNSIGNED,XMLDB_NOTNULL,null,null,null);
$resource_file_table->addFieldInfo('timemodified',XMLDB_TYPE_INTEGER,10,XMLDB_UNSIGNED,XMLDB_NOTNULL,null,null,null);
$resource_file_table->addKeyInfo('primary', XMLDB_KEY_PRIMARY, array('id'));
$results[] = create_table($resource_file_table);

/*******************
 * resource moodle table
 *******************/
$resource_moodle_table = new XMLDBTable('block_assmgr_res_moodle');
$resource_moodle_table->addFieldInfo('id', XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null, null, null);
$resource_moodle_table->addFieldInfo('activity_id', XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, null);
$resource_moodle_table->addFieldInfo('module_name',XMLDB_TYPE_CHAR,255,null,XMLDB_NOTNULL, null,null,null,null);
$resource_moodle_table->addFieldInfo('timecreated',XMLDB_TYPE_INTEGER,10,XMLDB_UNSIGNED,XMLDB_NOTNULL,null,null,null);
$resource_moodle_table->addFieldInfo('timemodified',XMLDB_TYPE_INTEGER,10,XMLDB_UNSIGNED,XMLDB_NOTNULL,null,null,null);
$resource_moodle_table->addKeyInfo('primary', XMLDB_KEY_PRIMARY, array('id'));
$results[] = create_table($resource_moodle_table);

/*******************
 * resource text table
 *******************/
$resource_text_table = new XMLDBTable('block_assmgr_res_text');
$resource_text_table->addFieldInfo('id', XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null, null, null);
$resource_text_table->addFieldInfo('text',XMLDB_TYPE_TEXT, 'big', null, XMLDB_NOTNULL, null,null,null,null);
$resource_text_table->addFieldInfo('timecreated',XMLDB_TYPE_INTEGER,10,XMLDB_UNSIGNED,XMLDB_NOTNULL,null,null,null);
$resource_text_table->addFieldInfo('timemodified',XMLDB_TYPE_INTEGER,10,XMLDB_UNSIGNED,XMLDB_NOTNULL,null,null,null);
$resource_text_table->addKeyInfo('primary', XMLDB_KEY_PRIMARY, array('id'));
$results[] = create_table($resource_text_table);

/*******************
 * resource url table
 *******************/
$resource_url_table = new XMLDBTable('block_assmgr_res_url');
$resource_url_table->addFieldInfo('id', XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null, null, null);
$resource_url_table->addFieldInfo('url',XMLDB_TYPE_CHAR,255,null,XMLDB_NOTNULL, null,null,null,null);
$resource_url_table->addFieldInfo('timecreated',XMLDB_TYPE_INTEGER,10,XMLDB_UNSIGNED,XMLDB_NOTNULL,null,null,null);
$resource_url_table->addFieldInfo('timemodified',XMLDB_TYPE_INTEGER,10,XMLDB_UNSIGNED,XMLDB_NOTNULL,null,null,null);
$resource_url_table->addKeyInfo('primary', XMLDB_KEY_PRIMARY, array('id'));
$results[] = create_table($resource_url_table);

// -----------------------------------------------------------------------------
// --                       MODIFY EXISTING TABLES                            --
// -----------------------------------------------------------------------------
file_var_crap('modifying existing tables');

/*******************
 * evidence_type table
 *******************/$evidence_type_table = new XMLDBTable('evidence_type');

$evidence_type_id = new XMLDBField('id');
$evidence_type_id->setAttributes(XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null, null, null);
$results[] = change_field_type($evidence_type_table, $evidence_type_id);

$evidence_type_name = new XMLDBField('name');
$evidence_type_name->setAttributes(XMLDB_TYPE_CHAR, 255, null, XMLDB_NOTNULL, null, null, null, null);
$results[] = change_field_type($evidence_type_table, $evidence_type_name);

// create a time created field for the evidence type table
$evidence_type_timecreated = new XMLDBField('timecreated');
$evidence_type_timecreated->setAttributes(XMLDB_TYPE_INTEGER,10,XMLDB_UNSIGNED,XMLDB_NOTNULL,null,null,null,$current_time);
$results[] = add_field($evidence_type_table, $evidence_type_timecreated);

// create a time modified field for the evidence type table
$evidence_type_timemodified = new XMLDBField('timemodified');
$evidence_type_timemodified->setAttributes(XMLDB_TYPE_INTEGER,10,XMLDB_UNSIGNED,XMLDB_NOTNULL,null,null,null,$current_time);
$results[] = add_field($evidence_type_table, $evidence_type_timemodified);

// rename the table
$results[] = rename_table($evidence_type_table, 'block_assmgr_evidence_type', false, false);

// get all the evidence type records
$evidence_types = get_records('block_assmgr_evidence_type');

// change all the plain text evidence types in to the string token format
$evidence_types_map = array(
    'Observation'           => array('observation', 'observationdesc'),
    'Product Evidence'      => array('productevidence', 'productevidencedesc'),
    'Questioning'           => array('questioning', 'questioningdesc'),
    'Simulation/Assignment' => array('simulationassignment', 'simulationassignmentdesc'),
    'Electronic Recording'  => array('electronicrecording', 'electronicrecordingdesc'),
    'Witness Statement'     => array('witnessstatement', 'witnessstatementdesc'),
    'APA/APL'               => array('apaapl', 'apaapldesc'),
    'Personal Statement'    => array('personalstatement', 'personalstatementdesc'),
    'Other'                 => array('other', 'otherdesc')
);

foreach($evidence_types as $evidence_type) {
    $new = $evidence_types_map[$evidence_type->name];
    $evidence_type->name = $new[0];
    $evidence_type->description = $new[1];

    update_record('block_assmgr_evidence_type', assmgr_encode($evidence_type));
}

/*******************
 * folder table
 *******************/
file_var_crap('altering folder table');

$folder_table = new XMLDBTable('block_assessmgr_fold');

// change id to int 10
$folder_table->addFieldInfo('folder_id', XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED, null, null, null, null, null);

$folder_table_id = new XMLDBField('id');
$folder_table_id->setAttributes(XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null, null, null);
$results[] = change_field_type($folder_table, $folder_table_id);

$folder_table_f_id = new XMLDBField('folder_id');
$folder_table_f_id->setAttributes(XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED, null, null, null, null, null);
$results[] = change_field_type($folder_table, $folder_table_f_id);

$folder_table_name = new XMLDBField('name');
$folder_table_name->setAttributes(XMLDB_TYPE_CHAR, 255, null, XMLDB_NOTNULL, null, null, null, null);
$results[] = change_field_type($folder_table, $folder_table_name);

// create a time created field for the folder table
$folder_timecreated = new XMLDBField('timecreated');
$folder_timecreated->setAttributes(XMLDB_TYPE_INTEGER,10,XMLDB_UNSIGNED,XMLDB_NOTNULL,null,null,null,$current_time);
$results[] = add_field($folder_table, $folder_timecreated);

// create a time modified field for the folder table
$folder_timemodified = new XMLDBField('timemodified');
$folder_timemodified->setAttributes(XMLDB_TYPE_INTEGER,10,XMLDB_UNSIGNED,XMLDB_NOTNULL,null,null,null,$current_time);
$results[] = add_field($folder_table, $folder_timemodified);

// get the user id field
$folder_candidate = new XMLDBField('user_id');
$folder_candidate->setAttributes(XMLDB_TYPE_INTEGER,10,XMLDB_UNSIGNED,XMLDB_NOTNULL,null,null,null);

// rename the user id field to candidate_id
$results[] = rename_field($folder_table, $folder_candidate, 'candidate_id', false, false);

// rename the table
$results[] = rename_table($folder_table, 'block_assmgr_folder', false, false);

// convert all root folders to new format
set_field('block_assmgr_folder', 'folder_id', NULL, 'folder_id', -2);

/*******************
 * portfolio table
 ******************/
file_var_crap('altering portfolio table');

$portfolio_table = new XMLDBTable('block_assessmgr_sub');

$portfolio_table_id = new XMLDBField('id');
$portfolio_table_id->setAttributes(XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null, null, null);
$results[] = change_field_type($portfolio_table, $portfolio_table_id);

$portfolio_table_cand = new XMLDBField('candidate_id');
$portfolio_table_cand->setAttributes(XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, null);
$results[] = change_field_type($portfolio_table, $portfolio_table_cand);

// create a needs assess field
$portfolio_table_needs = new XMLDBField('needsassess');
$portfolio_table_needs->setAttributes(XMLDB_TYPE_INTEGER,1,XMLDB_UNSIGNED,XMLDB_NOTNULL,null,null,null,0);
$results[] = add_field($portfolio_table, $portfolio_table_needs);

// create a time created field
$portfolio_table_timecreated = new XMLDBField('timecreated');
$portfolio_table_timecreated->setAttributes(XMLDB_TYPE_INTEGER,10,XMLDB_UNSIGNED,XMLDB_NOTNULL,null,null,null,$current_time);
$results[] = add_field($portfolio_table, $portfolio_table_timecreated);

// create a time modified field
$portfolio_table_timemodified = new XMLDBField('timemodified');
$portfolio_table_timemodified->setAttributes(XMLDB_TYPE_INTEGER,10,XMLDB_UNSIGNED,XMLDB_NOTNULL,null,null,null,$current_time);
$results[] = add_field($portfolio_table, $portfolio_table_timemodified);

// drop the course_id field
$portfolio_table_cou = new XMLDBField('course_id');
drop_field($portfolio_table, $portfolio_table_cou);

// get the block_instance_id field
$portfolio_table_course = new XMLDBField('block_instance_id');
$portfolio_table_course->setAttributes(XMLDB_TYPE_INTEGER,10,XMLDB_UNSIGNED,XMLDB_NOTNULL,null,null,null);
// rename the block_instance_id field to course_id
$results[] = rename_field($portfolio_table, $portfolio_table_course, 'course_id', false, false);

// drop the identifier field
$portfolio_table_ident = new XMLDBField('identifier');
drop_field($portfolio_table, $portfolio_table_ident);

// drop the status field
$portfolio_table_stat = new XMLDBField('status');
drop_field($portfolio_table, $portfolio_table_stat);

// drop the viewed field
$portfolio_table_view = new XMLDBField('viewed');
drop_field($portfolio_table, $portfolio_table_view);

// drop the viewed field
$portfolio_table_res = new XMLDBField('result');
drop_field($portfolio_table, $portfolio_table_res);

// drop the viewed field
$portfolio_table_verifycom = new XMLDBField('verifycomment');
drop_field($portfolio_table, $portfolio_table_verifycom);

// drop the viewed field
$portfolio_table_verify = new XMLDBField('verified');
drop_field($portfolio_table, $portfolio_table_verify);

// retrieve all portfolio records this will be used to set portfolio dates and needsassess field
$portfolios = get_records('block_assessmgr_sub');

// drop the idate field
$portfolio_table_idate = new XMLDBField('idate');
drop_field($portfolio_table, $portfolio_table_idate);

// drop the assessed field
$portfolio_table_assessed = new XMLDBField('assessed');
drop_field($portfolio_table, $portfolio_table_assessed);

// rename the table
$results[] = rename_table($portfolio_table, 'block_assmgr_portfolio', false, false);

/*******************
 * evidence table
 *******************/
file_var_crap('altering evidence table');

$evidence_table = new XMLDBTable('block_assessmgr_ev');

// change id field and folder id to big int
$evidence_table_id = new XMLDBField('id');
$evidence_table_id->setAttributes(XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null, null, null);
$results[] = change_field_type($evidence_table, $evidence_table_id);

$evidence_type_name = new XMLDBField('name');
$evidence_type_name->setAttributes(XMLDB_TYPE_CHAR, 255, null, XMLDB_NOTNULL, null, null, null, null);
$results[] = change_field_type($evidence_table, $evidence_type_name);

$evidence_table_folder_id = new XMLDBField('folder_id');
$evidence_table_folder_id->setAttributes(XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED, null, null, null, null, null);
$results[] = change_field_type($evidence_table, $evidence_table_folder_id);

// drop the status field
$evidence_table_stat = new XMLDBField('status');
drop_field($evidence_table, $evidence_table_stat);

// drop the confirmation comment field
$evidence_table_com = new XMLDBField('confirmation_comment');
drop_field($evidence_table, $evidence_table_com);

// drop the confirmation comment field
$evidence_table_sub_stat = new XMLDBField('submitted_status');
drop_field($evidence_table, $evidence_table_sub_stat);

// drop the confirm status field
$evidence_table_con_stat = new XMLDBField('confirm_status');
drop_field($evidence_table, $evidence_table_con_stat);

// drop the assessed status field
$evidence_table_ass_stat = new XMLDBField('assessed_status');
drop_field($evidence_table, $evidence_table_ass_stat);

// drop the verified status field
$evidence_table_verify_stat = new XMLDBField('verified_status');
drop_field($evidence_table, $evidence_table_verify_stat);

// drop the course field
$evidence_table_course = new XMLDBField('course');
drop_field($evidence_table, $evidence_table_course);

// get the user id field
$evidence_table_cand = new XMLDBField('user_id');
$evidence_table_cand->setAttributes(XMLDB_TYPE_INTEGER,10,XMLDB_UNSIGNED,XMLDB_NOTNULL,null,null,0);
// rename the user id field to candidate_id
$results[] = rename_field($evidence_table, $evidence_table_cand, 'candidate_id', false, false);

 // create a creator_id field
$evidence_table_creator = new XMLDBField('creator_id');
$evidence_table_creator->setAttributes(XMLDB_TYPE_INTEGER,10,XMLDB_UNSIGNED,XMLDB_NOTNULL,null,null,null,0);
$results[] = add_field($evidence_table, $evidence_table_creator);

// create a time created field
$evidence_table_timecreated = new XMLDBField('timecreated');
$evidence_table_timecreated->setAttributes(XMLDB_TYPE_INTEGER,10,XMLDB_UNSIGNED,XMLDB_NOTNULL,null,null,null,$current_time);
$results[] = add_field($evidence_table, $evidence_table_timecreated);

// create a time modified field
$evidence_table_timemodified = new XMLDBField('timemodified');
$evidence_table_timemodified->setAttributes(XMLDB_TYPE_INTEGER,10,XMLDB_UNSIGNED,XMLDB_NOTNULL,null,null,null,$current_time);
$results[] = add_field($evidence_table, $evidence_table_timemodified);

// retrieve all evidence records
$evidence = get_records('block_assessmgr_ev');

file_var_crap('Processing '.count($evidence).' evidence records');

// retrieve all resource records
$oldresources = get_records_sql(
    "SELECT ev_res.evidence_id, ev_res.*
     FROM {$CFG->prefix}block_assessmgr_ev_res AS ev_res"
);

$counter = 0;
foreach ($evidence as $evid) {
    $upgradable_evidence = true;

    $mdate = strtotime($evid->lastchanged);
    // if the idate is not set. set the timecreated field to current time
    $evid->timecreated = !empty($mdate) ? $mdate : $current_time;
    $evid->timemodified = $evid->timecreated;

    $oldresource = $oldresources[$evid->id];

    // check if the eivdence was made  by an assessor
    if ($evid->assessor_ev != 0) {
        // retrieve the details of person who assessed the evidence
        $assessment_details = get_records_sql(
            "SELECT  gg.id,
                     gg.usermodified
             FROM    {$CFG->prefix}block_assessmgr_ev AS ev,
                     {$CFG->prefix}block_assessmgr_ev_sub AS sub,
                     {$CFG->prefix}grade_items AS gi,
                     {$CFG->prefix}grade_grades AS gg
             WHERE   ev.id = sub.evidence_id
             AND     assessor_ev = 1
             AND     sub.assessed_status = 1
             AND     gi.itemnumber = sub.id
             AND     gi.idnumber = gg.id
             AND     gi.outcomeid IS NULL
             AND     ev.id = {$evid->id}
             AND     gg.usermodified IS NOT NULL"
        );



        // get the userid of the user who assessed the evidence
        // or set the userid to moodle_support

        if (!empty($assessment_details)) {

            $ass_details = current($assessment_details);
            $evid->creator_id = $ass_details->usermodified;
        }

    } else {
        $evid->creator_id = $evid->candidate_id;
    }

    // create a object to hold the resource
    $resource = new object();
    $resource->evidence_id = $evid->id;
    $resource->timecreated = $evid->timemodified;
    $resource->timemodified = $evid->timecreated;

    // create object to hold the resource detail
    $resource_object = new object();
    $resource_object->timecreated = $evid->timemodified;
    $resource_object->timemodified = $evid->timecreated;

    switch ($evid->evidenceresourcetypeid) {
        case '2':
            // web link
            $resource->resource_type_id = $resource_types['assmgr_resource_url'];
            $resource->tablename = 'block_assmgr_res_url';

            $resource_object->url = $oldresource->url;
            break;
        case '3':
            // file upload
            $resource->resource_type_id = $resource_types['assmgr_resource_file'];
            $resource->tablename = 'block_assmgr_res_file';

            $resource_object->filename = $oldresource->realfilename;

            // TODO move files
            // move and rename file to new file area (using $evidence->filename as filename)

            $pathname = make_user_directory($evid->candidate_id);
            $filepath = $pathname."/block_assessmgr/".$oldresource->directory."/".$oldresource->filename;
            $newfile = assmgr_evidence_folder($evid->candidate_id,$evid->id)."/".$oldresource->realfilename;

            if (file_exists ($filepath)) {
               $res =  rename($filepath,$CFG->dataroot."/".$newfile);
            } else {
                $res =  copy($CFG->dataroot."/lostdata.txt",$CFG->dataroot."/".$newfile.'.txt');
            }

              break;
        case '4':
            // text evidence
            $resource->resource_type_id = $resource_types['assmgr_resource_text'];
            $resource->tablename = 'block_assmgr_res_text';
            $resource_object->text = $oldresource->richtext;
              break;
        case '6':
        case '8':
/*
            // get the cm_id, as this is stored differently depending on auto/manual import
            $cm_id = ($evid->evidenceresourcetypeid == 8) ? $oldresource->directory : $oldresource->url;

            // get the course module instance
            $cm = get_record_sql(
                "SELECT *
                 FROM {$CFG->prefix}course_modules
                 WHERE id = {$cm_id}"
            );

            // check that the the activity actually exists (could have been deleted)
            if(!empty($cm->instance)) {
                // get the assignment
                $activity = get_record_sql(
                    "SELECT *
                     FROM {$CFG->prefix}assignment
                     WHERE id = {$cm->instance}"
                );

                // moodle evidence
                $resource->resource_type_id = $resource_types['assmgr_resource_moodle'];
                $resource->tablename = 'block_assmgr_res_moodle';

                $resource_object->module_name = 'assignment';
                $resource_object->activity_id = $activity->id;

            } else {
                $upgradable_evidence = false;
            }

            break;
            */
       default:
            // a non upgradable evidence type - this evidence will not be saved
            $upgradable_evidence = false;
            break;
    }

    if ($upgradable_evidence) {
        $resource->record_id = insert_record($resource->tablename, assmgr_encode($resource_object));
        insert_record('block_assmgr_resource',assmgr_encode($resource));
        $res = update_record('block_assessmgr_ev',assmgr_encode($evid));
    } else {
        // if we can't upgrade it then delete it

        // get any submissions
        $badsubmissions = get_records_sql(
            "SELECT *
             FROM {$CFG->prefix}block_assessmgr_ev_sub
             WHERE evidence_id = {$evid->id}"
        );

        if(!empty($badsubmissions)) {
            foreach($badsubmissions as $badsubmission) {
                recursively_delete_submission($badsubmission->id);
            }
        }

        // delete the evidence and the resource
        delete_records('block_assessmgr_ev', 'id', $evid->id);
        delete_records('block_assessmgr_ev_res', 'evidence_id', $evid->id);
    }

    $counter++;

    if($counter%1000 == 0) {
        file_var_crap("Processed $counter evidence records...");
    }
}

file_var_crap('Finished processing evidence records');

// drop the ev_res table
$ev_res_table = new XMLDBTable('block_assessmgr_ev_res');
$result[] = drop_table($ev_res_table);

// drop the evidenceresourceid field
$evidence_table_res = new XMLDBField('evidenceresourceid');
drop_field($evidence_table, $evidence_table_res);

// drop the evidenceresourcetypeid field
$evidence_table_res_type = new XMLDBField('evidenceresourcetypeid');
drop_field($evidence_table, $evidence_table_res_type);

// drop the confirmed field
$evidence_table_confirmed = new XMLDBField('confirmed');
drop_field($evidence_table, $evidence_table_confirmed);

// drop the last changed field
$evidence_table_lastchanged = new XMLDBField('lastchanged');
drop_field($evidence_table, $evidence_table_lastchanged);

// drop the last changed field
$evidence_table_assessor_ev = new XMLDBField('assessor_ev');
drop_field($evidence_table, $evidence_table_assessor_ev);

// rename the table
$results[] = rename_table($evidence_table, 'block_assmgr_evidence');

/*******************
 * change structure of submission table
 *******************/file_var_crap('altering submission table');

$submission_table = new XMLDBTable('block_assessmgr_ev_sub');

$submission_table_id = new XMLDBField('id');
$submission_table_id->setAttributes(XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null, null, null);
$results[] = change_field_type($submission_table, $submission_table_id);

// update the evidence_id field
$submission_table_evidence_id = new XMLDBField('evidence_id');
$submission_table_evidence_id->setAttributes(XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED, null, null, null, null, null);
$results[] = change_field_type($submission_table, $submission_table_evidence_id);

// create a creator_id field
$submission_table_creator = new XMLDBField('creator_id');
$submission_table_creator->setAttributes(XMLDB_TYPE_INTEGER,10,XMLDB_UNSIGNED,XMLDB_NOTNULL,null,null,null,0);
$results[] = add_field($submission_table, $submission_table_creator);

// create a hidden field for
$submission_table_hidden = new XMLDBField('hidden');
$submission_table_hidden->setAttributes(XMLDB_TYPE_INTEGER,1,XMLDB_UNSIGNED,XMLDB_NOTNULL,null,null,null,0);
$results[] = add_field($submission_table, $submission_table_hidden);

$submission_table_synchronise = new XMLDBField('synchronise');
$submission_table_synchronise->setAttributes(XMLDB_TYPE_INTEGER,1,XMLDB_UNSIGNED,XMLDB_NOTNULL,null,null,null,0);
$results[] = add_field($submission_table, $submission_table_synchronise);

// create a time created field
$submission_table_timecreated = new XMLDBField('timecreated');
$submission_table_timecreated->setAttributes(XMLDB_TYPE_INTEGER,10,XMLDB_UNSIGNED,XMLDB_NOTNULL,null,null,null,$current_time);
$results[] = add_field($submission_table, $submission_table_timecreated);

// create a time modified field
$submission_table_timemodified = new XMLDBField('timemodified');
$submission_table_timemodified->setAttributes(XMLDB_TYPE_INTEGER,10,XMLDB_UNSIGNED,XMLDB_NOTNULL,null,null,null,$current_time);
$results[] = add_field($submission_table, $submission_table_timemodified);


// get the submission_id field
$submission_table_sub_id = new XMLDBField('submission_id');
$submission_table_sub_id->setAttributes(XMLDB_TYPE_INTEGER,10,XMLDB_UNSIGNED,XMLDB_NOTNULL,null,null,null);
// rename the submission_id field to portfolio_id
$results[] = rename_field($submission_table, $submission_table_sub_id, 'portfolio_id', false, false);

// drop the status field
$submission_table_stat = new XMLDBField('status');
drop_field($submission_table, $submission_table_stat);

// drop the submitted status field
$submission_table_sub_stat = new XMLDBField('submitted_status');
drop_field($submission_table, $submission_table_sub_stat);

// drop the assessed status field
$submission_table_assess_stat = new XMLDBField('assessed_status');
drop_field($submission_table, $submission_table_assess_stat);

// drop the verified status field
$submission_table_veri_stat = new XMLDBField('verified_status');
drop_field($submission_table, $submission_table_veri_stat);

// drop the comments field
$submission_table_com = new XMLDBField('comments');
drop_field($submission_table, $submission_table_com);

 // drop the comments field
$submission_table_assess = new XMLDBField('assess_ready');
drop_field($submission_table, $submission_table_assess);

// rename the table
$results[] = rename_table($submission_table, 'block_assmgr_submission', false, false);

file_var_crap('deleting bad submissions');

// get all bogus submissions
$badsubmissions = get_records_sql(
    "SELECT *
     FROM {$CFG->prefix}block_assmgr_submission
     WHERE evidence_id NOT IN (SELECT id FROM {$CFG->prefix}block_assmgr_evidence)"
);

if(!empty($badsubmissions)) {
    foreach($badsubmissions as $badsubmission) {
        recursively_delete_submission($badsubmission->id);
    }
}

file_var_crap('setting the creator id for all submissions');

// get all remaining submissions
$submissions = get_records('block_assmgr_submission');

foreach ($submissions as $sub) {
    $evidence = get_record_sql(
       "SELECT *
        FROM {$CFG->prefix}block_assmgr_evidence
        WHERE id = {$sub->evidence_id}"
    );

    $sub->creator_id = $evidence->creator_id;
    $res = update_record('block_assmgr_submission', assmgr_encode($sub));
}
/********************************************
 * set timecrated and needs assess on portfolios
 */
file_var_crap('setting timecreated and needs assess on '.count($portfolios).' portfolios');

// convert the idate into timecreated
foreach ($portfolios as $port) {
    $idate = strtotime($port->idate);

    // if the idate is not set. set the timecreated field to current time
    $port->timecreated = !empty($idate) ? $idate : $current_time;
    update_record('block_assmgr_portfolio',assmgr_encode($port));
    $dbc->set_portfolio_needsassess($port->id);
}

/*******************
 * Transfer old grades into the new grades table
 ******************/
$graded_submissions = get_submission_grades();
file_var_crap('Transfering '.count($graded_submissions).' old grades into the new grades table');

if (!empty($graded_submissions)) {
    foreach($graded_submissions as $gs) {
        //added this line to stop empty grades being inserted
        if (!empty($gs->rawgrade) && $gs->rawgrade != 0.0) {
             $submission_grade = new object();
             $submission_grade->submission_id   = $gs->submission_id;
             $submission_grade->outcome_id      = $gs->outcome_id;
             $submission_grade->creator_id      = $gs->usermodified;
             $submission_grade->grade           = $gs->rawgrade;
             $submission_grade->timecreated     = $current_time;
             $submission_grade->timemodified    = $current_time;
             $res =  $dbc->create_submission_grade($submission_grade);
        }
    }
}

/*******************
 * change submission evidence type table
 *******************/
file_var_crap('Altering submission evidence type table');

$sub_evid_type_table = new XMLDBTable('block_assessmgr_ev_sub_ev_ty');

$sub_evid_type_id = new XMLDBField('id');
$sub_evid_type_id->setAttributes(XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null, null, null);
$results[] = change_field_type($sub_evid_type_table, $sub_evid_type_id);

$sub_evid_type_et_id = new XMLDBField('evidence_type_id');
$sub_evid_type_et_id->setAttributes(XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, null);
$results[] = change_field_type($sub_evid_type_table, $sub_evid_type_et_id);

// create a time created field for the folder table
$sub_evid_type_creator = new XMLDBField('creator_id');
$sub_evid_type_creator->setAttributes(XMLDB_TYPE_INTEGER,10,XMLDB_UNSIGNED,XMLDB_NOTNULL,null,null,null,0);
$results[] = add_field($sub_evid_type_table, $sub_evid_type_creator);

// get the user id field
$sub_evid_type_sub_id = new XMLDBField('evidence_submission_id');
$sub_evid_type_sub_id->setAttributes(XMLDB_TYPE_INTEGER,10,XMLDB_UNSIGNED,XMLDB_NOTNULL,null,null,null);
// rename the user id field to candidate_id
$results[] = rename_field($sub_evid_type_table, $sub_evid_type_sub_id, 'submission_id', false, false);

// create a time created field for the folder table
$sub_evid_type_timecreated = new XMLDBField('timecreated');
$sub_evid_type_timecreated->setAttributes(XMLDB_TYPE_INTEGER,10,XMLDB_UNSIGNED,XMLDB_NOTNULL,null,null,null,$current_time);
$results[] = add_field($sub_evid_type_table, $sub_evid_type_timecreated);

// create a time modified field for the folder table
$sub_evid_type_timemodified = new XMLDBField('timemodified');
$sub_evid_type_timemodified->setAttributes(XMLDB_TYPE_INTEGER,10,XMLDB_UNSIGNED,XMLDB_NOTNULL,null,null,null,$current_time);
$results[] = add_field($sub_evid_type_table, $sub_evid_type_timemodified);

// delete all the candidate ev_sub_ev_ty records
delete_records('block_assessmgr_ev_sub_ev_ty', 'type', 1);

// get all the remaining records
$submission_evidence_types = get_records('block_assessmgr_ev_sub_ev_ty');

// update all assessor records with their creator
foreach ($submission_evidence_types as $sub_type) {
    $sql = "SELECT *,
                 ev.creator_id AS ev_creator_id,
                 ev.candidate_id AS ev_candidate_id,
                 sub.creator_id AS sub_creator_id,
                 sub.id AS submission_id

          FROM {$CFG->prefix}block_assmgr_evidence AS ev,
               {$CFG->prefix}block_assmgr_submission  AS sub

          WHERE ev.id = sub.evidence_id
            AND sub.id = {$sub_type->submission_id}";

    $submission_record = get_record_sql($sql);

    if (!empty($submission_record)) {

        // an assessor evidence type
        if ($submission_record->ev_candidate_id != $submission_record->ev_creator_id) {
            $sub_type->creator_id = $submission_record->ev_creator_id;
        } else {
            // retrieve the userid of a assessor
            $sql = "SELECT  gg.id,
                            gg.usermodified
                   FROM    {$CFG->prefix}block_assmgr_submission AS sub,
                            {$CFG->prefix}grade_items AS gi,
                            {$CFG->prefix}grade_grades AS gg
                    WHERE   gi.itemnumber = sub.id
                    AND     gi.idnumber = gg.id
                    AND     sub.id = {$sub_type->submission_id}
                    AND     gi.itemtype = 'mod'
                    AND     gi.itemmodule = 'assessmgr'
                    AND     gg.usermodified IS NOT NULL";

            $grade_details = get_records_sql($sql);
            if (!empty($grade_details)) {
                $grade_detail = current($grade_details);
                $sub_type->creator_id = $grade_detail->usermodified;

            } else {
                $sub_type->creator_id = 1;
            }
        }
        update_record('block_assessmgr_ev_sub_ev_ty',assmgr_encode($sub_type));
    }
}

 // drop the type status field
$sub_evid_type_table_type = new XMLDBField('type');
drop_field($sub_evid_type_table, $sub_evid_type_table_type);

// rename the table
$results[] = rename_table($sub_evid_type_table, 'block_assmgr_sub_evid_type', false, false);

// get the current time for a performance metric
$end_time = time();
file_var_crap($end_time- $current_time, "operation took...");

file_var_crap('Finished stage 1');
redirect("{$CFG->wwwroot}/blocks/assmgr/upgrade_stage2.php", 'Finished stage 1', 2);