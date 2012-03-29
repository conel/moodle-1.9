<?php

	// Needed to start session and import proxy details
	include_once('../../config.php');
	
	// Initialise array that will hold errors
	$errors = array();
	// reset errors session variable
	$_SESSION['sms']['errors'] = '';
	
	$referer = ($_SERVER['HTTP_REFERER'] != '') ? $_SERVER['HTTP_REFERER'] : '';
	if ($referer != '') {
		// Need to strip previous "errors=true" text from query string if it exists
		$errors_start = (strpos($referer,'&errors=')) ? strpos($referer,'&errors=') : strlen($referer);
		$redirect_url = substr($referer,0,$errors_start);
	}
	
	// Only login if being posted to
	if (isset($_POST['sms_username']) && isset($_POST['sms_password'])) {
		
		$user = $_POST['sms_username'];
		$pass = $_POST['sms_password'];
				
		if ($user == '') $errors[] =  "2sms username not entered";
		if ($pass == '') $errors[] =  "2sms password not entered";

		$result = '';
		
		$myOutMsg =   '<?xml version="1.0" encoding="UTF-8" ?>';
		$myOutMsg .=  '<Request xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" ';
		$myOutMsg .=  'xsi:noNamespaceSchemaLocation="http://schema.2sms.com/2.0/schema/0010_RequestAccountVerify.xsd" ';
		$myOutMsg .=  'Version="1.0">';
		$myOutMsg .=  '<Identification>';
		$myOutMsg .=  '<UserID>'.$user.'</UserID>';
		$myOutMsg .=  '<Password>'.$pass.'</Password>';
		$myOutMsg .=  '</Identification>';
		$myOutMsg .=  '<Service>';
		$myOutMsg .=  '<ServiceName>AccountVerify</ServiceName>';
		$myOutMsg .=  '<ServiceDetail/>';
		$myOutMsg .=  '</Service>';
		$myOutMsg .=  '</Request>';

		if (function_exists('curl_init')) {
			
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, 'http://www.2sms.com/xml/xml.jsp');
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_TIMEOUT, 30);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
			curl_setopt($ch, CURLOPT_PROXY, PROXY_SERVER);
			curl_setopt($ch, CURLOPT_PROXYPORT, PROXY_PORT);
			curl_setopt($ch, CURLOPT_PROXYUSERPWD, PROXY_USERNAME .":". PROXY_PASSWORD);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $myOutMsg);
			$result = curl_exec($ch);
			if (curl_errno($ch)) {
				echo "<h1>Error".curl_error($ch)."</h1>";
			}
			curl_close($ch);

		} else {
		
			echo "Curl Not Found. Using sockets...\r\n\r\n";
			$postdata = "POST /xml/xml.jsp HTTP/1.0\r\n";
			$postdata .="Host: www.2sms.com\r\n";
			$postdata .="Content-length: " . strlen($myOutMsg) . "\r\n" ;
			$postdata .="Content-Type: text/xml\r\n";
			$postdata .="Connection: Close\r\n\r\n";
			$postdata .="$myOutMsg\r\n";

			echo $postdata;

			$fp = fsockopen('www.2sms.com', 80, $errno, $errstr, 30);
			if (!$fp){
				echo "ERROR:" . $errno . "-" . $errstr . "<br>";
			} else {
				socket_set_timeout($fp, 30);
				fputs ($fp,$postdata);

				while (!feof($fp)) {
					$result .= fgets($fp, 1024);
				}
				fclose($fp);
			}

		}
		
		if ($result == 1 or strcmp('<ErrorCode>00</ErrorCode>', $result)) {
			
			// Convert result to simpleXML so we can easily retrieve result data
			$xml = new SimpleXMLElement($result);
			
			// ErrorCode 03: Invalid account details
			if ($xml->Error->ErrorCode != 00) {
				
				// Build final errors array - double quotes make error value a string and not an object reference
				$errors[] = "".$xml->Error->ErrorReason."";

				// Store errors erray in a session so we can show errors on redirect page
				$_SESSION['sms']['errors'] = $errors;

				// Redirect to previous screen, showing error
				$redirect_url = $redirect_url . "&errors=true";
				
				header('location: '.$redirect_url.'');
				exit;

			} else {
				
				// Valid login details
				
				/* Save Cookie */
				// Calculate 6 months into the future
				// Format: seconds * minutes * hours * days + current time
				$in_six_months = 60 * 60 * 24 * 181 + time(); 
				setcookie('sms_username', $user, $in_six_months, '/');
				$_SESSION['sms']['logged_in'] = 'y';
				$_SESSION['sms']['user'] = $user;
				$_SESSION['sms']['pass'] = $pass;
		
				header('location: '.$_SERVER['HTTP_REFERER'].'');
				exit;
				
			}

		} // if $result exists
		
	} else {
				
		/* No POST data: redirect */
		if (isset($_SERVER['HTTP_REFERER']) && $_SERVER['HTTP_REFERER'] != '') {
		
			$errors[] = "No Login Details provided";
			$_SESSION['sms']['errors'] = $errors;
			
			// Redirect to previous screen, showing error
			$redirect_url = $redirect_url . "&errors=true";
			header('location: '.$redirect_url);
			exit;
			
		} else {
		
			// Someone has just entered in the URL of this page: Do Nothing
			echo 'Invalid access attempt - <a href="/">Go Home</a>';
			exit;
			
		}
	}
?>