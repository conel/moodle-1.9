<?php

    require_once('../config.php');
    require_once("../course/lib.php");
    require_login(); 

	$ts = optional_param('ts', 0, PARAM_INT);

    $sitecontext = get_context_instance(CONTEXT_SYSTEM);
    if (has_capability('mod/data:viewsitestats',$sitecontext) || has_capability('moodle/site:doanything',$sitecontext)) {  // are we god ?
        $access_isgod = 1 ;
    } else {
		error('You do not have permission to view this page', $CFG->wwwroot);
	}
        
    $title = "Statistics - Daily Activity";

    $navlinks = array();
    $navlinks[] = array('name' => 'Statistics', 'link' => 'index.php', 'type' => 'misc');
    $navlinks[] = array('name' => 'Daily Activity', 'link' => 'activity.php', 'type' => 'misc');
    $navigation = build_navigation($navlinks);

    print_header($title, $title, $navigation, '', '', true, '&nbsp;');

?>
<style type="text/css">
/* Activity Stats */
table.day_activity {
	text-transform:capitalize;
	border:1px solid #CCC;
	width:100%;
}
table.day_activity td {
	border:1px solid #CCC;
}
table.day_activity h4 {
	font-size:1.2em;
}
#activity h1 {
	font-size:1.6em;
	text-transform:capitalize;
	margin-bottom:10px;
}
#activity h2 {
	font-size:1.45em;
	color:#000;
	text-transform:capitalize;
}
#activity h3 {
	font-size:2.2em; 
	color:#000; 
	font-weight:bold;
	float:left;
}
#activity h4 {
	font-size:1.3em; 
	text-transform:capitalize;
	margin:0;
	padding:0;
	margin-top:10px;
	margin-bottom:2px;
	color:#000;
}
#activity p {
	margin-bottom:2px;
}
#activity a.view_usage {
	display:block;
	margin-left:87px;
	padding-top:9px;
	padding-bottom:2px;
}
table.day_activity {
	display:none;
	width:100%;
}
table.activity_stats {
	margin:0;
	padding:0;
}
table.activity_stats td {
	border:none;
}
div.line {
	display:block;
	height:9px;
	background-color:#464646;
}
</style>
<div id="content">
    <table id="layout-table" summary="layout">
        <tbody>
            <tr>
                <td id="left-column" summary="layout">
                    <div id="stats_menu">

                        <h3>Activity</h3>
                        <ul>
                            <li><a href="index.php?stat_type=all&amp;filter=all">Monthly Trends</a></li>
							<li><a href="compare.php?filter=directorate">Comparisons</a></li>
							<li><span style="color:#AAA;">Daily Activity</span></li>
                        </ul>

                        <h3>Courses</h3>
                        <ul>
                            <li><a href="reports.php">Last Updated Courses</a></li>
                        </ul>
						
						<h3>Other</h3>
						<ul>
							<li><a href="wincache.php">WinCache</a></li>
						</ul>
                    </div>

                </td>
                <td id="middle-column">
<?php

				// Check stats from 9am to 5pm
				$start_hour = 9;
				$end_hour = 17;
				$timestamp = mktime($start_hour, 0, 0, date('n'), (date('j')), date('Y'));
				
				// Make dropdown from today plus past six days
				$selectbox = '<select name="ts" onchange="this.form.submit()">';
				for ($i=7; $i>=1; $i--) {
					$ts_today = strtotime("-$i days", $timestamp);
					$friendly_date = date('l - d/m/y', $ts_today);
					$selected = (isset($ts) && $ts == $ts_today) ? ' selected="selected"' : '';
					$selectbox .= '<option value="'.$ts_today.'"'.$selected.'>'.$friendly_date.'</option>';
				}
				$selected = (isset($ts) && ($ts == $timestamp || $ts == 0)) ? ' selected="selected"' : '';
				$selectbox .= '<option value="'.$timestamp.'"'.$selected.'>Today</option>';
				$selectbox .= '</select>';
				
				// Use GET param if set: otherwise use current day
				if ($ts != 0) {
					$timestamp = $ts;
				}
				
				echo '<div id="activity">';
				
				echo '<h1>Daily Activity</h1>';
				
				// Get approx. number of students currently online
				echo '<h4>Online Users</h4>';
				$now = time();
				$five_mins_ago = strtotime('-5 minutes', $now);
				$query = sprintf("SELECT COUNT(DISTINCT userid) FROM ".$CFG->prefix."log WHERE time > %d", $five_mins_ago);
				$online_users = number_format(count_records_sql($query));
				echo "<p><img src=\"".$CFG->wwwroot."/theme/standard/pix/t/go.gif\" alt=\"Online\" width=\"11\" height=\"11\" />&nbsp; <b>$online_users users online now</b> (users with activity in the past 5 minutes).</p>";
				$query = "SELECT COUNT(id) FROM ".$CFG->prefix."user WHERE auth != 'nologin'";
				$active = number_format(count_records_sql($query));
				echo "<p>$active active Moodle users.</p>";
				echo '<br />';
				
				echo '<div id="day_select"><form action="activity.php" method="get">';
				echo 'Past Week: ' . $selectbox;
				echo '</form></div>';
				
				echo '<h4>Usage that most affects performance</h4>';
				echo '<div class="graph"><img src="'.$CFG->wwwroot.'/stats/activitygraph.php?ts='.$timestamp.'" alt="Graph" width="750" height="400" /></div>';

				echo '<h4>User Logins</h4>';
				$end_today = strtotime('+8 hours', $timestamp);
				$query = sprintf("SELECT COUNT(DISTINCT userid) as no_logins  FROM ".$CFG->prefix."log WHERE time > %d AND time < %d and module = 'user' and action ='login'", 
				    $timestamp,
				    $end_today
				);
				if ($user_logins = get_records_sql($query)) {
					foreach($user_logins as $login) {
						$no_logins = number_format($login->no_logins);
					}
				} else {
					$no_logins = 0;
				}
				$date_chosen = date('d/m/y', $timestamp);
				$date_today = date('d/m/y', time());
				if ($date_chosen == $date_today) {
					echo "<p>$no_logins unique Moodle logins today (".date('d/m/y', $timestamp).")</p>";
				} else {
					echo "<p>$no_logins unique Moodle logins on <b>".date('l', $timestamp)." (".date('d/m/y', $timestamp).")</b></p>";
				}
				
				$ts_last_week = strtotime('-1 week', $timestamp);
				$ts_last_week_end = strtotime('+8 hours', $ts_last_week);
				$query = sprintf("SELECT COUNT(DISTINCT userid) as no_logins FROM ".$CFG->prefix."log WHERE time > %d AND time < %d AND module = 'user' AND action ='login'", 
                    $ts_last_week,
                    $ts_last_week_end
				);
				if ($user_logins = get_records_sql($query)) {
					foreach($user_logins as $login) {
						$no_logins = number_format($login->no_logins);
					}
				} else {
					$no_logins = 0;
				}
				echo "<p>$no_logins unique logins on this day, ".date('l', $timestamp)." last week.</p>";
				
				echo '</div>';
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
