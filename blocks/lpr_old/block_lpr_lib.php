<?php
/**
 * Library of assorted functions for the LPR module.
 *
 * @copyright &copy; 2009 University of London Computer Centre
 * @author http://www.ulcc.ac.uk, http://moodle.ulcc.ac.uk
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package LPR
 * @version 1.0
 */

/**
 * Gets a nested array of all categories, with their IDs, names and and nested
 * positions in the hierarchy.
 *
 * @param string $category The ID of the parent category to start at.
 * @return array The nested array of category IDs and names.
 */
function get_categories_array($category = NULL) {

    // initialize the arrays if needed
    $nested = array();

    if (empty($category)) {
        // Start at the top level.
        $category = new stdClass();
        $category->id = 0;
    } else {
        $nested = array(
            'id'        => $category->id,
            'name'      => format_string($category->name),
            'children'  => array()
        );
    }
    // Add all the children recursively, while updating the parent's "children" array.
    if ($categories = get_child_categories($category->id)) {
        foreach ($categories as $cat) {
            $child = get_categories_array($cat);
            if($category->id == 0) {
                $nested[$child['id']] = $child;
            } else {
                $nested['children'][$child['id']] = $child;
            }
        }
    }
    return $nested;
}

/**
 * Prints a nested array of categories as a javscript enabled hierarchial browser.
 *
 * @param string $categories The nested array of categories.
 * @param string $start The start date used to filer the results
 * @param string $end The end date used to filer the results
 */
function recursively_print_categories($categories, $start = null, $end = null) {
    global $CFG;

    foreach($categories as $id => $category) {
        // render the table for the current category ?>
        <li>
            <?php
            if(!empty($category['children'])) { ?>
                <img id="img<?php echo $id; ?>" alt="nav"
                    src="<?php echo $CFG->wwwroot; ?>/theme/conel/pix/t/switch_plus.gif" class="nav"
                    onclick="javascript: toggle_category_menu('<?php echo $id; ?>');" />
                <?php
            } else { ?>
                <img id="img<?php echo $id; ?>" alt="empty_nav"
                    src="<?php echo $CFG->wwwroot; ?>/theme/conel/pix/t/switch_square.gif" />
                <?php
            } ?>
            <a href="<?php echo $CFG->wwwroot; ?>/blocks/lpr/actions/reports.php?category_id=<?php echo $id; ?>&amp;start_date=<?php echo $start; ?>&amp;end_date=<?php echo $end; ?>">
                <?php echo $category['name']; ?>
            </a>
            <ul id='cat<?php echo $id; ?>' class='cat_container'>
                <?php
                if(!empty($category['children'])) {
                    recursively_print_categories($category['children'], $start, $end);
                } ?>
            </ul>
        </li>
        <?php
    }
}

/**
 * Prints a nested array of categories as a javscript enabled hierarchial browser.
 *
 * @param int $percentage The attendance / punctuality as a percentage
 * @return int The mapped value of the attendance / punctuality
 */
function map_attendance($percentage) {
    if($percentage <= 60) {
        return 1;
    } elseif($percentage <= 69) {
        return 2;
    } elseif($percentage <= 79) {
        return 3;
    } elseif($percentage <= 89) {
        return 4;
    } elseif($percentage <= 91) {
        return 5;
    } elseif($percentage <= 93) {
        return 6;
    } elseif($percentage <= 95) {
        return 7;
    } elseif($percentage <= 97) {
        return 8;
    } elseif($percentage <= 99) {
        return 9;
    } elseif($percentage == 100) {
        return 10;
    } else {
        return null;
    }
}

function fix_bad_html($string) {
    // Specify configuration
    $config = array(
        'indent'                      => true,
        'output-xhtml'                => true,
        'wrap'                        => 200,
        'show-body-only'              => true,
        'clean'                       => true,
        'drop-proprietary-attributes' => true,
        'word-2000'                   => true,
        'drop-empty-paras'            => true,
        'hide-comments'               => true
    );
    // Tidy
    $tidy = new tidy();
    $tidy->parseString($string, $config, 'utf8');
    $tidy->cleanRepair();
    return $tidy;
}

function strip_table($string) {
    $match = array(
        '/<table[^>]*>/',
        '/<\/table>/',
        '/<thead[^>]*>/',
        '/<\/thead>/',
        '/<tfoot[^>]*>/',
        '/<\/tfoot>/',
        '/<tbody[^>]*>/',
        '/<\/tbody>/',
        '/<tr[^>]*>/',
        '/<\/tr>/',
        '/<th[^>]*>/',
        '/<\/th>/',
        '/<td[^>]*>/',
        '/<\/td>/'
    );

    $replace = array(
        '',
        '',
        '',
        '',
        '',
        '',
        '',
        '',
        '<br/>',
        '<br/>',
        '',
        '',
        '',
        '',
    );
    return preg_replace($match, $replace, $string);
}