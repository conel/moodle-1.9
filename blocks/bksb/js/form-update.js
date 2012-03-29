$(document).ready(function() {
$("#bksb_update").submit(function() {

	/* JavaScript form checking */
	
	// check username is not blank
	var username = $("#update_username").val();
	if (username == "") {
		alert('Username is a required field');
		$("#update_username").focus();
		return false;
	}
	// check firstname is not blank
	var firstname = $("#update_firstname").val();
	if (firstname == "") {
		alert('Firstname is a required field');
		$("#update_firstname").focus();
		return false;
	}
	// check lastname is not blank
	var lastname = $("#update_lastname").val();
	if (lastname == "") {
		alert('Lastname is a required field');
		$("#update_lastname").focus();
		return false;
	}
	
	// Ajax update
	var dataString = $("#bksb_update").serialize();
	$.ajax({
	  type: "POST",
	  url: 'bksb_update_ajax.php',
	  data: dataString,
	  success: function(data) {
		$('#bksb_update').hide();
		//$('#update_message').html(data);
	  }
	});
	
	return false;

});
});