<?php
global $CFG;
require("$CFG->dirroot/mod/flash/movies/send_answer.php");
class service extends send_answer
{
    var $config;
    var $q_no; //No. of questions
    var $_answer;
    var $_frmCredentials;
    
    function service($toPass)
    {
        parent::send_answer($toPass);//default constructor

        $flash = get_record("flash", "id", intval($toPass['flashid']));
        $flash->answers=unserialize($flash->answers);
        $this->config=unserialize($flash->config);
        $this->q_no=$flash->q_no;
        for ($h=0; $h < (count ($this->config)); $h++)
        {
            //process question data in config array before sending it to Flash
            if (isset($this->config[$h]))//non numeric keys won't be iterated through.
            {
                if ($this->config[$h]['q_type']=='Text Selection')
                {
                    $this->_answer[$h]=$flash->answers[$h];
                } else
                {
                    $this->_answer[$h]=$this->config[$h]['text'];
                    if ($this->config[$h]['seperator']=='Character')
                    { 
                        $textArray=array();
                        for ($i=0; $i < strlen($this->config[$h]['text']); $i++)
                        {
                            $textArray[$i]=$this->config[$h]['text']{$i};
                        }
                        $this->config[$h]['text']=$textArray;
                    } elseif ($this->config[$h]['seperator']=='Multi Byte Character')
                    { 
                        $this->config[$h]['text']=$this->_utf_string_to_array_of_chars($this->config[$h]['text']);
                    } else
                    {
                        $this->config[$h]['text']=explode ($this->config[$h]['seperator'], $this->config[$h]['text']);
                    }
                    srand($this->_make_seed()); 
                    shuffle($this->config[$h]['text']); 
                }
            }
        }
    }
    function _utf_string_to_array_of_chars($utfstring)
    {
        $textArray=array();
        for ($i=0; $i < strlen($utfstring); )
        {
            if (ord($utfstring{$i}) < 0x7f) {
                $textArray[$i]=$utfstring{$i};
                $i++;
            } elseif (!(ord($utfstring{$i}) & 0x20)) //2 byte char
            {
                $textArray[$i]=$utfstring{$i}.
                                $utfstring{$i+1};
                $i=$i+2;
            }elseif (!(ord($utfstring{$i}) & 0x10)) //3 byte char
            {
                $textArray[$i]=$utfstring{$i}.
                                $utfstring{$i+1}.
                                $utfstring{$i+2};
                $i=$i+3;
            }elseif (!(ord($utfstring{$i}) & 0x08)) //4 byte char
            {
                $textArray[$i]=$utfstring{$i}.
                                $utfstring{$i+1}.
                                $utfstring{$i+2}.
                                $utfstring{$i+3};
                $i=$i+4;
            }
        }
        return $textArray;
    }
    function _make_seed() { 
       list($usec, $sec) = explode(' ', microtime()); 
       return (float) $sec + ((float) $usec * 100000); 
    } 
    function answer($qNo, $answer)
    {
        if ($this->config[$qNo]['q_type']=='Text Selection')
        {
            $answer->wrong=$answer->right=$answer->ignored=0;
            foreach (($answer->answer) as $wordNo)
            {
                switch ($this->_answer[$qNo][$wordNo]) {
                case '-1':
                    $answer->wrong++;
                    break;
                case '1':
                    $answer->right++;
                    break;
                default :
                case '0':
                    $answer->ignored++;
                    break;
                };
                    
                    
            }
            $countRWI=array_count_values($this->_answer[$qNo]);
            $totalCorrect=$countRWI[1]; // no of 1's - (should be selected) in $this->_answer[$qNo] 
            $answer->selected=$answer->answer;
            $answer->answer="$answer->right correctly selected".
                        (($answer->wrong)?" - $answer->wrong wrongly selected":'')." / $totalCorrect should have been selected".
                        (($answer->ignored)?" - ($answer->ignored unnescesarily selected).":".");
            $grade=($answer->right - $answer->wrong >0) //if more wrongly selected than right give 0 grade, not negative grade
                            ? round(((($answer->right - $answer->wrong) / $totalCorrect) * 100),2)
                            : 0;
            $message="Found : $answer->right / $totalCorrect. ".(($answer->wrong)?"\nWrong : $answer->wrong.":'')."\nGrade : $grade %";
            $correct=($todb->grade==100);
        } else
        {
            $correct=($answer->answer==$this->_answer[$qNo]);
            $grade=($correct)?100:0;
            $message='';
        }
        parent::answer($qNo+1, $answer, $grade);
        return (array($correct,$message));
    }
} 
    
?>
