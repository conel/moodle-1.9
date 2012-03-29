<?php

	include_once('../../config.php');
	
	if (isset($_SESSION['sms'])) {
		// Delete username cookies
		// setcookie('sms_username','',1,'/');
		unset($_SESSION['sms']);
	}
	
	if (isset($_SERVER['HTTP_REFERER']) && $_SERVER['HTTP_REFERER'] != '') {
	
		$referer = $_SERVER['HTTP_REFERER'];
		// If errors query string present, remove it
		$errors_start = (strpos($referer,'&errors=')) ? strpos($referer,'&errors=') : strlen($referer);
		$redirect_url = substr($referer,0,$errors_start);
		
		header('location: '.$redirect_url.'');
		exit;
		
	} else {
	
		echo 'Invalid access attempt - <a href="/">Go Home</a>';
		exit;
		
	}
?>