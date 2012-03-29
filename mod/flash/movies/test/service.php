<?php

    class service
    {
        var $num=100;
        var $story = "This text is 'inherited' from the remote object. You don't see this text declared in the test.fla file, right?";

        function service() {
            $this->init();
        }
        
        function init() {
        } 

        function increase($i) {
            $this->num = $this->num + $i;
            echo "INCREASE BY $i: ".$this->num."\n";
            return $this->num;
        }

        function reduce($i) {
            $this->num = $this->num - $i;
            echo "REDUCE BY $i: ".$this->num."\n";
            return $this->num ;
        }

        function multiply($i) {
            $this->num=$this->num * $i;
            echo "MULTIPLY BY $i: ".$this->num."\n";
            return $this->num;
        }

        function divide($i) {
            $this->num=$this->num / $i;
            echo "DIVIDE BY $i: ".$this->num."\n";
            return $this->num;
        }

        function justDoIt($i,$j) {
            $this->num-= $i;
            echo "REDUCE BY $i: ".$this->num."\n";
            $this->num *= $j;
            echo "MULTIPLY BY $j: ".$this->num."\n";
            return $this->num;
        }
        
    } 
        
?>