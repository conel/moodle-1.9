<?php

    /**
     * AttendancePunctuality Class
     *
     * @author Nathan Kowald <NKowald@staff.conel.ac.uk>
     * @version 1.0
     * @description Talks to EBS and gets attendance and punctuality info for Moodle
     *
     */

    // Make sure config is included so we can use MDL database functions
    require_once($_SERVER['DOCUMENT_ROOT'] . '\config.php');
    // Use the execute_query method ULCC created
    require_once($_SERVER['DOCUMENT_ROOT'] . '\blocks\lpr\models\block_lpr_conel_mis_db.php');

    class AttendancePunctuality extends block_lpr_conel_mis_db {

        public $valid_terms;
        public $errors;
        public $timer_start;
        public $marks_key;
        public $academic_year;
		public $academic_year_4digit;
        public $debug; // show errors?
        
        function __construct() {

            // call parent constructor to set up db
            parent::__construct();

            $this->debug = false; // on dev
            $this->valid_terms = array(1, 2, 3);
            $this->errors = array();
            $this->timer_start = '';
            // Start the timer as soon as the class is instantiated
            $this->start_timer();

            $this->academic_year = $this->resolve_year();
			$this->academic_year_4digit = $this->getAcYear4Digit();

            $this->marks_key['/'] = 'Present';
            $this->marks_key['O'] = 'Absent';
            $this->marks_key['C'] = 'Class Cancel';
            $this->marks_key['E'] = 'Left Early';
            $this->marks_key['F'] = '5 Minutes Late';
            $this->marks_key['G'] = '10 Minutes Late';
            $this->marks_key['K'] = '20 Minutes Late';
            $this->marks_key['L'] = 'Late';
            $this->marks_key['T'] = 'Tutorial';
            $this->marks_key['V'] = 'Visit/Work Experience';
            $this->marks_key['Z'] = 'Author Late';
            $this->marks_key['A'] = 'Author Absent';
            $this->marks_key['H'] = 'Holiday';
            $this->marks_key['S'] = 'Sick';
        }

        /**
         * start_timer
         *
         * Used for measuring script performance, starts a timer
         */
        public function start_timer() {
            // Performance monitoring
            $time = microtime();
            $time = explode(' ', $time);
            $time = $time[1] + $time[0];
            $this->timer_start = $time;
        }

        /**
         * stop_timer
         *
         * Used for measuring script performance, stops the timer: returns data
         */
        public function stop_timer() {
            $time = microtime();
            $time = explode(' ', $time);
            $time = $time[1] + $time[0];
            $finish = $time;
            $total_time = round(($finish - $this->timer_start), 4);
            return 'Page generated in '.$total_time.' seconds.'."\n";
        }
		
		public function getAcYear4Digit() {
			// Get four digit academic year code
            $cur_year = date('y');
            $next_year = sprintf('%2d', ($cur_year + 1));
            $ac_year = $cur_year . $next_year;
			return $ac_year;
		}


        /**
         * getTermDates
         *
         * @return - Returns indexed array containing term start and end dates or false if none found
         */
        public function getCurrentTermDates() {

            // Now we've got current ac_year code, look up term start/end dates
            $query = "SELECT term_code, term_start_date, term_end_date FROM mdl_terms WHERE ac_year_code = ".$this->academic_year_4digit."";
            if ($terms = get_records_sql($query)) {
                $current_terms = array();
                foreach($terms as $term) {
                   $current_terms[$term->term_code] = array('start' => $term->term_start_date, 'end' => $term->term_end_date); 
                }
                return $current_terms;
            } else {
                $this->errors[] = "Could not get term dates for this academic year: ".$this->academic_year_4digit."";
                return false;
            }

        }

		/** 
		* sortByDate
		* This sorts the attendace and punctuality module data by weekday, then start time
		*
		*/
		public function sortByDate($a, $b) {
			if ($a['day_num'] < $b['day_num']) return -1;
			if ($a['day_num'] > $b['day_num']) return 1;
			if ($a['start_time'] < $b['start_time']) return -1;
			if ($a['start_time'] > $b['start_time']) return 1;
			return 0;
		}
		
        /**
         * getDistinctModuleSlots
         *
         * @param     int    $student_id   external learner id
         * @param     int    $term         the term to get data from
         * @return    array  Returns distinct module slots with /01, /02 etc added to distinguish slots or false on fail
         */
        public function getDistinctModuleSlots($student_id, $term='') {
            if ($student_id != '' && is_numeric($term) && in_array($term, $this->valid_terms)) {
                $query = sprintf("SELECT 
                    DISTINCT REGISTER_ID, MODULE_CODE, MODULE_DESCRIPTION, REG_DAY, REG_DAY_NUM, START_TIME, END_TIME 
                    FROM FES.MOODLE_LEARNER_REGISTER 
                    WHERE STUDENT_ID = %d 
                    ORDER BY REGISTER_ID, REG_DAY_NUM, START_TIME", $student_id);

                if ($slots = $this->db->Execute($query)) {
                    $data = array();

                     if(!empty($slots)) {
                        while (!$slots->EOF) {
                            // Create slot name (MODULE_CODE + '/01' format)
                            $slot_num = 1;
                            $slot_code = $slots->fields['MODULE_CODE'] . '/' . sprintf("%02d", $slot_num);
                            while (array_key_exists($slot_code, $data)) {
                                ++$slot_num;
                                $slot_code = $slots->fields['MODULE_CODE'] . '/' . sprintf("%02d", $slot_num);
                            }
                            $data[$slot_code]['register_id'] = trim($slots->fields['REGISTER_ID']);
                            $data[$slot_code]['module_code'] = trim($slots->fields['MODULE_CODE']);
                            $data[$slot_code]['module_desc'] = trim($slots->fields['MODULE_DESCRIPTION']);
                            $data[$slot_code]['day'] = trim($slots->fields['REG_DAY']);
                            $data[$slot_code]['day_num'] = trim($slots->fields['REG_DAY_NUM']);
                            $data[$slot_code]['start_time'] = trim($slots->fields['START_TIME']);
                            $data[$slot_code]['end_time'] = trim($slots->fields['END_TIME']);
                            $slots->MoveNext();
                        }
                     }

                    if (count($data) > 0) {
						// Order the associative array by Day, Start
						uasort($data, array($this, 'sortByDate'));
                        return $data;
                    } else {
                        $this->errors[] = 'No timetable slots exist for this student';
                        return false;
                    }
                } else {
                    $this->errors[] = 'No timetable slots found for this user';
                }
            } else {
                $this->errors[] = 'Invalid student ID or term';
                return false;
            }
        }

        /**
         * handleBlanks
         *
         * Easy method to handle values, if blank leave blank, else return the value
         */
        private function handleBlanks($value='') {
            if ($value == '') {
                return "";
            } else {
                return $value;
            }
        }

        /**
         * getAttendancePunctuality
         *
         * @param     int    $student_id   external learner id
         * @param     int    $term         the term to get data from
         * @return    array  Returns array containing aggregate ATT & PUNCT data for each distinct slot
         */
        public function getAttendancePunctuality($student_id='', $term='') {
            if ($student_id != '' && is_numeric($term) && in_array($term, $this->valid_terms)) {
                // First build array of distinct timetable slots (per module, day, start, end)
                $slots = $this->getDistinctModuleSlots($student_id, $term);

                if (count($slots) > 0) {
                    // Get the rest of the details based on module, day, start, end
                    foreach ($slots as $key => $value) {

                    /* old way of getting data from term 1 only
                    AND ((TO_DATE(REGISTER_DATE, 'DD/MM/YYYY') - TO_DATE('01/01/1970','DD/MM/YYYY')) * (86400)) > %d 
                    AND ((TO_DATE(REGISTER_DATE, 'DD/MM/YYYY') - TO_DATE('01/01/1970','DD/MM/YYYY')) * (86400) < %d) 
                     */

                        $query = sprintf("SELECT 
                            SUM(POS_ATT) AS SESSIONS_PRESENT, 
                            (SUM(TOTAL) - SUM(POS_ATT)) AS SESSIONS_ABSENT, 
                            (SUM(POS_ATT) / SUM(TOTAL)) AS ATTENDANCE, 
                            (SUM(POS_PUNCT) / SUM(POS_ATT)) AS PUNCTUALITY, 
                            SUM(POS_PUNCT) AS SESSIONS_ON_TIME, 
                            (SUM(POS_ATT) - SUM(POS_PUNCT)) AS SESSIONS_LATE 
                                FROM FES.MOODLE_ATT_PUNCT_T 
                                WHERE STUDENT_ID = %d  
                                AND REGISTER_ID = '%d' 
								AND ACADEMIC_YEAR = '%s'",
                                $student_id,
                                $value['register_id'],
                                $this->academic_year
                            );

                        if ($att_punc = $this->execute_query($query)) {
                            $data = array();
                            foreach ($att_punc as $attpun) {
                                $slots[$key]['attendance'] = $this->handleBlanks($attpun->ATTENDANCE);
                                $slots[$key]['punctuality'] = $this->handleBlanks($attpun->PUNCTUALITY);
                                $slots[$key]['sessions_present'] = $this->handleBlanks($attpun->SESSIONS_PRESENT);
                                $slots[$key]['sessions_absent'] = $this->handleBlanks($attpun->SESSIONS_ABSENT);
                                $slots[$key]['sessions_on_time'] = $this->handleBlanks($attpun->SESSIONS_ON_TIME);
                                $slots[$key]['sessions_late'] = $this->handleBlanks($attpun->SESSIONS_LATE);
                            }
                        }
                    }

                    // Return the data, including the extra info
                    return $slots;

                } else {
                    $this->errors[] = 'No timetable slots found for this user';
                    return false;
                }
            } else {
                $this->errors[] = 'Invalid student or term id';
                return false;
            }
        }

        /**
         * getRegisterWeeks
         *
         * @param     int    $student_id   external learner id
         * @param     int    $term         the term to get data from
         * @return    array  Returns array containing week dates for the given term 
         *                   Gets term start date then adds 7 days to get the next week date
         */
        public function getRegisterWeeks($student_id='', $term='') {
            if ($terms = $this->getCurrentTermDates()) {

                $register_week_start = $terms[$term]['start'];
                $register_term_end = $terms[$term]['end'];

                $register_week_fmt = date('d/m/Y', $register_week_start);
                $reg_weeks = array();
                $reg_weeks[] = $register_week_fmt;

                // While the current register week is in the current term, add a week
                while($register_week_start < $register_term_end) {
                    $register_week_start += 604800; // add a week in seconds
                    $register_week_fmt = date('d/m/Y', $register_week_start); // format as dd/mm/yyyy
                    if ($register_week_start < $register_term_end) {
                        $reg_weeks[] = $register_week_fmt;
                    }
                }
                if (count($reg_weeks) > 0) {
                    return $reg_weeks;
                } else {
                    return false;
                }

            } else {
                $this->errors[] = 'Could not get current term dates';
                return false;
            }
        }
        
        /**
         * getMarkForModuleSlot
         *
         * @param     int     $student_id   external learner id
         * @param     int     $term         the term to get data from
         * @param     string  $register_id  the register_id to get mark from
         * @param     string  $date         the date to get mark from
         *
         * @return    boolean               If mark is found returns it else returns false
         */
        public function getMarkForModuleSlot($student_id='',$term='',$register_id='',$date='') {
            $query = sprintf("SELECT REG_MARK 
                FROM FES.MOODLE_LEARNER_REGISTER 
                WHERE STUDENT_ID = %d 
                AND REGISTER_ID = '%d' 
                AND REG_DATE = '%s' 
                AND REG_MARK IS NOT NULL", 
                $student_id, 
                $register_id,
                $date
            );
    
            if ($registers = $this->execute_query($query)) {
                $register_mark = '';
                foreach ($registers as $reg) {
                    $register_mark = $reg->REG_MARK;
                }
                if ($register_mark != '') {
                    return $register_mark;
                } else {
                    return false;
                }
            } else {
                return false;
            }
        }

         /**
         * Gets the average of the learner's attendance over the current year.
         *
         * @param string $learner_id The external id of the learner.
         * @return stdClass The result object.
         */
        public function get_attendance_avg($learner_id) {

            $query = sprintf("SELECT (SUM(POS_ATT) / SUM(TOTAL)) AS ATTENDANCE 
                FROM FES.MOODLE_ATT_PUNCT_T 
                WHERE STUDENT_ID = '%d' 
                AND ACADEMIC_YEAR = '%s'",
                $learner_id, 
                $this->academic_year
            );
			
            return array_pop($this->execute_query($query));
        }

        /**
         * Gets the average of the learner's punctuality over the current year.
         *
         * @param string $learner_id The external id of the learner.
         * @return stdClass The result object.
         */
        public function get_punctuality_avg($learner_id) {	
                   
            $query = sprintf("SELECT (SUM(POS_PUNCT) / SUM(POS_ATT)) AS PUNCTUALITY 
                FROM FES.MOODLE_ATT_PUNCT_T 
                WHERE STUDENT_ID = '%d' 
                AND ACADEMIC_YEAR = '%s'",
                $learner_id, 
                $this->academic_year
            );

            return array_pop($this->execute_query($query));
        }


        /**
         * getAttPuncData
         *
         * @param int $figure the 0.34 etc. value of passed attendance or punctuality
         * @return array array of att or punc values for display
         */
        public function getAttPuncData($figure='') {
			$figure = ($figure != '') ? round($figure * 100, 2) : '0';
			// Here comes the calculations
			$colour = '';
			if ($figure >= 91) {
				$colour = 'green';
			} else if ($figure >= 84 && $figure < 91) {
				$colour = 'amber';
			} else if ($figure < 84 && is_numeric($figure)) {
				$colour = 'red';
			}
			// Now return an array of attenace data to use
			$data = array();
			$data['decimal'] = $figure;
			$data['colour'] = $colour;
			$data['formatted'] = (is_numeric($figure)) ? $figure . '%' : '';
			return $data;
        }
		
		// $module_code = 'NV1MHAR1-1DA11A/FSM' (for example)
		public function getAttPuncForModule($learner_id, $module_code) {

			$query = sprintf("SELECT 
					(SUM(POS_ATT) / SUM(TOTAL)) AS ATTENDANCE,
					(CASE WHEN (SUM(POS_ATT)) = 0 THEN 0 ELSE (SUM(POS_PUNCT) / SUM(POS_ATT)) END) AS PUNCTUALITY 
					FROM FES.MOODLE_ATT_PUNCT_T 
					WHERE STUDENT_ID = '%d'
					AND MODULE_CODE = '%s'
					AND ACADEMIC_YEAR = '%s'",
					$learner_id,
					$module_code,
					$this->academic_year
			);
			
			if ($attpunc = $this->execute_query($query)) {
				 foreach ($attpunc as $attpun) {
					$attendance = $this->handleBlanks($attpun->ATTENDANCE);
					$punctuality = $this->handleBlanks($attpun->PUNCTUALITY);
				}
				$attendance = round($attendance * 100, 2);
				$punctuality = round($punctuality * 100, 2);
				
				$data = array('attendance' => $attendance, 'punctuality' => $punctuality);
				return $data;
				
			} else {
				$this->errors[] = 'No attendance or punctuality data for learner: '.$learner_id.' and module: '.$module_code.'';
				return false;
			}
			
		}
		
		// nkowald - 2011-10-13 - Adding list modules function here (called from inside subject targets)
		public function getModules($learner_id, $term=1) {
		
			$query = sprintf("SELECT DISTINCT MODULE_CODE, MODULE_DESC FROM FES.MOODLE_ATT_PUNCT_TEST WHERE STUDENT_ID = %d AND ACADEMIC_YEAR = '%s' ORDER BY MODULE_CODE ASC", 
			$learner_id,
			$this->academic_year
			);
			
			if ($modules = $this->execute_query($query)) {
				$learner_modules = array();
				$c = 0;
				foreach ($modules as $module) {
					$learner_modules[$c]['module_code'] = $module->MODULE_CODE;
					$learner_modules[$c]['module_desc'] = $module->MODULE_DESC;
					$c++;
				}
				// Now we have distinct module data, get and add attendance and punctuality to these figures
				foreach($learner_modules as $key => $value) {
					// Get attendance and punctuality data for the given module
					$apdata = $this->getAttPuncForModule($learner_id, $value['module_code']);
					if (is_array($apdata)) {
						$learner_modules[$key]['attendance'] = $apdata['attendance'];
						$learner_modules[$key]['punctuality'] = $apdata['punctuality'];
					} else {
						$this->errors[] = "No attendance and punctuality data for module ".$value['module_code'];
					}
				}
				return $learner_modules;
			} else {
				$this->errors[] = 'No modules found for learner: '.$learner_id;
				return false;
			}
		}


        /**
         * __call
         *
         * @return if a method is called that doesn't exist, throw exception with this message
         */
        public function __call($name, $arg) {
            throw new BadMethodCallException("Sorry, this method '$name' does not exist");
        }


        function __destruct() {
            if ($this->debug === true) {
                if (count($this->errors) > 0) {
                    echo '<div class="errors">';
                    echo '<h3>Errors:</h3>';
                    echo '<ul>';
                    foreach ($this->errors as $error) {
                        echo "<li>$error</li>";
                    }
                    echo '</ul>';
                    echo '</div>';
                }
            }
        }

    } // AttendancePunctuality

?>
