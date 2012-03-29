<?php
/**
 * This function sends a response to the FAM tables and adds a message to the log to say the user
 * has answered a question. It also stores an associated grade.
 * $answer is expected to be an array with elements 'time', 'answer' etc.
 * these correspond to the %code%s which will be replaced in the feedback.
 * The Flash Activity Module framework takes care of reporting grades to the Moodle
 * gradebook from the FAM tables.
 *
 * @param integer $accessid
 * @param integer $courseid
 * @param integer $cmid
 * @param integer $qno
 * @param array $answer
 * @param integer $grade between 0 and 100
 */
function flash_save_response($accessid, $courseid, $cmid, $qno, $answer, $grade)	{
	$todb=new object;
    $todb->q_no=intval($qno);
    $todb->accessid=$accessid;
    $todb->answer=addslashes (serialize($answer));
    $todb->grade=$grade;
    insert_record("flash_answers", $todb);
    //log it
    $answer_to_log=(is_array($answer->answer))?join($answer->answer, ', '):$answer->answer;
    add_to_log($courseid, "flash", "answer", "view.php?id=$cmid&access=$accessid", "$accessid", $cmid);
}
/**
 * Fetches the last answer from the db for a particular Flash activity, for this user.
 *
 * @param string $flashid id of the particular Flash activity
 * @param integer $course course in which the activity is
 * @return array the array sent from the movie
 */
function flash_get_last_response($flashid){
    global $CFG, $USER;
    $sql="SELECT ans.answer, acc.timemodified as time FROM {$CFG->prefix}flash as flash,".
            " {$CFG->prefix}flash_answers as ans, {$CFG->prefix}flash_accesses as acc ".
            "WHERE acc.flashid='$flashid' AND  ans.accessid=acc.id ".
            "AND acc.userid='{$USER->id}' ".
            "ORDER BY time DESC";
    $ansfromdb=get_record_sql($sql);
    return unserialize($ansfromdb->answer);
}
?>