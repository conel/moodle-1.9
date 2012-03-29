<?php
/**
 * Returns the config options for a specific instance of the assessment
 * manager.
 *
 * N.B. There is no function to do this in Moodle.
 * @see http://moodle.org/mod/forum/discuss.php?d=129799#p568635
 *
 * @param int $course_id The id of the course for which to get the config instance
 * @return object An object containing config information
 */
function get_instance_config($course_id) {
    global $CFG;

    $encoded = '';

    // get the global config options
    $config = get_config('block_assessmgr');

    // check for the presence of a table to determine which query to run
    $oldtable = get_records_sql("SHOW TABLES LIKE '{$CFG->prefix}block_instance'");

    // fetch the local settings
    if(!empty($oldtable)) {
        // version 1.9x

        $encoded = get_field_sql(
            "SELECT ins.configdata
             FROM {$CFG->prefix}block AS blk,
                  {$CFG->prefix}block_instance AS ins
             WHERE blk.name = 'assessmgr'
               AND blk.id = ins.blockid
               AND ins.pageid = {$course_id}
               AND ins.pagetype = 'course-view'"
        );
    }

    if(!empty($encoded)) {
        $local = unserialize(base64_decode($encoded));
        // merge the two together
        foreach($local as $name => $value) {
            // the instance config can not override a negative value
            if(!isset($config->{$name}) || $config->{$name} == 1) {
                $config->{$name} = $value;
            }
        }
    }

    return $config;
}

function get_portfolios($limitfrom=false,$limitnum=false) {
    global $CFG;

    $limit = (($limitfrom != false || $limitfrom == 0) && !empty($limitnum)) ? " limit {$limitfrom}, {$limitnum}" : '';

    $sql = "SELECT *
            FROM {$CFG->prefix}block_assmgr_portfolio
            {$limit}";

    return get_records_sql($sql);
}

//finds all portfolio claim entries and get the course that they
//pertain to
function get_portfolio_claim_entries() {
    global $CFG;

    $sql = "SELECT      distinct(sub_id), portfolio.course_id
                FROM        {$CFG->prefix}block_assessmgr_claim AS claim,
                            {$CFG->prefix}block_assmgr_portfolio AS portfolio
                WHERE       evid_sub_id = 0
                AND         outcome_id != 0
                AND         grade_id = 0
                AND         sub_id = portfolio.id
                AND         type = 1
                ORDER BY    claim.id";

    return get_records_sql($sql);
}



//check if a grade item record for the given portfolio exits
function course_grade_item_exists($portfolio_id,$course_id) {
    return record_exists('grade_items',array('itemtype'=>'course','iteminstance'=>$portfolio_id,'course_id'=>$course_id));
}

//returns the grade item record for the given course if it exists
function outcome_grade_item_exists($course_id,$category_id,$outcome_id) {
    global $CFG;

    $sql = "SELECT  *
            FROM    {$CFG->prefix}grade_items
            WHERE   itemmodule = 'block_assmgr'
            AND     categoryid  = {$category_id}
            AND     courseid   = {$course_id}
            AND     outcomeid  = {$outcome_id}";

    return get_record_sql($sql);
}

//returns the grade item(s) for the submission given
function sub_outcome_claim_exists($course_id,$category_id,$outcome_id,$submission_id) {
    global $CFG;

    $sql = "SELECT  *
            FROM    {$CFG->prefix}grade_items
            WHERE   itemmodule = 'block_assmgr'
            AND     categoryid  = {$category_id}
            AND     courseid   = {$course_id}
            AND     outcomeid  = {$outcome_id}
            AND     iteminstance = {$submission_id}";
   return get_records_sql($sql);
}

//returns all outcomes for the given course
function get_outcomes($course_id) {
    global $CFG;

      $sql = "SELECT o.*
              FROM {$CFG->prefix}grade_outcomes AS o,
                   {$CFG->prefix}grade_outcomes_courses AS oc
             WHERE oc.courseid = {$course_id}
               AND oc.outcomeid = o.id
             ORDER BY o.id ASC";

    return get_records_sql($sql);
}

//returns all submissions for the protfolio given
function get_portfolio_submissions($portfolio_id) {
    global $CFG;

        $sql = " SELECT      *, s.id as id
                FROM        {$CFG->prefix}block_assmgr_submission as s,
                            {$CFG->prefix}block_assmgr_evidence as e
                WHERE       s.portfolio_id = {$portfolio_id}
                AND         e.id = s.evidence_id";

        return get_records_sql($sql);
}

//returns the claim record for the particular portfolio if it exists
function get_portfolio_outcome_claim($outcome_id,$portfolio_id) {
    global $CFG;

   $sql = " SELECT      *
            FROM        {$CFG->prefix}block_assessmgr_claim
            WHERE       evid_sub_id = 0
            AND         outcome_id = {$outcome_id}
            AND         sub_id = {$portfolio_id}
            AND         grade_id = 0
            AND         type = 1";

    return get_record_sql($sql);
}

//return the claim record for the particular submission if it exists
function get_submission_outcome_claim($outcome_id,$submission_id) {
    global $CFG;

   $sql = " SELECT      *
            FROM        {$CFG->prefix}block_assessmgr_claim
            WHERE       evid_sub_id = {$submission_id}
            AND         outcome_id = {$outcome_id}
            AND         sub_id = 0
            AND         grade_id = -1
            AND         type = 2";

    return get_record_sql($sql);
}

//returns all feedback for portfolios
function get_portfolio_feedback($portfolio_id = NULL) {
    global $CFG;

    $port_sql =  (empty($portfolio_id))?  '' : "AND   submission_id = {$portfolio_id}";

    $sql = "SELECT      *
            FROM        {$CFG->prefix}block_assessmgr_sub_hist
            WHERE       action = 'assessed'
            AND         com is NOT NULL
            AND         com != ''
            $port_sql
            ORDER BY    submission_id";

    return get_records_sql($sql);

}

/********************************************
 * Returns all grades for all submissions
 *********************************************/
function get_submission_grades() {
    global $CFG;


   $sql =       "SELECT gi.id,
                        evid_sub_id as submission_id,
                        claim.outcome_id,
                        usermodified,
                        rawgrade
                FROM    {$CFG->prefix}grade_items  AS gi,
                        {$CFG->prefix}grade_grades AS gg,
                        {$CFG->prefix}block_assessmgr_claim AS claim
                WHERE   gi.itemtype = 'mod'
                AND     gi.itemmodule = 'assessmgr'
                AND     claim.id =  gi.idnumber
                AND     gi.id = gg.itemid
                AND     gg.rawgrade IS NOT NULL
                AND     evid_sub_id != 0
                AND     sub_id = 0";

   return get_records_sql($sql);

}

/********************************************
 * Returns all grades for all submissions
 *********************************************/
function get_submission_feedback($submission_id = NULL) {
    global $CFG;

    $sub_sql =  (empty($submission_id)) ? '' : "AND   evidsub_id = {$submission_id}";

    $sql = "SELECT      *
            FROM        {$CFG->prefix}block_assessmgr_sub_hist
            WHERE       action = 'evidence assessed'
            AND         com is NOT NULL
            AND         com != ''
            $sub_sql
            ORDER BY    evidsub_id";

    return get_records_sql($sql);
}

/********************************************
 * This function deletes a submission and all of its
 * related records in the block_assmgr_claim, grade_grades, grade_items
 * and block_assmgr_sub_evid_type tables
 *********************************************/
function recursively_delete_submission($submission_id) {
    global $CFG;

    $oldtable = get_records_sql("SHOW TABLES LIKE '{$CFG->prefix}block_assessmgr_claim'");
    $claimtable = empty($oldtable) ? 'block_assmgr_claim' : 'block_assessmgr_claim';
    $claimfield = empty($oldtable) ? 'submission_id' : 'evid_sub_id';

    // get any claims
    $badclaims = get_records_sql(
        "SELECT *
         FROM {$CFG->prefix}{$claimtable}
         WHERE {$claimfield} = {$submission_id}"
    );

    if(!empty($badclaims)) {
        foreach($badclaims as $badclaim) {
            // get any grade items
            $baditems = get_records_sql(
                "SELECT *
                 FROM {$CFG->prefix}grade_items
                 WHERE itemmodule = 'assessmgr'
                   AND itemnumber = {$badclaim->id}"
            );

            if(!empty($baditems)) {
                foreach($baditems as $baditem) {
                    // delete the item and the grade
                    delete_records('grade_items', 'id', $baditem->id);
                    delete_records('grade_grades', 'itemid', $baditem->id);
                }
            }

            // delete the claim
            delete_records($claimtable, 'id', $badclaim->id);
        }
    }

    $oldtable = get_records_sql("SHOW TABLES LIKE '{$CFG->prefix}block_assessmgr_ev_sub_ev_ty'");
    $subevtytable = empty($oldtable) ? 'block_assmgr_sub_evid_type' : 'block_assessmgr_ev_sub_ev_ty';
    $subevtyfield = empty($oldtable) ? 'submission_id' : 'evidence_submission_id';

    // delete any submission evidence types
    delete_records($subevtytable, $subevtyfield, $submission_id);

    $oldtable = get_records_sql("SHOW TABLES LIKE '{$CFG->prefix}block_assessmgr_ev_sub'");
    $subtable = empty($oldtable) ? 'block_assmgr_submission' : 'block_assessmgr_ev_sub';

    // delete the submission
    delete_records($subtable, 'id', $submission_id);

    // flush the buffer so we can see real time results
    flush();
}

/********************************************
 * THis function selects all activities in moodle that have outcomes grades or final grades
 * attached to them then it seelcts
 */

function get_synchronised_submission() {
       $sql = "SELECT   sub.id AS submission_id
              FROM  mdl_block_assmgr_evidence AS evid,
                        mdl_block_assmgr_resource AS res,
                        mdl_block_assmgr_res_moodle AS resmood,
                        mdl_block_assmgr_submission AS sub,
                        mdl_block_assmgr_portfolio AS portfolio
              WHERE     evid.id = res.evidence_id
              AND   res.tablename = 'block_assmgr_res_moodle'
              AND   res.record_id = resmood.id
              AND   sub.evidence_id = evid.id
              AND   portfolio.id = sub.portfolio_id
              AND ROW(evid.candidate_id, resmood.module_name, portfolio.course_id,resmood.activity_id) IN (

              SELECT gg.userid AS candidate_id,
                       m.name AS module_name,
                       cm.course AS course_id,
                       cm.instance AS activity_id
                  FROM mdl_course_modules  AS cm,
                       mdl_modules  AS m,
                       mdl_grade_items  AS gi,
                       mdl_grade_items  AS gio,
                       mdl_grade_grades  AS gg
                 WHERE cm.module = m.id

                   AND gi.itemtype = 'mod'
                   AND gi.itemmodule = m.name
                   AND gi.iteminstance = cm.instance
                   AND gi.outcomeid IS NULL
                   AND gi.itemtype = gio.itemtype
                   AND gi.itemmodule = gio.itemmodule
                   AND gi.iteminstance = gio.iteminstance
                   AND gio.outcomeid IS NOT NULL
                   AND (gi.id = gg.itemid OR gio.id = gg.itemid)
                   )";

        return get_records_sql($sql);
}

/********************************************
 * This function sets the synchroinse field of the
 * submissions with the given ids to 1
 *********************************************/
function update_submission_field($submission_ids) {
    $ids = implode(",",$submission_ids);

    execute_sql("UPDATE    mdl_block_assmgr_submission
                          SET       synchronise = 1
                          WHERE     id IN ({$ids})");

}

function assmgr_encode(&$data) {
    if(is_object($data) || is_array($data)) {
        // skip the flexible_table
        if(!is_a($data, 'flexible_table')) {
            foreach($data as $index => &$datum) {
                $datum = assmgr_encode($datum);
            }
        }
        return $data;
    } else {
        // encode the single value
        $data = trim(htmlentities($data, ENT_QUOTES, 'utf-8', false));
        // convert the empty string into null as such values break nullable FK fields
        return ($data == '') ? null : $data;
    }
}


/********************************************
 * Returns a recordset containing mdl_block_assmgr_grade records
 * with duplicates
 *********************************************/
function find_duplicate_grades() {
    $sql = "SELECT      MIN(id) as id
            FROM        mdl_block_assmgr_grade
            WHERE       outcome_id IS NOT NULL
            GROUP BY    submission_id, outcome_id
            HAVING COUNT(submission_id) > 1";
    return get_records_sql($sql);


}

/********************************************
 * Deletes all grades from the block_assmgr_grade
 * table that are in the given array of grade ids
 *********************************************/
function delete_grades($grade_ids) {
    $ids = implode(",",$grade_ids);

    execute_sql("DELETE FROM mdl_block_assmgr_grade
                  WHERE     id IN ({$ids})");
}

/********************************************
 * Returns a recordset containing block_assmgr_submission records
 * with duplicates
 *********************************************/
function find_duplicate_submissions() {
    $sql = "SELECT id
            FROM  mdl_block_assmgr_submission
            GROUP BY portfolio_id, evidence_id
            HAVING COUNT(portfolio_id) > 1";
    return get_records_sql($sql);
}

/********************************************
 * Returns a recordset containing block_assmgr_claim records
 * with duplicates
 *********************************************/
function find_duplicate_claims() {
    $sql = "SELECT id
            FROM  mdl_block_assmgr_claim
            GROUP BY submission_id, outcome_id
            HAVING COUNT(submission_id) > 1";

    return get_records_sql($sql);

}

/********************************************
 * Deletes all records from the block_assmgr_submission
 * table that have ids that are in the
 * given array of submission ids
 *********************************************/
function delete_submisssions($submission_ids) {
    $ids = implode(",",$submission_ids);
    $sql = "DELETE FROM mdl_block_assmgr_submission
                  WHERE     id IN ({$ids})";
    execute_sql($sql);
}

/********************************************
 * Deletes all claims from the block_assmgr_claim
 * table that have ids that are in the given array
 * of submission ids
 *********************************************/
function delete_claims($claims_ids) {
    $ids = implode(",",$claims_ids);
    $sql = "DELETE FROM mdl_block_assmgr_claim
                  WHERE     id IN ({$ids})";
    execute_sql($sql);
}

/********************************************
 * Deletes all grades from the block_assmgr_grade
 * table that have submission_id that are in the given
 * array of submission ids
 *********************************************/
function delete_submisssion_grades($submission_ids) {
    $ids = implode(",",$submission_ids);
    $sql = "DELETE FROM mdl_block_assmgr_grade
                  WHERE     submission_id IN ({$ids})";
    execute_sql($sql);
}

/********************************************
 * Deletes all records from the block_assmgr_grade
 * that are in the given array of submission ids
 *********************************************/
function delete_submisssion_claims($submission_ids) {
    $ids = implode(",",$submission_ids);
    $sql = "DELETE FROM mdl_block_assmgr_claim
                  WHERE     submission_id IN ({$ids})";
    execute_sql($sql);
}


/********************************************
 * Deletes all records from the block_assmgr_sub_evid_type
 * that are in the given array of submission ids
 *********************************************/
function delete_submisssion_sub_ev_type($submission_ids) {
    $ids = implode(",",$submission_ids);
    $sql = "DELETE FROM mdl_block_assmgr_sub_evid_type
                  WHERE     submission_id IN ({$ids})";
    execute_sql($sql);
}

/********************************************
 * Returns a recordset records contains the overall
 * grade_items.id, grade_items.outcomeid and gg.rawgrade
 * (even if it is not set)
  *********************************************/
/*
function get_unset_overall_outcome_grades($portfolio_id,$candidate_id) {

    $sql = "SELECT  gi.id,
                    gi.outcomeid as outcome_id,
                    gg.rawgrade as grade
            FROM    mdl_block_assmgr_portfolio AS p,
                    mdl_grade_items AS gi LEFT JOIN mdl_grade_grades AS gg
                    ON (gi.id = gg.itemid AND gg.userid = {$candidate_id})
            WHERE   p.id = {$portfolio_id}
            AND     p.course_id = gi.courseid
            AND     gi.itemtype = 'mod'
            AND     gi.iteminstance IS NULL
            AND     gi.itemmodule = 'block_assmgr'
            AND     gi.outcomeid  IN
                    (SELECT     DISTINCT(outcome_id)
                       FROM     mdl_block_assmgr_submission AS s,
                                mdl_block_assmgr_grade AS g
                        WHERE   s.id = g.submission_id
                        AND     s.portfolio_id = {$portfolio_id})";

     return get_records_sql($sql);
}
*/


function get_unset_overall_outcome_grades() {

    $sql = "SELECT      grade.id, port.id AS portfolio_id, port.candidate_id, grade.outcome_id
              FROM      mdl_block_assmgr_portfolio AS port,
                        mdl_block_assmgr_submission AS sub,
                        mdl_block_assmgr_grade AS grade
             WHERE      port.id = sub.portfolio_id
               AND      sub.id = grade.submission_id
               AND      grade.grade IS NOT NULL
               AND      ROW(grade.outcome_id, port.candidate_id, port.course_id) NOT IN
                            ( SELECT  gi.outcomeid, gg.userid, gi.courseid
                              FROM  mdl_grade_items AS gi,
                                    mdl_grade_grades AS gg
                             WHERE gi.id = gg.itemid
                               AND gi.itemtype = 'mod'
                               AND gi.itemmodule = 'block_assmgr'
                               AND gi.iteminstance IS NULL
                               AND gi.outcomeid IS NOT NULL
                               AND gg.finalgrade IS NOT NULL)
            GROUP BY port.id, grade.outcome_id";

         return get_records_sql($sql);
}



/********************************************
 * Updates the gradepass field to 1.0 in all grade_items
 * that use a scale that contains incomplete in them
  *********************************************/
function update_grade_pass()    {
        $sql = " UPDATE     mdl_grade_items
                    SET     gradepass = '2.00'
                  WHERE     itemtype = 'mod'
                    AND     iteminstance IS NULL
                    AND     itemmodule = 'block_assmgr'
                    AND     gradepass = 0
                    AND     outcomeid IN (SELECT    go.id
                                            FROM    mdl_grade_outcomes AS go,
                                                    mdl_scale AS s
                                            WHERE   go.scaleid = s.id
                                              AND    s.scale LIKE 'Incomplete%')";
        execute_sql($sql);
}


function get_portfolio_by_id($portfolio_id) {

    return get_record('block_assmgr_portfolio','id',$portfolio_id);


}


// helper function
function file_var_crap($mixed, $name = null) {
    global $CFG;

    static $start = null;

    if(empty($start)) {
        $start = time();
    }

    echo "<div style='border:1px solid red;'><strong style='color: red;'>{$name} (T:".(time()-$start).")</strong><pre>";
    ob_start();
    var_dump($mixed);
    $html = ob_get_clean();
    echo htmlspecialchars($html);
    echo '</pre></div>';

    $filename = $CFG->dataroot.'/temp/temp.txt';

    // Let's make sure the file exists and is writable first.
    if (is_writable($filename)) {

        // In our example we're opening $filename in append mode.
        // The file pointer is at the bottom of the file hence
        // that's where $html will go when we fwrite() it.
        if (!$handle = fopen($filename, 'a')) {
             echo "Cannot open file ($filename)";
             exit;
        }

        // Write $html to our opened file.
        if (fwrite($handle, "(T:".(time()-$start).") ".$html) === FALSE) {
            echo "Cannot write to file ($filename)";
            exit;
        }

        fclose($handle);

    } else {
        echo "The file $filename is not writable";
    }

    echo(str_repeat(' ',256));
    // check that buffer is actually set before flushing
    if (ob_get_length()){
        @ob_flush();
        @flush();
        @ob_end_flush();
    }
    @ob_start();
}

?>