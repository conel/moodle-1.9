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

class assmgr_resource_text_mform extends assmgr_resource_mform {

    protected function specific_definition($mform) {
        global $CFG;

        // put all the evidece form elements into a fieldset
        $mform->addElement('header', 'textresource', get_string('textresource', 'block_assmgr'));

        // Text element
        $mform->addElement(
            'htmleditor',
            'text',
            get_string('assmgr_resource_text', 'block_assmgr'),
            array('class' => 'form_input', 'rows'=> '10', 'cols'=>'65')
        );

        $mform->addRule('text', null, 'maxlength', 65535, 'client');
        $mform->addRule('text', null, 'required', null, 'client');
        $mform->setType('text', PARAM_RAW);
    }

    protected function specific_validation($data) {
        //no extra validation needs to carried out
    }

    protected function specific_process_data($data) {
        global $CFG, $USER;

        $mform =& $this->_form;

        // get the resource record
        $resource = $this->dbc->get_resource($data->id);

        $text = new object();
        $text->text = $data->text;

        if(empty($resource)) {
            $resource = new object();
            $resource->evidence_id = $data->id;
            $resource->resource_type_id = $data->resource_type_id;
            $resource->tablename = 'block_assmgr_res_text';
        
            // add the resource specific record
            $resource->record_id = $this->dbc->create_resource_plugin('block_assmgr_res_text', $text);
            $this->dbc->create_resource($resource);

        } else if (empty($resource->record_id)) {
            
            // possibly no text was added when it was made, meaning there will be no corresponding record
            $resource->record_id = $this->dbc->create_resource_plugin('block_assmgr_res_text', $text);
            $this->dbc->set_resource($resource);
          
        } else {
            $text->id = $resource->record_id;
            $this->dbc->update_resource_plugin('block_assmgr_res_text', $text);
            $this->dbc->set_resource($resource);
           
        }

    }
}