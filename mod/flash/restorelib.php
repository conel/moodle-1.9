<?PHP 
//
//    Part of Flash Activity Module :
//    A Moodle activity module that takes care of a lot of functionality for Flash
//    movie developeres who want their movies to work with Moodle
//    to use Moodles grades table, configuration, backup and restore features etc.
//    Copyright (C) 2004, 2005  James Pratt
//    Contact  : me@jamiep.org http://jamiep.org
//
//    Developed for release under GPL,
//    funded by AGAUR, Departament d'Universitats, Recerca i Societat de la
//    Informacieneralitat de Catalunya.
//
//    This program is free software; you can redistribute it and/or modify
//    it under the terms of the GNU General Public License as published by
//    the Free Software Foundation; either version 2 of the License, or
//    (at your option) any later version.
//
//    This program is distributed in the hope that it will be useful,
//    but WITHOUT ANY WARRANTY; without even the implied warranty of
//    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//    GNU General Public License for more details.
//
//    You should have received a copy of the GNU General Public License
//    along with this program; see flash/license.txt;
//      if not, write to the Free Software
//    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA 

    //This php script contains all the stuff to restore
    //flash mods

    //This is the "graphical" structure of the flash mod:
    //
    //                           flash                                      
    //                        (CL,pk->id)
    //                            |
    //                            |
    //                       flash_accesses
    //                     (UL,pk->id, fk->flashid)
    //                            |
    //                            |
    //                            |
    //                       flash_answers
    //                    (UL,pk->id,fk->accessid) 
    //
    // Meaning: pk->primary key field of the table
    //          fk->foreign key to link with parent
    //          nt->nested field (recursive data)
    //          CL->course level info
    //          UL->user level info
    //          files->table may have files)
    //
    //-----------------------------------------------------------
    require_once($CFG->dirroot.'/mod/flash/swfphp/swf.php');

    function flash_todb($data)
    //a function which won't utf decocde data
    //used instead of backup_todb
    {
        return restore_decode_absolute_links(addslashes($data));
    };

    //This function executes all the restore procedure about this mod
    function flash_restore_mods($mod,$restore) {
        
        global $CFG;

        $status = true;

        //Get record from backup_ids
        $data = backup_getid($restore->backup_unique_code,$mod->modtype,$mod->id);

        if ($data) {
            //Now get completed xmlized object
            $info = $data->info;
            //traverse_xmlize($info);                                                                     //Debug
            //print_object ($GLOBALS['traverse_array']);                                                  //Debug
            //$GLOBALS['traverse_array']="";                                                              //Debug

            //Now, build the flash record structure
            $flash->course = $restore->course_id;
            $flash->name = flash_todb($info['MOD']['#']['NAME']['0']['#']);
            $flash->moviename = flash_todb($info['MOD']['#']['MOVIENAME']['0']['#']);
            $flash->grade = flash_todb($info['MOD']['#']['GRADE']['0']['#']);
            $flash->gradingmethod= flash_todb($info['MOD']['#']['GRADINGMETHOD']['0']['#']);
            $flash->showgrades= flash_todb($info['MOD']['#']['SHOWGRADES']['0']['#']);
            $flash->showheader= flash_todb($info['MOD']['#']['SHOWHEADER']['0']['#']);
            $flash->to_config = flash_todb($info['MOD']['#']['TO_CONFIG']['0']['#']);
            $flash->config = flash_todb($info['MOD']['#']['CONFIG']['0']['#']);
            $flash->q_no = flash_todb($info['MOD']['#']['Q_NO']['0']['#']);
            $flash->answers = flash_todb($info['MOD']['#']['ANSWERS']['0']['#']);
            $flash->feedback = flash_todb($info['MOD']['#']['FEEDBACK']['0']['#']);
            $flash->guestfeedback = flash_todb($info['MOD']['#']['GUESTFEEDBACK']['0']['#']);
            $flash->usesplash = flash_todb($info['MOD']['#']['USESPLASH']['0']['#']);
            $flash->splash = flash_todb($info['MOD']['#']['SPLASH']['0']['#']);
            $flash->splashformat = flash_todb($info['MOD']['#']['SPLASHFORMAT']['0']['#']);
            $flash->usepreloader = flash_todb($info['MOD']['#']['USEPRELOADER']['0']['#']);
            $flash->fonts = flash_todb($info['MOD']['#']['FONTS']['0']['#']);
            $flash->timemodified = flash_todb($info['MOD']['#']['TIMEMODIFIED']['0']['#']);

            //The structure is equal to the db, so insert the flash
            $newid = insert_record ("flash",$flash);

            //Do some output     
            echo "<ul><li>".get_string("modulename","flash")." \"".$flash->name."\"<br>";
            flash_movie_info_restore($flash->moviename);
            backup_flush(300);

            if ($newid) {
                //We have the newid, update backup_ids
                backup_putid($restore->backup_unique_code,$mod->modtype,
                             $mod->id, $newid);
                //Now check if want to restore user data and do it.
                if ($restore->mods['flash']->userinfo) {
                    //Restore flash_accesses
                    $status = flash_accesses_restore_mods ($newid,$info,$restore);
                 }
            } else {
                $status = false;
            }

            //Finalize ul        
            echo "</ul>";

        } else {
            $status = false;
        }

        return $status;
    }
    function flash_movie_info_restore($moviename)
    {
        //we don't get this info from the backup data but read info from the movies themselves
        global $CFG;
        $flash = new SWF("$CFG->dirroot/mod/flash/movies/$moviename/$moviename.swf"); 
        if(!$flash->is_valid()){ 
            echo "<strong>Can't open file $CFG->dirroot/mod/flash/movies/$moviename/$moviename.swf</strong> You need to install this for activities based on it to work and you must then go to the update page for the activity to update db records.<br />";
        } else
        {
            $todb=new object();
            $dimensions=$flash->getMovieSize(); 
            $bgcolor=$flash->getBackgroundColor();
            $todb->bgcolor=$bgcolor['hex'];
            $todb->version=$flash->getVersion();
            $todb->width=$dimensions['width'];
            $todb->height=$dimensions['height'];
            $todb->framerate= $flash->getFrameRate(); 
            $todb->moviename= $moviename; 
            $todb->timemodified = time();
            if ($oldrec=get_record('flash_movies', 'moviename', $moviename))
            {
                $todb->id=$oldrec->id;
                if (!update_record('flash_movies',$todb))
                {
                    error('Error updating movie record.');
                }
            }else
            {
                insert_record('flash_movies',$todb);
            }
        }
    }
    //This function restores the flash_accesses
    function flash_accesses_restore_mods($flash_id,$info,$restore) {

        global $CFG;

        $status = true;

        //Get the accesses array
        $accesses = $info['MOD']['#']['ACCESSES']['0']['#']['ACCESS'];

        //Iterate over accesses
        for($i = 0; $i < sizeof($accesses); $i++) {
            $sub_info = $accesses[$i];
            //traverse_xmlize($sub_info);                                                                 //Debug
            //print_object ($GLOBALS['traverse_array']);                                                  //Debug
            //$GLOBALS['traverse_array']="";                                                              //Debug

            //We'll need this later!!
            $oldid = flash_todb($sub_info['#']['ID']['0']['#']);
            $olduserid = flash_todb($sub_info['#']['USERID']['0']['#']);

            //Now, build the flash_accesses record structure
            $access->flashid = $flash_id;
            $access->userid = flash_todb($sub_info['#']['USERID']['0']['#']);
            $access->timemodified = flash_todb($sub_info['#']['TIMEMODIFIED']['0']['#']);

            //We have to recode the userid field
            $user = backup_getid($restore->backup_unique_code,"user",$access->userid);
            if ($user) {
                $access->userid = $user->new_id;
            }

            //The structure is equal to the db, so insert the flash_accesses
            $newid = insert_record ("flash_accesses",$access);

            //Do some output
            if (($i+1) % 50 == 0) {
                echo ".";
                if (($i+1) % 1000 == 0) {
                    echo "<br>";
                }
                backup_flush(300);
            }

            if ($newid) {
                //We have the newid, update backup_ids
                backup_putid($restore->backup_unique_code,"flash_accesses",$oldid,
                             $newid);
                //Restore flash_answers
                $status = flash_answers_restore_mods ($newid,$sub_info,$restore);
            } else {
                $status = false;
            }
        }

        return $status;
    }
    //This function restores the flash_accesses
    function flash_answers_restore_mods($access_id,$info,$restore) {

        global $CFG;

        $status = true;

        //Get the answers array
        $answers = $info['#']['ANSWERS']['0']['#']['ANSWER'];

        //Iterate over answers
        for($i = 0; $i < sizeof($answers); $i++) {
            $sub_info = $answers[$i];
            //traverse_xmlize($sub_info);                                                                 //Debug
            //print_object ($GLOBALS['traverse_array']);                                                  //Debug
            //$GLOBALS['traverse_array']="";                                                              //Debug

            //We'll need this later!!
            $oldid = flash_todb($sub_info['#']['ID']['0']['#']);

            //Now, build the flash_answers record structure
            $answer->accessid = $access_id;
            $answer->answer = flash_todb($sub_info['#']['ANSWER']['0']['#']);
            $answer->q_no = flash_todb($sub_info['#']['Q_NO']['0']['#']);
            $answer->grade = flash_todb($sub_info['#']['GRADE']['0']['#']);


            //The structure is equal to the db, so insert the flash_answers
            $newid = insert_record ("flash_answers",$answer);

            //Do some output
            if (($i+1) % 50 == 0) {
                echo ".";
                if (($i+1) % 1000 == 0) {
                    echo "<br>";
                }
                backup_flush(300);
            }

            if ($newid) {
                //We have the newid, update backup_ids
                backup_putid($restore->backup_unique_code,"flash_answers",$oldid,
                             $newid);
            } else 
            {
                $status = false;
            }
        }

        return $status;
    }

    //This function returns a log record with all the necessay transformations
    //done. It's used by restore_log_module() to restore modules log.
    function flash_restore_logs($restore,$log) {
                    
        $status = false;
                    
        //Depending of the action, we recode different things
        switch ($log->action) {
        case "add":
            if ($log->cmid) {
                //Get the new_id of the module (to recode the info field)
                $mod = backup_getid($restore->backup_unique_code,$log->module,$log->info);
                if ($mod) {
                    $log->url = "view.php?id=".$log->cmid;
                    $log->info = $mod->new_id;
                    $status = true;
                }
            }
            break;
        case "update":
            if ($log->cmid) {
                //Get the new_id of the module (to recode the info field)
                $mod = backup_getid($restore->backup_unique_code,$log->module,$log->info);
                if ($mod) {
                    $log->url = "view.php?id=".$log->cmid;
                    $log->info = $mod->new_id;
                    $status = true;
                }
            }
            break;
        case "view":
            if ($log->cmid) {
                //Get the new_id of the module (to recode the info field)
                $mod = backup_getid($restore->backup_unique_code,$log->module,$log->info);
                if ($mod) {
                    $log->url = "view.php?id=".$log->cmid;
                    $log->info = $mod->new_id;
                    $status = true;
                }
            }
            break;
        case "view all":
            $log->url = "index.php?id=".$log->course;
            $status = true;
            break;
        case "test attempt":
            if ($log->cmid) {
                //Get the new_id of the module (to recode the info field)
                $access = backup_getid($restore->backup_unique_code,'flash_accesses' ,$log->info);
                if ($access) {
                    $log->url = "view.php?id=$log->cmid&access=$access->new_id";
                    $log->info = $access->new_id;
                    $status = true;
                }
            }
            break;
        case "answer":
            if ($log->cmid) {
                //Get the new_id of the module (to recode the info field)
                $access = backup_getid($restore->backup_unique_code,'flash_accesses' ,$log->info);
                if ($access) {
                    $log->url = "view.php?id=$log->cmid&access=$access->new_id";
                    $log->info = $access->new_id;
                    $status = true;
                }
            }
            break;
        default:
            echo "action (".$log->module."-".$log->action.") unknow. Not restored<br>";                 //Debug
            break;
        }

        if ($status) {
            $status = $log;
        }
        return $status;
    }
?>
