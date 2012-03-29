<STYLE><!--
  
  #example{scrollbar-3d-light-color: '#0084d8'; scrollbar-arrow-color: 'black'; scrollbar-base-  color:'#00568c'; scrollbar-dark-shadow-color:''; scrollbar-face-color:''; scrollbar-highlight-color:''; scrollbar-shadow-color:''; text-align:left; position:relative; width: 404px; 
padding:2px; height:300px; overflow:scroll; border-width:2px; border-style:outset; background-color:lightgrey;}

 --></STYLE>
<?php
/**
 *
 * @author Sergey Butakov
 * this module compares two submissions side by side
 *
 */
	require_once("../../config.php");
	global $CFG;
	require_once($CFG->dirroot."/blocks/crot/lib.php");
	require_once($CFG->dirroot."/course/lib.php");
	require_once($CFG->dirroot."/mod/assignment/lib.php");
	
	// globals
	$minclustersize = $CFG->block_crot_clustersize;
	$distfragments  = $CFG->block_crot_clusterdist;
	$allColors	= explode(",", $CFG->block_crot_colours);

	$ida = required_param('ida', PARAM_INT);   // submission A
	$idb = required_param('idb', PARAM_INT);   // submission B

	if (! $submA = get_record("crot_documents", "id", $ida)) {
		error("Doc A ID is incorrect");
	}
	if ($submA->crot_submission_id == 0)
	{
		$isWebA = true;
	} else {
		$isWebA = false;
	}
	
	if (! $submB = get_record("crot_documents", "id", $idb)) {
		error("Doc B ID is incorrect");
	}

	if ($submB->crot_submission_id == 0)
	{
		$isWebB = true;
	} else  {
		$isWebB = false;
	}

    	// TODO get global assignment id
	$a1 = get_record("crot_submissions", "id", $submA->crot_submission_id);
	$b1 = get_record("crot_submissions", "id", $submB->crot_submission_id);
	if (!$isWebA) {
		if (! $subA = get_record("assignment_submissions", "id", $a1->submissionid))  {
			error("Submission A ID is incorrect");
		}
		if (! $assignA = get_record("assignment", "id", $subA->assignment)) {
			error("Assignment A ID is incorrect");
		}
		if (! $courseA = get_record("course", "id", $assignA->course)) {
			error("Course A ID is incorrect");
		}

		require_course_login($courseA);
		if (!isteacher($courseA->id)) {
			error(get_string('have_to_be_a_teacher', 'block_crot'));
		}
	}
	if (!$isWebB) {
		if (! $subB = get_record("assignment_submissions", "id", $b1->submissionid)) {
			error("Submission B ID is incorrect");
		}
		if (! $assignB = get_record("assignment", "id", $subB->assignment)) {
			error("Assignment B ID is incorrect");
		}

	if (! $courseB = get_record("course", "id", $assignB->course)) {
			error("Course B ID is incorrect");
		}
		
		require_course_login($courseB);
		if (!isteacher($courseB->id)) {
			error(get_string('have_to_be_a_teacher', 'block_crot'));
		}
	}
	// end of checking permissions

	// built navigation	
	$strmodulename = get_string("block_name", "block_crot");
	$strassignment  = get_string("assignments", "block_crot");
	$navlinks = array();
	$navlinks[] = array('name' => $strmodulename. " - " . $strassignment, 'link' => '', 'type' => 'activity');
	$navigation = build_navigation($navlinks);
	// end of top navigation

	print_header_simple($strmodulename. " - " . $strassignment, "", $navigation, "", "", true, "", navmenu($courseA));
	
	// TODO add to log
	//add_to_log($course->id, "antiplagiarism", "view all", "index.php?id=$course->id", "");

	
	// get content of the 1st document
	$textA = stripslashes($submA->content);
	//$textA = ($submA->content);

	// get all hashes for docA
	$sql_query = "SELECT * FROM {$CFG->prefix}crot_fingerprint f WHERE crot_doc_id = $ida ORDER BY position asc";
	$hashesA = get_records_sql($sql_query);
	// get all hashes for document B
	$sql_query = "SELECT * FROM {$CFG->prefix}crot_fingerprint f WHERE crot_doc_id = $idb ORDER BY position asc";
	$hashesB = get_records_sql($sql_query);

	// TODO create separate function for coloring ?
	$sameHashA = array ();

	// coloring: step 1 - get same hashes	
	foreach ($hashesA as $hashA) {
		// look for same hash in the array  B
		foreach ($hashesB as $hashB){
			if ($hashA->value == $hashB->value) {
				// same hash found!
				$sameHashA [] = $hashA;
				break;
			}
		}
	}
	
	// coloring: step 2 - put hashes into clusters
	$clustersA = array();
	$newcluster = array();
	$sizeA = sizeof($sameHashA);
//	$minclustersize=2;
	for ($i=0; $i<$sizeA; $i++)	{
		if ($i >0 ) {
			if (($sameHashA[$i]->position - $sameHashA[$i-1]->position) <= $distfragments)		{	
			// the hashes are close to each other - put hash into the cluster
				$newcluster[] = $sameHashA[$i];
			}
			else {	// hashes are far from each other - wrap up the  old cluster
				if (sizeof($newcluster) >= $minclustersize)	{
					$clustersA[]= $newcluster;
				}								
				// create a new cluster	
				$newcluster = array();		
				// put the orphan into the new cluster
				$newcluster[] = $sameHashA[$i];
						
			}
			if (($i == ($sizeA -1)) and (sizeof($newcluster) >= $minclustersize)) {
				// last hash
				$clustersA[]= $newcluster;
			}
		} else {	
			// put the first hash into the cluster
			$newcluster[] = $sameHashA[0];			
		}
	}
		
	// coloring: step 3 - add colors to each cluster
	$colorsA = array ();
		// initilize colors
	$i=0;
	foreach ($clustersA as $clusterA) {
		$colorsA[]="#AA0000";//$allColors[$i];
		$i++;
	}
	// loop backward to add colors
	for ($i = sizeof ($clustersA) -1; $i>=0; $i--) {
		$clusterA = $clustersA[$i];
		// get borders
		$startPos = $clusterA[0]->position;
		$endPos   = $clusterA[sizeof($clusterA)-1]->position;
		// add colors to the cluster
		$textA = colorer($textA, $startPos, $endPos, $colorsA[$i]);		
	}
	
	// get the content of the second document 
	
	$textB = stripslashes($submB->content);
	//$textB = ($submB->content);
	
	// add colors to doc B
	$sameHashB = array ();
	
	// coloring for doc B: step 1 - get same hashes	
	// this has to be done in a separate loop to make sure those hashes are ordered by position
	foreach ($hashesB as $hashB) {		// look for same hash in the array  B
		foreach ($sameHashA as $hashA){
			if ($hashA->value == $hashB->value) {
				// same hash found!
				$sameHashB [] = $hashB;
				break;
			}
		}
	}

	$clustersB = array();
	$newcluster = array();
	$sizeB = sizeof($sameHashB);
	$minclustersize=2;
	for ($i=0; $i<$sizeB; $i++)	{
		if ($i >0 ) {
			if (($sameHashB[$i]->position - $sameHashB[$i-1]->position) <= $distfragments)		{	
			// the hashes are close to each other - put hash into the cluster
				$newcluster[] = $sameHashB[$i];
			}
			else {	// hashes are far from each other - wrap up the  old cluster
				if (sizeof($newcluster) >= $minclustersize)	{
					$clustersB[]= $newcluster;
				}								
				// create a new cluster	
				$newcluster = array();		
				// put the orphan into the new cluster
				$newcluster[] = $sameHashB[$i];
						
			}
			if (($i == ($sizeB -1)) and (sizeof($newcluster) >= $minclustersize)) {
				// last hash
				$clustersB[]= $newcluster;
			}
		} else {	
			// put the first hash into the cluster
			$newcluster[] = $sameHashB[0];			
		}
	}
		
	// coloring: step 3 - add colors to each cluster
	$colorsB = array ();
		// initilize colors
	$i=0;
	foreach ($clustersB as $clusterB) {
		$colorsB[]="#AA0000";//$allColors[$i];
		$i++;
	}
	// loop backward to add colors
	for ($i = sizeof ($clustersB) -1; $i>=0; $i--) {
		$clusterB = $clustersB[$i];
		// get borders
		$startPos = $clusterB[0]->position;
		$endPos   = $clusterB[sizeof($clusterB)-1]->position;
		// add colors to the cluster
		$textB = colorer($textB, $startPos, $endPos, $colorsB[$i]);		
	}

	// create and display  2-column table to compare two documents
	// get name A
    	if (!$isWebA)
    	{
		if (! $studentA = get_record("user", "id", $subA->userid)) {
			$strstudentA = "name is unknown";
	    	} else {
			$strstudentA = $studentA->lastname." ".$studentA->firstname.":<br> ".$courseA->shortname.",<br> ".$assignA->name;
		}
	}
	else {
		$wdoc = get_record("crot_web_documents", "document_id", $ida);
		if (strlen($wdoc->link)>40) {
			$linkname = substr($wdoc->link,0,40);
		}
		else  {
			$linkname = $wdoc->link;
		}
		$strstudentA = "Web document:<br>"."<a href=\"$wdoc->link\">$linkname</a>";;
	}
	
	// get name B
	if (!$isWebB) {
	if (! $studentB = get_record("user", "id", $subB->userid)) {
		$strstudentB = "name is unknown";
		} 
		else {
		$strstudentB = $studentB->lastname." ".$studentB->firstname.":<br> ".$courseB->shortname.",<br> ".$assignB->name;
		}
	}
	else {
		$wdoc = get_record("crot_web_documents", "document_id", $idb);
		if (strlen($wdoc->link)>40) {
			$linkname = substr($wdoc->link,0,40);
		}
		else {
			$linkname = $wdoc->link;
		}
		$strstudentB = "Web document:<br>"."Source: <a href=\"".urldecode($wdoc->link)."\" target=\"_blank\">".urldecode($linkname)."</a>";;
	}

	$textA = "<div id=\"example\"><FONT SIZE=1>".ereg_replace("\n","<br>",$textA)."</font> </div>";
	$textB = "<div id=\"example\"><FONT SIZE=1>".ereg_replace("\n","<br>",$textB)."</font></div>";
	$table->head  = array ($strstudentA, $strstudentB);
	$table->align = array ("center", "center");
	$table->data[] = array ($textA, $textB);
	print_table($table);

	// footer 
	print_footer($courseA);

?>
