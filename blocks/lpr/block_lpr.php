<?php
/**
 * Block LPR class for the Learner Progress Review (LPR).
 *
 * @copyright &copy; 2009 University of London Computer Centre
 * @author http://www.ulcc.ac.uk, http://moodle.ulcc.ac.uk
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package LPR
 * @version 1.0
 */
class block_lpr extends block_list {

    function init() {
		// nkowald - 2010-11-24 - Couldn't find where this was stored so updated the title in here.
        //$this->title = get_string('blockname', 'block_lpr');
        $this->title = 'ILP Reporting Tool';
        $this->version = 2010030100;
    }

    function get_content() {
        global $CFG, $USER;

        // include the permissions check
        require_once("{$CFG->dirroot}/blocks/lpr/access_content.php");

        if ($this->content !== NULL) {
          return $this->content;
        }

        $this->content = null;

        $this->content = new stdClass();
        $this->content->items = array();
        $this->content->icons = array();

        $course = get_record('course', 'id', $this->instance->pageid);

        if($can_view) {
            $this->content->items[] = '<a href="'.$CFG->wwwroot.'/blocks/lpr/actions/list.php?course_id='.$course->id.'">'.get_string('listlprs', 'block_lpr').'</a>';
            $this->content->icons[] = '<img src="'.$CFG->wwwroot.'/theme/conel/pix/i/users.gif" class="icon" alt="" />';
        }

        if($can_view) {
            $this->content->items[] = '<a href="'.$CFG->wwwroot.'/blocks/lpr/actions/reports.php?category_id='.$course->category.'&amp;course_id='.$course->id.'">'.get_string('lprstats', 'block_lpr').'</a>';
            $this->content->icons[] = '<img src="'.$CFG->wwwroot.'/theme/conel/pix/i/users.gif" class="icon" alt="" />';
        }

        if($can_print) {
            $this->content->items[] = '<a href="'.$CFG->wwwroot.'/blocks/lpr/actions/export.php?course_id='.$course->id.'">'.get_string('pdfexport', 'block_lpr').'</a>';
            $this->content->icons[] = '<img src="'.$CFG->wwwroot.'/theme/conel/pix/i/users.gif" class="icon" alt="" />';
        }

        $this->content->footer = '';

        return $this->content;
    }

    function instance_allow_multiple() {
        return false;
    }

    function has_config() {
        return true;
    }

    function config_save($data) {
        // Default behavior: save all variables as $CFG properties
        $module = 'project/lpr';

        foreach ($data as $name => $value) {
            set_config($name, $value, $module);
        }

        return true;
    }
}