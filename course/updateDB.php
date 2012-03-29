<?php 

    include_once('../config.php');

    if ($CFG->forcelogin) {
        require_login();
    }

    $action 				= $_POST['action']; 
    $updateRecordsArray 	= $_POST['recordsArray'];
    $return_msg = '';

    if ($action == "updateRecordsListings"){
        
        $i = 1;
        if (is_array($updateRecordsArray) && count($updateRecordsArray) > 0) {
            foreach ($updateRecordsArray as $course_id) {
                if(!is_numeric($course_id)) {
                    $return_msg = 'invalid access attempt';
                    break;
                }
                if(!set_field('course', 'sortorder', $i, 'id', $course_id)) {
                   $return_msg = 'Error: course order NOT saved'; 
                   break;
                } else {
                    $i++;
                }
            }
            
            if ($return_msg == '') {
                $return_msg = 'Course order saved';
            }
        } else {
            $return_msg = 'invalid access attempt';
        }

    } else {
        $return_msg = 'invalid access attempt';
    }

    echo $return_msg;

?>
