<?PHP
function flash_upgrade_get_movie_info($moviename)
{
    global $CFG;
    require_once($CFG->dirroot.'/mod/flash/swfphp/swf.php');
    $flash = new SWF("{$CFG->dirroot}/mod/flash/movies/$moviename/$moviename.swf"); 
    if(!$flash->is_valid()){ 
        echo("Error opening $CFG->dirroot/mod/flash/movies/$moviename/$moviename.swf <br />");
        return false;
    } else
    {
        $retobject=new object();
        $dimensions=$flash->getMovieSize(); 
        $bgcolor=$flash->getBackgroundColor();
        $retobject->bgcolor=$bgcolor['hex'];
        $retobject->version=$flash->getVersion();
        $retobject->width=$dimensions['width'];
        $retobject->height=$dimensions['height'];
        $retobject->framerate= $flash->getFrameRate(); 
        $retobject->moviename= $moviename; 
        $retobject->timemodified = time();
        return $retobject;
    }
}
function flash_upgrade($oldversion) {
/// This function does anything necessary to upgrade 
/// older versions to match current functionality 

    global $CFG;

    if ($oldversion < 2005030900) {

        if (!modify_database('',"ALTER TABLE prefix_flash ADD usesplash TINYINT( 1 ) AFTER feedback;"))
        {
            return false;
        }
        if (!modify_database('',"ALTER TABLE prefix_flash ADD splash TEXT AFTER usesplash;"))
        {
            return false;
        }
        if (!modify_database('',"ALTER TABLE prefix_flash ADD splashformat TINYINT( 2 ) AFTER splash ;"))
		{
			return false;
		}
	                
        if (!modify_database('',"ALTER TABLE prefix_flash ADD usepreloader TINYINT( 1 ) AFTER feedback ,
ADD fonts TEXT AFTER usepreloader ;"))
        {
            return false;
        }    
    }
    if ($oldversion < 2005032900) {
        if (!modify_database('',"ALTER TABLE prefix_flash ADD guestfeedback VARCHAR(255) AFTER answers;"))
        {
            return false;
        } 
    }   
    if ($oldversion < 2005040500) {
        if (!modify_database('',"ALTER TABLE `prefix_flash` CHANGE `guestfeedback` `guestfeedback` TEXT DEFAULT NULL;"))
        {
            return false;
        } 
        if (!modify_database('',"ALTER TABLE `prefix_flash` CHANGE `feedback` `feedback` TEXT DEFAULT NULL;"))
        {
            return false;
        } 
        if (!execute_sql("update `{$CFG->prefix}flash_answers` SET q_no=q_no+1  WHERE 1;"))
        {
            return false;
        } 
        if (!execute_sql("update `{$CFG->prefix}flash` SET moviename='order_and_select'  WHERE moviename='order';"))
        {
            return false;
        } 
        if (!execute_sql("update `{$CFG->prefix}flash` SET usepreloader='1' WHERE moviename='order_and_select';"))
        {
            return false;
        } 
        
    }    
	
    if ($oldversion < 2005063000) {
        if (!modify_database('',"ALTER TABLE `prefix_flash` ADD `showgrades` TINYINT( 1 ) DEFAULT '1' NOT NULL AFTER `gradingmethod`;"))
        {
            return false;
        } 

        
    }
	
    if ($oldversion < 2005070500) {
        if (!modify_database('',"ALTER TABLE `prefix_flash` ADD `showheader` TINYINT( 1 ) DEFAULT '1' NOT NULL AFTER `showgrades`;"))
        {
            return false;
        } 

        
    }
    if ($oldversion < 2005080100) {
        if (!modify_database('',"CREATE TABLE `prefix_flash_movies` (`id` INT( 11 ) NOT NULL AUTO_INCREMENT , `version` INT( 11 ) NOT NULL , `bgcolor` VARCHAR( 7 ) NOT NULL , `width` INT( 11 ) NOT NULL , `height` INT( 11 ) NOT NULL , `framerate` INT( 11 ) NOT NULL , `moviename` VARCHAR( 255 ) NOT NULL , `timemodified` INT( 11 ) NOT NULL, PRIMARY KEY ( `id` ) );"))
        {
            return false;
        } 
        //$flashs=get_records(

        
    }
    if ($oldversion < 2005080101) {
        if ($flashs=get_records_sql("SELECT DISTINCT moviename, moviename FROM `{$CFG->prefix}flash`;"))
        {
            foreach ($flashs as $flashmoviename)
            {
                if (file_exists("{$CFG->dirroot}/mod/flash/movies/{$flashmoviename->moviename}/{$flashmoviename->moviename}.swf") )
                {
                    if (!$movieinfo=flash_upgrade_get_movie_info($flashmoviename->moviename))
                        
                    {
                        echo ("Skipping $flashmoviename->moviename<br />");
                    } else
                    {
                        if (!insert_record('flash_movies', $movieinfo))
                        {
                            return false;
                        } 
                    }
                } else
                {
                    echo ("Can't find $flashmoviename->moviename<br />");
                }
            }
        }
    }
    if ($oldversion < 2005080200) {
        if (!modify_database('',"ALTER TABLE `prefix_flash` ADD `size` TINYINT( 1 ) DEFAULT '0' NOT NULL AFTER `showheader`;"))
        {
            return false;
        } 
    }
    return true;
}

?>
