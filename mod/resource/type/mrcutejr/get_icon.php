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


/***
 * Determines icon that should be used for the passed in resource; in the case of the mrcutejr type resource this can
 * be a different icon for a different instance.
 * @param object $resource - the resource in question used to determine icon; assumes $resource->reference is valid
 * @return Returns a string for the type of resource icon to be used.
 */
function mod_resource_mrcutejr_get_icon(&$resource) {
    global $CFG;
    require_once ($CFG->libdir.'/filelib.php');
    $icon = mimeinfo("icon", $resource->reference);
    if ($icon != 'unknown.gif') {
       return "f/$icon";
    } else {
       return "f/web.gif";
    }
}

?>
