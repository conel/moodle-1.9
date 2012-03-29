<?php 
    
    /**
     *  Class validate_user
     *
     *  Used to validate students who don't have Student IDs (idnumber) 
     *  against their user record in Moodle.
     *
     *  @author nkowald
     *  @since 2010-03-11
     *  @version 0.1
     *
     **/

    class validate_user {
        
        // holds... errors
        public $errors;


        /**
         *  __construct
         *
         *  Standard PHP5 constructor
         *  Calls this method on each newly-created object, 
         *  is suitable for any initialisation that the object may need before used
         *
         **/
        public function __construct() {

            // include needed Moodle files - just in case
            include_once('../../config.php');
            include_once('../../course/lib.php');
            include_once('../../lib/adodb/adodb.inc.php');

            // set up property for holding error messages
            $this->errors = array();

        }


        /**
         *  validate_is_student
         *
         *  Works out if the current logged in Moodle user is a student
         *  Works with the logic that an admin may have a student role but not the other way round
         *  A student is a Moodle user without any role assignments for 1,2,3,4,6,8,10,11 - check mdl_role for associated role names
         *
         *  @param     object    $USER object
         *  @return    boolean
         *
         **/
        public function validate_is_student($user_obj) {

            $is_student = FALSE;
            // Check what role current logged in user has
            $sql = "SELECT DISTINCT roleid FROM mdl_role_assignments where userid = ".$user_obj->id."";
            if ($user_roles = get_records_sql($sql)) {
                $ass_roles = array(); // you've a dirty mind
                foreach($user_roles as $role) {
                    $ass_roles[] = $role->roleid;
                }
                // list of roles user should NOT have if they're a student (logic: admin may have student role but not other way round)
                $not_students = array(1,2,3,4,6,8,10,11);
                foreach($ass_roles as $ass) {
                    if (!in_array($ass, $not_students)) {
                        $is_student = TRUE;
                    }
                }
            }
            return $is_student;

        } // validate_is_student()


        /**
         *  validate_check_user_id_exists
         *
         *  Check the logged in user has a valid userid (student ID) saved in their Moodle user record
         *
         *  @param     object    $USER object
         *  @return    boolean
         *
         **/
        public function validate_check_user_id_exists($user_obj='') {

            $this->errors = array();
            if (is_object($user_obj)) {
                // Get all dist
                $query = "SELECT * FROM mdl_user WHERE id = $user_obj->id";
                if ($result = get_records_sql($query)) {
                    // Will only ever return one record
                    foreach($result as $user) {
                        $useridnumber = $user->idnumber;
                    }
                }
                // Validate the found ID number against logged in user
                if ($this->validate_validate_user_id($useridnumber, $user_obj->firstname, $user_obj->lastname)) {
                    return TRUE;
                } else {
                    return FALSE;
                }
            } else {
                return FALSE;
            }

        } // validate_check_user_id_exists()


        /**
         *  validate_validate_user_id
         *
         *  For the entered user_id: verify it exists in EBS and returned result 
         *  matches on firstname and lastname values
         *
         *  Note: Uses PHP levenshtein comparison func. to get around spelling mistakes
         *  as I've seen names differ by a letter from EBS to Moodle user 
         *  (currently allows for one letter change in each firstname, lastname)
         *
         *  @param     int      $user_id    User's entered ID (student ID)
         *  @param     string   $firstname  User's firstname (passed from USER object)
         *  @param     string   $lastname   User's lastname  (passed from USER object)
         *
         *  @return    boolean
         *
         **/
        public function validate_validate_user_id($user_id='', $firstname='', $lastname='') {

            $this->errors = array();

            if (!is_numeric($user_id)) {
                if (!in_array("ID not a valid number",$this->errors)) {
                    $this->errors[] = "ID not a valid number";
                }
                return FALSE;
            }

            $mis = &ADONewConnection('oci8');
            $mis->Connect('ebs.conel.ac.uk', 'quis', 'shazam', 'fs1');
            $mis->SetFetchMode(ADODB_FETCH_ASSOC);
            $result = $mis->SelectLimit("SELECT PERSON_CODE, FORENAME, SURNAME FROM QUIS.CONEL_AGENT_ENROLMENT_LIST WHERE PERSON_CODE = $user_id ORDER BY AGE DESC",1,0);

            if (!$result) {
                //echo "<!--". $mis->ErrorMsg(). "-->"; // Displays the error message if no results could be returned
                return FALSE;
                $this->errors[] = "ID number does not exist";
            } else {
                
                if (isset($result->_array[0])) {
                    $p_code = $result->_array[0]['PERSON_CODE'];
                    $f_name = $result->_array[0]['FORENAME'];
                    $l_name = $result->_array[0]['SURNAME'];

                    $valid = TRUE;

                    if (strtolower($firstname) == strtolower($f_name)) {
                        $valid = TRUE;
                    } else {
                        $fn_val = levenshtein(strtolower($firstname), strtolower($f_name));
                    }

                    if (strtolower($lastname) == strtolower($l_name)) {
                        $valid = TRUE;
                    } else {
                        $ln_val = levenshtein(strtolower($lastname), strtolower($l_name));
                    }

                    // If levenshtein val exists for a name comparison
                    if (isset($fn_val) && isset($ln_val)) {
                        if ($fn_val <= 1 && $ln_val <= 1) {
                            $valid = TRUE;
                        } else {
                            $this->errors[] = "Your name, <b>$firstname $lastname</b> differs from the name of the student ID entered";
                            $valid = FALSE;
                        }
                    }

                    return $valid;

                } else {
                    $this->errors[] = "This is not a student ID";
                    return FALSE;
                }

            }

        } // validate_validate_user_id()


        /**
         *  validate_get_age
         *
         *  Calculate the age from a given birth date
         *  Example: validate_get_age("25/12/1982");
         *
         *  @param     string   $birthdate (dd/mm/yyyy format)
         *  @return    string   $year_diff (age)
         *
         **/
        public function validate_get_age($birthdate) {

            // Explode the date into meaningful variables
            list($birth_day, $birth_month, $birth_year) = explode("/", $birthdate);

            // Find the differences
            $day_diff = date("d") - $birth_day;
            $month_diff = date("m") - $birth_month;
            $year_diff = date("Y") - $birth_year;

            // If the birthday has not occured this year
            if ($day_diff < 0 || $month_diff < 0)
            $year_diff--;

            return $year_diff;

        } // validate_get_age()


        /**
         *  validate_update_user
         *
         *  When we are certain entered userid is owned by logged in user:
         *  update Moodle user's idnumber field so we can later run EBS queries with it
         *
         *  @param     int      $moodle_user_id   Moodle user's ID - used to update record
         *  @param     int      $valid_id         User's entered student ID - used to update record
         *  @return    boolean
         *
         **/
        public function validate_update_user_id($moodle_user_id = '', $valid_id = '') {

            if ($result = set_field('user','idnumber',$valid_id,'id',$moodle_user_id)) {
                return TRUE;
            } else {
                $this->errors[] = "Failed to update your student ID in Moodle";
                return FALSE;
            }

        } //validate_update_user_id()


        /**
         *  validate_get_user_details
         *
         *  Gets the needed user details from EBS
         *
         *  @param     int    $response_id    - response id of the student
         *  @return    array  - array of retrieved user details
         *
         **/
        public function validate_get_user_details($response_id='') {

            // Get username from Moodle based on response id.
            // Must be a valid number
            if (!is_numeric($response_id)) {
                return FALSE;
            }
            $id_of_user = '';
            $query = "SELECT username from mdl_questionnaire_response WHERE id=$response_id";
            if ($username = get_records_sql($query)) {
                foreach($username as $cur_user) {
                    // Should only return one entry due to selecting on id
                    $id_of_user = $cur_user->username;
                } 
            }
            if ($id_of_user != '') {
                // get person_code from moodle
                $query = "SELECT idnumber from mdl_user WHERE id=$id_of_user";
                if ($id_numbers = get_records_sql($query)) {
                    foreach($id_numbers as $id_num) {
                        // Should only return one entry due to selecting on id
                        $person_code = $id_num->idnumber;
                    } 
                }
            } else {
                return FALSE;
            }

            $mis = &ADONewConnection('oci8');
            $mis->Connect('ebs.conel.ac.uk', 'quis', 'shazam', 'fs1');
            $mis->SetFetchMode(ADODB_FETCH_ASSOC);
            $result = $mis->SelectLimit("SELECT PROGRESS_STATUS, TITLE, FORENAME, SURNAME, SEX, DATE_OF_BIRTH, FULL_POST_CODE, ETHNICITY_DESC, SCHOOL_NAME, COURSE_DESCRIPTION, SECTOR, DISABILITY_DESC, LEARNING_DIFF_DESC FROM QUIS.CONEL_AGENT_ENROLMENT_LIST WHERE PERSON_CODE = $person_code ORDER BY AGE DESC",100,0);
            //$result = $mis->SelectLimit("SELECT * FROM QUIS.CONEL_AGENT_ENROLMENT_LIST WHERE PERSON_CODE = $person_code ORDER BY AGE DESC",100,0);

            if (!$result) {

                //echo "<!--". $mis->ErrorMsg(). "-->"; // Displays the error message if no results could be returned
                return FALSE;
                $this->errors[] = "User does not exist";

            } else {
                
                $user_details = array();
                while(!$result->EOF) {
                    foreach(array_keys($result->fields) as $key) {
                       $user_details[$key][] = $result->fields[$key]; 
                    }
                    $result->MoveNext();
                }

                // show all user details untouched
                /*
                echo '<pre>';
                var_dump($user_details);
                echo '</pre>';
                */

                $i = 0;
                foreach($user_details as $key => $value) {
                    if ($key == 'DATE_OF_BIRTH') {
                        // Create a new array item to hold age
                        $details['AGE'] = $this->validate_get_age($value[0]);
                    }
                    // Don't touch these items - we will cull by 'PROGRESS_STATUS' later on
                    if ($key == 'COURSE_DESCRIPTION' || $key == 'SECTOR' || $key == 'PROGRESS_STATUS') {
                        $details[$key] = $value;
                    } else {
                        $details[$key] = array_unique($value); // Only add unique items
                        $details[$key] = (count($details[$key]) == 1) ? $details[$key][0] : $details[$key]; // If one item added, make into a string
                    }
                    // If student not active on this course - don't add course or sector to array
                    $i++;
                }

                // Remove courses and sectors from user details if user is not actively enrolled against it
                $n = 0;
                foreach($details['PROGRESS_STATUS'] as $status) {
                    // if not active: remove course and sector from details array
                    if ($status != 'A') {
                       unset($details['COURSE_DESCRIPTION'][$n]); 
                       unset($details['SECTOR'][$n]); 
                    }
                    $n++;
                }

                // Make details hold unique courses and sectors only
                $details['COURSE_DESCRIPTION'] = array_unique($details['COURSE_DESCRIPTION']);
                $details['SECTOR'] = array_unique($details['SECTOR']);

                // Remove a few items from the details array;
                unset($details['PROGRESS_STATUS']);
                unset($details['DATE_OF_BIRTH']);

                // show touched details
                /*
                echo '<pre>';
                var_dump($details);
                echo '</pre>';
                */

                return $details;

            }
        } // validate_get_user_details()

        
        /**
         *  validate_get_user_details_as_csv_values
         *
         *  Gets user details as CSV values
         *
         *  @param     array  - user details
         *  @return    array  - values in CSV format 
         *
         **/
        public function validate_get_user_details_as_csv_values($details) {
            // Details needs to be an array
            if (!is_array($details)) {
                $this->errors[] = 'User details needs to be an array';
                return FALSE;
            } else {
                $values_as_csv = array();
                foreach($details as $key => $value) {
                    if (is_array($value)) {
                        $new_value = implode(', ', $value); 
                        $values_as_csv[$key] = $new_value;
                    } else {
                        $values_as_csv[$key] = $value;
                    }
                }
                return $values_as_csv;
            }
        } // validate_get_user_details_as_csv_values


        /**
         *  validate_print_user_details
         *
         *  Prints the user details, formatted in a table
         *
         *  @param     array  - user details
         *  @return    echo   - echos table html
         *
         **/
        public function validate_print_user_details($user_details) {
            if (!is_array($user_details)) {
                return FALSE;
                $this->errors[] = 'User Details need to be passed as an array';
            } else {
                echo '<table border="1" cellpadding="4" cellspacing="0">';
                echo '<thead>';
                echo '<tr>';
                foreach ($user_details as $key => $value) {
                    $key = ucwords(strtolower(str_replace('_',' ',$key)));
                    echo "<td><b>$key</b></td>"; 
                }
                echo '</tr>';
                echo '</thead>';
                echo '<tbody>';
                echo '<tr>';
                foreach ($user_details as $key => $value) {
                    echo "<td>";
                    if (is_array($value)) {
                        if (count($value) > 0) {
                            echo "<ul>";
                            foreach($value as $val) {
                                echo "<li>$val</li>";
                            }
                            echo "</ul>";
                        } else {
                            echo "&nbsp;";
                        }
                    } else {
                        echo $value;
                    }
                    echo "</td>";
                }
                echo '</tr>';
                echo '</tbody>';
                echo '</table>';
            }
        } // validate_print_user_details


    } // end class validate_user

?>
