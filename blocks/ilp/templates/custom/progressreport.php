<?php  

/*
 * @copyright &copy; 2009 University of London Computer Centre
 * @author http://www.ulcc.ac.uk, http://moodle.ulcc.ac.uk
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @mod ilpconcern
 */

    require_once("../../../../config.php");
    require_once("$CFG->dirroot/mod/ilpconcern/lib.php");
    require_once($CFG->libdir.'/formslib.php');

    global $CFG;

	$id = optional_param('id', 0, PARAM_INT);
    $action = optional_param('action',NULL, PARAM_CLEAN);

class ilp_addreport_form extends moodleform {

    function definition() {
        global $USER, $CFG;     
        
        $mform    =& $this->_form;
        
        if($id > 0) {
			$template = get_record('ilp_module_template','id',$id);
			$mform->addElement('hidden', 'id', $template->id);
		}
		
		$mform->addElement('header', 'report','Student Progress Report');

	$options = array(1,2,3,4,5,6,7,8,9,10);

        $mform->addElement('select', 'mtg', 'Minimum Target Grade',$options);
		$mform->addRule('mtg', null, 'required', null, 'client');

		$mform->addElement('select', 'atg', 'Aspirational Target Grade',$options);
		$mform->addRule('atg', null, 'required', null, 'client');
		
		$mform->addElement('select', 'attendance', 'Attendance',$options);
		$mform->addRule('attendance', null, 'required', null, 'client');
		
		$mform->addElement('select', 'punctuality', 'Punctuality',$options);
		$mform->addRule('punctuality', null, 'required', null, 'client');
		
		$mform->addElement('select', 'attainment', 'Attainment / Learning',$options);
		$mform->addRule('attainment', null, 'required', null, 'client');
		
		$mform->addElement('select', 'funcskills', 'Functional Skills',$options);
		$mform->addRule('funcskills', null, 'required', null, 'client');
		
		$mform->addElement('select', 'empskills', 'Employment Skills',$options);
		$mform->addRule('empskills', null, 'required', null, 'client');


		
		$mform->addElement('htmleditor', 'comment', 'Comments',array('canUseHtmlEditor'=>'detect','rows'  => 20,'cols'  => 65));
        $mform->setType('comment', PARAM_RAW);
        $mform->addRule('comment', null, 'required', null, 'client');
		$mform->setHelpButton('comment', array('writing', 'richtext'), false, 'editorhelpbutton');
                       
        $this->add_action_buttons($cancel = true, $submitlabel=get_string('savechanges'));
    }
            
    function process_data($data) {
        
      }
}

print_header_simple();

$mform = new ilp_addreport_form();

if ($mform->is_cancelled()){
}

print_heading('Add Progress Report');
$mform->display(); 
 

?>