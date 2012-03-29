<?php 
//  Users entered incorrect IDs when they used BKSB
//  This page allows staff to update user details

    require_once('../../config.php');
    //require_once('block_ilp_lib.php');
	include('../ilp/access_context.php');

    include_once('BksbReporting.class.php');
    $bksb = new BksbReporting();

    global $GFG, $USER;

	$old_username 	= optional_param('old_username', '', PARAM_RAW);
	$new_username 	= optional_param('new_username', '', PARAM_RAW);
	$firstname 		= optional_param('firstname', '', PARAM_RAW);
	$lastname 		= optional_param('lastname', '', PARAM_RAW);
	$action 		= optional_param('action', '', PARAM_RAW);
	
    require_login();

	$sitecontext = get_context_instance(CONTEXT_SYSTEM);
	
    if (has_capability('moodle/site:doanything',$sitecontext)) {  // are we god ?
        $access_isgod = 1 ;
    } else {
		error('Not enough access to view this page');
	}
	
	if ($action == 'update') {
	
		$error = FALSE;
		$errors = array();
		
		//$old_username = trim($old_username);
		$new_username = trim($new_username);
		$firstname = trim($firstname);
		$lastname = trim($lastname);
		
		/* Validate data */
		
		// No fields can be blank
		if ($old_username == '' || $new_username == '' || $firstname == '' || $lastname == '') {
			$error = TRUE;
			$errors[] = 'All fields are required';
		}
		
		// Username can only be numbers
		if (!is_numeric($new_username)) {
			$error = TRUE;
			$errors[] = 'Username must only contain numbers';
		}
		
		// Check it's a valid idnumber by looking it up in mdl_user
		/* Removed this on Scott's request
		if (!$exists = get_record('user', 'idnumber', $new_username)) {
			$error = TRUE;
			$errors[] = 'No username match in Moodle';	
		}
		*/
		
		// Check firstname isn't numeric
		if (is_numeric($firstname)) {
			$error = TRUE;
			$errors[] = 'Invalid firstname';
		}
		
		// Check lastname isn't numeric
		if (is_numeric($lastname)) {
			$error = TRUE;
			$errors[] = 'Invalid lastname';
		}
		
		// Uppercase words in first and lastname
		$firstname = ucwords($firstname);
		$lastname  = ucwords($lastname);
		
		// Let's assume the data is valid, now we can update it in our tables
		if ($error === FALSE) {
		
			$update = $bksb->updateBksbData($old_username, $new_username, $firstname, $lastname);
			
			if ($update) {
				echo "Success!";
			} else {
				$errors[] = "SQL Update failed";
			}
		
		}
		
	} // If form posted

if (isset($errors) && count($errors) > 0) {
	echo '<div class="errors">';
	echo '<b>Errors</b>';
	echo '<ul>';
	foreach ($errors as $error) {
		echo "<li>$error</li>";
	}
	echo '</ul>';
	echo '</div>';
}
?>