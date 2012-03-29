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
 * This script hooks into a moodle install to check authorization for return a mrcutejr shared resource and returns
 * the resource if authorized.  This script relies on code in resource.class.php->display() for proper request format.
 */

//initialize
require_once('config.php');         //mrcutejr config includes moodle config.php
require_once('mrcutejr_auth.php');  //also includes Crypt_Xtea.php
$fileparam = required_param('file', PARAM_PATH);
$resources_dir = $CFG->block_mrcutejr_repository;//absolute path to mrcutejr files

//basic security checks
if($_SERVER['REQUEST_METHOD']=="POST") {//since we only us GET we kick out potential automated POST requests
    @die();// no feedback for illegitimate requests
}
require_login();

//parse the original file parameter
$filenameparts = split("__", $fileparam);

//another security check
$filenameparts = split("__", $fileparam);
if(
    count($filenameparts)!=4       ||       //should be exactly 4 parts: [0]=ecnryppted [1]=cid [2]=rid [3]=ext
    !is_numeric($filenameparts[1]) ||       //must be numeric id (for course)
    !is_numeric($filenameparts[2])          //must be numeric id (for resource)
) {
    @die();// no feedback for illegitimate requests
}

//define some variables for readability
$encryptedresource  = $filenameparts[0];
$course_id          = $filenameparts[1];
$resource_id        = $filenameparts[2];

//decrypt & clean the decrypted request
$jrauth = new mrcutejr_auth($course_id, $resource_id);
$filename = clean_param($jrauth->decrypt($encryptedresource), PARAM_PATH);//clean again incase encrypted malicious code

//finally we can put together our request & return the resource
$path = $resources_dir.$filename;//absolute path to file, including the file name
mrcutejr_send_file($path);
?>