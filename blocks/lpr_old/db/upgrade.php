<?php 
// This file keeps track of upgrades to 
// the LPR block.
//
// Sometimes, changes between versions involve
// alterations to database structures and other
// major things that may break installations.
//
// The upgrade function in this file will attempt
// to perform all the necessary actions to upgrade
// your older installtion to the current version.
//
// If there's something it cannot do itself, it
// will tell you what you need to do.
//
// The commands in here will all be database-neutral,
// using the functions defined in lib/ddllib.php

function xmldb_block_lpr_upgrade($oldversion=0) {

    global $CFG, $THEME, $db;

    $result = array();
	
    if ($oldversion < 2010030100) {

		/// Define table block_lpr_mis_modules to be created
		$table = new XMLDBTable('block_lpr_mis_modules');

		/// Adding fields to table block_lpr_mis_modules
		$table->addFieldInfo('id' , XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null, null, null);
		$table->addFieldInfo('lpr_id' , XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED,  XMLDB_NOTNULL, null, null, null, null);
		$table->addFieldInfo('module_code', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null, null, null);
		$table->addFieldInfo('module_desc', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null, null, null); 
		$table->addFieldInfo('punct_positive', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null,'0', null);  
		$table->addFieldInfo('marks_present', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null,'0', null);
		$table->addFieldInfo('marks_total', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null,'0', null);
		$table->addFieldInfo('selected', XMLDB_TYPE_INTEGER, '1', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null,'0', null);
			
		/// Adding keys to table block_lpr_mis_modules
		$table->addKeyInfo('primary', XMLDB_KEY_PRIMARY, array('id'), null, null);
		$table->addKeyInfo('lpr_idfk', XMLDB_KEY_FORIEGN, array('lpr_id'), 'block_lpr', array('id'));

		// Adding indexes table block_lpr_mis_modules
		$table->addIndexInfo('module_code_index', XMLDB_INDEX_NOTUNIQUE, array('module_code'));
		$table->addIndexInfo('module_code_lpr_id_index', XMLDB_INDEX_NOTUNIQUE, array('module_code','lpr_id'));

		/// Launch create table for block_lpr_mis_modules
		$result[] = create_table($table);
		
		// drop unnecessary field
		$results[] = execute_sql(
			"ALTER TABLE `{$CFG->prefix}block_lpr` 
				ADD COLUMN `unit_desc` TEXT NULL AFTER `timemodified`, 
				DROP COLUMN `tutor_id`, 	
				DROP COLUMN `lecturer_id`,    
				CHANGE `reporter_id` `lecturer_id` BIGINT(10) UNSIGNED DEFAULT NULL"
		);
	}
	
    foreach($results as $result) {
        if($result == false) {
            return false;
        }
    }
	
	return true;
}
?>