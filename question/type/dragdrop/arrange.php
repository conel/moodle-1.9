<?php  // $Id: arrange.php,v 1.5 2008/07/05 10:58:54 jmvedrine Exp $
// arrange.php  brian@mediagonal.ch / free.as.in.speech@gmail.com

require_once("../../../config.php");
require_once("$CFG->dirroot/lib/questionlib.php");
require_once("$CFG->dirroot/question/type/dragdrop/dragdrop.php");
require_once("$CFG->dirroot/question/editlib.php");

$id = required_param('id', PARAM_INT);  // question id
$courseid = optional_param('courseid', 0, PARAM_INT);
$cmid = optional_param('cmid', 0, PARAM_INT);
$returnurl = optional_param('returnurl', 0, PARAM_LOCALURL);
$process = optional_param('process', '', PARAM_ALPHA);
                                // savereturn:save & return to question editing,
                                // savecontinue: save and continue back to overview,
                                // cancel: return to question editing without saving
if ($cmid){
    list($module, $cm) = get_module_from_cmid($cmid);
    require_login($cm->course, false, $cm);
    if (!$returnurl) {
        $returnurl = "{$CFG->wwwroot}/question/edit.php?cmid={$cm->id}";
    }
} elseif ($courseid) {
    require_login($courseid, false);
    if (!$returnurl) {
        $returnurl = "{$CFG->wwwroot}/question/edit.php?courseid={$COURSE->id}";
    }
    $cm = null;
} else {
    print_error('needcmidorcourseid', 'qtype_dragdrop');
}

// Validate the question id
if (!$question = get_record('question', 'id', $id)) {
    print_error('questiondoesnotexist', 'question', $returnurl);
}
get_question_options($question);

if(!question_has_capability_on($question, 'edit')) {
    print_error('noeditingright', 'qtype_dragdrop');
}

$dd = new dragdrop($CFG, $id, $courseid, $cmid, $returnurl);

if ($process) {
    $dd->process($process);
} else {
    $dd->edit_positions();
}
?>