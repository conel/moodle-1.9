<?php
	//include_once("link_manager.php");
	//include_once("crumbtrail.php");
	include_once("output.php");
	include_once("../../config.php");

	if (empty($CFG->object_dirroot)) $CFG->object_dirroot = 'repository';
	$object_config->object_dir_root = $CFG->object_dirroot;					// relative path
	if (empty($CFG->object_webroot)) $CFG->object_webroot = $CFG->wwwroot . '/mod/object/repository';
	$object_config->object_web_root = $CFG->object_webroot;			// absolute path
	
	//$object_config->view_web_root = 'object/Tom/viewer.php';	// absolute path
	$object_config->pics = './images';
	//$object_config->url = 'http://ifp.altoncollege.ac.uk';
	
	$object_display = new Display;
	//$GLOBALS['lm'] = new LinkManager;
	
	//include_once("ims_nav_builder.php");
	
	function shortenURL($url)
		{
			$url = strtr($url, '\\', '/');
			$arr = split('/', $url);
			$return = array();
			
			foreach ($arr as $dir) {
				if ($dir != '.' && $dir != '') {
					$return[] = $dir;
				}
			}
			return implode('/', $return);
	}
	
	function getURLProtection($url)
		{
			$url = strtr($url, '\\', '/');
			$arr = split('/', $url);
			$return = array();
			
			foreach ($arr as $dir) {
				if ($dir == '..') {
					array_pop($return);
				} 
				elseif ($dir != '.' && $dir != '') {
					$return[] = $dir;
				}
			}
			return implode('/', $return);
	}
?>
