<?php
/**
 * Renders a set of LPRs as PDFs.
 *
 * @copyright &copy; 2009 University of London Computer Centre
 * @author http://www.ulcc.ac.uk, http://moodle.ulcc.ac.uk
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package LPR
 * @version 1.0
 */
// initialise moodle
require_once('../../../config.php');

// using these globals
global $SITE, $CFG, $USER;

// include the permissions check
require_once("{$CFG->dirroot}/blocks/lpr/access_content.php");

if(!$can_print) {
    error("You do not have permission to print LPRs");
}

// include the pdf export class
require_once("{$CFG->dirroot}/blocks/lpr/models/block_lpr_pdf_export.php");
// include the pdf converter
// Replace with latest version of dompdf once everythings working 
require_once("./dompdf/dompdf_config.inc.php");

// fetch the filter params
$category_id = optional_param('category_id', null, PARAM_INT);
$learner_id = optional_param('learner_id', null, PARAM_INT);
$start_date = optional_param('start_date', null);
$end_date = optional_param('end_date', null);
$foldername = optional_param('folder', null);
$single = optional_param('single', 0, PARAM_BOOL);
$email = optional_param('email', $USER->email, PARAM_RAW);

// convert into american style date to resolve the unix-timestamp
$date = explode('/', $start_date);
$start_time = !empty($date[2]) ? mktime(0, 0, 0, $date[1], $date[0], $date[2]) : null;

$date = explode('/', $end_date);
$end_time = !empty($date[2]) ? mktime(0, 0, 0, $date[1], $date[0]+1, $date[2]) : null;

// include the LPR databse library
require_once("{$CFG->dirroot}/blocks/lpr/models/block_lpr_db.php");

// instantiate the lpr db wrapper
$lpr_db = new block_lpr_db();

// instantiate the pdf_export class
$pdf = new PdfExporter();

// is there a CSV with idnumbers
if(!empty($_FILES['csv_file'])) {
    if(!empty($_FILES['csv_file']['name'])) {
        // lets open it
        if (($handle = fopen($_FILES['csv_file']['tmp_name'], "r")) !== FALSE) {
            // make sure the column header we need is there
            $titlerow = fgetcsv($handle, 10000, ",");
            $index = array_search('PERSON_CODE', $titlerow);
            if($index !== false) {
                // fetch all the idnumbers
                $idnumbers = array();
                while (($data = fgetcsv($handle, 10000, ",")) !== FALSE) {
                    $idnumbers[] = $data[$index];
                }
				$unixtime = time();
				$rand = rand(1,99999);
				$job_id = substr($unixtime, -5, 5) . $rand;
				
				// Insert each record into the print queue table.
				foreach ($idnumbers as $idnumber) {
					// nkowald - 2011-06-06 - Blank ID numbers were getting through, this should fix that
					if ($idnumber != 0 && is_numeric($idnumber)) {
						$query = "INSERT INTO mdl_pdf_exports (job_id, email, category_id, learner_id, folder_name, start_date, end_date, date_added) VALUES('$job_id', '$email', '$category_id', '$idnumber', '$foldername', '$start_time', '$end_time', '$unixtime')";
						execute_sql($query, false);
					}
				}
				
            }
            fclose($handle);
            unlink($_FILES['csv_file']['tmp_name']);
        }
    }
} else {
     // use the filter params
	$unixtime = time();
	$rand = rand(1,99999);
	$job_id = substr($unixtime, -5, 5) . $rand;
	// nkowald - 2011-06-06 - Blank ID numbers were getting through, this should fix that
	if ($learner_id != 0 and is_numeric($learner_id)) {
		$query = "INSERT INTO mdl_pdf_exports (job_id, email, category_id, learner_id, folder_name, start_date, end_date, date_added) VALUES('$job_id', '$email', '$category_id', '$learner_id', '$foldername', '$start_time', '$end_time', '$unixtime')";
		execute_sql($query, false);
	}
}

// Add the "running" check here to further cut down on processes
if (!$exists = record_exists('pdf_exports', 'status', 1)) {
    $pdf->processPDFs();
} else {
    /* Job is currently running */
    $pdf->checkForBrokenExports();
}

echo "<h2 class='main'>LPR PDF Exports Added to Queue</h2>";
$redirect_url = ($single) ? $CFG->wwwroot . '/blocks/lpr/actions/export.php?single=1' : $CFG->wwwroot . '/blocks/lpr/actions/export.php';
redirect($redirect_url, "You will receive your PDF(s) by email (<b>$email</b>) shortly.", 4);

?>