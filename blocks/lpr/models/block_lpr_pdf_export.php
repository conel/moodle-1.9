<?php
	class PdfExporter 
    
	{
        public $errors;
		public $CFG;
		public $USER;
		public $lpr_db;

        public $config;
        public $remove_pdfs_after_print;

        public function __construct() {

			global $CFG, $USER;
			
            // include needed Moodle files - just in case
            require_once('../../../config.php');

			// include the LPR databse library
			require_once("{$CFG->dirroot}/blocks/lpr/models/block_lpr_db.php");
			$this->lpr_db = new block_lpr_db(); // instantiate the lpr db wrapper

            // include php mailer
            //require_once('phpmailer.inc.php');
            require_once('class.phpmailer.php');

            // set up property for holding error messages
            $this->errors = array();

            $this->config = get_config('project/lpr');
			
            $this->CFG = $CFG;

            // If this is TRUE, it will delete PDFs once emailed
            $this->remove_pdfs_after_print = FALSE;

        } // __construct()
		

		public function getNextJobID() {
			$query = "SELECT DISTINCT job_id FROM mdl_pdf_exports WHERE status = 0 ORDER BY date_added, id LIMIT 1";
			if ($results = get_records_sql($query)) {
				foreach ($results as $result) {
					$job_id = $result->job_id;
				}
				return $job_id;
			} else {
				$this->errors[] = 'No PDF exports in the queue';
				return FALSE;
			}
		}
		
		public function getLPRsForJobID($job_id = 0) {
			
			if ($job_id != 0) {
				
				// Get all records from this job id to print
				$query = "SELECT * FROM mdl_pdf_exports WHERE job_id = $job_id AND status = 0 ORDER BY date_added, id";
				if ($records = get_records_sql($query)) {

                    $no_records = 0;
                    foreach ($records as $record) {
                        $no_records++;
                    }

					if ($no_records > 1) {
						// Multiple
						$learner_ids = array();
						foreach ($records as $record) {
							$learner_ids[] = $record->learner_id;
							$start_time = $record->start_date;
							$end_time = $record->end_date;
						}
						$lprs = $this->lpr_db->get_lprs_for_print_by_idnumber($learner_ids, $start_time, $end_time);
						
					} else {
						// Single
						foreach ($records as $record) {
							$learner_id = $record->learner_id;
							$category_id = $record->category_id;
							$start_time = $record->start_date;
							$end_time = $record->end_date;
						}

						$lprs = $this->lpr_db->get_lprs_for_print($category_id, $learner_id, $start_time, $end_time);
					}
					
					return $lprs;
					
				} else {
					$this->errors[] = "No print jobs found for the given job id: $job_id";
					return FALSE;
				}
			} else {
				$this->errors[] = "No job id provided";
				return FALSE;
			}
		}
		
		
		 public function mailPDFs($email_to, $email_from, $email_subject, $email_msg, $files) {
	        
            $host = 'ADMIN-EXCH.conel.ac.uk';

			// Defaults if givens are blank
			$email_to       = ($email_to != '') ? $email_to : 'NKowald@staff.conel.ac.uk';
			$email_from     = ($email_from != '') ? $email_from : 'e-LearningTeam@staff.conel.ac.uk';
			$email_subject  = ($email_subject != '') ? $email_subject : 'LPR PDF Export';
			$email_msg      = ($email_msg != '') ? $email_msg : '';

            $mail = new PHPMailer(true);
            $mail->IsSMTP();

            if ($email_msg != '') {
                // HTML message head
                $html_msg_head = '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
                <html>
                <head><title>PDF Export</title>
                <style type="text/css">
                body {
                    font-family:Arial, Helvetica, sans-serif;
                    font-size:13px; 
                    line-height:1.3em;
                }
                .incomplete {
                    color:red;
                }
                </style>
                </head>
                <body>';                        
                $html_msg_footer = '</body></html>';

                $email_msg = $html_msg_head . $email_msg . $html_msg_footer;
            }

            try {
                $mail->Host = $host;
                $mail->SMTPDebug = 0; // 1 = errors and messages, 2 = messages only
                $mail->AddAddress($email_to);
                $mail->SetFrom($email_from, 'Moodle');
                $mail->AddReplyTo($email_from, 'Moodle');
                $mail->Subject = $email_subject;
                $mail->AltBody = 'To view this message, please use an HTML compatible email viewer';
                $mail->MsgHTML($email_msg);
                if ($files != '') {
                    foreach ($files as $pdf) {
                        $mail->AddAttachment($pdf);
                    }
                }
                $mail->Send();

            } catch (phpmailerException $e) {
                echo $e->errorMessage(); // Pretty error msg
            } catch (Exception $e) {
                echo $e->getMessage();
            }

		}

        public function handleFailedJob($job_id) {
            if ($job_id != '') {
                // Get job details
                $query = "SELECT * FROM mdl_pdf_exports WHERE job_id = $job_id";
                if ($jobs = get_records_sql($query)) {
                    $learners = array();
                    foreach ($jobs as $job) {
                        $email_to = $job->email;
                        $learners[] = $job->learner_id;
                    }
                    if (count($learners) > 1) {
                        $email_subject = "Failed LPR PDF Exports";
                    } else {
                        $email_subject = "Failed LPR PDF Export";
                    }

                    $email_msg = '
                    <p>The following PDF exports failed to print. Please try printing them again.</p>
                    <p>If you continue to have problems exporting these PDFs contact the e-learning team at <a href="mailto:e-LearningTeam@staff.conel.ac.uk" target="_blank">e-LearningTeam@staff.conel.ac.uk</a> providing them with the learner ids of the failed PDF exports below.</p>
                    <h3>Failed PDF Exports</h3>
                    <ul>';
                    foreach ($learners as $learner) {
                        $user = get_record('user', 'idnumber', $learner);
                        $name = $user->firstname . " " . $user->lastname;
                        $email_msg .= "<li>Learner ID: $learner &ndash; $name</li>";
                    }
                    $email_msg .= '</ul>';

                    // Send a "job failed" email
                    $this->mailPDFs($email_to, '', $email_subject, $email_msg, '');

                    // Finally, delete job
                    $query = "DELETE FROM mdl_pdf_exports WHERE job_id = $job_id";
                    execute_sql($query, false);

                    return true;
                }
            } else {
                return false;
            }
        }

        public function getNoLearnersFromJobID($job_id) {
            $query = "SELECT * FROM mdl_pdf_exports WHERE job_id = $job_id AND learner_id != 0";
            if ($learners = get_records_sql($query)) {
                return $learners;
            } else {
				$query = "DELETE FROM mdl_pdf_exports WHERE learner_id = 0";
				execute_sql($query, false);
				return false;
			}
        }

        public function setJobAsRunning($job_id, $learner_id) {
            $query = "UPDATE mdl_pdf_exports SET status = 1 WHERE job_id = $job_id AND learner_id = $learner_id";
            execute_sql($query, false);
        }

        public function completeJob($job_id) {
            $query = "DELETE FROM mdl_pdf_exports WHERE job_id = $job_id and status = 1";
            execute_sql($query, false);
        }
		
		public function generatePDFs() {
            $time_started = time();

            // Only run if already running
            if (!$exists = record_exists('pdf_exports', 'status', 1)) {
				
                // Get next job ID in queue
                $job_id = $this->getNextJobID();

                if ($job_id) {

                    $learners_in_job = $this->getNoLearnersFromJobID($job_id);
					// JavaScript error checking should stop learner_ids of 0 coming through
					if (!$learner_in_job) {
						$job_id = $this->getNextJobID();
						$learners_in_job = $this->getNoLearnersFromJobID($job_id);
					}
					
                    $single = (count($learners_in_job) == 1) ? 1 : 0;
                    
                    if ($job_id !== FALSE) {
                        $lprs = $this->getLPRsForJobID($job_id);
                    } else {
                        $lprs = NULL;
                    }

                    if(!empty($lprs)) {
					
                        // seperate them into learner groups
                        foreach($lprs as $lpr) {
                            $learners[$lpr->idnumber]['id'] = $lpr->learner_id;
                            $learners[$lpr->idnumber]['lprs'][] = $lpr->id;
                        }

                        //echo ($single == 0)? "<h2 class='main'>Printing ".(count($learners))." Reports</h2>": null;

                        $complete = 0;
                        $incomplete = 0;
                        $skipped = 0;
                        $totalcount = 0;

                        date_default_timezone_set('Europe/London');

                        // increase the memory limit so the program doesn't crash
                        //ini_set('memory_limit', '1000M');
                        // nkowald - 2010-11-29
                        ini_set('memory_limit', '2000M');

                        // create the PDFs
                        $exported_pdfs = array();
                        $error_pdfs = array();
                        $complete_pdfs = array();

                        foreach($learners as $filename => $group) {

                            // Set up arrays for errors, completes and incompletes
                            // get idnumber from user's moodle id
                            $moodle_id = $group['id'];
                            $mdl_id = get_record('user', 'id', $moodle_id);
                            // Get meta details about the PDF export: email, category, folder etc.
                            $idnumber = ($single) ? $mdl_id->id : $mdl_id->idnumber;
                            $this->setJobAsRunning($job_id, $idnumber);
                            $query = "SELECT email, category_id, folder_name, start_date, end_date FROM mdl_pdf_exports WHERE job_id = $job_id AND learner_id = $idnumber";

                            if ($pdf_user = get_records_sql($query)) {
                                foreach($pdf_user as $job) {
                                    $start_time = $job->start_date;
                                    $end_time = $job->end_date;
                                    $foldername = $job->folder_name;
                                    $email_to = $job->email;
                                }
                            } else {
                                // Should NOT export if can't find the job
                                break;
                            }

                            // N.B. set_time_limit() renews the timeout every time it is called,
                            // which means we don't need an arbitrarily large number here
                            //set_time_limit(40);

                            $ids = $group['lprs'];
                            $learner_id = $group['id'];

                            $totalcount++;

                            //flush();

                            // url encode this array as the the actual PDF will be generated in a
                            // totally seperate request thread
                            $ids = urlencode(base64_encode(serialize($ids)));

                            // N.B. becuase of a bug in DOMPdf we need to call this through a cURL
                            // wrapper so we can recover from an otherwise fatal timeout error if
                            // the HTML can't be processed
                            
                            $export_url = $this->CFG->wwwroot . "/blocks/lpr/actions/pdf.php?ids={$ids}&learner_id={$learner_id}&start_time={$start_time}&end_time={$end_time}";
							$ch = curl_init($export_url);
							$cert_url = $this->CFG->wwwroot . "/blocks/lpr/actions/GlobalSignRootCA.crt";

                            // nkowald - 2010-06-22 - Needs these CURL options for it to work on LIVE
							// nkowald - 2010-06-22 - Thought it may require proxy, but doesn't
							curl_setopt($ch, CURLOPT_PROXY, PROXY_SERVER);
							curl_setopt($ch, CURLOPT_PROXYPORT, PROXY_PORT);
							curl_setopt($ch, CURLOPT_PROXYUSERPWD, PROXY_USERNAME .":". PROXY_PASSWORD);
                            curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
                            //curl_setopt($ch, CURLOPT_USERPWD, $_SERVER['AUTH_USER'].":".$_SERVER['AUTH_PASSWORD']);
                            curl_setopt($ch, CURLOPT_USERPWD, AUTH_USER.':'.AUTH_PASS);
                            //curl_setopt($ch, CURLOPT_USERPWD, 'CONEL\sszabo:conel123');
							curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
                            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
                            curl_setopt($ch, CURLOPT_CAINFO, $cert_url);
                            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                            curl_setopt($ch, CURLOPT_TIMEOUT, 40); // if the request takes longer than this then assume failure
		
                            // capture the HTML output for the current document
                            $output = curl_exec($ch);

                            // check the error conditions
                            if(curl_errno($ch) != '') {
                                $error = curl_error($ch);
                                // get learner details for friendly error message
                                $user = get_record('user', 'id', $learner_id);
                                // Update pdf export table marking as error (3)
                                $query = "UPDATE mdl_pdf_exports SET status = 3 WHERE job_id = $job_id and learner_id = $learner_id";
                                execute_sql($query, false);

                                $error_pdfs[$filename] = "<b>Error generating a PDF for ".$user->firstname . " " . $user->lastname." ($learner_id)</b><br /><span style=\"color:red;\">$error</span><br />
								<br />This is usually caused by too much LPR data for the PDF generator to handle. A smaller date range for this learner may export successfully.";
                                curl_close($ch);
                                flush();
                                $skipped++;
                                continue;
                            }
                            
                            curl_close($ch);

                            // check if the report is complete within this date range
                            $user = get_record('user', 'id', $learner_id);

                            if(!$this->lpr_db->is_ilp_complete($learner_id, $start_time, $end_time)) {
                                $complete_pdfs[$filename] = $filename . '.pdf (<span class="incomplete">Incomplete LPR</span>) &ndash; '. $user->firstname .  ' ' . $user->lastname;
                                $incomplete++;
                            } else {
                                $complete++;
                                $complete_pdfs[$filename] = $filename . '.pdf &ndash; '. $user->firstname .  ' ' . $user->lastname;
                            }

                            // make the directory, if necessary
                            if(!is_dir("{$this->config->pdf_path}/{$foldername}")) {
                                mkdir("{$this->config->pdf_path}/{$foldername}", 0777, true);
                            }

                            // save the PDF to disk
                            $filepath = $this->config->pdf_path . "/" . $foldername . "/" . $filename . ".pdf";
                            $fp = fopen($filepath, "w");
                            fwrite($fp, $output);
                            fclose($fp);

                            flush();

                            // Add PDF to generated array
                            $exported_pdfs[] = $filepath;

                            // Delete this entry from the table
                            $query = "DELETE FROM mdl_pdf_exports WHERE learner_id = $idnumber AND job_id = $job_id";
                            execute_sql($query, false);
                        }
                        
                        // Finished creating PDFs - Email PDF to recipient
                        $email_msg = '';
                        $email_msg .= ($complete != 0) ? "Generated {$complete} complete PDF report(s).<br />" : "";
                        $email_msg .= ($incomplete != 0) ? "Generated {$incomplete} incomplete PDF report(s).<br />" : "";
                        $email_msg .= ($skipped != 0) ? "Skipped {$skipped} report(s) that could not be printed.<br />" : "";

                        if (count($error_pdfs) > 0) {
                            $email_msg .= "<h3>Could Not Print</h3>";
                            $email_msg .= "<ul>";
                            foreach ($error_pdfs as $error) {
                                $email_msg .= "<li>$error</li>";
                            }
                            $email_msg .= "</ul>";
                        }
                        if (count($complete_pdfs) > 0) {
                            $email_msg .= "<h3>Exported PDFs</h3>";
                            $email_msg .= "<ul>";
                            foreach ($complete_pdfs as $pdf) {
                                $email_msg .= "<li>$pdf</li>";
                            }
                            $email_msg .= "</ul>";
                        }
                        $time_finished = time();

                        $time_taken = $time_finished - $time_started;
                        if ($time_taken > 60) {
                            $time_taken_string = $time_taken / 60;
                            $time_taken_string = ($time_taken_string > 1) ? $time_taken_string . ' minutes' : $time_taken_string . ' minute';
                        } else {
                            $time_taken_string = $time_taken;
                            $time_taken_string = ($time_taken_string > 1) ? $time_taken_string . ' seconds' : $time_taken_string . ' second';
                        }
                        $num_pdfs = count($exported_pdfs);
                        $pdf_txt = ($num_pdfs > 1 || $num_pdfs == 0) ? 'PDFs' : 'PDF';
						
						// Only generate 'time taken' message when there's no errors
						if (count($error_pdfs) == 0) {
							$email_msg .= "<p style=\"color:#8C8C8C;\">Time taken to generate $num_pdfs $pdf_txt: $time_taken_string</p>";
						}

                        // Email the generated PDFs and general outcome.
                        $email_from = 'e-LearningTeam@conel.ac.uk';
						if (count($error_pdfs) > 0 && count($complete_pdfs) == 0) {
							$subject = "Failed PDF Export";
						} else {
							$subject = "LPR PDF Export";
						}
                        $files = $exported_pdfs;
                        $message = $email_msg;

                        $emailed = $this->mailPDFs($email_to, $email_from, $subject, $message, $files);
                        if ($emailed === FALSE) {
                            $this->errors[] = 'Sending email failed!';
                        } else {
                            if ($this->remove_pdfs_after_print === TRUE) {

                                /* Delete PDF from C:\inetpub\moodle\blocks\lpr\print */
                                // We want to delete PDFs after sent so they don't take up a lot of space

                                // Delete PDF file
                                foreach($files as $file) {
                                    if (is_file($file)) {
                                        unlink($file);
                                    }
                                }
                                // Delete folder if not 'singleprint'
                                $save_path = $this->config->pdf_path . "/" . $foldername;
                                if (is_dir($save_path)) {
                                    rmdir($save_path);
                                }

                            }
                        }
                        
                    } else {
                        // No matching LPRs
                        // Get the LPRS that didn't match then email the user a report
						
						$email_msg = '<h3>No Matching LPRs</h3>';
						if (count($learners_in_job) > 1) {
							$email_msg .= '<p>No matches were found for the following users and date range.</p>';
						} else {
							$email_msg .= '<p>No matches were found for the following user and date range.</p>';
						}
						$start_date = '';
						$end_date = '';

						foreach ($learners_in_job as $learner) {
							$start_date = $learner->start_date;
							$end_date = $learner->end_date;
						}
						if ($start_date != '' && $end_date != '') {
							$email_msg .= "<h5>Date Range: ".date('d/m/Y', $start_date)." to ".date('d/m/Y', $end_date)."</h5>";
						}
						$email_msg .= '<ul>';
						foreach ($learners_in_job as $learner) {
							$user = ($single) ? get_record('user', 'id', $learner->learner_id) : get_record('user', 'idnumber', $learner->learner_id);
							$name = $user->firstname . " " . $user->lastname;
							$email_msg .= "<li>Learner ID: ".$user->idnumber." &ndash; $name </li>";
							$email_to = $learner->email;
						}
						$email_msg .= '</ul>';

						$email_subject = "No Matching LPRs";

						// Send a "job failed" email
						$this->mailPDFs($email_to, '', $email_subject, $email_msg, '');

                        // Finally, delete job
                        $query = "DELETE FROM mdl_pdf_exports WHERE job_id = $job_id";
                        execute_sql($query, false);

                    }

                } else {
                    // There's no Exports to process
                    return false;
                }
                // Rerun process again.
                $this->generatePDFs();

            } else {
                // Finished running, stop execution
                $this->errors[] = 'PDF Exporting already running';
                return false;
            }

		}

        public function processPDFs() {
            // Run batch file so we can redirect script
            // solution found on this website: http://www.somacon.com/p395.php
            $WshShell = new COM("WScript.Shell");
            $oExec = $WshShell->Run("cmd /C D:\\moodle\\blocks\\lpr\\actions\\process-pdfs.bat", 0, false);
        }

        public function checkForBrokenExports() {

            // check that the running status is not due to a broken export - covering bases here, should always work
            // If a PDF is set as running and is more than 15 minutes old then we know it's broken - a PDF will never take longer than 15 minutes.
            $query = "SELECT job_id, date_added FROM mdl_pdf_exports WHERE status = 1 ORDER BY date_added";
            $date_added = '';
            if ($runnings = get_records_sql($query)) {
                foreach($runnings as $running) {
                    $job_id = $running->job_id;
                    $date_added = $running->date_added;
                    $date_now = time();
                    if ($date_added != '') {
                        // Check that the running date is less than 
                        if (($date_now - $date_added) > 900) {
                            // Running for 15 minutes now: there's a problem so let's delete it
                            if ($this->handleFailedJob($job_id)) {
                                // Start the PDF printing process again
                                $this->processPDFs();
                            }
                        }
                    }
                } 
            }
            
        }

		
		/***********************************
		*  __destruct
        *
		************************************/
        public function __destruct() {
			
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
?>
