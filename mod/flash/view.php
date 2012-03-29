<?PHP  
//
//    Part of Flash Activity Module :
//    A Moodle activity module that takes care of a lot of functionality for Flash
//    movie developeres who want their movies to work with Moodle
//    to use Moodles grades table, configuration, backup and restore features etc.
//    Copyright (C) 2004, 2005  James Pratt
//    Contact  : me@jamiep.org http://jamiep.org
//
//    Developed for release under GPL,
//    funded by AGAUR, Departament d'Universitats, Recerca i Societat de la
//    Informacieneralitat de Catalunya.
//
//    This program is free software; you can redistribute it and/or modify
//    it under the terms of the GNU General Public License as published by
//    the Free Software Foundation; either version 2 of the License, or
//    (at your option) any later version.
//
//    This program is distributed in the hope that it will be useful,
//    but WITHOUT ANY WARRANTY; without even the implied warranty of
//    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//    GNU General Public License for more details.
//
//    You should have received a copy of the GNU General Public License
//    along with this program; see flash/license.txt;
//      if not, write to the Free Software
//    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA 
 
/// This page prints a particular instance of a flash activity
    require_once("../../config.php");
    require_once("lib.php");
    require_once($CFG->dirroot.'/mod/flash/SWFObject/swfobject.php');

 function createnewmovie($dir, $moviename, $width, $height, $framerate) {
    global $CFG;
    require "$CFG->dirroot/mod/flash/swfphp/swf.php"; 
     $flash = &new SWF("$CFG->dirroot/mod/flash/$moviename.swf"); 
     if($flash->is_valid()){ 
        
        // change dimensions and framerate
        $flash->setFrameRate($framerate); 
        $flash->setMovieSize($width, $height);
        
        flash_check_data_dir_exists($dir,true) ;
        // and write a new file... 
        if(!$flash->write("$CFG->dataroot/$dir/$moviename{$width}x{$height}x{$framerate}.swf",1))
        {
            return false;
        } else
        {
            return true;
        }
     } else
    {
        return false;
    }
}     
function flash_check_data_dir_exists($dir,$create=false) {

    global $CFG; 

    $status = true;
    if(!is_dir($CFG->dataroot.$dir)) {
        if (!$create) {
            $status = false;
        } else {
            umask(0000);
            $status = make_upload_directory ($dir,$CFG->directorypermissions);
        }
    }
    return $status;
}
function flash_print_header($course, $cm, $flash, $showheader='1', $size='0', $flashmovierec=NULL)
{
	/// Print the page header
	global $CFG;
	if ($showheader=='1')
	{
		
		if ($course->category) {
			$navigation = "<A HREF=\"../../course/view.php?id=$course->id\">$course->shortname</A> ->";
		}
	
		$strflashs = get_string("modulenameplural", "flash");
		$strflash  = get_string("modulename", "flash");
	
		$CFG->unicode=true;//force the use of utf-8 for this page
        if ($size)
        {
            $meta=flash_so_js_include(false).'<link rel="stylesheet" type="text/css" href="'.
                    $CFG->wwwroot.'/mod/flash/fullbrowser_wh.css'.
                    '" />'."\n";
        } else
        {
            $meta='';
        }
        
		print_header("$course->shortname: $flash->name", "$course->fullname",
					 "$navigation <A HREF=index.php?id=$course->id>$strflashs</A> -> $flash->name", 
					  "", $meta, true, update_module_button($cm->id, $course->id, $strflash), 
							navmenu($course, $cm));
	} else
	{
        if ($flashmovierec) {
            $bgcolor="background-color:$flashmovierec->bgcolor;
";
        }else {
            $bgcolor='';
        }
	    echo <<<EOF
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<!-- saved from url=(0014)about:internet -->
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>$course->shortname: $flash->name</title>
EOF;
flash_so_js_include(true);
        echo <<<EOF
<style type="text/css">
	
	/* hide from ie on mac \*/
	html {
		height: 100%;
		overflow: hidden;
	}
	
	#flashcontent {
		height: 100%;
	}
	a#movie {
		height: 100%;
	}
	/* end hide */

	body {
		height: 100%;
		margin: 0;
		padding: 0;
        $bgcolor
	}
	#layout{
		height: 100%;
		width: 100%;
	}
	

</style>
</head>
<body>
EOF;
/*	    $meta=flash_so_js_include(false).'<link rel="stylesheet" type="text/css" href="'.
                    $CFG->wwwroot.'/mod/flash/fullbrowser.css'.
                    '" />'."\n";
		print_header("$course->shortname: $flash->name", '', '', '', $meta);
		
*/		
	}

}
/**
 * Return html used to embed Flash movie / movie loader in page
 *
 * @param    string  $baseUrl The base url to use when loading resources from within the Flash movie with a relative URL. Should point to $CFG->wwwroot/mod/flash/resources directory for loading fonts
 * @param    string  $movieloaderurl The URL of the first movie to load main movie or preloader
 * @param    string  $moviename Name of the movie
 * @param    string  $height Hight in pixels or percent
 * @param    string  $width Width in pixels or percent
 * @param    string  $bgcolor background color for the movie
 * @param    string  $flashMovieSess The random movie session key generated for this session movie needs this to access services
 * @param    string  $defaultGatewayUrl URL at which to find the service's gateway
 * @param    string  $fonts Fonts for the preloader to load
 * @param    string  $doneUrl The URL to go to after the movie is done, to clean up session, this URL will redirect to another page - course page or results page or other
 * @return   string html to embed Flash movie
 */
function flash_embed_html($baseUrl, $movieloaderurl, $moviename, $height, $width, $bgcolor, $flashMovieSess, $defaultGatewayUrl, $fonts, $doneUrl, $version)
{
        global $CFG;
        $movieUrl=urlencode("$CFG->wwwroot/mod/flash/movies/$moviename/$moviename.swf");
        if (!empty($baseUrl)){        
           return flash_so_js_include(false)."\n<a id=\"movie\" name=\"movie\"></a>
	<div id=\"flashcontent\">
		<strong>You need to upgrade your Flash Player. This movie ($moviename) requires Flash Player $version</strong>
	</div>
	<script type=\"text/javascript\">
        // <![CDATA[
        
        var so = new SWFObject(\"$movieloaderurl\", \"$moviename\", \"$width\", \"$height\", \"$version\", \"$bgcolor\");
        so.addParam(\"BASE\", \"$baseUrl\");
        so.addParam(\"menu\", \"false\");
        so.addParam(\"allowScriptAccess\", \"sameDomain\");
        so.addParam(\"align\", \"lt\");
        so.addVariable(\"POMovieSess\", \"$flashMovieSess\");
        so.addVariable(\"POGatewayURL\", \"$defaultGatewayUrl\");
        so.addVariable(\"PODoneURL\", \"$doneUrl\");
        so.addVariable(\"POMovieURL\", \"$movieUrl\");
        so.addVariable(\"POFonts\", \"$fonts\");

        so.write(\"flashcontent\");
        // ]]>
    </script>
    <script>if (document.anchors['movie']!=null){document.anchors['movie'].focus()};</script>";            
        } else {
           return flash_so_js_include(false)."\n<a id=\"movie\" name=\"movie\"></a>
	<div id=\"flashcontent\">
		<strong>You need to upgrade your Flash Player. This movie ($moviename) requires Flash Player $version</strong>
	</div>

	<script type=\"text/javascript\">
        // <![CDATA[
        
        var so = new SWFObject(\"$movieloaderurl\", \"$moviename\", \"$width\", \"$height\", \"$version\", \"$bgcolor\");
        so.addParam(\"BASE\", \"$CFG->wwwroot/mod/flash/movies/$moviename/\");
        so.addParam(\"menu\", \"false\");
        so.addParam(\"allowScriptAccess\", \"sameDomain\");
        so.addParam(\"align\", \"lt\");
        so.addVariable(\"POMovieSess\", \"$flashMovieSess\");
        so.addVariable(\"POGatewayURL\", \"$defaultGatewayUrl\");
        so.addVariable(\"PODoneURL\", \"$doneUrl\");
        so.addVariable(\"POMovieURL\", \"$movieUrl\");
        so.addVariable(\"POFonts\", \"$fonts\");

        so.write(\"flashcontent\");
        // ]]>
    </script>
    <script>if (document.anchors['movie']!=null){document.anchors['movie'].focus()};</script>";                  
        }

}

    $id=optional_param('id', NULL, PARAM_INT);    // Course Module ID, or
    $a=optional_param('a', NULL, PARAM_INT);     // flash ID
    $showall=optional_param('showall', NULL, PARAM_INT);     // show full table ?
    $access=optional_param('access', NULL, PARAM_INT);     // show info about just one access of the quiz if non zero ?
    $sess_token=optional_param('sess_token', NULL, PARAM_ALPHANUM);     // Flash sess token 
    $goto=optional_param('goto', NULL, PARAM_LOCALURL);     // where to go to next if deleting a Flash sess token 

    if ($id) {
        if (! $cm = get_record("course_modules", "id", $id)) {
            error("Course Module ID was incorrect");
        }
    
        if (! $course = get_record("course", "id", $cm->course)) {
            error("Course is misconfigured");
        }
    
        if (! $flash = get_record("flash", "id", $cm->instance)) {
            error("Course module is incorrect");
        }

    } elseif ($a) {
        if (! $flash = get_record("flash", "id", $a)) {
            error("Course module is incorrect");
        }
        if (! $course = get_record("course", "id", $flash->course)) {
            error("Course is misconfigured");
        }
        if (! $cm = get_coursemodule_from_instance("flash", $flash->id, $course->id)) {
            error("Course Module ID was incorrect");
        }
    } elseif ($sess_token)
    {
        //used to redirect browser after activity is over :
		$flashid=$_SESSION['flashSess'][$sess_token]['flashid'];
        unset($_SESSION['flashSess'][$sess_token]);
		if ($goto=='course')
		{
			//goto course view
			if (! $flash = get_record("flash", "id", $flashid)) {
				error("Course module is incorrect");
			}
			redirect("$CFG->wwwroot/course/view.php?id=".$flash->course."#nextsection");
		}elseif ($goto==NULL)
		{
			redirect("$CFG->wwwroot/mod/flash/view.php?a=$flashid");
		}else // goto is a url
		{
			$goto=stripslashes($goto);
			redirect($goto);
		}
    } else
    {
        error("Course Module ID was not provided");
        
    }
    require_login($course->id);
    $modform = "$CFG->dirroot/mod/flash/movies/{$flash->moviename}/mod.html";
    //to_config is 0 when config is finished
    //it is negative for config pages defined in this file
    //it is positive for config pages defined for a movie in movies/{moviename}/mod.html
    if ($flash->to_config>0 &&(file_exists($modform)))
    {
        if (!isteacheredit($course->id)) {
            error("This activity is not yet set up properly!");
        }

        if (! $module = get_record("modules", "id", $cm->module)) {
            error("This module doesn't exist");
        }

        if (! $form = get_record($module->name, "id", $cm->instance)) {
            error("The required instance of this module doesn't exist");
        }
        
        if (! $cw = get_record("course_sections", "id", $cm->section)) {
            error("This course section doesn't exist");
        }

        if (isset($return)) {  
            $SESSION->returnpage = "$CFG->wwwroot/mod/$module->name/view.php?id=$cm->id";
        }
        
        //set up variables for form pretty much in the same way as course/mod.php
        //we are duplicating functionality in course/mod.php but I don't see a way
        //round this without changing course/mod.php
        $SESSION->sesskey = !empty($USER->id) ? $USER->sesskey : '';

        $form->coursemodule = $cm->id;
        $form->section      = $cm->section;     // The section ID
        $form->course       = $course->id;
        $form->module       = $module->id;
        $form->modulename   = $module->name;
        $form->instance     = $cm->instance;
        $form->mode         = "update";

        $sectionname    = get_string("name$course->format");
        $fullmodulename = strtolower(get_string("modulename", $module->name));

        if ($form->section) {
            $heading->what = $fullmodulename;
            $heading->in   = "$sectionname $cw->section";
            $pageheading = get_string("updatingain", "moodle", $heading);
        } else {
            $pageheading = get_string("updatinga", "moodle", $fullmodulename);
        }
        if (!empty($form->config))
        {
            $form->config=unserialize($form->config);
        }
        if (!empty($form->answers))
        {
            $form->answers=unserialize($form->answers);
        }
        $streditinga = get_string("editinga", "moodle", $fullmodulename);
        $strmodulenameplural = get_string("modulenameplural", $module->name);

        $CFG->unicode=true;//force the use of utf-8 for this page
        if ($course->category) {
            print_header("$course->shortname: $streditinga", "$course->fullname",
                         "<A HREF=\"$CFG->wwwroot/course/view.php?id=$course->id\">$course->shortname</A> -> 
                          <A HREF=\"$CFG->wwwroot/mod/$module->name/index.php?id=$course->id\">$strmodulenameplural</A> -> 
                          $streditinga", $focuscursor, "", false);
        } else {
            print_header("$course->shortname: $streditinga", "$course->fullname",
                         "$streditinga", $focuscursor, "", false);
        }
    
        unset($SESSION->modform); // Clear any old ones that may be hanging around.
    
    

        if ($usehtmleditor = can_use_html_editor()) {
            $defaultformat = FORMAT_HTML;
            $editorfields = '';
        } else {
            $defaultformat = FORMAT_MOODLE;
        }

        $icon = "<img align=absmiddle height=16 width=16 src=\"$CFG->modpixpath/$module->name/icon.gif\">&nbsp;";

        print_heading_with_help($pageheading, "mods", $module->name, $icon);
        print_simple_box_start("center", "", "$THEME->cellheading");
        $submitformto="$CFG->wwwroot/course/mod.php?update=$cm->id&return=true";
        include_once($modform);
        print_simple_box_end();

        if ($usehtmleditor and empty($nohtmleditorneeded)) { 
            use_html_editor($editorfields);
        }    
        print_footer($course);
        exit();
    }

        

    add_to_log($course->id, "flash", "view", "view.php?id=$cm->id", "$flash->id", $cm->id);
	
	//first let's work out whether to display the results / splash page or the movie
    $action="table";
    if ($_GET["do"]=="splash")
    {
        if ($flash->usesplash=="1") 
        {
            $action="splash";
        } else
        {
            $action="test";
        }
    } elseif ($_GET["do"]=="test")
    {
        $action="test";
    } else
    {
        if (isteacher($course->id))   {
			$flash_accesses_sql="SELECT  accesses.* ". 
            "FROM ".$CFG->prefix."flash_accesses AS accesses, ".
            $CFG->prefix."flash_answers AS answers ".
            "WHERE ".
            "accesses.flashid=".$flash->id." ".
            "AND answers.accessid=accesses.id";
        } else {
			$flash_accesses_sql="SELECT  accesses.* ". 
            "FROM ".$CFG->prefix."flash_accesses AS accesses, ".
            $CFG->prefix."flash_answers AS answers ".
            "WHERE ".
            "accesses.userid=".$USER->id." AND accesses.flashid=".$flash->id." ".
            "AND answers.accessid=accesses.id";
        }
        if (count_records_sql($flash_accesses_sql)==0) 
        {
            if ($flash->usesplash=="1") 
            {
                $action="splash";
            } else
            {
                $action="test";
            }
        } elseif (!isteacher($course->id))
        {
            $action="yourtable";
        }
    }
/// Print the main part of the page
    if ($action=="splash") //splash page??
    {
		flash_print_header($course, $cm, $flash);
        echo '<p><div align="center"><a href="view.php?id='.$cm->id.'&do=test#movie">'.get_string('continue').'</a></div></p>';
        print_simple_box( format_text($flash->splash, $flash->splashformat) , "center");
        echo '<p><div align="center"><a href="view.php?id='.$cm->id.'&do=test#movie">'.get_string('continue').'</a></div></p>';
		print_footer($course);
    } elseif ($action=="test")
    {
        if (!$flashmovierec=get_record('flash_movies', 'moviename', $flash->moviename))
        {
            error('Can\'t find movie record for movie '.$flash->moviename.' in \'flash_movie\'!');
        }
       	flash_print_header($course, $cm, $flash, $flash->showheader, $flash->size, $flashmovierec);
        //construct movie name for laoder url if we are using one.
        //Creates a new movie with the same dimensions and framerate of the main movie if one hasn't been created already.
        $moviecachedir="flash_moviecache";
        $servicePath="$CFG->dirroot/mod/flash/movies/{$flash->moviename}/service.php";
        if (!$flashMovieSess=flash_get_flashMovieSess($flash->id, $course->id, $cm->id, 'service', $servicePath))
        {
            error("Couldn't set up flash session!");
            
        }
        $doneUrl=urlencode("$CFG->wwwroot/mod/flash/view.php?sess_token=".$flashMovieSess);
        $defaultGatewayUrl=urlencode("$CFG->wwwroot/mod/flash/server/MoodleGateway.php");
        if ($flash->usepreloader=="0") 
        {
            $firstloadurl="movies/{$flash->moviename}/{$flash->moviename}.swf";//no preloader
        } else
        {
            //baseUrl is the address relative to which content is loaded from within flash when a relative url is used :
            $baseUrl="$CFG->wwwroot/mod/flash/resources/";
            $fonts=urlencode($flash->fonts); 
            if ($flash->usepreloader=="2") // mx learning interaction tracking
            {   
                $movieloadername='mxli_loader';
            } elseif ($flash->usepreloader=="1") // font and movie preloader
            {
                $movieloadername='loader';
            } 
            $movieloaderpath="$CFG->dataroot/$moviecachedir/$movieloadername/$movieloadername{$flashmovierec->width}x{$flashmovierec->height}x{$flashmovierec->framerate}.swf";
            if (!file_exists($movieloaderpath))
            {
                //create movie loader with new dimensions and framerate
                if (!createnewmovie("$moviecachedir", $movieloadername, $flashmovierec->width, $flashmovierec->height, $flashmovierec->framerate))
                {
                    error("Couldn't create movie $movieloaderpath");
                }
            }
            if ($CFG->slasharguments) { 
                $firstloadurl="loader.php/$movieloadername/{$flashmovierec->width}/{$flashmovierec->height}/{$flashmovierec->framerate}?ver=150";
            } else
            {
                $firstloadurl="loader.php?file=$movieloadername/{$flashmovierec->width}/{$flashmovierec->height}/{$flashmovierec->framerate}&ver=150";
                
            }

        } 
        
        if ($flash ->showgrades && !empty($flash->gradingmethod))
        {
            $gradingmethodstring=get_string('gradingmethod_'.$flash->gradingmethod,'flash');
            echo '<p><strong><div align=\'center\'>'.get_string('gradingmethodused','flash', $gradingmethodstring);
            helpbutton("gradingmethodtype", get_string("gradingmethod", "flash"), "flash");
            echo '</div></strong></p>';
        }elseif ($flash ->size!='1') 
        {
            echo "<br />\n";
        }
        if ($flash ->size=='1')  { // movie occupies 100 % height and width
            echo (flash_embed_html($baseUrl, $firstloadurl, $flash->moviename, 
            '100%'/*height*/,'100%'/*width*/, $flashmovierec->bgcolor, 
            $flashMovieSess, $defaultGatewayUrl, $fonts, $doneUrl, $flashmovierec->version));
        } elseif ($flash ->showheader=='1') {
            print_simple_box_start("center", "", "$THEME->cellheading");
            echo (flash_embed_html($baseUrl, $firstloadurl, $flash->moviename, 
                    $flashmovierec->height /*height*/, $flashmovierec->width /*width*/, 
                    $flashmovierec->bgcolor, $flashMovieSess, $defaultGatewayUrl, $fonts, $doneUrl, $flashmovierec->version));
            print_simple_box_end();
        } else {
            echo "<table id='layout'><tr><td valign='center' align='center'><table border='0'><tr><td>";
            echo (flash_embed_html($baseUrl, $firstloadurl, $flash->moviename, 
                    $flashmovierec->height /*height*/, $flashmovierec->width /*width*/, 
                    $flashmovierec->bgcolor, $flashMovieSess, $defaultGatewayUrl, $fonts, $doneUrl, $flashmovierec->version));
            echo "</td></tr></table></td></tr></table>";
        }
		if ($flash->showheader=='1'){
			print_footer($course);
		}else {
			echo '</body>
</html>';
		}

    } elseif ($action=="yourtable") 
    {
       	flash_print_header($course, $cm, $flash);
        echo '<p><div align="center"><a href="view.php?id='.$cm->id.'&do=splash#movie">'.get_string('dotest', 'flash').'</a></div></p>';
        flash_print_table_of_accesses($flash, $USER, $showall);
        echo '<p><div align="center"><a href="view.php?id='.$cm->id.'&do=splash#movie">'.get_string('dotest', 'flash').'</a></div></p>';
		print_footer($course);
    } else
    {
       	flash_print_header($course, $cm, $flash);
        echo '<p><div align="center"><a href="view.php?id='.$cm->id.'&do=splash#movie">'.get_string('dotest', 'flash').'</a></div></p>';
        flash_print_table_of_accesses($flash,"", $showall, $access);
        echo '<p><div align="center"><a href="view.php?id='.$cm->id.'&do=splash#movie">'.get_string('dotest', 'flash').'</a></div></p>';
		print_footer($course);
        
    }

/// Finish the page

?>