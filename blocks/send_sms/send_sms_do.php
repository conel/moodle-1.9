<?php
	include_once('../../config.php');
	
	// Only used if being posted to and logged in as an authenticated 2sms user
	if (isset($_POST) && ($_SESSION['sms']['logged_in'] == 'y')) {
	
		// Initialise array that will hold errors
		$errors = array();
		
		$user = (isset($_SESSION['sms']['user']) && $_SESSION['sms']['user'] != '') ? $_SESSION['sms']['user'] : '';
		$pass = (isset($_SESSION['sms']['pass']) && $_SESSION['sms']['pass'] != '') ? $_SESSION['sms']['pass'] : '';
		
		if ($user == '') $errors[] =  "2sms username not provided";
		if ($pass == '') $errors[] =  "2sms password not provided";
		
		// Check valid username
		if (!filter_var($user, FILTER_VALIDATE_EMAIL)) {
			$errors[] =  "Invalid username";
		}
		
		// Multiple messages are supported so we can remove this stripping to 160 chars
		//$text = substr($_POST['message'], 0, 160);
		$text = $_POST['message'];
		$text = utf8_encode($text);
		
		if ($text == '') { $errors[] = "Please enter a Message"; }
		
		// Check for multiple numbers
		$mobnum = str_replace(' ','',$_POST['mobile_numbers']);
		$numbers = explode(',',$mobnum);
		$no_numbers = count($numbers);
		
		// Check for valid mobile number
		foreach($numbers as $number) {
			// Need to do this, not sure why - regex fails otherwise
			$number = $number;
			// Test number
			$reg_exp = "/^\++[0-9]*$/";
			$valid = preg_match($reg_exp,$number);
			if (!$valid) {
				$entered = ($number != '') ? "($number)" : "(No number entered)";
				$errors[] = "Invalid mobile number $entered";
			}
		}
		
		// If there are errors show them - outputted as a list
		if (count($errors) > 0) {
			$html_errors = "<h2>Errors</h2>";
			$html_errors .= "<ul>";
			foreach($errors as $error) {
				$html_errors .= "<li>$error</li>";
			}
			$html_errors .= "</ul>";
			echo $html_errors;
			exit;
		}
		
		$result = '';
		
		$no_sends = 0;
		foreach($numbers as $mobnum) {

			// Sanitise mobile number: Remove spaces - needed to handle ',' and ', ' delimiters
			$mobnum = str_replace(' ','',$mobnum);

			$myOutMsg =  '<?xml version="1.0" encoding="UTF-8" ?>';
			$myOutMsg .= '<Request xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" ';
			$myOutMsg .= 'xsi:noNamespaceSchemaLocation="http://schema.2sms.com/1.0/0410_RequestSendMessage.xsd" ';
			$myOutMsg .= 'Version="1.0">';
			$myOutMsg .= '<Identification>';
			$myOutMsg .= '<UserID>' .$user.'</UserID>';
			$myOutMsg .= '<Password>'.$pass.'</Password>';
			$myOutMsg .= '</Identification>';
			$myOutMsg .= '<Service>';
			$myOutMsg .= '<ServiceName>SendMessage</ServiceName>';
			$myOutMsg .= '<ServiceDetail>';
			$myOutMsg .= '<SingleMessage>';
			$myOutMsg .= '<Destination>'.$mobnum.'</Destination>';
			$myOutMsg .= '<Text>'.$text.'</Text>';
			$myOutMsg .= '</SingleMessage>';
			$myOutMsg .= '</ServiceDetail>';
			$myOutMsg .= '</Service>';
			$myOutMsg .= '</Request>';

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
					print "<h1>Error".curl_error($ch)."</h1>";
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
			
			$no_sends++;
		
		} //foreach
		
		if ($result == 1 or strcmp('<ErrorCode>00</ErrorCode>', $result)) {
			
			// Convert result to simpleXML so we can easily retrieve result data
			$xml = new SimpleXMLElement($result);
			
			/* see everything returned by xml formatted result
				echo '<pre>';
				var_dump($xml);
				echo '</pre>';
			*/
			
			// If error occured, display error
			if ($xml->Error->ErrorCode != 00) {
				echo "<strong>Message Failed</strong><br />";
				echo $xml->Error->ErrorReason;
			} else {
				// Message Sent
				echo "<strong>Message Sent!</strong><br />";
				$credit = $xml->ResponseData->Detail->CreditsRemaining;
				$credit = ($credit > 1) ? $credit : '0';
				$credit_txt = ($credit == '1.0') ? 'credit' : 'credits';
				if ($no_sends > 1) {
					echo "Message sent to $no_sends people.<br />";
				}
				echo "You have $credit $credit_txt remaining.";
			}

		} else {
			echo 'We <strong>failed</strong> to send your code';
		}
		
	} else {
				
		// No POST data: redirect
		if (!isset($_SESSION['sms']['logged_in'])) {
			
			echo "<strong>You are not logged in</strong><br />Please refresh your browser then login.";
			
		} else {
		
			// Someone has just entered in the URL of this page: Do Nothing
			echo 'Invalid access attempt - <a href="/">Go Home</a>';
			exit;
			
		}
	}
?>