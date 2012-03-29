<?php
// initialise moodle
require_once('../../config.php');

global $CFG, $SAVESTUDENTS;

// include the necessary DB library
require_once ($CFG->dirroot.'/lib/adodb/adodb.inc.php');

$SAVESTUDENTS = "";

function get_moodle_id($ebs_id) {
	global $CFG;

	$sql = "SELECT  * 
			FROM    {$CFG->prefix}user
			WHERE	idnumber = '{$ebs_id}' or username = '{$ebs_id}'";

	return get_record_sql($sql);
}

function get_academic_year($academicyear) {
	global $CFG;
	
	$sql 	= 	"SELECT		*
				 FROM 		{$CFG->prefix}academic_years
				 WHERE		ac_year_name = '{$academicyear}'";

	
	$year_rec	=	get_record_sql($sql);
	
	return (!empty($year_rec)) ? $year_rec->ac_year_code : NULL;
}

function module_record_exists($student_id,$module_code,$year,$term) {
	global $CFG;
	
	$sql = "SELECT	*
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
	for($i=0;$i < 3; $i++) {
		$record->term = $i + 1;
		
		if ($record->module_code == 'ESGMSLB1-0DA11A/ESL' || $record->module_code == 'ESGMSLB1-0DA11A/ICT') {
			
			$SAVESTUDENTS .=implode("','",$record);
			
		}
		
		if (!module_record_exists($record->mdl_student_id,$record->module_code,$record->academic_year,$record->term)) {
			insert_record('module_complete',$record);
		} 
	}
	
	return NULL;
}

$mis = NewADOConnection('oci8');

$mis->SetFetchMode(ADODB_FETCH_ASSOC);

if ($mis->Connect('ebs.conel.ac.uk', 'ebsmoodle', '82814710', 'fs1')) {

	//Select all records from the moodle_subject_report
	$subjectreports	=		$mis->execute(" SELECT 	* 
											FROM 	FES.MOODLE_TUTOR_REPORT ");
							
	$exception =	"'ACADEMIC_YEAR','MODULE_CODE','TUTOR_NAME','EBS_STUDENT_ID','EBS_TUTOR_ID', \n";
												
	if (!empty($subjectreports))  {
		$sublog = "tutor reports started ".date('d-m-y H:i:s');
		echo $sublog;
		write_file($sublog,'tutorlog.txt');
	
		$records_count = 0;
		$created_records = 0;
		$exception_records = 0;
		foreach ($subjectreports as $report) {
			
			$student_id = get_moodle_id($report['EBS_STUDENT_ID']);
			$tutor_id	=	get_moodle_id($report['EBS_TUTOR_ID']);
			
			if (!empty($student_id) && !empty($tutor_id)) {
			
				$r = new object();
				$r->module_code	=	$report['DESCRIPTION'];
				$r->tutor_name	= 	$report['TUTOR_NAME'];
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
		$sublog = " <br />\n  tutor reports finished ".date('d-m-y H:i:s');
		$sublog .= "<br />\n $records_count records \n
					<br />\n $created_records records created \n
					<br />\n $exception_records records where not created";
		
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