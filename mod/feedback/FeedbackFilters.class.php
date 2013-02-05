<?php

class FeedbackFilters {

    public $feedback;
    public $errors;
    public $ac_year_4digits;
	public $CFG;
    private $_debug;
	private $_past_year_survey;
	private $_call_count;

    public function __construct(stdClass $feedback_obj, $ac_year) {

        require_once('../../config.php');
		global $CFG;

        $this->_debug = true;
        $this->errors = array();

        $this->CFG = $CFG;
        $this->feedback = $feedback_obj;
        $this->ac_year_4digits = $ac_year;
		$this->_past_year_survey = $this->isOldSurvey();
		$this->_call_count = 0;

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

        $query = "SELECT DISTINCT pageid 
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
            WHERE item IN 
            (
                SELECT id 
                FROM mdl_feedback_item 
                WHERE feedback = %d 
                AND hasvalue = 1
            ) 
            ORDER BY course_id ASC', 
            $this->feedback->id
        );

        $feedback_values = get_records_sql($query);
        $cids_with_answers = array();

		foreach($feedback_values as $value) {
			if ($value->course_id == 0) continue;
			$cids_with_answers[] = $value->course_id;
		}
        return $cids_with_answers;

    }

    public function getCoursesWithNoFeedback() {

        $cids_have_block	= $this->getCourseIDsWithFeedbackBlock();
        $cids_have_answered = $this->getCourseIDsWithAnswers();
        $unanswered = array_diff($cids_have_block, $cids_have_answered);

        // Get course ids and names to display to user
        $not_submitted = implode(',', $unanswered);

        $query = sprintf(
        'SELECT id, fullname 
            FROM mdl_course 
            WHERE id IN (%s)',
            $not_submitted 
        );
        $no_feedback = array();
        if ($unanswered_courses = get_records_sql($query)) {
            foreach($unanswered_courses as $c) {
                if ($c->id == 1) continue;
                $no_feedback[] = '<a href="'.$this->CFG->wwwroot.'/course/view.php?id='.$c->id.'">'.$c->fullname.'</a>';
            }
        }
        return $no_feedback;

    }

    public function getFeedbackItems() {
        return get_records_select(
            'feedback_item', 'feedback = '. $this->feedback->id .' AND hasvalue = 1', 'position'
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
                  WHERE fv.item = %d 
                  AND fi.typ = '%s' 
                  AND fv.course_id = c.id 
                  AND fi.id = fv.item 
                  GROUP BY course_id, shortname 
                  ORDER BY avgvalue DESC", 
            $avgvalue, 
            $question_id,
            $question_type
        );

        if ($courses = get_records_sql($query)) {
            return $courses;
        } else {
            $this->errors[] = 'No course averages found for this question (id: ' . $question_id . ', type: ' . $question_type. ')';
            return false;
        }

    }

    public function getDirectoratesMenu() {
		$query = 
		   "SELECT id, name 
            FROM ".$this->CFG->prefix."course_categories 
            WHERE parent = 0 
            AND name LIKE 'Directorate%' 
            ORDER BY name ASC";

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
            WHERE name LIKE 'School of %' 
            ORDER BY name ASC";

        if ($schools = get_records_sql_menu($query)) {
            return $schools;
        } else {
            $this->errors[] = 'Schools categories not found';
            return false;
        }
    }

    public function getCurriculumMenu() {
        $curric_ids = "3,4,16,22,24,29,30,31,33,39,40,41,42,43,44,45,46,47,48,49,50,51,59,61,63,117,118,119,120,121,122,123,124,125,205,263,306,333,334,335,385,386,393,394,398,399,400,401,402,562";

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


    public function getCoursesWithAnswers($course_search='') {

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
		$query .= " WHERE fi.feedback = ".$this->feedback->id;
		$query .= " AND fi.id = fv.item AND c.id = fv.course_id ";
		if ($this->_past_year_survey === true) {
			$query .= " AND c.id = cia.course_id";
		}
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
            $query .= " WHERE fi.feedback = ".$this->feedback->id;
			$query .= " AND c.id = fv.course_id AND fi.id = fv.item";
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

	private function getOldIDNumbers(array $courses) {
		$prop_name = 'year_' . $this->ac_year_4digits;
		$course_ids = array();
		foreach ($courses as $c) {
			$course_ids[] = $c->id;	
		}
		$course_ids_csv = implode(',', $course_ids);

		// Put all the given years idnumbers into an array
		$query = sprintf(
			"SELECT course_id, %s as name 
			FROM %scourse_idnumber_archive
			WHERE course_id IN (%s)",
			$prop_name,
			$this->CFG->prefix,
			$course_ids_csv
		);
		$old_id_numbers = get_records_sql($query);
		return $old_id_numbers;
	}

    public function formatCoursesForDropdown(array $courses) {

		if ($this->_past_year_survey === true) {
			$old_id_numbers = $this->getOldIDNumbers($courses);	
		}
        $found = array();
        foreach ($courses as $cid) {
			$found[$cid->id] = $cid->shortname;
			if ($this->_past_year_survey === true) {
				$name = $old_id_numbers[$cid->id]->name;
				$found[$cid->id] = ($name !== NULL) ? $name : '[*' . $cid->shortname . '*]';
			}
        }
        return $found;
    }


    public function getSubcategories($category_id='') {
        if (!is_numeric($category_id)) {
            $this->errors[] = 'Category not a number ' . $category_id;
            return false;
        }
		$query = sprintf(
			"SELECT path FROM ".$this->CFG->prefix."course_categories WHERE path REGEXP('(/%d/){1}(/)?')", 
            $category_id
        );

        $paths = get_records_sql($query);

		$subcategories = array();
		foreach($paths as $path) {
			$new_cats = explode('/', $path->path);
			$subcategories = array_merge($subcategories, $new_cats);
		}
		$subcategories = array_unique($subcategories);

        return $subcategories;
    }

    public function getCoursesInCategory($category_id, $course_search='') {

        $courses_with_answers = $this->getCoursesWithAnswers($course_search);

        if ($courses_with_answers === false) {
            $this->errors[] = 'No courses have answers';
            return false;
        }
		
        $subcategories = $this->getSubcategories($category_id);

		$found_courses = array();
		foreach ($courses_with_answers as $c) {
			if (in_array($c->category, $subcategories)) {
				$found_courses[] = $c;
			}
		}

        $courses = $this->formatCoursesForDropdown($found_courses);
        return $courses;

    }

    public function getCoursesInLevel($level_key) {

        $level_courses = $this->getCoursesForLevel($level_key);

        if ($level_courses === false) {
            $this->errors[] = 'No courses have answers';
            return false;
        }

        $courses = $this->formatCoursesForDropdown($level_courses);
        return $courses;

    }

	public function getCourseIDsAsCSV($courses) {
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

    private function getQuestions() {
        $query = sprintf(
            "SELECT id, name 
            FROM mdl_feedback_item 
            WHERE feedback = %d 
            AND hasvalue = 1 
            ORDER BY position ASC", 
            $this->feedback->id
        );

        if ($questions = get_records_sql($query)) {
            return $questions;
        } else {
            $this->errors[] = 'This feedback has no questions';
            return false;
        }
    }


    public function getFilterIDs() {

        $filter_qs = array(
            'site_id' => 'Which site do you study at?',
            'gender_id' => 'Gender',
            'age_id' => 'Age',
            'ethnic_id' => 'How would you describe your ethnic origin?',
            'attendance_id' => 'Attendance',
            'dld_id' => 'Do you have a Learning Difficulty?',
            'dldb_id' => 'Do you have a Disability?'
        );

        $questions = $this->getQuestions();

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
