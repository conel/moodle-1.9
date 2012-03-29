<?php
/**
 * Global config file for the the Assessment Manager.
 *
 * @copyright &copy; 2009-2010 University of London Computer Centre
 * @author http://www.ulcc.ac.uk, http://moodle.ulcc.ac.uk
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package AssMgr
 * @version 2.0
 */
global $CFG;

require_once($CFG->dirroot.'/blocks/assmgr/constants.php');

//include assessment manager db class
require_once($CFG->dirroot.'/blocks/assmgr/db/assmgr_db.php');

// include the evidence class
require_once($CFG->dirroot.'/blocks/assmgr/classes/resources/assmgr_resource.php');

$dbc = new assmgr_db();

// install any new evidence resource types
assmgr_resource::install_new_plugins();


// -----------------------------------------------------------------------------
// resource type settings
// -----------------------------------------------------------------------------

$resourcetypes = new admin_setting_heading('block_assmgr/resourcetypes', get_string('resourcetypes', 'block_assmgr'), '');
$settings->add($resourcetypes);

// get all the currently installed evidence resource types
$resources = $dbc->get_resource_types();

//add the resources to the form
if(!empty($resources)) {
    foreach ($resources AS $resty) {
        $resource = new admin_setting_configcheckbox("block_assmgr/{$resty->name}", get_string("{$resty->name}", 'block_assmgr'), null, 1);
        $settings->add($resource);
    }
}

// Defaults for various constants

// Portfolio locking
$portlocking = new admin_setting_heading('block_assmgr/portfoliolocking', get_string('portfoliolocking', 'block_assmgr'), '');
$settings->add($portlocking);

$settings->add(new admin_setting_configtext('block_assmgr/defaultexpirytime', get_string('defaultexpirytime', 'block_assmgr'),
                                        get_string('defaultexpirytimeconfig', 'block_assmgr'), 2100));
$settings->add(new admin_setting_configtext('block_assmgr/ajaxexpirytime', get_string('ajaxexpirytime', 'block_assmgr'),
                                        get_string('ajaxexpirytimeconfig', 'block_assmgr'), 300));

//AJAX/flextable
$portlocking = new admin_setting_heading('block_assmgr/ajaxtables', get_string('ajaxtables', 'block_assmgr'), '');
$settings->add($portlocking);

$settings->add(new admin_setting_configtext('block_assmgr/defaulthozsize', get_string('defaulthozsize', 'block_assmgr'),
                                        get_string('defaulthozsizeconfig', 'block_assmgr'), 10));
$settings->add(new admin_setting_configtext('block_assmgr/maxunits', get_string('maxunits', 'block_assmgr'),
                                        get_string('maxunitsconfig', 'block_assmgr'), 5));
$settings->add(new admin_setting_configtext('block_assmgr/maxoutcomesshort', get_string('maxoutcomesshort', 'block_assmgr'),
                                        get_string('maxoutcomesshortconfig', 'block_assmgr'), 5));
$settings->add(new admin_setting_configtext('block_assmgr/maxoutcomeslong', get_string('maxoutcomeslong', 'block_assmgr'),
                                        get_string('maxoutcomeslongconfig', 'block_assmgr'), 8));
$settings->add(new admin_setting_configtext('block_assmgr/maxevidtypesshort', get_string('maxevidtypesshort', 'block_assmgr'),
                                        get_string('maxevidtypesshortconfig', 'block_assmgr'), 5));
$settings->add(new admin_setting_configtext('block_assmgr/maxevidtypeslong', get_string('maxevidtypeslong', 'block_assmgr'),
                                        get_string('maxevidtypeslongconfig', 'block_assmgr'), 10));
$settings->add(new admin_setting_configtext('block_assmgr/defaultverticalperpage', get_string('defaultverticalperpage', 'block_assmgr'),
                                        get_string('defaultverticalperpageconfig', 'block_assmgr'), 10));

// set up the options for moodleevidencetype
$evidencetypes = $dbc->get_evidence_types();
$options = array();

foreach ($evidencetypes as $evidencetype) {
    $options[$evidencetype->name] = get_string($evidencetype->name, 'block_assmgr');
}

$moodleevidence = new admin_setting_configselect('block_assmgr/moodleevidencetype', get_string('moodleevidencetype', 'block_assmgr'),
                                               get_string('moodleevidencetypeconfig', 'block_assmgr'), 'simulationassignment', $options);
$settings->add($moodleevidence);

// -----------------------------------------------------------------------------
// Get plugin settings
// -----------------------------------------------------------------------------

global $CFG;

$plugins = $CFG->dirroot.'/blocks/assmgr/classes/resources/plugins';

$resource_types = assmgr_records_to_menu($dbc->get_resource_types(), 'id', 'name');

foreach ($resource_types as $resource_file) {

    require_once($plugins.'/'.$resource_file.".php");
    // instantiate the object
    $class = basename($resource_file, ".php");
    $resourceobj = new $class();
    $method = array($resourceobj, 'config_settings');

    //check whether the config_settings method has been defined
    if (is_callable($method,true)) {
        $settings = $resourceobj->config_settings($settings);
    }
}

?>