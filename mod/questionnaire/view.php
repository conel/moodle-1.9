<?php  // $Id: view.php,v 1.19.2.3 2008/12/08 17:42:59 mchurch Exp $

/// This page prints a particular instance of questionnaire

    require_once("../../config.php");
    require_once("lib.php");

    // nkowald - 2010-03-10 - include userid class
    include_once('validate_user.class.php');

    /// Used by the phpESP code.
    global $HTTP_POST_VARS, $HTTP_GET_VARS, $HTTP_SERVER_VARS;

    $SESSION->questionnaire->current_tab = 'view';

    $id = optional_param('id', NULL, PARAM_INT);    // Course Module ID, or
    $a = optional_param('a', NULL, PARAM_INT);      // questionnaire ID

    $qac = optional_param('qac');                   // possible actions
    $sid = optional_param('sid', NULL, PARAM_INT);  // Survey id.

    if ($id) {
        if (! $cm = get_coursemodule_from_id('questionnaire', $id)) {
            error("Course Module ID was incorrect");
        }

        if (! $course = get_record("course", "id", $cm->course)) {
            error("Course is misconfigured");
        }

        if (! $questionnaire = get_record("questionnaire", "id", $cm->instance)) {
            error("Course module is incorrect");
        }

    } else {
        if (! $questionnaire = get_record("questionnaire", "id", $a)) {
            error("Course module is incorrect");
        }
        if (! $course = get_record("course", "id", $questionnaire->course)) {
            error("Course is misconfigured");
        }
        if (! $cm = get_coursemodule_from_instance("questionnaire", $questionnaire->id, $course->id)) {
            error("Course Module ID was incorrect");
        }
    }

/// Check login and get context.
    require_course_login($course, true, $cm);
    $context = get_context_instance(CONTEXT_MODULE, $cm->id);
    require_capability('mod/questionnaire:view', $context);
    add_to_log($course->id, "questionnaire", "view", "view.php?id=$cm->id", "$questionnaire->name", $cm->instance, $USER->id);

    // nkowald - 10-03-10 - Check userid exists
    $valid = new validate_user();
    if ((isset($_GET['id']) && $_GET['id'] == 49699) && $valid->validate_is_student($USER)) {
	
            if ($valid->validate_check_user_id_exists($USER)) {

                $questionnaire = new questionnaire(0, $questionnaire, $course, $cm);
                $questionnaire->strquestionnaires = get_string("modulenameplural", "questionnaire");
                $questionnaire->strquestionnaire  = get_string("modulename", "questionnaire");
                $questionnaire->view();

            } else {
                $update_form = '
                <p><b>'.$USER->firstname.' '.$USER->lastname.'</b>, in order to answer this questionnaire you must enter your student ID number.<br />
                Your ID number can be found on your student swipe card - under the barcode.</p>
                <form action="'.$_SERVER['REQUEST_URI'].'" method="post" name="update_id">
                    <b>Student ID:</b> <input type="text" name="user_id" value="" />
                    <input type="submit" value="Continue" />
                </form>';


                if (isset($_POST) && isset($_POST['user_id'])) {
                    
                    $user_id = trim($_POST['user_id']);
                    // validate user_id
                    if ($valid->validate_validate_user_id($user_id, $USER->firstname, $USER->lastname)) {
                        if ($valid->validate_update_user_id($USER->id, $user_id)) {
                           // successfully updated, show form 
                            $questionnaire = new questionnaire(0, $questionnaire, $course, $cm);
                            $questionnaire->strquestionnaires = get_string("modulenameplural", "questionnaire");
                            $questionnaire->strquestionnaire  = get_string("modulename", "questionnaire");
                            $questionnaire->view();
                        } else {
                            // error updating user's id
                            if (count($valid->errors) > 0) {
                                echo '<b>Errors</b><br />';
                                echo '<ul class="errors">';
                                foreach($valid->errors as $error) {
                                    echo "<li>$error</li>";
                                }
                                echo '</ul>';
                            }
                        }
                    } else {
                        // invalid user id
                        $nav_array = array();
                        print_header('Student ID Required', 'Student ID Required',$nav_array,'update_id.user_id');
                        echo '<p style="color:red;"><b>Invalid student ID. Please check your number and try again</b></p>';
                        // error updating user's id
                        if (count($valid->errors) > 0) {
                            echo '<b>Errors</b><br />';
                            echo '<ul class="errors">';
                            foreach($valid->errors as $error) {
                                echo "<li>$error</li>";
                            }
                            echo '</ul>';
                        }
                        echo $update_form;
                        print_footer();
                    }

                } else {
                    $nav_array = array();
                    print_header('Student ID Required', 'Student ID Required',$nav_array,'update_id.user_id');
                    echo $update_form;
                    print_footer();
                }
            }
    } else {
        // Successful Update or Not a Student
        $questionnaire = new questionnaire(0, $questionnaire, $course, $cm);
        $questionnaire->strquestionnaires = get_string("modulenameplural", "questionnaire");
        $questionnaire->strquestionnaire  = get_string("modulename", "questionnaire");
        $questionnaire->view();
    }
    // nkowald


?>
