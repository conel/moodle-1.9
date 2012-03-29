<?php // $Id: index.php,v 1.2 2003/08/26 09:40:58 moodler Exp $
      // This simple script displays all the users on one page.
      // soterd bij ID
      // By default it is not linked anywhere on the site.  If you want to 
      // make it available you should link it in yourself from somewhere.
      // Remember also to comment or delete the lines restricting access
      // to administrators only (see below)


    require_once('../config.php');
	require_once("../course/lib.php");

//    require_login(); 

/// Remove the following three lines if you want everyone to access it
//   if (!isadmin()) {
//        error("Currently only the administrator can access this page!");
//    }
    
    $title = get_string("courses");
    $title .=" (ID)";
    
    print_header($title, $title, $title);
    
    $title_id = get_string("courses");
    $title_id .="(ID) (Desc)";
    $title_fullname=get_string("fullname");
    $title_shortname=get_string("shortname");
//    $title_key=get_string("password");
    $title_startdate=get_string("startdate");
	//added_by_PA
	$title_lastmod="Last Updated";
	$title_num_resources="No: Resources";
    $title_num_labels="No: Labels";
	$title_res_minus_labels="Resources - Labels";
    ?>
<style type="text/css">
<!--
.style1 {
	font-family: Verdana, Arial, Helvetica, sans-serif;
	font-size: 12px;
}
-->
</style>

    <center>

      <p class="style1">This page lists all visible Moodle courses - it shows when they were last updated and shows how many resources (excluding Labels) each course has.</p>
      <p class="style1">To view a particular course, click on it's course number (in bold) and it will open in a new window. </p>
      <p class="style1">At the bottom of the page the average # resources per course is also listed. </p>
      <table border=3>
    <?php
    
    print_spacer(6,1);    
    
    if (!$courses = get_records("course","visible", "1","timemodified DESC", "id,fullname,shortname,startdate,timemodified,password,modinfo")) {
        error("geen courses!");
    }
    
    $counter = 1;
	$tot_res =0;
    

        
    echo "<tr bgcolor=yellow align=center>
            <td align=center>TOT</td>
            <td align=center><b>".$title_id."</b></td>
            <td align=center><b>Details</b></td>
            <td align=center>".$title_fullname."</td>
            <td align=center>".$title_shortname."</td>
			<td align=center>".$title_lastmod."</td>
			<td align=center>".$title_res_minus_labels."</td>
          </tr>";
    $i = 0;
    foreach ($courses as $course) {
            
        if ($i <=20) {	
			$res = count(get_array_of_activities($course->id));
			$lab = count(get_all_instances_in_course('label', $course));
			$total = $res - $lab;
            
			echo "<tr> 
                    <td align=center bgcolor=\'#FFFFB8\'> $counter </td>
                    <td align=center bgcolor=\'#eeffff\'><b><a target='blank' href=\"$CFG->wwwroot/course/view.php?id=$course->id\">".$course->id."</a></b></td>
                    <td align=center><a target='_info' href=\"$CFG->wwwroot/course/info.php?id=$course->id\">
                    <img alt='Info' src='http://moodle.coleggwent.ac.uk/pix_gloscat/help.gif'></a>
                    <td align=left>".$course->fullname."</td>
                    <td align=left>".$course->shortname."</td>
					<td align=right><font size=-2>".strftime('%d/%m/%y %H:%M',$course->timemodified)."</font></td>
					<td align=right><font size=-2>".$total."</font></td>
                  </tr>";    
    $counter=$counter+1;
	$tot_res=$tot_res+$total;
        $i++;
        }
    }
	
//Summery Stats Added by PA

//Work out mean number of resources
$avg_courses=floor($tot_res/$counter);

echo "

	<tr> 
                    <td colspan=\"7\" align=right bgcolor='#000000'></td>
	</tr>
	<tr> 
                    <td colspan=\"7\" align=right>
                        <b>Mean Resources per course: ".$avg_courses."</b>
                    </td>
	</tr>";  
	
    ?>
    </table>
    </center>
    <?php
    print_footer();
    ?>

