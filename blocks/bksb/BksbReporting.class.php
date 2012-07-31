<?php

    /******************************************************************
	*
	*  BKBSReporting
	*  ====================
	*
    *  @usage           Used to retrieve Initial Assessment Results
	*
	*  @author			Nathan Kowald
	*  @since			26-08-2010
	*  @lastmodified    30-08-2011
	*
	******************************************************************/
    class BksbReporting 
    {

        private $server;
        private $password;
        private $selected_db;

        public $errors;
        public $con;
        public $connection;
        public $ass_cats;
		public $ass_types;

        public function __construct() {

            $this->errors = array();

            $this->server = "BKSB2\BKSBPORTAL";
            $this->user = "bksb";
            $this->password = "bksb";
            $this->selected_db = "bksb_mini_test";

            // create an instance of the  ADO connection object
            $this->connection = new COM ("ADODB.Connection") or die("Cannot start ADO");
            // define connection string, specify database driver
            $con = "PROVIDER=SQLOLEDB;SERVER=".$this->server.";UID=".$this->user.";PWD=".$this->password.";DATABASE=".$this->selected_db;
            $this->connection->open($con); //Open the connection to the database

            // array to hold table columns - ass cats (usage: your mum is an ass-cat).
            $this->ass_cats = array(
                'English Results', 
                'Maths Results', 
                'ICT Results Word', 
                'ICT Results PowerPoint', 
                'ICT Results Email', 
                'ICT Results Database', 
                'ICT Results Excel', 
                'ICT Results Publisher', 
                'ICT Results Internet'
            );

			$this->ass_types = array(
                1 => 'Literacy E2',
                2 => 'Literacy E3',
                3 => 'Literacy L1',
                4 => 'Literacy L2',
                5 => 'Literacy L3',
                6 => 'Numeracy E2',
                7 => 'Numeracy E3',
                8 => 'Numeracy L1',
                9 => 'Numeracy L2',
                10 => 'Numeracy L3'
			);

        }

		// Fucking idiot me only updated usernames in one table, instead of 'all instances' that use the username: FIX!
		/*
		public function restoreUsernames() {
		
			$query = "SELECT user_id, userName FROM dbo.bksb_Users ORDER BY user_id";
			if ($result = $this->old_connection->execute($query)) {
				$old_usernames = array();
				while (!$result->EOF) {
					$user_id = $result->fields['user_id']->value;
					$username = $result->fields['userName']->value;
					
					$old_usernames[$user_id] = $username;
					
					$result->MoveNext(); //move on to the next record
				}
			}
			
			// show array
			foreach ($old_usernames as $key => $value) {
				// Query will update record $key with username of $value
				$update_query = "UPDATE dbo.bksb_Users SET userName = '$value' WHERE user_id = $key";
				if ($result_new = $this->connection->execute($update_query)) {
					// worked!
				} else {
					echo "Update failed for user $key";
				}
			}
		}
		*/
		
		// Legacy function
		public function getAssTypeFromNo($no) {
			if (isset($this->ass_types[$no])) {
				return $this->ass_types[$no];
			} else {
				return false;
			}
		}
		
        public function getAllResults($user_id='') {

            if ($user_id == '') {
				return false;
			}
			$details = array();
			
			// nkowald - 2012-01-03 - If username contain's single quote, escape it
			$user_id = str_replace("'", "''", $user_id);

			$query = "SELECT Result FROM dbo.bksb_IAResults WHERE UserName = '$user_id' ORDER BY DateCompleted DESC";
			if ($result = $this->connection->execute($query)) {
				while (!$result->EOF) {
					$details[$user_id][] = $result->fields['Result']->value;
					$result->MoveNext(); //move on to the next record
				}
			}
			$query = "SELECT WordProcessing, Spreadsheets, Databases, DesktopPublishing, Presentation, Email, General, Internet FROM dbo.bksb_ICTIAResults WHERE UserName = '$user_id' ORDER BY session_id DESC";
			if ($result = $this->connection->execute($query)) {
				while (!$result->EOF) {
					$details[$user_id][] = 'WordProcessing ' . $result->fields['WordProcessing']->value;
					$details[$user_id][] = 'Spreadsheets ' . $result->fields['Spreadsheets']->value;
					$details[$user_id][] = 'Databases ' . $result->fields['Databases']->value;
					$details[$user_id][] = 'DesktopPublishing ' . $result->fields['DesktopPublishing']->value;
					$details[$user_id][] = 'Presentation ' . $result->fields['Presentation']->value;
					$details[$user_id][] = 'Email ' . $result->fields['Email']->value;
					$details[$user_id][] = 'General ' . $result->fields['General']->value;
					$details[$user_id][] = 'Internet ' .$result->fields['Internet']->value;
					$result->MoveNext(); //move on to the next record
				}
			}
			//$result->Close();

			return $details;
        }

        public function getResults($user_id) {
            // Create the table headings
            $results = array();

            // Check if user id exists in Moodle
            foreach ($this->ass_cats as $cat) {
                if ($result = $this->getUserResultForCat($cat, $user_id)) {
                    $results[] = "$result";
                } else {
                    $results[] = "&mdash;";
                }
            }
            return $results;
        }

        public function getUserResultForCat($cat, $user_id='') {

            if (in_array($cat, $this->ass_cats) && $user_id != '') {
                if ($results = $this->getAllResults($user_id)) {
                    if (is_array($results)) {
                        // return the correct result for the given category
                        $html = '';
                        switch ($cat) {
                            case 'English Results':
                            foreach($results[$user_id] as $result) {
                                if (strpos($result, 'English') !== false) {
                                    $html = str_replace('English ','',$result);
                                    return $html;
                                }
                            }
                            if ($html == '') return false;
                            break;

                            case 'Maths Results':
                            foreach($results[$user_id] as $result) {
                                if (strpos($result, 'Mathematics') !== false) {
                                    $html = str_replace('Mathematics ','', $result);
                                    return $html;
                                }
                            }
                            if ($html == '') return false;
                            break;

                            case 'ICT Results Word':
                            foreach($results[$user_id] as $result) {
                                if (strpos($result, 'WordProcessing') !== false) {
                                    $html = str_replace('WordProcessing ','',$result);
                                    return $html;
                                }
                            }
                            if ($html == '') return false;
                            break;

                            case 'ICT Results PowerPoint':
                            foreach($results[$user_id] as $result) {
                                if (strpos($result, 'Presentation') !== false) {
                                    $html = str_replace('Presentation ','',$result);
                                    return $html;
                                }
                            }
                            if ($html == '') return false;
                            break;

                            case 'ICT Results Email':
                            foreach($results[$user_id] as $result) {
                                if (strpos($result, 'Email') !== false) {
                                    $html = str_replace('Email ','',$result);
                                    return $html;
                                }
                            }
                            if ($html == '') return false;
                            break;

                            case 'ICT Results Database':
                            foreach($results[$user_id] as $result) {
                                if (strpos($result, 'Databases') !== false) {
                                    $html = str_replace('Databases ','',$result);
                                    return $html;
                                }
                            }
                            if ($html == '') return false;
                            break;

                            case 'ICT Results Excel':
                            foreach($results[$user_id] as $result) {
                                if (strpos($result, 'Spreadsheets') !== false) {
                                    $html = str_replace('Spreadsheets ','',$result);
                                    return $html;
                                }
                            }
                            if ($html == '') return false;
                            break;

                            case 'ICT Results Publisher':
                            foreach($results[$user_id] as $result) {
                                if (strpos($result, 'DesktopPublishing') !== false) {
                                    $html = str_replace('DesktopPublishing ','',$result);
                                    return $html;
                                }
                            }
                            if ($html == '') return false;
                            break;

                            case 'ICT Results Internet':
                            foreach($results[$user_id] as $result) {
                                if (strpos($result, 'Internet') !== false) {
                                    $html = str_replace('Internet ','',$result);
                                    return $html;
                                }
                            }
                            if ($html == '') return false;
                            break;
                        }
                    } else {
                        return false;
                    }
                } else {
                    return false;
                }
            } else {
                return false;
            }
        }

        public function getDiagnosticOverview($user_id='', $assessment_no='') {

            if (!is_numeric($assessment_no) || !is_numeric($user_id)) {
				return false;
			}
			$assessment = $this->ass_types[$assessment_no];
			$query = "SELECT curric_ref, TrackingComment FROM dbo.vw_student_curric_bestScoreAndComment WHERE Assessment = '$assessment' AND userName = '$user_id'";

			if ($result = $this->connection->execute($query)) {
				$overview = array();
				$curric_refs = array();
				while (!$result->EOF) {
					$overview[$result->fields['curric_ref']->value] = ($result->fields['TrackingComment']->value == NULL) ? 'Tick' : $result->fields['TrackingComment']->value;
					$curric_refs[] = $result->fields['curric_ref']->value;
					$result->MoveNext(); //move on to the next record
				}

				// convert curric refs into CSV
				$refs_csv = implode("','",$curric_refs);
				$refs_csv = "'" . $refs_csv . "'";
				// Get correct order for all curric_refs
				$ordered_results = array();
				$query = "SELECT curric_ref, Title, report_pos FROM dbo.bksb_CurricCodes WHERE curric_ref IN ($refs_csv) ORDER BY report_pos ASC";
				if ($result = $this->connection->execute($query)) {
					while (!$result->EOF) {
						// curriculum reference
						$ref = $result->fields['curric_ref']->value;
						// result - retrieved from first query
						$grade = $overview[$ref];

						$ordered_results[] = array(
							'curric_ref' => $ref,
							'title' => $result->fields['Title']->value,
							'result' => $grade
						);
						$result->MoveNext(); //move on to the next record
					}
				}
				if (count($ordered_results) > 0) {
					return $ordered_results;
				} else {
					return false;
				}
			}
			$result->Close();
        }

         public function getDiagnosticResults($user_id='', $assessment_no='') {

            if (is_numeric($assessment_no) && is_numeric($user_id)) {
                $assessment = $this->ass_types[$assessment_no];
                $query = "SELECT curric_ref, TrackingComment FROM dbo.vw_student_curric_bestScoreAndComment WHERE Assessment = '$assessment' AND userName = '$user_id'";

                if ($result = $this->connection->execute($query)) {
                    $overview = array();
                    $curric_refs = array();
                    while (!$result->EOF) {
                        $overview[strtolower($result->fields['curric_ref']->value)] = ($result->fields['TrackingComment']->value == NULL) ? 'Tick' : $result->fields['TrackingComment']->value;
						$curric_refs[] = $result->fields['curric_ref']->value;
                        $result->MoveNext(); //move on to the next record
                    }

                    // convert curric refs into CSV
                    $ordered_results = array();
                    if (count($curric_refs) > 1) {
                        $refs_csv = implode("','",$curric_refs);
                        $refs_csv = "'" . $refs_csv . "'";
                        // Get correct order for all curric_refs
                        $query = "SELECT curric_ref, Title, report_pos FROM dbo.bksb_CurricCodes WHERE curric_ref IN ($refs_csv) ORDER BY report_pos ASC";
						
                        if ($result = $this->connection->execute($query)) {
                            $ordered_results = array();
                            while (!$result->EOF) {
                                // curriculum reference
                                $ref = strtolower($result->fields['curric_ref']->value);
								
                                // result - retrieved from first query
                                $grade = $overview[$ref];

                                /*
                                $ordered_results[$user_id][] = array(
                                    'curric_ref' => $ref,
                                    'title' => $result->fields['Title']->value,
                                    'result' => $grade
                                );
                                 */
                                $ordered_results[] = $grade;
                                $result->MoveNext(); //move on to the next record
                            }
							$no_questions = $this->getNoQuestions($assessment_no);
							// Sometimes user might not have completed all questions, add dashes if this is the case
							
							if (count($ordered_results) < $no_questions) {
								for ($i=1; $i<=$no_questions; $i++) {
									$counter = $i-1;
									if (!isset($ordered_results[$counter])) {
										$ordered_results[$counter] = '&mdash;';
									}
								}
							}
							
                        }
                    }
                    if (count($ordered_results) > 0) {
                        return $ordered_results;
                    } else {
                        // Get number of questions to fill array
                        $no_questions = $this->getNoQuestions($assessment_no);
                        for ($i=0; $i < $no_questions; $i++) {
                            $ordered_results[] = '&mdash;';
                        }
                        return $ordered_results;
                    }
                }
                $result->Close();
            } else {
                return false;
            }
        }


        public function getNoQuestions($assessment_no='') {

            // Assessment numbers map to assessment types
            if (is_numeric($assessment_no) && $assessment_no != '') {
                switch($assessment_no) {
                    case 1:
                        $assessment = 'Literacy E2';
                        $query = "SELECT count(report_pos) AS no_questions FROM dbo.bksb_CurricCodes WHERE Subject = 'Lit' AND Level = 'E2'";
                        break;

                    case 2:
                        $assessment = 'Literacy E3';
                        $query = "SELECT count(report_pos) AS no_questions FROM dbo.bksb_CurricCodes WHERE Subject = 'Lit' AND Level = 'E3'";
                        break;

                    case 3:
                        $assessment = 'Literacy L1';
                        $query = "SELECT count(report_pos) AS no_questions FROM dbo.bksb_CurricCodes WHERE Subject = 'Lit' AND Level = 'L1'";
                        break;

                    case 4:
                        $assessment = 'Literacy L2';
                        $query = "SELECT count(report_pos) AS no_questions FROM dbo.bksb_CurricCodes WHERE Subject = 'Lit' AND Level = 'L2'";
                        break;

                    case 5:
                        $assessment = 'Literacy L3';
                        $query = "SELECT count(report_pos) AS no_questions FROM dbo.bksb_CurricCodes WHERE Subject = 'Lit' AND Level = 'L3'";
                        break;

                    case 6:
                        $assessment = 'Numeracy E2';
                        $query = "SELECT count(report_pos) AS no_questions FROM dbo.bksb_CurricCodes WHERE Subject = 'num' AND Level = 'E2'";
                        break;

                    case 7:
                        $assessment = 'Numeracy E3';
                        $query = "SELECT count(report_pos) AS no_questions FROM dbo.bksb_CurricCodes WHERE Subject = 'Num' AND Level = 'E3'";
                        break;

                    case 8:
                        $assessment = 'Numeracy L1';
                        $query = "SELECT count(report_pos) AS no_questions FROM dbo.bksb_CurricCodes WHERE Subject = 'Num' AND Level = 'L1'";
                        break;

                    case 9:
                        $assessment = 'Numeracy L2';
                        $query = "SELECT count(report_pos) AS no_questions FROM dbo.bksb_CurricCodes WHERE Subject = 'Num' AND Level = 'L2'";
                        break;

                    case 10:
                        $assessment = 'Numeracy L3';
                        $query = "SELECT count(report_pos) AS no_questions FROM dbo.bksb_CurricCodes WHERE Subject = 'Num' AND Level = 'L3'";
                        break;

                    default:
                        return false;
                }

                // Perform SQL query here
                if ($result = $this->connection->execute($query)) {
                    $ordered_results = array();
                    while (!$result->EOF) {
                        $no_questions = $result->fields['no_questions']->value;
                        $result->MoveNext(); //move on to the next record
                    }
                    return $no_questions;
                } // if
            } else {
                return false;
            }

        }

        public function getAssDetails($assessment_no='') {

            // Assessment numbers map to assessment types
            if (is_numeric($assessment_no) && $assessment_no != '') {
                switch($assessment_no) {
                    case 1:
                        $assessment = 'Literacy E2';
                        $query = "SELECT curric_ref, Title FROM dbo.bksb_CurricCodes WHERE Subject = 'Lit' AND Level = 'E2' ORDER BY report_pos";
                        break;

                    case 2:
                        $assessment = 'Literacy E3';
                        $query = "SELECT curric_ref, Title FROM dbo.bksb_CurricCodes WHERE Subject = 'Lit' AND Level = 'E3' ORDER BY report_pos";
                        break;

                    case 3:
                        $assessment = 'Literacy L1';
                        $query = "SELECT curric_ref, Title FROM dbo.bksb_CurricCodes WHERE Subject = 'Lit' AND Level = 'L1' ORDER BY report_pos";
                        break;

                    case 4:
                        $assessment = 'Literacy L2';
                        $query = "SELECT curric_ref, Title FROM dbo.bksb_CurricCodes WHERE Subject = 'Lit' AND Level = 'L2' ORDER BY report_pos";
                        break;

                    case 5:
                        $assessment = 'Literacy L3';
                        $query = "SELECT curric_ref, Title FROM dbo.bksb_CurricCodes WHERE Subject = 'Lit' AND Level = 'L3' ORDER BY report_pos";
                        break;

                    case 6:
                        $assessment = 'Numeracy E2';
                        $query = "SELECT curric_ref, Title FROM dbo.bksb_CurricCodes WHERE Subject = 'num' AND Level = 'E2' ORDER BY report_pos";
                        break;

                    case 7:
                        $assessment = 'Numeracy E3';
                        $query = "SELECT curric_ref, Title FROM dbo.bksb_CurricCodes WHERE Subject = 'Num' AND Level = 'E3' ORDER BY report_pos";
                        break;

                    case 8:
                        $assessment = 'Numeracy L1';
                        $query = "SELECT curric_ref, Title FROM dbo.bksb_CurricCodes WHERE Subject = 'Num' AND Level = 'L1' ORDER BY report_pos";
                        break;

                    case 9:
                        $assessment = 'Numeracy L2';
                        $query = "SELECT curric_ref, Title FROM dbo.bksb_CurricCodes WHERE Subject = 'Num' AND Level = 'L2' ORDER BY report_pos";
                        break;

                    case 10:
                        $assessment = 'Numeracy L3';
                        $query = "SELECT curric_ref, Title FROM dbo.bksb_CurricCodes WHERE Subject = 'Num' AND Level = 'L3' ORDER BY report_pos";
                        break;

                    default:
                        return false;
                }

                // Perform SQL query here
                if ($result = $this->connection->execute($query)) {
                    $ordered_results = array();
                    while (!$result->EOF) {
                        $title = $result->fields['Title']->value;
                        $curric_ref = $result->fields['curric_ref']->value;
                        $ordered_results[] = array($title, $curric_ref);
                        $result->MoveNext(); //move on to the next record
                    }
                    return $ordered_results;
                } // if
            } else {
                return false;
            }

        }
		
		public function getBksbSessionNo($username='', $ass_type=0) {
			
			$bksb_ass_type = 0;
			
			switch ($ass_type) {
				case 1:
				// Literacy E2
				$bksb_ass_type = 239;
				break;
				
				case 2:
				// Literacy E3
				$bksb_ass_type = 5;
				break;
				
				case 3:
				// Literacy L1
				$bksb_ass_type = 6;
				break;
				
				case 4:
				// Literacy L2
				$bksb_ass_type = 7;
				break;
				
				case 5:
				// Literacy L3
				$bksb_ass_type = 238;
				break;
				
				case 6:
				// Numeracy E2
				$bksb_ass_type = 237;
				break;
				
				case 7:
				// Numeracy E3
				$bksb_ass_type = 1;
				break;
				
				case 8:
				// Numeracy L1
				$bksb_ass_type = 2;
				break;
				
				case 9:
				// Numeracy L2
				$bksb_ass_type = 3;
				break;
				
				case 10:
				// Numeracy L3
				$bksb_ass_type = 4;
				break;
			}
				
			// Get the session id 
			$query = "SELECT session_id FROM dbo.bksb_Sessions WHERE userName = '".$username."' AND assessment_id = '".$bksb_ass_type."'";
			
			// Perform SQL query here
			if ($result = $this->connection->execute($query)) {
				$session_id = '';
				while (!$result->EOF) {
					$session_id = $result->fields['session_id']->value;
					$result->MoveNext(); //move on to the next record
				}
			}
			if ($session_id != '') {
				return $session_id;
			} else {
				return false;
			}
		} //getBksbSessionNo()
		
		public function getBksbPercentage($username='', $ass_type=0) {
	
			$session_id = $this->getBksbSessionNo($username, $ass_type);
			
			// Get the percentage - the BKSB way
			$query = "SELECT ((SUM(score) / SUM(out_of)) * 100) AS percentage FROM dbo.bksb_QuestionResponses WHERE session_id = '".$session_id."'";
			
			// Perform SQL query here
			if ($result = $this->connection->execute($query)) {
				$percentage = '';
				while (!$result->EOF) {
					$percentage = $result->fields['percentage']->value;
					$percentage = round($percentage, 0);
					$result->MoveNext(); //move on to the next record
				}
			}
			
			if ($percentage != '' || $percentage == 0) {
				return $percentage;
			} else {
				return false;
			}
			
			// old 'best results' way of getting percentage
			// nkowald 2010-10-05 - Get no of answered questions
			/*
			$x = 0; // incorrect
			$p = 0; // correct
			$un = 0; // unanswered
			foreach ($bksb_results as $res) {
				if ($res == 'X') {
					$x++;
				} else if ($res == 'P') {
					$p++;
				} else {
					$un++;
				}
			}
			// Percentage of right answers can be worked out by ((($p / ($x + $p)) * 100)
			$percentage = round( (($p / ($x + $p)) * 100), 0);
			*/
			
		} // getBksbPercentage()
		
		// Checking results return in all required tables
		/*
		- bksb_Users			(userName)
		- bksb_GroupMembership	(UserName)
		- bksb_IAResults		(UserName)
		- bksb_ICTIAResults		(UserName)
		- bksb_Sessions			(userName)
		*/
		
		// Performs simple queries on above tables querying for username
		// If all usernames found we can update all usernames with correct idnumber
		
		public function checkMatchingUsername($username='') {
		
			$username_exists = array(FALSE, FALSE, FALSE, FALSE, FALSE);
			
			if ($username != '') {

				// bksb_Users check
				$query1 = "SELECT userName FROM dbo.bksb_Users WHERE userName = '$username'";
				if ($result1 = $this->connection->execute($query1)) {
					$user_found = '';
					while (!$result1->EOF) {
						$user_found = $result1->fields['userName']->value;
						$result1->MoveNext();
					}
					if ($user_found != '') {
						$username_exists[0] = TRUE;
					}
				}
				
				// bksb_GroupMembership check
				$query2 = "SELECT UserName FROM dbo.bksb_GroupMembership WHERE UserName = '$username'";
				if ($result2 = $this->connection->execute($query2)) {
					$user_found = '';
					while (!$result2->EOF) {
						$user_found = $result2->fields['UserName']->value;
						$result2->MoveNext();
					}
					if ($user_found != '') {
						$username_exists[1] = TRUE;
					}
				}
				
				// bksb_IAResults check
				$query3 = "SELECT UserName FROM dbo.bksb_IAResults WHERE UserName = '$username'";
				if ($result3 = $this->connection->execute($query3)) {
					$user_found = '';
					while (!$result3->EOF) {
						$user_found = $result3->fields['UserName']->value;
						$result3->MoveNext();
					}
					if ($user_found != '') {
						$username_exists[2] = TRUE;
					}
				}
				
				// bksb_ICTIAResults check
				$query4 = "SELECT UserName FROM dbo.bksb_ICTIAResults WHERE UserName = '$username'";
				if ($result4 = $this->connection->execute($query4)) {
					$user_found = '';
					while (!$result4->EOF) {
						$user_found = $result4->fields['UserName']->value;
						$result4->MoveNext();
					}
					if ($user_found != '') {
						$username_exists[3] = TRUE;
					}
				}
				
				// bksb_Sessions check
				$query5 = "SELECT userName FROM dbo.bksb_Sessions WHERE userName = '$username'";
				if ($result5 = $this->connection->execute($query5)) {
					$user_found = '';
					while (!$result5->EOF) {
						$user_found = $result5->fields['userName']->value;
						$result5->MoveNext();
					}
					if ($user_found != '') {
						$username_exists[4] = TRUE;
					}
				}
				
				// We have results: return array
				return $username_exists;
				
			} else {
				return FALSE;
			}

		}
		
		// Expects an array with the following properties:
		/*
		array(5) {
		  ["username"]=> string(6) "151177"
		  ["firstname"]=> string(5) "0yema"
		  ["lastname"]=> string(14) "fatuma milambo"
		  ["dob"]=> => "01/01/1900"
		  ["postcode"] => "N15 4RU"
		  ["id"]=> int(6354)
		  ["reason"]=> string(33) "ID Number doesn't exist in Moodle" // not needed here
		}
		*/
		public function updateInvalidUsers(array $invalid_users) {
		
			$new_usernames = array();
            $no_users_updated = 0;
            
            require_once(dirname(dirname(dirname(__FILE__))).'/config.php'); // global moodle config file.
            global $CFG;
            require_once($CFG->dirroot.'/blocks/ilp/templates/custom/dbconnect.php'); // include the connection code for CONEL's MIS db
            
            // Before we return invalid BKSB users lets search moodle user table by first and last names to see if we get matched: then update bksb
            // Add new usernames to an array to remove duplicates at the end
            foreach ($invalid_users as $key => $user) {
            
                $firstname = $user['firstname'];
                $lastname = $user['lastname'];
                
                if ($user_match = get_record('user', 'firstname', $firstname, 'lastname', $lastname)) {
                    $no_matches = count($user_match);
                    
                    if ($no_matches === 1 && $user_match->idnumber != '') {
                
                        
                        // Find which bksb tables contain this username and need to be updated
                        $user_exists = $this->checkMatchingUsername($user['username']);
                        
                        $new_usernames[] = $user_match->idnumber;

                        // bksb_Users
                        if ($user_exists[0] === TRUE) {
                            // Handle duplicate users - If a valid idnumber is found already in BKSB, skip updating this incorrect ID
                            $query = "UPDATE dbo.bksb_Users SET userName = '".$user_match->idnumber."' WHERE (user_id = '".$user['id']."')";
                            if (!$result = $this->connection->execute($query)) {
                                echo "Query failed: $query <br />";
                            }
                        }
                        // bksb_GroupMembership
                        if ($user_exists[1] === TRUE) {
                            $gm_query = "UPDATE dbo.bksb_GroupMembership SET UserName = '".$user_match->idnumber."' WHERE UserName = '".$user['username']."'";
                            if (!$gm_result = $this->connection->execute($gm_query)) {
                                echo "Query failed: $gm_query <br />";
                            }
                        }
                        // bksb_IAResults
                        if ($user_exists[2] === TRUE) {
                            $ia_query = "UPDATE dbo.bksb_IAResults SET UserName = '".$user_match->idnumber."' WHERE UserName = '".$user['username']."'";
                            if (!$ia_result = $this->connection->execute($ia_query)) {
                                echo "Query failed: $ia_query <br />";
                            }
                        }
                        // bksb_ICTIAResults
                        if ($user_exists[3] === TRUE) {
                            $ictia_query = "UPDATE dbo.bksb_ICTIAResults SET UserName = '".$user_match->idnumber."' WHERE UserName = '".$user['username']."'";
                            if (!$ictia_result = $this->connection->execute($ictia_query)) {
                                echo "Query failed: $ictia_query <br />";
                            }
                        }
                        // bksb_Sessions
                        if ($user_exists[4] === TRUE) {
                            $sess_query = "UPDATE dbo.bksb_Sessions SET userName = '".$user_match->idnumber."' WHERE userName = '".$user['username']."'";
                            if (!$sess_result = $this->connection->execute($sess_query)) {
                                echo "Query failed: $sess_query <br />";
                            }
                        }
                        
                        // Unset invalid user as if one of these tables doesn't exist, might just mean it doesn't exist
                        unset($invalid_users[$key]);
                        $no_users_updated++;
                        
                    }
                } else {
                
                    // nkowald - 2011-08-22 - No match found on first and lastname in Moodle, let's try matching on postcode and date of birth (both means this person = found)
                    // nkowald - 2011-08-22 - running from cron we need to use this
                    $user_match_idnumber = '';
                    $postcode = '';
                    $dob = '';
                    
                    if ($user['postcode'] != '') {
                        // Clean postcode for matching: uppercase, strip spaces, trim edges
                        $postcode = trim(str_replace(' ', '', strtoupper($user['postcode'])));
                    }
                    // Date format should be 14/12/1958 - validation on bksb means this is fine
                    if ($user['dob'] != '') {
                        $dob = $user['dob'];
                    }
                    
                    // If both postcode and dob exists, this is enough info to search for a match
                    $query = '';
                    if ($postcode != '' && $dob != '') {
                        $query = "SELECT idnumber FROM mdl_user WHERE dob = '$dob' AND REPLACE(postcode, ' ', '') = '".$postcode."'";
                    } else if ($dob != '' && $postcode == '') {
                        // Search on dob and firstname
                        $query = "SELECT idnumber FROM mdl_user WHERE dob = '$dob' AND LOWER(firstname) = '".strtolower($user['firstname'])."'";
                    } else if ($postcode == '' && $dob != '') {
                        // Search on postcode and firstname
                        $query = "SELECT idnumber FROM mdl_user WHERE LOWER(firstname) = '".strtolower($user['firstname'])."' AND REPLACE(postcode, ' ', '') = '".$postcode."'";
                    }
                    
                    $student_id = '';
                    if ($matches = get_records_sql($query)) {
                        foreach($matches as $match) {
                            $student_id = $match->idnumber;
                        }
                    }
                    
                    if ($student_id != '') {
                        $user_match_idnumber = $student_id;
                        
                        // We found a match looking in EBS, great! Let's update BKSB
                        
                        $new_usernames[] = $user_match_idnumber;
                        
                        // Find which bksb tables contain this username and need to be updated
                        $user_exists = $this->checkMatchingUsername($user['username']);

                        // bksb_Users
                        if ($user_exists[0] === TRUE) {
                            // Handle duplicate users - If a valid idnumber is found already in BKSB, skip updating this incorrect ID
                            $query = "UPDATE dbo.bksb_Users SET userName = '$user_match_idnumber' WHERE (user_id = '".$user['id']."')";
                            if (!$result = $this->connection->execute($query)) {
                                echo "Query failed: $query <br />";
                            }
                        }
                        // bksb_GroupMembership
                        if ($user_exists[1] === TRUE) {
                            $gm_query = "UPDATE dbo.bksb_GroupMembership SET UserName = '$user_match_idnumber' WHERE UserName = '".$user['username']."'";
                            if (!$gm_result = $this->connection->execute($gm_query)) {
                                echo "Query failed: $gm_query <br />";
                            }
                        }
                        // bksb_IAResults
                        if ($user_exists[2] === TRUE) {
                            $ia_query = "UPDATE dbo.bksb_IAResults SET UserName = '$user_match_idnumber' WHERE UserName = '".$user['username']."'";
                            if (!$ia_result = $this->connection->execute($ia_query)) {
                                echo "Query failed: $ia_query <br />";
                            }
                        }
                        // bksb_ICTIAResults
                        if ($user_exists[3] === TRUE) {
                            $ictia_query = "UPDATE dbo.bksb_ICTIAResults SET UserName = '$user_match_idnumber' WHERE UserName = '".$user['username']."'";
                            if (!$ictia_result = $this->connection->execute($ictia_query)) {
                                echo "Query failed: $ictia_query <br />";
                            }
                        }
                        // bksb_Sessions
                        if ($user_exists[4] === TRUE) {
                            $sess_query = "UPDATE dbo.bksb_Sessions SET userName = '$user_match_idnumber' WHERE userName = '".$user['username']."'";
                            if (!$sess_result = $this->connection->execute($sess_query)) {
                                echo "Query failed: $sess_query <br />";
                            }
                        }
                        
                        // Unset invalid user as if one of these tables doesn't exist, might just mean it doesn't exist
                        unset($invalid_users[$key]);
                        $no_users_updated++;
                        
                    }
                    
                } // else
            } // foreach
			
			// Finally, return the invalids
			if ($no_users_updated > 0) {
				$user_txt = ($no_users_updated > 1) ? 'users' : 'user';
				echo "<pre>Updated $no_users_updated $user_txt!</pre>";
				
				// Remove duplicates
				$this->removeDuplicateUsers(TRUE, $new_usernames);
			} else {
				echo '<pre>No users were updated</pre>';
			}
		}
		
		
		// nkowald - 2011-01-10 - Get all users from BKSB
		// $firstname - Get invalid users by firstname
		// $lastname - Get invalid users by lastname
		
		public function getInvalidBksbUsers($firstname='', $lastname='', $order_field='userName') {
		
			// Escape firstname and lastname for SQL query
			$firstname = ($firstname != '') ? trim($firstname) : $firstname;
			$lastname = ($lastname != '') ? trim($lastname) : $lastname;
			
			// Check for valid $order_field
			$valid_orders = array('userName', 'FirstName', 'LastName', 'DOB', 'Postcode');
			
			// if firstname is given and lastname given
			if ($firstname != '' && $lastname != '') {
				$query = "SELECT user_id, userName, FirstName, LastName, DOB, PostcodeA as Postcode FROM dbo.bksb_Users WHERE FirstName = '$firstname' AND LastName = '$lastname' ORDER BY FirstName ASC";	
			} else if ($firstname != '' && $lastname == '') {
				$query = "SELECT user_id, userName, FirstName, LastName, DOB, PostcodeA as Postcode FROM dbo.bksb_Users WHERE FirstName = '$firstname' ORDER BY FirstName ASC";	
			} else if ($firstname == '' && $lastname != '') {
				$query = "SELECT user_id, userName, FirstName, LastName, DOB, PostcodeA as Postcode FROM dbo.bksb_Users WHERE LastName = '$lastname' ORDER BY LastName ASC";	
			} else {
				if (in_array($order_field, $valid_orders)) {
					$order = "$order_field ASC";
				} else {
					$order = 'Postcode DESC';
				}
				$query = "SELECT user_id, userName, FirstName, LastName, DOB, PostcodeA as Postcode FROM dbo.bksb_Users ORDER BY $order";
			}
			
			//$query = "SELECT user_id, userName, FirstName, LastName FROM (SELECT Row_Number() OVER (ORDER BY userName) AS RowIndex, * FROM bksb_Users) AS Sub WHERE Sub.RowIndex >= 1 AND Sub.RowIndex <= 1000 ORDER BY FirstName";
			
			if ($result = $this->connection->execute($query)) {
				$invalid_users = array();
				
				while (!$result->EOF) {

					// Do checks here instead of later
					$username = $result->fields['userName']->value;
					$invalid = FALSE;
					
					
					if (!is_numeric($username)) {
						$reason = 'Non-numeric username';
						$invalid = TRUE;
					}
					else if (!record_exists('user', 'idnumber', $username)) {
						$reason = 'ID Number doesn\'t exist in Moodle';
						$invalid = TRUE;
					}
				
					if ($invalid === TRUE) {
						$invalid_users[] = array(
									'username' => $username, 
									'firstname' => $result->fields['FirstName']->value,
									'lastname' => $result->fields['LastName']->value,
									'dob' => ($result->fields['DOB']->value != '01/01/1900') ? $result->fields['DOB']->value : "",
									'postcode' => ($result->fields['Postcode']->value != '') ? strtoupper($result->fields['Postcode']->value) : "",
									'id' => $result->fields['user_id']->value,
									'reason' => $reason
						);
					}
					$result->MoveNext(); //move on to the next record
				}
				
			}
			
			return $invalid_users;
		}
	
		
		// nkowald - 2010-01-10 - Get a list of all bksb groups
		public function getBksbGroups() {
			$query = "SELECT * FROM dbo.bksb_Groups";
			if ($result = $this->connection->execute($query)) {
                    while (!$result->EOF) {
                        $groups[] = $result->fields['group_id']->value;				
                        $result->MoveNext(); //move on to the next record
                    }
					return $groups;
			} else {
				return false;
			}
		}
		
		public function getDiagnosticOverviewsForGroup($group_name='') {
		
			if ($group_name != '') {
				$query = "SELECT DISTINCT userName
						FROM dbo.bksb_Sessions
						WHERE (assessment_id IN (SELECT ass_ref FROM dbo.bksb_Assessments WHERE ([assessment group] = 1))) AND (userName IN (SELECT UserName FROM dbo.bksb_GroupMembership WHERE (group_id = '".$group_name."') AND status='Complete') )
						ORDER BY userName";
				
				if ($result = $this->connection->execute($query)) {
						$user_diag = array();

						// Set total counts
						$user_diag['total_literacy_e2'] = $user_diag['total_literacy_e3'] = $user_diag['total_literacy_l1'] = $user_diag['total_literacy_l2'] = $user_diag['total_literacy_l3'] = 0;
						$user_diag['total_numeracy_e2'] = $user_diag['total_numeracy_e3'] = $user_diag['total_numeracy_l1'] = $user_diag['total_numeracy_l2'] = $user_diag['total_numeracy_l3'] = 0;
						
						while (!$result->EOF) {
						
							$username = $result->fields['userName']->value;
							
							$user_diag[$username]['user_name'] = $result->fields['userName']->value;	
							//$user_diag[$username]['session_id'] = $result->fields['session_id']->value;															
							//$user_diag[$username]['status'] = $result->fields['status']->value;				
							
							// Add Level
							$ass_query = "SELECT DISTINCT Assessment FROM dbo.vw_student_curric_bestScoreAndComment WHERE (userName = '$username')";
							if ($result_ass = $this->connection->execute($ass_query)) {
								while (!$result_ass->EOF) {
									$assessments[] = $result_ass->fields['Assessment']->value;
									$result_ass->MoveNext(); //move on to the next record
								}
								
								// Literacy E2
								$user_diag[$username]['literacy_e2'] = (in_array('Literacy E2', $assessments)) ? 'Yes' : '-';
								if (in_array('Literacy E2', $assessments)) { $user_diag['total_literacy_e2']++; }
								// Literacy E3
								$user_diag[$username]['literacy_e3'] = (in_array('Literacy E3', $assessments)) ? 'Yes' : '-';
								if (in_array('Literacy E3', $assessments)) { $user_diag['total_literacy_e3']++; }
								// Literacy L1
								$user_diag[$username]['literacy_l1'] = (in_array('Literacy L1', $assessments)) ? 'Yes' : '-';
								if (in_array('Literacy L1', $assessments)) { $user_diag['total_literacy_l1']++; }
								// Literacy L2
								$user_diag[$username]['literacy_l2'] = (in_array('Literacy L2', $assessments)) ? 'Yes' : '-';
								if (in_array('Literacy L2', $assessments)) { $user_diag['total_literacy_l2']++; }
								// Literacy L3
								$user_diag[$username]['literacy_l3'] = (in_array('Literacy L3', $assessments)) ? 'Yes' : '-';
								if (in_array('Literacy L3', $assessments)) { $user_diag['total_literacy_l3']++; }
								
								// Numeracy E2
								$user_diag[$username]['numeracy_e2'] = (in_array('Numeracy E2', $assessments)) ? 'Yes' : '-';
								if (in_array('Numeracy E2', $assessments)) { $user_diag['total_numeracy_e2']++; }
								// Numeracy E3
								$user_diag[$username]['numeracy_e3'] = (in_array('Numeracy E3', $assessments)) ? 'Yes' : '-';
								if (in_array('Numeracy E3', $assessments)) { $user_diag['total_numeracy_e3']++; }
								// Numeracy L1
								$user_diag[$username]['numeracy_l1'] = (in_array('Numeracy L1', $assessments)) ? 'Yes' : '-';
								if (in_array('Numeracy L1', $assessments)) { $user_diag['total_numeracy_l1']++; }
								// Numeracy L2
								$user_diag[$username]['numeracy_l2'] = (in_array('Numeracy L2', $assessments)) ? 'Yes' : '-';
								if (in_array('Numeracy L2', $assessments)) { $user_diag['total_numeracy_l2']++; }
								// Numeracy L3
								$user_diag[$username]['numeracy_l3'] = (in_array('Numeracy L3', $assessments)) ? 'Yes' : '-';
								if (in_array('Numeracy L3', $assessments)) { $user_diag['total_numeracy_l3']++; }
								
								$assessments = array();
							}
							
							$result->MoveNext(); //move on to the next record
						}
						
						// return all user diags for this given group
						return $user_diag;
				}
				// Now we want to add some data to the arrays
				
			} else {
				return false;
			}
			
		}
		
		// nkowald - Need a new method to get users per group
		private function getUsersForGroup($group_name = '') {
		
			$query = sprintf("SELECT UserName FROM dbo.bksb_GroupMembership WHERE (group_id = '%s')", 
				$group_name
			);
			
			if ($result = $this->connection->execute($query)) {
				$users = array();

				while (!$result->EOF) {
					$user = $result->fields['UserName']->value;
					// Need to escape single quotes
					$user = str_replace("'", "''", $user);
					$users[] = $user;
					$result->MoveNext(); //move on to the next record
				}
				
				return $users;
			} else {
				return false;
			}
			
		}
		
		public function getNameFromUsername($username = '') {
			
			if ($username != '') {
				// Escape single quotes
				$username = str_replace("'", "''", $username);
				$query = sprintf("SELECT FirstName, LastName FROM bksb_Users WHERE userName = '%s'",
					$username
				);
				
				if ($result = $this->connection->execute($query)) {

					$name = array();
					while (!$result->EOF) {
						$name['firstname'] = ucwords($result->fields['FirstName']->value);
						$name['lastname'] = ucwords($result->fields['LastName']->value);
						$result->MoveNext(); //move on to the next record
					}
					
					return $name;
				}
				
			} else {
				return false;
			}

		}
		
		public function getIAForGroup($group_name='', $ia_type = '',  $unix_start = '', $unix_end = '') {
			
			if ($group_name != '' && $ia_type != '') {

				// get users for given group
				if ($users = $this->getUsersForGroup($group_name)) {
					// convert to csv
					$group_users = implode(',', $users);
					$group_users = "'" . str_replace(",", "','", $group_users) . "'";
				} else {
					return false;
				}
				
				$group_sessions = '';
				// Now we have a list of the users from the group, if valid start and end date given, cut down to users with a complete session between date range
				if (($unix_start != '' && is_numeric($unix_start)) && ($unix_end != '' && is_numeric($unix_end))) {
				
					/* 
					Convert start into SQL smalldatetime format
					 It's weird but in Microsoft SQL Server Management Studio Express the date displays as d/m/Y HOWEVER
					 it actually requires you to use m/d/Y format in the query.
					*/ 
					
					$sdt_start = date('m/d/Y H:i:s', $unix_start);
					$sdt_end = date('m/d/Y H:i:s', $unix_end);
					
					$query = sprintf("SELECT session_id 
						FROM bksb_Sessions 
						WHERE (status = 'Complete') 
						AND (dateCreated >= '%s') 
						AND (dateCreated <= '%s') 
						AND userName IN (%s)",
						$sdt_start,
						$sdt_end,
						$group_users
					);
					
					if ($result = $this->connection->execute($query)) {
						$sessions = array();

						while (!$result->EOF) {
							$sessions[] = $result->fields['session_id']->value;
							$result->MoveNext(); //move on to the next record
						}
						
						// Finally, update group_users to be the filtered list of users 
						$group_sessions = implode(',', $sessions);
						$group_sessions = "'" . str_replace(",", "','", $group_sessions) . "'";
					}
					
				}
				
				// Use this query if English or Maths selected
				if ($ia_type == 'English' || $ia_type == 'Mathematics') {

					if ($group_sessions != '') {
						$query = "SELECT UserName, Result FROM dbo.bksb_IAResults WHERE (Session_id IN ($group_sessions)) ORDER BY UserName";
					} else {
						$query = "SELECT UserName, Result FROM dbo.bksb_IAResults WHERE (UserName IN ($group_users)) ORDER BY UserName";
					}

					if ($result = $this->connection->execute($query)) {
							$user_ass = array();

							while (!$result->EOF) {
							
								$username = $result->fields['UserName']->value;
								
								$user_ass[$username]['user_name'] = $result->fields['UserName']->value;			
								$user_ass[$username]['results'][] = $result->fields['Result']->value;

								$result->MoveNext(); //move on to the next record
							}
							
							// Set total counts
							$user_ass['total_literacy_e2'] = $user_ass['total_literacy_e3'] = $user_ass['total_literacy_l1'] = $user_ass['total_literacy_l2'] = $user_ass['total_literacy_l3'] = 0;
							$user_ass['total_numeracy_e2'] = $user_ass['total_numeracy_e3'] = $user_ass['total_numeracy_l1'] = $user_ass['total_numeracy_l2'] = $user_ass['total_numeracy_l3'] = 0;
							
							foreach ($user_ass as $user) {
							
								$username = $user['user_name'];

								// English E2
								$user_ass[$username]['literacy_e2'] = (in_array('English Entry 2', $user_ass[$username]['results'])) ? 'Yes' : '-';
								if (in_array('English Entry 2', $user_ass[$username]['results'])) { $user_ass['total_literacy_e2']++; }
								// English E3
								$user_ass[$username]['literacy_e3'] = (in_array('English Entry 3', $user_ass[$username]['results'])) ? 'Yes' : '-';
								if (in_array('English Entry 3', $user_ass[$username]['results'])) { $user_ass['total_literacy_e3']++; }
								// English L1
								$user_ass[$username]['literacy_l1'] = (in_array('English Level 1', $user_ass[$username]['results'])) ? 'Yes' : '-';
								if (in_array('English Level 1', $user_ass[$username]['results'])) { $user_ass['total_literacy_l1']++; }
								// English L2
								$user_ass[$username]['literacy_l2'] = (in_array('English Level 2', $user_ass[$username]['results'])) ? 'Yes' : '-';
								if (in_array('English Level 2', $user_ass[$username]['results'])) { $user_ass['total_literacy_l2']++; }
								// English L3
								$user_ass[$username]['literacy_l3'] = (in_array('English Level 3', $user_ass[$username]['results'])) ? 'Yes' : '-';
								if (in_array('English Level 3', $user_ass[$username]['results'])) { $user_ass['total_literacy_l3']++; }

								// Mathematics E2
								$user_ass[$username]['numeracy_e2'] = (in_array('Mathematics Entry 2', $user_ass[$username]['results'])) ? 'Yes' : '-';
								if (in_array('Mathematics Entry 2', $user_ass[$username]['results'])) { $user_ass['total_numeracy_e2']++; }
								// Mathematics E3
								$user_ass[$username]['numeracy_e3'] = (in_array('Mathematics Entry 3', $user_ass[$username]['results'])) ? 'Yes' : '-';
								if (in_array('Mathematics Entry 3', $user_ass[$username]['results'])) { $user_ass['total_numeracy_e3']++; }
								// Mathematics L1
								$user_ass[$username]['numeracy_l1'] = (in_array('Mathematics Level 1', $user_ass[$username]['results'])) ? 'Yes' : '-';
								if (in_array('Mathematics Level 1', $user_ass[$username]['results'])) { $user_ass['total_numeracy_l1']++; }
								// Mathematics L2
								$user_ass[$username]['numeracy_l2'] = (in_array('Mathematics Level 2', $user_ass[$username]['results'])) ? 'Yes' : '-';
								if (in_array('Mathematics Level 2', $user_ass[$username]['results'])) { $user_ass['total_numeracy_l2']++; }
								// Mathematics L3
								$user_ass[$username]['numeracy_l3'] = (in_array('Mathematics Level 3', $user_ass[$username]['results'])) ? 'Yes' : '-';
								if (in_array('Mathematics Level 3', $user_ass[$username]['results'])) { $user_ass['total_numeracy_l3']++; }

							}
							
							// return all user initial assessments for this given group
							return $user_ass;
					}
				} else if ($ia_type == 'ICT') {
					
					if ($group_sessions != '') {
						$query = "SELECT UserName, WordProcessing, Spreadsheets, Databases, DesktopPublishing, Presentation, Email, General, Internet FROM dbo.bksb_ICTIAResults WHERE (session_id IN ($group_sessions)) ORDER BY UserName";
					} else {
						$query = "SELECT UserName, WordProcessing, Spreadsheets, Databases, DesktopPublishing, Presentation, Email, General, Internet FROM dbo.bksb_ICTIAResults WHERE (UserName IN ($group_users)) ORDER BY UserName";
					}

						
					if ($result = $this->connection->execute($query)) {
					
						$user_ass = array();

						while (!$result->EOF) {
						
							$username = $result->fields['UserName']->value;
							
							$valid_types = array('word_processing', 'spreadsheets', 'databases', 'desktop_publishing', 'presentation', 'email', 'general', 'internet');
							
							$user_ass[$username]['user_name'] = $result->fields['UserName']->value;			
							$user_ass[$username]['results']['word_processing'] = $result->fields['WordProcessing']->value;
							$user_ass[$username]['results']['spreadsheets'] = $result->fields['Spreadsheets']->value;
							$user_ass[$username]['results']['databases'] = $result->fields['Databases']->value;
							$user_ass[$username]['results']['desktop_publishing'] = $result->fields['DesktopPublishing']->value;
							$user_ass[$username]['results']['presentation'] = $result->fields['Presentation']->value;
							$user_ass[$username]['results']['email'] = $result->fields['Email']->value;
							$user_ass[$username]['results']['general'] = $result->fields['General']->value;
							$user_ass[$username]['results']['internet'] = $result->fields['Internet']->value;

							$result->MoveNext(); //move on to the next record
						}
						
						// Set total counts
						//$user_ass['total_word_processing'] = $user_ass['total_spreadsheets'] = $user_ass['total_databases'] = $user_ass['total_desktop_publishing'] = $user_ass['total_presentation'] = $user_ass['total_email'] = $user_ass['total_general'] = $user_ass['total_internet'] = 0;
					
						// Strip HTML from results where they exist
						foreach($user_ass as $key => $user) {
							
							foreach ($user['results'] as $type => $value) {
								if (in_array($type, $valid_types)) {
									if (strstr($value, '<br />')) {
										$wp = explode('<br />', $value);
										$user_ass[$key]['results'][$type] = $wp[0];
									}
								}
							}
						
						}

						return $user_ass;
						
					}
				
				}
				
			} else {
				return false;
			}

		}
		

		public function getIctTotals(array $users) {

				// Word Processing
				$totals['word_processing']['total_below_entry_3'] = $totals['word_processing']['total_entry_3'] = $totals['word_processing']['total_below_level_1'] = $totals['word_processing']['total_level_1'] = $totals['word_processing']['total_level_2'] = 0;
				// Spreadsheets
				$totals['spreadsheets']['total_below_entry_3'] = $totals['spreadsheets']['total_entry_3'] = $totals['spreadsheets']['total_below_level_1'] = $totals['spreadsheets']['total_level_1'] = $totals['spreadsheets']['total_level_2'] = 0;
				// Databases
				$totals['databases']['total_below_entry_3'] = $totals['databases']['total_entry_3'] = $totals['databases']['total_below_level_1'] = $totals['databases']['total_level_1'] = $totals['databases']['total_level_2'] = 0;
				// Desktop Publishing
				$totals['desktop_publishing']['total_below_entry_3'] = $totals['desktop_publishing']['total_entry_3'] = $totals['desktop_publishing']['total_below_level_1'] = $totals['desktop_publishing']['total_level_1'] = $totals['desktop_publishing']['total_level_2'] = 0;
				// Presentation
				$totals['presentation']['total_below_entry_3'] = $totals['presentation']['total_entry_3'] = $totals['presentation']['total_below_level_1'] = $totals['presentation']['total_level_1'] = $totals['presentation']['total_level_2'] = 0;
				// Email
				$totals['email']['total_below_entry_3'] = $totals['email']['total_entry_3'] = $totals['email']['total_below_level_1'] = $totals['email']['total_level_1'] = $totals['email']['total_level_2'] = 0;
				// General
				$totals['general']['total_below_entry_3'] = $totals['general']['total_entry_3'] = $totals['general']['total_below_level_1'] = $totals['general']['total_level_1'] = $totals['general']['total_level_2'] = 0;
				// Internet
				$totals['internet']['total_below_entry_3'] = $totals['internet']['total_entry_3'] = $totals['internet']['total_below_level_1'] = $totals['internet']['total_level_1'] = $totals['internet']['total_level_2'] = 0;
				
				foreach ($users as $user) {
					
					$username = $user['user_name'];
					
					// Yet another loop to update counts
					foreach ($users[$username]['results'] as $key => $result) {
						
						switch ($result) {
							case 'Below Entry 3':
								$totals[$key]['total_below_entry_3']++;
								break;
							case 'Entry 3':
								$totals[$key]['total_entry_3']++;
								break;
							case 'Below Level 1':
								$totals[$key]['total_below_level_1']++;
								break;
							case 'Level 1':
								$totals[$key]['total_level_1']++;
								break;
							case 'Level 2':
								$totals[$key]['total_level_2']++;
						}
					}
					
				}

				// Now we've got totals count: create HTML of totals
				$html = '<h3>Totals</h3><div id="ict_totals"><table id="ict_container"><tr>';
				foreach ($totals as $key => $total) {
					switch ($key) {
						case 'word_processing':
						$html .= '<td><b>Word Processing</b><br /><table class="ict_totals"><tr><td>Below Entry 3: </td><td>' . $totals[$key]['total_below_entry_3'] .'</td></tr><tr><td>Entry 3:</td><td>' . $totals[$key]['total_entry_3'] .'</td></tr><tr><td>Below Level 1:</td><td> '.$totals[$key]['total_below_level_1'].'</td></tr><tr><td>Level 1:</td><td> '.$totals[$key]['total_level_1'].'</td></tr><tr><td>Level 2:</td><td> '.$totals[$key]['total_level_2'].'</td></tr></table>';
						break;
						
						case 'spreadsheets':
						$html .= '<td><b>Spreadsheets</b><br /><table class="ict_totals"><tr><td>Below Entry 3: </td><td>' . $totals[$key]['total_below_entry_3'] .'</td></tr><tr><td>Entry 3:</td><td>' . $totals[$key]['total_entry_3'] .'</td></tr><tr><td>Below Level 1:</td><td> '.$totals[$key]['total_below_level_1'].'</td></tr><tr><td>Level 1:</td><td> '.$totals[$key]['total_level_1'].'</td></tr><tr><td>Level 2:</td><td> '.$totals[$key]['total_level_2'].'</td></tr></table>';
						break;
						
						case 'databases':
						$html .= '<td><b>Databases</b><br /><table class="ict_totals"><tr><td>Below Entry 3: </td><td>' . $totals[$key]['total_below_entry_3'] .'</td></tr><tr><td>Entry 3:</td><td>' . $totals[$key]['total_entry_3'] .'</td></tr><tr><td>Below Level 1:</td><td> '.$totals[$key]['total_below_level_1'].'</td></tr><tr><td>Level 1:</td><td> '.$totals[$key]['total_level_1'].'</td></tr><tr><td>Level 2:</td><td> '.$totals[$key]['total_level_2'].'</td></tr></table>';	
						break;
						
						case 'desktop_publishing':
						$html .= '<td><b>Desktop Publishing</b><br /><table class="ict_totals"><tr><td>Below Entry 3: </td><td>' . $totals[$key]['total_below_entry_3'] .'</td></tr><tr><td>Entry 3:</td><td>' . $totals[$key]['total_entry_3'] .'</td></tr><tr><td>Below Level 1:</td><td> '.$totals[$key]['total_below_level_1'].'</td></tr><tr><td>Level 1:</td><td> '.$totals[$key]['total_level_1'].'</td></tr><tr><td>Level 2:</td><td> '.$totals[$key]['total_level_2'].'</td></tr></table>';	
						break;
						
						case 'presentation':
						$html .= '<td><b>Presentation</b><br /><table class="ict_totals"><tr><td>Below Entry 3: </td><td>' . $totals[$key]['total_below_entry_3'] .'</td></tr><tr><td>Entry 3:</td><td>' . $totals[$key]['total_entry_3'] .'</td></tr><tr><td>Below Level 1:</td><td> '.$totals[$key]['total_below_level_1'].'</td></tr><tr><td>Level 1:</td><td> '.$totals[$key]['total_level_1'].'</td></tr><tr><td>Level 2:</td><td> '.$totals[$key]['total_level_2'].'</td></tr></table>';							
						break;
						
						case 'email':
						$html .= '<td><b>Email</b><br /><table class="ict_totals"><tr><td>Below Entry 3: </td><td>' . $totals[$key]['total_below_entry_3'] .'</td></tr><tr><td>Entry 3:</td><td>' . $totals[$key]['total_entry_3'] .'</td></tr><tr><td>Below Level 1:</td><td> '.$totals[$key]['total_below_level_1'].'</td></tr><tr><td>Level 1:</td><td> '.$totals[$key]['total_level_1'].'</td></tr><tr><td>Level 2:</td><td> '.$totals[$key]['total_level_2'].'</td></tr></table>';
						break;
						
						case 'general':
						$html .= '<td><b>General</b><br /><table class="ict_totals"><tr><td>Below Entry 3: </td><td>' . $totals[$key]['total_below_entry_3'] .'</td></tr><tr><td>Entry 3:</td><td>' . $totals[$key]['total_entry_3'] .'</td></tr><tr><td>Below Level 1:</td><td> '.$totals[$key]['total_below_level_1'].'</td></tr><tr><td>Level 1:</td><td> '.$totals[$key]['total_level_1'].'</td></tr><tr><td>Level 2:</td><td> '.$totals[$key]['total_level_2'].'</td></tr></table>';	
						break;
						
						case 'internet':
						$html .= '<td><b>Internet</b><br /><table class="ict_totals"><tr><td>Below Entry 3: </td><td>' . $totals[$key]['total_below_entry_3'] .'</td></tr><tr><td>Entry 3:</td><td>' . $totals[$key]['total_entry_3'] .'</td></tr><tr><td>Below Level 1:</td><td> '.$totals[$key]['total_below_level_1'].'</td></tr><tr><td>Level 1:</td><td> '.$totals[$key]['total_level_1'].'</td></tr><tr><td>Level 2:</td><td> '.$totals[$key]['total_level_2'].'</td></tr></table>';
						
					}
				}
				$html .= '</tr></table></div>';
				
				return $html;
				
		}
		
		public function removeDuplicateUsers($notify = FALSE, $usernames=array()) {
			
			// nkowald - 2011-08-22 - If usernames given, skip straight to that
			$duplicate_users = array();
			
			if (count($usernames) == 0) {
				// Find duplicate users
				$query1 = "SELECT userName, COUNT(userName) AS occurrences FROM bksb_Users GROUP BY userName HAVING (COUNT(userName) > 1)";
				$duplicate_users = array();
				
				if ($result = $this->connection->execute($query1)) {
					while (!$result->EOF) {
						$duplicate_users[] = array('username' => $result->fields['userName']->value, 'no_duplicates' => $result->fields['occurrences']->value);
						$result->MoveNext();
					}
				}
			} else {
				foreach ($usernames as $u_name) {
					$duplicate_users[] = array('username' => $u_name);
				}
			}
			
			if (count($duplicate_users) > 0) {
			
				$no_dupes_deleted = 0;
				foreach ($duplicate_users as $user) {

					// Check for valid user records existing with this valid $new_username username - we don't want to create duplicate user records
					$query2 = "SELECT user_id, userName, FirstName, LastName FROM dbo.bksb_Users WHERE userName = '".$user['username']."' ORDER BY user_id DESC";
				
					if ($result = $this->connection->execute($query2)) {
						$dupe_users = array();
						while (!$result->EOF) {
							$dupe_users[] = array(
								'user_id' => $result->fields['user_id']->value, 
								'userName' => $result->fields['userName']->value, 
								'firstname' => $result->fields['firstname'], 
								'lastname' => $result->fields['lastname']
							);
							$result->MoveNext();
						}
					}

					$delete_ids = array();
					$i = 0;
					foreach ($dupe_users as $duser) {
						// Put every duplicate value AFTER the first occurrence in an array to delete
						if ($i > 0){
							$delete_ids[] = $duser['user_id'];
						}
						$i++;
					}
					if (count($delete_ids) > 0) {
						// Remove duplicates
						
						foreach ($delete_ids as $id) {
							$sql = "DELETE FROM dbo.bksb_Users WHERE user_id = '$id'";
							if ($result = $this->connection->execute($sql)) {
								$no_dupes_deleted++;
							}
						}
						
					}
					
				}

				// Display how many duplicate users were removed
				if ($notify === TRUE) {
					$user_txt = ($no_dupes_deleted > 1) ? 'users' : 'user';
					echo "<pre>Removed $no_dupes_deleted duplicate $user_txt</pre>";
				}
				
			}
			
		}
		
		
		public function updateBksbData($old_username='', $new_username='', $firstname='', $lastname='') {
			if ($old_username != '' && $new_username != '' && $firstname != '' && $lastname != '') {
			
				// Find which bksb tables contain this username and need to be updated
				$user_exists = $this->checkMatchingUsername($old_username);
				$updated = TRUE;
					
				// bksb_Users
				if ($user_exists[0] === TRUE) {			
					$query = "UPDATE dbo.bksb_Users SET userName = '$new_username', FirstName = '$firstname', LastName = '$lastname' WHERE (userName = '$old_username')";
					if (!$result = $this->connection->execute($query)) {
						echo "Query failed: $query <br />";
						$updated = FALSE;
					}
					
				}
				// bksb_GroupMembership
				if ($user_exists[1] === TRUE) {
					$gm_query = "UPDATE dbo.bksb_GroupMembership SET UserName = '$new_username' WHERE UserName = '$old_username'";
					if (!$gm_result = $this->connection->execute($gm_query)) {
						echo "Query failed: $gm_query <br />";
						$updated = FALSE;
					}
				}
				// bksb_IAResults
				if ($user_exists[2] === TRUE) {
					$ia_query = "UPDATE dbo.bksb_IAResults SET UserName = '$new_username' WHERE UserName = '$old_username'";
					if (!$ia_result = $this->connection->execute($ia_query)) {
						echo "Query failed: $ia_query <br />";
						$updated = FALSE;
					}
				}
				// bksb_ICTIAResults
				if ($user_exists[3] === TRUE) {
					$ictia_query = "UPDATE dbo.bksb_ICTIAResults SET UserName = '$new_username' WHERE UserName = '$old_username'";
					if (!$ictia_result = $this->connection->execute($ictia_query)) {
						echo "Query failed: $ictia_query <br />";
						$updated = FALSE;
					}
				}
				// bksb_Sessions
				if ($user_exists[4] === TRUE) {
					$sess_query = "UPDATE dbo.bksb_Sessions SET userName = '$new_username' WHERE userName = '$old_username'";
					if (!$sess_result = $this->connection->execute($sess_query)) {
						echo "Query failed: $sess_query <br />";
						$updated = FALSE;
					}
				}
				
				// Remove any duplicate users
				// nkowald - 2011-08-22 - put new username into an array for the remove dupes functions
				$duplicate_users[] = $new_username;
				$this->removeDuplicateUsers(FALSE, $duplicate_users);
				
				// Finished updating
				if ($updated === TRUE) {
					return true;
				} else {
					return false;
				}
				
			} else {
				return false;
			}
		}
		
		// Get user ids of E-Learning Technologists
		// Check if given user is an E-learning "technologist"
		public function is_elt($userid = '') {
			if ($userid != '') {
				$query = "SELECT DISTINCT userid FROM mdl_role_assignments WHERE roleid = (SELECT id FROM mdl_role WHERE shortname = 'elearningtechnologist')";
				$user_ids = array();
				if ($users = get_records_sql($query)) {
					foreach ($users as $user) {
						$user_ids[] = $user->userid;
					}
				}
				if (in_array($userid, $user_ids)) {
					return TRUE;
				} else {
					return FALSE;
				}
			} else {
				return FALSE;
			}
		}
		
		public function findBksbUserName($idnumber='', $forename='', $surname='', $dob='', $postcode='') {

            $username = '';
            $forename = str_replace("'", "''", $forename);
            $surname = str_replace("'", "''", $surname);
            $postcode = str_replace(' ', '', $postcode);

            // Try to match on idnumber first
            $query = "SELECT userName FROM dbo.bksb_Users WHERE userName = '$idnumber'";	
			if ($result = $this->connection->execute($query)) {
				while (!$result->EOF) {
					$username = $result->fields['userName']->value;
					$result->MoveNext(); //move on to the next record
				}
                if ($username != '') {
                    return $username;
                }
			}
            $query = "SELECT userName FROM dbo.bksb_Users WHERE FirstName = '$forename' AND LastName = '$surname' ORDER BY user_id DESC";	
            if ($result = $this->connection->execute($query)) {
                $usernames = array();
                while (!$result->EOF) {
                    $usernames[] = $result->fields['userName']->value;
                    $result->MoveNext(); //move on to the next record
                }
                if (count($usernames) > 0) {
                    return $usernames;
                }
            }
            // change format of dob
            $query = "SELECT userName FROM dbo.bksb_Users WHERE (REPLACE(PostcodeA, ' ', '') = '$postcode') AND (CONVERT(VARCHAR(10), DOB, 103) = '$dob')";
            if ($result = $this->connection->execute($query)) {
                while (!$result->EOF) {
                    $username = $result->fields['userName']->value;
                    $result->MoveNext(); //move on to the next record
                }
                if ($username != '') {
                    return $username;
                }
            }
            // If we get here, we haven't found a match so return false
            return false;
        }


        public function __destruct() {

            $this->connection->Close();
            if (count($this->errors) > 0) {

                echo '<div style="color:red;">';
                echo "<h2>Errors</h2>";
                echo '<ul>';
                foreach($this->errors as $error) {
                    echo "<li>$error</li>";
                }
                echo '</ul>';
                echo '</div>';

            }

            $this->errors[] = array();
        }

    }
    // class statsEnhanced
?>
