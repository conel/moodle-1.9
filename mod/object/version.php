<?php

/////////////////////////////////////////////////////////////////////////////////
///  Code fragment to define the version of object
///  This fragment is called by moodle_needs_upgrading() and /admin/index.php
/////////////////////////////////////////////////////////////////////////////////

$module->version  = 2006070500;  // The current module version (Date: YYYYMMDDXX)
$module->requires = 2004052505;  // Requires this Moodle version
$module->cron     = 0;           // Period for cron to check this module (secs)

?>
