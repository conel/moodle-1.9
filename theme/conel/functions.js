function rand ( n ) {
	return ( Math.floor ( Math.random ( ) * n + 1 ) );
}

// Make sure it plays with other js frameworks
jQuery.noConflict();

jQuery(document).ready(function(){
	if (jQuery('#home_image_holder') != '') {
		img_src = '/theme/conel/pix/home-image' + rand(7) + '.jpg';
		img_html = '<img src="' + img_src + '" id="welcome_image" alt="Welcome to the E-ZONE at the College of North East London" height="269" width="440" />';
		jQuery('#home_image_holder').html(img_html);
	}
});
	
// These lovely cookie functions courtesy of: http://www.quirksmode.org/js/cookies.html
function createCookie(c,d,b){if(b){var a=new Date;a.setTime(a.getTime()+b*864E5);b="; expires="+a.toGMTString()}else b="";document.cookie=c+"="+d+b+"; path=/"}
function readCookie(c){c+="=";for(var d=document.cookie.split(";"),b=0;b<d.length;b++){for(var a=d[b];a.charAt(0)==" ";)a=a.substring(1,a.length);if(a.indexOf(c)==0)return a.substring(c.length,a.length)}return null}
function eraseCookie(c){createCookie(c,"",-1)};