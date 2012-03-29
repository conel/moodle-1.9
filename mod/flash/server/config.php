<?php
/*
PHPObject Gateway Configuration File
*/
$POCfg=array();
/*
CLASS DIRECTORIES
- paths to where PHP classes are stored
- paths should end with backslashes, eg. "/www/classes/"
*/
$POCfg['classdir'][0]    = "classes/";
//$POCfg['classdir'][1]    = "classes/";
//$POCfg['classdir'][2]    = "/www/classes/";

//prefix for class names, class names are for example $POCfg['prefix'].'Foo' which would be in Foo.php 
$POCfg['prefix'] = ""; 

/*
USEKEY
- if defined, all requests going through the gateway must provide this key
*/
$POCfg['useKey']    = "secret";

/*
DISABLE STANDALONE
- if true, standalone player cannot access this gateway
*/
$POCfg['disableStandalone']    = true;

//Debug
//Set this to a path relative to the Gateway and the prefix for log file names
// to tell the Gateway to write a log file for every request received.
//eg. $POCfg['debug']    = "logs/in-out";
//Set to "" for no debug log files. Make sure you do this and clean up old log files when
//not debugging, don't leave log files lying around in web accessable directories.
$POCfg['debug']    = "logs/log.txt";


?>