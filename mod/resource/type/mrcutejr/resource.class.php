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


//include the Moodle core "file" resource type so we can extend it
require_once ($CFG->dirroot.'/mod/resource/type/file/resource.class.php');

/***
 * This is an extension to enhance functionality of the "resource_file" Moodle resource type plugin (that's the one you
 * see when you click "Add a resource...->Link to file or website"; this plugin allows users to create a site-wide
 * shared resource (URL or File).  The default Moodle "resource_file" plugin must be available in your installation for
 * this plugin to work, (which it will be unless you have specifically removed it during/after installing your Moodle).
 * 
 * This plugin was developed & tested on Moodle v1.9.5 by Leo Thiessen at
 * http://www.bydistance.com/
 * 
 */
class resource_mrcutejr extends resource_file {
    
    /***
     * This is called when the resource link is clicked by a user; it displays the resource.
     */
    function display() {
        global $CFG, $USER;
        //verify if this is our custom "repository" shared URL or File
        $rsrcref = $this->resource->reference;
        $isMrCuteJr = (!empty($rsrcref) && substr($rsrcref,0,8)=="MrCuteJr");
        if($isMrCuteJr) {
            //get our repository resource reference (dubbed "realreference")
            $s = split("_",$rsrcref);
            $rsrcid = str_replace("MrCuteJr","",$s[0]);
            if(!$rsrcid) {
                print_error(
                    "mrcutejr_nosuchid",
                    'resource_mrcutejr',
                    'javascript:history.go(-1)'
                );
            }
            $rsrc = get_record("resource_mrcutejr", "id", $rsrcid);
            if(!$rsrc) {
                print_error(
                    "mrcutejr_errorretrievingrecord",
                    'resource_mrcutejr',
                    'javascript:history.go(-1)'
                );
            }
            if($rsrc->isfile) {
                //we secure access by using our own functionality to replace moodle/file.php functionality
                require_once ($CFG->dirroot.'/mod/resource/type/mrcutejr/mrcutejr_auth.php');
                $jrauth = new mrcutejr_auth($this->course->id, $this->resource->id);
                //stick with moodle syntax of using ?file=xyz.ext
                $mrcutejrgeturl = $CFG->wwwroot.'/mod/resource/type/mrcutejr/get.php?file=';
                $encryptedreference = $jrauth->encrypt($rsrc->realreference);
                //we add an extension so moodle will know how to treat this file
                $ext = strrchr($rsrc->realreference,'.');
                if($ext==false) {
                    $ext = "__.extension";  //generic value instead of false, "__" is for parsing later
                } else {
                    $ext = "__$ext";
                }
                //we append our id's this way to avoid confusing moodle url parsing/mime-type detecting
                $extra = "__" . $this->course->id . "__" . $this->resource->id . $ext;
                $this->resource->reference = $mrcutejrgeturl . $encryptedreference . $extra;
            } else {
                $this->resource->reference = $rsrc->realreference;
            }
        }//else this can be a normal resource_file...
        parent::display();//displays either - nothing fancy required!
    }
    
    /***
     * This is called when the form is submitted.
     *
     * @param object $resource
     * @return
     */
    function add_instance(&$resource) {
        $this->define_undefined_options($resource);//prevent "undefined" warning
        $success = parent::add_instance($resource);
        $this->update_record_windowoptions($resource);//store window "popup" settings
        return $success;
    }

    /***
     * This is called when the form is submitted for a pre-existing resource.
     *
     * @param object $resource
     * @return boolean successfail
     */
    function update_instance(&$resource) {
        $this->define_undefined_options($resource);//prevent "undefined" warning
        $success = parent::update_instance($resource);
        $this->update_record_windowoptions($resource);//store window "popup" settings
        return $success;
    }

    /***
     * This function takes the "alltext" field and updates the mrcutejr resource table with the data so that it can be
     * used as defaults for future uses of this shared resources (i.e. when add resource to different course).  If an
     * error occurs this method throws no notices or errors - it's not considered a critical failure to store these
     * options & so we just return the original object.
     *
     * @param object $resource - the resource to have windowoptions added (taken from alltext field)
     * @return boolean - success/fail
     */
    function update_record_windowoptions(&$resource) {
        $rsrcref = $resource->reference;
        $isMrCuteJr = (!empty($rsrcref) && substr($rsrcref,0,8)=="MrCuteJr");//only work on MrCuteJr resources
        if($isMrCuteJr) {
            $s = split("_",$rsrcref);//get our "realreference"
            $rsrcid = str_replace("MrCuteJr","",$s[0]);
            if($rsrcid) {
                $newopts = new stdclass();
                $newopts->id = $rsrcid;
                $newopts->windowoptions = 
                            "forcedownload=".(!empty($resource->forcedownload)? $resource->forcedownload: 0);
                if(!empty($resource->popup)) {
                    $strpopup = str_replace(',width=',',id_width=',$resource->popup);
                    $strpopup = str_replace(',height=',',id_height=',$strpopup);
                    $newopts->windowoptions .= ',id_windowpopup=1,'.$strpopup;
                } else {
                    $newopts->windowoptions .= ',id_windowpopup=0';
                }
                //TODO: implement save & restore of framepage options
                //$framepage = $resource->options=='objectframe' ? 2 : $resource->options=='objectframe' ? 1 : 0;
                //$newopts->windowoptions .= ',id_framepage='.$framepage;
                return update_record("resource_mrcutejr", $newopts);
            }
        }
        return false;
    }
    
    /***
     * Called when edit mode user chooses to delete the resource.
     *
     * @param object $resource
     * @return
     */
    function delete_instance($resource) {
        //Design note (decision): delete of the mrcutejr resource itself can only be done by admin from a different
        //view; this delete only deletes resource link in specific course, not the mrcutejr resource.
        //TODO: implement full delete
        return parent::delete_instance($resource);
    }
    
    /***
     * This is called to setup the form when creating a new or editing an exsiting resource.
     *
     * @param object $mform
     * @return
     */
    function setup_elements(&$mform) {
        global $CFG, $RESOURCE_WINDOW_OPTIONS;
        //if using ajax, we'll add it here
        if(!empty($CFG->block_mrcutejr_enable_ajax_search) && $CFG->block_mrcutejr_enable_ajax_search==1) {
            $myjavascript = file_get_contents(dirname(__FILE__).'/ajaxsearch.js');
            if($myjavascript) {
                $jssearchurl = 'var searchurl = "'
                                .$CFG->wwwroot.'/mod/resource/type/mrcutejr/search.php?isajaxcall=1&search=";';
                $myjavascript = '<div id="mrcutejr-ajax-search-results" style="visibility:hidden;display:block;background:#EFEFEF;border:1px solid #CCE;font-size:85%;">search results</div>'
                                .'<script type="text/javascript" charset="utf-8">/* <![CDATA[ */'
                                .$jssearchurl.$myjavascript
                                .'/* ]]> */</script>'
                                .'<style type="text/css">#mrcutejr_results_table {width:100%;margin:0;padding:0;}#mrcutejr_results_table tr.even {background:#FFF;}</style>';
                require_js(array('yui_yahoo', 'yui_dom-event', 'yui_connection'));
                $mform->addElement('html', $myjavascript);
            }
        }
        $mform->addElement('group', '', ' ');//borderless space
        //add our custom buttons
        $url = "/mod/resource/type/mrcutejr/search.php";
        //next 10 lines or so are to auto-populate our search button - if editing an existing resource, then by
        //pre-populating our search query we can immediately jump to the "current" resource so a user can easily
        //find it to edit it
        $theid = optional_param('update', 0, PARAM_INT);//resource ID
        if(!empty($theid)) {
            //I'm sure there's a better way for this, but it's here for now:
            $modinfo = unserialize($this->course->modinfo);
            $rslt = get_record("resource", "id", $modinfo[$theid]->id);
            if($rslt && strpos($rslt->reference,"MrCuteJr")===0) {
                $rsrcref = split('_', $rslt->reference);
                $url .= "?search=".$rsrcref[0];//e.g. "?search=MrCuteJr23"
            }
        }
        $n = "mrcutejr";//name of popup window
        $o = 'menubar=0,location=0,scrollbars,resizable,width=700,height=400';
        $fullscrn = 0;
        //btn#1 - "Find/Edit an Existing Shared URL/File"
        $onclick = array(
            "onclick"=>"return openpopup('$url','$n','$o',$fullscrn);"
        );
        $mform->addElement(
            'button', 
            'new', 
            get_string('searchbutton', 'resource_mrcutejr'),
            $onclick
        );
        //btn#2 - "New URL"
        $url = "/mod/resource/type/mrcutejr/new.php";
        $onclick["onclick"] = 
            "return openpopup('$url?addurl=1', '$n', '$o', $fullscrn);";
        $mform->addElement(
            'button', 
            'new', 
            get_string('newurlbutton', 'resource_mrcutejr'),
            $onclick
        );
        $onclick["onclick"] = 
            "return openpopup('$url?addfile=1', '$n', '$o', $fullscrn);";
        //btn#3 - "New File"
        $mform->addElement(
            'button', 
            'new', 
            get_string('newfilebutton', 'resource_mrcutejr'),
            $onclick
        );
        $mform->addElement('group', '', ' ');//borderless space

        //let the parent class setup the options
        parent::setup_elements($mform);

        //fix some erroneous options defaults that seem to occur when subclassing like this...
        $mform->setDefault('windowpopup', 0);//causes behavior with "forcedownload" to behave normally...
    }
    
    /***
     * Hmm... the original documentation on this method states: "override to add your own options".
     *
     * From testing it seems that this is called after setup_elements() but before output is sent to the browser.  An
     * example of print_r($default_values) output is:
     *
     * <pre>
     *     Array
     *     (
     *         [section] => 3
     *         [visible] => 1
     *         [course] => 7
     *         [module] => 14
     *         [modulename] => resource
     *         [groupmode] => 0
     *         [groupingid] => 0
     *         [groupmembersonly] => 0
     *         [instance] =>
     *         [coursemodule] =>
     *         [add] => resource
     *         [return] => 0
     *         [type] => mrcutejr
     *     )
     * </pre>
     *
     * @param object $default_values
     * @return
     */
    function setup_preprocessing(&$default_values) {
        parent::setup_preprocessing($default_values);
    }



    /* CUSTOM FUNCTIONS (functions that aren't necessarily in 'resource_file')
     *************************************************************************/

    /***
     * This simply sets undefined (false) options to be equal to zero (0) instead of not existing - this is only to
     * avoid php warnings in code not in the scope of this plugin (resource_file module code).
     *
     * @param object $resource
     */
    function define_undefined_options(&$resource) {
        //TODO: check if this is still needed after we implement saving &
        //restoring window of options
        global $RESOURCE_WINDOW_OPTIONS;
        if($resource->windowpopup) {
            foreach ($RESOURCE_WINDOW_OPTIONS as $option) {
                if(empty($resource->$option)) {
                    $resource->$option = 0;//define 'false' since doesn't exist
                }
            }
        }
    }
    
}//END CLASS resource_mrcutejr
?>