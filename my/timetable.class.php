<?php

    class Timetable {
        
        public $time_start;
        public $time_end;
        public $week_start;
        public $time_start_loop;
        public $td_count;
        public $tr_colspan;
        public $printed_to_edge;
        public $course_code_colours;
        public $module_code_colours;
        public $week_dates;
        public $current_week_no;
		public $ac_year_start_date;
		public $CFG;
        private $db;

        /**
         * Make connection to the MIS database.
         */
        public function __construct() {
            $this->time_start = 8;
            $this->time_end   = 22;
            $this->no_slots = ($this->time_end - $this->time_start) + 1;
            $this->printed_to_edge = FALSE;
            $this->course_code_colours = array();
            $this->module_code_colours = array();

            global $CFG;
			$this->CFG = $CFG;
            require_once ($CFG->dirroot.'/lib/adodb/adodb.inc.php'); // include the necessary DB library
            
            $this->db = NewADOConnection('oci8'); // set up the connection
            //$this->db->debug=true;
            if (!$this->db->Connect('ebs.conel.ac.uk', 'ebsmoodle', '82814710', 'fs1')) {
                error('Can\'t connect to EBS so can\'t display timetable data', $this->wwwroot .'/my/index.php');
            }
            $this->db->SetFetchMode(ADODB_FETCH_ASSOC);
			
			if ($start_date = $this->get_start_date_for_this_ac_year()) {
				$this->ac_year_start_date = $start_date;
			} else {
				$year = date('Y');
				$this->ac_year_start_date = mktime(0, 0, 0, 8, 1, $year, 0); // will revert to manual date if no record set in mdl_academic_years;
            }
			
        }

        /**
         * Private function to determine which academic year we are currently in.
         *
         */
        private function resolve_year() {
            $academicYearStart = strftime("%Y",strtotime("-8 months",time()));
            $academicYearEnd = strftime("%Y",strtotime("+4 months",time()));
            return "Academic Year $academicYearStart/$academicYearEnd";
        }
		
		private function get_start_date_for_this_ac_year() {
			// Get current academic year
			$ac_year = $this->resolve_year();
			$query = sprintf("SELECT TO_CHAR(START_DATE, 'dd/mm/yyyy') AS START_DATE_FORMATTED FROM FES.SESSIONS WHERE SESSION_LONG_DESC = '%s'", $ac_year);
					
			//print_r($query);
					
			if ($data = $this->execute_query($query)) {
				foreach ($data as $datum) {
					$start = $datum->START_DATE_FORMATTED;
				}
			} else {
				return false;
			}
			
			$d = explode('/', $start);
			$day = (substr($d[0], 0, 1) == 0) ? sprintf('%1d', $d[0]) : $d[0];
			$month = (substr($d[1], 0, 1) == 0) ? sprintf('%1d', $d[1]) : $d[1];
			$year = $d[2];
			$start_date = mktime(0,0,0,$month,$day,$year, 0);
			return $start_date;
		}

        /**
         * Private member function to execute queries and build a moodle style
         * array of objects.
         *
         * @param string $sql The sql string you wish to be executed.
         * @return array The array of objects returned from the query.
         */
        private function execute_query($sql) {
        
            // execute the query
            $result = $this->db->Execute($sql);
            // initialise the data array
            $data = array();
            // convert the resultset into a Moodle style object
            if(!empty($result)) {
                while (!$result->EOF) {
                    $obj = new stdClass;
                    foreach (array_keys($result->fields) as $key) {
                        $obj->{$key} = $result->fields[$key];
                    }
                    $index = reset($result->fields);
                    $data[$index] = $obj;
                    $result->MoveNext();
                }
            }

            // return an array of objects
            return $data;
        
        }

        private function getWeeksDropdown($id=0) {

            // Get current week if set
            $current_week = $this->current_week_no;
            $selected_week = (isset($_GET['week'])) ? $_GET['week'] : $current_week;

            $dropdown = "&nbsp;<form action=\"timetable.php\" name=\"week_changer\" method=\"get\">\n";
            $dropdown .= "<select name=\"week\" id=\"week_select\" onchange=\"javascript:this.form.submit();\">\n";

            // e.g. Starting date for this academic year = 02/08/2010, we can work out the rest by adding seven days onto this 52 times
			$timestamp = $this->ac_year_start_date;
            $day_counter = 0;

            // 52 weeks in an academic year
            for ($i=1; $i<=52; $i++) {

                $week_date = date('d M Y', strtotime('+'.$day_counter.' days', $timestamp));
                //$week_date .= ($current_week == $i) ? " ($i) *" : " ($i)";
                $week_date .= ($current_week == $i) ? " *" : "";

                if ($i == $selected_week) { 
                    $dropdown .= "\t<option value=\"$i\" selected=\"selected\">$week_date</option>\n"; 
                } else {
                    $dropdown .= "\t<option value=\"$i\">$week_date</option>\n"; 
                }

                $day_counter += 7;
            }
            $dropdown .= "</select>\n</form>\n";
            return $dropdown;
        }

        private function setUpCourseColours($id=0, $week_no=0) {
            if ($id != 0 && $week_no != 0) {
                $query = sprintf("SELECT MODULE_CODE, COURSE_CODE, OCC_CODE
                        FROM FES.MOODLE_STUDENT_TIMETABLE
                        WHERE PERSON_CODE = '%d' AND WEEK_NO = '%d' ORDER BY START_TIME", $id, $week_no);
						
				//print_r($query);
						
                if ($data = $this->execute_query($query)) {
                    foreach ($data as $datum) {
                        $module_code = $datum->MODULE_CODE;
                        $no_colours_mc = count($this->module_code_colours);
                        $no_colours_mc++;

                        $course_code = $datum->COURSE_CODE . "_" . $datum->OCC_CODE;
                        $no_colours_cc = count($this->course_code_colours);
                        $no_colours_cc++;

                        if (!in_array($course_code, $this->course_code_colours)) {
                            $this->course_code_colours[$course_code] = 'course' . $no_colours_cc;
                        }
                        if (!in_array($module_code, $this->module_code_colours)) {
                            $this->module_code_colours[$module_code] = 'course' . $no_colours_mc;
                        }
                    }
                } else {
                    return false;
                    $error = "No records found for the given parameters";
                }
            }
        }

        public function getDayDetails($learner_id=0, $week_no=0, $day_no=0) {
            if ($learner_id != 0 && $week_no != 0 && $day_no != 0) {

                $query = sprintf("SELECT START_TIME, END_TIME, EVENT_DESC, MODULE_CODE, ROOM, ROOM_LOCATION, COURSE_CODE, OCC_CODE, COURSE_TITLE 
                    FROM FES.MOODLE_STUDENT_TIMETABLE 
                    WHERE PERSON_CODE = '%d' AND WEEK_NO = '%d' AND DAY_NO = '%d' ORDER BY START_TIME", $learner_id, $week_no, $day_no);
                // Do query and return formatted details

                $data = $this->execute_query($query);

                // Create an array that holds 
                if ($data) {

                    foreach ($data as $datum) {
                        
                        // format start date
                        $start = str_replace(':','', substr($datum->START_TIME, 0, -3));
                        $start_det = substr($datum->START_TIME, 0, -3);
                        $end = str_replace(':','', substr($datum->END_TIME, 0, -3));
                        $end_det = substr($datum->END_TIME, 0, -3);
                        $course_code = $datum->COURSE_CODE . "_" . $datum->OCC_CODE;
                        $module_code = $datum->MODULE_CODE;

                        // Because this is inside Moodle and we can, lets link to the course the timetable slot's for
                        $link_url = '';
                        if ($course = get_record('course', 'idnumber', $course_code)) {
                            $course_id = $course->id;
                            $link_url = $this->CFG->wwwroot . '/course/view.php?id=' . $course_id;
                        }
                        $course_html = ($link_url != '') ? '<a href="'.$link_url.'" target="_blank" title="Open course">'.$course_code.'</a>' : $course_code;
                        $details = $course_html . "<br />" . $datum->COURSE_TITLE . "<br />" . $datum->EVENT_DESC . "<br /><span>". $start_det . " - " . $end_det . "</span><br />" . $datum->ROOM . " (" . $datum->ROOM_LOCATION . ")";
                        $arr_name = "day_" . $day_no;
                        ${$arr_name}[] = array('start' => $start, 'end' => $end, 'details' => $details, 'course_code' => $course_code, 'module_code' => $module_code );

                    }

                    return $$arr_name;

                } else {
                    return false;
                }

            } else {
                return false;
            }
        }

        // Including the ID of the logged in student in the Oracle query decreases query time significantly
        public function getThisWeekNo($id=0) {

            $timestamp = time();
            // Get date of monday, this week
            $day_of_week = date('N', $timestamp);
            $days_ago = "-" . ($day_of_week - 1) . " days"; 
            $monday_date = date('d/m/Y', strtotime($days_ago)); 
 
            $timestamp = $this->ac_year_start_date;
			$day_counter = 0;

            // 52 weeks in an academic year
            $week_dates = array();
            for ($i=1; $i<=52; $i++) {
                $week_dates[$i] = date('d/m/Y', strtotime('+'.$day_counter.' days', $timestamp));
                $day_counter += 7;
            }

            // Return week number
            $cur_week_found = array_search($monday_date, $week_dates);
            return $cur_week_found; // will return false if not found

        }

        public function printHeader($id=0, $week_no=0) {

            // TODO: Sanitise input

            $curr_week_no = $this->current_week_no;

            // Get Name from id
            if ($week_no == 0) {
                $week_no = $curr_week_no;
            }

            if ($user = get_record('user', 'idnumber', $id)) {
                $name = $user->firstname . " " . $user->lastname;
            }

            $week_dd = $this->getWeeksDropdown($id);

            // Query to find some details we'll use for the header
            $begin = '';
            $year = '';

			$year = $this->resolve_year();
			$year = str_ireplace('Academic Year ', '', $year);
			$time_wk = $week_no - 1;
			$begin = strtotime("+$time_wk week", $this->ac_year_start_date);
			$begin = date('d/m/Y', $begin);
			
			/*
			switch ($week_no) {
                case $curr_week_no:
                    $week_text = $week_no . " (this week)";
                    break;
                case ($curr_week_no + 1):
                   $week_text = $week_no . " (next week)";
                   break;
                case ($curr_week_no - 1):
                   $week_text = $week_no . " (last week)";
                   break;
                default:
                    $week_text = $week_no;
            }
			*/
			switch ($week_no) {
                case $curr_week_no:
                    $week_text = $begin . " (this week)";
                    break;
                case ($curr_week_no + 1):
                   $week_text = $begin . " (next week)";
                   break;
                case ($curr_week_no - 1):
                   $week_text = $begin . " (last week)";
                   break;
                default:
                    $week_text = $begin;
            }

            // Timestamp from commencing date
            $split_dates = explode('/', $begin);
            $dd = $split_dates[0];
            $mm = $split_dates[1];
            $Y = $split_dates[2];
            $timestamp = mktime(0, 0, 0, $mm, $dd, $Y, 0);

            // Generate an array of week dates from the week commence date
            $this->week_dates = array(
                1 => date('d/m/Y', $timestamp),
                2 => date('d/m/Y', strtotime('+1 days', $timestamp)),
                3 => date('d/m/Y', strtotime('+2 days', $timestamp)),
                4 => date('d/m/Y', strtotime('+3 days', $timestamp)),
                5 => date('d/m/Y', strtotime('+4 days', $timestamp)),
                6 => date('d/m/Y', strtotime('+5 days', $timestamp)),
                7 => date('d/m/Y', strtotime('+6 days', $timestamp))
            );
            
            // Now add previous / next link
            $prev_link = $this->CFG->wwwroot . '/my/timetable.php?week=' . ($week_no - 1);
            $next_link = $this->CFG->wwwroot . '/my/timetable.php?week=' . ($week_no + 1);
            //$table_html = '<h2>'.$name.' &ndash; Week: <span>'.$week_text . $week_dd .'</span>&nbsp; Begin: <span>'.$begin.'</span>&nbsp; Academic Year: <span>'.$year.'</span></h2>';
            $table_html = '<h2>'.$name.' &ndash; Week Beginning: <span>'.$week_text . $week_dd .'</span>&nbsp; Academic Year: <span>'.$year.'</span></h2>';
            $table_html .= "\n";
            $table_html .= '<div id="timetable_navigation"><div id="time_week_prev">';
            $table_html .= ($week_no - 1 != 0) ? '<a href="'.$prev_link.'">&lt; Previous Week</a>' : '&nbsp';
            $table_html .= '</div><div id="time_week_next">';
            $table_html .= ($week_no + 1 != 53) ? '<a href="'.$next_link.'">Next Week &gt;</a>' : '&nbsp;';
            $table_html .= "</div><br class=\"clear_both\" /></div>\n";
            return $table_html;

        }

        public function showTimetable($id=0, $week_no=0) {

            $this->current_week_no = $this->getThisWeekNo($id);
            if ($week_no == 0) {
                $week_no = $this->current_week_no;
            }
            $days_of_week = array(
                1 => 'Monday',
                2 => 'Tuesday',
                3 => 'Wednesday',
                4 => 'Thursday',
                5 => 'Friday',
                6 => 'Saturday',
                7 => 'Sunday'
            );

            // Get week no
            $this->setUpCourseColours($id, $week_no);

            $table_html .= $this->printHeader($id, $week_no);
            $table_html .= '<table id="timetable_tt" cellpadding="1" cellspacing="0" width="100%"><thead><tr><td width="75">&nbsp;</td>';
            $table_html .= "\n";
            $time_start = $this->time_start;
            while ($time_start <= $this->time_end) {
                // Add zero to start if < 10
                $time_start = ($time_start < 10) ? sprintf('%02d', $time_start) : $time_start;
                $table_html .= "<td colspan=\"60\">".$time_start."00</td>\n";
                $time_start++;
            }
            $table_html .= "</tr></thead>\n";

            // format start time as 800
            foreach ($days_of_week as $day_no => $day) {

                $this->td_count = 1;
                $this->time_start_loop = sprintf('%d00', $this->time_start);
                $this->tr_colspan = 0;
                $this->printed_to_edge = FALSE;
                // today's date
                $today = date('d/m/Y');
                $current = ($today == $this->week_dates[$day_no]) ? ' class="current"' : '';
                $table_html .= "<tr$current>\n<td class=\"day\">".ucfirst($day)."<br /><span>".$this->week_dates[$day_no]."</span></td>\n";

                if ($data = $this->getDayDetails($id, $week_no, $day_no)) {

                    $array_counter = 0;
                    $this->time_end = (strlen($this->time_end) == 2) ? $this->time_end . "00" : $this->time_end;

                    for ($i=1; $i <= $this->no_slots; $i++) {
                        // Each hour slot is divided into its 15 min parts
                        $colspan = 0;

                        // format $time_start as 0800 (if less than 10)
                        $this->time_start_loop = ($this->time_start_loop < 1000 && strlen($this->time_start_loop) == 3) ? 0 . $this->time_start_loop : $this->time_start_loop;

                        if ($this->time_start_loop <= $this->time_end) {

                            // Slot time is the 'start' time for the current slot
                            $slot_time = $data[$array_counter]['start'];

                            // Code in here can only be non-timeslot cells, so should print a cell if reaches 60 colspan (1 hour)
                            while ($this->time_start_loop < $slot_time) {
                                $colspan += 15;
                                $hour = substr($this->time_start_loop, 0, 2);
                                $minute = substr($this->time_start_loop, 2, 2);
                                $minute += 15;
                                if ($minute == 60) {
                                    $minute = "00";
                                    $hour++;
                                    $hour = (strlen($hour) == 1) ? 0 . $hour : $hour;
                                }
                                $this->time_start_loop = $hour . $minute;
                                if ($colspan == 60 || (substr($this->time_start_loop, -2) == 00)) {
                                    $table_html .= "<td colspan=\"$colspan\">&nbsp;</td>\n";
                                    $this->td_count++;
                                    $this->tr_colspan += $colspan;
                                    $colspan = 0;
                                } else if ($this->time_start_loop == $data[$array_counter]['start']) {
                                    $table_html .= "<td colspan=\"$colspan\">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td>\n";
                                    $this->tr_colspan += $colspan;
                                    $colspan = 0;
                                    $this->td_count++;
                                }
                            }

                            // Slot time equals current time in loop
                            if ($this->time_start_loop == $slot_time) {
                                // Need to work out colspan value
                                $colspan = 0; // reset colspan
                                $start = $slot_time;
                                $end = $data[$array_counter]['end']; 
                                while ($start < $end) {
                                    $colspan += 15; 
                                    $hour = substr($start, 0, 2);
                                    $minute = substr($start, 2, 2);
                                    $minute += 15;
                                    if ($minute == 60) {
                                        $minute = "00";
                                        $hour++;
                                        $hour = (strlen($hour) == 1) ? 0 . $hour : $hour;
                                    }
                                    $start = $hour . $minute;
                                }
                                // Now we know colspan: print td
                                // get classname from course code or module_code
                                //$course_code = $data[$array_counter]['course_code'];
                                $module_code = $data[$array_counter]['module_code'];
                                //$td_class = (isset($this->course_code_colours[$course_code])) ? " " . $this->course_code_colours[$course_code] : '';
                                $td_class = (isset($this->module_code_colours[$module_code])) ? " " . $this->module_code_colours[$module_code] : '';
                                $table_html .= "<td colspan=\"$colspan\" class=\"details$td_class\">".$data[$array_counter]['details']."</td>\n";
                                $this->tr_colspan += $colspan;
                                $this->td_count++;
                                $array_counter++; // use next timeslot next time
                                $this->time_start_loop = $end;
                            } else {
                                // Current point in loop has no more timeslots so increase until tds filled
                                $colspan = 0;
                                while ($colspan < 60) {
                                    $hour = substr($this->time_start_loop, 0, 2);
                                    $minute = substr($this->time_start_loop, 2, 2);
                                    $minute += 15;
                                    if ($minute == 60) {
                                        $minute = "00";
                                        $hour++;
                                        $hour = (strlen($hour) == 1) ? 0 . $hour : $hour;
                                    }
                                    $this->time_start_loop = $hour . $minute;
                                    $colspan += 15;
                                    if ((substr($this->time_start_loop, -2) == 00) && ($this->td_count <= $this->no_slots) && $this->printed_to_edge == FALSE) {
                                        $table_html .= "<td colspan=\"$colspan\">&nbsp;</td>\n";
                                        $this->tr_colspan += $colspan;
                                        $this->td_count++;
                                        $this->printed_to_edge = TRUE;
                                        $colspan = 0;
                                    }
                                    if ((substr($this->time_start_loop, -2) == 00) && ($this->td_count <= ($this->no_slots + 1)) && $this->printed_to_edge == TRUE) {
                                        if ($this->tr_colspan < ($this->no_slots * 60)) {
                                            $table_html .= "<td colspan=\"60\">&nbsp;</td>\n";
                                            $this->tr_colspan += 60;
                                            $this->td_count++;
                                            $colspan = 0;
                                        }
                                    }
                                }
                            }

                        }

                    } // for

                } else {
                    for ($j = 1; $j <= $this->no_slots; $j++) {
                        $table_html .= "<td colspan=\"60\">&nbsp;</td>\n";
                        $this->tr_colspan += 60;
                        $this->td_count++;
                    }
                }

                $table_html .= "</tr>\n";
            }
                $table_html .= "</table>\n";
                echo $table_html;

        }

    }

?>
