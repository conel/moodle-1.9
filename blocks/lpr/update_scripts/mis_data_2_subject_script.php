<?php
// initialise moodle

// nkowald - 2011-02-18 - running from cron we need to use this
require_once(dirname(dirname(dirname(dirname(__FILE__)))).'/config.php'); // global moodle config file.
//require_once('../../../config.php');

global $CFG, $SAVESTUDENTS;

$SAVESTUDENTS = "";

// include the necessary DB library
require_once ($CFG->dirroot.'/lib/adodb/adodb.inc.php');

function get_moodle_id($ebs_id) {
	global $CFG;

	$sql = "SELECT id 
			FROM    {$CFG->prefix}user
			WHERE	idnumber = '{$ebs_id}' or username = '{$ebs_id}'";

	return get_record_sql($sql);
}

function module_record_exists($student_id,$module_code,$year,$term) {
	global $CFG;
	
	$sql = "SELECT	id 
			FROM	{$CFG->prefix}module_complete
			WHERE	ebs_student_id = '{$student_id}'
			AND		module_code = '{$module_code}'
			AND		academic_year = '{$year}'
			AND		term = {$term}";
			
	$res	=	get_record_sql($sql);
	
	return (empty($res)) ? false : true; 
}

function get_academic_year($academicyear) {
	global $CFG;
	
	$sql 	= 	"SELECT		ac_year_code 
				 FROM 		{$CFG->prefix}academic_years
				 WHERE		ac_year_name = '{$academicyear}'";
	
	$year_rec	=	get_record_sql($sql);
	
	return (!empty($year_rec)) ? $year_rec->ac_year_code : NULL;
}

function write_file($filecontents,$fname) {
	global $CFG;

	$filename = $CFG->dataroot.'/lpr/temp/'.$fname;

    if (is_writable($filename)) {
        // In our example we're opening $filename in append mode.
        // The file pointer is at the bottom of the file hence
        // that's where $html will go when we fwrite() it.
        if (!$handle = fopen($filename, 'a')) {
             echo "Cannot open file ($filename)";
             exit;
        }

        // Write $html to our opened file.
        if (fwrite($handle, $filecontents) === FALSE) {
            echo "Cannot write to file ($filename)";
            exit;
        }
        fclose($handle);
    } else {
        echo "The file $filename is not writable";
    }
}

function create_record($record) {
	for($i=0;$i < 3; $i++) {
		$record->term = $i + 1;
		
	if (!module_record_exists($record->ebs_student_id,$record->module_code,$record->academic_year,$record->term)) {
			insert_record('module_complete',$record);
		} 
	}
	
	return NULL;
}

$mis = NewADOConnection('oci8');
$mis->SetFetchMode(ADODB_FETCH_ASSOC);

if ($mis->Connect('ebs.conel.ac.uk', 'ebsmoodle', '82814710', 'fs1')) {

	$year = date('Y');
	$four_digit_ac_year = 'Academic Year ' . $year . '/' . sprintf('%4d', ($year + 1));
	
	$now = time();
	$query = "SELECT ac_year_name FROM mdl_academic_years WHERE ac_year_start_date < $now and ac_year_end_date > $now";
	if ($ac_years = get_records_sql($query)) {
		foreach($ac_years as $year) {
		   $four_digit_ac_year = $year->ac_year_name; 
		}
	}
	
	//Select all records from the moodle_subject_report
	$subjectreports	= $mis->execute("SELECT * FROM FES.MOODLE_SUBJECT_REPORT WHERE ACADEMIC_YEAR = '$four_digit_ac_year' AND MODULE_CODE != 'Tutorial'");
	//where ROWNUM < 10
	$exception = "'ACADEMIC_YEAR','MODULE_CODE','TUTOR_NAME','EBS_STUDENT_ID','EBS_TUTOR_ID', \n";
												
	if (!empty($subjectreports))  {
		$sublog = "subject reports started ".date('d-m-y H:i:s')." \n";
		echo $sublog;
		write_file($sublog,'sublog.txt');
	
		$records_count = 0;
		$created_records = 0;
		$exception_records = 0;
		$duplicate_records = 0;
		
		foreach ($subjectreports as $report) {
			
			$student_id = get_moodle_id($report['EBS_STUDENT_ID']);
			
			// nkowald - 2011-11-17 - In EBS there's a few records like G3143 etc. This function strips all letters from these ids
			//$tutor_id	=	get_moodle_id($report['EBS_TUTOR_ID']);
			$ebs_tut_id = filter_var($report['EBS_TUTOR_ID'], FILTER_SANITIZE_NUMBER_INT);
			$tutor_id	= get_moodle_id($ebs_tut_id);
			
			$ebs_tutor_id = '';
			$mdl_tutor_id = '';
			
			// Tutor_id might still be FALSE because EBS_TUTOR_ID is something weird and long like 399058 (Schnell Smith) instead of 4583
			if ($tutor_id == false) {
				// Match on staff, firstname and surname (case insensitive)
				$casei_name = strtolower($report['TUTOR_NAME']);
				$query = "SELECT id, idnumber FROM mdl_user WHERE CONCAT(lower(firstname), ' ', lower(lastname)) = '$casei_name'";
				if ($matches = get_records_sql($query)) {
					$num_matches = count($matches);
					if ($num_matches == 1) {
						foreach ($matches as $match) {
							$mdl_tutor_id = $match->id;
							$ebs_tutor_id = ($match->idnumber != '') ? $match->idnumber : $report['EBS_TUTOR_ID'];
						}
					}
				}
			} else {
				$mdl_tutor_id = $tutor_id->id;
				$ebs_tutor_id = $report['EBS_TUTOR_ID'];
			}

			if (!empty($student_id) && !empty($mdl_tutor_id)) {
			
				$r = new object();
				$r->module_code	=	$report['MODULE_CODE'];
                // nkowald - 2011-02-18 - Added single quote escaping as it was failing here
				$r->tutor_name		= 	addslashes($report['TUTOR_NAME']);
				$r->ebs_student_id	= 	$report['EBS_STUDENT_ID'];
				$r->ebs_tutor_id	= 	$ebs_tutor_id;
				$r->mdl_student_id	= 	$student_id->id;
				$r->mdl_tutor_id	= 	$mdl_tutor_id;
				$r->academic_year	=	get_academic_year($report['ACADEMIC_YEAR']);
				create_record($r);
				$created_records++;
				
			} else {
				$exception_records++;
				$exception .= "'".implode("','",$report)."', \n";
			}
			$records_count++;
		}
		$sublog = " \n  subject reports finished ".date('d-m-y H:i:s');
		$sublog .= "\n $records_count records \n
					\n $created_records records created \n
					\n $exception_records records where not created";
		
		write_file($sublog,'sublog.txt');
	}
		
	if (!empty($SAVESTUDENTS)) {
		write_file($SAVESTUDENTS,'save_students.txt');
	}	
		
	if (!empty($exception)) {
		write_file($exception,'subjectreport_exceptions.txt');
	}
}

?>