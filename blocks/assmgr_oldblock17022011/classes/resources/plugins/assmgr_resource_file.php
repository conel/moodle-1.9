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

class assmgr_resource_file extends assmgr_resource {

    var $resource;
    var $resource_id;
    var $candidate_id;
    var $user_id;
    var $filepath;
    var $display_name;

    public function load($resource_id) {
        $resource = $this->dbc->get_resource_by_id($resource_id);
        if (!empty($resource)) {
            $this->resource_id = $resource_id;
            $resource_record = $this->dbc->get_resource_plugin('block_assmgr_res_file',$resource->record_id);
            if (!empty($resource_record)) {
                $this->resource = $resource_record->filename;
                $evidence = $this->dbc->get_evidence($resource->evidence_id);
                if (!empty($evidence)) {
                    $this->evidence_id = $evidence->id;
                    $this->display_name = $evidence->name;
                    $this->candidate_id = $evidence->candidate_id;
                    $this->user_id =$evidence->creator_id;
                    return true;
                }
            }
        }
        return false;

    }

    public function get_file_path() {
        if (!empty($this->filepath)) return $this->filepath;

        if (!empty($this->resource)) {
            $this->filepath = assmgr_evidence_folder($this->candidate_id, $this->evidence_id)."/".$this->resource;
            return $this->filepath;
        }

        return false;
    }

    /**
     *
     */
    public function get_content() {
        return $this->get_link();
    }

    /**
     *
     */
    public function get_link($use_evidence_name=false) {
        global $CFG, $OUTPUT;

        require_once($CFG->libdir."/filelib.php");
        $displayed_text = $this->resource;
        $evidence = $this->dbc->get_evidence($this->evidence_id);
        $path = $this->get_file_path();
        if (!empty($evidence) && !$use_evidence_name) {
            $displayed_text = limit_length($this->display_name, 50);
        }

        $url = get_file_url($path, null, 'coursefile');
        $localurl = str_replace($CFG->wwwroot, '', $url);

        $icon = 'f/'.str_replace(array('.gif', '.png'), '', mimeinfo('icon', basename($path)));

        return "<a href='{$url}?forcedownload=1'><img src='".$OUTPUT->pix_url($icon)."' class='activityicon' alt='activityicon' />&nbsp;{$displayed_text}</a>";
    }

/**
     *
     */
    public function install() {
        global $CFG, $DB;

    // create the new table to store the file
        $table = new $this->xmldb_table('block_assmgr_res_file');
        $set_attributes = method_exists($this->xmldb_key, 'set_attributes') ? 'set_attributes' : 'setAttributes';

        $table_id = new $this->xmldb_field('id');
        $table_id->$set_attributes(XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE);
        $table->addField($table_id);

        $table_filename = new $this->xmldb_field('filename');
        $table_filename->$set_attributes(XMLDB_TYPE_CHAR, 255, null, XMLDB_NOTNULL);
        $table->addField($table_filename);

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
     *
     */
    public function uninstall() {
        $table = new $this->xmldb_table('block_assmgr_res_file');
        drop_table($table);
    }


    /**
     *
     */
    public function delete_resource($tablename,$id) {
        global $CFG;
        if (!empty($this->resource_id)) {
            $fullfilepath = filepath_fix($CFG->dataroot.'/'.$this->get_file_path(),array('\\'=>'/'));
            if ($this->get_file_path() !=  false && file_exists($fullfilepath)) {
                if (is_file($fullfilepath)) {
                    if (!unlink($fullfilepath)) {
                        return false;
                    }
                }
            }
            return parent::delete_resource($tablename,$id);
        }
        return false;
    }

    /**
     *
     */
    public function audit_type() {
        return get_string('fileupload', 'block_assmgr');
    }

    /**
    * function used to return the language strings for the resource
    */
    function language_strings(&$string) {
        $string['assmgr_resource_file'] = 'File Upload';
        $string['assmgr_resource_file_description'] = 'Upload a file';
        $string['currentfile']       = 'Current File';
        return $string;
    }

    function config_settings(&$settings) {
        $fileuploads = new admin_setting_heading('block_assmgr/fileuploads', get_string('fileuploads', 'block_assmgr'), '');
        $settings->add($fileuploads);

        $excluded_fileupload = new admin_setting_configtext('block_assmgr/excluded_fileupload', get_string('excluded_fileupload', 'block_assmgr'), null, FILE_EXTENSION_BLACKLIST);
        $settings->add($excluded_fileupload);

        // set up the options for portfoliomax
        $options = array();
        for ($i=5; $i <= 100; $i+=5) {
            $options[$i] = "{$i} Mb";
        }

        $portfoliomax = new admin_setting_configselect('block_assmgr/portfoliomax', get_string('portfoliomax', 'block_assmgr'), null, 10, $options);
        $settings->add($portfoliomax);

        // set up the options for fileuploadmax
        $upload_max = (int)ini_get('upload_max_filesize');
        $options = array();
        for ($i=1; $i <= $upload_max; $i++) {
            $options[$i] = "{$i} Mb";
        }

        $fileuploadmax = new admin_setting_configselect('block_assmgr/fileuploadmax', get_string('fileuploadmax', 'block_assmgr'), get_string('uploadmaxfilesize', 'block_assmgr', $upload_max), 5, $options);
        $settings->add($fileuploadmax);

        return $settings;
    }

    /**
    * function used to return the size of the resource currently loaded
    */
    function size() {
        global $CFG;
        if (!empty($this->resource)) {
            $path = $this->get_file_path();
            $file = filepath_fix("{$CFG->dataroot}/{$path}");
            if (file_exists($file)) {
                return filesize("$file");
            }
        }
       return 0;
    }

    /**
    * function used to specify whether the current resource requires file storage
    */
    public function file_storage() {
        return true;
    }

}
?>