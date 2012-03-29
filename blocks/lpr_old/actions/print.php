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

// include the pdf converter
require_once("./dompdf/dompdf_config.inc.php");

// fetch the filter params
$category_id = optional_param('category_id', null, PARAM_INT);
$learner_id = optional_param('learner_id', null, PARAM_INT);
$start_date = optional_param('start_date', null);
$end_date = optional_param('end_date', null);
$foldername = optional_param('folder', null);
$single = optional_param('single', 0, PARAM_BOOL);

// convert into american style date to resolve the unix-timestamp
$date = explode('/', $start_date);
$start_time = !empty($date[2]) ? mktime(0, 0, 0, $date[1], $date[0], $date[2]) : null;
$date = explode('/', $end_date);
$end_time = !empty($date[2]) ? mktime(0, 0, 0, $date[1], $date[0]+1, $date[2]) : null;

// include the LPR databse library
require_once("{$CFG->dirroot}/blocks/lpr/models/block_lpr_db.php");

// instantiate the lpr db wrapper
$lpr_db = new block_lpr_db();

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
                // get the LPR data
                $lprs = $lpr_db->get_lprs_for_print_by_idnumber($idnumbers, $start_time, $end_time);
            }
            fclose($handle);
            unlink($_FILES['csv_file']['tmp_name']);
        }
    }
} else {
    // use the filter params
    $lprs = $lpr_db->get_lprs_for_print($category_id, $learner_id, $start_time, $end_time);
}

// get the module config
$config = get_config('project/lpr');

$learners = array();

//$navlinks[] = array('name' => "Processing PDF Reports", 'link' => '', 'type' => 'title');
//$navigation = build_navigation($navlinks);
//print_header_simple('Processing PDF Reports', '',$navigation);

if(!empty($lprs)) {

    // seperate them into learner groups
    foreach($lprs as $lpr) {
        $learners[$lpr->idnumber]['id'] = $lpr->learner_id;
        $learners[$lpr->idnumber]['lprs'][] = $lpr->id;
    }

    echo ($single == 0)? "<h2 class='main'>Printing ".(count($learners))." Reports</h2>": null;

    $complete = 0;
    $incomplete = 0;
    $skipped = 0;
    $totalcount = 0;

    date_default_timezone_set('Europe/London');

    // increase the memory limit so the program doesn't crash
    ini_set('memory_limit', '1000M');

    // create the PDFs
    foreach($learners as $filename => $group) {
        // N.B. set_time_limit() renews the timeout every time it is called,
        // which means we don't need an arbitrarily large number here
        set_time_limit(40);

        $ids = $group['lprs'];
        $learner_id = $group['id'];

        $totalcount++;

        // get the learner record
        $user = get_record('user', 'id', $learner_id);
        echo ($single == 0)? "{$totalcount}. ".(fullname($user))." #{$filename}... ": null;
        flush();

        // url encode this array as the the actual PDF will be generated in a
        // totally seperate request thread
        $ids = urlencode(base64_encode(serialize($ids)));

        // N.B. becuase of a bug in DOMPdf we need to call this through a cURL
        // wrapper so we can recover from an otherwise fatal timeout error if
        // the HTML can't be processed

        $ch = curl_init($CFG->wwwroot."/blocks/lpr/actions/pdf.php?ids={$ids}&learner_id={$learner_id}&start_time={$start_time}&end_time={$end_time}");
		
		// nkowald - 2010-06-22 - Thought it may require proxy, but doesn't
		//curl_setopt($ch, CURLOPT_PROXY, PROXY_SERVER);
		//curl_setopt($ch, CURLOPT_PROXYPORT, PROXY_PORT);
		//curl_setopt($ch, CURLOPT_PROXYUSERPWD, PROXY_USERNAME .":". PROXY_PASSWORD);
		
		// nkowald - 2010-06-22 - Needs these CURL options for it to work on LIVE
		curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
		curl_setopt($ch, CURLOPT_USERPWD, $_SERVER['AUTH_USER'].":".$_SERVER['AUTH_PASSWORD']);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
		curl_setopt($ch, CURLOPT_CAINFO, getcwd() . "/GlobalSignRootCA.crt");
		
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 40); // if the request takes longer than this then assume failure
		
        // capture the HTML output for the current document
        $output = curl_exec($ch);
		
        // check the error conditions
        if(curl_errno($ch) != '') {
            $error = curl_error($ch);
            echo "<b>Fatal error</b>: {$error}<br/>\n";
            curl_close($ch);
            flush();
            $skipped++;
            continue;
        } elseif(preg_match('/Fatal error.+/', $output, $matches) != 0) {
            // TODO the above check should be simpler to avoid running a preg_match
            // over 100MB+ of data when we're printing lots of reports
            echo "{$matches[0]}<br/>\n";
            curl_close($ch);
            flush();
            $skipped++;
            continue;
        }
        curl_close($ch);

        // check if the report is complete within this date range
        if(!$lpr_db->is_ilp_complete($learner_id, $start_time, $end_time)) {
            $incomplete++;
        } else {
            $complete++;
        }

        // make the directory, if necessary
        if(!is_dir("{$config->pdf_path}/{$foldername}")) {
            mkdir("{$config->pdf_path}/{$foldername}", 0777, true);
        }

        // save the PDF to disk
		if($single == 0) {
			$fp = fopen("{$config->pdf_path}/{$foldername}/{$filename}.pdf", "w");
			fwrite($fp, $output);
			fclose($fp);
		
			// check how long it took to render and save
			echo "done!<br/>\n";
			flush();
		} else {
			$learner = get_record('user', 'id', $learner_id);
		
			// nkowald - 2010-06-22 - Re-adding these headers to get around IE not exporting bug
			header('Cache-Control: private');
			header('Pragma: ');
			
			// We'll be outputting a PDF
			header('Content-type: application/pdf');

			// It will be called downloaded.pdf
			header('Content-Disposition: attachment; filename="'.$learner->idnumber.'.pdf"');

			echo $output;
			exit;
		}
    }

    echo "<br/><b>Generated {$complete} complete PDF report(s), {$incomplete} "
        ."incomplete report(s), and skipped {$skipped} report(s) that could not be printed.</b>";
    ?>
    <div id="redirect">
        <div id="continue">
            ( <a href="<?php echo $CFG->wwwroot; ?>/blocks/lpr/actions/export.php">Continue</a> )
        </div>
    </div>
    <?php
    //print_footer();
} else {

    echo "<h2 class='main'>No matching LPRs.</h2>";
    redirect("{$CFG->wwwroot}/blocks/lpr/actions/export.php", "Processing complete.", 5);
}
?>