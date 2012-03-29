<?php
/**
 * Form class for editing file evidence.
 *
 * @copyright &copy; 2010 University of London Computer Centre
 * @author http://www.ulcc.ac.uk, http://moodle.ulcc.ac.uk
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package AssMgr
 * @version 2.0
 */
require_once($CFG->dirroot."/blocks/assmgr/classes/resources/assmgr_resource_mform.php");

class assmgr_resource_moodle_mform extends assmgr_resource_mform {

    protected function specific_definition($mform) {
        global $CFG;

        //include the assessment manager parser class
        require_once($CFG->dirroot.'/blocks/assmgr/classes/assmgr_parser.class.php');

        $mform->addElement('hidden', 'activity_id', NULL,array('id' => 'activity_id'));
        $mform->setType('activity_id', PARAM_INT);
        $mform->addRule('activity_id', null, 'required', null, 'client');
        $mform->addElement('hidden', 'module_name', NULL, array('id' => 'module_name'));
        $mform->setType('module_name', PARAM_CLEAN);
        $mform->addRule('module_name', null, 'required', null, 'client');

        //fieldset to place activities table in
        $mform->addElement('header', 'moodleactivitydescription', get_string('activityheader', 'block_assmgr'));

        $mform->addElement('static', 'markactivities', null,
                  '<span id="markedact">'.get_string('markactivities', 'block_assmgr').'</span>');

        // TODO make this a help pop-up
        // get_string('activitytext', 'block_assmgr')

        // build the moodle activity table
        ob_start();
        ?>
        <div id="assmgr_activities_container">
            <?php require_once($CFG->dirroot."/blocks/assmgr/actions/list_moodle_assignments.ajax.php"); ?>
        </div>
        <?php
        $tablehtml = ob_get_clean();

        //$tablehtml);

        $mform->addElement('html', $tablehtml);


        // put all the evidece form elements into a fieldset
        $mform->addElement('header', 'assignmentresource', get_string('assignmentresource', 'block_assmgr'));

        $mform->addElement('static', 'chosenassignment', get_string('chosenassignment', 'block_assmgr'),
                  '<span id="chosenass">'.get_string('chooseassignment', 'block_assmgr').'</span>');

        
    }

    protected function specific_validation($data) {

        if ($this->module_instance_exists($data['module_name'],$data['activity_id'])) {
            $display_error = true;
            if (!empty($data['evidence_id'])) {
                $evidence_resource = $this->dbc->get_evidence_resource($data['evidence_id']);
                $resource_record = $this->dbc->get_resource_plugin('block_assmgr_res_moodle',$evidence_resource->record_id);
                if ($resource_record->activity_id == $data['activity_id'] && $resource_record->module_name == $data['module_name']) {$display_error = false;
                }
            }
            if ($display_error) {
                $activity_instance = $this->dbc->get_module_instance($data['module_name'],$data['activity_id']);
                $this->errors['chosenassignment'] = get_string('activityexistserror', 'block_assmgr',array($activity_instance->name));
            }
        }

        if (empty($data['module_name']) || empty($data['activity_id'])) {
            $this->errors['chosenassignment'] = get_string('chooseassignment','block_assmgr');
        }

        return $this->errors;

    }

    protected function specific_process_data($data) {
        global $CFG, $USER;

        $mform =& $this->_form;

        // get the resource record
        $resource = $this->dbc->get_resource($data->id);

        if (empty($resource)) {
            $resource = new object();
            $resource->evidence_id = $data->id;
            $resource->resource_type_id = $data->resource_type_id;
            $resource->tablename = 'block_assmgr_res_moodle';
        }

        // add the resource specific record
        $moodle_assignment = new object();
        $moodle_assignment->activity_id   = $data->activity_id;
        $moodle_assignment->module_name   = $data->module_name;

        if (empty($resource->id)) {
            $resource->record_id = $this->dbc->create_resource_plugin('block_assmgr_res_moodle', $moodle_assignment);
            $this->dbc->create_resource($resource);
        } else {
            $moodle_assignment->id = $resource->record_id;
            $this->dbc->update_resource_plugin('block_assmgr_res_moodle', $moodle_assignment);
            $this->dbc->set_resource($resource);
        }
    }

    /***
     * Returns true or false depending on whether an activity with
     * assignment and instance id has already been created
     *
     */

   function module_instance_exists($module, $instance_id) {
        global $DB;

        $sql = "SELECT  *
                FROM    {block_assmgr_res_moodle} AS modactivity,
                        {block_assmgr_resource}   AS res,
                        {block_assmgr_evidence}   AS evid

                WHERE   evid.id         = res.evidence_id
                AND     res.record_id = modactivity.id
                AND     modactivity.activity_id = {$instance_id}
                AND     modactivity.module_name = '{$module}'";
        return $DB->record_exists_sql($sql);
    }

    function definition_after_data() {
        global $PARSER, $CFG;
        $new_activity_id = $PARSER->optional_param('activity_id', null, PARAM_INT);
        $new_module_name = $PARSER->optional_param('module_name', null, PARAM_CLEAN);
        //variable used in the case that the user is editing and chooses evidence that has already been used in portfolio
        $display_current = false;

        if (!empty($new_activity_id) && !empty($new_module_name)) {
            $activity_instance = $this->dbc->get_module_instance($new_module_name,$new_activity_id);
            if (!$this->module_instance_exists($new_module_name,$new_activity_id)) {
                $this->_form->setDefault('module_name', $new_module_name);
                $this->_form->setDefault('activity_id', $new_activity_id);

                $this->_form->setDefault('chosenassignment',$activity_instance->name);
            } else {
                $display_error = true;
                if (!empty($this->evidence_id)) {
                    $evidence_resource = $this->dbc->get_evidence_resource($this->evidence_id);
                    $resource_record = $this->dbc->get_resource_plugin('block_assmgr_res_moodle',$evidence_resource->record_id);
                    if ($resource_record->activity_id == $new_activity_id && $resource_record->module_name == $new_module_name) $display_error = false;

                }
                if ($display_error) {
                $this->_form->setElementError('chosenassignment', get_string('activityexistserror', 'block_assmgr',array($activity_instance->name)));
                }




                $display_current = true;
            }
        }  else {
           $display_current = true;
        }

        if (!empty($this->evidence_id) && $display_current) {
                $resource = $this->dbc->get_evidence_resource($this->evidence_id);
                $resource_plugin = $this->dbc->get_resource_plugin($resource->tablename,$resource->record_id);

                require_once($CFG->dirroot."/blocks/assmgr/classes/resources/plugins/assmgr_resource_moodle.php");

                $moodle_resource = new assmgr_resource_moodle();
                $moodle_resource->load($resource->id);

                $this->_form->setDefault('activity_id', $resource_plugin->activity_id);
                $this->_form->setDefault('module_name', $resource_plugin->module_name);
                $this->_form->setDefault('chosenassignment',$moodle_resource->get_link(true));
        }


    }
}