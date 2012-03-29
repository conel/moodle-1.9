<?php
class send_answer
{
    var $_frmCredentials;//holds the array sent from the gateway.
    
    /*The constructor function is passed one extra hidden parameter when
    *it is called from Flash. This is appended by the gateway 
    *and is an array containing the courseid, accessid, course module id
    *and the flash id.
    *It is the last
    *parameter passed to the constructor. 
    */
    function send_answer($lastparam)
    {
        $this->_frmCredentials=$lastparam;
        //detect if answers have already been submitted for this access id
        //protects against refresh page resulting in the test being taken again with the same access id.
        if (record_exists("flash_answers", "accessid", intval($lastparam['accessid'])))
        {
            die;
        };
    }
    
    /*This puts an answer into the db so it can be exported later
    *also stores an associated grade.
    *$answer is expected to be an array with elements 'time', 'answer' etc.
	*these correspond to the %code%s which will be replaced in the feedback
    */
    function answer($qNo, $answer, $grade)
	{
		$accessid=intval($this->_frmCredentials['accessid']);
		$courseid=$this->_frmCredentials['courseid'];
	    $cmid=$this->_frmCredentials['cmid'];
        $todb=new object;
        $todb->q_no=intval($qNo);
        $todb->accessid=$accessid;
        $todb->answer=addslashes (serialize($answer));
        $todb->grade=$grade;
        $answer_to_log=(is_array($answer->answer))?join($answer->answer, ', '):$answer->answer;
        add_to_log($courseid, "flash", "answer", "view.php?id=$cmid&access=$accessid", "$accessid", $cmid);
        insert_record("flash_answers", $todb);
    }        
}
?>