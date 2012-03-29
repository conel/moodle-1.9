<?php
//include Moodle config stuff for access to Moodle db, session vars etc.
require_once("../../../config.php");
//instantiate Gateway
require_once("Gateway.php");
require_once('logger.php');
$logger=new MoodleLogger();

$Gateway = new Gateway($logger);
?>