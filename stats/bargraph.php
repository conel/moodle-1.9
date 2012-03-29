<?php

    require_once('../config.php');
    require_once('../course/lib.php');
    require_once('StatsEnhanced.class.php');
    require_once($CFG->dirroot.'/lib/graphlib.php'); 
    
    $days = optional_param('days', (365 - date('d', time('now'))), PARAM_INT);
    $stat_type = optional_param('stat_type', 'all', PARAM_RAW);
    $filter = optional_param('filter', 'all', PARAM_RAW);
    $cat = optional_param('cat', 0, PARAM_INT);
    $subcat = optional_param('subcat', 0, PARAM_INT);
    $subsubcat = optional_param('subsubcat', 0, PARAM_INT);

    $stats = new StatsEnhanced();
    if ($cat == 0) {
        $category_ids = $stats->getCategoryIdsForFilter($filter);
    }
    if ($cat != 0) {
        if (!$category_ids = $stats->getSubcategoryIdsForCat($cat)) {
            if ($course_ids = $stats->getCourseIdsForCat($cat)) {
                $category_ids = array();
            }
        }
    }
    if ($subcat != 0) {
        if (!$category_ids = $stats->getSubcategoryIdsForCat($subcat)) {
            if ($course_ids = $stats->getCourseIdsForCat($subcat)) {
                $category_ids = array();
            }
        }
    }
    if ($subsubcat != 0) {
        if (!$category_ids = $stats->getSubcategoryIdsForCat($subsubcat)) {
            if ($course_ids = $stats->getCourseIdsForCat($subsubcat)) {
                $category_ids = array();
            }
        }
    }

    if (count($category_ids) > 0) {
        $avgs = array();
        foreach ($category_ids as $cid) {
            $avgs[$cid] = $stats->getAvgForTimePeriod($days, $cid, 0);
        }
    }
    if (count($course_ids) > 0) {
        $avgs = array();
        foreach ($course_ids as $cid) {
            $avgs[$cid] = $stats->getAvgForTimePeriod($days, 0, $cid);
        }
    }

    $stat_height = '';
    $stat_width = '';
    switch($filter) {
        case 'directorate':
            $stat_height = 700;
            $stat_width = 600;
            break;
        case 'school':
            $stat_height = 650;
            $stat_width = 750;
            break;
        case 'curriculum_area':
            $stat_height = 600;
            $stat_width = 950;
            break;
        default:
            $stat_height = 700;
            $stat_width = 800;
    }
    if (count($course_ids) > 0) {
        $stat_height = 800;
    }
    $graph = new graph($stat_width, $stat_height);

    $graph->parameter['legend'] = 'outside-right';
    $graph->parameter['legend_size'] = 10;
    $graph->parameter['title'] = false;
    $graph->parameter['y_min_left'] = 0;
    //$graph->parameter['y_max'] = 444;
    $graph->parameter['y_decimal_left'] = 0;
    $graph->y_tick_labels = null;
    $graph->parameter['x_axis_angle'] = 90;
    $graph->parameter['axis_size'] = 10;

    $graph->y_order = array(1);
    $graph->y_format = array(
        1 => array(
        'colour' => 'blue',
        'bar' => 'fill',
        'shadow_offset' => 0,
        'legend' => 'Total Activity'
        )
    );

    foreach ($avgs as $key => $value) {
		$stat_name = $value[$key]['name'];
		/*
		if (strlen($stat_name) > 50) {
			
			// put all words into an array
			$words = explode(' ', $stat_name);
			// Break at the 8th word
			$break = $words[7];
			
			if ($pos = strpos($stat_name, $break)) {
				$stat_name_start = substr($stat_name, 0, $pos);
				$stat_name_end = substr($stat_name, $pos);
				$stat_name = $stat_name_start . "\n" . $stat_name_end;
			}
		}
		*/
        $graph->x_data[] = $stat_name;
    }
    foreach ($avgs as $key => $value) {
        if ($value[$key]['avg'] == NULL && $value[$key]['incomplete_avg'] != 0) {
            $graph->y_data[1][] = $value[$key]['incomplete_avg'];
        } else {
            $graph->y_data[1][] = $value[$key]['avg'];
        }
    }

    /*
    echo '<pre>';
    var_dump($graph);
    echo '</pre>';
    */
    $graph->draw();

?>
