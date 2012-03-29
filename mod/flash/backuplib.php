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


    //This php script contains all the stuff to backup
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

    function flash_backup_mods($bf,$preferences) {
        
        global $CFG;

        $status = true;

        //Iterate over flash table
        $flashs = get_records ("flash","course",$preferences->backup_course,"id");
        if ($flashs) {
            foreach ($flashs as $flash) {
                //Start mod
                fwrite ($bf,start_tag("MOD",3,true));
                //Print flash data
                fwrite ($bf,full_tag("ID",4,false,$flash->id, false));
                //tags will not be UTF encoded as they are already in UTF format 
                fwrite ($bf,full_tag("MODTYPE",4,false,"flash", false));
                fwrite ($bf,full_tag("NAME",4,false,$flash->name, false));
                fwrite ($bf,full_tag("MOVIENAME",4,false,$flash->moviename, false));
                fwrite ($bf,full_tag("GRADE",4,false,$flash->grade, false));
                fwrite ($bf,full_tag("GRADINGMETHOD",4,false,$flash->gradingmethod, false));
                fwrite ($bf,full_tag("SHOWGRADES",4,false,$flash->showgrades, false));
                fwrite ($bf,full_tag("SHOWHEADER",4,false,$flash->showheader, false));
                fwrite ($bf,full_tag("TO_CONFIG",4,false,$flash->to_config, false));
                fwrite ($bf,full_tag("CONFIG",4,false,$flash->config, false));
                fwrite ($bf,full_tag("Q_NO",4,false,$flash->q_no, false));
                fwrite ($bf,full_tag("ANSWERS",4,false,$flash->answers, false));
                fwrite ($bf,full_tag("FEEDBACK",4,false,$flash->feedback, false));
                fwrite ($bf,full_tag("GUESTFEEDBACK",4,false,$flash->guestfeedback, false));
                fwrite ($bf,full_tag("USESPLASH",4,false,$flash->usesplash, false));
                fwrite ($bf,full_tag("SPLASH",4,false,$flash->splash, false));
                fwrite ($bf,full_tag("SPLASHFORMAT",4,false,$flash->splashformat, false));
                fwrite ($bf,full_tag("USEPRELOADER",4,false,$flash->usepreloader, false));
                fwrite ($bf,full_tag("FONTS",4,false,$flash->fonts, false));
                fwrite ($bf,full_tag("TIMEMODIFIED",4,false,$flash->timemodified, false));
                //if we've selected to backup users info, then execute backup_flash_accesss
                if ($preferences->mods["flash"]->userinfo) {
                    $status = backup_flash_accesses($bf,$preferences,$flash->id);
                }
                //End mod
                $status =fwrite ($bf,end_tag("MOD",3,true));
            }
        }
        return $status;
    }

    //Backup flash_accesss contents (executed from flash_backup_mods)
    function backup_flash_accesses ($bf,$preferences,$flash) {

        global $CFG;

        $status = true;

        $flash_accesses = get_records("flash_accesses","flashid",$flash,"id");
        //If there is submissions
        if ($flash_accesses) {
            //Write start tag
            $status =fwrite ($bf,start_tag("ACCESSES",4,true));
            //Iterate over each access
            foreach ($flash_accesses as $flash_access) {
                //Start access
                $status =fwrite ($bf,start_tag("ACCESS",5,true));
                //Print submission contents
                fwrite ($bf,full_tag("ID",6,false,$flash_access->id, false));
                fwrite ($bf,full_tag("USERID",6,false,$flash_access->userid, false));
                fwrite ($bf,full_tag("TIMEMODIFIED",6,false,$flash_access->timemodified, false));
                //Now print answers to xml
                $status = backup_flash_answers ($bf, $preferences, $flash_access->id);
                //End access
                $status =fwrite ($bf,end_tag("ACCESS",5,true));
            }
            //Write end tag
            $status =fwrite ($bf,end_tag("ACCESSES",4,true));
        }
        return $status;
    }
    
    //Backup flash_answers contents (executed from backup_flash_accesss )
    function backup_flash_answers ($bf,$preferences,$access) {

        global $CFG;

        $status = true;

        $flash_answers = get_records("flash_answers","accessid",$access,"id");
        //If there is submissions
        if ($flash_answers) {
            //Write start tag
            $status =fwrite ($bf,start_tag("ANSWERS",6,true));
            //Iterate over each answer
            foreach ($flash_answers as $flash_answer) {
                //Start answer
                $status =fwrite ($bf,start_tag("ANSWER",7,true));
                //Print submission contents
                fwrite ($bf,full_tag("ID",8,false,$flash_answer->id, false));
                fwrite ($bf,full_tag("ANSWER",8,false,$flash_answer->answer, false));
                fwrite ($bf,full_tag("Q_NO",8,false,$flash_answer->q_no, false));
                fwrite ($bf,full_tag("GRADE",8,false,$flash_answer->grade, false));
                //End answer
                $status =fwrite ($bf,end_tag("ANSWER",7,true));
            }
            //Write end tag
            $status =fwrite ($bf,end_tag("ANSWERS",6,true));
        }
        return $status;
    }
   
   ////Return an array of info (name,value)
   function flash_check_backup_mods($course,$user_data=false,$backup_unique_code) {
        //First the course data
        $info[0][0] = get_string("modulenameplural","flash");
        if ($ids = flash_ids ($course)) {
            $info[0][1] = count($ids);
        } else {
            $info[0][1] = 0;
        }

        //Now, if requested, the user_data
        if ($user_data) {
            $info[1][0] = get_string("accesses","flash");
            if ($ids = flash_access_ids_by_course ($course)) 
            {
                $info[1][1] = count($ids);
                
            } else 
            {
                $info[1][1] = 0;
            }
            $info[2][0] = get_string("answers","flash");
            if ($ids = flash_answer_ids_by_course ($course)) {
                $info[2][1] = count($ids);
            } else 
            {
                $info[2][1] = 0;
            }
        }
        return $info;
    }






    // INTERNAL FUNCTIONS. BASED IN THE MOD STRUCTURE

    //Returns an array of flashs id
    function flash_ids ($course) {

        global $CFG;

        return get_records_sql ("SELECT f.id, f.course
                                 FROM {$CFG->prefix}flash f
                                 WHERE f.course = '$course'");
    }
   
    //Returns an array of flash_accesss id
    function flash_access_ids_by_course ($course) {

        global $CFG;
        $sql="SELECT acc.id , acc.flashid
                                 FROM {$CFG->prefix}flash_accesses acc,
                                      {$CFG->prefix}flash f
                                 WHERE f.course = '$course' AND
                                       acc.flashid = f.id";
        return get_records_sql ($sql);
    }
    function flash_answer_ids_by_course ($course) {

        global $CFG;
        $sql="SELECT ans.id , acc.flashid
                                 FROM {$CFG->prefix}flash_answers ans,
                                      {$CFG->prefix}flash_accesses acc,
                                      {$CFG->prefix}flash f
                                 WHERE ans.accessid = acc.id AND
                                         f.course = '$course' AND
                                       acc.flashid = f.id";
        return get_records_sql ($sql);
    }
?>
