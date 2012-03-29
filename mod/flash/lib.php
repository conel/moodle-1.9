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

function flash_get_movie_info($moviename)
{
    global $CFG;
    require_once($CFG->dirroot.'/mod/flash/swfphp/swf.php');
    $flash = new SWF("$CFG->dirroot/mod/flash/movies/$moviename/$moviename.swf"); 
    if(!$flash->is_valid()){ 
        error("Error opening $moviename.swf");
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

function flash_add_instance($flash) {
    //don't have to do any serializing here because 
    //this function is only used for the first page of the
    //config from
    if (!empty($flash->moviename))
    {
        $flashmovie=flash_get_movie_info($flash->moviename);
        if ($flashmovieold=get_record('flash_movies', 'moviename', $flash->moviename))
        {
            $flashmovie->id=$flashmovieold->id;
            if (!update_record('flash_movies', $flashmovie))
            {
                error('Error updating flash movie record');
            }
        } else
        {
            if (!insert_record('flash_movies', $flashmovie))
            {
                error('Error creating new flash movie record');
            }
        }
    }
    $flash->timemodified = time();
    if ($flash->fontssubmitted=="1")
    {
        if (is_array($flash->fonts))
        {
            $flash->fonts=join(',', $flash->fonts);
        }else
        {
            $flash->fonts='';
        
        }
    }
    return insert_record('flash', $flash);
}

function flash_array_stripslashes(&$item) {
   if (is_array($item))
   {
       reset ($item);
       while (list($key) = each($item)) {
           flash_array_stripslashes($item[$key]);
       }
    } else
    {
        $item=stripslashes($item);
    }      
 }
function flash_cleanup_lb(&$item) {
// convert \r\n line breaks to \n 
   if (is_array($item))
   {
       reset ($item);
       while (list($key) = each($item)) {
           flash_cleanup_lb($item[$key]);
       }
    } else
    {
        $item=preg_replace("/\r\n|\r/", "\n", $item);
    }      
 }


function flash_update_instance($flash) {
    if (!empty($flash->moviename))
    {
        $flashmovie=flash_get_movie_info($flash->moviename);
        if ($flashmovieold=get_record('flash_movies', 'moviename', $flash->moviename))
        {
            $flashmovie->id=$flashmovieold->id;
            if (!update_record('flash_movies', $flashmovie))
            {
                error('Error updating flash movie record');
            }
        } else
        {
            if (!insert_record('flash_movies', $flashmovie))
            {
                error('Error creating new flash movie record');
            }
        }
    }
    if ($flash->fontssubmitted=="1")
    {
        if (is_array($flash->fonts))
        {
            $flash->fonts=join(',', $flash->fonts);
        }else
        {
            $flash->fonts='';
        
        }
    }
    $flash->timemodified = time();
    $flash->id = $flash->instance;

    $old_record=get_record('flash', 'id', $flash->id);
    if ($flash->to_config=='0')
    {
        unset($_SESSION['flashFormSess'][$flash->id]);
    } elseif (!empty($flash->sess))
    {
        foreach ($flash->sess as $key=>$value)
        {
            $_SESSION['flashFormSess'][$flash->id][$key]=$value;
        }
    }

    if (!empty($flash->config))
    {
        flash_array_stripslashes($flash->config);
        flash_cleanup_lb($flash->config);
        if (empty($old_record->config)||(!is_array($flash->config)))
        {
            $flash->config=addslashes(serialize($flash->config));
        } else
        {
            if (!$config=(unserialize($old_record->config)))
            {
                //throw an error if there is something unserializable in config field
                return false;
            }
            //add new elements to array
            foreach ($flash->config as $key=>$value)
            {
                $config[$key]=$value;
            }
            
            $flash->config=addslashes(serialize($config));
        }
    }

    if (!empty($flash->answers))
    {
        flash_array_stripslashes($flash->answers);
        flash_cleanup_lb($flash->answers);
        if (empty($old_record->answers)||!is_array($flash->answers))
        {
            $flash->answers=addslashes(serialize($flash->answers));
        } else
        {
            if (!$answers=(unserialize($old_record->answers)))
            {
                //throw an error if there is something unserializable in answers field
                return false;
            }
            //add new elements to array
            foreach ($flash->answers as $key=>$value)
            {
                $answers[$key]=$value;
            }
            
            $flash->answers=addslashes(serialize($answers));
        }
    };
    
    return update_record('flash', $flash);
}


function flash_delete_instance($id) {
/// Given an ID of an instance of this module, 
/// this function will permanently delete the instance 
/// and any data that depends on it.  

    if (! $flash = get_record('flash', 'id', "$id")) {
        return false;
    }

    $result = true;

    # Delete any dependent records here #

    if (! delete_records('flash', 'id', $flash->id)) {
        $result = false;
    }
    if (!$flash_accesses =get_records ('flash_accesses', 'flashid', $flash->id)) {
        return $result;
    } else {
        foreach ( $flash_accesses as $flash_access)
        {
            if (get_records ('flash_answers', 'accessid', $flash_access->id)) 
            {
                if (!delete_records('flash_answers', 'accessid', $flash_access->id))
                {
                    $result = false;
                }
            }
        }
        if (!delete_records('flash_accesses', 'flashid', $flash->id)) {
            $result = false;
        }
    }
    return $result;
}

function flash_user_outline($course, $user, $mod, $flash) {
/// Return a small object with summary information about what a 
/// user has done with a given particular instance of this module
/// Used for user activity reports.
/// $return->time = the time they did it
/// $return->info = a short text description

    global $CFG;
    $no_of_times_taken=count_records('flash_accesses', 'userid', $user->id, 'flashid', $flash->id);
    if ($no_of_times_taken > 0)
    {
        $sql='SELECT MAX(accesses.timemodified) as lastime FROM '.$CFG->prefix.'flash_accesses AS accesses '.
            ' WHERE accesses.flashid = '.$flash->id.' AND accesses.userid = '. $user->id;
            
        $lasttime=get_field_sql($sql);
        $return->time = $lasttime;
        $return->info = get_string('taken_test_n_times', 'flash', $no_of_times_taken);

    } else
    {
        $return->time = time();
        $return->info = get_string('not_taken', 'flash');
    }

    return $return;
}

function flash_user_complete($course, $user, $mod, $flash) {
/// Print a detailed representation of what a  user has done with 
/// a given particular instance of this module, for user activity reports.

    global $CFG;
    global $showall;
    $a->n=count_records('flash_accesses', 'userid', $user->id, 'flashid', $flash->id);
    if ($a->n > 0)
    {
        $sql='SELECT MAX(accesses.timemodified) as lasttime, MIN(accesses.timemodified) as firsttime FROM '.$CFG->prefix.'flash_accesses AS accesses '.
            ' WHERE accesses.flashid = '.$flash->id.' AND accesses.userid = '. $user->id;
        
        $times=get_record_sql($sql);    
        $a->lasttime=userdate($times->lasttime);
        $a->firsttime=userdate($times->firsttime);
        
        print_string('taken_test_n_times_most_recent_first', 'flash', $a);
        return flash_print_table_of_accesses($flash, $user, $showall );

    } else
    {
        print_string('not_taken', 'flash');
        return;
    }

}

function flash_print_recent_activity($course, $isteacher, $timestart) {
/// Given a course and a time, this module should find recent activity 
/// that has occurred in flash activities and print it out. 
/// Return true if there was output, or false is there was none.

    global $CFG;
    $sql='SELECT flash.id as id, COUNT(*) as n, flash.name as name, MAX(accesses.timemodified) as lasttime FROM '.$CFG->prefix.'flash_accesses AS accesses ,'.
    $CFG->prefix.'flash AS flash '.
    'WHERE accesses.flashid = flash.id  AND flash.course = '. $course->id.' '.
    'AND accesses.timemodified > '. $timestart.' GROUP BY flash.id';
    //echo 'sql :'.$sql;
    if ($records=get_records_sql($sql))
    {
        print '<a href="'.$CFG->wwwroot.'/mod/flash/index.php?id='.$course->id.'"><b><font size="2">'.get_string('modulenameplural','flash').' :</font></b></a><br />';
        print '<font size="1">';
        foreach ($records as $a)
        {
            print "<p>";
            $a->lasttime=userdate($a->lasttime);
               print '<a href="'.$CFG->wwwroot.'/mod/flash/view.php?a='.$a->id.'">'.$a->name.'</a> : <br />';
            print_string('taken_test_n_times_most_recent', 'flash', $a);
            print "</p>\n";
        } 
        print '</font>';
        return true;
    } else
    {
        return false;
    }

}

function flash_cron () {
/// Function to be run periodically according to the moodle cron
/// This function searches for things that need to be done, such 
/// as sending out mail, toggling flags etc ... 

    global $CFG;

    return true;
}

function flash_grades($flashid, $userid=0) {
    global $CFG;
    $precision=2; //no of decimal points for grades
    $intflashid=intval($flashid);
    $records=get_records_sql('SELECT a.id, a.accessid, acc.userid, a.q_no, a.grade '.
                            "FROM {$CFG->prefix}flash_answers as a, {$CFG->prefix}flash_accesses as acc ".
                            'WHERE a.accessid=acc.id AND acc.flashid='.$intflashid.' '.
                            (($userid==0)?'':" AND acc.userid=$userid ").
                            'ORDER BY acc.timemodified');
    if (!$records)
    {
        return NULL;
    } else
    {
        $flashrecord= get_record('flash', 'id', "$intflashid");
        $noofqs=$flashrecord->q_no;
        
        
		$return->maxgrade= $flashrecord->grade;
        $gm=$flashrecord->gradingmethod;
        switch ($gm)
        {
            case 'best':
                foreach ($records as $current)
                {
                    if ($return->grades[$current->userid][$current->q_no]<$current->grade)
                    {
                        $return->grades[$current->userid][$current->q_no]=$current->grade;
                    }
                }
                foreach ($return->grades as $thisuserid => $grade)
                {
                    $return->grades[$thisuserid]= 
                        round((array_sum($return->grades[$thisuserid])/($noofqs*100))*$return->maxgrade,$precision);
                }
                break;
            case 'ave' :
                foreach ($records as $current)
                {
                    $return->grades[$current->userid][$current->q_no]+=$current->grade;
                    $attempt_count[$current->userid][$current->q_no]++;
                }
                foreach ($return->grades as $thisuserid => $questions)
                {
                    foreach ($questions as $q_no => $question)
                    {
                        $return->grades[$thisuserid][$q_no]=
                            $return->grades[$thisuserid][$q_no] 
                                / $attempt_count[$thisuserid][$q_no] ;
                    };
                    $return->grades[$thisuserid]= 
    	                round((array_sum($return->grades[$thisuserid])/($noofqs*100))*$return->maxgrade,$precision);
                }
                break;
            case 'best_q':
            case 'last' :
            case 'first' :
                foreach ($records as $current)
                {
                    $grades[$current->userid][$current->accessid][$current->q_no][]=$current->grade;
                    
                }
                foreach ($grades as $thisuserid => $grade)
                {
                    foreach ($grade as $accessid => $questions)
                    {
                        foreach ($questions as $q_no => $grades_array)
                        {
                            $grades[$thisuserid][$accessid][$q_no]=array_sum($grades_array)/count($grades_array);
                        }    
                    };
                }                
                foreach ($grades as $thisuserid => $grade)
                {
                    foreach ($grade as $accessid => $questions)
                    {
                        $return->grades[$thisuserid][$accessid]=
                            (array_sum($questions)/($noofqs*100))*$return->maxgrade;
                    };
                    if ($gm=='best_q')
                    {
                        rsort($return->grades[$thisuserid], SORT_NUMERIC);
                    } elseif ($gm=='last')
                    {
                        krsort($return->grades[$thisuserid], SORT_NUMERIC);
                    } elseif ($gm=='first')
                    {
                        ksort($return->grades[$thisuserid], SORT_NUMERIC);
                    }
                    reset($return->grades[$thisuserid]);
                    $return->grades[$thisuserid]=round(current($return->grades[$thisuserid]),$precision);
                }
                break;        
        }
        return $return;
    } 
}

function flash_get_participants($flashid) {
//Must return an array of user records (all data) who are participants
//for a given instance of flash. Must include every user involved
//in the instance, independient of his role (student, teacher, admin...)
//See other modules as example.
    global $CFG;

    //Get students
    $students = get_records_sql("SELECT DISTINCT u.*
                                 FROM {$CFG->prefix}user u,
                                      {$CFG->prefix}flash_accesses a
                                 WHERE a.flashid = '$flashid' and
                                       u.id = a.userid");
    //Return students array (it contains an array of unique users)
    return ($students);
}

function flash_scale_used ($flashid,$scaleid) {
//This function returns if a scale is being used by one flash
//it it has support for grading and scales. Commented code should be
//modified if necessary. See forum, glossary or journal modules
//as reference.
   
    $return = false;
    return $return;
}

//////////////////////////////////////////////////////////////////////////////////////
/// Other flash functions
/// 

function flash_print_table_of_accesses($flash, $user='', $showall=0, $access=0)
{
    global $CFG, $course;
    $teacheraccess=isteacher($course->id);
	
	//print heading :
    if ($teacheraccess && $user=='')
    {
        echo '<h2>'.get_string('allresultsheading', 'flash', $flash->name).'</h2>';
    } elseif ($teacheraccess)
    {
        $a->fullname = (fullname($user));
        $a->flashname= ($flash->name);
        echo '<h2>'.get_string('resultsheading', 'flash', $a ).'</h2>';
    } else 
    {
        $a->fullname = (fullname($user));
        $a->flashname= ($flash->name);
        echo '<h2>'.get_string('yourresultsheading', 'flash', $a).'</h2>';
    }
	
	if ($flash ->showgrades)
	{
		//print grading method and get grades :
		if ($teacheraccess && $user=='')
		{
			echo '<p><strong><div align=\'center\'>';
			$grades=flash_grades($flash->id);
			$gradingmethodstring=get_string('gradingmethod_'.$flash->gradingmethod,'flash');
			echo get_string('gradingmethodused','flash', $gradingmethodstring);
			helpbutton("gradingmethodtype", get_string("gradingmethod", "flash"), "flash");
			echo '</div></strong></p>';
		} elseif ($teacheraccess)
		{
			$grades=flash_grades($flash->id);
			echo '<p><strong><div align=\'center\'>';
			$gradingmethodstring=get_string('gradingmethod_'.$flash->gradingmethod,'flash');
			echo get_string('gradingmethodused','flash', $gradingmethodstring);
			helpbutton("gradingmethodtype", get_string("gradingmethod", "flash"), "flash");
			echo '</div></strong></p>';
		} else 
		{
			if ($grades=flash_grades($flash->id, $user->id))
			{
				$a->grade=$grades->grades[$user->id];
				$a->maxgrade=$flash->grade;
				echo '<p><strong><div align=\'center\'>'.get_string('yourgrade','flash', $a).'<br />';
				$gradingmethodstring=get_string('gradingmethod_'.$flash->gradingmethod,'flash');
				echo get_string('gradingmethodused','flash', $gradingmethodstring);
				helpbutton("gradingmethodtype", get_string("gradingmethod", "flash"), "flash");
				echo '</div></strong></p>';
			};
		}
	}
	if ($teacheraccess && $user=='') // we'll print all users accesses
    {
        $flash_answers_sql='SELECT  answers.id, accesses.id, accesses.timemodified, answers.q_no, answers.answer, '.
            'user.firstname AS firstname, user.lastname AS lastname, user.id AS userid, '.
            'answers.grade, f.feedback, f.guestfeedback ';
        $flash_answers_sql_from =
            'FROM '.$CFG->prefix.'flash_accesses AS accesses, '.
            $CFG->prefix.'flash_answers AS answers, '.
            $CFG->prefix.'user AS user, '.
            $CFG->prefix.'flash as f '.
            'WHERE answers.accessid=accesses.id '.
            'AND accesses.userid=user.id '.
            'AND accesses.flashid='.$flash->id.' '.
            'AND f.id=accesses.flashid '.
            (($access!=0)?"AND accesses.id=$access ":'').
            'ORDER BY accesses.timemodified DESC, answers.id ASC';
    } else // just select accesses for $user
    {
        $flash_answers_sql='SELECT  answers.id, accesses.id, accesses.timemodified, answers.q_no, answers.answer, '.
            'user.firstname AS firstname, user.lastname AS lastname, user.id AS userid, '.
            'answers.grade, f.feedback, f.guestfeedback ';
        $flash_answers_sql_from =
            'FROM '.$CFG->prefix.'flash_accesses AS accesses, '.
            $CFG->prefix.'flash_answers AS answers, '.
            $CFG->prefix.'user AS user, '.
            $CFG->prefix.'flash as f '.
            'WHERE answers.accessid=accesses.id '.
            'AND accesses.userid='.    $user->id.
            ' AND user.id=accesses.userid AND accesses.flashid='.$flash->id.' '.
            'AND f.id=accesses.flashid '.
            'ORDER BY accesses.timemodified DESC, answers.id ASC';
    }  
    $record_count=count_records_sql('SELECT COUNT(*) '.$flash_answers_sql_from);
    if ($showall!=1)
    {
        $flash_answers_from.=' LIMIT 25';
    } 
    if (! $flash_answers=get_records_sql ($flash_answers_sql . $flash_answers_sql_from))
    {
        echo('<p><strong><div align=\'center\'>'.get_string('nogrades','flash').'</div></strong></p>');
    } else 
    {

		if ($access!=0)
        {
            echo '<p><strong><div align=\'center\'>'.get_string('showing_one_attempt', 'flash').'</div></strong></p>';
        } elseif ($showall!=1 && $record_count>25)
        {
		    $goto=preg_replace('/&showall=0/','', qualified_me());
            echo '<p><strong><div align=\'center\'>'.get_string('showing_limited', 'flash', $record_count);
            echo ' '.'<a href="'.$goto.'&showall=1">'.get_string('show_unlimited', 'flash', $record_count).'</a></div></strong></p>';
        }  elseif ($record_count>25)
        {
            $goto=preg_replace('/&showall=1/','', qualified_me());
            echo '<p><strong><div align=\'center\'><a href="'.$goto.'&showall=0">'.get_string('show_limited', 'flash').'</a>';
            echo ' '.get_string('showing_unlimited', 'flash', $record_count).'</div></strong></p>';
        } 
		//main table
        $users_table->width='100%';
        $users_table->head = array (
                        0 => get_string('col_headings_user_fullname', 'flash'),
                        1 => get_string('col_headings_answers', 'flash'));
		if ($flash ->showgrades)
		{
			$users_table->head[2] = get_string('col_headings_grade', 'flash');
		}
        $users_table->size = array(0=>'10%', 1 => '80%', 2 => '10%'); 
        $users_table->align =array(0 => 'center', 1 => 'center', 2 => 'center'); 
		//table for each access
        $feedback_table->width='90%';
		$feedback_table->head = array (
							1 => get_string('timemodified', 'flash'),
							2 => get_string('col_headings_answers', 'flash'));
		$feedback_table->size = array(1 => '10%', 2 => '90%'); 
        $feedback_table->align = array(1 => 'center', 2 => 'center'); 
        //question table is the nested tables
        $question_table->width='90%';
        $question_table->align= array(0 => 'center', 1 => 'center', 2 => 'center'); 
		if (($flash->q_no)>1)
		{
			$question_table->size[]='10%';
			$question_table->head[]=get_string('col_headings_ans_q_no', 'flash');
		}
		if (($flash->q_no)>1)
		{
			$question_table->head[]=get_string('col_headings_answer', 'flash');
		}

		$question_table->size[]='80%';
		if ($flash ->showgrades)
		{
			$question_table->size[]='10%';
			if (($flash->q_no)>1)
			{
				$question_table->head[] = get_string('col_headings_grade', 'flash');
			}
		}
        
        foreach ($flash_answers as $flash_answer_key => $flash_answer)
        {
            if (!isset($answers_for_table[$flash_answer->userid]))
            {
                $answers_for_table[$flash_answer->userid]['name']=get_string('fullnamedisplay', 'moodle', $flash_answer);
            }
            if (!isset($answers_for_table[$flash_answer->userid]['accesses'][$flash_answer->id]))
            {
                $answers_for_table[$flash_answer->userid]['accesses'][$flash_answer->id]['timemodified']=userdate($flash_answer->timemodified);
            };
			if ($flash->q_no>1) //if there is only one question don't need question nos
            {
				$answers_for_table[$flash_answer->userid]['accesses'][$flash_answer->id]['answers'][$flash_answer_key][]=$flash_answer->q_no;
			}
            $unserializedanswer=unserialize($flash_answer->answer);
            if (isguest())
            {
                $feedback=$flash_answer->guestfeedback;
            } else
            {
                $feedback=$flash_answer->feedback;
            }
            foreach ($unserializedanswer as $key => $property)
            {
                if (!is_array($property) && !is_object($property)) {
                    $feedback=str_replace(('%'.$key.'%'), ('<strong>'.$property.'</strong>'), $feedback);
                    $feedback=format_text($feedback, FORMAT_HTML);
                }  else
                {
                    $strofarr="<strong><ul><li>".
                                        join("</li>\n<li>",$property).
                                        "</li>\n</ul></strong>\n";
                    $feedback=str_replace(('%'.$key.'%'), 
                                                                ($strofarr), 
                                                                $feedback);
                    $feedback=format_text($feedback, FORMAT_HTML);
                    
                }
            }
            $answers_for_table[$flash_answer->userid]['accesses'][$flash_answer->id]['answers'][$flash_answer_key][]=$feedback;
			if ($flash ->showgrades)
			{
				$answers_for_table[$flash_answer->userid]['accesses'][$flash_answer->id]['answers'][$flash_answer_key][]=$flash_answer->grade."%";
			}
            
        } 
        foreach ($answers_for_table as $userid => $users_answers)
        {
            foreach ($answers_for_table[$userid]['accesses'] as $accessid => $answer_for_table)
            {
                $question_table->data=array_values($answers_for_table[$userid]['accesses'][$accessid]['answers']);
                $answers_for_table[$userid]['accesses'][$accessid]['question_table']=make_table($question_table);
                unset ($answers_for_table[$userid]['accesses'][$accessid]['answers']);
            }
            $feedback_table->data=$answers_for_table[$userid]['accesses'];
            unset($answers_for_table[$userid]['accesses']);
    
            $answers_for_table[$userid]['feedback_table']=make_table($feedback_table);
			if ($flash ->showgrades)
			{
				$answers_for_table[$userid]['grade']=$grades->grades[$userid]." / ".$flash->grade;
			}
        }
        $users_table->data=$answers_for_table;
        print_table($users_table);
		if ($access!=0)
        {
            echo '<p><strong><div align=\'center\'>'.get_string('showing_one_attempt', 'flash').'</div></strong></p>';
        } elseif ($showall!=1 && $record_count>25)
        {
            echo '<p><strong><div align=\'center\'>'.get_string('showing_limited', 'flash', $record_count);
            echo ' '.'<a href="'.$goto.'&showall=1">'.get_string('show_unlimited', 'flash', $record_count).'</a></div></strong></p>';
        }  elseif ($record_count>25)
        {
            echo '<p><strong><div align=\'center\'><a href="'.$goto.'&showall=0">'.get_string('show_limited', 'flash').'</a>';
            echo ' '.get_string('showing_unlimited', 'flash', $record_count).'</div></strong></p>';
        } 
   }
}

function flash_export($flash, $download_type)
{
    global $CFG, $USER, $course;
    include ($CFG->libdir.'/adodb/toexport.inc.php');
    if (!isteacher($course->id))
    {
        error('Only teachers are allowed to export results of a flash activity.');
    }    
    $flash_answers_sql='SELECT answers.id, flash.name AS flash_name, '.
            'user.firstname AS user_firstname, user.lastname AS user_lastname, '.
            'answers.q_no AS ans_q_no, '.
            'answers.answer AS useranswer, '.
            'accesses.timemodified '.
            "FROM {$CFG->prefix}flash_accesses AS accesses,  ".
            "{$CFG->prefix}flash_answers AS answers,  ".
            "{$CFG->prefix}flash AS flash,  ".
            "{$CFG->prefix}user AS user ".
            'WHERE answers.accessid=accesses.id  '.
            'AND accesses.userid=user.id  '.
            'AND flash.id =accesses.flashid '.
            "AND accesses.flashid=$flash->id ".
            'ORDER BY accesses.timemodified DESC';

    if (! $flash_answers=get_records_sql ($flash_answers_sql))
    {
        redirect("$CFG->wwwroot/mod/flash/index.php?id=$flash->course", get_string('nogrades','flash'), "5");
    } else
    {
        foreach ( $flash_answers as $result_id => $result )
        {
            $flash_answers_filtered[$result_id][get_string('col_headings_timemodified_date', 'flash')]
                    =userdate($result->timemodified);
            
            foreach ($result as $result_key => $result_field)
            {
                if (! is_int($result_key)  && $result_key!='useranswer')
                {
                    $flash_answers_filtered[$result_id][get_string('col_headings_'.$result_key, 'flash')]=$result_field;
                }
            }
            $unserialized_answer=unserialize($result->useranswer);
            foreach ($unserialized_answer as $key => $property)
            {
                $headingname=get_string('col_headings_'.$key, 'flash');
                $flash_answers_filtered[$result_id][$headingname] = (is_array($property))?join($property, ', '):$property;
            }
        }
        $result_ids=array_keys($flash_answers_filtered);
        $field_names=array_keys($flash_answers_filtered[$result_ids[0]]);


/// OK, we have all the data, now present it to the user
        if ($download_type == 'xls') {
            require_once($CFG->libdir.'/excel/Worksheet.php');
            require_once($CFG->libdir.'/excel/Workbook.php');

// HTTP headers
            header("Content-type: application/vnd.ms-excel;charset=UTF-8 \r\n");
            $downloadfilename = clean_filename("$course->shortname accesses");
            header("Content-Disposition: attachment; filename=\"$downloadfilename.xls\"");
            header('Expires: 0');
            header('Cache-Control: must-revalidate,post-check=0,pre-check=0');
            header('Pragma: public');

/// Creating a workbook
            $workbook = new Workbook('-');
            $myxls =& $workbook->add_worksheet('Accesses');
    
/// Print names of all the fields

            $x_cell=0;
            foreach ($field_names as $fieldname)
            {    
                $myxls->write_string(0,$x_cell,$fieldname);
                $x_cell++;
            }

        
/// Print all the lines of data.

            $y_cell= 0;
            foreach ($flash_answers_filtered as $resultid => $accesses) {
                $y_cell++;
                $x_cell=0;
                foreach ($accesses as $field_name => $value)
                {
                    $myxls->write_string($y_cell, $x_cell, $value);
                    $x_cell++;
                }
            }
            
            $workbook->close();
        
            exit;
    
        } elseif ($download_type == 'csv') 
        {
    
    /// Print header to force download
    
            header("Content-Type: application/download;charset=UTF-8 \r\n"); 
            $downloadfilename = clean_filename("$course->shortname Accesses");
            header("Content-Disposition: attachment; filename=\"$downloadfilename.csv\"");
            header('Expires: 0');
            header('Cache-Control: must-revalidate,post-check=0,pre-check=0');
            header('Pragma: public');
    
    /// Print names of all the fields
            $nocomma=true;// don't start a new line with a comma
            foreach ($field_names as $fieldname)
            {    
                if (!$nocomma) echo ',';
                echo($fieldname);
                $nocomma=false;
            }
            echo "\r\n";
/// Print all the lines of data.
            foreach ($flash_answers_filtered as $resultid => $accesses) 
            {
                $nocomma=true;
                foreach ($accesses as $field_name => $value)
                {
                    if (!$nocomma) echo ',';
                    echo($value);
                    $nocomma=false;
                }
                echo "\r\n";
            }
            exit;
        
        
        } elseif ($download_type == 'txt') 
        {
    
    /// Print header to force download
    
            header("Content-Type: application/download;charset=UTF-8 \r\n"); 
            $downloadfilename = clean_filename("$course->shortname Accesses");
            header("Content-Disposition: attachment; filename=\"$downloadfilename.txt\"");
            header('Expires: 0');
            header('Cache-Control: must-revalidate,post-check=0,pre-check=0');
            header('Pragma: public');
    
    /// Print names of all the fields
            $notab=true; // don't start a new line with a tab
            foreach ($field_names as $fieldname)
            {    
                if (!$notab) echo "\t";
                echo($fieldname);
                $notab=false;
            }
            echo "\r\n";
/// Print all the lines of data.
            foreach ($flash_answers_filtered as $resultid => $accesses) 
            {
                $notab=true;
                foreach ($accesses as $field_name => $value)
                {
                    if (!$notab) echo "\t";
                    echo($value);
                    $notab=false;
                }
                echo "\r\n";
            }
            exit;
        
        
        }   
    }
}
function flash_get_flashMovieSess($flashid, $courseid, $cmid, $mustuse='', $service='')
//sets up a flash session and returns a token pointing to it
{
    global $USER;
    $flash_access=new object;
    $flash_access->userid=$USER->id;
    $flash_access->timemodified = time();
    $flash_access->flashid=$flashid;
    if (!$accessid=insert_record('flash_accesses', $flash_access))
    {
        return false;
    }
    add_to_log($courseid, "flash", "test attempt", "view.php?id=$cmid&access=$accessid", "$accessid", $cmid);
    do
    //repeat until we have a new key, just to be absolutely! sure we get a new key 
    {
        $sess_token = md5(uniqid(rand(), true)); 
    } while (is_array($_SESSION['flashSess']) && array_key_exists($sess_token ,$_SESSION['flashSess']));
    
    
    $_SESSION['flashSess'][$sess_token]['mustuse']=$mustuse;
    $_SESSION['flashSess'][$sess_token]['service']=$service;
    $_SESSION['flashSess'][$sess_token]['flashid']=$flashid;
    $_SESSION['flashSess'][$sess_token]['cmid']=$cmid;
    $_SESSION['flashSess'][$sess_token]['courseid']=$courseid;
    $_SESSION['flashSess'][$sess_token]['lastaccess']=time();
    $_SESSION['flashSess'][$sess_token]['accessid']=$accessid;
    
    return $sess_token;
}
/*function flash_print_textarea($usehtmleditor, $rows, $cols, $width, $height, $name, $value="", $courseid=0) {
/// Prints a basic textarea field
/// $width and height are legacy fields and no longer used

    global $CFG, $course;

    if (empty($courseid)) {
        if (!empty($course->id)) {  // search for it in global context
            $courseid = $course->id;
        }
    }

    if ($usehtmleditor) {
        if (!empty($courseid) and isteacher($courseid)) {
            echo "<script type=\"text/javascript\" src=\"$CFG->wwwroot/mod/flash/editor/flashhtmlarea.php?id=$courseid\"></script>\n";
        } else {
            echo "<script type=\"text/javascript\" src=\"$CFG->wwwroot/mod/flash/editor/flashhtmlarea.php\"></script>\n";
        }
        echo "<script type=\"text/javascript\" src=\"$CFG->wwwroot/lib/editor/dialog.js\"></script>\n";
        echo "<script type=\"text/javascript\" src=\"$CFG->wwwroot/lib/editor/lang/en.php\"></script>\n";
        echo "<script type=\"text/javascript\" src=\"$CFG->wwwroot/lib/editor/popupwin.js\"></script>\n";

        if ($rows < 10) {
            $rows = 10;
        }
        if ($cols < 65) {
            $cols = 65;
        }
    }

    echo "<textarea id=\"$name\" name=\"$name\" rows=\"$rows\" cols=\"$cols\" wrap=\"virtual\">";
    p($value);
    echo "</textarea>\n";
}*/



?>