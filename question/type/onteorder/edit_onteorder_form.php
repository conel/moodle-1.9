<?php

/**
 * The editing form code for this question type.
 *
 * @copyright &copy; 2008 Micha³ Zaborowski
 * @author michal.zaborowski@byd.pl
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package onte_questiontypes
 *//** */
require_once($CFG->dirroot.'/question/type/edit_question_form.php');
/**
 * QTYPENAME editing form definition.
 * 
 * See http://docs.moodle.org/en/Development:lib/formslib.php for information
 * about the Moodle forms library, which is based on the HTML Quickform PEAR library.
 */
class question_edit_onteorder_form extends question_edit_form {
	
   function definition_inner() {
		global $COURSE, $CFG;
		echo '<link rel="stylesheet" type="text/css" href="type/'.$this->qtype().'/styles.css" />';   
        // TODO, add any form fields you need.
        // $mform->addElement( ... );
            

        $qtype = $this->qtype();
        $langfile = "qtype_$qtype";

        $mform =& $this->_form;

        // Standard fields at the start of the form.
        //$mform->addElement('header', 'generalheader', get_string("general", 'form'));

        

        $mform->addElement('textarea', 'sentence', get_string('sentence', 'qtype_onteorder'), array('size' => 5));
        

        // Standard fields at the end of the form.
        $mform->addElement('hidden', 'questiontextformat', 0);
        $mform->setType('questiontextformat', PARAM_INT);

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        $mform->addElement('hidden', 'qtype');
        $mform->setType('qtype', PARAM_ALPHA);

        $mform->addElement('hidden', 'inpopup');
        $mform->setType('inpopup', PARAM_INT);

        $mform->addElement('hidden', 'versioning');
        $mform->setType('versioning', PARAM_BOOL);
		
		
        

	

 
	}

    function set_data($question) {
        // TODO, preprocess the question definition so the data is ready to load into the form.
        // You may not need this method at all, in which case you can delete it.

        // For example:
		if (isset($question->options)){
            $default_values['sentence'] =  $question->options->sentence;
            $question = (object)((array)$question + $default_values);
        }
        parent::set_data($question);
    }

    function validation($data) {
        $errors = array();

        // TODO, do extra validation on the data that came back from the form. E.g.
        // if (/* Some test on $data['customfield']*/) {
        //     $errors['customfield'] = get_string( ... );
        // }

        if ($errors) {
            return $errors;
        } else {
            return true;
        }
    }

    function qtype() {
        return 'onteorder';
    }
}
?>