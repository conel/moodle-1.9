<?php
/**
 * Defines the editing form for the multidistract question type.
 *
 * @package questions
 */

// local define for initial number of answers
define('LOCAL_NUMANS_START',5); 

/**
 * multiple choice editing form definition.
 */
class question_edit_multidistract_form extends question_edit_form {
    /**
     * Add question-type specific form fields.
     *
     * @param object $mform the form being built.
     */
    function definition_inner(&$mform) {
        $menu = array(get_string('answersingleno', 'qtype_multidistract'), get_string('answersingleyes', 'qtype_multidistract'));
        $mform->addElement('select', 'single', get_string('answerhowmany', 'qtype_multidistract'), $menu);
        $mform->setDefault('single', 1);

        //$mform->addElement('advcheckbox', 'shuffleanswers', get_string('shuffleanswers', 'qtype_multidistract'), null, null, array(0,1));
        //$mform->setHelpButton('shuffleanswers', array('multichoiceshuffle', get_string('shuffleanswers','qtype_multichoice'), 'quiz'));
        //$mform->setDefault('shuffleanswers', 1);

        $mform->addElement( 'hidden', 'shuffleanswers', 1 );

        $creategrades = get_grade_options();
        $gradeoptions = $creategrades->gradeoptionsfull;
        $repeated = array();
        $repeated[] =& $mform->createElement('header', 'choicehdr', get_string('choiceno', 'qtype_multidistract', '{no}'));
        $repeated[] =& $mform->createElement('text', 'answer', get_string('answer', 'quiz'), array('size' => 50));
        $repeated[] =& $mform->createElement('select', 'fraction', get_string('grade'), $gradeoptions);
        $repeated[] =& $mform->createElement('htmleditor', 'feedback', get_string('feedback', 'quiz'));

        if (isset($this->question->options)){
            $countanswers = count($this->question->options->answers);
        } else {
            $countanswers = 0;
        }
        $repeatsatstart = (LOCAL_NUMANS_START > ($countanswers + QUESTION_NUMANS_ADD))?
                            LOCAL_NUMANS_START : ($countanswers + QUESTION_NUMANS_ADD);
        $repeatedoptions = array();
        $repeatedoptions['fraction']['default'] = 0;
        $mform->setType('answer', PARAM_RAW);
        $this->repeat_elements($repeated, $repeatsatstart, $repeatedoptions, 'noanswers', 'addanswers', QUESTION_NUMANS_ADD, get_string('addmorechoiceblanks', 'qtype_multidistract'));

        $mform->addElement('header', 'overallfeedbackhdr', get_string('overallfeedback', 'qtype_multidistract'));

        $mform->addElement('htmleditor', 'correctfeedback', get_string('correctfeedback', 'qtype_multidistract'));
        $mform->setType('correctfeedback', PARAM_RAW);

        $mform->addElement('htmleditor', 'partiallycorrectfeedback', get_string('partiallycorrectfeedback', 'qtype_multidistract'));
        $mform->setType('partiallycorrectfeedback', PARAM_RAW);

        $mform->addElement('htmleditor', 'incorrectfeedback', get_string('incorrectfeedback', 'qtype_multidistract'));
        $mform->setType('incorrectfeedback', PARAM_RAW);

    }

    function set_data($question) {
        if (isset($question->options)){
            $answers = $question->options->answers;
            if (count($answers)) {
                $key = 0;
                foreach ($answers as $answer){
                    $default_values['answer['.$key.']'] = $answer->answer;
                    $default_values['fraction['.$key.']'] = $answer->fraction;
                    $default_values['feedback['.$key.']'] = $answer->feedback;
                    $key++;
                }
            }
            $default_values['single'] =  $question->options->single;
            $default_values['shuffleanswers'] =  $question->options->shuffleanswers;
            $default_values['correctfeedback'] =  $question->options->correctfeedback;
            $default_values['partiallycorrectfeedback'] =  $question->options->partiallycorrectfeedback;
            $default_values['incorrectfeedback'] =  $question->options->incorrectfeedback;
            $question = (object)((array)$question + $default_values);
        }
        parent::set_data($question);
    }

    function qtype() {
        return 'multidistract';
    }

    function validation($data){
        $errors = array();
        $answers = $data['answer'];
        $answercount = 0;

        $totalfraction = 0;
        $maxfraction = -1;

        foreach ($answers as $key => $answer){
            //check no of choices
            $trimmedanswer = trim($answer);
            if (!empty($trimmedanswer)){
                $answercount++;
            }
            //check grades
            if ($answer != '') {
                if ($data['fraction'][$key] > 0) {
                    $totalfraction += $data['fraction'][$key];
                }
                if ($data['fraction'][$key] > $maxfraction) {
                    $maxfraction = $data['fraction'][$key];
                }
            }
        }

        if ($answercount==0){
            $errors['answer[0]'] = get_string('notenoughanswers', 'qtype_multidistract', 2);
            $errors['answer[1]'] = get_string('notenoughanswers', 'qtype_multidistract', 2);
        } elseif ($answercount==1){
            $errors['answer[1]'] = get_string('notenoughanswers', 'qtype_multidistract', 2);

        }

        /// Perform sanity checks on fractional grades
        if ($data['single']) {
            if ($maxfraction != 1) {
                $maxfraction = $maxfraction * 100;
                $errors['fraction[0]'] = get_string('errfractionsnomax', 'qtype_multidistract', $maxfraction);
            }
        } else {
            $totalfraction = round($totalfraction,2);
            if ($totalfraction != 1) {
                $totalfraction = $totalfraction * 100;
                $errors['fraction[0]'] = get_string('errfractionsaddwrong', 'qtype_multidistract', $totalfraction);
            }
        }
        return $errors;
    }
}
?>
