<?php
    class block_bksb extends block_base {

        function init() {
            $this->title   = 'BKSB Assessment Results';
            $this->version = 20100901;
        }

        function get_content() {
            if ($this->content !== NULL) {
              return $this->content;
            }

            $access_isgod = 0 ;
            $access_isteacher = 0 ;
            $access_isstudent = 0 ;
            $access_isother = 0 ;

            if (has_capability('moodle/site:doanything', get_context_instance(CONTEXT_SYSTEM))) {  // are we god ?
                $access_isgod = 1 ;
            }
            if (!$currentcontext = get_context_instance(CONTEXT_COURSE, $this->instance->pageid)) {
                $access_isother = 1 ;
            } else {
                if (has_capability('block/ilp:viewclass',$currentcontext)) { // are we the teacher on the course ?
                    $access_isteacher = 1 ;
                } elseif (has_capability('block/ilp:view',$currentcontext)) {  // are we a student on the course ?
                    $access_isstudent = 1 ;
                }
            }

            // Double-check if user is student
            $userid = $_SESSION['USER']->id;
            $role = get_role_staff_or_student($userid);
            if ($role == 5) $access_isstudent = 1;

            $this->content =  new stdClass;
            $course_id = (isset($_GET['id'])) ? $_GET['id'] : '';
            if ($access_isstudent) {
                $get_params = ($course_id != '') ? '?userid='.$userid.'&amp;courseid='.$course_id.'' : '?userid='.$userid;
                $block_content = 
                '<ul id="bksb_block_ul">
                    <li class="ia_icon"><a href="'.$CFG->wwwroot.'/blocks/bksb/bksb_initial_assessment.php'.$get_params.'">My Initial Assessments</a></li>
                    <li class="da_icon"><a href="'.$CFG->wwwroot.'/blocks/bksb/bksb_diagnostic_overview.php'.$get_params.'">My Diagnostic Assessments</a></li>
                </ul>';
            } else {
                if ($course_id != '') {
                    $block_content = 
                    '<ul id="bksb_block_ul">
                        <li class="ia_icon"><a href="'.$CFG->wwwroot.'/blocks/bksb/bksb_initial_assessment.php?courseid='.$course_id.'">Initial Assessments</a></li>
                        <li class="da_icon"><a href="'.$CFG->wwwroot.'/blocks/bksb/bksb_diagnostic_overview.php?courseid='.$course_id.'&assessment=1">Diagnostic Assessments</a></li>
                    </ul>';
                } else {
                    $block_content = 'No course id';
                }
            }
            $this->content->text   = $block_content;
            $this->content->footer = '';

            return $this->content;
        }

    }
?>
