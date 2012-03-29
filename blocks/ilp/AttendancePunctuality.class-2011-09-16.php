<?php

    /**
     * AttendancePunctuality Class
     *
     * @author Nathan Kowald <NKowald@staff.conel.ac.uk>
     * @version 1.0
     * @description Talks to EBS and gets attendance and punctuality info for Moodle
     *
     */

    // let's use the execute_query method ULCC created
    require_once('../lpr/models/block_lpr_conel_mis_db.php');

    class AttendancePunctuality extends block_lpr_conel_mis_db {

        public $valid_terms;
        public $errors;
        public $timer_start;
        public $marks_key;
        
        function __construct() {

            // call parent constructor to set up db
            parent::__construct();

            // Make sure config is included so we can use MDL database functions
            include_once('../../config.php');

            $this->valid_terms = array(1, 2, 3);
            $this->errors = array();
            $this->timer_start = '';
            // Start the timer as soon as the class is instantiated
            $this->start_timer();

            $this->marks_key['/'] = 'Present';
            $this->marks_key['O'] = 'Absent';
            $this->marks_key['C'] = 'Class Cancel';
            $this->marks_key['E'] = 'Left Early';
            $this->marks_key['F'] = '5 Minutes Early';
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

        public function start_timer() {
            // Performance monitoring
            $time = microtime();
            $time = explode(' ', $time);
            $time = $time[1] + $time[0];
            $this->timer_start = $time;
        }

        public function stop_timer() {
            $time = microtime();
            $time = explode(' ', $time);
            $time = $time[1] + $time[0];
            $finish = $time;
            $total_time = round(($finish - $this->timer_start), 4);
            return 'Page generated in '.$total_time.' seconds.'."\n";
        }


        /**
         * getTermDates
         *
         * @return - Returns indexed array containing term start and end dates or false if none found
         */
        public function getCurrentTermDates() {

            // Get four digit academic year code
            $cur_year = date('y');
            $next_year = sprintf('%2d', ($cur_year + 1));
            $ac_year = $cur_year . $next_year;

            // Now we've got current ac_year code, look up term start/end dates
            $query = "SELECT term_code, term_start_date, term_end_date FROM mdl_terms WHERE ac_year_code = $ac_year";
            if ($terms = get_records_sql($query)) {
                $current_terms = array();
                foreach($terms as $term) {
                   $current_terms[$term->term_code] = array('start' => $term->term_start_date, 'end' => $term->term_end_date); 
                }
                return $current_terms;
            } else {
                return false;
            }

        }

        public function getDistinctModuleSlots($student_id, $term='') {
            if ($student_id != '' && is_numeric($term) && in_array($term, $this->valid_terms)) {
                $query = sprintf("SELECT 
                    DISTINCT MODULE_CODE, MODULE_DESCRIPTION, REG_DAY, REG_DAY_NUM, START_TIME, END_TIME 
                    FROM FES.MOODLE_LEARNER_REGISTER 
                    WHERE STUDENT_ID = %d 
                    ORDER BY REG_DAY_NUM, START_TIME", $student_id);

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
                        return $data;
                    } else {
                        $this->errors[] = 'No timetable slots exist for this student';
                        return false;
                    }
                }
            } else {
                $this->errors[] = 'Invalid student ID or term';
                return false;
            }
        }

        private function handleBlanks($value='') {
            if ($value == '') {
                return "";
            } else {
                return $value;
            }
        }

        public function getAttendancePunctuality($student_id='', $term='') {
            if ($student_id != '' && is_numeric($term) && in_array($term, $this->valid_terms)) {
                // First build array of distinct timetable slots (per module, day, start, end)
                $slots = $this->getDistinctModuleSlots($student_id, $term);

                if (count($slots) > 0) {
                    // Get the rest of the details based on module, day, start, end
                    foreach ($slots as $key => $value) {

                    /*
                    $query = sprintf("SELECT 
                        MODULE_CODE, 
                        MODULE_DESC, 
                        SUM(POS_ATT) AS SESSIONS_PRESENT, 
                        SUM(TOTAL) - SUM(POS_ATT) AS SESSIONS_ABSENT, 
                        SUM(POS_ATT)/SUM(TOTAL) AS ATTENDANCE, 
                        SUM(POS_PUNCT)/SUM(POS_ATT) AS PUNCTUALITY, 
                        SUM(POS_PUNCT) AS SESSIONS_ON_TIME, 
                        SUM(POS_ATT) - SUM(POS_PUNCT) AS SESSIONS_LATE 
                            FROM FES.MOODLE_ATT_PUNCT_T 
                            WHERE STUDENT_ID = %d 
                            AND ((TO_DATE(REGISTER_DATE, 'DD/MM/YYYY') - TO_DATE('01/01/1970','DD/MM/YYYY')) * (86400)) > %d 
                            AND ((TO_DATE(REGISTER_DATE, 'DD/MM/YYYY') - TO_DATE('01/01/1970','DD/MM/YYYY')) * (86400) < %d) 
                            GROUP BY MODULE_CODE, MODULE_DESC", 

                            $student_id, $term_start, $term_end);
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
                                AND MODULE_CODE = '%s'
                                AND REPLACE(TT_DAY, ' ', '') = '%s' 
                                AND START_TIME = '%s' 
                                AND END_TIME = '%s'", 
                                $student_id,
                                $value['module_code'], 
                                $value['day'], 
                                $value['start_time'], 
                                $value['end_time'] 
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
                }
            }
        }

        public function getRegisterWeeks($student_id='', $term='') {
            $terms = $this->getCurrentTermDates();
            $register_week_start = $terms[$term]['start'];
            $register_term_end = $terms[$term]['end'];

            $register_week_fmt = date('d/m/Y', $register_week_start);
            $reg_weeks = array();
            $reg_weeks[] = $register_week_fmt;

            while($register_week_start < $register_term_end) {
                $register_week_start += 604800;
                // format as dd/mm/yyyy
                $register_week_fmt = date('d/m/Y', $register_week_start);
                if ($register_week_start < $register_term_end) {
                    $reg_weeks[] = $register_week_fmt;
                }
            }
            if (count($reg_weeks) > 0) {
                return $reg_weeks;
            } else {
                return false;
            }
        }
        
        public function getMarkForModuleSlot($student_id='',$term='',$module_code='',$date='', $start='', $end='') {
            $query = sprintf("SELECT REG_MARK 
                FROM FES.MOODLE_LEARNER_REGISTER 
                WHERE STUDENT_ID = %d 
                AND MODULE_CODE = '%s' 
                AND REG_DATE = '%s' 
                AND START_TIME = '%s' 
                AND END_TIME = '%s' 
                AND REG_MARK IS NOT NULL", 
                $student_id, 
                $module_code,
                $date,
                $start,
                $end
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
            }
        }

        public function __call($name, $arg) {
            throw new BadMethodCallException("Sorry, this method '$name' does not exist");
        }


        function __destruct() {

        }

    } // AttendancePunctuality

?>
