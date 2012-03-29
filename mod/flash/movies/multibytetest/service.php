<?php

    class service
    {
        var $story;
        var $_hello="hello";

        function service($id,$name)
        {
            $storyList    = Array();
            $storyList[0]    = "何ですか？私のPHPObjectです！";
            $storyList[1]    = "Ho ho ho! Merry Christmas!";
            $storyList[2]    = "And a Happy New Year!!!";
            $this->story    = $name . " " . $storyList[(isset($id) ? $id : 0)];
        }

        function getTranslatedDay($in)
        {
            if ($in == "日")
            {
                return "日 is Sunday";
            }
            elseif ($in == "月")
            {
                return "月 is Monday";
            }
            elseif ($in == "火")
            {
                return "火 is Tuesday";
            }
            elseif ($in == "水")
            {
                return "水 is Wednesday";
            }
            elseif ($in == "木")
            {
                return "木 is Thursday";
            }
            elseif ($in == "金")
            {
                return "金 is Friday";
            }
            elseif ($in == "土")
            {
                return "土 is Saturday";
            }
        }
    } 
        
?>