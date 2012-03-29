<?php  // $Id: defaults.php,v 1.1 2008/04/30 10:20:07 skodak Exp $
    if (empty($CFG->exercise_initialdisable)) {
        if (!count_records('exercise')) {
            set_field('modules', 'visible', 0, 'name', 'exercise');  // Disable it by default
            set_config('exercise_initialdisable', 1);
        }
    }

?>
