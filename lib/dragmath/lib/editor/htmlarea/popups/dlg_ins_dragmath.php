<?php

#################################################################################
##
## $Id: dlg_ins_dragmath.php,v 1.2.4.1 2008/05/14 16:04:28 net-buoy Exp $
##
#################################################################################

    require("../../../../config.php");

    $id = optional_param('id', SITEID, PARAM_INT);

    require_course_login($id);
    @header('Content-Type: text/html; charset=utf-8');

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
<meta http-equiv="content-type" content="text/html; charset=utf-8" />
<title>"DragMath Equation Editor</title>
<link rel="stylesheet" href="dialog.css" type="text/css" />
<script type="text/javascript" src="popup.js"></script>
<script type="text/javascript">
//<![CDATA[
function Init() {
  __dlg_init();
}
function insert(text) {
  __dlg_close(text);
  return false;
};

function cancel() {
  __dlg_close(null);
  return false;
};
function getExportAndInsert(){
    var mathExpression = document.dragmath.getMathExpression();
	//
	// TBD any massaging needed here?
	//
	var text = mathExpression;
	//
	// Escape the expression
	//
	// var text = '$$' + text + '$$';
    // At this point the additional "text variable should probably be deleted as unnecessary
	insert(text);
}
//]]>
</script>
</head>
<body onload="Init()">

<applet 
	width=540 height=300 
	archive="Project.jar,AbsoluteLayout.jar,swing-layout-1.0.jar,jdom.jar,jep.jar"
    code="Display.MainApplet.class"
    codebase="../plugins/DragMath/applet/classes" 
	name="dragmath">
	<param name=language value="en">
	<param name=outputFormat value="MoodleTex">
	<param name=showOutputToolBar value="false">
	To use this page you need a Java-enabled browser. 
	Download the latest Java plug-in from 
	<a> href="http://www.java.com">Java.com</a>
</applet >
<form name="form">
	<div>
	<button type="button" onclick="return getExportAndInsert();">Insert</button>
	<button type="button" onclick="return cancel();">Cancel</button>
	</div>
</form>

</body>
</html>
