<?php  // $Id: lib.php,v 1.2 2006/07/05 22:38:49 wildgirl Exp $

function object_add_instance($object) {
/// Given an object containing all the necessary data, 
/// (defined by the form in mod.html) this function 
/// will create a new instance and return the id number 
/// of the new instance.
//print_r($object);
	if (empty($object->material_root) || empty($object->imsmanifest) || empty($object->start_url)) { error("No Course Selected"); return false; }
	if ($object->material_root == '' || $object->imsmanifest == '' || $object->start_url == '') { error("No Course Selected"); return false; }
	
    return insert_record("object", $object);
}


function object_update_instance($object) {
/// Given an object containing all the necessary data, 
/// (defined by the form in mod.html) this function 
/// will update an existing instance with new data.
	$object->id = $object->instance;
	//print_r($object);
	if (empty($object->material_root) || empty($object->imsmanifest) || empty($object->start_url)) { error("No Course Selected"); return false; }
	if ($object->material_root == '' || $object->imsmanifest == '' || $object->start_url == '') { error("No Course Selected"); return false; }
	
    return update_record("object", $object);
}


function object_delete_instance($id) {
/// Given an ID of an instance of this module, 
/// this function will permanently delete the instance 
/// and any data that depends on it.  

    if (! $object = get_record("object", "id", "$id")) {
        return false;
    }

    $result = true;

    if (! delete_records("object", "id", "$object->id")) {
        $result = false;
    }

    return $result;
}

function object_get_participants($objectid) {
//Returns the users with data in one resource
//(NONE, but must exist on EVERY mod !!)

    return false;
}

function object_get_coursemodule_info($coursemodule) {
/// Given a course_module object, this function returns any 
/// "extra" information that may be needed when printing
/// this activity in a course listing.
///
/// See get_array_of_activities() in course/lib.php

   $info = NULL;

   return $info;
}

?>
