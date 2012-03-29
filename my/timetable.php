<?php

    require_once('../config.php');
    require_once('../course/lib.php');
    require_once('timetable.class.php');

    //$filter = optional_param('filter', 'all', PARAM_RAW);
    $week_no = optional_param('week', 0, PARAM_INT);

    //require_login(); 
    $username = (isset($USER)) ? $USER->firstname . " " . $USER->lastname : 'Student';
    $html_title = "Student Timetable - $username";
    $title = "Student Timetable";

    $navlinks = array();
    $navlinks[] = array('name' => 'My Moodle', 'link' => 'index.php', 'type' => 'misc');
    $navlinks[] = array('name' => 'Timetable', 'link' => 'timetable.php', 'type' => 'misc');
    $navigation = build_navigation($navlinks);
    $view = optional_param('view', 'stats', PARAM_RAW);

    require_login();

     // Add custom styles
    $stylesheet = '<link rel="stylesheet" type="text/css" media="screen" href="'.$CFG->wwwroot.'/my/timetable.css" />';
    print_header($html_title, $title, $navigation, '', $stylesheet, true, '&nbsp;');

    $timetable = new Timetable();

?>
    <table id="layout-table" summary="layout">
        <tbody>
            <tr>
                <td id="middle-column">
                    <div id="timetable_holder">
                        <?php
                            // ID number must always be a student and must ALWAYS come from currently logged in user - 
                            // never from get parameter or any student could see any other student's timetable data
                            $id_number = (isset($USER->id)) ? $USER->id : 0;
                            if ($id_number != 0) {
                                $role = get_role_staff_or_student($id_number);
                                if ($role == 5) {
                                    // Get students EBS idnumber
                                    $id = (isset($USER->idnumber) && $USER->idnumber != '') ? $USER->idnumber : 0;
                                    if ($id != 0) {
                                        $timetable->showTimetable($id, $week_no);
                                    } else {
                                        error('No idnumber recorded for this student');
                                    }
                                } else {
                                    error('Only logged in students can view their timetable data');
                                }
                            } else {
                                error('Students must be logged in to view their timetable data');
                            }
                        ?>
                    </div>
                </td>
            </tr>
        </tbody>
    </table>
<?php
    print_footer();
?>
