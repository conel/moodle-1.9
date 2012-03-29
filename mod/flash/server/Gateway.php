<?php

/*
================================================================================

PHPObject Gateway (for use with PHPObject)
v1.51 (12-January-2005)

Copyright (C) 2003-2004  Sunny Hong | http://ghostwire.com
         Modified by James Pratt     | http://e-gakusei.org

This library is free software; you can redistribute it and/or
modify it under the terms of the GNU Lesser General Public
License as published by the Free Software Foundation; either
version 2.1 of the License, or (at your option) any later version.

This library is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
Lesser General Public License for more details.

You should have received a copy of the GNU Lesser General Public
License along with this library; if not, write to the Free Software
Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

License granted only if full copyright notice retained.

If you have any questions or comments, please visit the forums at:
http://ghostwire.com

================================================================================
*/
ini_set('display_errors', 0);
class Gateway {
    var $src;        //object containing data sent from Flash
    var $myObj;    //object created from class file
    var $service;    //name of class and class file
    var $classMethods; //array of methods available to be called
    
    
    var $methods;    //array of methods called from Flash
    var $params;    //array of arrays of parameters for $methods called from Flash
    var $taskid = 0;    //integer
    var $auth;         //object initialised with credentials using _Credential.php
    var $POCfg;         // config data from phpobjct's config.php
    
    var $movie_sess_token; // session token is a key to the session for this movie
                            // this session may be started before the movie is run
                            // it may include data stored by the server that tells
                            // us what service files we can access
    
    var $obj_sess_token; // obj_sess_token is a key to the session variable array pointing
                        // to where we are storing $myObj between requests
    var $clean_up; //boolean : indicates whether to save data in session 
		//or can be set to true on the last session call
    var $logger; //object to use to log events and errors
    /**
    * Constructor stops any output buffering, restart buffering and gets data sent from Flash and put it in 
    * property src. Then it calls init method.
    *
    * @param object &$logger pass in an object with methods to log events in the gateway.
    */
    function Gateway(&$logger)  {
        $this->logger=&$logger;
        $this->logger->open_event_log();
        if ( !$fp = @include("config.php") ) {
            $this->_doError("Error - Configuration File not found");
        }
        $this->POCfg = $POCfg;
        //stop any buffering and filtering of output that might have been started by
        //include files. 
        $output="";
        while (ob_get_level()) {
            if (ob_get_length()) {
                $output .= ob_get_contents();
            }
            ob_end_clean();
        }
        if ($output!="") {//Throw an error if there was any output from include files.
            $this->_doError("Error - Error from include file : $output.");
        }
        if (headers_sent()) {
            $this->_doError("Error - Communication Error - Headers sent.");
        }
        ob_start();//restart buffering
        //access post data directly. Post data is not value pairs but is a serialized object.
        if (!empty($GLOBALS['HTTP_RAW_POST_DATA'])) {
            $this->src = unserialize($GLOBALS['HTTP_RAW_POST_DATA']);
            $this->logger->log_event("IN :\n\r".print_r($this->src, true)."\n\r");
            $this->init();
        } else  {
            $this->_doError("Error - No data passed to Gateway!");
        }
    }
    
    /**
    * Called by constructor.
    *
    */
    function init()
    {
        $CLIENT = (phpversion() <= "4.1.0") ? $HTTP_SERVER_VARS['HTTP_USER_AGENT'] : $_SERVER['HTTP_USER_AGENT'];
        if ($this->POCfg['disableStandalone'] && ($CLIENT == "Shockwave Flash")) { // ** standalone player **
            $this->_doError("Error - Standalone Player");
        } else {
            $this->_getHeader();

            if (count($this->methods)) {
                //this is a method(s) call
                $this->_unserializeObj();
                $this->_executeFunctions();
            } else  { 
                //init service call
                $this->_instantiateObj();
            }
            
            $this->logger->log_event("myObj :\n\r".print_r($this->myObj, true)."\n\r");
            if (!$this->clean_up)
            {
                $this->auth->save_info($this->obj_sess_token, 'myObjserial', serialize($this->myObj));
            } else
			{
                $this->auth->delete_movie_sess();
            }
            $tmp=$this->_clean($this->myObj, $this->src);
            //_loader will have got deleted because of the leading '_' 
            //so we will copy that as well 
            $tmp->_loader=$this->myObj->_loader;
            if (count($this->methods)){
                //if this is a method call
                unset($tmp->_loader->classMethods);//don't send list of available methods again
                unset($tmp->_loader->obj_sess_token);
            }
            unset ($this->myObj);
            $this->myObj=$tmp;
            
            $this->_output();
        }
    } 

    /*
    * extracts directives, validates key,
    * validates credentials, starts service
    * 
    */
    function _getHeader()
    {
        $this->logger->log_event("_getHeader\n");
        $v = $this->src->_data;
        if ($v[4] !== $this->POCfg['useKey']) {
            $this->_doError("Error - Please provide a valid key");
        }  else {
            if ($fp = @include($this->POCfg['classdir'][0] . "_Credentials.php")) {// ** always look for this in first classdir **

                if (!class_exists('Credentials'))
                {
                    $this->_doError("Unable to define Credentials class");
                    
                }
                $this->logger->log_event("loaded credentials\n");
                $this->auth = new Credentials($this);
                if (!$this->auth->validate($v[5]))
                {
                    $this->_doError("Error - Invalid credentials");
                } else
                {
                    $this->movie_sess_token=$this->auth->movieFlashSess;
                }
                
            } else
            {
                $this->_doError("Credentials class not found.");
            }
            $this->taskid        = $v[0];
            $this->methods    = $v[2];
            $this->params    = $v[3];
            $this->blank        = isset($v[7]) ? true    : false;
            $this->constructorParams = $v[8];
            $this->obj_sess_token= $v[9];
            $this->clean_up=$v[10];
            
            if (!count($this->methods)) {//if there are no method calls this is a service init
                if (!isset ($this->auth->mustUse)){
                    $this->service = $v[1];
                } else {
                    $this->service = $this->auth->mustUse;
                }
                if (isset ($this->auth->toPass))  {//add param to constructor params
                    if (isset($this->constructorParams)) {
                        if (is_array($this->constructorParams))  {
                            if ((count($this->constructorParams))>2) {
                                $this->_doError("Only upto 2 constructor params are acccepted when using authorisation.");
                            }
                            $this->constructorParams[count($this->constructorParams)]=$this->auth->toPass;
                        } else  {
                            $this->constructorParams        = array($this->constructorParams, $this->auth->toPass);
                        }
                    }  else {
                        $this->constructorParams        = array($this->auth->toPass);
                    }
                }
            } elseif (isset($this->obj_sess_token)) {
                $return=$this->auth->get_saved_info($this->obj_sess_token, 'service');
                if ($return['error']){
                    $this->_doError("Error - Sessions not set up properly.");
                } else {
                    $this->service=$return['value'];
                }
            } else {
                $this->_doError("Error - Sessions not set up properly.");
            }
            
            //include the class file
            if (class_exists($this->service))  {
                $this->_doError("Error - Service '$v[1]' not available - predefined classes cannot be accessed directly");
            } else {
                if (isset($this->auth) && !empty($this->auth->service))   {
                    if ( !$fp = @include($this->auth->service))  {
                        $this->_doError("Error initiating service - Could not open '{$this->auth->service}'");
                    }                    
                } elseif ( basename($this->service.".php") != $this->service.".php" )   {
                    $this->_doError("Error - Service '$v[1]' not available - illegal characters used");
                }   else  {
                    $k = 0;
                    $j = count($this->POCfg['classdir']);
                    for($i=0;$i<$j;$i++) {
                        if ( $fp = @include(($this->POCfg['classdir'][$i]).($this->service).".php") )  {
                            $k = 1;
                            break;
                        }
                    }
                    if (!$k)  {
                        $this->_doError("Error - Service '$v[1]' not available");
                    }
                }
            }
            
        }
    }
    function _unserializeObj()    {
        $this->logger->log_event("Unserializing\n\r".($this->auth->get_saved_info($this->obj_sess_token, 'myObjserial'))."\n\r");
        $return=$this->auth->get_saved_info($this->obj_sess_token, 'myObjserial');
        if ($return['error']) {
            $this->_doError('You cannot access methods before calling the constructor function.');
        }
        $this->myObj=unserialize($return['value']);
        //get properties sent from Flash 
        $this->_unpack($this->src,"myObj");
        //copy unserialised info to src so we can later check for changes
        $this->_unpack($this->myObj,"src");
        $this->_getCallableMethods();
    }
    function _executeFunctions()    {        
        //execute any functions
        $this->myObj->_loader->serverResults= array();
        $x = count($this->methods);
        for ($i=0; $i < $x; $i++)  {
            $result=$this->_execute($this->methods[$i], $this->params[$i]);
            $this->myObj->_loader->serverResults[$i]= array ('method'=>$this->methods[$i],'result'=>$result);
        }
    }

    function _instantiateObj()    {
        //constructor call
        $this->logger->log_event("Instantiating\n\r");
        // ** instantiate the object **
        $svcName=$this->POCfg['prefix'].$this->service;
        if (isset($this->constructorParams))  {
            if (is_array($this->constructorParams)){
                $paramCount = count($this->constructorParams);
                switch ($paramCount){
                    // ** we support only up to 3 parameters, but you can easily modify the code below **
                    case 3:
                        $this->myObj    = new $svcName($this->constructorParams[0],$this->constructorParams[1],$this->constructorParams[2]);
                        break;
                    case 2:
                        $this->myObj    = new $svcName($this->constructorParams[0],$this->constructorParams[1]);
                        break;
                    case 1:
                        $this->myObj    = new $svcName($this->constructorParams[0]);
                        break;
                    default:
                        $this->_doError("Invalid number of constructor parameters");
                }
            }else{
                $this->myObj    = new $svcName($this->constructorParams);
            }
        }else{
            $this->myObj        = new $svcName;
        }
        $this->_getCallableMethods();
                    // ** initializating - we pick up a list of class methods, to be stored on the client-side **
        $this->myObj->_loader->classMethods = array_change_key_case(array_flip($this->classMethods), CASE_LOWER);
        if (!$this->clean_up){
            if (!$this->obj_sess_token=$this->auth->new_obj_sess_token()){
                $this->_doError('Couldn\'t open new object session');
            }
            $this->auth->save_info($this->obj_sess_token, 'service', $this->service);
            $this->myObj->_loader->obj_sess_token=$this->obj_sess_token;
        };
    }
            
    function _getCallableMethods(){
        $m = get_class_methods(get_class($this->myObj));
        $m = array_filter($m, "filterPublic");    // ** only allow public methods to be called from Flash **
        $this->classMethods = array_values($m);
    }

    // *************************************
    // unpack object properties and populate
    // *************************************
    function _unpack($src,$dest){
        if ( (is_object($src)) || (is_array($src)) )
        {
            foreach($src as $k=>$v)
            {
                if ($k != '_data' && $k{0} != '_')
                {
                    $this->$dest->$k = $v;
                }
            }
        }
    }

    // *************************************
    // executes requested method
    // *************************************
    // ** thanks to Guido Govers guido_govers@hotmail.com **
    function _execute($m,$p) {
        $m = (phpversion() < "5.0.0") ? strtolower($m) : $m;
        if(!$m){
            $this->_doError("ERROR - Error while calling methods");
        }
        if(in_array($m,$this->classMethods)) {
            ksort($p);
            // ** execute method **
            return call_user_func_array(array(&$this->myObj, $m), $p);
            // ** thanks to Jamie P <jamie@e-gakusei.org> for the above elegant code **
        }else {
            $this->_doError("ERROR - Method '$m' does not exist");
        }
    }
    function _clean($to_send, $old){
        if ($this->blank){
            return new stdClass();
        } else{
            $d = new stdClass();
            //$to_send_props = get_object_vars($to_send); 
            foreach ($to_send as $prop => $val){
                if (($prop{0}!="_") && (!isset($old->$prop)||($old->$prop != $val))){
                //properties with prefix '_' are not passed to Flash
                    $d->$prop = $val;
                } 
            }     
            return $d;
        }
    }    
    // *************************************
    // returns error message to flash
    // *************************************
    function _doError($m) {
        error_log($m,0);
        $this->logger->log_error($m."\n\rSent to server :\n\r".print_r($this->src,true)."From (IP address) :\n\r".$_SERVER['REMOTE_ADDR']);
        $this->myObj->_loader->serverError = "$m\n";
        $this->_output();
    }

    // *************************************
    // returns object to flash 
    // *************************************
    function _output(){
        $output = ob_get_contents();
        if (ob_get_length()) {
            $this->myObj->_loader->output = $output;
        }
        ob_end_clean();
        $this->logger->log_event("OUT :\n\r".print_r($this->myObj,true)."\n\r");
        
        $t = serialize($this->myObj);
        $t = $this->taskid . $t;

        if (!headers_sent()) {
        //Tell browser never to cache output (just to be sure)
            // Date in the past 
            header("Expires: Mon, 26 Jul 1997 05:00:00 GMT"); 
            
            header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT"); 
            header("Cache-Control: no-store, no-cache, must-revalidate"); 
            header("Cache-Control: post-check=0, pre-check=0", false); 
            header("Pragma: no-cache"); 
            header("content-type: text/plain;charset=UTF-8 \r\n");
            
            // so flash can check load progress :
            header("Content-Length: " . strlen($t));
        } else {
            error_log('Headers already sent, can\'t send headers.',0);
        }
        exit($t);
    }


} 
if (!class_exists('object'))
{
    class object {};
};
session_start();

// **************************
// instantiate the gateway
// **************************


// **************************
// array filtering function
// **************************
function filterPublic($v)
{
    return (substr($v,0,1) != "_");
}
    
?>