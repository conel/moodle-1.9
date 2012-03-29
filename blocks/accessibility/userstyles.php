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
 * Sets per-user styles                                                (1)
 *
 * This file is the cornerstone of the block - when the page loads, it
 * checks if the user has a custom settings for the font size and colour
 * scheme (either in the session or the database) and creates a stylesheet
 * to override the standard styles with this setting. This requires a <link>
 * tag to be added to the header.html of any theme that this block needs to
 * work with.                                                           (2)
 *
 * @see block_accessibility.php                                        (3)
 * @package   blocks-accessibility                                      (4)
 * @copyright Copyright &copy; 2009 Taunton's College                   (5)
 * @author Mark Johnson                                                 (6)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later (7)
 */

require_once('../../config.php');
require_once($CFG->dirroot.'/blocks/accessibility/lib.php');
header('Content-Type: text/css');
// First, check the session to see if the user's overridden the default/saved setting
$options = get_record('accessibility', 'userid', $USER->id);

if(!empty($USER->fontsize)) {

    $fontsize = $USER->fontsize;

} else if (!empty($options->fontsize)) {
    $fontsize = $options->fontsize;
}
if(!empty($USER->colourscheme)) {

    $colourscheme = $USER->colourscheme;

} else if (!empty($options->colourscheme)) {

     $colourscheme = $options->colourscheme;

}

if (!empty($fontsize) || !empty($colourscheme)) {
// Echo out CSS for the body element. Use !important to override any other external
    // stylesheets.
    if (!empty($fontsize)) {
        echo 'body {font-size: '.$fontsize.'% !important;}';
    }
    if (!empty($colourscheme)) {
        switch ($colourscheme) {
            case 2:
                echo '* {background-color: #FFFFCC !important;};
                    forumpost .topic {background-image: none !important;}
                    * {background-image: none !important;}';
                break;

            case 3:
                echo '* {background-color: #99CCFF !important;}
                    forumpost .topic {background-image: none !important;}
                    * {background-image: none !important;}';
                break;

            case 4:
                echo '* {color: #ffff00 !important;}
                    * {background-color: #000000 !important;}
                    * {background-image: none !important;}
                    #content a, .tabrow0 span {color: #ff0000 !important;}
                    .tabrow0 span:hover {text-decoration: underline;}
                    #textresize .outer,  #colourchange .outer {border-color:#fff !important;}';
                break;

        }

    }
}

?>
