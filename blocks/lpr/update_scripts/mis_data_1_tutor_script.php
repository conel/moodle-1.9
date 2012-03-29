<?php
// initialise moodle

/*
if (isset($_SERVER['REMOTE_ADDR'])) {
    error_log("should not be called from web server!");
    exit;
}
*/

// nkowald - 2011-02-18 - running from cron we need to use this
require_once(dirname(dirname(dirname(dirname(__FILE__)))).'/config.php'); // global moodle config file.

//require_once('../../../config.php');

global $CFG, $SAVESTUDENTS;

// include the necessary DB library
require_once ($CFG->dirroot.'/lib/adodb/adodb.inc.php');

$SAVESTUDENTS = "";

function get_moodle_id($ebs_id) {
	global $CFG;
	// nkowald - 2011-10-04 - Only get Moodle id (less expensive query)
	//$sql = "SELECT * FROM {$CFG->prefix}user WHERE idnumber = '{$ebs_id}' or username = '{$ebs_id}'";
	$sql = "SELECT id FROM {$CFG->prefix}user WHERE idnumber = '{$ebs_id}' or username = '{$ebs_id}'";
	return get_record_sql($sql);
}

function get_academic_year($academicyear) {
	global $CFG;
	$sql 	= 	"SELECT		ac_year_code 
				 FROM 		{$CFG->prefix}academic_years
				 WHERE		ac_year_name = '{$academicyear}'";
	
	$year_rec	=	get_record_sql($sql);
	return (!empty($year_rec)) ? $year_rec->ac_year_code : NULL;
}

function module_record_exists($student_id, $module_code, $year, $term) {
	global $CFG;
	
	$sql = "SELECT	id 
			FROM	{$CFG->prefix}module_complete
			WHERE	mdl_student_id = '{$student_id}'
			AND		module_code = '{$module_code}'
			AND		academic_year = '{$year}'
			AND		term = {$term}";
			
	$res	=	get_record_sql($sql);
	
	return (empty($res)) ? false : true; 
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
	for($i=0; $i < 3; $i++) {
		$record->term = $i + 1;
		
		if ($record->module_code == 'ESGMSLB1-0DA11A/ESL' || $record->module_code == 'ESGMSLB1-0DA11A/ICT') {
			$SAVESTUDENTS .=implode("','",$record);
		}
		if (!module_record_exists($record->mdl_student_id, $record->module_code, $record->academic_year, $record->term)) {
			insert_record('module_complete',$record);
		}
	}
	
	return NULL;
}

$mis = NewADOConnection('oci8');
$mis->SetFetchMode(ADODB_FETCH_ASSOC);

if ($mis->Connect('ebs.conel.ac.uk', 'ebsmoodle', '82814710', 'fs1')) {

	// Select all records from the MOODLE_TUTOR_REPORT
	$year = date('Y');
	$four_digit_ac_year = 'Academic Year ' . $year . '/' . sprintf('%4d', ($year + 1));
	
	$now = time();
	$query = "SELECT ac_year_name FROM mdl_academic_years WHERE ac_year_start_date < $now and ac_year_end_date > $now";
	if ($ac_years = get_records_sql($query)) {
		foreach($ac_years as $year) {
		   $four_digit_ac_year = $year->ac_year_name; 
		}
	}

	$subjectreports	= $mis->execute("SELECT EBS_STUDENT_ID, EBS_TUTOR_ID, DESCRIPTION, TUTOR_NAME, ACADEMIC_YEAR FROM FES.MOODLE_TUTOR_REPORT WHERE ACADEMIC_YEAR = '".$four_digit_ac_year."'");					
	$exception =	"'ACADEMIC_YEAR','MODULE_CODE','TUTOR_NAME','EBS_STUDENT_ID','EBS_TUTOR_ID', \n";
												
	if (!empty($subjectreports)) {
		$sublog = "tutor reports started ".date('d-m-y H:i:s')." \n";
		echo $sublog;
		write_file($sublog,'tutorlog.txt');
	
		$records_count = 0;
		$created_records = 0;
		$exception_records = 0;
		foreach ($subjectreports as $report) {
			
			$student_id = get_moodle_id($report['EBS_STUDENT_ID']);
			//$tutor_id	=	get_moodle_id($report['EBS_TUTOR_ID']);
			// nkowald - 2011-11-17 - In EBS there's a few records like G3143 etc. This function strips all letters from these ids
			$ebs_tut_id = filter_var($report['EBS_TUTOR_ID'], FILTER_SANITIZE_NUMBER_INT);
			$tutor_id	= get_moodle_id($ebs_tut_id);
			
			if (!empty($student_id) && !empty($tutor_id)) {
			
				$r = new object();
				$r->module_code		=	$report['DESCRIPTION'];
                // nkowald - 2011-02-18 - Adding single quote escaping
				$r->tutor_name		= 	addslashes($report['TUTOR_NAME']);
				$r->ebs_student_id	= 	$report['EBS_STUDENT_ID'];
				$r->ebs_tutor_id	= 	$report['EBS_TUTOR_ID'];
				$r->mdl_student_id	= 	$student_id->id;
				$r->mdl_tutor_id	= 	$tutor_id->id;
				$r->academic_year	=	get_academic_year($report['ACADEMIC_YEAR']);
				create_record($r);
				$created_records++;
			} else {
				$exception_records++;
				$exception .= "'".implode("','",$report)."', \n";
			}
			$records_count++;
		}
		$sublog = " \n  tutor reports finished ".date('d-m-y H:i:s') . "\n";
		$sublog .= "\n $records_count records \n
					\n $created_records records created \n
					\n $exception_records records where not created \n";
		
		write_file($sublog,'tutorlog.txt');
	}
		
	if (!empty($SAVESTUDENTS)) {
		write_file($SAVESTUDENTS,'save_students.txt');
	}
	
	if (!empty($exception)) {
		write_file($exception,'tutorreport_exceptions.txt');
	}
	
}

?>