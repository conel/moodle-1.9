<?php
class Credentials {
    
    var $movieFlashSess; //reference pointing to a sub array of $_SESSION used for storing Flash stuff
                        // for this Movie
    var $mustUse; //service that must be used by this phpobject
    var $toPass; //object that will be passed to the phpObject constructor function
    var $gateway; // the gateway object
    function Credentials(&$parent){
        $this->gateway=&$parent;
    }
    function validate($credentials){
    //check to see if a session with key $credentials has been started
		global $USER;
        if (!array_key_exists('flashSess', $_SESSION)||
            !array_key_exists($credentials[0], $_SESSION['flashSess']))    {
            return false;
        } else  {
            $this->movieFlashSess= $credentials[0];
            $this->mustUse=$_SESSION['flashSess'][$this->movieFlashSess]['mustuse'];
            $this->toPass['flashid']=$_SESSION['flashSess'][$this->movieFlashSess]['flashid'];
            $this->toPass['cmid']=$_SESSION['flashSess'][$this->movieFlashSess]['cmid'];
            $this->toPass['courseid']=$_SESSION['flashSess'][$this->movieFlashSess]['courseid'];
            $this->toPass['accessid']=$_SESSION['flashSess'][$this->movieFlashSess]['accessid'];
            $this->service=$_SESSION['flashSess'][$this->movieFlashSess]['service'];
            $_SESSION['flashSess'][$this->movieFlashSess]['lastaccess']=time();
            return true;
        }
    }
    function get_saved_info($obj_key, $prop_name)
    {
        //$this->gateway->logger->log_event('get_saved_info'); //example of using log from within Credentials
        $return=array();
        if (!($return['error']=!isset($_SESSION['flashSess'][$this->movieFlashSess][$obj_key]->$prop_name))){
            $return['value']=$_SESSION['flashSess'][$this->movieFlashSess][$obj_key]->$prop_name;
        } else {
            error_log('Failed to retrieve session info.',0);
        }
        return $return;
    }
    function save_info($obj_key, $prop_name, $value)
    {
        $_SESSION['flashSess'][$this->movieFlashSess][$obj_key]->$prop_name=$value;
    }
    function new_obj_sess_token()
    {
        if (@is_array($_SESSION['flashSess']) 
                && @array_key_exists($this->movieFlashSess ,$_SESSION['flashSess'])){
            do{//repeat until we have a new key, just to be absolutely! sure we get a new key 
                    $obj_sess_token = md5(uniqid(rand(), true)); 
            } while (array_key_exists($obj_sess_token ,$_SESSION['flashSess'][$this->movieFlashSess]));
            $_SESSION['flashSess'][$this->movieFlashSess][$obj_sess_token]= new object;
            return $obj_sess_token;
        } else {
            return false;
        }
        

    }
    function delete_movie_sess()
    {
        if (@isset($_SESSION['flashSess'][$this->movieFlashSess]))
        {
            unset($_SESSION['flashSess'][$this->movieFlashSess]);
        }
    }
}

?>