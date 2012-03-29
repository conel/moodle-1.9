<?php

	//error_reporting(E_ALL);
	//session_start();
	
	include_once("config.php");
	include_once("crumbtrail.php");
	include_once("ims_nav_builder.php");

	$object_nav = new FileNavigator;
	
	include_once("../../config.php");
	require_login();	
	
	//require "ims_nav_builder.php";
	
	$main_body = $object_nav->printNav();
	$title = $object_nav->getPageTitle();	
	
	echo "
	
<html>
<head>
<style type='text/css'>
@import url(style.css);
#objectframe {
	top:50px;
}
</style>
	";
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
echo "
<title>$title</title>
</head>
<body>
<script language='javascript' type='text/javascript'>
            <!--
            
            function set_value_key(txt) {
            	  if (window.event && window.event.keyCode == 13) {
                    set_value(txt);
                    
                }
            }
            
            function set_value(name, start_url, material_root, imsfile, description) {
				
				opener.document.forms['form'].name.value = name;
				opener.document.forms['form'].start_url.value = start_url;
				opener.document.forms['form'].material_root.value = material_root;
				opener.document.forms['form'].imsmanifest.value = imsfile;
				opener.document.forms['form'].summary.value = description;

                window.close();
            }
            
            -->
</script>
$main_body
</body>
</html>
	";
	
	$object_nav->saveVariablesToSession();
	exit();
	
	class FileNavigator {
		var $fileroot;
		var $imsfile;
		var $object_display;
		var $goto;
		var $current_material;
		var $current_material_code;
		
		function materialPath($fileroot)
		{
			global $object_config;
			return "./".shortenURL($object_config->object_dir_root."/".$fileroot);
		}
		
		function webPath($fileroot)
		{
			global $object_config;
			return $object_config->object_web_root."/".$fileroot;
		}
		
		function FileNavigator()
		{
			//print_r($_SESSION);
			
			if (isset($_GET['reset']))
			{
				$_SESSION['object-finder'] = array();
			}
			
			if (! isset($_SESSION['object-finder']['fileroot']))
			{
				$_SESSION['object-finder']['fileroot'] = '';
			}
			if (! isset($_GET['object-display']))
			{
				$_SESSION['object-finder']['object-display'] = false;
				$_SESSION['object-finder']['imsfile'] = '';
			}
			
			if (! isset($_GET['current-material']))
			{
				$_SESSION['object-finder']['current-material'] = false;
			}
			
			if (isset($_GET['object-display']))
			{
				if($_GET['object-display'] == 'true') $_SESSION['object-finder']['object-display'] = true; 
				if($_GET['object-display'] == 'false') $_SESSION['object-finder']['object-display'] = false;
			} 
			
			if($_SESSION['object-finder']['object-display'] == false) {
				$_SESSION['object-finder']['current-material'] = false;
			}
			
			if (isset($_GET['imsfile']))
			{
				$_SESSION['object-finder']['imsfile'] = $_GET['imsfile'];
			}
			
			if (isset($_GET['hidebutton']))
			{
				$_SESSION['object-finder']['hidebutton'] = true;
			}
			
			if (isset($_GET['goto']))
			{
				
				$_GET['goto'] = getURLProtection(html_entity_decode($_GET['goto']));
				if (($_GET['goto'] != '..')/* && ($_GET['goto'] != '.')*/ && is_dir($this->materialPath($_GET['goto'])))
				{
					$_SESSION['object-finder']['fileroot'] = $_GET['goto'];
					$this->goto = $_GET['goto'];
				}
			}
			
			if(isset($_GET['current-material']))
			{
				$_GET['current-material'] = shortenURL($_GET['current-material']);
				$_SESSION['object-finder']['current-material'] = $_GET['current-material'];
			}
			else
			{
				$_SESSION['object-finder']['current-material'] = false;
			}
			
			/*if ($this->materialPath(is_file(shortenURL($_SESSION['object-finder']['fileroot']) . '/' . 'imsmanifest.xml'))) {
				$_SESSION['object-finder']['object-display'] = true;
				$_SESSION['object-finder']['imsfile'] = 'imsmanifest.xml';
			}*/
			
			if (!isset($_SESSION['object-finder']['imsfile'])) $_SESSION['object-finder']['imsfile'] = false;
			
			$this->fileroot = shortenURL($_SESSION['object-finder']['fileroot']);
			$this->imsfile = $_SESSION['object-finder']['imsfile'];
			$this->object_display = $_SESSION['object-finder']['object-display'];
			$this->current_material = shortenURL($_SESSION['object-finder']['current-material']);			
		}
		
		function saveVariablesToSession()
		{
			$_SESSION['object-finder']['fileroot'] = shortenURL($this->fileroot);
			$_SESSION['object-finder']['imsfile'] = $this->imsfile;
			$_SESSION['object-finder']['object-display'] = $this->object_display;
			$_SESSION['object-finder']['current-material'] = shortenURL($this->current_material);		
		}
		function getPageTitle()
		{
			if(!$this->object_display) {
				list($a, $title) = make_crumbtrail($this->fileroot);
			} else {
				if(is_file($this->materialPath($this->fileroot.'/'.$this->imsfile)))
				{
					$builder = new IMSNavBuilder;
					$builder->buildNav($this->materialPath($this->fileroot.'/'.$this->imsfile));
					$title = $builder->title;
				}
			}
			
			return $title;
		}
		
		function printNav()
		{
			global $object_display, $object_config;
			
			//$list_output = "<h3>$this->fileroot</h3>" . $GLOBALS['display']->printUpLink("Back");
			list($crumbtrail_html, $crumbtrail_last_item) = make_crumbtrail($this->fileroot);
			//$list_output = '';
				
			if($this->object_display) $id='sidemenu';
			else $id = 'menu';
			
			$menu_html = "<div id=$id>";
			if(!$this->object_display)
			{
								
				$dir = opendir($this->materialPath("$this->fileroot"));
				if(! $dir) $this->fileroot = '.';
							
				$menu_html .= "<ul>";
				
				
				// start - change to make the file browser display files independent to the order they are returned by readdir
				$tmp_file_array = array();
				$tmp_folder_array = array();
				
				while($file = readdir($dir))
				{
					if ($file == '..' || $file == '.')
					{
						continue;		
					}
					if (is_dir($this->materialPath($this->fileroot.'/'.$file)))
					{
						if (is_file($this->materialPath($this->fileroot."/$file/imsmanifest.xml"))) {
	  				    if (!$title = $this->getTitleFromIMS(IMSNavBuilder::decode_utf16(file_get_contents($this->materialPath($this->fileroot . "/$file/imsmanifest.xml"))))) {
				            $builder = new IMSNavBuilder;
				            $builder->buildNav($this->materialPath($this->fileroot."/$file/imsmanifest.xml"));
	            
  			            $title = $builder->title;
								    unset($builder);
				        }
              $tmp_file_array[$title] = $file;
				    } else {
				      $tmp_folder_array[$file] = $file;
				    }
				  }
				}
				closedir($dir);	

				ksort($tmp_file_array);
				ksort($tmp_folder_array);
				foreach ($tmp_folder_array as $file) {
				    $menu_html .= $object_display->wrapLink($object_display->printDirectoryLink($file, array('goto'=>"$this->fileroot/$file")));
				}
				foreach ($tmp_file_array as $title => $file) {
  	        $menu_html .= $object_display->wrapLink($object_display->printMaterialLink($title, array('goto'=>"$this->fileroot/$file", 'imsfile'=>'imsmanifest.xml', 'object-display'=>'true')));
				}

				unset($tmp_file_array);
				unset($tmp_folder_array);
				// end
				
				/* 
				while($file = readdir($dir))
				{
					if ($file == '..')
					{
						//$list_output .= $GLOBALS['display']->wrapLink($GLOBALS['display']->printUpLink($file));			
					}
					elseif (($file != '.') && is_dir($this->materialPath($this->fileroot.'/'.$file)))
					{
						if (is_file($this->materialPath($this->fileroot."/$file/imsmanifest.xml"))) {
							$found_ims = 1;
							$ims = "imsmanifest.xml";
						} else {
							$found_ims = 0;
						}
						
						if ($found_ims == 1)
						{
							if(! $title = $this->getTitleFromIMS(file_get_contents($this->materialPath($this->fileroot."/$file/imsmanifest.xml")))) {
								$builder = new IMSNavBuilder;
								$builder->buildNav($this->materialPath($this->fileroot."/$file/imsmanifest.xml"));
								$title = $builder->title;
								unset($builder);
							}
							
							$menu_html .= $object_display->wrapLink($object_display->printMaterialLink($title, array('goto'=>"$this->fileroot/$file", 'imsfile'=>'imsmanifest.xml', 'object-display'=>'true')));
						}
						else
						{	
							$menu_html .= $object_display->wrapLink($object_display->printDirectoryLink($file, array('goto'=>"$this->fileroot/$file")));
						}
					}
				}
				closedir($dir);
				*/
				
				$menu_html .= "</ul>";
			}
			else
			{
				if(is_file($this->materialPath($this->fileroot.'/'.$this->imsfile)))
				{
					$builder = new IMSNavBuilder;
					$builder->buildNav($this->materialPath($this->fileroot.'/'.$this->imsfile));
					if (!isset($_GET['current-material'])) {
						$_GET['current-material'] = $builder->get_startpoint();
						$this->current_material = $builder->get_startpoint();
					}
					$menu_html .= $builder->printNav($this->fileroot);

					$crumbtrail_last_item = strtr(addslashes(htmlentities($builder->title)), "\n\r\t", '   ');
					$description = strtr(addslashes(htmlentities($builder->description)), "\n\r\t", '   ');
					unset($builder);
				}	
				else
				{
					die("NOT A FILE: ".$this->fileroot.'/'.$this->imsfile);
				}	
				
				$temp = explode('/', $this->fileroot);
				array_pop($temp);
				$temp_fileroot = implode('/', $temp);
				unset($temp);
				
				//$list_output .= $GLOBALS['display']->printDirectoryLink("back", array('goto'=>$temp_fileroot, 'object-display'=>'false'));
				
				//if(!$this->current_material)
				//{
					//get first page...
				//}
			}
			
			if ($this->current_material)
			{			
				$material = $this->current_material;
				$material_root = shortenURL($this->fileroot);
				$href = "?current-material=$material&imsfile=$this->imsfile&material-root=$material_root^$crumbtrail_last_item";
				$menu_html .= "</div>";
				//echo "<pre>|$description|</pre>";
				if(!isset($_SESSION['object-finder']['hidebutton'])) $menu_html .= "<p><button onClick=\"return set_value('$crumbtrail_last_item', '$material', '$material_root', '$this->imsfile', '$description')\">Add to course</button></p>";
			}
			$menu_html .= "";
			$material_html = "";
			if ($this->current_material)				
			{
				$mroot = strtr($this->webPath($this->fileroot) . '/' . $this->current_material, '\\', '/');
				$material_html .= "<div id='objectframe'><iframe src='$mroot' width=650 height=550 scrolling=no frameborder=0>Get a good browser!</iframe></div>";
			}
			
			return "<p>$crumbtrail_html$crumbtrail_last_item</p>$menu_html$material_html";		
		}
		
		function printUpLink($text)
		{
			$temp = explode('/', $this->fileroot);
			array_pop($temp);
			$temp_fileroot = implode('/', $temp);
			unset($temp);
			return "<a href=\"?goto=$temp_fileroot\"><img src='back.gif' border=0></a>";		
		}
		
		function getTitleFromIMS($xml)
		{		
			if (! $title = text_between($xml, "<imsmd:title>", "</imsmd:title>")) return false;
			//return text_between($title, "<imsmd:langstring>", "</imsmd:langstring>");		
			eregi(ereg_MatchedHTMLTags('imsmd:langstring'), $title, $Matches);
			if (count($Matches) > 0) {
				return $Matches[4];
			} else{
				return false;
			}
		}

	};
	
	// Use with eregi to ensure case-insensitive match.
	//        e.g. to split an HTML page based on body tag: 
	//            eregi(ereg_MatchedHTMLTags('body'), $Source, $Matches) 
	// The following values will be held in $Matches
	//(marked values are unintended byproducts of the expression)
	//          *[0] - the entire string ($Source).
	//            [1] - everything before the opening tag
	//            [2] - the opening tag, including all contents (i.e. everything between < and >)
	//          *[3] - the opening tag from end of the tag name, 
	//                      e.g. '<body bgcolor="#000000">' gives ' bgcolor="#000000">'
	//            [4] - the tag contents (everything between the opening and closing tag)
	//            [5] - the complete closing tag.
	//          *[6] - the closing tag from the end of the tag name
	//                      e.g. '</body invalid text>' gives ' invalid text>'
	//            [7] - everything after the closing tag. 
    function ereg_MatchedHTMLTags($tagname) {
        return "^(.*)(<[ \\n\\r\\t]*$tagname(>|[^>]*>))(.*)(<[ \\n\\r\\t]*/[ \\n\\r\\t]*$tagname(>|[^>]*>))(.*)$";
    }

	function text_between($str, $start, $end)
	{
		if (strpos($str, $start) === false) return false; 
		$start_pos = strpos($str, $start) + strlen($start);
		if (strpos($str, $end, $start_pos) === false) return false; 
		$end_pos = strpos($str, $end, $start_pos);
		return substr($str, $start_pos, $end_pos - $start_pos);
	}	
		
	function echoDebug($str)
	{
		echo "<pre>";
		print_r($str);
		echo "</pre>";
	}
?>

</body>
</html>