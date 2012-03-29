<?php

/**
 * Call to include js for swfobject.
 * Keeps a check on whether js for swfobject has been included.
 *
 */
function flash_so_js_include($echo=true){
    global $CFG;
    static $issojsincluded;
    if (empty ($issojsincluded)){
        $issojsincluded=true;
        $return= '<script type="text/javascript" src="'.$CFG->wwwroot.'/mod/flash/SWFObject/swfobject.js"></script>';
    }else{
        $return='';
    }
    if ($echo){
        echo $return;
    }else {
        return $return;
    }
}
?>