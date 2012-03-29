<?php

    require_once('../config.php');
    require_once("../course/lib.php");
    require_once("StatsEnhanced.class.php");

    $days = optional_param('days', (365 - date('d', time('now'))), PARAM_INT);
    $stat_type = optional_param('stat_type', 'all', PARAM_RAW);
    $filter = optional_param('filter', 'all', PARAM_RAW);
    $filter_course = optional_param('course_id', 0, PARAM_INT);
    $filter_category = optional_param('category_id', 0, PARAM_INT);
    $cid = optional_param('cid', 0, PARAM_INT);
    $sid = optional_param('sid', 0, PARAM_INT);
    $did = optional_param('did', 0, PARAM_INT);
    if (isset($_GET['cid'])) $filter_category = $cid;
    if (isset($_GET['sid'])) $filter_category = $sid;
    if (isset($_GET['did'])) $filter_category = $did;
    $subcat = optional_param('subcat', 0, PARAM_INT);
    $subsubcat = optional_param('subsubcat', 0, PARAM_INT);

    require_login(); 
	
	$sitecontext = get_context_instance(CONTEXT_SYSTEM);
	
    if (has_capability('mod/data:viewsitestats',$sitecontext) || has_capability('moodle/site:doanything',$sitecontext)) {  // are we god ?
        $access_isgod = 1 ;
    } else {
		error('You do not have permission to view this page', $CFG->wwwroot);
	}
        
    $title = "Statistics - Monthly Trends";

    $navlinks = array();
    $navlinks[] = array('name' => 'Statistics', 'link' => 'index.php', 'type' => 'misc');
    $navlinks[] = array('name' => 'Monthly Trends', 'link' => 'index.php', 'type' => 'misc');
    $navigation = build_navigation($navlinks);
    $view = optional_param('view', 'stats', PARAM_RAW);

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
                            <li><span style="color:#AAA;">Monthly Trends</span></li>
                            <li><a href="compare.php?filter=directorate">Comparisons</a></li>
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
                    echo '<form action="'.$_SERVER['PHP_SELF'].'" id="stat_filters" method="get">';

                    if ($view != 'course') {

                        echo '<p>Filter: ';
                        $filters = array('all', 'directorate', 'school', 'curriculum_area');
                        $current_filter = (isset($filter) && in_array($filter, $filters)) ? $filter : '';

                        foreach ($filters as $fil) {
                            $selected_html = ($current_filter == $fil) ? ' checked="checked" ' : '';
                            echo '<input name="filter" id="filter_'.$fil.'" type="radio" value="'.$fil.'"'.$selected_html.'/><label for="filter_'.$fil.'">'.ucwords(str_replace('_', ' ', $fil)).'</label>&nbsp;';
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
                            choose_from_menu($schools, 'sid', $filter_category, 'choose', '');
                            echo '</p>';
                        }
                        
                        // Curriculum Area drop-down                        
                        $query = "SELECT id, name FROM ".$CFG->prefix."course_categories
                                            WHERE id IN (".$stats->valid_curric_ids.") 
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
                                echo '<p id="select_subsubcategories"><label>Sub-subcategories:</label>';
                                choose_from_menu($subsubcats, 'subsubcat', $subsubcat, 'choose', 'this.form.submit()');
                                echo '</p>';
                            }
                         }

                         // Now filter category should be the highest most category
                        if ($filter_category != 0) {
                            $selected_cat = $filter_category;
                        }
                        if ($subcat != 0) {
                            $selected_cat = $subcat;
                        }
                        if ($subsubcat != 0) {
                            $selected_cat = $subsubcat;
                        }

                        // Show course drop-down if directorate/school/curriculum area selected
                        $valid_filters = array('curriculum_area');
                        if (in_array($filter, $valid_filters) && $subcat != 0) {
                            $courses = $stats->getCourseIdsFromCategory($selected_cat);
                            // explode $courses to transform from CSV string to array
                            if ($courses != '') {
                                $query = "SELECT id, shortname FROM ".$CFG->prefix."course WHERE id IN(".$courses.") ORDER BY shortname ASC";
                                if ($filtered_courses = get_records_sql_menu($query)) {
                                    $courses_array = array();
                                    foreach ($filtered_courses as $key => $value) {
                                        $courses_array[$key] = $value; 
                                    }
                                    echo'<p id="select_course_id"'.$show_style.'><label>Course:</label> ';
                                    choose_from_menu($courses_array, 'course_id', $filter_course, 'choose', 'this.form.submit()');
                                    echo '</p>';
                                }
                            }
                        }

                     // show if not course view
                    } else {
                        // We are in course view so need to keep defaults in the form values
                        echo '
                            <input type="hidden" name="course_id" value="'.$filter_course.'" />
                            <input type="hidden" name="filter" value="all" />
                            <input type="hidden" name="view" value="course" />
                            ';
                    }

                    echo '<div id="suboptions">';
                    echo 'Type: ';
                    echo '<select name="stat_type">';
                    $types = array('all', 'views', 'adds', 'updates', 'uploads', 'deletes');
                    foreach ($types as $type) {
                        $selected = ($stat_type == $type) ? ' selected="selected"' : '';
                        echo '<option value="'.$type.'" '.$selected.'>'.ucwords($type).'</option>';
                    }
                    echo '</select>';
                    echo '&nbsp;(<a href="#" id="show_key">show key</a>)';
                    echo '&nbsp;&nbsp;&nbsp;';
                    echo 'Time period: '.$time_select.' &nbsp;';
                    echo '<input type="submit" value="View" />';
                    echo '</div>';
                    echo '</div>';
                    echo '</form>';
                    echo '<br />';

                    if ($filter_course != 0) {
                        $course_name = $stats->getName(0, $filter_course);
                        echo "<h3 id=\"stat_title\">Monthly Trends for course: <span>$course_name</span></h3>";
                    } else if ($selected_cat != 0) {
                        $cat_name = $stats->getName($selected_cat, 0);
                        echo "<h3 id=\"stat_title\">Monthly Trends for: <span>$cat_name</span></h3>";
                    } else {
						// Find number of visible courses to display in heading
						
						if ($courses = get_records('course', 'visible', 1)) {
							$no_courses = ' <span>(' . count($courses) . ' courses)</span>';
						} else {
							$no_courses = '';
						}
                        echo "<h3 id=\"stat_title\">Monthly Trends for all courses$no_courses</h3>";
                    }

                    echo '<div id="type_key">';
                        echo '<h3>Type Key</h3>';

                        echo '<h4>Views</h4>';
                        echo '<p>view all, view discussion, view entry, view form, view forum, view forums, view grade, view graph, view report, view responses, view submission, view subscribers</p>';

                        echo '<h4>Adds</h4>';
                        echo '<p>add, add category, add contact, add discussion, add entry, add mod, add post</p>'; 

                        echo '<h4>Updates</h4>';
                        echo '<p>update, update entry, update feedback, udpate grades, update mod, update post</p>';

                        echo '<h4>Uploads</h4>';
                        echo '<p>uploads</p>';

                        echo '<h4>Deletes</h4>';
                        echo '<p>delete, delete attempt, delete discussion, delete entry, delete mod, delete post</p>';
                    echo '</div>';

                    //$days = (isset($_GET['days'])) ? $_GET['days'] : ));

                    // Now filter category should be the highest most category
                    if ($filter_category != 0) {
                        $filter_category = $filter_category;
                    }
                    if ($subcat != 0) {
                        $filter_category = $subcat;
                    }
                    if ($subsubcat != 0) {
                        $filter_category = $subsubcat;
                    }

                    echo '<div class="graph"><img src="'.$CFG->wwwroot.'/stats/graph.php?mode=1&amp;course=1&amp;time='.$days.'&amp;report=4&amp;roleid=0&amp;stat_type='.$stat_type.'&amp;course_id='.$filter_course.'&amp;category_id='.$filter_category.'" alt="Statistics graph" id="stat_graph" width="750" height="400" /></div>';
                    echo '</center>';
                    echo '<br />';

                    if (isset($_GET['action']) && $_GET['action'] == 'update') {
                        // validate get start/end dates
                        $start_date = $_GET['sd'];
                        $end_date = $_GET['ed'];
                        $stats->generateStats($start_date, $end_date, $stat_type, $filter_course, $filter_category);
                    }
                    $stats->displayMonthlyStats($days, $stat_type, $filter_course, $filter_category);
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
