<?php
// viewing the object material - same as old view.php.
//
// $object->title 
// $object->start_url
// $object->material_root
// $object->imsmanifest
//

    require_once("../../config.php");
	require_once("ims_nav_builder.php");

	$id = required_param('id', PARAM_INT);
	   
	if (! $cm = get_record("course_modules", "id", $id)) {
        error("Course Module ID was incorrect");
    }

    if (! $course = get_record("course", "id", $cm->course)) {
        error("Course is misconfigured");
    }

	require_course_login($course, false, $cm);

    if (! $object = get_record("object", "id", $cm->instance)) {
        error("Course module is incorrect");
    }
	$tab = (isset($tab)) ? $tab : '';
	add_to_log($course->id, "object", "view", "view.php?id=$cm->id&amp;tab=$tab", $object->id, $cm->id);
	
    $strobject = get_string("modulename", "object");
    $strobjects = get_string("modulenameplural", "object");
	
	$navigation = "<a href=\"index.php?id=$course->id\">$strobjects</a> -> ".format_string($object->name);
    print_header_simple(format_string($object->name), "",
                 $navigation, "", "", true,
                  update_module_button($cm->id, $course->id, $strobject), navmenu($course, $cm));
	
	echo '<link rel="stylesheet" type="text/css" href="viewstyle.css" />';
?>	
<script language="javascript">
<!--
/*
* Dummy SCORM API
*/
 
function GenericAPIAdaptor(){
        this.LMSInitialize = LMSInitializeMethod;
        this.LMSGetValue = LMSGetValueMethod;
        this.LMSSetValue = LMSSetValueMethod;
        this.LMSCommit = LMSCommitMethod;
        this.LMSFinish = LMSFinishMethod;
        this.LMSGetLastError = LMSGetLastErrorMethod;
        this.LMSGetErrorString = LMSGetErrorStringMethod;
        this.LMSGetDiagnostic = LMSGetDiagnosticMethod;
}
/*
* LMSInitialize.
*/
function LMSInitializeMethod(parameter){return "true";}
/*
* LMSFinish.
*/
function LMSFinishMethod(parameter){return "true";}
/*
* LMSCommit.
*/
function LMSCommitMethod(parameter){return "true";}
/*
* LMSGetValue.
*/
function LMSGetValueMethod(element){return "";}
/*
* LMSSetValue.
*/
function LMSSetValueMethod(element, value){return "true";}
/*
* LMSGetLastErrorString
*/
function LMSGetErrorStringMethod(errorCode){return "No error";}
/*
* LMSGetLastError
*/
function LMSGetLastErrorMethod(){return "0";}
/*
* LMSGetDiagnostic
*/
function LMSGetDiagnosticMethod(errorCode){return "No error. No errors were encountered. Successful API call.";}
 
var API = new GenericAPIAdaptor;
//-->
</script>
<?php
	
	if (!isset($object->start_url)) error("Instance not configured correctly.");
	if (isset($_GET['current-material'])) {
		$material = strtr(/*$CFG->wwwroot . '/' .*/ $object_config->object_web_root . '/' . $object->material_root . "/" . $_GET['current-material'], '\\', '/');
	} else {
		$material = strtr(/*$CFG->wwwroot . '/' .*/ $object_config->object_web_root . '/' . $object->material_root . "/" . $object->start_url, '\\', '/');
		$_GET['current-material'] = strtr($object->start_url, '\\', '/');
	}
	$imsfile =  $object_config->object_dir_root . '/' . $object->material_root . "/" . $object->imsmanifest;
				  
	if (isset($object->material_root) && isset($object->imsmanifest)) {
			$nav = new IMSNavBuilder;
			$nav->buildNav($imsfile);
			if ($nav->needs_nav) {
				$navigation = $nav->printNav($object->material_root);
			} else $navigation = '';
	} else $navigation = '';
	
	//if (!empty($object->summary)) $summary = "<p><strong>Summary:</strong> $object->summary</p>";
	//else $summary = '';
	
	echo "<div id='container'>";
	if ($navigation != '') {
		echo "<div id='sidemenu'>";
		echo $navigation;
		echo "</div>";
					
		echo "<div id='objectframe'><iframe src='$material' width=700 height=550 frameborder=0 scrolling=no>Get a good browser!</iframe></div>";
	} else {
		echo "<div id='sidemenu_no_nav'>";
		echo $navigation;
		echo "</div>";
					
		echo "<div id='objectframe_no_nav'><iframe src='$material' width=700 height=550 frameborder=0 scrolling=no>Get a good browser!</iframe></div>";
	}
	echo "</div>";
	print_footer($course);
?>

