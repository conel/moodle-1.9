<?php
/**
 * Deletes a Learner Progress Review.
 *
 * Can only be called by hitting 'cancel' on the summmary page.
 *
 * @copyright &copy; 2009 University of London Computer Centre
 * @author http://www.ulcc.ac.uk, http://moodle.ulcc.ac.uk
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package LPR
 * @version 1.0
 */

// initialise moodle
require_once('../../../config.php');

// using these globals
global $SITE, $CFG, $USER;

// include the permissions check
require_once("{$CFG->dirroot}/blocks/lpr/access_content.php");

if(!$can_view) {
    error("You do not have permission to view LPRs");
}

if(!$can_write) {
    error("You do not have permission to edit LPRs");
}

// include the LPR databse library
require_once("{$CFG->dirroot}/blocks/lpr/models/block_lpr_db.php");

// fetch the mandatory LPR id
$id = required_param('id', PARAM_INT);

// fetch the optional url param
$url = optional_param('url', $CFG->wwwroot);

// instantiate the lpr db wrapper
$lpr_db = new block_lpr_db();

// delete the lpr record
$lpr_db->delete_lpr($id);

// because Moodel doesn't enforce foreign keys, we can't cascade the delete, so
// we need to delete from the related tables manually
$lpr_db->delete_attendances($id);
$lpr_db->delete_indicator_answers($id);

// redirect back to the webroot
header("Location: {$url}");
?>