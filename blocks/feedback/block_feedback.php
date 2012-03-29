<?php
if(is_file($CFG->dirroot.'/mod/feedback/lib.php')) {
    require_once($CFG->dirroot.'/mod/feedback/lib.php');
    define('FEEDBACK_BLOCK_LIB_IS_OK', true);
}
class block_feedback extends block_base {

    function init() {
        $this->title = get_string('feedback', 'block_feedback');
        $this->version = 2009050701;
    }

	// nkowald - 2010-05-24 - We want the feedback block to show/hide feedbacks based on their visibility on the home page
	function showBasedOnHomeVisibility($feedback='') {
		$visible = FALSE;
		$query = "SELECT modinfo FROM mdl_course WHERE id = 1";
		if ($serialised_val = get_records_sql($query)) {
			foreach($serialised_val as $val) {
				$mod_info = unserialize($val->modinfo);
			}
		}
		$feedbacks = array();
		foreach($mod_info as $mod) {
			if ($mod->mod == 'feedback') {
				$feedbacks[$mod->cm] = $mod->visible;
			}
		}
		if ($feedbacks[$feedback->cmid] != '') {
			$visible = ($feedbacks[$feedback->cmid] == 0) ? FALSE : TRUE;
		}
		return $visible;
	}
	
    function get_content() {
        global $CFG, $feedback_lib;
        
        if(!defined('FEEDBACK_BLOCK_LIB_IS_OK')) {
            $this->content = New stdClass;
            $this->content->text = get_string('missing_feedback_module', 'block_feedback');
            $this->content->footer = '';
            return $this->content;
        }
        
        $courseid = intval($this->instance->pageid);
        if($courseid <= 0) $courseid = SITEID;
        if($this->content !== NULL) {
            return $this->content;
        }

        if (empty($this->instance->pageid)) {
            $this->instance->pageid = SITEID;
        }

        $this->content = New stdClass;
        $this->content->text = '';

        if ( $feedbacks = feedback_get_feedbacks_from_sitecourse_map($courseid)) {
			
			$links = array();
            foreach ($feedbacks as $feedback) { //arb
				$visible = $this->showBasedOnHomeVisibility($feedback);
				if ($visible) {
					$links[] = '<a href="'.htmlspecialchars($CFG->wwwroot.'/mod/feedback/view.php?id='.$feedback->cmid.'&courseid='.$courseid).'">'.$feedback->name . '</a>';
				}
            }
			// nkowald - 2010-05-24
			if (count($links) > 0) {
				$this->content->text .= '<ul>';
				foreach($links as $link) {
					$this->content->text .= '<li>'.$link.'</li>';
				}
				$this->content->text .= '</ul>';
			}
        }

        $this->content->footer = '';

        return $this->content;

    }
    
    function applicable_formats() {
        return array('site' => true, 'course' => true);
    }
	
	
}

?>
