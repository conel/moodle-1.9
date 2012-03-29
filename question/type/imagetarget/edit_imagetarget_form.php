<?php
/**
 * Defines the editing form for the imagetarget question type.
 *
 * @copyright &copy; 2007 Jamie Pratt
 * @author Jamie Pratt me@jamiep.org
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package questionbank
 * @subpackage questiontypes
 */

/**
 * imagetarget editing form definition.
 */
class question_edit_imagetarget_form extends question_edit_form {
    /**
     * Add question-type specific form fields.
     *
     * @param MoodleQuickForm $mform the form being built.
     */
    function definition_inner(&$mform) {
        global $CFG, $COURSE;

        // $addcropper marks whether the user clicked on the "add area" button
        // $cropperadded is set on the page with the new cropper image so that
        // the new area gets saved correctly when the question is saved
        $addcropper = optional_param('addcropper', '', PARAM_INT);
        $cropperadded = optional_param('cropperadded', '', PARAM_INT);

        // $deletecropper and $cropperdeleted work like the $addcropper and $cropperadded
        $deletecropper = optional_param('deletecropper', '', PARAM_INT);
        $cropperdeleted = optional_param('cropperdeleted', '', PARAM_INT);

        $mform->addElement('static', 'answersinstruct', get_string('correctanswers', 'qtype_imagetarget'), get_string('filloutoneanswer', 'quiz'));
        $mform->closeHeaderBefore('qimage');

        $coursefiles = get_directory_list("$CFG->dataroot/$COURSE->id", $CFG->moddata);
        foreach ($coursefiles as $filename) {
            if (mimeinfo("icon", $filename) == "image.gif") {
                $images["$filename"] = $filename;
            }
        }

        if (empty($images)) {
            $mform->addElement('static', 'qimage', get_string('questionimage', 'qtype_imagetarget'), get_string('noimagesyet'));
        } else {
            $mform->addElement('select', 'qimage', get_string('questionimage', 'qtype_imagetarget'), array_merge(array(''=>get_string('none')), $images));
        }

        if (isset($this->question->options->qimage)) {
            $mform->addElement('submit', 'replaceimage', get_string('replaceimage', 'qtype_imagetarget'));

            $script = '<script src="type/imagetarget/cropper/cropper.css" type="text/css"></script>';
            $script .= '<script src="type/imagetarget/cropper/lib/prototype.js" type="text/javascript"></script>';
            $script .= '<script src="type/imagetarget/cropper/lib/scriptaculous.js?load=builder,dragdrop" type="text/javascript"></script>';
            $script .= '<script src="type/imagetarget/cropper/cropper.js" type="text/javascript"></script>';

            $mform->addElement('static', 'cropper', '', $script);
        } else {
            $mform->addElement('submit', 'chooseimage', get_string('chooseimage', 'qtype_imagetarget'));
        }

        $e = $mform->addElement('hidden', 'answer[0]', '*');
        $e->updateAttributes(array('id'=>'id_answer_0'));
        $mform->addElement('hidden', 'fraction[0]', 0);

        $answercount = 1;

        if (isset($this->question->options->qimage) and isset($this->question->options->answers)) {
            $answers = $this->question->options->answers;
            if ($deletecropper || $cropperdeleted) {
                array_pop($answers);
                $mform->addElement('hidden', 'cropperdeleted', 1);
            }
            foreach ($answers as $answer) {
                $coords = $answer->answer;
                $c = explode(',', $coords);
                if (count($c) == 4) {
                    $this->add_image_to_form($mform, $answercount, $c);
                    $answercount++;
                }
            }
        }

        if ($addcropper || $cropperadded) {
            $c = array(20, 20, 80, 80);
            $this->add_image_to_form($mform, $answercount, $c);
            $answercount++;

            $mform->addElement('hidden', 'cropperadded', 1);
        }

        $mform->addElement('static', '', '', '<p />');

        // buttons to allow adding/deleting areas
        if (isset($this->question->options->qimage)) {
            $mform->addElement('submit', 'addcropper', get_string('addanotherarea', 'qtype_imagetarget'));
            // only allow deleting when there is more than cropper
            if ($answercount > 2) {
                $mform->addElement('submit', 'deletecropper', get_string('deletearea', 'qtype_imagetarget'));
            }
        }

        $mform->addElement('header', 'feedback', get_string('feedback'));
        $mform->addElement('htmleditor', 'correctfeedback', get_string('correct', 'qtype_imagetarget'));
        $mform->setType('correctfeedback', PARAM_RAW);
        $mform->addElement('htmleditor', 'incorrectfeedback', get_string('incorrect', 'qtype_imagetarget'));
        $mform->setType('incorrectfeedback', PARAM_RAW);
    }

    function set_data($question) {
        if (isset($question->options)){
            $default_values['qimage'] = $question->options->qimage;

            $answers = $question->options->answers;
            if (count($answers)) {
                $key = 0;
                foreach ($answers as $answer){
                    $default_values['answer['.$key.']'] = $answer->answer;
                    $default_values['fraction['.$key.']'] = $answer->fraction;
                    if ($answer->answer == "*") {
                        $default_values['incorrectfeedback'] = $answer->feedback;
                    }
                    else {
                        $default_values['correctfeedback'] = $answer->feedback;
                    }
                    $key++;
                }
            }
            $question = (object)((array)$question + $default_values);
        }
        parent::set_data($question);
    }

    function validation($data){
        $errors = array();

        if (empty($data['qimage'])) {
            $errors['qimage'] = get_string('needimage', 'qtype_imagetarget');
        }

        return $errors;
    }

    /**
     * Output the javascript for a single crop image.
     */
    function add_image_to_form($mform, $answercount, $c) {
        global $COURSE;

        $e = $mform->addElement('hidden', "answer[$answercount]");
        $e->updateAttributes(array('id'=>'id_answer_'.$answercount));
        $mform->addElement('hidden', 'fraction['.$answercount.']', 1);

        $script = "<script type=\"text/javascript\">
        Event.observe( window, 'load', function() {
            new Cropper.Img( 'cropimage_$answercount', {
                onEndCrop: onEndCrop$answercount,
                minWidth: 20,
                minHeight: 20,
                displayOnInit: true,
                captureKeys: false,
                onloadCoords: { x1: $c[0], y1: $c[1], x2: $c[2], y2: $c[3] }
           })
        });
        function onEndCrop$answercount(coords, dimensions) {
            answer = coords.x1 + ',' + coords.y1 + ',' + coords.x2 + ',' + coords.y2;
            $( 'id_answer_$answercount' ).value = answer;
        }
        </script>";

        $mform->addElement('static', 'cropper_'.$answercount, '', $script);

        $data = new stdClass;
        $data->image = $this->question->options->qimage;
        $data->category = $this->question->category;
        $image = get_question_image($data);
        $img = '<img id="cropimage_'.$answercount.'" src="' . $image . '" alt="question image" />';
        $mform->addElement('static', 'cropimage_'.$answercount, get_string('selecttarget', 'qtype_imagetarget'), $img);
    }


    function qtype() {
        return 'imagetarget';
    }
}
?>
