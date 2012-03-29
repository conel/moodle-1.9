/*
 **************************************************************************
 *                                                                        *
 *                                                                        *
 *                             MrCUTE Jr.                                 *
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

//credits to MrCUTE2 developers for original code (code is slightly tweaked)

/**
 * Inserts values from our pop-up window into the main browser window's form, then closes the pop-up window.
 * @param {String} location - a reference to our shared resource id (mrcutejr#x)
 * @param {String} name - the value for the normal resource name field
 * @param {String} description - the value for the normal resource desc field
 */
function set_value(location, name, description, popup) {
    opener.document.getElementById('id_reference_value').value = location;
    opener.document.getElementById('id_name').value = rawurldecode(name);
    //check for HTMLArea editor - will probably change in Moodle 2.0
    var fram = opener.document.getElementsByTagName('iframe')[0];
    var oDoc = false;
    if(fram) {
        oDoc = fram.contentWindow || fram.contentDocument;
        if(oDoc) {
            oDoc.document.body.innerHTML = rawurldecode(description);
        }
    }
    //if we have wysiwyg editing disabled, so just a normal form field
    if(!oDoc) {
        opener.document.getElementById('id_summary').value
            = rawurldecode(description);
    }
    if(popup!=null && popup!="") {
        var opts = popup.split(",");
        for(i=0; i < opts.length; i++) {
            var keyValue = opts[i].split('=');
            if(keyValue[0].substring(3,0)=="id_") {
                var widthHeightObj = opener.document.getElementById(keyValue[0]);
                widthHeightObj.value = keyValue[1];
                widthHeightObj.disabled = false;//just enable everything...
            } else {
                var allNameObjects = opener.document.getElementsByName(keyValue[0]);
                for(i2=0; i2 < allNameObjects.length; i2++) {
                    if(allNameObjects[i2].tagName=="INPUT") {
                        try {//still may not be exactly what we want, but likely it is...
                            allNameObjects[i2].checked = (keyValue[1]==1) ? true : false;
                            allNameObjects[i2].disabled = false;
                        } catch(err) {}
                    }
                }
            }
        }
    }
    window.close();
}

/**
 * Decodes the provided string from an url-encoded state back to a normal string.
 * @param {String} str - the string to be decoded.
 */
function rawurldecode(str) {
    var histogram = {};
    var ret = str.toString();
    var replacer = function(search, replace, str) {
        var tmp_arr = [];
        tmp_arr = str.split(search);
        return tmp_arr.join(replace);
    };
    histogram["'"]   = '%27';
    histogram['(']   = '%28';
    histogram[')']   = '%29';
    histogram['*']   = '%2A';
    histogram['~']   = '%7E';
    histogram['!']   = '%21';
    for (replace in histogram) {
        search = histogram[replace];
        ret = replacer(search, replace, ret);
    }
    ret = ret.replace(
        /%([a-fA-F][0-9a-fA-F])/g, 
        function (all, hex) {
            return String.fromCharCode('0x'+hex);
        }
    );
    ret = decodeURIComponent(ret);
    return ret;
}