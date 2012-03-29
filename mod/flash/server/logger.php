<?php
define('FLASH_LOG_ERROR', 1);
define('FLASH_LOG_EVENT', 2);
define('FLASH_LOG_ALL', 255);

class MoodleLogger{
    var $logdir;
    var $errorfile; 
    var $eventfile; 
    //file pointer
    var $_eventfp=NULL; 
    /**
    * Constructor just sets up some variables.
    * Sets up log dir if need be.
    */
    function MoodleLogger()
    {
        $this->logdir='flash_logs';
        //make a directory in the moodle data dir
        //if its not there already
        //and return it's full path
        $this->logdir=make_upload_directory($this->logdir);
        $this->errorfile=$this->logdir.'/errors.txt';
        $this->eventfile=$this->logdir.'/log.txt';
    }
    /**
    * Open event log file to append log items to it.
    */
    function open_event_log(){
        global $CFG;
        if ($CFG->flash_debug & FLASH_LOG_ALL)     
        {        
            if (!$this->_eventfp = fopen($this->eventfile, 'a'))
            {
                error_log("Error - Couldn't open log file for writing.");
            } 
            fwrite($this->_eventfp , date("\n[F j, Y, g:i:s a]\n"));
        } else
        {
            $this->_eventfp = NULL;
        };
        
    }
    /**
    * You must open the event log before calling it.
    * @param string $event describes event.
    */
    function log_event($event){
        global $CFG;
        if ($CFG->flash_debug & FLASH_LOG_EVENT)        
        {
            fwrite($this->_eventfp,"$event\n\r");
        }
    
    }
    /**
    * Close event log.
    */
    function close_event_log(){
        if ($this->_eventfp!=NULL)
        {
            fclose($this->_eventfp);
        }
    
    }
    /**
    * The error log doesn't need to be opened first. It is opened 
    * and written to all in one operation. Typically before the 
    * gateway dies.
    * @param string $error describes error.
    */
    function log_error($error){
        global $CFG;
        if ($CFG->flash_debug & FLASH_LOG_ERROR)        
        {
            fwrite($this->_eventfp,"\nError : [".$error."] Error");
        }
    }
    /**
    * Clear event log.
    */
    function clear_event_log(){
        if (file_exists($this->eventfile))
        {
            unlink($this->eventfile);
        }
    
    }
    /**
    * Display event log in a text area.
    */
    function display_event_log(){
        echo "<strong>Flash Event Log : </strong><br />";
        if (file_exists($this->eventfile))
        {
            echo "<div style='text-align:left; border-width:thin; border-color: #000000; border-style:solid; padding: 5px;'";
            $todisplay=file_get_contents($this->eventfile);
            $todisplay = break_up_long_words($todisplay, 80, "\n");
            $todisplay = htmlentities($todisplay);
            $todisplay= preg_replace("/\nError : \[(.*?)\] Error/s", "<strong>Error : </strong><font color='#FF0000'>\\1</font>", $todisplay);
            $todisplay= preg_replace("/\n\[([^\n\]]*?)\]\n/", "\n<strong>\\1</strong>\n", $todisplay);
            $todisplay = str_replace('  ', '&nbsp; ', $todisplay);
            $todisplay = nl2br($todisplay);
            echo($todisplay);
            echo '</div>';
        } else
        {
            echo "No actions logged.";
        }
    }
}
?>