<?php
// define the indicators
define('ATTAINMENT_LEARNING',       1);
define('FUNCTIONAL_SKILLS',         2);
define('EMPLOYMENT_SKILLS',         3);
define('MINIMUM_TARGET_GRADE',      4);
define('ASPIRATIONAL_TARGET_GRADE', 5);
// define the roles
define('ROLE_STUDENT',              5);

/**
 * Databse class for the Learner Progress Review (LPR) Block module.
 *
 * @copyright &copy; 2009 University of London Computer Centre
 * @author http://www.ulcc.ac.uk, http://moodle.ulcc.ac.uk
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package LPR
 * @version 1.0
 */
class block_lpr_db {

    /**
     * Gets the requested LPR
     *
     * @param string $id The id of the LPR
     * @return stdClass The result object
     */
    function get_lpr($id) {
        global $CFG;

        return get_record_sql(
            "SELECT lpr.*
             FROM {$CFG->prefix}block_lpr AS lpr
             WHERE lpr.id={$id}"
        );
    }

    /**
     * Updates an LPR record.
     *
     * @param string $lpr The LPR object
     */
    function set_lpr($lpr) {
        update_record('block_lpr', $lpr);
    }

    /**
     * Creates a new LPR record.
     *
     * @param string $lpr The LPR object.
     */
    function create_lpr($lpr) {
        return insert_record('block_lpr', $lpr, true);
    }

    /**
     * Deletes an LPR record.
     *
     * @param string $id The id of the LPR record
     */
    function delete_lpr($id) {
        delete_records('block_lpr', 'id', $id);
    }

    /**
     * Gets the details of all existing LPRs for this learner/course
     *
     * @see The ILP template page
     *
     * @param string $learner_id The id of the learner
     * @param string $course_id The id of the course
     * @param string $sortorder The direction of sorting
     * @param string $limit The numer of LPRs to return
     * @return array The array of result objects
     */
    function get_lprs($learner_id = null, $course_id = null, $sortorder = 'ASC', $limit = null) {
        global $CFG;

        $where = array();

        if(!empty($learner_id))
            $where[] = "lpr.learner_id={$learner_id}";
        if(!empty($course_id))
            $where[] = "lpr.course_id={$course_id}";

        return get_records_sql(
            "SELECT lpr.*
             FROM {$CFG->prefix}block_lpr AS lpr
             WHERE " . (!empty($where) ? implode(" AND ", $where) : "1=1") ."
             ORDER BY lpr.timecreated {$sortorder} " .
             (($limit > 0) ? "LIMIT {$limit}" : '')
        );
    }

   /**
     * Gets the details of all existing LPRs for this learner/course, using
     * filters and sort keys provided by the sortable table.
     *
     * @param string $learner_id The id of the learner
     * @param string $course_id The id of the course
     * @param string $sortorder The direction of sorting
     * @param string $limit The numer of LPRs to return
     * @return array The array of result objects
     */
    function list_lprs($learner_id = null, $course_id = null, $table = null) {

        global $CFG;

        if(!empty($learner_id))
            $where[] = "lpr.learner_id={$learner_id}";
        if(!empty($course_id))
            $where[] = "lpr.course_id={$course_id}";

        // build the query to fetch the LPRs and their related data
        $select = "SELECT lpr.id AS lpr_id,
                          lpr.name AS lpr_name,
                          lpr.timemodified,
                          lpr.course_id,
                          lpr.learner_id,
                          lpr.lecturer_id,
                          c.fullname AS course_name,
                          l.picture AS learner_picture,
                          l.firstname, l.lastname,
                          r.firstname AS reporter_firstname,
                          r.lastname AS reporter_lastname ";

        $from = "FROM {$CFG->prefix}block_lpr lpr
                  LEFT JOIN {$CFG->prefix}course c ON (lpr.course_id = c.id)
                  LEFT JOIN {$CFG->prefix}user l ON (lpr.learner_id = l.id)
                  LEFT JOIN {$CFG->prefix}user r ON (lpr.lecturer_id = r.id) ";

        $where = "WHERE ".(!empty($where)? implode(' AND ', $where) : "1=1");

        $sort = '';

        // fetch any filters provided by the table
        if ($table->get_sql_where()) {
            $where .= ' AND ';
            $where .= preg_replace(
                array('/firstname/', '/lastname/'),
                array('l.firstname', 'l.lastname'),
                $table->get_sql_where());
        }

        // fetch any sort keys provided by the table
        if ($table->get_sql_sort()) {
              $sort = ' ORDER BY '.$table->get_sql_sort();
        }

        // fetch the perpage limit
        $perpage = get_user_preferences('target_perpage', 10);

        // get a count of all the records for the pagination links
        $count = count_records_sql('SELECT count(lpr.id) '.$from.$where);

        // tell the table how many pages it needs
        $table->pagesize($perpage, $count);

        // execute the paginated query
        return get_records_sql(
            $select.$from.$where.$sort,
            $table->get_page_start(),
            $table->get_page_size()
        );

    }

    /**
     * Counts how many courses there are in a set of categories.
     *
     * @param array $cats An array of category IDs.
     * @return array The array of result objects.
     */
    function count_courses_by_cat($cats) {
        global $CFG;
        $cats = implode(',', $cats);
        return get_field_sql(
            "SELECT COUNT(*) AS count
             FROM {$CFG->prefix}course AS c
             WHERE c.category IN ($cats)"
        );
    }

    /**
     * Counts how many LPRs there are in a set of categories.
     *
     * @param array $cats An array of category IDs.
     * @param int $start_time The unix timestamp after which the LPR was created.
     * @param int $end_time The unix timestamp before which the LPR was created.
     * @return array The array of result objects.
     */
    function count_lprs_by_cat($cats, $start_time = null, $end_time = null) {
        global $CFG;
        $cats = implode(',', $cats);
        return get_field_sql(
            "SELECT COUNT(*) AS count
             FROM {$CFG->prefix}course AS c,
                  {$CFG->prefix}block_lpr AS lpr
             WHERE lpr.course_id = c.id
               AND c.category IN ($cats) "
                 . (!empty($start_time) ? " AND lpr.timecreated > {$start_time} " : "")
                 . (!empty($end_time)   ? " AND lpr.timecreated < {$end_time} "   : "")
        );
    }

    /**
     * Counts how many LPRs there are for a given learner / course
     *
     * @param string $learner_id The id of the learner
     * @param string $course_id The id of the course
     * @param int $start_time The unix timestamp after which the LPR was created.
     * @param int $end_time The unix timestamp before which the LPR was created.
     * @return array The array of result objects
     */
    function count_lprs($learner_id = null, $course_id = null, $start_time = null, $end_time = null) {
        global $CFG;

        $where = array();

        if(!empty($learner_id))
            $where[] = "lpr.learner_id={$learner_id}";
        if(!empty($course_id))
            $where[] = "lpr.course_id={$course_id}";
        if(!empty($start_time))
            $where[] = "lpr.timecreated > {$start_time}";
        if(!empty($end_time))
            $where[] = "lpr.timecreated < {$end_time}";

        return get_field_sql(
            "SELECT COUNT(*)
             FROM {$CFG->prefix}block_lpr AS lpr
             WHERE " . (!empty($where) ? implode(" AND ", $where) : "1=1")
        );
    }

    /**
     * Counts how many 'At Risk' LPRs there are for a given learner / course
     *
     * @param string $learner_id The id of the learner
     * @param string $course_id The id of the course
     * @param int $start_time The unix timestamp after which the LPR was created.
     * @param int $end_time The unix timestamp before which the LPR was created.
     * @return array The array of result objects
     */
    function count_lpr_risks($learner_id = null, $course_id = null, $start_time = null, $end_time = null) {
        global $CFG;

        return get_field_sql(
            "SELECT COUNT(*)
             FROM {$CFG->prefix}block_lpr AS lpr,
                  {$CFG->prefix}block_lpr_indicator_answers AS attain,
                  {$CFG->prefix}block_lpr_indicator_answers AS target
             WHERE attain.lpr_id = lpr.id
               AND attain.indicator_id = ".ATTAINMENT_LEARNING."
               AND target.lpr_id = lpr.id
               AND target.indicator_id = ".MINIMUM_TARGET_GRADE."
               AND attain.answer < target.answer "
                 . (!empty($learner_id) ? " AND lpr.learner_id={$learner_id} " : "")
                 . (!empty($course_id)  ? " AND lpr.course_id={$course_id} " : "")
                 . (!empty($start_time) ? " AND lpr.timecreated > {$start_time} " : "")
                 . (!empty($end_time)   ? " AND lpr.timecreated < {$end_time} "   : "")
        );
    }

    /**
     * Counts how many LPRs there are in a set of categories.
     *
     * @param array $cats An array of category IDs.
     * @param int $start_time The unix timestamp after which the LPR was created.
     * @param int $end_time The unix timestamp before which the LPR was created.
     * @return array The array of result objects.
     */
    function count_lpr_risks_by_cat($cats, $start_time = null, $end_time = null) {
        global $CFG;

        $cats = implode(',', $cats);

        return get_field_sql(
            "SELECT COUNT(*)
             FROM {$CFG->prefix}course AS course,
                  {$CFG->prefix}block_lpr AS lpr,
                  {$CFG->prefix}block_lpr_indicator_answers AS attain,
                  {$CFG->prefix}block_lpr_indicator_answers AS target
             WHERE lpr.course_id = course.id
               AND course.category IN ($cats)
               AND attain.lpr_id = lpr.id
               AND attain.indicator_id = ".ATTAINMENT_LEARNING."
               AND target.lpr_id = lpr.id
               AND target.indicator_id = ".MINIMUM_TARGET_GRADE."
               AND attain.answer < target.answer "
                 . (!empty($start_time) ? " AND lpr.timecreated > {$start_time} " : "")
                 . (!empty($end_time)   ? " AND lpr.timecreated < {$end_time} "   : "")
        );
    }

    /**
     * Gets all the 'At Risk' LPRs for a set of categories and / or learner / course
     *
     * @param array $categories An array of category IDs
     * @param string $learner_id The id of the learner
     * @param string $course_id The id of the course
     * @param int $start_time The unix timestamp after which the LPR was created.
     * @param int $end_time The unix timestamp before which the LPR was created.
     * @return array The array of result objects
     */
    function get_lpr_risks($categories = null, $learner_id = null, $course_id = null, $table = null, $start_time = null, $end_time = null) {
        global $CFG;

        // build the query to fetch the LPRs and their related data
        $select =  "SELECT lpr.id AS lpr_id,
                          lpr.name AS lpr_name,
                          lpr.timemodified,
                          lpr.course_id,
                          lpr.learner_id,
                          lpr.lecturer_id,
                          c.fullname AS course_name,
                          l.picture AS learner_picture,
                          l.firstname, l.lastname,
                          r.firstname AS reporter_firstname,
                          r.lastname AS reporter_lastname ";

        $from = "FROM {$CFG->prefix}block_lpr_indicator_answers AS attain,
                      {$CFG->prefix}block_lpr_indicator_answers AS target,
                      {$CFG->prefix}block_lpr lpr
                  LEFT JOIN {$CFG->prefix}course c ON (lpr.course_id = c.id)
                  LEFT JOIN {$CFG->prefix}user l ON (lpr.learner_id = l.id)
                  LEFT JOIN {$CFG->prefix}user r ON (lpr.lecturer_id = r.id) ";

        $where = "WHERE l.id = lpr.learner_id
                    AND c.id = lpr.course_id
                    AND attain.lpr_id = lpr.id
                    AND attain.indicator_id = ".ATTAINMENT_LEARNING."
                    AND target.lpr_id = lpr.id
                    AND target.indicator_id = ".MINIMUM_TARGET_GRADE."
                    AND attain.answer < target.answer "
                      . (!empty($learner_id) ? " AND lpr.learner_id={$learner_id} " : "")
                      . (!empty($course_id)  ? " AND lpr.course_id={$course_id} " : "")
                      . (!empty($categories) ? " AND c.category IN (".(implode(',',$categories)).")" : "")
                      . (!empty($start_time) ? " AND lpr.timecreated > {$start_time} " : "")
                      . (!empty($end_time)   ? " AND lpr.timecreated < {$end_time} "   : "");

        $sort = '';

        // fetch any filters provided by the table
        if ($table->get_sql_where()) {
            $where .= ' AND ';
            $where .= preg_replace(
                array('/firstname/', '/lastname/'),
                array('l.firstname', 'l.lastname'),
                $table->get_sql_where());
        }

        // fetch any sort keys provided by the table
        if($table->get_sql_sort()) {
              $sort = ' ORDER BY '.$table->get_sql_sort();
        }

        // get a count of all the records for the pagination links
        $count = count_records_sql('SELECT count(*) '.$from.$where);

        // fetch the perpage limit
        $perpage = get_user_preferences('target_perpage', 10);

        // check what page we're on now
        $pages = optional_param('page', 0, PARAM_INT);

        // tell the table how many pages it needs
        $table->pagesize($perpage, $count);

        // execute the paginated query
        return get_records_sql(
            $select.$from.$where.$sort,
            $table->get_page_start(),
            $table->get_page_size()
        );
    }

    /**
     * Counts how many learners have LPRs in a set of categories.
     *
     * @param array $cats An array of category IDs.
     * @param int $start_time The unix timestamp after which the LPR was created.
     * @param int $end_time The unix timestamp before which the LPR was created.
     * @return array The array of result objects.
     */
    function count_lpr_learners_by_cat($cats, $start_time = null, $end_time = null) {
        global $CFG;
        $cats = implode(',', $cats);
        return get_field_sql(
            "SELECT COUNT(*) AS count
             FROM (
                 SELECT l.id
                 FROM {$CFG->prefix}course AS c,
                      {$CFG->prefix}block_lpr AS lpr,
                      {$CFG->prefix}user AS l
                 WHERE lpr.course_id = c.id
                   AND lpr.learner_id = l.id
                   AND c.category IN ({$cats}) "
                     . (!empty($start_time) ? " AND lpr.timecreated > {$start_time} " : "")
                     . (!empty($end_time)   ? " AND lpr.timecreated < {$end_time} "   : "")."
                 GROUP BY l.id
             ) AS learners"
        );
    }

    /**
     * Counts how many learners have LPRs in a given course.
     *
     * @param string $course_id The id of the course
     * @param int $start_time The unix timestamp after which the LPR was created.
     * @param int $end_time The unix timestamp before which the LPR was created.
     * @return array The array of result objects.
     */
    function count_lpr_learners($course_id, $start_time = null, $end_time = null) {
        global $CFG;
        return get_field_sql(
            "SELECT COUNT(*) AS count
             FROM (
                 SELECT lpr.learner_id
                 FROM {$CFG->prefix}block_lpr AS lpr
                 WHERE lpr.course_id = {$course_id} "
                     . (!empty($start_time) ? " AND lpr.timecreated > {$start_time} " : "")
                     . (!empty($end_time)   ? " AND lpr.timecreated < {$end_time} "   : "")."
                 GROUP BY lpr.learner_id
             ) AS learners"
        );
    }

    /**
     * Counts how many courses have LPRs in a set of categories.
     *
     * @param array $cats An array of category IDs.
     * @param int $start_time The unix timestamp after which the LPR was created.
     * @param int $end_time The unix timestamp before which the LPR was created.
     * @return array The array of result objects.
     */
    function count_lpr_courses_by_cat($cats, $start_time = null, $end_time = null) {
        global $CFG;
        $cats = implode(',', $cats);
        return get_field_sql(
            "SELECT COUNT(*) AS count
             FROM (
                 SELECT c.id
                 FROM {$CFG->prefix}course AS c,
                      {$CFG->prefix}block_lpr AS lpr
                 WHERE lpr.course_id = c.id
                   AND c.category IN ({$cats}) "
                     . (!empty($start_time) ? " AND lpr.timecreated > {$start_time} " : "")
                     . (!empty($end_time)   ? " AND lpr.timecreated < {$end_time} "   : "")."
                 GROUP BY c.id
             ) AS courses"
        );
    }

    /**
     * Gets the list of all indicators.
     *
     * @return array The array of result objects.
     */
    function get_indicators() {
        global $CFG;

        return get_records_sql(
            "SELECT *
             FROM {$CFG->prefix}block_lpr_indicators AS ind
             ORDER BY ind.id"
        );
    }

    /**
     * Gets all the indicator answers for the given lpr.
     *
     * @param string $lpr_id The id of the LPR
     * @param int $start_time The unix timestamp after which the LPR was created.
     * @param int $end_time The unix timestamp before which the LPR was created.
     * @return array The array of result objects
     */
    function get_indicator_answers($lpr_id, $start_time = null, $end_time = null) {
        global $CFG;
        // fetch the indicator answers for the given LRP
        return get_records_sql(
            "SELECT ans.indicator_id, ans.*
             FROM {$CFG->prefix}block_lpr_indicator_answers AS ans
             WHERE ans.lpr_id={$lpr_id} "
                . (!empty($start_time) ? " AND lpr.timecreated > {$start_time} " : "")
                . (!empty($end_time)   ? " AND lpr.timecreated < {$end_time} "   : "")
        );
    }

    /**
     * Gets the averages of all the indicator answers for the given learner / course.
     *
     * @param string $learner_id The id of the learner
     * @param string $course_id The id of the course
     * @param int $start_time The unix timestamp after which the LPR was created.
     * @param int $end_time The unix timestamp before which the LPR was created.
     * @return array The array of result objects
     */
    function get_indicator_answers_avg($learner_id = null, $course_id = null, $start_time = null, $end_time = null) {
        global $CFG;
        // fetch the indicator answers for the given LRP
        return get_records_sql(
            "SELECT ans.indicator_id, AVG(answer) AS answer
             FROM {$CFG->prefix}block_lpr AS lpr,
                  {$CFG->prefix}block_lpr_indicator_answers AS ans
             WHERE ans.lpr_id = lpr.id "
               . (!empty($learner_id) ? " AND lpr.learner_id = {$learner_id} " : '')
               . (!empty($course_id)  ? " AND lpr.course_id  = {$course_id}"   : '')
               . (!empty($start_time) ? " AND lpr.timecreated > {$start_time} " : "")
               . (!empty($end_time)   ? " AND lpr.timecreated < {$end_time} "   : "")."
             GROUP BY ans.indicator_id"
        );
    }

    /**
     * Gets the averages of all the indicator answers for the given category and
     * all its sub-categories.
     *
     * @param string $category_id The id of the category
     * @param int $start_time The unix timestamp after which the LPR was created.
     * @param int $end_time The unix timestamp before which the LPR was created.
     * @return array The array of result objects
     */
    function get_cat_indicator_avg($category_id = null, $start_time = null, $end_time = null) {
        global $CFG;

        if(!empty($category_id)) {
            $cats = array_keys(get_categories($category_id, null, false));
            $cat_condition = "c.category IN (".(implode(',', $cats)).")";
        } else {
            $cat_condition = "1=1";
        }

        // fetch the average of the sttendance data for the given category
        return get_records_sql(
            "SELECT ans.indicator_id, AVG(answer) AS answer
             FROM {$CFG->prefix}block_lpr AS lpr,
                  {$CFG->prefix}block_lpr_indicator_answers AS ans,
                  {$CFG->prefix}course AS c
             WHERE ans.lpr_id = lpr.id
               AND lpr.course_id  = c.id
               AND {$cat_condition} "
                 . (!empty($start_time) ? " AND lpr.timecreated > {$start_time} " : "")
                 . (!empty($end_time)   ? " AND lpr.timecreated < {$end_time} "   : "")."
             GROUP BY ans.indicator_id"
        );
    }

    /**
     * Creates a new indicator_answer record.
     *
     * @param string $data The indicator_answer object.
     */
    function create_indicator_answer($data) {
        insert_record('block_lpr_indicator_answers', $data);
    }

    /**
     * Deletes all indicator_answer records for a given LPR.
     *
     * @param string $lpr_id The id of the LPR.
     */
    function delete_indicator_answers($lpr_id) {
        delete_records('block_lpr_indicator_answers', 'lpr_id', $lpr_id);
    }


    /**
     * Gets the attendance data for a given LPR.
     *
     * @param string $lpr_id The id of the LPR
     * @return array The array of result objects
     */
    function get_attendance($lpr_id) {
        global $CFG;
        // fetch the sttendance data for the given LRP
        return get_record_sql(
            "SELECT att.lpr_id, att.*
             FROM {$CFG->prefix}block_lpr_attendances AS att
             WHERE att.lpr_id={$lpr_id}"
        );
    }

    /**
     * Gets the average of the attendance data for the given learner / course.
     *
     * @param string $learner_id The id of the learner
     * @param string $course_id The id of the course
     * @param int $start_time The unix timestamp after which the LPR was created.
     * @param int $end_time The unix timestamp before which the LPR was created.
     * @return array The array of result objects
     */
    function get_attendance_avg($learner_id = null, $course_id = null, $start_time = null, $end_time = null) {
        global $CFG;
        // fetch the average of the sttendance data for the given learner / course
			   
        return get_record_sql(
            "SELECT AVG(attendance) AS attendance, AVG(punctuality) AS punctuality
             FROM {$CFG->prefix}block_lpr AS lpr,
                  {$CFG->prefix}block_lpr_attendances AS att
             WHERE att.lpr_id = lpr.id "
               . (!empty($learner_id) ? " AND lpr.learner_id = {$learner_id} " : '')
               . (!empty($course_id)  ? " AND lpr.course_id  = {$course_id}"   : '')
               . (!empty($start_time) ? " AND lpr.timecreated > {$start_time} " : "")
               . (!empty($end_time)   ? " AND lpr.timecreated < {$end_time} "   : "")
        );
   
    }

    /**
     * Gets the average of the attendance data for the given category and all its
     * sub-categories.
     *
     * @param string $category_id The id of the category
     * @param int $start_time The unix timestamp after which the LPR was created.
     * @param int $end_time The unix timestamp before which the LPR was created.
     * @return array The array of result objects
     */
    function get_cat_attendance_avg($category_id = null, $start_time = null, $end_time = null) {
        global $CFG;

        if(!empty($category_id)) {
            $cats = array_keys(get_categories($category_id, null, false));
            $cat_condition = "c.category IN (".(implode(',', $cats)).")";
        } else {
            $cat_condition = "1=1";
        }

        // fetch the average of the sttendance data for the given category
        return get_record_sql(
            "SELECT AVG(attendance) AS attendance, AVG(punctuality) AS punctuality
             FROM {$CFG->prefix}block_lpr AS lpr,
                  {$CFG->prefix}block_lpr_attendances AS att,
                  {$CFG->prefix}course AS c
             WHERE att.lpr_id = lpr.id
               AND lpr.course_id  = c.id
               AND {$cat_condition} "
                 . (!empty($start_time) ? " AND lpr.timecreated > {$start_time} " : "")
                 . (!empty($end_time)   ? " AND lpr.timecreated < {$end_time} "   : "")
        );
    }

    /**
     * Creates a new attendance record.
     *
     * @param string $data The attendance object.
     */
    function create_attendance($data) {
        return insert_record('block_lpr_attendances', $data, true);
    }

    /**
     * Deletes all attendance records for a given LPR.
     *
     * @param string $lpr_id The id of the LPR.
     */
    function delete_attendances($lpr_id) {
        delete_records('block_lpr_attendances', 'lpr_id', $lpr_id);
    }

    /**
     * Gets the sequuence number of the next LPR for the given learner/course/type.
     *
     * @param string $learner_id The id of the learner.
     * @param string $course_id The id of the course.
     * @return array The array of destination objects.
     */
    function get_next_sequence($learner_id, $course_id) {
        global $CFG;
        return get_record_sql(
            "SELECT IFNULL(MAX(lpr.sequence), 0)+1 AS next
             FROM {$CFG->prefix}block_lpr AS lpr
             WHERE lpr.learner_id = {$learner_id}
              AND lpr.course_id = {$course_id}"
        );
    }

    /**
     * Gets the full list of categories.
     *
     * @return array The array of result objects
     */
    function get_categories() {
        global $CFG;
        // fetch the list of categories
        return get_records_sql(
            "SELECT cat.id, cat.name
             FROM {$CFG->prefix}course_categories AS cat
             ORDER BY cat.sortorder ASC"
        );
    }

    /**
     * Gets the full list of learners, as defined by the learner_id field in the
     * LPRs.
     *
     * @return array The array of result objects
     */
    function get_learners() {
        global $CFG;
        return get_records_sql(
            "SELECT usr.id, CONCAT_WS(' ', usr.firstname, usr.lastname) AS name
             FROM {$CFG->prefix}block_lpr AS lpr,
                  {$CFG->prefix}user AS usr
             WHERE lpr.learner_id = usr.id
             ORDER BY usr.firstname ASC"
        );
    }

    /**
     * Gets a a list of LPRs for printing, filtered by a category/tutor/learner/datetime.
     *
     * @param string $category_id The id of the category
     * @param string $learner_id The id of the learner
     * @param int $start_time The unix timestamp after which the LPR was created.
     * @param int $end_time The unix timestamp before which the LPR was created.
     * @return array The array of result objects
     */
    function get_lprs_for_print($category_id = null, $learner_id = null, $start_time = null, $end_time = null) {
        global $CFG;

        // build the query to fetch the LPRs and their related data
        $select =  "SELECT @rownum:=@rownum+1 AS rownum, lpr.id, l.idnumber, l.id AS learner_id ";

        $from = "FROM {$CFG->prefix}block_lpr lpr
                  LEFT JOIN {$CFG->prefix}course c ON (lpr.course_id = c.id)
                  LEFT JOIN {$CFG->prefix}user l ON (lpr.learner_id = l.id),
                  (SELECT @rownum:=0) r ";

        $where = "WHERE 1=1 "
                      . (!empty($category_id)? " AND c.category={$category_id} " : "")
                      . (!empty($learner_id) ? " AND lpr.learner_id={$learner_id} " : "")
                      . (!empty($start_time) ? " AND lpr.timecreated > {$start_time} " : "")
                      . (!empty($end_time)   ? " AND lpr.timecreated < {$end_time} "   : "");

        $sort = 'ORDER BY l.idnumber ASC';

        return get_records_sql($select.$from.$where.$sort);
    }

    /**
     * Gets a a list of LPRs for printing, based off an aray of idnumbers.
     *
     * @param array $idnumbers An array of student idnumbers (i.e. external IDs)
     * @param int $start_time The unix timestamp after which the LPR was created.
     * @param int $end_time The unix timestamp before which the LPR was created.
     * @return array The array of result objects
     */
    function get_lprs_for_print_by_idnumber($idnumbers, $start_time = null, $end_time = null) {
        global $CFG;

        // build the query to fetch the LPRs and their related data
        $select =  "SELECT @rownum:=@rownum+1 AS rownum, lpr.id, l.idnumber, l.id AS learner_id ";

        $from = "FROM {$CFG->prefix}user l
                    LEFT JOIN {$CFG->prefix}block_lpr lpr ON (lpr.learner_id = l.id)
                    LEFT JOIN {$CFG->prefix}course c ON (lpr.course_id = c.id),
                    (SELECT @rownum:=0) r ";

        $where = "WHERE 1=1 "
                      . (!empty($idnumbers)  ? " AND l.idnumber IN (".implode(',', $idnumbers).") " : "")
                      . (!empty($start_time) ? " AND lpr.timecreated > {$start_time} " : "")
                      . (!empty($end_time)   ? " AND lpr.timecreated < {$end_time} "   : "");

        $sort = 'ORDER BY l.idnumber ASC';
		

        return get_records_sql($select.$from.$where.$sort);
    }

    /**
     * Gets a list of students for a given course.
     *
     * @param array $course_id The id of the course
     * @return array The array of result objects
     */
    function get_students($course_id) {

        // get the current context
        $context = get_context_instance(CONTEXT_COURSE, $course_id);

        // we are looking for all users with this role assigned in this context or higher
        $listofcontexts = get_parent_contexts($context);

        // add the site context if there is no parent
        if(empty($listofcontexts)) {
            $systemcontext = get_context_instance(CONTEXT_SYSTEM, SITEID);
            $listofcontexts[] = $systemcontext->id;
        }

        // add the current context
        $listofcontexts[] = $context->id;

        // N.B. role of 5 == "student"
        $role->id = 5;

        $role_assignments = array();

        // get the list of student IDs
        foreach($listofcontexts as $context_id) {
            $instance = get_context_instance_by_id($context_id);
            $context_users = get_users_from_role_on_context($role, $instance);
            if(!empty($context_users)) {
                $role_assignments = array_merge($role_assignments, $context_users);
            }
        }

        $students = array();

        foreach($role_assignments as $ra) {
            // only retrieve new students
            if(empty($students[$ra->userid])) {
                // get the student's user record
                $student = get_record('user', 'id', $ra->userid, '', '', '', '', 'id, firstname, lastname');
                // N.B. users can be deleted without deleting their role assignments
                // so $ra->userid may not resolve to an actual record
                if(!empty($student)) {
                    $students[$student->id] = $student;
                }
            }
        }

        // TODO the list of students needs sorting

        return $students;
    }

    /**
     * Gets a list of students for a given category, all its sub-categories, and
     * all their courses.
     *
     * N.B. This does not check that the student id returned from the role assignments
     * table actually exists, which it may not because Moodle doesn't support
     * proper foreign key references!
     *
     * @param array $courses An array of course IDs.
     * @return array The array of result objects
     */
    function get_students_by_cat($category_id = null) {
        global $CFG;

        $contexts = array();

        // get all the sub-categories for the given category
        $cats = array_keys(get_categories((empty($category_id) ? 'none' : $category_id), null, false));

        // get all the context instances for those categories
        foreach($cats as $category_id) {
            $contexts[] = get_context_instance(CONTEXT_COURSECAT, $category_id);
        }

        // get all the courses for all those categories
        $courses = get_records_sql(
            "SELECT c.id
             FROM {$CFG->prefix}course AS c
             WHERE c.category IN (".(implode(',', $cats)).")"
        );

        // get all the context instances for all those courses
        foreach($courses as $course) {
            $contexts[] = get_context_instance(CONTEXT_COURSE, $course->id);
        }

        $role->id = ROLE_STUDENT;

        $students = array();

        // get the role assignment records, containing all the students, based on
        // the complete list of contexts we've made
        foreach($contexts as $instance) {
            $context_users = get_users_from_role_on_context($role, $instance);
            if(!empty($context_users)) {
                foreach($context_users as $user) {
                    $students[$user->userid] = $user;
                }
            }
        }

        return $students;
    }

   /**
     * Takes a list of students and returns a count of how many don't have a
     * completed ILP.
     *
     * @param array $students An array of student records.
     * @return array The array of result objects
     */
    function count_incomplete_ilps($students, $start_time = null, $end_time = null) {
        global $CFG;

        $student_list = implode(', ', array_keys($students));

        // count the students {@see get_students_by_cat()} and the incomplete ILPs
        $count = get_record_sql(
            "SELECT COUNT(user_id) AS students, SUM(IF(ip_id IS NULL, 1, 0)) AS incomplete
             FROM (
                SELECT u.id AS user_id, ip.id AS ip_id
                FROM {$CFG->prefix}user AS u
                  LEFT JOIN {$CFG->prefix}ilpconcern_posts AS ip ON (
                    ip.setforuserid = u.id AND ip.status = 0 "
                    . (!empty($start_time) ? " AND ip.deadline > {$start_time} " : "")
                    . (!empty($end_time)   ? " AND ip.deadline < {$end_time} "   : "")."
                  )
                WHERE u.id IN ({$student_list})
                GROUP BY u.id
             ) AS students"
        );

        return $count;
    }

    /**
     * Gets all the students who do not have complete ILPs within a category
     * or a course.
     *
     * @param string $category_id The id of the category.
     * @param string $course_id The id of the course.
     * @param int $start_time The unix timestamp after which the LPR was created.
     * @param int $end_time The unix timestamp before which the LPR was created.
     * @return array The array of result objects
     */
    function get_incomplete_ilps($category_id, $course_id, $table, $start_time, $end_time) {
        global $CFG;

        // get the complete list of students
        $students = (!empty($course_id))
             ? $this->get_students($course_id)
             : $this->get_students_by_cat($category_id);

        $student_list = implode(', ', array_keys($students));

        $select = "SELECT u.* ";

        $from = "FROM {$CFG->prefix}user AS u
                  LEFT JOIN {$CFG->prefix}ilpconcern_posts AS ip
                         ON (ip.setforuserid = u.id
                             AND ip.status = 0 " .
                             (!empty($start_time) ? " AND ip.deadline > {$start_time} " : "") .
                             (!empty($end_time)   ? " AND ip.deadline < {$end_time} "   : "") . "
                         ) ";

        $where = "WHERE u.id IN ({$student_list})
                    AND ip.id IS NULL ";

        $group = "GROUP BY u.id ";

        $sort = '';

        // fetch any filters provided by the table
        if ($table->get_sql_where()) {
            $where .= ' AND '.$table->get_sql_where();
        }

        // fetch any sort keys provided by the table
        if ($table->get_sql_sort()) {
              $sort = 'ORDER BY '.$table->get_sql_sort();
        }

        // fetch the perpage limit
        $perpage = get_user_preferences('target_perpage', 10);

        // get a count of all the records for the pagination links
        $count = count_records_sql("SELECT COUNT(*) FROM ({$select}{$from}{$where}{$group}) as ilps");
        // tell the table how many pages it needs
        $table->pagesize($perpage, $count);

        // execute the paginated query
        return get_records_sql(
            $select.$from.$where.$group.$sort,
            $table->get_page_start(),
            $table->get_page_size()
        );
    }

    /**
     * Returns true or false if there is a complete ILP for the given learner
     * within the given range.
     *
     * @param string $learner_id The id of the learner.
     * @param int $start_time The unix timestamp after which the ILP was completed.
     * @param int $end_time The unix timestamp before which the ILP was completed.
     * @return boolean The completness of the learner's ILP.
     */
    function is_ilp_complete($learner_id, $start_time, $end_time) {
        global $CFG;

        return get_field_sql(
            "SELECT (COUNT(*) > 0)
             FROM {$CFG->prefix}ilpconcern_posts ip
             WHERE ip.status = 0
               AND ip.setforuserid = {$learner_id} "
                 . (!empty($start_time) ? " AND ip.deadline > {$start_time} " : "")
                 . (!empty($end_time)   ? " AND ip.deadline < {$end_time} "   : "")
        );
    }

    /**
     * Gets the tutor reviews for a given leanrer within a given date range.
     *
     * @param string $learner_id The id of the learner.
     * @param int $start_time The unix timestamp after which the ILP was completed.
     * @param int $end_time The unix timestamp before which the ILP was completed.
     * @return boolean The completness of the learner's ILP.
     */
    function get_tutor_reviews($learner_id, $start_time, $end_time) {
        global $CFG;

        return get_records_sql(
            "SELECT ip.id, ip.concernset, u.firstname, u.lastname
             FROM {$CFG->prefix}ilpconcern_posts AS ip
                LEFT JOIN {$CFG->prefix}user AS u ON (u.id = ip.setbyuserid)
             WHERE ip.status = 0
               AND ip.setforuserid = {$learner_id} "
                 . (!empty($start_time) ? " AND ip.deadline > {$start_time} " : "")
                 . (!empty($end_time)   ? " AND ip.deadline < {$end_time} "   : "")
        );
    }
	
    /**
     * Saves modules codes to the LPR for a user
     *
     * @param object $data The data of the MIS LPR records of the learner.
     * @return boolean success of save.
     */
    function save_module($data) {
        return insert_record('block_lpr_mis_modules', $data, true);
	}
	/**
     * Gets the modules attached to an LPR
     *
     * @param string $lpr_id The id of the LPR
     * @return array The list of Modules.
     */
    function get_modules($lpr_id, $selected=null) {
        global $CFG;

        
        $sql = "SELECT *
                FROM {$CFG->prefix}block_lpr_mis_modules
                WHERE lpr_id= $lpr_id ";
				
		if(!is_null($selected)) {
			$sql .= ($selected === true) ? " AND selected = 1" : " AND selected = 0";
		}
			 
        return get_records_sql($sql);
    }

	/**
     *  work out the average attendance and punctuality 
     *
     * @param string $lpr_id The id of the LPR
     * @return object of averages
     */	
	function get_average_selected($lpr_id) {
		global $CFG;
		
		return get_record_sql(
				"SELECT (SUM(marks_present)/SUM(marks_total)) AS attendance,
                    (SUM(punct_positive)/SUM(marks_present)) AS punctuality
				FROM {$CFG->prefix}block_lpr_mis_modules
				WHERE lpr_id= $lpr_id 
				  AND selected=1
				GROUP BY lpr_id"
		);
	}
	
	function get_tutor($learner_id) {
		global $CFG;
		
        return get_record_sql(
            "SELECT u.*
             FROM {$CFG->prefix}ilpconcern_posts AS ip,
                  {$CFG->prefix}user AS u
             WHERE ip.status = 0
			   AND u.id = ip.setbyuserid
               AND ip.setforuserid = {$learner_id}
			ORDER BY ip.deadline DESC"
        );	
	}
	
	 // nkowald - 2010-11-15 - Adding Target data into here for use in PDF exports
/**
	* Displays the ilptarget summary to the ILP
	*
	* @param id   			userid fed from ILP page (required)
	* @param courseid   	courseid fed from ILP page (required)
	* @param full   		display a full report or just a title link - for layout and navigation
	* @param sortorder     DESC or ASC - to sort on deadline dates
	 * @param limit		    limit the number of targets shown on the page
	 * @param status	    -1 means all otherwise a particular status can be entered
	 * @param tutorsetonly 	display tutor set targets only
	 * @param studentsetonly display student set targets only
	 * @param this_year     display targets set this year only - added by nkowald - 2010-11-09
    */

    function get_ilptarget_html($id, $full=TRUE, $sortorder='ASC', $limit=0, $status=-1, $tutorsetonly=FALSE, $studentsetonly=FALSE, $this_year=TRUE) {

        global $CFG,$USER;
        require_once("$CFG->dirroot/blocks/ilp_student_info/block_ilp_student_info_lib.php");
        require_once("$CFG->dirroot/mod/ilptarget/lib.php");
        include ('access_context.php');

        $module = 'project/ilp';
        $config = get_config($module);

        $user = get_record('user','id',$id);

        $select = "SELECT {$CFG->prefix}ilptarget_posts.*, up.username ";
        $from = "FROM {$CFG->prefix}ilptarget_posts, {$CFG->prefix}user up ";
        $where = "WHERE up.id = setbyuserid AND setforuserid = $id ";

        if($status != -1) {
            $where .= "AND status = $status ";
        }elseif($config->ilp_show_achieved_targets == 1){
            $where .= "AND status != 3 ";
        }else{
            $where .= "AND status = 0 ";
        }

        if($tutorsetonly == TRUE && $studentsetonly == FALSE) {
            $where .= "AND setforuserid != setbyuserid ";
        }

        if($studentsetonly == TRUE && $tutorsetonly == FALSE) {
            $where .= "AND setforuserid = setbyuserid ";
        }

        // nkowald - 2010-10-21 - Need to show only targets set this year
        if ($this_year === TRUE) {
            // get the unix timestamp of 01/01/(this year)
            $year_now = date('Y');
            $ts_year_start = mktime(0, 0, 0, 1, 1, $year_now);
            // Add unix timestamp check to query
            $where .= "AND timecreated > $ts_year_start ";
        }
        // nkowald

        $order = "ORDER BY deadline $sortorder ";

        $target_posts = get_records_sql($select.$from.$where.$order,0,$limit);

        $ilp_html = '';

        if($full == FALSE) {
            $targettotal = count_records_sql('SELECT COUNT(*) FROM '.$CFG->prefix.'ilptarget_posts WHERE setforuserid = '.$user->id.' AND status != "3"' );
            $targetcomplete = count_records_sql('SELECT COUNT(*) FROM '.$CFG->prefix.'ilptarget_posts WHERE setforuserid = '.$user->id.' AND status = "1"');
            $ilp_html .= '<p style="display:inline; margin-left: 5px">'.$targetcomplete.'/'.$targettotal.' '.get_string('complete', 'ilptarget').'</p>';
        }

        if($full == TRUE) {
            if($target_posts) {
            
                foreach($target_posts as $post) {
                
                    $posttutor = get_record('user','id',$post->setbyuserid);

                    $target_html = '<table border="1"><tr><td style="vertical-align:top;"><table width="100%" border="0"><tr><td width="100"><b>';
                    $target_html .= get_string('name', 'ilptarget') . ':</b></td><td>' .$post->name.'</td></tr>';
                    $target_html .= '<tr><td><b>S.M.A.R.T. Target:</b></td>';
                    $target_html .= '<td style="vertical-align:top;">'.$post->targetset.'</td></tr></table></td>';
                    $target_html .= '<td width="150" style="vertical-align:top;">';
                    $target_html .= fullname($posttutor) . '<br />';
                    if($post->courserelated == 1){
                        //$targetcourse = get_record('course','id',$post->targetcourse);
                        //$target_html .=  '<li>'.get_string('course').': '.$targetcourse->shortname.'</li>';
                    }
                    $target_html .=  '<strong>' .get_string('set', 'ilptarget').':</strong> '.userdate($post->timecreated, get_string('strftimedate')) . '<br />';
                    $target_html .=  '<strong>' .get_string('deadline', 'ilptarget').':</strong> '.userdate($post->deadline, get_string('strftimedate')) . '<br />';
                    
                    if($post->status == 1){
                        $target_html .=  '<img src="'.$CFG->pixpath.'/mod/ilptarget/achieved.gif" alt="" />';
                    }
                    $target_html .= '</td></tr></table>';
                    
                    $ilp_html .= $target_html;
                    $ilp_html .= '<br /><br />';
                    
                }
            }
            return $ilp_html;
        }
    }
        
}