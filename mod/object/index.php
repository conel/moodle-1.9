<?php

    require_once("../../config.php");

    $id = required_param('id', PARAM_INT);   // course

    if (!empty($CFG->forcelogin)) {
        require_login();
    }

    if (! $course = get_record("course", "id", $id)) {
        error("Course ID is incorrect");
    }

    if ($course->category) {
        require_login($course->id);
        $navigation = "<a href=\"../../course/view.php?id=$course->id\">$course->shortname</a> ->";
    } else {
        $navigation = '';
    }

    add_to_log($course->id, "object", "view all", "index.php?id=$course->id", "");

    $strobject = get_string("modulename", "object");
    $strobjects = get_string("modulenameplural", "object");
	
	if ($course->format == "weeks") {
        $table->head  = array (get_string("week"), get_string("name"), get_string("summary"));
        $table->align = array ("center", "left", "left");
    } elseif ($course->format == "topics") {
        $table->head  = array (get_string("topic"), get_string("name"), get_string("summary"));
        $table->align = array ("center", "left", "left");
    } else {
        $table->head  = array (get_string("name"), get_string("summary"));
        $table->align = array ("left", "left");
    }

    print_header("$course->shortname: $strobject", "$course->fullname", "$navigation $strobjects", 
                 "", "", true, "", navmenu($course));

    if (! $objects = get_all_instances_in_course("object", $course)) {
        notice("There are no objects in this course.", "../../course/view.php?id=$course->id");
        exit;
    }
	
    $currentsection = "";
    $options->para = false;
	
    foreach ($objects as $object) {
		$printsection = "";
        if ($object->section !== $currentsection) {
            if ($object->section) {
                $printsection = $object->section;
            }
            if ($currentsection !== "") {
                $table->data[] = 'hr';
            }
            $currentsection = $object->section;
        }

		if ($course->format == "weeks" || $course->format == "topics") {
            $table->data[] = array ($printsection, "<a href=\"view.php?id=$object->coursemodule\">$object->name</a>", "$object->summary");
        } else {
            $table->data[] = array ("<a href=\"view.php?id=$object->coursemodule\">$object->name</a>", "$object->summary");
        }
    }

    echo "<br />";

    print_table($table);

    print_footer($course);
 
?>

