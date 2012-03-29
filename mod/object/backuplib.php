<?php

	function object_backup_mods($bf, $preferences) {

        global $CFG;

        $status = true;

        //Iterate over lesson table
        $objects = get_records("object", "course", $preferences->backup_course, "id");
        if ($objects) {
            foreach ($objects as $object) {
                //Start mod
                fwrite ($bf,start_tag("MOD",3,true));
                //Print lesson data
                fwrite ($bf,full_tag("ID",4,false,$object->id));
				fwrite ($bf,full_tag("MODTYPE",4,false,"object"));
				fwrite ($bf,full_tag("NAME",4,false,$object->name));
				fwrite ($bf,full_tag("SUMMARY",4,false,$object->summary));
				fwrite ($bf,full_tag("START_URL",4,false,$object->start_url));
				fwrite ($bf,full_tag("MATERIAL_ROOT",4,false,$object->material_root));
				fwrite ($bf,full_tag("IMSMANIFEST",4,false,$object->imsmanifest));
				
				fwrite ($bf,end_tag("MOD",3,true));
            }
        }
        return $status;  
    }
	
    function object_check_backup_mods($course,$user_data=false,$backup_unique_code) {
        //First the course data
        $info[0][0] = get_string("modulenameplural","object");
        if ($ids = object_ids ($course)) {
            $info[0][1] = count($ids);
        } else {
            $info[0][1] = 0;
        }

        return $info;
    }
	
	// nkowald - 2011-07-05 - Needs this function to backup successfully!
	function object_backup_one_mod($bf,$preferences,$object_id) {
        $status = true;
		
        if (is_numeric($object_id)) {
            $object = get_record('object','id', $object_id);
        }
		
		if ($object) {
			//Start mod
			fwrite ($bf,start_tag("MOD",3,true));
			//Print lesson data
			fwrite ($bf,full_tag("ID",4,false,$object->id));
			fwrite ($bf,full_tag("MODTYPE",4,false,"object"));
			fwrite ($bf,full_tag("NAME",4,false,$object->name));
			fwrite ($bf,full_tag("SUMMARY",4,false,$object->summary));
			fwrite ($bf,full_tag("START_URL",4,false,$object->start_url));
			fwrite ($bf,full_tag("MATERIAL_ROOT",4,false,$object->material_root));
			fwrite ($bf,full_tag("IMSMANIFEST",4,false,$object->imsmanifest));
			fwrite ($bf,end_tag("MOD",3,true));
		}
		
        return $status;
    }

    function object_ids ($course) {

        global $CFG;

        return get_records_sql ("SELECT a.id, a.course
                                 FROM {$CFG->prefix}object a
                                 WHERE a.course = '$course'");
    }
?>