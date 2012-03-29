<?php
//ini_set('error_reporting', E_ALL);
//ini_set('display_errors', 1);

require_once('BKSBReporting.class.php');
$max_rows = 800;

	if (isset($_POST['submit'])) {
	
     $fname = $_FILES['sel_file']['name']; 
     $chk_ext = explode(".", $fname);

     if(strtolower($chk_ext[1]) == "csv") {
     
         $filename = $_FILES['sel_file']['tmp_name'];
         $handle = fopen($filename, "r");
		 
		 $ins_counter = 0;
         $users = array();
         while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
		 
             if ($ins_counter > 0) {
                 if ($data[0] != '') {
                    $idnumber = $data[0];
                    $forename = $data[1];
                    $surname = $data[2];
                    $dob = $data[3];
                    $postcode = $data[4];

                    $users[$ins_counter]['idnumber'] = $idnumber;
                    $users[$ins_counter]['forename'] = $forename;
                    $users[$ins_counter]['surname']  = $surname;
                    $users[$ins_counter]['dob']      = $dob;
                    $users[$ins_counter]['postcode'] = $postcode;
                 }
             }

            $ins_counter++;
		 }
    
         fclose($handle);
		 $message = "Successfully read ".number_format($ins_counter)." records";
     } else {
		 $message = "Invalid File";
     }    

     function export_csv($users, $part = false) {

        $max_rows = 800;

        $bksb = new BKSBReporting();
         if ($part !== false && is_numeric($part)) {
             // nkowald - 2012-12-22 - If part is set, cut the array down to size based on part selected
             $no_parts = round((count($users) / $max_rows), 0); 
             $start = 0;
             if ($part > 1) {
                $start = (($part - 1) * $max_rows);
             }
             $end = ($start + $max_rows);
             if ($part == $no_parts) {
                $end = count($users);
             }
             // Finally cut down the users array with start and end values
             $users = array_slice($users, $start, $end);
         }

         // Now handle these users
         if (count($users) > 0) {
            foreach ($users as $key => $value) {
                // Look for a match in BKSB
                if ($bksb_username = $bksb->findBksbUserName($value['idnumber'], $value['forename'], $value['surname'], $value['dob'], $value['postcode'])) {
                    // match found: lets get IA results   
                    // Get all categories to check
                    foreach ($bksb->ass_cats as $cat) {
                        if (is_array($bksb_username)) {
                            foreach ($bksb_username as $username) {
                                $results = $bksb->getUserResultForCat($cat, $username);
                                if ($results) {
                                    $users[$key]['bksb_reference'] = $username;
                                    break;
                                }
                            }
                        } else {
                            $users[$key]['bksb_reference'] = $bksb_username;
                            $results = $bksb->getUserResultForCat($cat, $bksb_username);
                        }
                        $users[$key][$cat] = ($results != '') ? strip_tags($results) : '';
                    }
                } else {
                    $users[$key]['bksb_reference'] = 'Not Found';
                    foreach ($bksb->ass_cats as $cat) {
                        $users[$key][$cat] = '';
                    }
                }
            }
         }

         // Finally, let's see what's in our array of users: should be as before plus IA details
        $csv_output = "PERSON_CODE,FORENAME,SURNAME,DATE_OF_BIRTH,POSTCODE,BKSB_REFERENCE," . strtoupper(implode(',', $bksb->ass_cats)) . "\n"; 
        foreach ($users as $user) {
            $csv_output .= implode(',', $user) . "\n";
        }
        /*
        echo '<pre>';
        var_dump($csv_output);
        echo '</pre>';
        exit;
         */
        $filename = "IA_Results_".date("Y-m-d", time());
        if ($part !== false && is_numeric($part)) {
            $filename .= '_'.$part.'-of-'.$no_parts;
        }
        header("Content-type: application/vnd.ms-excel");
        header("Content-disposition: csv" . date("Y-m-d") . ".csv");
        header( "Content-disposition: filename=".$filename.".csv");
        print $csv_output;
        exit; 

    }

    $large_export = false;

    if (isset($_POST['part']) && is_numeric($_POST['part'])) {
        // Export CSV based on chosen part
        export_csv($users, $_POST['part']);

    } else {

        // nkowald - 2011-12-22 - If there's more than $max_rows records, let the user choose which $max_rows to get
        if (count($users) <= $max_rows) {
            export_csv($users);
        } else {
            $large_export = true;
        }
    }
}
	
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="en-AU" xml:lang="en-AU">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta name="robots" content="noindex" />
<meta name="googlebot" content="noindex" />
<script type="text/javascript" src="/VLE/theme/conel/jquery-1.4.2.min.js"></script>
<title>BKSB Initial Assessment Resuts CSV Exporter</title>
</head>

<body style="font-family:Arial, Helvetica, Sans-serif;">
<script type="text/javascript">
//<![CDATA[
$(document).ready(function() {

	$("#form_import").submit(function(e) {
        if ($("#file_upload").val() == '') {
            alert('Please select an input CSV');
            return false;
        }
    });

});

//]]>
</script>
<div id="holder">
<?php
	if (isset($message) && $message != '') {
		echo '<p class="status">'.$message.'</p>';
	}
?>
	<h2>Export BKSB Initial Assessment Results</h2>
<fieldset><legend>IA Results</legend>
<form action="<?php echo $_SERVER["PHP_SELF"];?>" method="post" enctype="multipart/form-data" id="form_import">
<table id="add_csv">
	<tr>
		<td class="label" valign="top">Input CSV file:</td>		
		<td><input type="file" name="sel_file" id="file_upload" /><br /></td>		
	</tr>
<?php
    if (isset($large_export) && $large_export === true) {
?>
    <tr>
        <td>&nbsp;</td>
        <td>
        <p>CSV files containing more than <?php echo $max_rows; ?> rows may timeout. <br />
           <b>Please reselect this input CSV and choose a range to export.</b></p>
<?php
        // Number of parts come from number of rows. 
        $no_parts = round(count($users) / $max_rows, 0); 
        $start = 1;
        for ($i=1; $i <= $no_parts; $i++) {
            $end = ($i == $no_parts) ? count($users) : $i * $max_rows;
            echo '<input type="radio" name="part" value="'.$i.'" id="part_'.$i.'"><label for="part_'.$i.'"><b>'.$i.'.</b> ('.number_format($start).' - '.number_format($end).')</label> <br />';
            $start = $end + 1;
        }
?>
        <br />
        </td>
    </tr>
<?php
    } 
?>
	<tr>
		<td>&nbsp;</td>		
		<td><input type="submit" name="submit" value="Get CSV with IA Results" class="submit" /></td>		
	</tr>
</table>
</fieldset>
</form>
<div class="key">
<h3>Formats</h3>
    <p class="key" style="font-size:0.9em; margin-bottom:0px; padding-bottom:0;"><strong>Input CSV:</strong> idnumber, forename, surname, dob, postcode</p>
    <p class="key" style="font-size:0.9em; margin-top:4px;"><strong>Output CSV:</strong> idnumber, forename, surname, dob, postcode, bksb reference, english, maths, word, powerpoint, email, database, excel, publisher, internet</p>
<h3>About</h3>
<p>Exports all found Initial Assessment results (eng, math, ICT) where users from the input CSV were matched on either:</p>
<ol>
    <li>ID Number</li>
    <li>Forename and Surname</li>
    <li>Date of Birth and Postcode</li>
</ol>
<p>
<h3>Verifying Data</h3>
<p>In the output CSV, the BKSB_REFERENCE field - along with firstname and lastname - gives you enough information to login to BKSB and verify the data.</p>
<strong>URL:</strong> <a href="http://bksb2/bksb_Portal/" target="_blank">http://bksb2/bksb_Portal/</a><br />
<strong>Reference</strong> = BKSB_REFERENCE<br />
<strong>First Name</strong> = FORENAME<br />
<strong>Last Name</strong> = SURNAME<br />
</p>
</div>
</body>
</html>
