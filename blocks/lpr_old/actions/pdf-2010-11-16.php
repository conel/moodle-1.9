<?php
/**
 * Displays a set of Learner Progress Reviews, for conversion to PDF format.
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

// include the LPR databse library
require_once("{$CFG->dirroot}/blocks/lpr/models/block_lpr_db.php");

// include the connection code for CONEL's MIS db
require_once($CFG->dirroot.'/blocks/lpr/models/block_lpr_conel_mis_db.php');

// include the LPR library
require_once("{$CFG->dirroot}/blocks/lpr/block_lpr_lib.php");

// include the pdf converter
require_once("./dompdf/dompdf_config.inc.php");

// instantiate the lpr db wrapper
$lpr_db = new block_lpr_db();

// instantiate the lpr MIS db wrapper
$conel_db = new block_lpr_conel_mis_db();

$lprs = array();

$ids = unserialize(base64_decode(urldecode(optional_param('ids'))));
$learner_id = optional_param('learner_id');
$start_time = optional_param('start_time');
$end_time = optional_param('end_time');

// get all the data for each LPR
foreach($ids as $id) {
    if(!empty($id)) {
        $lprs[$id] = new stdClass();
        // fetch the necessary data
        $lprs[$id]->lpr = $lpr_db->get_lpr($id);
        $lprs[$id]->course = get_record('course', 'id', $lprs[$id]->lpr->course_id);
        $lprs[$id]->tutor = $lpr_db->get_tutor($learner_id);
        $lprs[$id]->lecturer = get_record('user', 'id', $lprs[$id]->lpr->lecturer_id);
        $lprs[$id]->answers = $lpr_db->get_indicator_answers($id);
        $lprs[$id]->attendance = $lpr_db->get_attendance($id);
        $lprs[$id]->category = get_record("course_categories", "id", $lprs[$id]->course->category);
		$lprs[$id]->modules = $lpr_db->get_modules($id, true);
    }
}

// get the learner record
$learner = get_record('user', 'id', $learner_id);

$attendance = $conel_db->get_attendance_qual_avg($learner->idnumber);

// get the tutor reviews
$reviews = $lpr_db->get_tutor_reviews($learner_id, $start_time, $end_time);

ob_start();
// now that we've got all the data we need, display the HTML
require("{$CFG->dirroot}/blocks/lpr/views/pdf.html");
$html = ob_get_contents();
ob_end_clean();

// convert that output into PDF
$dompdf = new DOMPDF();
$dompdf->load_html($html);
$dompdf->render();
// echo it back to the script that called this
echo $dompdf->output();
?>