<?php
	include_once('../../config.php');
	// Verify session
	
	// Check if any actions exist
	$action = optional_param('action', '', PARAM_RAW);
    $id = optional_param('id', 0, PARAM_INT);
    $instanceid = optional_param('instanceid', 0, PARAM_INT);
	
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="en-AU" xml:lang="en-AU">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<link rel="stylesheet" type="text/css" href="<?php echo $CFG->wwwroot; ?>/theme/standard/styles.php" />
<script type="text/javascript" src="<?php echo $CFG->wwwroot; ?>/theme/conel/jquery-1.4.2.min.js"></script>
<script type="text/javascript"> 
//<![CDATA[
jQuery(document).ready(function() {

	jQuery("#manage_ad_form").submit(function(){

		var dataString = jQuery("#manage_ad_form").serialize();
		$.ajax({
		   type: "POST",
		   url: "actions.php",
		   data: dataString,
		   success: function(msg){
			 window.opener.location.reload();
			 window.close();
		   }
		 });
		return false;
	 });
	 
	 // On cancel: close window - don't need to reload
	 jQuery("#cancel_delete").click(function(event){
		event.preventDefault();
		window.close();
	 });
	 
	 jQuery("#delete_ad").click(function(event){
		event.preventDefault();
		var dataString = jQuery("#delete_ad_form").serialize();
		$.ajax({
		   type: "POST",
		   url: "actions.php",
		   data: dataString,
		   success: function(msg){
			 window.opener.location.reload();
			 window.close();
		   }
		 });
	 });
	
});


function openpopup(url, name, options, fullscreen) {
    var fullurl = "https://clg.conel.ac.uk/VLE" + url;
    var windowobj = window.open(fullurl, name, options);
    if (!windowobj) {
        return true;
    }
    if (fullscreen) {
        windowobj.moveTo(0, 0);
        windowobj.resizeTo(screen.availWidth, screen.availHeight);
    }
    windowobj.focus();
    return false;
}
 
function uncheckall() {
    var inputs = document.getElementsByTagName('input');
    for(var i = 0; i < inputs.length; i++) {
        inputs[i].checked = false;
    }
}
 
function checkall() {
    var inputs = document.getElementsByTagName('input');
    for(var i = 0; i < inputs.length; i++) {
        inputs[i].checked = true;
    }
}
 
function inserttext(text) {
  text = ' ' + text + ' ';
  if ( opener.document.forms['theform'].message.createTextRange && opener.document.forms['theform'].message.caretPos) {
    var caretPos = opener.document.forms['theform'].message.caretPos;
    caretPos.text = caretPos.text.charAt(caretPos.text.length - 1) == ' ' ? text + ' ' : text;
  } else {
    opener.document.forms['theform'].message.value  += text;
  }
  opener.document.forms['theform'].message.focus();
}
 
function getElementsByClassName(oElm, strTagName, oClassNames){
	var arrElements = (strTagName == "*" && oElm.all)? oElm.all : oElm.getElementsByTagName(strTagName);
	var arrReturnElements = new Array();
	var arrRegExpClassNames = new Array();
	if(typeof oClassNames == "object"){
		for(var i=0; i<oClassNames.length; i++){
			arrRegExpClassNames.push(new RegExp("(^|\\s)" + oClassNames[i].replace(/\-/g, "\\-") + "(\\s|$)"));
		}
	}
	else{
		arrRegExpClassNames.push(new RegExp("(^|\\s)" + oClassNames.replace(/\-/g, "\\-") + "(\\s|$)"));
	}
	var oElement;
	var bMatchesAll;
	for(var j=0; j<arrElements.length; j++){
		oElement = arrElements[j];
		bMatchesAll = true;
		for(var k=0; k<arrRegExpClassNames.length; k++){
			if(!arrRegExpClassNames[k].test(oElement.className)){
				bMatchesAll = false;
				break;
			}
		}
		if(bMatchesAll){
			arrReturnElements.push(oElement);
		}
	}
	return (arrReturnElements)
}
//]]>
</script> 
<?php

	if ($action == 'edit' && $id != 0) {
	
	// Get advert from id number// Get ads from table
	$ad = get_record('block_ads', 'id', $id);
?>
<title>Edit Advert</title>
</head>

<body>
<div id="add_advert">
<h2>Edit Advert</h2>
<form action="manage_ad.php" method="post" id="manage_ad_form">
<table>
	<tr>
		<td>Image:</td>
		<td><input type="text" name="image" value="<?php echo $ad->image; ?>" class="textfield" id="image_url" />&nbsp;<input name="reference[popup]" value="Choose or upload a file ..." type="button" title="Choose or upload a file" onclick="return openpopup('/files/index.php?id=1&choose=image_url', 'popup', 'menubar=0,location=0,scrollbars,resizable,width=750,height=500', 0);" id="id_reference_popup"/></td>
	</tr>
	<tr>
		<td>Link:</td>
		<td><input type="text" name="link" value="<?php echo $ad->link; ?>" class="textfield" /></td>
	</tr>
	<tr>
		<td>Title:</td>
		<td><input type="text" name="title" value="<?php echo $ad->title; ?>" class="textfield" /></td>
	</tr>
	<tr>
		<td>&nbsp;</td>
		<td>
		<input type="hidden" name="action" value="update" />
		<input type="hidden" name="id" value="<?php echo $ad->id; ?>" />
		<input type="submit" value="Update" />
		</td>
	</tr>
</table>
</form>
<?php
	} else if ($action == 'delete' && $id != 0) {
	
		// Get selected advert
		if ($advert = get_record('block_ads', 'id', $id)) {
			$id = $advert->id;
			$image = $advert->image;
			$title = $advert->title;
			$link = $advert->link;
			$position = $advert->position;
		}
		
?>
<title>Delete Advert</title>
</head>

<body>
<div id="add_advert">
<h2>Delete Advert</h2>
Are you sure you want to delete this advert?
<form action="actions.php" method="post" id="delete_ad_form">
	<input type="hidden" name="action" value="delete" />
	<input type="hidden" name="id" value="<?php echo $id; ?>" />
	<input type="button" value="Yes" id="delete_ad" />
	<input type="button" value="No" id="cancel_delete" />
</form>
<br />
	<div class="ad">
		<img src="<?php echo $CFG->wwwroot . '/file.php/1/' . $image; ?>" alt="" width="235" title="<?php echo $title; ?>" />
		<br />
		<p>
			<b>Link:</b> <a href="<?php echo $link; ?>" target="_blank"><?php echo $link; ?></a>
			<br />
			<b>Ad Number:</b> <?php echo $position; ?>
		</p>
	</div>
</div>

<?php
	} else {
?>
<title>Add Advert</title>
</head>

<body>
<div id="add_advert">
<h2>Add Advert</h2>
<form action="manage_ad.php" method="post" id="manage_ad_form">
<table>
	<tr>
		<td>Image:</td>
		<td><input type="text" name="image" value="" class="textfield" id="image_url" />&nbsp;<input name="reference[popup]" value="Choose or upload a file ..." type="button" title="Choose or upload a file" onclick="return openpopup('/files/index.php?id=1&choose=image_url', 'popup', 'menubar=0,location=0,scrollbars,resizable,width=750,height=500', 0);" id="id_reference_popup"/></td>
	</tr>
	<tr>
		<td>Link:</td>
		<td><input type="text" name="link" value="" class="textfield" /></td>
	</tr>
	<tr>
		<td>Title:</td>
		<td><input type="text" name="title" value="" class="textfield" /></td>
	</tr>
	<tr>
		<td>&nbsp;</td>
		<td>
		<input type="hidden" name="action" value="insert" />
		<input type="hidden" name="instanceid" value="<?php echo $instanceid; ?>" />
		<input type="submit" value="Add" />
		</td>
	</tr>
</table>
</form>
<?php
	}
?>
</div>

</body>
</html>