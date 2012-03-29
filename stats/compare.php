<?php

    require_once('../config.php');
    require_once("../course/lib.php");
    require_once($CFG->dirroot.'/lib/graphlib.php'); 
    require_once("StatsEnhanced.class.php");

    $days = optional_param('days', (365 - date('d', time('now'))), PARAM_INT);
    $stat_type = optional_param('stat_type', 'all', PARAM_RAW);
    $filter = optional_param('filter', 'directorate', PARAM_RAW);
    $filter_category = optional_param('category_id', 0, PARAM_INT);
    $filter_course = optional_param('course_id', 0, PARAM_INT);
    $cid = optional_param('cid', 0, PARAM_INT);
    $sid = optional_param('sid', 0, PARAM_INT);
    $did = optional_param('did', 0, PARAM_INT);
    if ($cid != 0) {
        $filter_category = $cid;
    } else if ($sid != 0) {
        $filter_category = $sid;
    } else if ($did != 0) {
        $filter_category = $did;
    }
    $subcat = optional_param('subcat', 0, PARAM_INT);
    $subsubcat = optional_param('subsubcat', 0, PARAM_INT);
    $view = optional_param('view', 'stats', PARAM_RAW);

    require_login(); 

    $sitecontext = get_context_instance(CONTEXT_SYSTEM);
    if (has_capability('mod/data:viewsitestats',$sitecontext) || has_capability('moodle/site:doanything',$sitecontext)) {  // are we god ?
        $access_isgod = 1 ;
    } else {
		error('You do not have permission to view this page', $CFG->wwwroot);
	}
        
    $title = "Statistics - Comparisons";

    $navlinks = array();
    $navlinks[] = array('name' => 'Statistics', 'link' => 'index.php', 'type' => 'misc');
    $navlinks[] = array('name' => 'Comparisons', 'link' => 'compare.php', 'type' => 'misc');
    $navigation = build_navigation($navlinks);

    print_header($title, $title, $navigation, '', '', true, '&nbsp;');

    echo '<script type="text/javascript" src="jquery.blockUI.js"></script>';
    echo '<script type="text/javascript" src="functions.js"></script>';

    $stats = new StatsEnhanced();
    $time_select = $stats->generateTimeSelect();

?>
<div id="content">
    <table id="layout-table" summary="layout">
        <tbody>
            <tr>
                <td id="left-column" summary="layout">
                    <div id="stats_menu">

                        <h3>Activity</h3>
                        <ul>
                            <li><a href="index.php?stat_type=all&amp;filter=all">Monthly Trends</a></li>
                            <li><span style="color:#AAA;">Comparisons</span></li>
							<li><a href="activity.php">Daily Activity</a></li>
                        </ul>

                        <h3>Courses</h3>
                        <ul>
                        <!--
                            <li>Most Active Courses</li>
                            <li>Least Active Courses</li>
                            -->
                            <li><a href="reports.php">Last Updated Courses</a></li>
                        </ul>
                        <!--
                        <h3>Teachers</h3>
                        <ul>
                            <li>Most Active Teachers</li>
                            <li>Least Active Teachers</li>
                        </ul>
                        -->
						<h3>Other</h3>
						<ul>
							<li><a href="wincache.php">WinCache</a></li>
						</ul>
                    </div>

                </td>
                <td id="middle-column">
                <?php
                    echo '<center>';
                    echo '<form action="'.$_SERVER['PHP_SELF'].'" method="get" id="stat_filters">';

                    echo '<p>Filter: ';
                    $filters = array('directorate', 'school', 'curriculum_area');
                    $current_filter = (isset($filter) && in_array($filter, $filters)) ? $filter : '';

                    foreach ($filters as $fil) {
                        $selected_html = ($current_filter == $fil) ? ' checked="checked" ' : '';
                        echo '<input name="filter" id="filter_'.$fil.'" type="radio" value="'.$fil.'"'.$selected_html.' /><label for="filter_'.$fil.'">'.ucwords(str_replace('_', ' ', $fil)).'</label>&nbsp;';
                    }
                    echo '</p>';

                    echo '<div id="subcat_holder">';

                    // Directorates drop-down
                    $query = "SELECT id, name FROM ".$CFG->prefix."course_categories ".
                                  "WHERE parent = 0 ".
                                  "AND name LIKE '%Directorate%' ".
                                  "ORDER BY sortorder ASC";

                    if ($directorates = get_records_sql_menu($query)) {
                        $show_style = ($current_filter == 'directorate') ? ' style="display:block;" ' : '';
                        echo'<p id="select_directorates"'.$show_style.'><label>Directorates:</label> ';
                        choose_from_menu($directorates, 'did', $filter_category, 'choose', '');
                        echo '</p>';
                    }

                    // Schools drop-down
                    $query = "SELECT * FROM ".$CFG->prefix."course_categories WHERE name LIKE ('School of%') ORDER BY sortorder";

                    if ($schools = get_records_sql_menu($query)) {
                        $show_style = ($current_filter == 'school') ? ' style="display:block;" ' : '';
                        echo'<p id="select_schools"'.$show_style.'><label>School:</label> ';
                        choose_from_menu($schools, 'sid', $filter_category, 'choose', ')');
                        echo '</p>';
                    }

                    $valid_curric_ids = "385, 122, 399, 45, 39, 22, 58, 124, 24, 61, 40, 3, 306, 4, 205, 42, 117, 118, 119, 123, 400, 386, 46, 63, 59, 41, 50, 333, 49, 398, 120, 121, 43, 401, 29, 402, 335, 125, 263, 30, 51, 334, 31, 393, 16, 33, 44, 394, 398, 55, 54, 67, 66, 68, 69, 274";
                    $query = "SELECT id, name FROM ".$CFG->prefix."course_categories
                                    WHERE id IN (".$valid_curric_ids.") 
                                    ORDER BY name ASC";

                    if ($curriculum_areas = get_records_sql_menu($query)) {
                        $show_style = ($current_filter == 'curriculum_area') ? ' style="display:block;" ' : '';
                        echo'<p id="select_curriculum_areas"'.$show_style.'><label>Curriculum Area:</label> ';
                        choose_from_menu($curriculum_areas, 'cid', $filter_category, 'choose', '');
                        echo '</p>';
                    }

                     // If filter_category chosen, show drop down of its subcategories
                     if ($filter_category != 0) {
                        $subcats = $stats->getSubcatDataForCatID($filter_category);
                        if ($subcats) {
                            echo '<p id="select_subcategories"><label>Subcategories:</label> ';
                            choose_from_menu($subcats, 'subcat', $subcat, 'choose', '');
                            echo '</p>';
                        }
                     }

                     // If subcat chosen, show drop down of its subsubcategories
                     if ($subcat != 0) {
                        $subsubcats = $stats->getSubcatDataForCatID($subcat);
                        if ($subsubcats) {
                            echo '<p id="select_subsubcategories"><label>Sub-subcategories: </label>';
                            choose_from_menu($subsubcats, 'subsubcat', $subsubcat, 'choose', 'this.form.submit()');
                            echo '</p>';
                        }
                     }

                    echo '<label>Time period:</label> '.$time_select.' &nbsp;';
                    echo '<input type="submit" value="View" />';
                    echo '</form>';
                    echo '</div>';
                    echo '<br />';

                    $level = 'filter';
                    if ($filter_category == 0) {
                        $category_ids = $stats->getCategoryIdsForFilter($filter);
                    } 
                    if ($filter_category != 0) {
                        if ($cids = $stats->getSubcategoryIdsForCat($filter_category)) {
                            $category_ids = $cids;
                            $level = 'category';
                        } else {
                            $category_ids = array();
                            if ($course_ids = $stats->getCourseIdsForCat($filter_category)) {
                                $level = 'category';
                            }
                        }
                    } 
                    if ($subcat != 0) {
                        if ($subcat_ids = $stats->getSubcategoryIdsForCat($subcat)) {
                            $category_ids = $subcat_ids;
                            $level = 'subcategory';
                        } else {
                            $category_ids = array();
                            if ($course_ids = $stats->getCourseIdsForCat($subcat)) {
                                $level = 'subcategory';
                            }
                        }
                    }
                    if ($subsubcat != 0) {
                        if ($subsubcat_ids = $stats->getSubcategoryIdsForCat($subsubcat)) {
                            $category_ids = $subsubcat_ids;
                            $level = 'subsubcategory';
                        } else {
                            $category_ids = array();
                            if ($course_ids = $stats->getCourseIdsForCat($subsubcat)) {
                                $level = 'subsubcategory';
                            }
                        }
                    }


                    switch($level) {

                        case 'filter':
                        echo "<h3 id=\"stat_title\">Comparisons for: <span>".str_replace('_', ' ', $filter)."s</span></h3>";
                        break;

                        case 'category':
                        $cat_name = $stats->getName($filter_category, 0);
                        echo "<h3 id=\"stat_title\">Comparisons for: <span>$cat_name</span></h3>";
                        break;

                        case 'subcategory':
                        $cat_name = $stats->getName($subcat, 0);
                        echo "<h3 id=\"stat_title\">Comparisons for: <span>$cat_name</span></h3>";
                        break;

                        case 'subsubcategory':
                        $cat_name = $stats->getName($subsubcat, 0);
                        echo "<h3 id=\"stat_title\">Comparisons for: <span>$cat_name</span></h3>";
                        break;

                    }

                    $avg_type = '';
                    if (count($category_ids) > 0) {
                        $avgs = array();
                        foreach ($category_ids as $cid) {
                            $avgs[$cid] = $stats->getAvgForTimePeriod($days, $cid, 0);
                        }
                        $avg_type = 'category';
                    }
                    if (count($course_ids) > 0) {
                        $avgs = array();
                        foreach ($course_ids as $cid) {
                            $avgs[$cid] = $stats->getAvgForTimePeriod($days, 0, $cid);
                        }
                        $avg_type = 'course';
                    }

                    $html = '';
                    $cat_html = '';
                    $months_for_days = $stats->getAllMonths($days);
                    arsort($months_for_days);
                    $incomplete = FALSE;

                    foreach ($avgs as $key => $value) {
                        // If avg value is null we need to generate stats for this category/period
                        $avg_value = $value[$key]['avg'];
                        $empty_months = $value[$key]['empty_months'];
                        $cat_html = '';

                        if ($avg_value == NULL) {

                            $cat_html .= "<h3>".$value[$key]['name']."</h3>";
                            $cat_html .= '<table border="1" cellpadding="5"><tr>';
                            // Reverse array so shows dates from newest to oldest LTR
                            foreach ($months_for_days as $month => $timestamps) {
                                // Convert date to readable format
                                $ts_start = $timestamps[0];
                                $month_readable = date('M Y', $ts_start);
                                $cat_html .= "<th>$month_readable</th>";
                            }
                            $cat_html .= "</tr>";
                            $cat_html .= "<tr>";

                            $incomplete_cat = FALSE;
                            foreach ($months_for_days as $month => $timestamps) {
                                $stat_type = '';
                                if (in_array($month, $empty_months['no_logs'])) {
                                    $stat_type = 'no_logs';
                                } else if (in_array($month, $empty_months['logs_exist'])) {
                                    $stat_type = 'logs_exist';
                                }
                                $stats_exist = TRUE;

                                  // if stat_exists = FALSE: check that logs exist, if no logs exist show 'no logs' text
                                if ($stat_type == 'no_logs') {
                                    $update_link = 'no logs';
                                    $stats_exist = FALSE;
                                } else if ($stat_type == 'logs_exist') {
                                    $stats_exist = (in_array($month, $empty_months['logs_exist'])) ? FALSE : TRUE;
                                    $incomplete_cat = TRUE;
                                }
								
								// nkowald - 2010-09-29 - If a stat is locked (complete [in theory]) then hide it with CSS
								if ($stat_type != 'no_logs') {
									if ($stats_exist == TRUE) {
										$update_class = 'update_link_comparisons locked';
									} else {
										$update_class = 'update_link_comparisons';
									}
									
									if ($avg_type == 'category') {
										$update_link = "<a href=\"compare.php?action=update&amp;sd=".$timestamps[0]."&amp;ed=".$timestamps[1]."&amp;stat_type=all&amp;category_id=".$key."&amp;filter=".$filter."&amp;days=".$days."\" class=\"$update_class\">update</a>";
									} else if ($avg_type = 'course') {
										$update_link = "<a href=\"compare.php?action=update&amp;sd=".$timestamps[0]."&amp;ed=".$timestamps[1]."&amp;stat_type=all&amp;course_id=".$key."&amp;filter=".$filter."&amp;days=".$days."\" class=\"$update_class\">update</a>";
									}
								}
								
                                $cat_html .= '<td style="text-align:center;">';
								// nkowald - 2010-09-29 - If a stat is locked (complete [in theory]) then hide it with CSS
                                //$cat_html .= ($stats_exist == TRUE) ? '<img src="images/tick.gif" width="39" height="34" alt="Stat exists" />' : $update_link;
                                $cat_html .= ($stats_exist == TRUE) ? '<img src="images/tick.gif" width="39" height="34" alt="Stat exists" />' : $update_link;
                                $cat_html .= '</td>';

                            } // foreach Months in cat

                            $cat_html .= "</tr>";
                            $cat_html .= "</table>";

                            if ($incomplete_cat == TRUE) {
                                $html .= $cat_html;
                            }

                        } // if avg_value == NULL


                    } // foreach average

                    if ($html != '') {
                        echo "<h1 style=\"color:red;\">Incomplete Stats!</h1>";
                        echo "<p>Update the stats below to see true comparisons.</p>";
                        $img = $stats->generateBargraphImgFromLevel($level, $filter, $days, $filter_category, $subcat, $subsubcat);
                        echo $img;
                        echo $html;
                    } else {

                        $img = $stats->generateBargraphImgFromLevel($level, $filter, $days, $filter_category, $subcat, $subsubcat);
                        echo $img;
                        echo '<table border="1">';
                        foreach ($avgs as $key => $value) {
                            echo '<tr>';
                            echo '<td>'.$value[$key]['name'].'</td>';
                            $avg = ($value[$key]['avg'] != NULL) ? $value[$key]['avg'] : $value[$key]['incomplete_avg'];
                            echo '<td>'.$avg.'</td>';
                            echo '</tr>';
                        }
                        echo '</table>';

                    }
                    echo '</center>';

                    if (isset($_GET['action']) && $_GET['action'] == 'update') {
                        // validate get start/end dates
                        $start_date = $_GET['sd'];
                        $end_date = $_GET['ed'];
                        $stats->generateStats($start_date, $end_date, $stat_type, $filter_course, $filter_category);
                    }
                ?>
                </td>
                <td id="right-column"></td>
            </tr>
        </tbody>
    </table>
</div>
<?php
    print_footer();
?>
