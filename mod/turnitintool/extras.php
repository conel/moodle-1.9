<?php  // $Id: extras.php,v 1.2 2010/06/25 11:49:46 paul.dawson Exp $
/**
 * @package   turnitintool
 * @copyright 2010 iParadigms LLC
 */

    require_once('../../config.php');
    require_once('../../course/lib.php');
    require_once($CFG->libdir.'/adminlib.php');
    require_once($CFG->libdir.'/tablelib.php');
    require_once("lib.php");
    
    if (!is_callable('groups_get_activity_group')) {
        $adminroot = admin_get_root();
        admin_externalpage_setup('managemodules',$adminroot);
    } else {
        admin_externalpage_setup('managemodules');
    }

    if (isset($PAGE) AND is_callable(array($PAGE, 'requires', 'css'))) { // Are we using new moodle or old?
        $cssurl = new moodle_url($CFG->wwwroot.'/mod/turnitintool/styles.css');
        $PAGE->requires->css($cssurl);
    }

    $a  = optional_param('a', 0, PARAM_INT);  // turnitintool ID
    $s  = optional_param('s', 0, PARAM_INT);  // submission ID
    $type  = optional_param('type', 0, PARAM_INT);  // submission ID

    if (!turnitintool_check_config()) {
        print_error('configureerror','turnitintool');
        exit();
    }

/// Print the main part of the page
    $param_do=optional_param('do',null,PARAM_CLEAN);
    $param_userlinks=optional_param('userlinks',null,PARAM_CLEAN);
    
    if (!is_null($param_do) AND $param_do!="unlinkusers") {
        if ($param_do=='viewreport') {
            echo '<pre>';
            echo "====== Turnitintool Data Dump Output ======

";
        } else if ($param_do=='savereport') {
        
            $filename='tii_datadump_'.$CFG->turnitin_account_id.'_'.date('dmYhm',time()).'.txt';
            header('Content-type: text/plain');
            header('Content-Disposition: attachment; filename="'.$filename.'"');

        
            echo "====== Turnitintool Data Dump File ======

";
        }
        
        $tables = array('turnitintool_users','turnitintool_courses','turnitintool','turnitintool_parts','turnitintool_submissions');
        
        foreach ($tables as $table) {
        
            echo "== ".$table." ==
";
            
            if ($data=turnitintool_get_records($table)) {
            
                $headers=array_keys(get_object_vars(current($data)));
                $columnwidth=25;
                
                echo str_pad('',(($columnwidth+2)*count($headers)),"=");
                if ($table=='turnitintool_users') {
                    echo str_pad('',$columnwidth+2,"=");
                }
                echo "
";
                
                foreach ($headers as $header) {
                    echo ' '.str_pad($header,$columnwidth," ",1).'|';
                }
                if ($table=='turnitintool_users') {
                    echo ' '.str_pad('Name',$columnwidth," ",1).'|';
                }
                echo "
";
                
                echo str_pad('',(($columnwidth+2)*count($headers)),"=");
                if ($table=='turnitintool_users') {
                    echo str_pad('',$columnwidth+2,"=");
                }
                echo "
";
                
                foreach ($data as $datarow) {
                    $datarow=get_object_vars($datarow);
                    foreach ($datarow as $datacell) {
                        echo ' '.htmlspecialchars(str_pad(substr($datacell,0,$columnwidth),$columnwidth," ",1)).'|';
                    }
                    if ($table=='turnitintool_users') {
                        $moodleuser=turnitintool_get_record('user','id',$datarow['userid']);
                        echo ' '.str_pad(substr($moodleuser->firstname.' '.$moodleuser->lastname,0,$columnwidth),$columnwidth," ",1).'|';
                    }
                    echo "
";
                }
                echo str_pad('',(($columnwidth+2)*count($headers)),"-");
                if ($table=='turnitintool_users') {
                    echo str_pad('',$columnwidth+2,"-");
                }
                echo "
                
";
            } else {
                echo get_string('notavailableyet','turnitintool')."
";
            }
        
        }
        
        if ($param_do=='viewreport') {
            echo "</pre>";
        }
    } else if (!is_null($param_do) AND $param_do=="unlinkusers") {
    
        if (!is_null($param_userlinks) AND count($param_userlinks)>0) {
            foreach ($param_userlinks as $userlink) {
                turnitintool_delete_records('turnitintool_users','userid',$userlink);
            }
        }
        
        turnitintool_header(NULL,NULL,$_SERVER["REQUEST_URI"],get_string("modulenameplural", "turnitintool"), $SITE->fullname);

        echo '<div id="turnitintool_style">';

        turnitintool_box_start('generalbox boxwidthwide boxaligncenter', 'general');
        
        echo '<b>'.get_string('unlinkusers','turnitintool').'</b><br />';
        echo '<p>'.get_string('unlinkinfo','turnitintool').'</p>';
        
        echo '<form method="POST" action="'.$CFG->wwwroot.'/mod/turnitintool/extras.php?do=unlinkusers">
';
        
        $table->width='100%';
        $table->tablealign='center';
        $table->id='unlink';
        $table->class='uploadtable';
        
        unset($cells);
        $cells[0]->class='cell c0';
        $cells[0]->data=get_string('userstounlink', 'turnitintool');
        
        if ($userrows=turnitintool_get_records('turnitintool_users')) {
            $output='<select multiple="multiple" size="20" name="userlinks[]">';
            foreach ($userrows as $userdata) {
                if (!$user=turnitintool_get_record('user','id',$userdata->userid)) {
                    $user->id = $userdata->userid;
                    $user->lastname = get_string('nonmoodleuser','turnitintool');
                    $user->firstname = '';
                    $user->email = get_string('notavailableyet','turnitintool');
                }
                $userarray[]=$user;
            }
            $lastname=array();
            // Obtain the columns to sort on
            foreach ($userarray as $key => $row) {
                $lastname[$key]  = $row->lastname;
            }
            array_multisort($lastname, SORT_ASC, $userarray);
            foreach ($userarray as $user) {
                $output.='<option label="'.$user->lastname.', '.$user->firstname.' ('.$user->email.')" value="'.$user->id.'">'.$user->lastname.', '.$user->firstname.' ('.$user->email.')</option>
';
            }

            $output.='</select><br />
            <input style="margin-top: 7px;" value="Unlink Users" type="submit" />';
        } else {
            $output='No Users Currently Linked';
        }

        $cells[1]->class='cell c1';
        $cells[1]->data=$output;
        $table->rows[]->cells=$cells;
        
        turnitintool_print_table($table);
        
        echo '</form>
';
        
        turnitintool_box_end();
        
        echo '</div>';

        turnitintool_footer();
    } else {
        
        $post->utp='2';
        
        $loaderbar = new turnitintool_loaderbarclass(3);
        $tii = new turnitintool_commclass(turnitintool_getUID($USER),$USER->firstname,$USER->lastname,$USER->email,2,$loaderbar);
	$tii->startSession();
        
	$result=$tii->createUser($post,get_string('connecttesting','turnitintool'));
 
        $rcode=$tii->getRcode();
        $rmessage=$tii->getRmessage();
	$tiiuid=$tii->getUserID();
		
	$tii->endSession();
        
        turnitintool_header(NULL,NULL,$_SERVER["REQUEST_URI"],get_string("modulenameplural", "turnitintool"), $SITE->fullname);
        turnitintool_box_start('generalbox boxwidthwide boxaligncenter', 'general');
        if ($rcode>=API_ERROR_START OR empty($rcode)) {
            if (empty($rmessage)) {
                $rmessage=get_string('connecttestcommerror','turnitintool');
            }
            turnitintool_print_error('connecttesterror','turnitintool',$CFG->wwwroot.'/admin/module.php?module=turnitintool',$rmessage,__FILE__,__LINE__);
        } else {
            $data=new object();
            $data->userid=$USER->id;
            $data->turnitin_uid=$tiiuid;
            if ($tiiuser=turnitintool_get_record('turnitintool_users','userid',$USER->id)) {
                $data->id=$tiiuser->id;
                turnitintool_update_record('turnitintool_users',$data);
            } else {
                turnitintool_insert_record('turnitintool_users',$data);
            }
            print_string('connecttestsuccess','turnitintool');
        }
        turnitintool_box_end();
        turnitintool_footer();
        
    }

/* ?> */