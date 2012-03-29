<?php
/**
 *
 * The question type class for the image target question type.
 *
 * The javascript version of this question type provides a draggable
 * bull's eye and a target image.  The position of the bull's eye on target
 * image is the submitted response for the question.
 *
 * The non-javascript version removes the "Submit" button and instead
 * the student clicks on the image to submit the coordinates.
 *
 * @copyright &copy; 2007 Adriane Boyd
 * @author adrianeboyd@gmail.com
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package aab_imagetarget
 */

class question_imagetarget_qtype extends default_questiontype {

    function name() {
        return 'imagetarget';
    }

    function get_question_options(&$question) {
        // Get additional information from database
        // and attach it to the question object
        if (!$question->options = get_record('question_imagetarget', 'question', $question->id)) {
            notify('Error: Missing question options!');
            return false;
        }

        if (!$question->options->answers = get_records('question_answers', 'question',
                $question->id, 'id ASC')) {
            notify('Error: Missing question answers!');
            return false;
        }
        return true;
    }

    /**
     * When the teacher is editing the question and clicks on the new button
     * "Choose image", the question options are saved as usual, but instead of
     * returning to the main questions page, the editing page is shown with
     * the chosen image so that the teacher can specify the coordinates for the
     * correct answer.
     */
    function save_question($question, $form, $course) {
        global $CFG, $COURSE;

        // save question as usual
        $question = parent::save_question($question, $form, $course);

        $params = array();
        $params['returnurl'] = $form->returnurl;
        $params['id'] = $question->id;
        $params['courseid'] = $COURSE->id;

        // figure out if a custom submit button was clicked
        // if so, redirect back to the editing page with the correct parameters
        if (isset($form->chooseimage) or isset($form->addcropper)) {
            $params['addcropper'] = 1;
            $url = new moodle_url($CFG->wwwroot . '/question/question.php', $params);
            redirect($url->out());
        } elseif (isset($form->replaceimage)) {
            $url = new moodle_url($CFG->wwwroot . '/question/question.php', $params);
            redirect($url->out());
        } elseif (isset($form->deletecropper)) {
            $params['deletecropper'] = 1;
            $url = new moodle_url($CFG->wwwroot . '/question/question.php', $params);
            redirect($url->out());
        }

        return $question;
    }

    /**
     * Save options in question_answers and question_imagetarget.
     */
    function save_question_options($question) {
        $result = new stdClass;

        if (!$oldanswers = get_records('question_answers', 'question', $question->id, 'id ASC')) {
            $oldanswers = array();
        }

        $answers = array();

        // Insert all the new answers
        foreach ($question->answer as $key => $dataanswer) {
            if ($dataanswer != "") {
                if ($oldanswer = array_shift($oldanswers)) {  // Existing answer, so reuse it
                    $answer = $oldanswer;
                    $answer->answer   = trim($dataanswer);
                    $answer->fraction = $question->fraction[$key];
                    if ($answer->fraction == 1) {
                        $answer->feedback = $question->correctfeedback;
                    }
                    else {
                        $answer->feedback = $question->incorrectfeedback;
                    }
                    if (!update_record("question_answers", $answer)) {
                        $result->error = "Could not update quiz answer! (id=$answer->id)";
                        return $result;
                    }
                } else { // This is a completely new answer
                    $answer = new stdClass;
                    $answer->answer   = trim($dataanswer);
                    $answer->question = $question->id;
                    $answer->fraction = $question->fraction[$key];
                    if ($answer->fraction == 1) {
                        $answer->feedback = $question->correctfeedback;
                    }
                    else {
                        $answer->feedback = $question->incorrectfeedback;
                    }
                    if (!$answer->id = insert_record("question_answers", $answer)) {
                        $result->error = "Could not insert quiz answer!";
                        return $result;
                    }
                }
                $answers[] = $answer->id;
            }
        }

        // delete old answer records
        if (!empty($oldanswers)) {
            foreach($oldanswers as $oa) {
                delete_records('question_answers', 'id', $oa->id);
            }
        }

        if ($options = get_record("question_imagetarget", "question", $question->id)) {
            $options->answers = implode(",",$answers);
            $options->qimage = $question->qimage;
            if (!update_record("question_imagetarget", $options)) {
                $result->error = "Could not update quiz imagetarget options! (id=$options->id)";
                return $result;
            }
        } else {
            unset($options);
            $options->question = $question->id;
            $options->answers = implode(",",$answers);
            $options->qimage = $question->qimage;
            if (!insert_record("question_imagetarget", $options)) {
                $result->error = "Could not insert quiz imagetarget options!";
                return $result;
            }
        }
    }

    /**
    * Deletes question from the question-type specific tables
    *
    * @return boolean Success/Failure
    * @param object $question  The question being deleted
    */
    function delete_question($questionid) {
        delete_records('question_imagetarget', 'question', $questionid);
        return true;
    }

    /**
     * The default saving of $state->responses[''] as the answer does not work
     * with the imagemap version, so the default save_session_responses function
     * is overridden to save the answer correctly.  The default
     * create_session_and_responses and restore_session_and_responses still
     * work properly.
     *
     * @param object $question the question object containing the answer
     * @param object $state the state object
     * @return boolean for success or failure saving
     */
    function save_session_and_responses(&$question, &$state) {
        // set the answer
        $state->answer = $this->process_imagemap($state->responses);

        // update the state entry
        if (!update_record('question_states', $state)) {
            return false;
        }

        return true;
    }

    /**
     * If this question type requires extra CSS or JavaScript to function,
     * then this method will return an array of <link ...> tags that reference
     * those stylesheets. This function will also call require_js()
     * from ajaxlib.php, to get any necessary JavaScript linked in too.
     *
     * The YUI libraries needed for dragdrop have been added to the default
     * set of libraries.
     *
     * The two parameters match the first two parameters of print_question.
     *
     * @param object $question The question object.
     * @param object $state    The state object.
     *
     * @return an array of bits of HTML to add to the head of pages where
     * this question is print_question-ed in the body. The array should use
     * integer array keys, which have no significance.
     */
    function get_html_head_contributions(&$question, &$state) {
        // Load YUI libraries
        require_js("yui_yahoo");
        require_js("yui_event");
        require_js("yui_dom");
        require_js("yui_dragdrop");
        require_js("yui_animation");

        $contributions = parent::get_html_head_contributions($question, $state);

        return $contributions;
    }

    function print_question_formulation_and_controls(&$question, &$state, $cmoptions, $options) {
        global $CFG;

        // Check browser version to see if YUI is supported properly.
        // This is similar to ajaxenabled() from lib/ajax/ajaxlib.php,
        // except it doesn't check the site-wide AJAX settings.
        $fallbackonly = false;

        $ie = check_browser_version('MSIE', 6.0);
        $ff = check_browser_version('Gecko', 20051106);
        $op = check_browser_version('Opera', 9.0);
        $sa = check_browser_version('Safari', 412);

        if ((!$ie && !$ff && !$op && !$sa) or !empty($USER->screenreader)) {
            $fallbackonly = true;
        }

        $formatoptions = new stdClass;
        $formatoptions->noclean = true;
        $formatoptions->para = false;
        $nameprefix = $question->name_prefix;

        $questiontext = format_text($question->questiontext,
                $question->questiontextformat,
                $formatoptions, $cmoptions->course);
        $image = get_question_image($question);

        // Get the response
        $response = $this->process_imagemap($state->responses);

        // Split it into coordinates
        $responseparts = explode(',', $response);
        $responsex = $responseparts[0];
        $responsey = $responseparts[1];

        // Set non-javascript and javascript prompts
        $prompt = get_string('prompt', 'qtype_imagetarget');

        $jsprompt = '<p id="'.$nameprefix.'base"><img id="'.$nameprefix.'target" src="'.$CFG->wwwroot.'/question/type/imagetarget/bullseye.png" /></p>';
        $jsprompt = get_string('jsprompt', 'qtype_imagetarget') . $jsprompt;
        $jsprompt = '<div>' . $jsprompt . '</div>';
        $jsprompt = preg_replace('/"/', '\\"', $jsprompt);

        // Set the path to the target image
        $data = new stdClass;
        $data->image = $question->options->qimage;
        $data->category = $question->category;
        $qimage = get_question_image($data);

        $qimagerelative = '/' . $cmoptions->course . '/' . $question->options->qimage;

        // Set the feedback
        $feedback = '';
        $class = '';
        $feedbackimg = '';

        // Get the answer that corresponds to the submitted coordinates
        $ans = $this->get_answer($question->options->answers, $response);

        // Set feedback variables as needed
        if ($options->feedback) {
            $class = question_get_feedback_class($ans->fraction);
            $feedbackimg = question_get_feedback_image($ans->fraction);
            $feedback = format_text($ans->feedback, true, $formatoptions, $cmoptions->course);
        }

        // Set submit button variables
        $submit = $this->get_question_submit_buttons($question, $state, $cmoptions, $options);

        include("$CFG->dirroot/question/type/imagetarget/display.html");
    }

   /**
    * Return javascript and non-javascript HTML for the submit button functions.
    *
    * The javascript version is the typical submit button (same as parent) with
    * the double quotes escaped.
    *
    * The non-javascript version is a hidden variable corresponding to the submit
    * button.
    *
    * @param object $question The question for which the submit button(s) are to
    *                         be rendered. Question type specific information is
    *                         included. The name prefix for any
    *                         named elements is in ->name_prefix.
    * @param object $state    The state to render the buttons for. The
    *                         question type specific information is also
    *                         included.
    * @param object $cmoptions
    * @param object $options  An object describing the rendering options.
    *
    * @return array containing the 'script' and 'noscript' submit HTML
    */
    function get_question_submit_buttons(&$question, &$state, $cmoptions, $options) {
        $submit = array();
        if (($cmoptions->optionflags & QUESTION_ADAPTIVE) and !$options->readonly) {
            $submit['script'] = '<input type="submit" name="' .$question->name_prefix. 'submit" value="'.
                    get_string('mark', 'quiz'). '" class="submit btn" onclick="'.
                    "form.action = form.action + '#q". $question->id. "'; return true;". '" />';
            $submit['script'] = preg_replace('/"/', '\\"', $submit['script']);
            $submit['noscript'] = '<input type="hidden" name="' .$question->name_prefix. 'submit" />';
        }
        return $submit;
    }

    function process_imagemap($responses) {
        if (isset($responses['imagemap_x']) and isset($responses['imagemap_y'])) {
            return $responses['imagemap_x'] . ',' . $responses['imagemap_y'];
        } elseif (isset($responses['']) and !empty($responses[''])) {
            return $responses[''];
        } elseif (isset($responses['previous'])) {
            return $responses['previous'];
        }
        return '0,0';
    }

    /**
     * Compares two responses.
     *
     * @param object $question question
     * @param object $state state to compare
     * @param object $teststate state to compare
     *
     * @return boolean true if identical
     */
    function compare_responses($question, $state, $teststate) {
        $response1 = $this->process_imagemap($state->responses);
        $response2 = $this->process_imagemap($teststate->responses);
        if ($response1 == $response2) {
            return true;
        }
        return false;
    }

    /**
     * Returns the coordinates of the center of the correct area.
     *
     * @param object $question question
     * @param object $state state
     *
     * @return string coordinates in "x,y" format
     */
    function get_correct_responses(&$question, &$state) {
        $responses = array();
        foreach ($question->options->answers as $answer) {
            if ($answer->fraction == 1) {
                $ansparts = explode(',', $answer->answer);
                $x = (int) (($ansparts[2] + $ansparts[0]) / 2);
                $y = (int) (($ansparts[3] + $ansparts[1]) / 2);
                $responses[''] = "$x,$y";
            }
        }
        return empty($responses) ? null : $responses;
    }

    /**
     * Return the answer that corresponds to the response coordinates.
     *
     * @param array $answers array of answers from question object
     * @param string $response response in "x,y" format
     *
     * @return object an object from $answers that corresponds to the response
     */
    function get_answer($answers, $response) {
        $responsex = 0;
        $responsey = 0;
        if ($response) {
            $responseparts = explode(',', $response);
            $responsex = $responseparts[0];
            $responsey = $responseparts[1];
        }

        foreach ($answers as $answer) {
            $ans = $answer->answer;
            $ansparts = explode(',', $ans);
            // if the answer has four coordinates, check if the response is within
            // the boundaries
            if (count($ansparts) == 4) {
                if (($ansparts[0] <= $responsex) and ($responsex <= $ansparts[2])
                    and ($ansparts[1] <= $responsey) and ($responsey <= $ansparts[3])) {
                    return $answer;
                }
            // if the answer is *, save it to return if all the coordinate answer
            // checks fail
            } elseif ($ans == "*") {
                $incorrectanswer = $answer;
            }
        }

        // return the incorrect answer if the response doesn't fall into any of the
        // specified coordinates
        return $incorrectanswer;
    }

    function grade_responses(&$question, &$state, $cmoptions) {
        // determine the response and get the corresponding answer
        $responses = $this->process_imagemap($state->responses);
        $ans = $this->get_answer($question->options->answers, $responses);

        // use the answer to set the grade
        $state->raw_grade = $ans->fraction;
        if (empty($state->raw_grade)) {
            $state->raw_grade = 0;
        }

        // make sure we don't assign negative or too high marks
        $state->raw_grade = min(max((float) $state->raw_grade,
                            0.0), 1.0) * $question->maxgrade;
        $state->penalty = $question->penalty * $question->maxgrade;

        // mark the state as graded
        $state->event = ($state->event ==  QUESTION_EVENTCLOSE) ? QUESTION_EVENTCLOSEANDGRADE : QUESTION_EVENTGRADE;

        return true;
    }

    /*
     * Backup the data in the question
     *
     * This is used in question/backuplib.php
     */
    function backup($bf, $preferences, $question, $level=6) {
        $status = true;

        $imagetargets = get_records('question_imagetarget', 'question', $question, 'id ASC');
        // If there are imagetargets
        if ($imagetargets) {
            // Iterate over each imagetarget
            foreach ($imagetargets as $imagetarget) {
                $status = fwrite($bf, start_tag('IMAGETARGET', $level, true));
                // Print imagetarget contents
                fwrite($bf, full_tag('ANSWERS', $level+1, false, $imagetarget->answers));
                fwrite($bf, full_tag('QIMAGE',$level+1, false, $imagetarget->qimage));
                $status = fwrite($bf, end_tag('IMAGETARGET', $level, true));
            }
            // Now print question_answers
            $status = question_backup_answers($bf, $preferences, $question);
        }
        return $status;
    }

    /*
     * Restores the data in the question
     *
     * This is used in question/restorelib.php
     */
    function restore($oldquestionid, $newquestionid, $info, $restore) {
        $status = true;

        // Get the imagetargets array
        $imagetargets = $info['#']['IMAGETARGET'];

        // Iterate over imagetargets
        for($i = 0; $i < sizeof($imagetargets); $i++) {
            $sho_info = $imagetargets[$i];

            // Now, build the question_imagetarget record structure
            $imagetarget = new stdClass;
            $imagetarget->question = $newquestionid;
            $imagetarget->answers = backup_todb($sho_info['#']['ANSWERS']['0']['#']);
            $imagetarget->qimage = backup_todb($sho_info['#']['QIMAGE']['0']['#']);

            // We have to recode the answers field (a list of answers id)
            // Extracts answer id from sequence
            $answersfield = '';
            $infirst = true;
            $tok = strtok($imagetarget->answers, ',');
            while ($tok) {
                //Get the answer from backup_ids
                $answer = backup_getid($restore->backup_unique_code, 'question_answers', $tok);
                if ($answer) {
                    if ($infirst) {
                        $answersfield .= $answer->new_id;
                        $infirst = false;
                    } else {
                        $answersfield .= ",".$answer->new_id;
                    }
                }
                // check for next
                $tok = strtok(",");
            }
            // We have the answers field recoded to its new ids
            $imagetarget->answers = $answersfield;

            // The structure is equal to the db, so insert the question_imagetarget
            $newid = insert_record('question_imagetarget', $imagetarget);

            // Do some output
            if (($i+1) % 50 == 0) {
                if (!defined('RESTORE_SILENTLY')) {
                    echo ".";
                    if (($i+1) % 1000 == 0) {
                        echo "<br />";
                    }
                }
                backup_flush(300);
            }

            if (!$newid) {
                $status = false;
            }
        }

        return $status;
    }

    function import_from_xml($data, $question, $format, $extra=null) {
        if (!array_key_exists('@', $data)) {
            return false;
        }
        if (!array_key_exists('type', $data['@'])) {
            return false;
        }
        if ($data['@']['type'] == 'imagetarget') {
            $question = $format->import_headers($data);

            // header parts particular to image target
            $question->qtype = 'imagetarget';
            $question->qimage = $format->getpath($data, array('#', 'qimage', 0, '#'), 0);

            // run through the answers
            $answers = $data['#']['answer'];
            $acount = 0;
            foreach ($answers as $answer) {
                $ans = $format->import_answer($answer);
                $question->answer[$acount] = $ans->answer;
                $question->fraction[$acount] = $ans->fraction;
                $question->feedback[$acount] = $ans->feedback;
                $acount++;
            }

            return $question;
        }

        return false;
    }

    function export_to_xml($question, $format, $extra=null) {
        $expout = '';

        $expout .= "    <qimage>{$question->options->qimage}</qimage>\n ";
        foreach($question->options->answers as $answer) {
            $percent = 100 * $answer->fraction;
            $expout .= "    <answer fraction=\"$percent\">\n";
            $expout .= $format->writetext( $answer->answer,3,false );
            $expout .= "      <feedback>\n";
            $expout .= $format->writetext( $answer->feedback,4,false );
            $expout .= "      </feedback>\n";
            $expout .= "    </answer>\n";
        }

        return $expout;
    }

}
//// END OF CLASS ////

//////////////////////////////////////////////////////////////////////////
//// INITIATION - Without this line the question type is not in use... ///
//////////////////////////////////////////////////////////////////////////
question_register_questiontype(new question_imagetarget_qtype());
?>
