<?php

class block_send_sms extends block_base {

	function init() {
		$this->title   = get_string("defaulttitle", 'block_send_sms'); 	
		$this->version = 2009072200;  
	}

	// This function allows you edit title block per use
	function specialization() {
		if(!empty($this->config->title)){
			$this->title = $this->config->title;
		} else {
			$this->config->title = 'Send SMS';
		}
	}
	
	function get_content() {
	
		if ($this->content !== NULL) {      
			return $this->content;    
		}
		
		// Cookie remembers username - though 'browser save' will override.
		$sms_username_cookie = (isset($_COOKIE['sms_username']) && $_COOKIE['sms_username'] != '') ? $_COOKIE['sms_username'] : '';
		
		// If user has logged in already, Show user details
		if ($_SESSION['sms']['logged_in'] == 'y') {
		
			$sms_username = $_SESSION['sms']['user'];
			$logged_in = '<div id="logged_in" class="smaller">';
			$logged_in .= '<p class="user_logout"><a href="'.$CFG->wwwroot.'/blocks/send_sms/send_sms_logout.php">logout</a></p>';
			$logged_in .= "<p>Logged in as: <strong>$sms_username</strong></p>";
			$logged_in .= '</div>';
			
		} else {
			
			// If errors exists, display them
			if (isset($_GET['errors']) && $_GET['errors'] == 'true') {
				$errors = ($_SESSION['sms']['errors']) ? $_SESSION['sms']['errors'] : '';
			}
			$errors_text = '';
			if ($errors != '') {
				$errors_text = '<div id="result" style="display:block;">';
				$errors_text .= '<strong class="error">Login Failed</strong><ul>';
				foreach($errors as $error) {
					$errors_text .= '<li>'.$error.'</li>';
				}
				$errors_text .= '</ul></div>';
			}
		
			// 2sms Login Form
			$login_form = '
			<div id="user_login"><p class="small">You must log in with your 2sms username and password.</p>
			'.$errors_text.'
			<form action="'.$CFG->wwwroot.'/blocks/send_sms/send_sms_login.php" method="post">
			<div>
				<label for="sms_username">2sms username:</label><br />
				<input type="text" name="sms_username" id="sms_username" value="'.$sms_username_cookie.'" /><br />
				<label for="sms_password">2sms password:</label><br />
				<input type="password" name="sms_password" id="sms_password" /><br />
				<input type="submit" value="Log in" class="sign_in" />
			</div>
			</form></div>
			';
		}
		
		// JavaScript text counter
		$js_function = '
			<script type="text/javascript" src="'.$CFG->wwwroot.'/blocks/send_sms/jquery-1.3.2.min.js"></script>
			<script type="text/javascript" src="'.$CFG->wwwroot.'/blocks/send_sms/functions.js"></script>
		';
		
		// Build web form to send SMS
		$web_form = '
			<div id="send_sms">
			<div id="result"></div>
			<form action="'.$CFG->wwwroot.'blocks/send_sms/send_sms_do.php" method="post" id="form_sms">
			<div>
				<label for="send_sms_mobile_numbers">Mobile Number(s):</label> <input type="text" name="mobile_numbers" id="send_sms_mobile_numbers" tabindex="1" /><br />
				<p class="smaller">Number(s) must be preceded by a "+" sign and the country code.
				<a href="'.$CFG->wwwroot.'/blocks/send_sms/mobile_format.html" target="_blank" class="smaller" id="view_format">More</a></p>
				<div id="instructions">
					<div>
						<strong>Mobile Number Format</strong><br />
						- Include <a href="http://www.thelist.com/countrycode.html" target="_blank">country code</a> preceded by a "+" sign<br />
						- Omit leading 0
						<span>
						<strong>Example:</strong><br />
						07711223344<br /> becomes<br /> +447711223344
						</span>
						<br />
						<strong>Multiple Recipients</strong><br />
						- Enter multiple numbers separated by comma<br />
					</div>
				</div>
				<label for="send_sms_message">Message:</label> <textarea name="message" rows="7" id="send_sms_message" wrap="physical" tabindex="2" onkeyup="breakUpMesssage(this, char_count);" onkeydown="breakUpMesssage(this, char_count);"></textarea>
				<input type="text" id="char_count" readonly="readonly" name="" value="160 / 1 credit" />
				<br />
				<input type="submit" value="Send SMS" id="send_sms_button" tabindex="3" />
			</div>
			</form>
			</div>
		';
		
		// If not logged in show login form
		if ($_SESSION['sms']['logged_in'] != 'y') {
			$block_content = $login_form . $js_function;
		} else {
			$block_content = $logged_in . $js_function . $web_form;
		}
		
		$this->content         = new stdClass;    
		$this->content->text   = $block_content;     
		$this->content->footer = '';
		
		return $this->content;  
	}
	
	function instance_allow_config() {
		return true;
	}

}
?>