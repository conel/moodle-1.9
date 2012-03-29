<?php

    require_once('../config.php');
    require_once("../course/lib.php");
    require_once("StatsEnhanced.class.php");

    $days = optional_param('days', (365 - date('d', time('now'))), PARAM_INT);
    $start_date = optional_param('sd', 0, PARAM_INT);
    $end_date = optional_param('ed', 0, PARAM_INT);
    $action = optional_param('action', 'update', PARAM_RAW);
    $stat_type = optional_param('stat_type', 'all', PARAM_RAW);
    $filter = optional_param('filter', 'all', PARAM_ALPHA);
    $filter_course = optional_param('course_id', 0, PARAM_INT);
    $filter_category = optional_param('category_id', 0, PARAM_INT);
    $page = optional_param('page', 'trends', PARAM_RAW);

    // require_login(); 

    $stats = new StatsEnhanced();
    if (isset($action) && $action == 'update') {
        // validate get start/end dates
        if ($stats->generateStats($start_date, $end_date, $stat_type, $filter_course, $filter_category)) {
            // success, show updated row
            if ($page == 'compare') {
                $update_html = $stats->updateSingleCompareStat($start_date, $end_date, $stat_type, $filter_course, $filter_category);
                echo $update_html;
            } else {
                echo $stats->updateMonthlyTableRow($start_date, $end_date, $stat_type, $filter_course, $filter_category);
            }
        } else {
            echo 'Errors';
        }
    }

?>
