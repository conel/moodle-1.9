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


file_var_crap("Started stage 2");

// instantiate the db
$dbc = new assmgr_db();

// get all the bogus portfolios
$badportfolios = get_records_sql(
    "SELECT *
     FROM {$CFG->prefix}block_assmgr_portfolio
     WHERE (course_id IS NULL OR course_id NOT IN (SELECT id FROM {$CFG->prefix}course))"
);

file_var_crap('Getting rid of '.count($badportfolios).' bogus portfolios and all related records');

if(!empty($badportfolios)) {
    foreach($badportfolios as $badportfolio) {
        // get all the bogus submissions
        $badsubmissions = get_records_sql(
            "SELECT *
             FROM {$CFG->prefix}block_assmgr_submission
             WHERE portfolio_id = {$badportfolio->id}"
        );

        if(!empty($badsubmissions)) {
            foreach($badsubmissions as $badsubmission) {
                recursively_delete_submission($badsubmission->id);
            }
        }

        // delete the portfolio
        delete_records('block_assmgr_portfolio', 'id', $badportfolio->id);
    }
}

file_var_crap('Bogus portfolios deleted');

// find all portfolios
$portfolio_records = get_portfolios();

file_var_crap('Processing '.(count($portfolio_records)).' portfolios to setup the grade book');

$counter = 0;

foreach ($portfolio_records as $port_record) {

    // This section should take care of any courses that dont have grade items
    // and/or categories

    // requesting the course grade category item will create the category if
    // it does not exist, it will also create the course grade item

    $course_category = grade_category::fetch_course_category($port_record->course_id);

    $course_params = array(
        'courseid'     => $port_record->course_id,
        'itemtype'     => 'course',
        'iteminstance' => $course_category->id
    );

    // fetch the course grade item
    $grade_item = grade_item::fetch($course_params);

    // get the instance config for the block
    $config = get_instance_config($port_record->course_id);

    // we still need to check if the course grade item exists, as fetching the
    // grade category will only create the item if the category is missing
    if(empty($grade_item)) {

        // create a new grade item for the course
        $grade_item = new grade_item($course_params, false);
        $grade_item->gradetype = GRADE_TYPE_SCALE;
        $grade_item->scaleid = empty($config->scale) ? null : $config->scale;
        $grade_item->insert('system');
    } else {
        if ($grade_item->gradetype != GRADE_TYPE_SCALE) {

            $sql = "SELECT  *
                    FROM    {$CFG->prefix}grade_items
                    WHERE   id = {$grade_item->id}";

            $gi_record = get_record_sql($sql);

            if (!empty($gi_record)) {
                $gi_record->gradetype = GRADE_TYPE_SCALE;
                $grade_item->scaleid = empty($config->scale) ? null : $config->scale;
            }

            update_record('grade_items', $gi_record);
        }
    }

    // now we have category and course grade items we have to reconcile all portfolio outcome grades
    // with marks making sure that there is an entry in the grade_item table for all of them
    // as previously they were not record if they had no grade

    // first we make the outcome category if it doesnt exist
    $outcome_params = array(
        'courseid' => $port_record->course_id,
        'parent'   => $course_category->id,
        'fullname' => "Assessment Criteria"
    );

    // a check to see if an outcome category for the outcome exists
    $outcome_category = grade_category::fetch($outcome_params);

    // does it exist already
    if(empty($outcome_category)) {
        // create the grade category to store the portfolio grade items
        $outcome_category = new grade_category($outcome_params, false);
        $outcome_category->insert();
    }

    // get all the outcomes attached to the course
    $outcomes = get_outcomes($port_record->course_id);
    if (!empty($outcomes)) {
        foreach ($outcomes as $outcome) {
            if (!outcome_grade_item_exists($port_record->course_id,$outcome_category->id,$outcome->id)) {
                $outcome_item = new grade_item();
                $outcome_item->courseid = $port_record->course_id;
                $outcome_item->categoryid = $outcome_category->id;
                $outcome_item->itemtype = 'mod';
                $outcome_item->itemmodule = 'block_assmgr';
                $outcome_item->iteminstance = null;
                $outcome_item->itemnumber = $outcome->id;
                $outcome_item->itemname = limit_length($outcome->fullname, 250, false);
                $outcome_item->outcomeid = $outcome->id;
                $outcome_item->gradetype = GRADE_TYPE_SCALE;
                $outcome_item->scaleid = $outcome->scaleid;
                $outcome_item->insert();
            }

            // find out if a claim assessment record. if there is one there is a good chance there is a grade item
            $portfolio_outcome_claim = get_portfolio_outcome_claim($outcome->id,$port_record->id);

            $outcome_exists = false;
            if (!empty($portfolio_outcome_claim)) {
                 $sql = "SELECT *
                         FROM   {$CFG->prefix}grade_items
                         WHERE  itemtype = 'mod'
                         AND    itemmodule = 'assessmgr'
                         AND    courseid = {$port_record->course_id}
                         AND    idnumber = {$portfolio_outcome_claim->id}";

                 $port_outcome_grade_item = get_record_sql($sql);

                 // if the grade item is found update the record
                 if (!empty($port_outcome_grade_item)) {
                    $generic_outcome_gi = outcome_grade_item_exists($port_record->course_id,$outcome_category->id,$outcome->id);

                    set_field('grade_grades', 'itemid', $generic_outcome_gi->id, 'itemid', $port_outcome_grade_item->id, 'userid', $port_record->candidate_id);
                    delete_records('grade_items', 'id', $port_outcome_grade_item->id);
                    delete_records('block_assessmgr_claim', 'id', $portfolio_outcome_claim->id);
                 }
            }
        }
    }

    $port_feedback = get_portfolio_feedback($port_record->id);

    if (!empty($port_feedback)) {
       // get the course grade item
       $gi = grade_item::fetch(
            array(
                'itemtype'      => 'course',
                'itemmodule'    => null,
                'courseid'      => $port_record->course_id
            )
        );

        $course_config = get_instance_config($port_record->course_id);

        foreach ($port_feedback as $p_feedback) {
            // update the grade with the feedback this should make any grade history
            // records needed if the feedback has changed
            $gi->update_final_grade($port_record->candidate_id, $course_config->scale, 'gradebook', $p_feedback->com, FORMAT_HTML);
        }
    }
    // get all submissions in portfolio
    $portfolio_submissions = get_portfolio_submissions($port_record->id);

    // retrieve all submissions in the portfolio
    if(!empty($portfolio_submissions)) {
        foreach ($portfolio_submissions as $submission) {
            $sub_feedback = get_submission_feedback($submission->id);
            $feedback = "";
            if (!empty($sub_feedback)) {
                foreach ($sub_feedback as $s_feedback) {
                    $feedback .= $s_feedback->com;
                }

                $submission_feedback = new object();
                $submission_feedback->submission_id = $submission->id;
                $submission_feedback->feedback      = $feedback;
                $submission_feedback->creator_id    = $s_feedback->user_id;
                $submission_feedback->timecreated   = $current_time;
                $submission_feedback->timemodified  = $current_time;
                $dbc->create_submission_grade($submission_feedback);
            }
        }
    }

    $counter++;

    if($counter%300 == 0) {
        file_var_crap("Processed $counter portfolios...");
    }
}

// get the current time for a performance metric
$end_time = time();
$totaltime = $end_time - $current_time;

file_var_crap($end_time- $current_time, "operation took...");

file_var_crap('Finished stage 2');
redirect("{$CFG->wwwroot}/blocks/assmgr/upgrade_stage4.php", 'Finished stage 2', 2);