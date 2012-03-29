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


/// This page is used to export Flash answers as text/ csv / excel files.

    require_once("../../config.php");
    require_once("lib.php");

    $id=optional_param('id', NULL, PARAM_INT);   // course
    $a=optional_param('a', NULL, PARAM_INT);     // NEWMODULE ID

    if ($id) {
        if (! $cm = get_record("course_modules", "id", $id)) {
            error("Course Module ID was incorrect");
        }
    
        if (! $course = get_record("course", "id", $cm->course)) {
            error("Course is misconfigured");
        }
    
        if (! $flash = get_record("flash", "id", $cm->instance)) {
            error("Course module is incorrect");
        }

    } else {
        if (! $flash = get_record("flash", "id", $a)) {
            error("Course module is incorrect");
        }
        if (! $course = get_record("course", "id", $flash->course)) {
            error("Course is misconfigured");
        }
        if (! $cm = get_coursemodule_from_instance("flash", $flash->id, $course->id)) {
            error("Course Module ID was incorrect");
        }
    }
    flash_export($flash, $_GET['type']);
?>

