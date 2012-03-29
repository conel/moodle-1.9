<?php
class block_crot extends block_base {
	function init() {
        	$this->title = get_string('block_name', 'block_crot');
        	$this->cron = 18000; /// Set min time between cron executions to 18000 secs (5 hrs)
        	$this->version = 2009110100;
    	}

function get_content() {
    if ($this->content !== NULL) {
        return $this->content;
    }
    global $CFG, $COURSE;
    $this->content = new stdClass;
    $navigation_settings = "<a href=\"". $CFG->wwwroot."/blocks/crot/sets.php?id=".$COURSE->id."\">". get_string('settings', 'block_crot') ."</a>";   
    $navigation_report = "<a href=\"". $CFG->wwwroot."/blocks/crot/index.php?id=".$COURSE->id."\">". get_string('report', 'block_crot') ."</a>";   
    $this->content->text =   $navigation_report . '<br>' . $navigation_settings;
    $this->content->footer = 'The block is under construction!<br>please report bugs to (moodlecrot at gmail.com)';
	//print_r($this);
    return $this->content;
}

function instance_allow_config() {
    return true;
}

function has_config() {
    return true;
}

function cron(){
        global $CFG;

        $mypath = $CFG->dirroot.'/blocks/crot/crot_crone.php';
        include ($mypath);
}


function config_save($data) {
	global $CFG;
  // Default behaviour: save all variables as $CFG properties
  // You don't need to override this if you 're satisfied with the above
	foreach ($data as $name => $value) {
		switch ($name):
				case "block_crot_delall":
					if ($value==true){
						$this->clean_data();
					}
					break;
				case "block_crot_testglobal":
					if ($value==true){
						$this->test_global_search();
					}
					break;
				case "block_crot_grammarsize":
					if ($value!=$CFG->block_crot_grammarsize){
						$this->clean_data();				
					}			
					set_config($name, $value);
					break;
				default:
					set_config($name, $value);
		endswitch;
	}
  	return TRUE;
}

function clean_data(){
	// cleaning up all the tables for Crot plugin except teachers' settings: delete_records("crot_assignments")
	delete_records("crot_documents");
	delete_records("crot_fingerprint");
	delete_records("crot_submission_pair");
	delete_records("crot_submissions");
	delete_records("crot_web_documents");
	echo "Crot tables were cleaned up!";
}

function test_global_search(){
	// method sends a few queries to test search
  	global $CFG;
	require_once($CFG->dirroot.'/blocks/crot/lib.php');
	// testing global connectivity
	echo "Testing global connectivity...<br>";
	// read file from global bing web site
	$infile = @file_get_contents("http://www.bing.com/siteowner/s/siteowner/Logo_63x23_Dark.png", FILE_BINARY);
	if (strlen($infile)>0 && substr($infile,1,3)=='PNG'){
		// print the file size
		echo "<i>Bing.com is accessible from your server - <font color=\"green\"><b>OK</b></font></i><br><hr>";
	} else {
		echo "can not reach bing.com<br>";
	}
	
	// testing Bing search
	$msnkey 	= $CFG->block_crot_live_key;
	$culture_info	= $CFG->block_crot_culture_info;
	$todown 	= $CFG->block_crot_number_of_web_documents;
	$query = ("Crot for Moodle");
	$query = "'".trim($query)."'";
	
	echo "Testing global search settings for Bing...<br>";
	try {
		$request = 'http://api.bing.net/xml.aspx?Appid=' . $msnkey . 
		'&sources=web&Query=' . urlencode( $query) . 
		'&culture='.$culture_info. 
		'&Web.Options=DisableHostCollapsing+DisableQueryAlterations'.
		'&Options=DisableLocationDetection';
		echo "Sending query:".$request;		
	  	$searches = fetchBingResults($query, $todown, $msnkey, $culture_info);
	}
	catch (Exception $e) {
	  print_error("exception in querying Bing!\n");
	}
	$i=1;
	if ($searches){
		echo "<i>- <font color=\"green\"><b>OK</b></font></i><hr>";
		echo "<b>Search results:</b><br>";
		echo "Top links for <i>\"".rawurldecode($query)."\"</i> query:<br>";
		echo "Top links for <i>\"".$query."\"</i> query:<br>";
		foreach($searches as $hit){
			echo "link $i:".substr($hit,0,60)."<br>";
			$i++;
		}
	}else{
		echo "<i> - <font color=\"red\"><b>ERROR!!!</b></font></i><hr>";		
	}
	echo "<script type=\"text/javascript\">alert(\"Test is over\");</script>";
	flush();
}

} // end of object


?>
