<?php
/**
 *
 * @author Sergey Butakov
 *
 */

	require_once("../../config.php");
	global $CFG;

	require_once($CFG->dirroot."/course/lib.php");
	require_once($CFG->dirroot."/mod/assignment/lib.php");

    $id = required_param('id', PARAM_INT);   // course
    $assignmentid = optional_param('action');   // action
//	echo $assignmentid;

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
    $strselectassignment  = get_string("select_assignment", "block_crot");
    $strname = get_string("name");
    $strstudent = get_string("student_name", "block_crot");
	$strsimilar = get_string("similar", "block_crot");

    $navlinks = array();
    $navlinks[] = array('name' => $strmodulename. " - " . $strassignment, 'link' => '', 'type' => 'activity');
    $navigation = build_navigation($navlinks);

    print_header_simple($strmodulename. " - " . $strassignment, "", $navigation, "", "", true, "", navmenu($course));
	//form
?>
<div style="text-align:center;margin-left:auto;margin-right:auto">
<form id="dirform" method="post" action="index.php?id=<?php echo $id; ?>">
<input type="hidden" name="assignmentid"     value="0" />
<select id="menuaction" name="action" onchange="javascript:getElementById('dirform').submit()">
<option value="0"><?php echo $strselectassignment; ?></option>

<?php
	
	// select all assignments for the current course
    	if (!$cms = get_coursemodules_in_course('assignment', $course->id, 'm.assignmenttype, m.timedue')) {
        	notice(get_string('noassignments', 'assignment'), "../../course/view.php?id=$course->id");
        	die;
    	}
	// check if an assignment is marked for local or global search and add it into the list

    	$modinfo = get_fast_modinfo($course);
    	foreach ($modinfo->instances['assignment'] as $cm) {
        	$cm->assignmenttype = $cms[$cm->id]->assignmenttype;
	        if ($cm->assignmenttype == "uploadsingle") {
			$mysql_query = format_string($cm->instance);
			$issearch = get_record("crot_assignments", "assignment_id", $mysql_query);
			if ($issearch){
				$options[]=format_string($cm->name);
				$ids[]=format_string($cm->id);
				echo "<option value=\"".format_string($cm->instance)."\">".format_string($cm->name)."</option>"; 	
			}

	        }
	}


	echo "</select>";

	if (isset($assignmentid)){
		// TODO add threshold options
		if (!isset($threshold)){
			$threshold = $CFG->block_crot_threshold;
		}
		/*
		echo "<input type=\"hidden\" name=\"threshold\" value=\"100\" />";
		echo "<select id=\"tmenuaction\" name=\"taction\" onchange=\"javascript:getElementById('dirform').submit()\">";
		echo "<option value=\"$threshold\">$threshold</option>";	
		for ($i=0; $i<101; $i+=10){
			echo "<option value=\"$i\">$i</option>";	
		}		
		echo "</select>";
		*/
		
		// fill the table with results
	        $table->head  = array ($strstudent, $strsimilar);
        	$table->align = array ("left", "left");
		// first loop to select all the names for particular assignment

		$sql_query = "SELECT u.lastname, u.firstname, u.id, a.id as aid FROM {$CFG->prefix}assignment_submissions a, {$CFG->prefix}user u WHERE a.userid = u.id  AND a.assignment = $assignmentid";
		$subs = get_records("assignment_submissions", "assignment", $assignmentid);
		
		if (empty($subs)) {
        		error(get_string('noattempts','assignment'));
    		}
		
		foreach ($subs as $asub){
			$usser = get_record("user", "id", $asub->userid);
			$table2 = "<table border=2 width='100%'><tr><td width='50%'>Name</td><td width='40%'>Course</td><td  width='10%'>#</td></tr>";
			//$asub->aid is global submission id
			$subm = get_record("crot_submissions", "submissionid", $asub->id);
			//$subm->id is crot submission id
			if (!empty($subm)){
			    $subm2 = get_record("crot_documents", "crot_submission_id", $subm->id);
			    //$subm2-id
			    $sql_query = "SELECT * FROM {$CFG->prefix}crot_submission_pair WHERE submission_a_id ='$subm2->id' OR  submission_b_id = '$subm2->id' order by number_of_same_hashes desc";
			    $similars = get_records_sql($sql_query);

			    // get total number of hashes in the document
			    $sql_query = "SELECT count(*) as cnt from {$CFG->prefix}crot_fingerprint where crot_doc_id = '$subm2->id'";
			    $numbertotal = get_record_sql($sql_query);
			// second loop to select assignments with level of similarities above the threshold.
			if (!empty($similars)){
			foreach ($similars as $asim){
				if ($asim->submission_a_id == $subm2->id){
					$partner = $asim->submission_b_id;
				} else {
					$partner = $asim->submission_a_id;
				}
				// back from id to assignment id
				$subm3 = get_record("crot_documents", "id", $partner);
				$party = $partner;
				
				if ($subm3->crot_submission_id == 0) {
					// web document
					$wwwdoc = get_record("crot_web_documents", "document_id", $party);					
					$nURL = "WWW: ".urldecode($wwwdoc->link);
					$namelink = "<a href=\"compare.php?ida=$subm2->id&idb=$party\">".substr($nURL,0,40)."</a>";
					$courseBname = "Web document";
				}
				else {
				
					$subm4 = get_record("crot_submissions", "id", $subm3->crot_submission_id);
					$partner=$subm4->submissionid;				
					// end back
					$sql_query = "SELECT u.lastname, u.firstname, u.id, a.assignment, a.id as aid FROM {$CFG->prefix}assignment_submissions a, {$CFG->prefix}user u 
							WHERE u.id = a.userid AND a.id = '$partner'";
					if ($partns = get_record_sql($sql_query)){
					    $namelink = "<a href=\"compare.php?ida=$subm2->id&idb=$party\">".substr($partns->lastname." ".$partns->firstname, 0,40)."</a>";
					
					    // get the course name from of subm number
						if (! $assignB = get_record("assignment", "id", $partns->assignment)) {
						    // Sept 06, 2010. It should not be an error here.
						    // if the assignemtn was not found just do nothing
						    // TODO: the corresponding record record should be deleted
						    continue;
	    					}
						if (! $courseB = get_record("course", "id", $assignB->course)) {
							$courseBname = "Course B is not recognized";
	    					} else {
						    $courseBname = $courseB->shortname;
						}
					} else{
					    $namelink="Cannot find local assignment. Most likely it was removed from the system";
					    $courseBname="not applicable";
					}
				}
				// divide  the number of common by the total # of hashes to get the percentage
				$perc =  round(($asim->number_of_same_hashes / $numbertotal->cnt) * 100, 2);
				//TODO add threshold here $CFG->block_crot_threshold
				if ($perc >$threshold){
          				$table2 = $table2."<tr><td>$namelink</td><td>$courseBname</td><td>$perc %</td></tr>";
          			}
			}
			}
			}else{
			    // no plagiarism have been detected OR check up was not performed yet
			    $table2 = "<table border=2 width='100%'><tr><td>no plagiarism have been detected OR check up was not performed yet</td></tr>";
			}
			$table2 = $table2."</table>";
			$namelink = "<a href=\"../../user/view.php?id=$usser->id\">".$usser->lastname." ".format_string($usser->firstname)."</a>";
	        	$table->data[] = array ($namelink, $table2);
		} 
		print_table($table);
	} else
	{
		//TODO add message that the assignemtn was not set echo $strselectassignment;
	}
	// end of form
?>
</form>
</div>
<?php
	echo "<br>Global plagiarism detection is supported by Bing search engine <a href =\"http://www.bing.com\" target=\"_new\"><img src= \"http://www.bing.com/siteowner/s/siteowner/Logo_63x23_Dark.png\"> </a>";
    	print_footer($course);
?>

