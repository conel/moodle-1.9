<?php
/*
 **************************************************************************
 *                                                                        *
 *                                                                        *
 *                            THIS SCRIPT                                 *
 *                                and                                     *
 *                         www.bydistance.com                             *
 *                 brought to you by Visions Encoded Inc.                 *
 *                                                                        *
 *                                                                        *
 *                                                                        *
 *             Visit us online at http://visionsencoded.com/              *
 *                You Bring The Vision, We Make It Happen                 *
 **************************************************************************
 **************************************************************************
 * NOTICE OF COPYRIGHT                                                    *
 *                                                                        *
 * Copyright (C) 2009                                                     *
 *                                                                        *
 * This program is free software; you can redistribute it and/or modify   *
 * it under the terms of the GNU General Public License as published by   *
 * the Free Software Foundation; either version 2 of the License, or      *
 * (at your option) any later version.                                    *
 *                                                                        *
 * This program is distributed in the hope that it will be useful,        *
 * but WITHOUT ANY WARRANTY; without even the implied warranty of         *
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the          *
 * GNU General Public License for more details:                           *
 *                                                                        *
 *                  http://www.gnu.org/copyleft/gpl.html                  *
 *                                                                        *
 *                                                                        *
 *                                                                        *
 **************************************************************************
 */


require_once ($CFG->libdir.'/formslib.php');

/***
 * The import csv form for MrCUTE Jr. Repository.
 */
class form_import_csv_mrcutejr extends moodleform {
    function definition() {
        $mform = &$this->_form;
        $mform->addElement('header', '', lang('importcsvformheader'));
        $mform->addElement('file', 'id_csvfile', lang('selectcsvfile'), array('size'=>'30','class'=>'w'));
        $mform->addRule('id_csvfile', null, 'required', null, 'client');
        //$this->add_action_buttons(false, 'button text');//leaves gap don't want, so we don't use it
        $mform->addElement('group', '', ' ');//borderless space
        $mform->addElement('submit', 'submit', lang('importcsvbutton'));
    }
}//END CLASS

?>