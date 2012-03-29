var nameobject = document.getElementById('id_name');
var resultsobject = document.getElementById('mrcutejr-ajax-search-results');
var lastsearchstring = "";
var waittimer = null;
var transaction = null;
if(searchurl && nameobject && resultsobject) {//searchurl comes from php
    YAHOO.util.Event.addListener("id_name", "keypress", fnNameObjectKeyPress, nameobject);
    // nkowald - 2010-10-27 - uncommented this as it was annoying when it stayed on.
    YAHOO.util.Event.addListener('id_name', "blur", hideMrCuteWithTimeout);
    var kl = new YAHOO.util.KeyListener(
        document,
        { keys:27 },//ESC key
        { fn:fnHideMrcutejrSearchResults }
    );
    kl.enable();
}
function fnNameObjectKeyPress(e, obj) {
    if(obj.value!=lastsearchstring) {//a new search
        if(waittimer!=null) {
            clearTimeout(waittimer);
        } else if(YAHOO.util.Connect.isCallInProgress(transaction)) {
            YAHOO.util.Connect.abort(transaction);
        }
        if(obj.value.length>2) {
            lastsearchstring = obj.value;
            waittimer = setTimeout(doMrcutejrAjaxCall, 250);//delay requests slightly to allow for typing speed
        }
    }
}
function doMrcutejrAjaxCall() {
    transaction = YAHOO.util.Connect.asyncRequest('GET', searchurl+lastsearchstring, fnMrcutejrAjaxCallback, null);
    waittimer = null;
}

var fnMrcutejrAjaxCallback = {
    success: function(o) {
        resultsobject.innerHTML = o.responseText;
        var region = YAHOO.util.Dom.getRegion('id_name');
        YAHOO.util.Dom.setStyle('mrcutejr-ajax-search-results','position','absolute');
        YAHOO.util.Dom.setX('mrcutejr-ajax-search-results', region.left);
        YAHOO.util.Dom.setY('mrcutejr-ajax-search-results', region.bottom);
        YAHOO.util.Dom.setStyle('mrcutejr-ajax-search-results','width',(region.right-region.left)+'px');
        //YAHOO.util.Dom.setStyle('mrcutejr-ajax-search-results','height','277px');
        // nkowald - 2010-10-27 - added max height instead, not sure if this property works with yahoo framework
        YAHOO.util.Dom.setStyle('mrcutejr-ajax-search-results','max-height','277px');
        YAHOO.util.Dom.setStyle('mrcutejr-ajax-search-results','overflow','auto');
        YAHOO.util.Dom.setStyle('mrcutejr-ajax-search-results','visibility','visible');//show
    },
    failure: function(o){}
}
function hideMrCuteWithTimeout() {
    setTimeout(fnHideMrcutejrSearchResults, 250); //delay hiding so you can select link
}

function fnHideMrcutejrSearchResults() {
    YAHOO.util.Dom.setStyle('mrcutejr-ajax-search-results','visibility','hidden');
    resultsobject.innerHTML = '';//free up memory
}



/**
 * Inserts values from our pop-up window into the main browser window's form, then closes the pop-up window.
 * @param {String} location - a reference to our shared resource id (mrcutejr#x)
 * @param {String} name - the value for the normal resource name field
 * @param {String} description - the value for the normal resource desc field
 */
function set_value(location, name, description, popup) {
    document.getElementById('id_reference_value').value = location;
    document.getElementById('id_name').value = rawurldecode(name);
    //check for HTMLArea editor - will probably change in Moodle 2.0
    var fram = document.getElementsByTagName('iframe')[0];
    var oDoc = false;
    if(fram) {
        oDoc = fram.contentWindow || fram.contentDocument;
        if(oDoc) {
            oDoc.document.body.innerHTML = rawurldecode(description);
        }
    }
    //if we have wysiwyg editing disabled, so just a normal form field
    if(!oDoc) {
        document.getElementById('id_summary').value
            = rawurldecode(description);
    }
    if(popup!=null && popup!="") {
        var opts = popup.split(",");
        for(i=0; i < opts.length; i++) {
            var keyValue = opts[i].split('=');
            if(keyValue[0].substring(3,0)=="id_") {
                var widthHeightObj = document.getElementById(keyValue[0]);
                widthHeightObj.value = keyValue[1];
                widthHeightObj.disabled = false;//just enable everything...
            } else {
                var allNameObjects = document.getElementsByName(keyValue[0]);
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
    fnHideMrcutejrSearchResults();
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
