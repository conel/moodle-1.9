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
 * This script hooks into a moodle mrcutejr resource type to check authorization for returning a mrcutejr shared
 * resource only to an admin user or authorized (whitelisted) editor.
 */

//initialize
require_once('config.php');                 //mrcutejr config includes moodle config.php

//security checks
if($_SERVER['REQUEST_METHOD']=="POST") {    //since we only us GET
    @die();                                 //no feedback for illegitimate requests
}
require_authorized_mrcutejr_editor();       //key difference from get.php

//return the file
$path = $CFG->block_mrcutejr_repository . required_param('file', PARAM_PATH); //absolute path to file, including name
mrcutejr_send_file($path);
?>
