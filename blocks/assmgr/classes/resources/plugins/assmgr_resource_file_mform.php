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

class assmgr_resource_file_mform extends assmgr_resource_mform {

    protected function specific_definition($mform) {
        global $CFG;

        // put all the evidece form elements into a fieldset
        $mform->addElement('header', 'fileresource', get_string('fileresource', 'block_assmgr'));

        // FILE element
        $mform->addElement('file', 'newfile', get_string('uploadafile', 'block_assmgr'));
        //$mform->addRule('newfile', null, 'required', 'client');

        $mform->addElement('static', 'currentfile', get_string('currentfile', 'block_assmgr'),
                  get_string('notapplicable', 'block_assmgr'));

    }

    protected function specific_validation($data) {
        // TODO check file exclusions, portfolio size, etc...
        //$config->fileuploadmax
        //$config->excluded_fileupload

        global $SITE;

        $um = new upload_manager('newfile', false, true, $SITE->id, false);
        if (!$um->validate_file($_FILES["newfile"])) {
            if ($_FILES["newfile"]['size'] != 0 && empty($this->evidence_id)) {
                $this->errors['newfile'] = $um->get_file_upload_error($_FILES["newfile"]);
            }
        }
        return $this->errors;
    }

    protected function specific_process_data($data) {
        global $CFG, $USER, $SITE;

        $mform =& $this->_form;
        $dir = assmgr_evidence_folder($data->candidate_id, $data->id);

        //upload_manager($inputname='', $deleteothers=false, $handlecollisions=false, $course=null, $recoverifmultiple=false, $modbytes=0, $silent=false, $allownull=false, $allownullmultiple=true)
        $um = new upload_manager('newfile', false, true, $SITE->id, false);

        // process the uploaded file (if any)
        $upload_error = false;

            // file validation (basically for file SIZE)
            if ($um->validate_file($_FILES["newfile"])) {

                // there are two different procedures for Moodle 1 and Moodle 2
                // Moodle 2
                if (function_exists("get_file_storage")) {
                    //TODO need to write code to handle the file uplaod in moodle 2.0
                } else { // Moodle 1

                    if ($um->process_file_uploads($dir)) {
                        // get the resource record
                        $resource = $this->dbc->get_resource($data->id);

                        if (empty($resource)) {
                            $resource = new object();
                            $resource->evidence_id = $data->id;
                            $resource->resource_type_id = $data->resource_type_id;
                            $resource->tablename = 'block_assmgr_res_file';
                        }

                        // add the resource specific record
                        $file = new object();
                        $file->filename = $um->get_new_filename();

                        if (empty($resource->id)) {
                            $resource->record_id = $this->dbc->create_resource_plugin('block_assmgr_res_file', $file);
                            $this->dbc->create_resource($resource);
                        } else {
                            $file->id = $resource->id;
                            $this->dbc->update_resource_plugin('block_assmgr_res_file', $file);
                            $this->dbc->set_resource($resource);
                        }

                    } else {
                        // TODO where should these errors go?
                        echo $um->get_errors();
                    }
                }
            }

        // in case of errors
        if($upload_error) {

            // redirect to the form and display the error
            $return_message = get_string('uploadtoobig', 'block_assmgr');
            redirect("{$CFG->wwwroot}/blocks/assmgr/actions/edit_submission.php?submission_id={$submission_id}&course_id={$course_id}#id_newfile", $return_message, REDIRECT_DELAY);
        }
    }

    function definition_after_data() {
        global $PARSER;

        if (!empty($this->evidence_id)) {
            $resource = $this->dbc->get_evidence_resource($this->evidence_id);
            $resource_plugin = $this->dbc->get_resource_plugin($resource->tablename,$resource->record_id);

            $file_resource = new assmgr_resource_file();
            $file_resource->load($resource->id);

            //TODO decide the manner in which we are going to retrieve the file
            //$filepath = make_user_directory($evidence_object->creator_id,true)."/block_assmgr/{$evidence_object->id}/{$resource_plugin->filename}";
            $this->_form->setDefault('currentfile', $file_resource->get_link(true));
        } else {
            $this->_form->addRule('newfile', null, 'required', 'client');
        }

        //check to see if the large file flag has been set
        $large_file = $PARSER->optional_param('largefile',NULL,PARAM_INT);
        if (!empty($large_file) && empty($_POST) && empty($_FILES)) {
           $this->_form->setElementError('newfile', get_string('uploadserverlimit'));
        }
    }



}