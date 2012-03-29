function rand ( n ) {
	return ( Math.floor ( Math.random ( ) * n + 1 ) );
}

// Make sure it plays with other js frameworks
jQuery.noConflict();

jQuery(document).ready(function(){
	if (jQuery('#home_image_holder') != '') {
		img_src = '/VLE/theme/conel/pix/home-image' + rand(7) + '.jpg';
		img_html = '<img src="' + img_src + '" id="welcome_image" alt="Welcome to the E-ZONE at the College of North East London" height="269" width="440" />';
		jQuery('#home_image_holder').html(img_html);
	}
});
	
// These lovely cookie functions courtesy of: http://www.quirksmode.org/js/cookies.html
function createCookie(name,value,days) {
	if (days) {
		var date = new Date();
		date.setTime(date.getTime()+(days*24*60*60*1000));
		var expires = "; expires="+date.toGMTString();
	}
	else var expires = "";
	document.cookie = name+"="+value+expires+"; path=/";
}

function readCookie(name) {
	var nameEQ = name + "=";
	var ca = document.cookie.split(';');
	for(var i=0;i < ca.length;i++) {
		var c = ca[i];
		while (c.charAt(0)==' ') c = c.substring(1,c.length);
		if (c.indexOf(nameEQ) == 0) return c.substring(nameEQ.length,c.length);
	}
	return null;
}

function eraseCookie(name) {
	createCookie(name,"",-1);
}