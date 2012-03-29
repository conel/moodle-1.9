<?php
   function object_restore_mods($mod,$restore) {

        global $CFG;

        $status = true;
		
        //Get record from backup_ids
        $data = backup_getid($restore->backup_unique_code,$mod->modtype,$mod->id);

        if ($data) {
            //Now get completed xmlized object
            $info = $data->info;

            //Now, build the object record structure
            $object->course = $restore->course_id;
            $object->name = backup_todb($info['MOD']['#']['NAME']['0']['#']);
			$object->summary = backup_todb($info['MOD']['#']['SUMMARY']['0']['#']);
            $object->start_url = backup_todb($info['MOD']['#']['START_URL']['0']['#']);
            $object->material_root = backup_todb($info['MOD']['#']['MATERIAL_ROOT']['0']['#']);  
			$object->imsmanifest = backup_todb($info['MOD']['#']['IMSMANIFEST']['0']['#']);

            //The structure is equal to the db, so insert the object
            $newid = insert_record("object", $object);

            //Do some output
            echo "<li>".get_string("modulename","object")." \"".format_string(stripslashes($object->name),true)."\"</li>";
            backup_flush(300);

            if ($newid) {
                //We have the newid, update backup_ids
                backup_putid($restore->backup_unique_code, $mod->modtype, $mod->id, $newid);
            } else {
                $status = false;
            }
        } else {
            $status = false;
        }

        return $status;
    }

?>