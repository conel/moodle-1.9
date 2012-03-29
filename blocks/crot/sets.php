<?php
/**
 *
 * @author Sergey Butakov
 * this module manages settings of the plug-in in the course
 *
 */

	require_once("../../config.php");
	global $CFG;
	require_once($CFG->dirroot."/course/lib.php");
	require_once($CFG->dirroot."/mod/assignment/lib.php");

    $id = required_param('id', PARAM_INT);   // course

    if (! $course = get_record("course", "id", $id)) {
        error("Course ID is incorrect");
    }

    require_course_login($course);
    if (!isteacher($course->id))
    {
      error(get_string('have_to_be_a_teacher', 'block_crot'));
    }

//    add_to_log($course->id, "antiplagiarism", "view all", "index.php?id=$course->id", "");

    $strmodulename = get_string("block_name", "block_crot");
    $strassignment  = get_string("assignments", "block_crot");
    $strlocal  = get_string("local", "block_crot");
    $strglobal  = get_string("global", "block_crot"); 
    $strsettings  = get_string("settings", "block_crot");
    $strname = get_string("name");

    $navlinks = array();
    $navlinks[] = array('name' => $strmodulename." - ".$strsettings, 'link' => '', 'type' => 'activity');
    $navigation = build_navigation($navlinks);

    print_header_simple($strmodulename." - ".$strsettings, "", $navigation, "", "", true, "", navmenu($course));

    if (!$cms = get_coursemodules_in_course('assignment', $course->id, 'm.assignmenttype, m.timedue')) {
        notice(get_string('noassignments', 'assignment'), "../../course/view.php?id=$course->id");
        die;
    }
// form

        $table->head  = array ($strassignment, $strlocal, $strglobal);
        $table->align = array ("center", "left", "left");

    	$currentsection = "";

    $types = assignment_types();

    $modinfo = get_fast_modinfo($course);
	$tblCrotAssignments = $CFG->prefix."crot_assignments";	
	$i=0;
  
    foreach ($modinfo->instances['assignment'] as $cm) {

        $cm->assignmenttype = $cms[$cm->id]->assignmenttype;

        //Show dimmed if the mod is hidden
        $class = $cm->visible ? '' : 'class="dimmed"';

        $link = "<a $class href=\"../../mod/assignment/view.php?id=$cm->id\">".format_string($cm->name)."</a>";
	// check database if the assignment is selected

	$mysql_query = format_string($cm->instance);
	$issearch = get_record("crot_assignments", "assignment_id", $mysql_query);
	$globalchecked = "";
	$localchecked  = "";	

	if ($issearch){
		// local
		if ($issearch->is_local==1){
			$localchecked = "checked";
		}	
		// global
		if ($issearch->is_global==1){
			$globalchecked = "checked";
		}	
	}


	$strlocal  =  "<input type=\"checkbox\" name='locals[$i]' value=\"$mysql_query\" $localchecked />";
	$checked = "";	
    	$strglobal  = "<input type=\"checkbox\" name=\"globals[$i]\" value=\"$mysql_query\" $globalchecked />"
			. "<input type=\"hidden\" name=\"assign[$i]\" value=\"$mysql_query\" />";

        if ($cm->assignmenttype == "uploadsingle") {
            $table->data[] = array ($link, $strlocal, $strglobal);
		$i++;
        }
    }

    echo "<br />";
?>

<form id="form" method="post" action="savesettings.php">
<?php
    print_table($table);
?>
<div style="text-align:center;margin-left:auto;margin-right:auto">
<input type="hidden" name="id"     value="<?php  echo $id; ?>" />
<input type="hidden" name="launch" value="execute" />
<input type="submit" value="<?php  print_string("save", 'block_crot') ?>" />
<input type="submit" name="cancel" value="<?php  print_string("cancel") ?>" />
</div>
</form>
<?php
    print_footer($course);
 
?>
