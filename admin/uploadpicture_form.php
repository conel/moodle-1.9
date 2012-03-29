<?php // $Id: uploadpicture_form.php,v 1.1.2.2 2009/06/16 09:07:57 jerome Exp $
    require_once $CFG->libdir.'/formslib.php';

    class admin_uploadpicture_form extends moodleform {
        function definition (){
            global $CFG, $USER;
    
            $mform =& $this->_form;
    
            $this->set_upload_manager(new upload_manager('userpicturesfile', false, false, null, false, 0, true, true, false));
    
            $mform->addElement('header', 'settingsheader', get_string('upload'));
    
            $mform->addElement('file', 'userpicturesfile', get_string('file'), 'size="40"');
            $mform->addRule('userpicturesfile', null, 'required');

            $choices =& $this->_customdata;
            $mform->addElement('select', 'userfield', get_string('uploadpicture_userfield', 'admin'), $choices);
            $mform->setType('userfield', PARAM_INT);
			// nkowald - 2011-03-15 - Made idnumber default as this is all we ever use
			$mform->setDefault('userfield', 1);

            $choices = array( 0 => get_string('no'), 1 => get_string('yes') );
            $mform->addElement('select', 'overwritepicture', get_string('uploadpicture_overwrite', 'admin'), $choices);
            $mform->setType('overwritepicture', PARAM_INT);
    
            $this->add_action_buttons(false, get_string('uploadpictures', 'admin'));
        }
    }
?>
