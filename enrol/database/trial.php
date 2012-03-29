<?php

// initialise moodle
//require_once('../../../config.php');
require_once('../../config.php');
global $CFG, $USER;

/**
 * Databse class to access CONEL's external MIS database for the
 * Learner Progress Review (LPR) Block module.
 *
 * @copyright &copy; 2009 University of London Computer Centre
 * @author http://www.ulcc.ac.uk, http://moodle.ulcc.ac.uk
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package LPR
 * @version 1.0
 */
class block_lpr_conel_mis_db {

    private $db;

    /**
     * Make connection to the MIS database.
     *
     */
    public function __construct() {
        global $CFG;
        // include the necessary DB library
        require_once ($CFG->dirroot.'/lib/adodb/adodb.inc.php');
        // set up the connection
        $this->db = NewADOConnection('oci8');
        $this->db->debug=true;
        $this->db->Connect('ebs.conel.ac.uk', 'ebsmoodle', '82814710', 'fs1');
        $this->db->SetFetchMode(ADODB_FETCH_ASSOC);
    }

    /**
     * Private member function to execute queries and build a moodle style
     * array of objects.
     *
     * @param string $sql The sql string you wish to be executed.
     * @return array The array of objects returned from the query.
     */
    private function execute_query($sql) {
        // execute the query
        $result = $this->db->Execute($sql);
        // initialise the data array
        $data = array();
        // convert the resultset into a Moodle style object
        while (!$result->EOF) {
            $obj = new stdClass;
            foreach (array_keys($result->fields) as $key) {
                $obj->{$key} = $result->fields[$key];
            }
            $data[] = $obj;
            $result->MoveNext();
        }
        // return an array of objects
        return $data;
    }

    /**
     * Gets the learner's attendance for a given course.
     *
     * @param string $learner_id The external id of the learner.
     * @param string $course_id The external id of the course.
     * @return stdClass The result object.
     */
    public function get_attendance_by_course($learner_id, $course_id) {
        return array_pop($this->execute_query(
            "SELECT AVG(MARKS_PRESENT/MARKS_TOTAL) AS attendance
             FROM FES.MOODLE_ATTENDANCE_PUNCTUALITY
             WHERE STUDENT_ID = '{$learner_id}'
               AND COURSE_CODE = '{$course_id}'
               AND MARKS_TOTAL > 0"
        ));
    }

    /**
     * Gets the learner's punctuality for a given course.
     *
     * @param string $learner_id The external id of the learner.
     * @param string $course_id The external id of the course.
     * @return stdClass The result object.
     */
    public function get_punctuality_by_course($learner_id, $course_id) {
        return array_pop($this->execute_query(
            "SELECT AVG(ATT_POSITIVE/PUNCT_TOTAL) AS punctuality
             FROM FES.MOODLE_ATTENDANCE_PUNCTUALITY
             WHERE STUDENT_ID = '{$learner_id}'
               AND COURSE_CODE = '{$course_id}'
               AND PUNCT_TOTAL > 0"
        ));
    }

    /**
     * Gets the average of the learner's attendance over the current year.
     *
     * @param string $learner_id The external id of the learner.
     * @return stdClass The result object.
     */
    public function get_attendance_avg($learner_id) {
        return array_pop($this->execute_query(
            "SELECT AVG(MARKS_PRESENT/MARKS_TOTAL) AS attendance
             FROM FES.MOODLE_ATTENDANCE_PUNCTUALITY
             WHERE STUDENT_ID = '{$learner_id}'
               AND ACADEMIC_YEAR = 'Academic Year 2009/2010'
               AND MARKS_TOTAL > 0"
        ));
    }

    /**
     * Gets the average of the learner's punctuality over the current year.
     *
     * @param string $learner_id The external id of the learner.
     * @return stdClass The result object.
     */
    public function get_punctuality_avg($learner_id) {
        return array_pop($this->execute_query(
            "SELECT AVG(ATT_POSITIVE/PUNCT_TOTAL) AS punctuality
             FROM FES.MOODLE_ATTENDANCE_PUNCTUALITY
             WHERE STUDENT_ID = '{$learner_id}'
               AND ACADEMIC_YEAR = 'Academic Year 2009/2010'
               AND PUNCT_TOTAL > 0"
        ));
    }
}

$course_id = 'NV2MHRA5_8EA21A';
$learner_id = '306437';
$mis = new block_lpr_conel_mis_db();
$result = $mis->get_attendance_by_course($learner_id, $course_id);
var_dump($result);
$result = $mis->get_punctuality_by_course($learner_id, $course_id);
var_dump($result);
$result = $mis->get_attendance_avg($learner_id);
var_dump($result);
$result = $mis->get_punctuality_avg($learner_id);
var_dump($result);
?>