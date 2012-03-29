<?php
/**
 * The question type class for the Onte Order Excercise question type.
 *
 * @copyright &copy; 2008 Micha³ Zaborowski
 * @author michal.zaborowski@byd.pl
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package onte_questiontypes
 *//** */

/**
 * The Onte Order Excercise question class
 *
 * TODO give an overview of how the class works here.
 */
class onteorder_qtype extends default_questiontype {

	protected $licznik = 0;

    function name() {
        return 'onteorder';
    }
    
    // TODO think about whether you need to override the is_manual_graded or
    // is_usable_by_random methods form the base class. Most the the time you
    // Won't need to.

    /**
     * @return boolean to indicate success of failure.
     */
    function get_question_options(&$question) {
        // TODO code to retrieve the extra data you stored in the database into
        // $question->options.
	        if (!$question->options = get_record('question_onteorder', 'question', $question->id)) {
            notify('Error: Missing question options!');
			
            return false;
        }
		//var_export($question->options->sentence);
		/*
        if (!$question->options->answers = get_records('question_answers', 'question',
                $question->id, 'id ASC')) {
            notify('Error: Missing question answers!');
            return false;
        }
        */
        return true;
    }

    /**
     * Save the units and the answers associated with this question.
     * @return boolean to indicate success of failure.
     */
    function save_question_options($question) {
        // TODO code to save the extra data to your database tables from the
        // $question object, which has all the post data from editquestion.html
		    if ($options = get_record("question_onteorder", "question", $question->id)) {
            $options->sentence = $question->sentence;
            if (!update_record("question_onteorder", $options)) {
                $result->error = "Could not update quiz onteorder options! (id=$options->id)";
                return $result;
            }
        } else {
            unset($options);
            $options->question = $question->id;
            $options->sentence = $question->sentence;
            if (!insert_record("question_onteorder", $options)) {
                $result->error = "Could not insert quiz onteorder options!";
                return $result;
            }
        }
		
		//var_export($question);
		//exit;
        return true;
    }

    /**
     * Deletes question from the question-type specific tables
     *
     * @param integer $questionid The question being deleted
     * @return boolean to indicate success of failure.
     */
    function delete_question($questionid) {
		delete_records_select("question_onteorder", 'question='.$questionid);
        return true;
    }

    function create_session_and_responses(&$question, &$state, $cmoptions, $attempt) {
        // TODO create a blank repsonse in the $state->responses array, which    
        // represents the situation before the student has made a response.
		//echo "TUTAJ!!!";
		//var_export($question);
        return true;
    }

    function restore_session_and_responses(&$question, &$state) {
        // TODO unpack $state->responses[''], which has just been loaded from the
        // database field question_states.answer into the $state->responses array.
        return true;
    }
    
    function save_session_and_responses(&$question, &$state) {
        // TODO package up the students response from the $state->responses
        // array into a string and save it in the question_states.answer field.
    
        $responses = '';
    
        return set_field('question_states', 'answer', $responses, 'id', $state->id);
    }
    
    function print_question_formulation_and_controls(&$question, &$state, $cmoptions, $options) {
        global $CFG;

        $readonly = empty($options->readonly) ? '' : 'disabled="disabled"';

        // Print formulation
        $questiontext = $this->format_text($question->questiontext,
        $question->questiontextformat, $cmoptions);
        $image = get_question_image($question, $cmoptions->course);
    
        // TODO prepare any other data necessary. For instance
        $feedback = '';
		if($this->licznik==0){
			include("$CFG->dirroot/question/type/onteorder/script.js");
			include("$CFG->dirroot/question/type/onteorder/styles.css");			
			$this->licznik++;
		}
        include("$CFG->dirroot/question/type/onteorder/display.html");
    }
    
    function grade_responses(&$question, &$state, $cmoptions) {
        // TODO assign a grade to the response in state.
		$poprawnych=0;
		$wyrazy=explode("\n", $question->options->sentence);
		$odpowiedzi=stripslashes($state->responses['']);
		$odpowiedzi=explode('[split]',$odpowiedzi);
		for($i=0; $i<count($wyrazy); $i++){
			if(rtrim($wyrazy[$i])==$odpowiedzi[$i]){
				$poprawnych++;
			}
		}
		$state->raw_grade = $poprawnych/count($wyrazy);
        if (empty($state->raw_grade)) {
            $state->raw_grade = 0;
        }

        // Make sure we don't assign negative or too high marks
        $state->raw_grade = min(max((float) $state->raw_grade,
                            0.0), 1.0) * $question->maxgrade;
		$state->penalty = $question->penalty * $question->maxgrade;
					
		$state->event = ($state->event ==  QUESTION_EVENTCLOSE) ? QUESTION_EVENTCLOSEANDGRADE : QUESTION_EVENTGRADE;
//var_export($state);
return true;
    }
    
    function compare_responses($question, $state, $teststate) {
        // TODO write the code to return two different student responses, and
        // return two if the should be considered the same.
		foreach ($state->responses as $i=>$sr) {
            if (empty($teststate->responses[$i])) {
                if (!empty($state->responses[$i])) {
                    return false;
                }
            } else if ($state->responses[$i] != $teststate->responses[$i]) {
                return false;
            }
        }
        return true;
    }

    /**
     * Checks whether a response matches a given answer, taking the tolerance
     * and units into account. Returns a true for if a response matches the
     * answer, false if it doesn't.
     */
    function test_response(&$question, &$state, $answer) {
        // TODO if your code uses the question_answer table, write a method to
        // determine whether the student's response in $state matches the    
        // answer in $answer.
        return false;
    }

    function check_response(&$question, &$state){
        // TODO
        return false;
    }

    function get_correct_responses(&$question, &$state) {
        // TODO
        return false;
    }

    function get_all_responses(&$question, &$state) {
        $result = new stdClass;
        // TODO
        return $result;
    }

    function get_actual_response($question, $state) {
        // TODO
        $responses = '';
        return $responses;
    }

    /**
     * Backup the data in the question
     *
     * This is used in question/backuplib.php
     */
    function backup($bf,$preferences,$question,$level=6) {
        $status = true;

        // TODO write code to backup an instance of your question type.

        return $status;
    }

    /**
     * Restores the data in the question
     *
     * This is used in question/restorelib.php
     */
    function restore($old_question_id,$new_question_id,$info,$restore) {
        $status = true;

        // TODO write code to restore an instance of your question type.

        return $status;
    }
	
	
    function print_question_submit_buttons(&$question, &$state, $cmoptions, $options) {
        /* The default implementation should be suitable for most question
        types. It prints a mark button in the case where individual marking is
        allowed. */
		
        if (($cmoptions->optionflags & QUESTION_ADAPTIVE) and !$options->readonly) {
            echo '<input onclick="sprawdz('.$question->options->question.');" type="button" value="';
            print_string('mark', 'quiz');
            echo '" class="submit btn"';
            echo ' />';
			
            echo '<input type="submit" name="';
            echo $question->name_prefix;
            echo 'submit" id="';
            echo $question->name_prefix;
            echo 'submit" value="';
            print_string('mark', 'quiz');
            echo '" style="display:none;"';
            echo ' />';			
        }
    }
	
	function export_to_xml( $question, $to, $extra ) {
		//global $CFG,$QTYPES;
	    if(   $question->qtype == 'onteorder'){
			$zdanie=$question->options->sentence;
			$zdanie=str_replace("\n", '|', $zdanie);
			$zdanie=str_replace("\r", '', $zdanie);
			return '<sentence>'.$zdanie.'</sentence>';
		}
		return false;
	}

	function import_from_xml( $data, $question, $format, $extra=null ) {
		if($data['@']['type']=='onteorder'){
			$question = $format->import_headers( $data );
			$question->sentence=str_replace('|', "\n", $data['#']['sentence']['0']['#']);
			$question->qtype='onteorder';	   
			return $question;
		}else{
			return false;
		}
   }
}

	

// Register this question type with the system.
question_register_questiontype(new onteorder_qtype());
?>
