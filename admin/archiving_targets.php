<?php

    set_time_limit(0);
    $starttime = microtime();
	
/// The following is a hack necessary to allow this script to work well 
/// from the command line.

    define('FULLME', 'cron');

/// Do not set moodle cookie because we do not need it here, it is better to emulate session
    $nomoodlecookie = true;

/// The current directory in PHP version 4.3.0 and above isn't necessarily the
/// directory of the script when run from the command line. The require_once()
/// would fail, so we'll have to chdir()

    if (!isset($_SERVER['REMOTE_ADDR']) && isset($_SERVER['argv'][0])) {
        chdir(dirname($_SERVER['argv'][0]));
    }

    require_once(dirname(__FILE__) . '/../config.php');

/// Extra debugging (set in config.php)
	//$CFG->showcronsql = 1;
	//$CFG->showcrondebugging = 1;
	    
    if (!empty($CFG->showcronsql)) {
        $db->debug = true;
    }
    if (!empty($CFG->showcrondebugging)) {
        $CFG->debug = DEBUG_DEVELOPER;
        $CFG->debugdisplay = true;
    }
        
/// extra safety
    @session_write_close();

/// check if execution allowed
    if (isset($_SERVER['REMOTE_ADDR'])) { // if the script is accessed via the web.
        if (!empty($CFG->cronclionly)) { 
            // This script can only be run via the cli.
            print_error('cronerrorclionly', 'admin');
            exit;
        }
        // This script is being called via the web, so check the password if there is one.
        if (!empty($CFG->cronremotepassword)) {
            $pass = optional_param('password', '', PARAM_RAW);
            if($pass != $CFG->cronremotepassword) {
                // wrong password.
                print_error('cronerrorpassword', 'admin'); 
                exit;
            }
        }
    }

/// emulate normal session
    $SESSION = new object();

/// send mime type and encoding
    if (check_browser_version('MSIE')) {
        //ugly IE hack to work around downloading instead of viewing
        @header('Content-Type: text/html; charset=utf-8');
        echo "<xmp>"; //<pre> is not good enough for us here
    } else {
        //send proper plaintext header
        @header('Content-Type: text/plain; charset=utf-8');
    }

/// no more headers and buffers
    while(@ob_end_flush());

/// increase memory limit (PHP 5.2 does different calculation, we need more memory now)
    @raise_memory_limit('128M');

/// Start output log

    $timenow  = time();

    mtrace("Server Time: ".date('r',$timenow)."\n\n");
	
	mtrace("Archiving personal targets with expired deadline.");
	
	execute_sql("UPDATE mdl_block_lpr SET arch_achieved=achieved, achieved=4 WHERE deadline<unix_timestamp() AND achieved<4");

	mtrace("Finished archiving");

    //Unset session variables and destroy it
    @session_unset();
    @session_destroy();

    mtrace("Cron script completed correctly");

    $difftime = microtime_diff($starttime, microtime());
    mtrace("Execution took ".$difftime." seconds"); 

/// finish the IE hack
    if (check_browser_version('MSIE')) {
        echo "</xmp>";
    }

?>
