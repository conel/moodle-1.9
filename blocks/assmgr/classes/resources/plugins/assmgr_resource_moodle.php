<?php
/**
 *
 * @copyright &copy; 2009-2010 University of London Computer Centre
 * @author http://www.ulcc.ac.uk, http://moodle.ulcc.ac.uk
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package AssMgr
 * @version 2.0
 */
require_once($CFG->dirroot."/blocks/assmgr/classes/resources/assmgr_resource.php");

class assmgr_resource_moodle extends assmgr_resource {

    var $resource_id;

    var $user_id;

    var $resource;

    var $display_name;

    var $module_name;

    var $activity_id;

    var $activity_name;

    /**
     * TODO comment this
     *
     */
    public function load($resource_id) {
        $resource = $this->dbc->get_resource_by_id($resource_id);
        if (!empty($resource)) {
            $this->resource_id = $resource_id;
            $resource_record = $this->dbc->get_resource_plugin('block_assmgr_res_moodle',$resource->record_id);
            if (!empty($resource_record)) {
                $this->resource = $resource_record->activity_id;
                $this->module_name = $resource_record->module_name;
                $activity_instance = $this->dbc->get_module_instance($resource_record->module_name,$resource_record->activity_id);
                $this->activity_name = (!empty($activity_instance)) ? $activity_instance->name : NULL;
            }

             $evidence = $this->dbc->get_evidence($resource->evidence_id);
             if (!empty($evidence)) {
                $this->display_name = $evidence->name;
                $this->evidence_id = $evidence->id;
                $this->user_id = $evidence->creator_id;
                return true;
             }
        }
        return false;
    }

    /**
     * Returns the resource
     *
     */
    public function get_content() {
        return $this->get_link();
    }

    /**
     * Returns a link to the resource
     *
     * @param boolean $use_resource_name should the name of the saved resource be
     * used or should the evidence name?
     * @return string a link to the resource
     */
    public function get_link($use_resource_name=false) {
        global $CFG, $OUTPUT;

        if (!empty($this->resource)) {

            $displayed_text = (!$use_resource_name) ? $this->display_name : $this->activity_name;
            $displayed_text = limit_length($displayed_text, 50);

            $module = $this->dbc->get_module_by_name($this->module_name);
            if (!empty($module)) {
                $course_module = $this->dbc->get_course_modules_by_instance($module->id,$this->resource);

                if (!empty($course_module) ) {
                    $activity = $this->dbc->get_module_instance($this->module_name, $this->resource);
                    $localurl = "/mod/{$this->module_name}/view.php?id={$course_module->id}";
                    $url = $CFG->wwwroot.$localurl;
                    $onclick = "this.target='popup'; return openpopup('{$localurl}', 'popup', 'menubar=0,location=0,scrollbars,resizable', 0);";
                    $icon = 'i/mnethost';

                    // check if the activity still exists and if not then dispaly a proper message
                    if (!empty($activity)) {
                        return "<a href='{$url}' onclick=\"{$onclick}\"><img src='".$OUTPUT->pix_url($icon)."' class='activityicon' alt='activityicon' />&nbsp;{$displayed_text}</a>";
                    } else {
                        $displayed_text = limit_length($displayed_text, 50, get_string('activitydeleted', 'block_assmgr'));
                        return "<img src='".$OUTPUT->pix_url($icon)."' class='activityicon' alt='activityicon' />&nbsp;{$displayed_text}";
                    }
                }
            }
        }

        return $this->display_name;
    }

    /**
     * Create the table this resource plugin needs to store its data.
     *
     */
    public function install() {
        global $CFG, $DB;

        // create the new table to store the file
        $table = new $this->xmldb_table('block_assmgr_res_moodle');
        $set_attributes = method_exists($this->xmldb_key, 'set_attributes') ? 'set_attributes' : 'setAttributes';

        $table_id = new $this->xmldb_field('id');
        $table_id->$set_attributes(XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE);
        $table->addField($table_id);

        $table_module_name = new $this->xmldb_field('module_name');
        $table_module_name->$set_attributes(XMLDB_TYPE_CHAR, 255, null, XMLDB_NOTNULL);
        $table->addField($table_module_name);

        $table_activity_id = new $this->xmldb_field('activity_id');
        $table_activity_id->$set_attributes(XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED, XMLDB_NOTNULL);
        $table->addField($table_activity_id);

        $table_timemodified = new $this->xmldb_field('timemodified');
        $table_timemodified->$set_attributes(XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED, XMLDB_NOTNULL);
        $table->addField($table_timemodified);

        $table_timecreated = new $this->xmldb_field('timecreated');
        $table_timecreated->$set_attributes(XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED, XMLDB_NOTNULL);
        $table->addField($table_timecreated);

        $table_key = new $this->xmldb_key('primary');
        $table_key->$set_attributes(XMLDB_KEY_PRIMARY, array('id'));
        $table->addKey($table_key);

        if(!$this->dbman->table_exists($table)) {
            $this->dbman->create_table($table);
        }
    }

    /**
     * Delete the table setup by this resource plugin.
     */
    public function uninstall() {
        $table = new $this->xmldb_table('block_assmgr_res_moodle');
        drop_table($table);
    }

    /**
     * TODO comment this
     *
     */
    public function audit_type() {
        // TODO this should be a language string
        return 'Moodle Activity';
    }

    /**
     * Function used to determine whether an assessor can make this type of evidence
     */
    public function assessor_create() {
        return false;
    }

    /**
     * Checks the activity record and trys to find the description field, which
     * differs between the different module/activity types.
     *
     * TODO comment this
     */
    private function get_activity_description($activty_record, $module_name) {
        if (!empty($activty_record)) {
            if (!empty($activty_record->description)) {
                return $activty_record->description;
            } else if (!empty($activty_record->summary)) {
                    return $activty_record->summary;
            } else if (!empty($activty_record->intro)) {
                return $activty_record->intro;
            }
        }
        return get_string('automaticallyadded', 'block_assmgr', $module_name);
    }

    /**
     * Checks whether there is a possible outcome grade for the (optional) course
     * and (optional) candidate.
     *
     * This only returns true if the module grade_item (e.g. an assignment) has
     * a least one outcome attached (these get their own grade_item records which
     * are linked by having the same itemtype, itemmodule and iteminstance values,
     * but are distinguished by having a non null value for outcomeid). The user
     * must also have a grade_grade record for either the parent grade_item or
     * one of the outcome grade items (so we know that there actually is some
     * evidence). However the user does not have to have any actual grade values
     * stored in the grade record.
     *
     * It also checks to see if the activity has already been imported as evidence,
     * but does not check if it has been submitted as the submission record will
     * be created at the same time as the evidence.
     *
     * @param int $course_id The optional id of the course
     * @param int $candidate_id The optional id of the user
     * @return array
     */
    private function get_unimported_activities($course_id = null, $candidate_id = null) {
        global $DB;


        if (!empty($course_id) && is_array($course_id)) {
            $course_condition = " AND cm.course IN (".implode(',',$course_id).")";
        } else {
            $course_condition = !empty($course_id) ? " AND cm.course = {$course_id} " : '';
        }
        $cand_condition = !empty($candidate_id) ? " AND gg.userid = {$candidate_id} " : '';

        $sub = "SELECT gg.id,
                       cm.instance AS activity_id,
                       m.name AS module_name,
                       cm.course AS course_id,
                       gg.userid AS candidate_id,
                       gg.usermodified AS assessor_id
                  FROM {course_modules} AS cm,
                       {modules} AS m,
                       {grade_items} AS gi,
                       {grade_items} AS gio,
                       {grade_grades} AS gg
                 WHERE cm.module = m.id
                       {$course_condition}
                   AND gi.itemtype = 'mod'
                   AND gi.itemmodule = m.name
                   AND gi.iteminstance = cm.instance
                   AND gi.outcomeid IS NULL
                   AND gi.itemtype = gio.itemtype
                   AND gi.itemmodule = gio.itemmodule
                   AND gi.iteminstance = gio.iteminstance
                   AND gio.outcomeid IS NOT NULL
                   AND %s
                       {$cand_condition}
                   AND ROW(gg.userid, m.name, cm.instance) NOT IN (
                        SELECT evid.candidate_id, resmood.module_name, resmood.activity_id
                          FROM {block_assmgr_evidence} AS evid,
                               {block_assmgr_resource} AS res,
                               {block_assmgr_res_moodle} AS resmood
                         WHERE evid.id = res.evidence_id
                           AND res.tablename = 'block_assmgr_res_moodle'
                           AND res.record_id = resmood.id
                   )
              GROUP BY cm.instance, gg.userid";

        // because of how badly MySQL handles OR conditions in joins it is much
        // cheaper to get the union of two queries with different conditions
        $sub1 = sprintf($sub, 'gi.id = gg.itemid');
        $sub2 = sprintf($sub, 'gio.id = gg.itemid');

        $sql = "SELECT *
                  FROM (($sub1) UNION ($sub2)) AS joined
              GROUP BY activity_id, candidate_id";

        return $DB->get_records_sql($sql);
    }

    /**
     * Returns a list of submissions that are synchronised with the gradebook.
     *
     * @param int $course_id The optional id of the course to update
     * @param int $candidate_id The optional id of the candidate to update
     */
    public function get_synchronised_submissions($course_id = null, $candidate_id = null) {
        global $DB;

        if (!empty($course_id) && is_array($course_id)) {
            $course_condition = " AND port.course_id IN (".implode(',',$course_id).")";
        } else {
            $course_condition = empty($course_id) ? '' : " AND port.course_id = {$course_id} ";
        }

        $candidate_condition = empty($candidate_id) ? '' : " AND port.candidate_id = {$candidate_id} ";

        $sql = "SELECT sub.id AS submission_id,
                       port.course_id,
                       port.candidate_id,
                       resm.module_name,
                       resm.activity_id
                  FROM {block_assmgr_portfolio} AS port,
                       {block_assmgr_submission} AS sub,
                       {block_assmgr_resource} AS res,
                       {block_assmgr_res_moodle} AS resm
                 WHERE sub.synchronise = 1
                   AND sub.evidence_id = res.evidence_id
                   AND sub.portfolio_id = port.id
                       {$course_condition}
                       {$candidate_condition}
                   AND res.tablename = 'block_assmgr_res_moodle'
                   AND res.record_id = resm.id";

        return $DB->get_records_sql($sql);
    }

    /**
     * Fetches the current grades for a given candidate in a given activity.
     *
     * @param int $course_id The optional id of the course to update
     * @param int $candidate_id The optional id of the candidate to update
     * @param string $module_name The name of the module
     * @param int $activity_id The id of the module instance
     */
    function get_activity_grades($candidate_id, $course_id, $module_name, $activity_id) {
        global $DB;

        $sql = "SELECT gg.id,
                       gi.outcomeid AS outcome_id,
                       gg.usermodified AS creator_id,
                       gg.finalgrade AS grade,
                       gg.feedback
                  FROM {grade_items} AS gi,
                       {grade_grades} AS gg
                 WHERE gi.itemtype = 'mod'
                   AND gi.itemmodule = '{$module_name}'
                   AND gi.iteminstance = {$activity_id}
                   AND gi.courseid = {$course_id}
                   AND gi.id = gg.itemid
                   AND gg.userid = $candidate_id";

        return $DB->get_records_sql($sql);
    }




    /**
     * Imports any relevant Moodle activities as submitted evidence into the
     * candidate's portfolio.
     *
     * @param int $course_id The optional id of the course to update
     * @param int $candidate_id The optional id of the candidate to update
     */
    public function update($course_id = null, $candidate_id = null, $verbose = true) {
        global $CFG;

        $start = time();


        require_once($CFG->dirroot."/blocks/assmgr/db/assmgr_db.php");

        $dbc = new assmgr_db();

        $enabled_courses    =   $this->get_resource_enabled_instances('assmgr_resource_moodle',$course_id);
            if (!empty($enabled_courses)) {

                // get all the activities for which there are user grades in the given
                // course but that haven't already got an evidence record
                $activities = $this->get_unimported_activities($enabled_courses, $candidate_id);

                if($verbose) {
                    echo "\n- Importing ".count($activities)." moodle activities... ";
                }

                if(!empty($activities)) {
                    foreach($activities as $activity) {
                        // create new evidence, resource and submission records for the activity
                        $this->import_evidence($activity->course_id, $activity->candidate_id, $activity->assessor_id, $activity->module_name, $activity->activity_id);
                    }
                }

                if($verbose) {
                    echo "done (".(time()-$start)." secs)";
                }

                // sync the grades, in case they've not been imported yet or have changed
                $this->synchronise_grades($enabled_courses, $candidate_id, $verbose);
            }
    }

    /**
     * Synchronises the grades for automatically imported moodle activities in
     * assessment manager with the grades from the gradebook.
     *
     * @param int $course_id The optional id of the course to update
     * @param int $candidate_id The optional id of the candidate to update
     */
    function synchronise_grades($course_id = null, $candidate_id = null, $verbose = true) {
        global $CFG;

        $start = time();

        // include the moodle library
        require_once($CFG->dirroot.'/lib/gradelib.php');

        // get all the synchronised submissions
        $submissions = $this->get_synchronised_submissions($course_id, $candidate_id);

        if($verbose) {
            echo "\n- Synchronising grades for ".count($submissions)." AssMgr Moodle activity submissions... ";
        }

        if(!empty($submissions)) {
            foreach($submissions as $sub) {
                // get the assessor's grades
                $grades = $this->get_activity_grades($sub->candidate_id, $sub->course_id, $sub->module_name, $sub->activity_id);

                $comment = '';
                $comment_creator_id = null;

                $newgrades = array();
                $newgrades_creator_id = null;

                foreach($grades as $grade) {
                    if(!empty($grade->outcome_id)) {
                        if(!empty($grade->grade)) {
                            $newgrades[$grade->outcome_id] = $grade->grade;
                            $newgrades_creator_id = $grade->creator_id;
                        }
                    } else {
                        $comment = $grade->feedback;
                        $comment_creator_id = $grade->creator_id;
                    }
                }

                $this->dbc->set_submission_comment($sub->submission_id, $comment, $comment_creator_id);
                $this->dbc->set_submission_grades($sub->submission_id, $newgrades, $newgrades_creator_id);
            }
        }

        if($verbose) {
            echo "done (".(time()-$start)." secs)\n";
        }
    }

    /**
     * Creates an evidence record for the activity.
     *
     * TODO comment this
     */
    private function import_evidence($course_id, $candidate_id, $assessor_id, $module_name, $activity_id) {

        global $CFG;


        //include the library file
        require_once($CFG->dirroot.'/blocks/assmgr/lib.php');

        // get the instance of the module
        $activity = $this->dbc->get_module_instance($module_name, $activity_id);

        // get the course folder
        $course_folder = $this->dbc->get_default_folder($course_id, $candidate_id);
        $course_folder_id = $course_folder->id;

        // get the module sub-folder
        $folder = $this->dbc->get_candidate_course_module_folder($module_name, $candidate_id, $course_folder_id);
        $folder_id = empty($folder) ?  $this->dbc->create_folder($module_name, $candidate_id, $course_folder_id) : $folder->id;

        // create a new evidence record
        $evidence = new object();
        $evidence->name = $this->resolve_evidence_name($activity->name, $candidate_id);
        $evidence->description = $this->get_activity_description($activity, $module_name);
        $evidence->folder_id = $folder_id;
        $evidence->candidate_id = $candidate_id;
        $evidence->creator_id = $candidate_id;

        // insert the evidence
        $evidence_id = $this->dbc->create_evidence($evidence);

        // create a new res moodle record
        $res_moodle = new object();
        $res_moodle->activity_id = $activity_id;
        $res_moodle->module_name = $module_name;

        // insert the moodle resource
        $res_moodle_id = $this->dbc->create_resource_plugin('block_assmgr_res_moodle', $res_moodle);

        // get the resouce type record
        $resource_type = $this->dbc->get_resource_type_by_name('assmgr_resource_moodle');

        // create the resource record
        $evidence_resource = new object();
        $evidence_resource->evidence_id = $evidence_id;
        $evidence_resource->resource_type_id = $resource_type->id;
        $evidence_resource->tablename = 'block_assmgr_res_moodle';
        $evidence_resource->record_id = $res_moodle_id ;

        // insert the resource record
        $result = $this->dbc->create_resource($evidence_resource);

        // get the portfolio_id, or create a new portfolio if necessary
        $portfolio_id = check_portfolio($candidate_id, $course_id);

        // create a new submission record
        $submission = new object();
        $submission->portfolio_id = $portfolio_id;
        $submission->evidence_id = $evidence_id;
        $submission->creator_id = $assessor_id;
        $submission->synchronise = 1;

        // insert the submission record
        $submission_id = $this->dbc->create_submission($submission);

        // TODO we need a better way of doing this
        // get the evidence type for moodle activities
        $evidence_type = $this->dbc->get_evidence_type_by_name(get_config('block_assmgr', 'moodleevidencetype'));

        if (!empty($evidence_type)) {
            // add the submission evidence type
            $this->dbc->create_submission_evidence_type($submission_id, $evidence_type->id, $assessor_id);
        }
    }

    /**
     * Checks whether the name of a piece of imported evidence exists.
     * If the evidence name exits a number in parenthesis is appended
     *
     * @param string $evidence_name The name of the evidence
     * @param int $candidate_id The id of the user
     * @return string the evidence name resolved
     */
    private function resolve_evidence_name($evidence_name,$candidate_id) {
        $data = array('name'=>$evidence_name,'candidate_id'=>$candidate_id);
        if($this->dbc->exists('evidence', array('name', 'candidate_id'), $data)) {
            $evidence_name = $this->name_append($evidence_name);
            $evidence_name = $this->resolve_evidence_name($evidence_name,$candidate_id);
        }
        return $evidence_name;
    }

    /**
     * Takes a given string and returns it with a number in between parenthesis.
     * appended. e.g (1). If the string already has a number with parentthesis
     * then the function will take this number and increment it by one
     *
     * @param string $evidence_name The name of the evidence
     * @return string the evidence name with a number in parenthesis appended
     */
    function name_append($evidence_name) {
        $pattern = "/\([0-9]+\)/";
        if (preg_match($pattern,$evidence_name,$matches)) {
            foreach ($matches as $matched) {
                $pattern = "/[0-9]+/";
                if (preg_match($pattern,$matched,$sub_match)) {
                    $number = (int) current($sub_match);
                    $number = (is_int($number)) ? $number + 1 : 0;
                    $evidence_name = str_replace($matched,"(".$number.")",$evidence_name);
                }
            }
        } else {
            $evidence_name = $evidence_name. ' (1)';
        }
        return $evidence_name;
    }

    /**
     * TODO comment this
     *
     */
    private function return_grade_creator($gradesobject) {
        foreach ($gradesobject->outcomes as $index => $outcome) {
          foreach ($outcome->grades as $grade) {
            if (!empty($grade->usermodified)) return $grade->usermodified;
          }
        }
        return false;
    }

    /**
     * function used to return the language strings for the resource
     */
    function language_strings(&$string) {

        $string['assmgr_resource_moodle'] = 'Moodle Activity';
        $string['assmgr_resource_moodle_description'] = 'Import work from an existing Moodle activity';
        $string['assignmentresource'] = 'Selected Moodle Activity';
        $string['chosenassignment'] = 'Chosen Activity:';
        $string['chooseassignment'] = 'Please Select An Activity:';
        $string['selectassignment'] = 'Click Here To Select An Activity';
        $string['activityexistserror'] = '$a[0] has already been added as evidence';
        $string['listassignments'] = 'List Moodle Activities';
        $string['activityheader'] = 'Moodle Activies';
        $string['activitytext'] = 'Please use the table below to select the moodle activity that you would like to be your evidence.<br/>'
                                 .'Once selected the evidence will be dispalyed in the Selected Moodle Activity area.<br/>'
                                 .'Please note that only activites that have been marked by you assessor are available for you to choose.<br/>';
        return $string;
    }
}
?>