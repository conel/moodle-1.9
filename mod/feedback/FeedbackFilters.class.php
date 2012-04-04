<?php

class FeedbackFilters {

    public $feedback;
    public $errors;
    public $ac_year_4digits;
    private $_debug;
	private $_past_year_survey;

    public function __construct(stdClass $feedback_obj, $ac_year) {

        require_once('../../config.php');

        global $CFG;
        $this->CFG = $CFG;
        $this->feedback = $feedback_obj;
        $this->ac_year_4digits = $ac_year;
		$this->_past_year_survey = $this->isOldSurvey();
        $this->errors = array();
        $this->_debug = true;

    }

    private function getACYear4Digits() {
        $academicYearStart = strftime("%y",strtotime("-8 months",time()));
        $academicYearEnd = strftime("%y",strtotime("+4 months",time()));
        $year = $academicYearStart . $academicYearEnd;
        return $year;
    }

	private function isOldSurvey() {
		$current_year = $this->getACYear4Digits();
		if ($current_year != $this->ac_year_4digits) {
			return true;
		} else {
			return false;
		}
	}

    // Get course ids that have the feedback block on them
    private function getCourseIDsWithFeedbackBlock() {

        $query = 
        "SELECT DISTINCT pageid 
            FROM ".$this->CFG->prefix."block_instance 
            WHERE blockid = 37 
            AND visible = 1 
            ORDER BY pageid ASC";

        $cids_with_feedback = array();
        if ($feedback_cids = get_records_sql($query)) {
            foreach($feedback_cids as $cid) {
                $cids_with_feedback[] = $cid->pageid;
            }
        }
        return $cids_with_feedback;

    }

    // Get course ids from submitted feedback values
    private function getCourseIDsWithAnswers() {

        $query = sprintf(
        'SELECT DISTINCT course_id 
            FROM '.$this->CFG->prefix.'feedback_value 
            WHERE course_id <> 0 
            AND item IN 
            (
                SELECT id 
                FROM mdl_feedback_item 
                WHERE feedback = %d 
                AND hasvalue = 1
            ) 
            ORDER BY course_id ASC', 
            $this->feedback->id
        );

        $cids_with_answers = array();
        if ($feedback_values = get_records_sql($query)) {
            foreach($feedback_values as $value) {
                $cids_with_answers[] = $value->course_id;
            }
        }
        return $cids_with_answers;

    }

    public function getCoursesWithNoFeedback() {

        $have_feedback_block = $this->getCourseIDsWithFeedbackBlock();
        $have_feedback = $this->getCourseIDsWithAnswers();
        $unanswered = array_diff($have_feedback_block, $have_feedback);

        // Get course ids and names to display to user
        $not_submitted = implode(',', $unanswered);

        $query = sprintf(
        'SELECT id, fullname, shortname, idnumber 
            FROM mdl_course 
            WHERE id IN (%s)',
            $not_submitted 
        );
        $no_feedback = array();
        if ($unanswered_courses = get_records_sql($query)) {
            foreach($unanswered_courses as $c) {
                if ($c->id == 1) continue;
                $id = ($c->idnumber != '') ? $c->idnumber : $c->shortname;
                $no_feedback[] = '<a href="'.$this->CFG->wwwroot.'/course/view.php?id='.$c->id.'">'.$c->id.' - '.$c->fullname.'</a>';
            }
        }
        return $no_feedback;

    }

    public function getFeedbackItems() {
        return get_records_select(
            'feedback_item', 
            'feedback = '. $this->feedback->id .' AND hasvalue = 1', 
            'position'
        );
    }

    public function getCourseAvgForQuestion($question_id='', $question_type='') {

        if ($question_id == '' || !is_numeric($question_id) || $question_type == '') {
            $this->errors[] = 'Invalid params given to getCourseAvgForQuestion';
            return false;
        }

        $avgvalue = ($this->CFG->dbtype != 'postgres7') ? 'avg(value)' : 'avg(cast (value as integer))';
		$query = sprintf(
			"SELECT fv.course_id, c.shortname, %s as avgvalue  
            FROM ".$this->CFG->prefix."feedback_value fv, 
                ".$this->CFG->prefix."course c, 
                ". $this->CFG->prefix."feedback_item fi 
                  WHERE fv.course_id = c.id 
                  AND fi.id = fv.item 
                  AND fi.typ = '%s' 
                  AND fv.item = %d 
                  GROUP BY course_id, shortname 
                  ORDER BY avgvalue DESC", 
            $avgvalue, 
            $question_type,
            $question_id
        );

        if ($courses = get_records_sql($query)) {
            return $courses;
        } else {
            $this->errors[] = 'No course averages found for this question (id: ' . $question_id . ', type: ' . $question_type. ')';
            return false;
        }

    }

    public function getDirectoratesMenu() {
        $query = "SELECT id, name 
            FROM ".$this->CFG->prefix."course_categories 
            WHERE parent = 0 
            AND name LIKE '%Directorate%' 
            ORDER BY sortorder ASC";

        if ($directorates = get_records_sql_menu($query)) {
            return $directorates;
        } else {
            $this->errors[] = 'Directorate categories not found';
            return false;
        }
    }

    public function getSchoolsMenu() {
        $query = "SELECT id, name 
            FROM ".$this->CFG->prefix."course_categories 
            WHERE name LIKE '%School of%' 
            ORDER BY sortorder ASC";

        if ($schools = get_records_sql_menu($query)) {
            return $schools;
        } else {
            $this->errors[] = 'Schools categories not found';
            return false;
        }
    }

    public function getCurriculumMenu() {
        $curric_ids = "3, 4, 16, 22, 24, 29, 30, 31, 33, 39, 40, 41, 42, 43, 44, 45, 46, 47, 48, 49, 50, 51, 59, 61, 63, 117, 118, 119, 120, 121, 122, 123, 124, 125, 205, 263, 306, 333, 334, 335, 385, 386, 393, 394, 398, 399, 400, 401, 402, 562";

        $query = sprintf(
            "SELECT id, name FROM ".$this->CFG->prefix."course_categories
                WHERE id IN (%s) 
                ORDER BY name ASC",
                $curric_ids    
        );
        if ($curric_cats = get_records_sql_menu($query)) {
            return $curric_cats;
        } else {
            $this->errors[] = 'Curriculum area categories not found';
            return false;
        }
    }


    public function getCoursesWithSurveyAnswers($course_search='') {

		$query = "SELECT DISTINCT c.id, c.shortname, c.category";
			if ($this->_past_year_survey === true) {
				$query .= ", cia.year_".$this->ac_year_4digits;
			}	
			$query .= " FROM ".$this->CFG->prefix."course c, 
            ".$this->CFG->prefix."feedback_value fv, 
			".$this->CFG->prefix."feedback_item fi";
			if ($this->_past_year_survey === true) {
				$query .= ", ".$this->CFG->prefix."course_idnumber_archive cia";
			}
			$query .= " WHERE c.id = fv.course_id AND fi.id = fv.item";
			if ($this->_past_year_survey === true) {
				$query .= " AND c.id = cia.course_id";
			}
            $query .= " AND fi.feedback = ".$this->feedback->id;
            if ($course_search != '') {
                $query .= " AND (c.shortname LIKE '%$course_search%' OR 
				c.fullname LIKE '%$course_search%'";
				if ($this->_past_year_survey === true) {
					$query .= " OR cia.year_".$this->ac_year_4digits." LIKE '%$course_search%')";
				}
            }
            $query .= " ORDER BY c.shortname";

        $courses = get_records_sql($query);
        return $courses;
    }

    public function getCoursesForLevel($level='') {

		$query = "SELECT DISTINCT c.id, c.shortname, c.category";
			if ($this->_past_year_survey === true) {
				$query .= ", cia.year_".$this->ac_year_4digits;
			}
			$query .= " FROM ".$this->CFG->prefix."course c, 
            ".$this->CFG->prefix."feedback_value fv, 
            ".$this->CFG->prefix."feedback_item fi";
			if ($this->_past_year_survey === true) {
				$query .= ", ".$this->CFG->prefix."course_idnumber_archive cia";
			}
			$query .= " WHERE c.id = fv.course_id AND fi.id = fv.item";
            $query .= " AND fi.feedback = ".$this->feedback->id;
			if ($this->_past_year_survey === true) {
				$query .= " AND c.id = cia.course_id";
			}
            if ($level != '') {
                $query .= " AND (c.shortname LIKE '__$level%')"; 
            }
            $query .= " ORDER BY c.shortname";

        $courses = get_records_sql($query);
        return $courses;
    }

    private function getPathIDsForCategory($category_id='') {
        if (!is_numeric($category_id)) {
            $this->errors[] = 'Category not a number ' . $category_id;
            return false;
        }
        $query = sprintf(
            "SELECT path 
            FROM ".$this->CFG->prefix."course_categories 
            WHERE id = %d", 
            $category_id
        );

        $path = get_record_sql($query);
        $path_ids = explode('/', $path->path);
        return $path_ids;
    }

    public function getCoursesFromIDs(array $courses) {

        $found = array();
		$this_ac_year = $this->getACYear4Digits();

        foreach ($courses as $cid) {
			$found[$cid->id] = $cid->shortname;
			if ($this->_past_year_survey === true) {
				if ($course_found = get_record('course_idnumber_archive', 'course_id', $cid->id)) {
					$prop_name = 'year_' . $this->ac_year_4digits;
					$name = $course_found->$prop_name;
					$found[$cid->id] = $name;
				}
			}
        }
        return $found;
    }

    public function getCoursesForFilter($filter_id, $course_search='') {

        $courses_with_answers = $this->getCoursesWithSurveyAnswers($course_search);

        if ($courses_with_answers === false) {
            $this->errors[] = 'No courses have answers';
            return false;
        }

        $found_courses = array();
        foreach ($courses_with_answers as $course) {
            $path_ids = $this->getPathIDsForCategory($course->category);
            if (in_array($filter_id, $path_ids)) {
				$found_courses[] = $course;
            }
        }

        $courses = $this->getCoursesFromIDs($found_courses);
        return $courses;

    }

    public function getCoursesForLevelFilter($level_key) {

        $level_courses = $this->getCoursesForLevel($level_key);

        if ($level_courses === false) {
            $this->errors[] = 'No courses have answers';
            return false;
        }

        $courses = $this->getCoursesFromIDs($level_courses);
        return $courses;

    }

	public function getCSVIDsFromCourse(array $courses) {
		if (count($courses) == 0) {
			return false;
		}
		$ids = array();
		foreach ($courses as $key => $value) {
			$ids[] = $key;
		}
		$csv_ids = implode(',', $ids);
		return $csv_ids;
	}


    public function getSelected($value, $expected, $type) {
        $html_select = ' selected="selected"';
        $html_radio  = ' checked="checked"';

        if ($value == $expected) {
            return ($type == 'radio') ? $html_radio : $html_select;    
        } else {
            return '';
        }
    }

    private function getFeedbackQuestions() {
        $query = sprintf(
            "SELECT id, name 
            FROM mdl_feedback_item 
            WHERE feedback = %d 
            AND hasvalue = 1 
            ORDER BY position", 
            $this->feedback->id
        );

        if ($questions = get_records_sql($query)) {
            return $questions;
        } else {
            $this->errors[] = 'This feedback has no questions';
            return false;
        }
    }

    public function feedbackHasResponses() {
        $questions = $this->getFeedbackQuestions();
        $qids = array();
        foreach ($questions as $q) {
            $qids[] = $q->id;
        }
        // put ids into a comma separated string for IN query
        $qids_cs = implode(',', $qids);

        $query = sprintf(
            "SELECT id FROM ".$this->CFG->prefix."feedback_value 
            WHERE item IN (%s)", $qids_cs
        );

        if (get_records_sql($query) === false) {
            return false;
        } else {
            return true;
        }
    }

    public function getFilterIDsAndValues() {

        $filter_qs = array(
            'site_id'       => 'Which site do you study at?',
            'gender_id'     => 'Gender',
            'age_id'        => 'Age',
            'enthnic_id'    => 'How would you describe your ethnic origin?',
            'attendance_id' => 'Attendance',
            'dld_id'        => 'Do you have a Learning Difficulty?',
            'dldb_id'       => 'Do you have a Disability?'
        );

        $questions = $this->getFeedbackQuestions();

        $mappings = array();
        if ($questions !== false) {
            foreach($questions as $q) {
                if ($key = array_search($q->name, $filter_qs)) {
                   $mappings[$key] = $q->id; 
                }
            }
            return $mappings;

        } else {
            $this->errors[] = 'No questions found for the current feedback';
            return false;
        }
    }

    public function getUsersByQandA($question_id, $answer_value) {
        $query = sprintf(
            "SELECT completed 
            FROM ".$this->CFG->prefix."feedback_value 
            WHERE item = %d 
            AND value = %s",
            $question_id,
            $answer_value
        );
        $users = array();
        if ($users_by_qa = get_records_sql($query)) {
            foreach($users_by_qa as $user) {
                $users[] = $user->completed;
            }
            return $users;
        } else {
            return false;
        }
    }

    public function __destruct() {

        if ($this->_debug === true && count($this->errors) > 0) {
            echo '<div style="color:red;">';
            echo "<h2>Errors</h2>";
            echo '<ul>';
            foreach($this->errors as $error) {
                echo "<li>$error</li>";
            }
            echo '</ul>';
            echo '</div>';
        }

        $this->errors[] = array(); // reset

    }

}
?>
