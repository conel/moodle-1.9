<?PHP 
//
//    Part of Flash Activity Module :
//    A Moodle activity module that takes care of a lot of functionality for Flash
//    movie developeres who want their movies to work with Moodle
//    to use Moodles grades table, configuration, backup and restore features etc.
//    Copyright (C) 2004, 2005  James Pratt
//    Contact  : me@jamiep.org http://jamiep.org
//
//    Developed for release under GPL,
//    funded by AGAUR, Departament d'Universitats, Recerca i Societat de la
//    Informaci�, Generalitat de Catalunya.
//
//    This program is free software; you can redistribute it and/or modify
//    it under the terms of the GNU General Public License as published by
//    the Free Software Foundation; either version 2 of the License, or
//    (at your option) any later version.
//
//    This program is distributed in the hope that it will be useful,
//    but WITHOUT ANY WARRANTY; without even the implied warranty of
//    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//    GNU General Public License for more details.
//
//    You should have received a copy of the GNU General Public License
//    along with this program; see flash/license.txt;
//      if not, write to the Free Software
//    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA 


/// This page lists all the instances of flash in a particular course

    require_once("../../config.php");
    require_once("lib.php");

    $id=required_param('id', PARAM_INT);   // course

    if (! $course = get_record("course", "id", $id)) {
        error("Course ID is incorrect");
    }

    require_login($course->id);

    add_to_log($course->id, "flash", "view all", "index.php?id=$course->id", "");


/// Get all required strings

    $strflashs = get_string("modulenameplural", "flash");
    $strflash  = get_string("modulename", "flash");


/// Print the header

    if ($course->category) {
        $navigation = "<A HREF=\"../../course/view.php?id=$course->id\">$course->shortname</A> ->";
    }

    print_header("$course->shortname: $strflashs", "$course->fullname", "$navigation $strflashs", "", "", true, "", navmenu($course));

/// Get all the appropriate data

    if (! $flashs = get_all_instances_in_course("flash", $course)) {
        notice("There are no ".$strflashs, "../../course/view.php?id=$course->id");
        die;
    }

/// Print the list of instances (your module will probably extend this)

    $timenow = time();
    $strname  = get_string("name");
    $strweek  = get_string("week");
    $strtopic  = get_string("topic");

    if ($course->format == "weeks") {
        $table->head  = array ($strweek, $strname);
    } else if ($course->format == "topics") {
        $table->head  = array ($strtopic, $strname);
    } else {
        $table->head  = array ($strname);
    }
    $table->align = array ("CENTER", "LEFT", "LEFT", "LEFT", "LEFT", "LEFT");
    if (isteacher($course->id) )
    {
        $table->head[]='Excel';
        $table->head[]='CSV';
        $table->head[]='Text';
        $table->head[]='';
    };

    $flash_answer_count_sql='SELECT flash.id, COUNT(*) as n '.
        "FROM {$CFG->prefix}flash_accesses AS accesses,  ".
        "{$CFG->prefix}flash_answers AS answers,  ".
        "{$CFG->prefix}flash AS flash ".
        'WHERE answers.accessid=accesses.id  '.
        'AND flash.id =accesses.flashid '.
        "AND flash.course =$course->id ".
        "GROUP BY flash.id";
    if (isteacher($course->id)){
        $flash_answer_counts=get_records_sql($flash_answer_count_sql);
    }
    foreach ($flashs as $flash) {
        if (!$flash->visible) {
            //Show dimmed if the mod is hidden
            $link = "<A class=\"dimmed\" HREF=\"view.php?id=$flash->coursemodule\">$flash->name</A>";
        } else {
            //Show normal if the mod is visible
            $link = "<A HREF=\"view.php?id=$flash->coursemodule\">$flash->name</A>";
        }
        
        if (isteacher($course->id) && !(($flash_answer_counts[$flash->id]->n)>0) )
        {
            if ($course->format == "weeks" or $course->format == "topics") {
                $table->data[] = array ($flash->section, $link, '', get_string('nogrades','flash'));
            } else {
                $table->data[] = array ($link, '', get_string('nogrades','flash'));
            }
        } elseif (isteacher($course->id))
        {
            $excel_link = "<A HREF=\"export.php?type=xls&id=$flash->coursemodule\">".get_string('downloadexcel')."</A>";
            $csv_link = "<A HREF=\"export.php?type=csv&id=$flash->coursemodule\">".get_string('downloadcsv', 'flash')."</A>";
            $text_link = "<A HREF=\"export.php?type=txt&id=$flash->coursemodule\">".get_string('downloadtext')."</A>";
            $list_all = "<A HREF=\"view.php?do=table&id=$flash->coursemodule\">".get_string('see_all_results', 'flash')."</A>";
            if ($course->format == "weeks" or $course->format == "topics") {
                $table->data[] = array ($flash->section, $link, $excel_link, $csv_link, $text_link, $list_all);
            } else {
                $table->data[] = array ($link, $excel_link, $csv_link, $text_link, $list_all);
            }
            
        } else
        {

            if ($course->format == "weeks" or $course->format == "topics") {
                $table->data[] = array ($flash->section, $link);
            } else {
                $table->data[] = array ($link);
            }
        }
    }

    echo "<BR>";

    print_table($table);

/// Finish the page

    print_footer($course);

?>
