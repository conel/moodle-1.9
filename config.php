<?php  /// Moodle Configuration File 

unset($CFG);

$CFG->dbtype    = 'mysqli';
$CFG->dbhost    = 'moodle-sql';
$CFG->dbname    = 'moodle';
$CFG->dbuser    = 'moodle';
$CFG->dbpass    = 'm00dle';
$CFG->dbpersist =  false;
$CFG->prefix    = 'mdl_';

$CFG->wwwroot = 'https://vle.conel.ac.uk';
$CFG->dirroot   = 'F:\moodle';
$CFG->dataroot  = 'F:\moodledata';
$CFG->admin     = 'admin';

$CFG->directorypermissions = 00777;  // try 02777 on a server in Safe Mode
$CFG->allowthemechangeonurl = TRUE; //nkowald - for testing new themes

// nkowald - 27/07/2009 - Proxy Details
define('PROXY_SERVER', 'curric-isa');
define('PROXY_PORT', 8080);
define('PROXY_USERNAME', 'text');
define('PROXY_PASSWORD', 'P@55w0rd');

// Used for CURL authentication
define('AUTH_USER', 'aservice');
define('AUTH_PASS', 'aservice_10');

// nkowald - 2010-10-12 - define academic year start and finish (easier than retrieving from query).
define('TS_YEAR_START', 1315180800);
define('TS_YEAR_END', 1341792000);

require_once("$CFG->dirroot/lib/setup.php");
// MAKE SURE WHEN YOU EDIT THIS FILE THAT THERE ARE NO SPACES, BLANK LINES,
// RETURNS, OR ANYTHING ELSE AFTER THE TWO CHARACTERS ON THE NEXT LINE.

?>