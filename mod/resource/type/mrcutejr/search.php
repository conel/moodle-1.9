<?php
/*
 **************************************************************************
 *                                                                        *
 *                                                                        *
 *                            THIS SCRIPT                                 *
 *                                and                                     *
 *                         www.bydistance.com                             *
 *                 brought to you by Visions Encoded Inc.                 *
 *                                                                        *
 *                                                                        *
 *                                                                        *
 *             Visit us online at http://visionsencoded.com/              *
 *                You Bring The Vision, We Make It Happen                 *
 **************************************************************************
 **************************************************************************
 * NOTICE OF COPYRIGHT                                                    *
 *                                                                        *
 * Copyright (C) 2009                                                     *
 *                                                                        *
 * This program is free software; you can redistribute it and/or modify   *
 * it under the terms of the GNU General Public License as published by   *
 * the Free Software Foundation; either version 2 of the License, or      *
 * (at your option) any later version.                                    *
 *                                                                        *
 * This program is distributed in the hope that it will be useful,        *
 * but WITHOUT ANY WARRANTY; without even the implied warranty of         *
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the          *
 * GNU General Public License for more details:                           *
 *                                                                        *
 *                  http://www.gnu.org/copyleft/gpl.html                  *
 *                                                                        *
 *                                                                        *
 *                                                                        *
 **************************************************************************
 */


/**
 * Renders the form used to search the MrCUTE Jr. repository and performs the search query
 */

require_once('config.php');//this file will also call Moodle config.php for us
require_once('forms/form_search_mrcutejr.php');

//must be an authorized user (aka NOT a student only - otherwise they could access *any* site file)
require_authorized_mrcutejr_editor();

//get language strings
$strmenustring   = lang('resourcetypemrcutejr');
$strfindtitle    = lang('searchheader');
$strchoose       = lang('choose');
$strpreview      = lang('preview');
$strdesc         = lang('description');
$strkeywords     = lang('keywords');
$strlastmodified = lang('modifieddate');
$strtype         = lang('type');
$stricon         = lang('selecturltype');
$stredit         = lang('edit');
$strdelete       = lang('delete');
$strnoresults    = lang('noresults');

//check for form submission search term
$mform = new form_search_mrcutejr(null, null, 'get');//can be url GET search
$search = '';
$fromform = $mform->get_data();
if($fromform && isset($fromform->search)) {
    $search = trim(strip_tags($fromform->search)); // trim & clean
}
if(empty($search)) {//then check URL parameters for search term
    $psearch = optional_param('search', '', PARAM_NOTAGS);
    if(!empty($psearch)) {
        $search = trim($psearch);
    }
}

//blockmode==1 means called from block search so we're not adding a resource
$blockmode = optional_param('blockmode', 0, PARAM_BOOL);
$toform = new object();
$toform->blockmode = $blockmode;
$mform->set_data($toform); //add blockmode flag to our form

//isajaxcall==1 means called from ajax search - should only ever occur when creating new or editing existing resource
$isajaxcall = ($blockmode)? 0 : optional_param('isajaxcall', 0, PARAM_BOOL);//blockmode with ajax not possible yet...

//add custom css and javascript files into standard header
$CFG->stylesheets[] = 'mrcutejr.css';
require_js('mrcutejr.js');//used to move data from popup to parent window

//print standard head
if(!$isajaxcall) {
    print_header($strfindtitle,'','',$mform->focus('id_search'),'',false);//title, no cache
}

// nkowald - 2010-02-09 - Add jwplayer includes
// <script type="text/javascript" src="'.$CFG->wwwroot.'/mod/resource/type/mrcutejr/jwplayer/jwbox/jquery.js"></script>
echo '
<script type="text/javascript" src="'.$CFG->wwwroot.'/mod/resource/type/mrcutejr/jwplayer/jwbox/jquery.jwbox.js"></script>
<link rel="stylesheet" type="text/css" href="'.$CFG->wwwroot.'/mod/resource/type/mrcutejr/jwplayer/jwbox/jwbox.css" />';

//handle search request
if($search) {
    if(strpos($search,"MrCuteJr")===0) {
        $reqID = str_replace("MrCuteJr", "", $search);
        if(is_numeric($reqID) && $reqID>0) {
            if($resource = get_record('resource_mrcutejr', 'id', $reqID)) {
                $rslts[] = $resource;
            }
        }
    }
    if(empty($rslts)) {
        //we got a search request so load database lib
        require_once ($CFG->libdir.'/dmllib.php');
        //get our safe search terms
        $searchterms = explode(" ", $search);//search for words independently
        foreach ($searchterms as $key => $word) {
            if(strlen($word) < 3) {//ignore any words 2 chars or shorter
                unset($searchterms[$key]);
            } else {
                //$searchterms[$key] = mysql_real_escape_string(trim($word));
                $searchterms[$key] = trim($word);
            }
        }
        $search = trim(implode(" ", $searchterms));
        //just a last check to verify we have something to search after cleaning
        if(empty($search)) {
            err('mrcutejr_nosearchterm');
        }
        //to allow case-insensitive search for postgesql (thx MrCUTE2!)
        if ($CFG->dbfamily == 'postgres') {
            $LIKE = 'ILIKE';
            $NOTLIKE = 'NOT ILIKE';   // case-insensitive
            $REGEXP = '~*';
            $NOTREGEXP = '!~*';
        } else {
            $LIKE = 'LIKE';
            $NOTLIKE = 'NOT LIKE';
            $REGEXP = 'REGEXP';
            $NOTREGEXP = 'NOT REGEXP';
        }
        
        //select portions of query - we basically query everything!
        $qt = '';//title
        $qd = '';//description
        $qk = '';//keywords
        $qf = '';//reference     (the reference we give to moodle resource "location" field)
        $qr = '';//realreference (aka real reference to the actual resource)
        
        //construct our query SELECT statement
        foreach ($searchterms as $t) {
            //for additional keword loops append ' AND '
            if ($qt) {
                $qt .= ' AND ';
            }
            if ($qd) {
                $qd .= ' AND ';
            }
            if ($qk) {
                $qk .= ' AND ';
            }
            if ($qf) {
                $qf .= ' AND ';
            }
            if ($qr) {
                $qr .= ' AND ';
            }
            //+ means include, - means exclude (?)
            if(substr($t,0,1) == '+') {
                $t = substr($t,1);
                $qt.=" title "        ."$REGEXP '(^|[^a-zA-Z0-9])$t([^a-zA-Z0-9]|$)' ";
                $qd.=" description "  ."$REGEXP '(^|[^a-zA-Z0-9])$t([^a-zA-Z0-9]|$)' ";
                $qk.=" keywords "     ."$REGEXP '(^|[^a-zA-Z0-9])$t([^a-zA-Z0-9]|$)' ";
                $qf.=" reference "    ."$REGEXP '(^|[^a-zA-Z0-9])$t([^a-zA-Z0-9]|$)' ";
                $qr.=" realreference "."$REGEXP '(^|[^a-zA-Z0-9])$t([^a-zA-Z0-9]|$)' ";
            } else if(substr($t,0,1) == "-") {
                $t = substr($t,1);
                $qt.=" title "        ."$NOTREGEXP '(^|[^a-zA-Z0-9])$t([^a-zA-Z0-9]|$)' ";
                $qd.=" description "  ."$NOTREGEXP '(^|[^a-zA-Z0-9])$t([^a-zA-Z0-9]|$)' ";
                $qk.=" keywords "     ."$NOTREGEXP '(^|[^a-zA-Z0-9])$t([^a-zA-Z0-9]|$)' ";
                $qf.=" reference "    ."$NOTREGEXP '(^|[^a-zA-Z0-9])$t([^a-zA-Z0-9]|$)' ";
                $qr.=" realreference "."$NOTREGEXP '(^|[^a-zA-Z0-9])$t([^a-zA-Z0-9]|$)' ";
            } else {
                $qt.=' title '        .$LIKE . ' \'%'. $t .'%\' ';
                $qd.=' description '  .$LIKE . ' \'%'. $t .'%\' ';
                $qk.=' keywords '     .$LIKE . ' \'%'. $t .'%\' ';
                $qf.=' reference '    .$LIKE . ' \'%'. $t .'%\' ';
                $qr.=' realreference '.$LIKE . ' \'%'. $t .'%\' ';
            }
        }//end foreach
        $or = ' OR ';
        $selectsql = '('.$qt.$or.$qd.$or.$qk.$or.$qf.$or.$qr.')';
        $sort = "modifieddate DESC";
        $qry = 'SELECT * FROM '.$CFG->prefix.'resource_mrcutejr WHERE ';
        $qry .= $selectsql.' ORDER BY '.$sort;

        // nkowald - 2010-02-09 - Pagination code
        
        // How many adjacent pages should be shown on each side?
        $adjacents = 3;
        // Get total number of returned results
        $total_pages = count(get_records_sql($qry));
        $limitnum = 5; //TODO:  perhaps make this an admin configurable setting?
        
        /* Setup vars for query. */
        // Get this from server variable.
        $targetpage = $_SERVER['REQUEST_URI']; //your file name  (the name of this file)
        $del_param = strpos($targetpage,'&page=');
        if ($del_param) {
            $targetpage = substr($targetpage,0,$del_param);
        }
        $page = $_GET['page'];
        $start = ($page) ? ($page - 1) * $limitnum : 0;
        if ($page == 0) $page = 1;					//if no page var is given, default to 1.

        $rslts = false;
        $rslts = get_records_sql($qry, $start, $limitnum);

        $prev = $page - 1;							//previous page is page - 1
        $next = $page + 1;							//next page is page + 1
        $lastpage = ceil($total_pages/$limitnum);	//lastpage is = total pages / items per page, rounded up.
        $lpm1 = $lastpage - 1;						//last page minus 1
        
        /* 
            Now we apply our rules and draw the pagination object. 
            We're actually saving the code to a variable in case we want to draw it more than once.
        */
        $pagination = "";
        if($lastpage > 1)
        {	
            $pagination .= "<div class=\"pagination\">";
            //previous button
            if ($page > 1) 
                $pagination.= "<a href=\"$targetpage&page=$prev\">< previous</a>";
            else
                $pagination.= "<span class=\"disabled\">< previous</span>";	
            
            //pages	
            if ($lastpage < 7 + ($adjacents * 2))	//not enough pages to bother breaking it up
            {	
                for ($counter = 1; $counter <= $lastpage; $counter++)
                {
                    if ($counter == $page)
                        $pagination.= "<span class=\"current\">$counter</span>";
                    else
                        $pagination.= "<a href=\"$targetpage&page=$counter\">$counter</a>";					
                }
            }
            elseif($lastpage > 5 + ($adjacents * 2))	//enough pages to hide some
            {
                //close to beginning; only hide later pages
                if($page < 1 + ($adjacents * 2))		
                {
                    for ($counter = 1; $counter < 4 + ($adjacents * 2); $counter++)
                    {
                        if ($counter == $page)
                            $pagination.= "<span class=\"current\">$counter</span>";
                        else
                            $pagination.= "<a href=\"$targetpage&page=$counter\">$counter</a>";					
                    }
                    $pagination.= "...";
                    $pagination.= "<a href=\"$targetpage&page=$lpm1\">$lpm1</a>";
                    $pagination.= "<a href=\"$targetpage&page=$lastpage\">$lastpage</a>";		
                }
                //in middle; hide some front and some back
                elseif($lastpage - ($adjacents * 2) > $page && $page > ($adjacents * 2))
                {
                    $pagination.= "<a href=\"$targetpage&page=1\">1</a>";
                    $pagination.= "<a href=\"$targetpage&page=2\">2</a>";
                    $pagination.= "...";
                    for ($counter = $page - $adjacents; $counter <= $page + $adjacents; $counter++)
                    {
                        if ($counter == $page)
                            $pagination.= "<span class=\"current\">$counter</span>";
                        else
                            $pagination.= "<a href=\"$targetpage&page=$counter\">$counter</a>";					
                    }
                    $pagination.= "...";
                    $pagination.= "<a href=\"$targetpage&page=$lpm1\">$lpm1</a>";
                    $pagination.= "<a href=\"$targetpage&page=$lastpage\">$lastpage</a>";		
                }
                //close to end; only hide early pages
                else
                {
                    $pagination.= "<a href=\"$targetpage&page=1\">1</a>";
                    $pagination.= "<a href=\"$targetpage&page=2\">2</a>";
                    $pagination.= "...";
                    for ($counter = $lastpage - (2 + ($adjacents * 2)); $counter <= $lastpage; $counter++)
                    {
                        if ($counter == $page)
                            $pagination.= "<span class=\"current\">$counter</span>";
                        else
                            $pagination.= "<a href=\"$targetpage&page=$counter\">$counter</a>";					
                    }
                }
            }
            
            //next button
            if ($page < $counter - 1) 
                $pagination.= "<a href=\"$targetpage&page=$next\">next ></a>";
            else
                $pagination.= "<span class=\"disabled\">next ></span>";
            $pagination.= "</div>\n";		
        }




        
    }
    //show our results
    $strsearchhdr = lang('resultsheader', $search);
    if($rslts) {
        //display search form at top of results
        if(!$isajaxcall) {
            $mform->display();
        }
        //some vars for our results
        $previewbaseurl = $CFG->wwwroot.'/mod/resource/type/mrcutejr/preview.php?file=';
        //$previewbaseurl = $CFG->wwwroot.'/file.php/mrcutejr/'
        //compile our results html
        $rowsHTML = '';
        $rowclass = 'even';
        $editicon = "<img src=\"$CFG->pixpath/t/edit.gif\" ".
                    "alt=\"$stredit\" title=\"$stredit\" />";
        $deleteicon = "<img src=\"$CFG->pixpath/t/delete.gif\" ".
                      "alt=\"$strdelete\" title=\"$strdelete\" />";
        require_once('get_icon.php');
        foreach($rslts as $record) {
            $rowclass = ($rowclass=='even') ? 'odd' : 'even';
            $icon = "$CFG->pixpath/".mod_resource_mrcutejr_get_icon($record);
            $jref = "MrCuteJr".$record->id."_".$record->reference;
            $jtitle = inline_javascript_encode($record->title);
            $jdesc = inline_javascript_encode($record->description);
            $jpopup = ($record->windowoptions) ? $record->windowoptions : '';
            $chooselink='<a href="#" title="'.$strchoose.'" '.
                        ' onclick="set_value(\''.$jref.
                            '\', \''.$jtitle.'\', '.
                            '\''.$jdesc.'\', '.
                            '\''.$jpopup.'\')"'.
                        '>'.$strchoose.'</a>';
            $previewhref = $record->realreference;              //url href
            $resourcetype = 'URL';
            if (strstr($record->reference, 'index.php?video=')) {
                $resourcetype = 'Streaming Video';
            } else if($record->isfile) {
                $previewhref = $previewbaseurl.$previewhref;    //file href
                $resourcetype = 'File';
            }
            $previewlink = '<a href="'.$previewhref.'" title="'.$strpreview.
                           '" target="_blank" >'.$record->title.'</a>';
            $date = date("Y/m/d (M)", $record->modifieddate);
            $edithref = "edit.php?edit=$record->id&blockmode=$blockmode";
            $editlink = ($isajaxcall)? '' : '<a href="'.$edithref.'" '.
                        'title="'.$stredit.'">'.$editicon.'</a>';
            $deletelink = ($isajaxcall)? '' : '<a href="#" '.
                          'title="'.$strdelete.'">'.$deleteicon.'</a>';
            //create our html
            $rowsHTML .= '<tr class="'.$rowclass.'">';
            // nkowald - 2010-02-09 - Adding the ability to preview media here

            // If called from ajax: show icon only - no previews
            if ($isajaxcall) {
                $rowsHTML .= '<td><img src="'.$icon.'" alt="" /></td>';
            } else {
                if (strpos($icon,'video')) {
                    // is video
                    $rowsHTML .= '<td>
                    <div class="jwbox">
                        <a href="'.$previewhref.'" id="jwplayer_link" title="Preview Video"><span>Click to preview video</span></a>
                        <div class="jwbox_hidden">
                            <div class="jwbox_content">
                                <object width="320" height="240" type="application/x-shockwave-flash" id="ply" name="ply" data="'.$CFG->wwwroot.'/mod/resource/type/mrcutejr/jwplayer/player.swf">
								<param name="movie" value="'.$CFG->wwwroot.'/mod/resource/type/mrcutejr/jwplayer/player.swf" />
                                <param name="allowfullscreen" value="true"/>
                                <param name="allowscriptaccess" value="always"/>
                                <param name="flashvars" value="file='.$previewhref.'"/></object>
                            </div>
                        </div>
                    </div></td>';
                } else if (strpos($icon,'audio')) {
					 // is audio file
                    $rowsHTML .= '<td>
                    <div class="jwbox">
                        <a href="'.$previewhref.'" id="jwplayer_audio_link" title="Preview Song"><span>Click to preview song</span></a>
                        <div class="jwbox_hidden">
                            <div class="jwbox_content">
                                <object width="320" height="65" type="application/x-shockwave-flash" id="ply" name="ply" data="'.$CFG->wwwroot.'/mod/resource/type/mrcutejr/jwplayer/player.swf">
								<param name="movie" value="'.$CFG->wwwroot.'/mod/resource/type/mrcutejr/jwplayer/player.swf" />
                                <param name="allowfullscreen" value="true"/>
                                <param name="allowscriptaccess" value="always"/>
                                <param name="flashvars" value="file='.$previewhref.'"/></object>
                            </div>
                        </div>
                    </div></td>';
				} else if (strpos($icon,'image')) {
                    // is Image file
                    $rowsHTML .= '<td>
                        <div class="jwbox">
                            <img src="'.$previewhref.'" alt="" title="Enlarge Image" width="120" height="90" style="cursor:pointer;" />
                            <div class="jwbox_hidden">
                                <div class="jwbox_content">
                                    <img alt="if image not showing right click this text then choose reload image" title="" src="'.$previewhref.'" width="400" />
                                </div>
                            </div>
                        </div>
                        </td>';

                } else if ($resourcetype == 'Streaming Video') {
               
                    // Check if URL is a streaming video URL and add our streaming media thumbnail
                    $rowsHTML .= '<td><center><a href="'.$previewhref.'" target="_blank"><img src="icon_streaming.png" alt="Preview Streaming Video" width="64" height="64" border="0" /></a></center></td>';

                } else if ($resourcetype == 'URL') {
                   
                    // build URL preview image src
                    $url_preview = 'http://open.thumbshots.org/image.aspx?url='.$previewhref.'';
                    $rowsHTML .= '<td><img src="'.$url_preview.'" alt="" width="120" height="90" /></td>';
                   
                } else {
                    $rowsHTML .= '<td><br /><center><img src="'.$icon.'" alt="" /></center></td>';
                }
            }
            // nkowald
            $rowsHTML .= "<td>$previewlink".(($isajaxcall)? ' ' : " - ".'<span class="desc" title="'.
                         $strdesc.'">'.$record->description.'</span> '.
                         '<span class="kwds" title="'.$strkeywords.
                         '">').'('.$strkeywords.": ".$record->keywords.')</span>'.
                         " &nbsp;$editlink".
                         " &nbsp;"./* $deletelink */"</td>";//TODO: IMPLEMENT DELETE **** need to be *very* careful
            $rowsHTML .= "<td>$resourcetype</td>";
            $rowsHTML .= ($isajaxcall)? '' : "<td>$date</td>";
            if(!$blockmode) {
                $rowsHTML .= "<td>$chooselink</td>";
            }
            $rowsHTML .= '</tr>';
        }//end foreach

        //we put our results in a form to maintain theme appearance consistency
        
        //DISPLAY SEARCH RESULTS
        ?>        
        <?php if(!$isajaxcall) { ?><form action="#" method="post" id="results_form" class="mform">
        <fieldset class="clearfix" >
            <legend class="ftoggler"><?php echo $strsearchhdr; ?></legend>
            <div class="fcontainer clearfix"></div><?php } ?>
            <table cellpadding="5" cellspacing="1" border="0" id="mrcutejr_results_table">
            <?php if(!$isajaxcall) { ?><thead>
                <tr>
                    <th><?php echo $stricon; ?></th>
                    <th><?php echo $strmenustring; ?></th>
                    <th><?php echo $strtype; ?></th>
                    <th><?php echo $strlastmodified; ?></th>
                    <?php if(!$blockmode) { ?>
                        <th><?php echo ucfirst($strchoose); ?></th>
                    <?php } ?>
                </tr>
            </thead><?php } ?>
            <tbody>
                <?php echo $rowsHTML; ?>
            </tbody>
            </table>
<?php if ($pagination != '') { ?>
            <table>
                <tr>
                    <td><?php echo $pagination; ?></td>
                </tr>
            </table>
<?php } ?>
        <?php if(!$isajaxcall) { ?></fieldset></form><?php } ?>
        <?php
    } else { 
        
        //NO RESULTS 
        ?>
        <?php if(!$isajaxcall) { ?><form action="#" method="post" id="results_form" class="mform">
        <fieldset class="clearfix" >
            <legend class="ftoggler"><?php echo $strsearchhdr; ?></legend>
            <?php } ?><div class="fcontainer clearfix"></div>
            <p><?php echo $strnoresults; ?></p><?php if(!$isajaxcall) { ?>
        </fieldset>
        </form><?php } ?>
   <?php }
}

//if not ajax, we need to close the html
if(!$isajaxcall) {
    //show search form
    $mform->display();
    //print empty footer - remove 'empty' to show current theme footer html
    print_footer('empty');
}

?>
