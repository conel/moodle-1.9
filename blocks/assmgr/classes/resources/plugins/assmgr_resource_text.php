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

class assmgr_resource_text extends assmgr_resource {

    var $resource_id;

    var $user_id;

    var $resource;

    var $display_name;

    public function load($resource_id) {
        $resource = $this->dbc->get_resource_by_id($resource_id);
        if (!empty($resource)) {
            $this->resource_id = $resource_id;
            $resource_record = $this->dbc->get_resource_plugin('block_assmgr_res_text',$resource->record_id);
            if (!empty($resource_record)) $this->resource = $resource_record->text;
            $evidence = $this->dbc->get_evidence($resource->evidence_id);
            if (!empty($evidence)) {
                $this->evidence_id = $evidence->id;
                $this->display_name = $evidence->name;
                $this->user_id = $evidence->creator_id;
                return true;
            }

        }
        return false;

    }

    /**
     *
     */
    public function get_content() {
        return $this->resource;
        return "teesttt";
    }

    /**
     *
     */
    public function get_link($use_evidence_name=false) {
        global $CFG, $OUTPUT, $PARSER;

        $displayed_text = limit_length($this->display_name, 50);
        $course_id = $PARSER->required_param('course_id', PARAM_INT);
        $url = "{$CFG->wwwroot}/blocks/assmgr/actions/view_evidence.php?id={$this->resource_id}&amp;course_id={$course_id}&amp;evidence_id={$this->evidence_id}";
        $icon = 'f/text';

        return "<a href='{$url}'><img src='".$OUTPUT->pix_url($icon)."' class='activityicon' alt='activityicon' />&nbsp;{$displayed_text}</a>";
    }

    /**
     *
     */
    public function install() {
        global $CFG, $DB;

        // create the new table to store the file
        $table = new $this->xmldb_table('block_assmgr_res_text');
        $set_attributes = method_exists($this->xmldb_key, 'set_attributes') ? 'set_attributes' : 'setAttributes';

        $table_id = new $this->xmldb_field('id');
        $table_id->$set_attributes(XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE);
        $table->addField($table_id);

        $table_text = new $this->xmldb_field('text');
        $table_text->$set_attributes(XMLDB_TYPE_TEXT, 'big', null, XMLDB_NOTNULL);
        $table->addField($table_text);

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
        $table = new $this->xmldb_table('block_assmgr_res_text');
        drop_table($table);
    }

     /**
     *
     */
    public function delete_resource($tablename,$id) {
        return parent::delete_resource($tablename,$id);
    }

     /**
     *
     */
    public function audit_type() {
        return 'Text';
    }

    /**
    * function used to return the language strings for the resource
    */
    function language_strings(&$string) {
        $string['assmgr_resource_text'] = 'Text';
        $string['assmgr_resource_text_description'] = 'Make a text document';
        $string['textresource'] = 'Text Resource';
        return $string;
    }


}
?>