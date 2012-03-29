// The following four functions are taken from the 2sms 'send sms' page on the 2sms site
function resetMessageBoxToMaxLength(ctrlToAdjust, maxLength) {
		var messageToAdjust = ctrlToAdjust.value;
		if ( messageToAdjust.length > maxLength ) {
			ctrlToAdjust.value = messageToAdjust.substring(0, maxLength);
		}
}

function breakUpMesssage(ctrlToValidate, ctrlForResult) {

	if (ctrlToValidate != undefined) {
		var messageToBreak = ctrlToValidate.value;

		if ( messageToBreak.length > 160 ) {
			var totalParts = 1;
			while (messageToBreak.length > 145) {
				totalParts++;
				splitLength = findFirstPartLength(messageToBreak, 145, 100);
				messageToBreak = messageToBreak.substring(splitLength);
			}
			ctrlForResult.value = (145 - messageToBreak.length) + ' / ' + totalParts + ' credits';
		}
		else {
			ctrlForResult.value = (160 - messageToBreak.length) + ' / 1 credit';
		}
	}
}

function findFirstPartLength(inputString, maxPartLength, minPartLength) {
	var myLength = -1;

	if (inputString.length <= maxPartLength) {
		myLength = inputString.length;
	}
	else if (isWhiteSpace(inputString.charAt(maxPartLength))) {
		myLength = maxPartLength;
	}
	else {
		var index = maxPartLength - 1;

		while (!isWhiteSpace(inputString.charAt(index)) && index >= minPartLength) {
			index--;
		}
		if ( (index < minPartLength) && !isWhiteSpace(inputString.charAt(index)) ) {
			myLength = maxPartLength;
		}
		else {
			myLength = (index + 1);
		}
	}
	return(myLength);
}
function isWhiteSpace(myChar) {
	var retVal = false;
	if (myChar == '\t') { retVal = true; }
	else if (myChar == '\n') { retVal = true; }
	else if (myChar == '\f') { retVal = true; }
	else if (myChar == '\r') { retVal = true; }
	else if (myChar == ' ')  { retVal = true; }
	return retVal;
}		

function ltrim(string) {
	return string.replace(/^\s+/,"");
}
function rtrim(string) {
	return string.replace(/\s+$/,"");
}

// Allows jQuery use with other JavaScript libraries
jQuery.noConflict();

jQuery(document).ready(function(){

	// Insert block styles into the head of the page
	var sms_styles = '<link rel="stylesheet" type="text/css" href="/blocks/send_sms/send_sms.css" />';
	jQuery('head').prepend(sms_styles);
	
	jQuery('#form_sms').submit(function(event){
	
		event.preventDefault();
		
		// Validate Mobile Number
		var mob_num_string = jQuery('#send_sms_mobile_numbers').val();
		
		// Mobile number can't be blank
		if (mob_num_string == '') {
			alert("Please enter a Mobile Number");
			jQuery('#send_sms_mobile_numbers').focus();
			return false;
		}
		
		// Split multiple numbers by comma
		var mob_nums = mob_num_string.split(',');
		
		for (var i in mob_nums) {

			var mob_number = ltrim(mob_nums[i]);
			
			var first_char = mob_number.charAt(0);
			if (first_char != '+') {
				alert("Please enter Country Code including '+' sign\n\nExample: +44" + mob_number + "");
				jQuery('#send_sms_mobile_numbers').focus();
				return false;
			}
			
			var valid_mob = /^\++[0-9]*$/.test(mob_number);
			if (!valid_mob) {
				alert("Invalid Mobile Number (" + mob_number + ")\n\n- Invalid characters used\n- Must only contain numbers and '+' sign");
				jQuery('#send_sms_mobile_numbers').focus();
				return false;
			}
			
		}
		
		// Validate SMS Message
		var message = jQuery('#send_sms_message').val();
		if (message == '') {
			alert("Please enter a Message");
			jQuery('#send_sms_message').focus();
			return false;
		}
		
		// Convert apostrophes to their html character code equivalent
		var reg_exp = "/'/g";
		var converted_msg = message.replace(reg_exp,"&#39;");
		jQuery('#send_sms_message').val(converted_msg);
		
		jQuery('#result').show();
		jQuery('#result').html('<img src="/pix/ajax-loader.gif" alt="loading" width="16" height="16" /> Sending SMS');
		var form_data = jQuery("#form_sms").serialize();
		jQuery.post('/blocks/send_sms/send_sms_do.php', form_data, function(data){
			jQuery('#result').html('<span>' + data + '</span>'); 
			jQuery('#send_sms_mobile_numbers').val(''); 
			jQuery('#send_sms_message').val(''); 
			jQuery('#char_count').val('160 / 1 credit'); 
		}, 'html');
		
	}); // on form submit
	
	jQuery('#view_format').click(function(event){
		event.preventDefault();
		jQuery('#instructions').slideToggle('6000');
	});

});