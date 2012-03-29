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

class assmgr_resource_url_mform extends assmgr_resource_mform {

    protected function specific_definition($mform) {
        global $CFG;

        // put all the evidece form elements into a fieldset
        $mform->addElement('header', 'urlresource', get_string('urlresource', 'block_assmgr'));
        $mform->addElement('text','url',  get_string('assmgr_resource_url', 'block_assmgr'));
        $mform->addRule('url', null, 'minlength', 8, 'client');
        $mform->addRule('url', null, 'maxlength', 255, 'client');
        $mform->addRule('url', null, 'required', null, 'client');
        $mform->setType('url', PARAM_CLEAN);
        $mform->setDefault('url','http://');
    }

    protected function specific_validation($data) {
        if(!preg_match("/^(http(s?):\\/\\/)((\w+(\.)){2,})((\w{2,}((\/)?|(\.)?))+)$/i", $data['url']))
            $this->errors['url'] = get_string('invalidurlerror', 'block_assmgr',array($data['url']));
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
            $resource->tablename = 'block_assmgr_res_url';
        }

        // add the resource specific record
        $url = new object();
        $url->url = $data->url;

        if (empty($resource->id)) {
            $resource->record_id = $this->dbc->create_resource_plugin('block_assmgr_res_url', $url);
            $this->dbc->create_resource($resource);

        } else {
            $url->id = $resource->record_id;
            $this->dbc->update_resource_plugin('block_assmgr_res_url', $url);
            $this->dbc->set_resource($resource);
        }

    }
}