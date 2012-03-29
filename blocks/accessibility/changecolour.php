<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.


/**
 * Sets the session variable for custom colour schemes                  (1)
 *
 * This page accepts the required colour scheme as an argument, and
 * sets a session variable accordingly. If the colour scheme is 1 (the
 * theme default) the variable is unset.
 * If the page is being requested via AJAX, we just return HTTP 200, or
 * 400 if the parameter was invalid. If requesting normally, we redirect
 * to reset the saved setting, or to the page we came from as required. (2)
 *
 * @package   blocks-accessibility                                      (3)
 * @copyright Copyright &copy; 2009 Taunton's College                   (4)
 * @author  Mark Johnson                                               (5)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later (6)
 * @param int scheme - The number of the colour scheme, 1-4             (7)
 */

require_once('../../config.php');
require_once($CFG->dirroot.'/blocks/accessibility/lib.php');

$scheme = required_param('scheme', PARAM_INT);
if (!accessibility_is_ajax()) {
    $redirect = required_param('redirect', PARAM_TEXT);
}

switch($scheme) {
	case 1:
        unset($USER->colourscheme);
        if (!accessibility_is_ajax()) {
            redirect($CFG->wwwroot.'/blocks/accessibility/database.php?op=reset&scheme=true&userid='.$USER->id.'&amp;redirect='.$redirect);
        }
        break;

    case 2:
        $USER->colourscheme = 2;
        break;

    case 3:
        $USER->colourscheme = 3;
        break;

    case 4:
        $USER->colourscheme = 4;
        break;

    default:
        header("HTTP/1.0 400 Bad Request");
        break;
}

if (!accessibility_is_ajax()) {
    redirect($CFG->wwwroot.$redirect);
}

?>
