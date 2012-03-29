<?php
/*
 **************************************************************************
 *                                                                        *
 *                                                                        *
 *                            THIS SCRIPT                                 *
 *                                and                                     *
 *                         www.bydistance.com                             *
 *                 brought to you by Visions Encoded Inc.                 *
 *                                                                        *
 *                                                                        *
 *                                                                        *
 *             Visit us online at http://visionsencoded.com/              *
 *                You Bring The Vision, We Make It Happen                 *
 **************************************************************************
 **************************************************************************
 * NOTICE OF COPYRIGHT                                                    *
 *                                                                        *
 * Copyright (C) 2009                                                     *
 *                                                                        *
 * This program is free software; you can redistribute it and/or modify   *
 * it under the terms of the GNU General Public License as published by   *
 * the Free Software Foundation; either version 2 of the License, or      *
 * (at your option) any later version.                                    *
 *                                                                        *
 * This program is distributed in the hope that it will be useful,        *
 * but WITHOUT ANY WARRANTY; without even the implied warranty of         *
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the          *
 * GNU General Public License for more details:                           *
 *                                                                        *
 *                  http://www.gnu.org/copyleft/gpl.html                  *
 *                                                                        *
 *                                                                        *
 *                                                                        *
 **************************************************************************
 */

//prevent direct access to this script for security purposes
if(strpos($_SERVER['REQUEST_URI'], basename(__FILE__))) {
    header("HTTP/1.0 404 Not Found");
    die();
}

require_once('config.php');//mrcutejr config includes moodle config.php
require_once('Crypt_Xtea.php');

/**
 * Provides means of securing a mrcutejr resource such that a file type resource will only be returned if the request
 * for the mrcutejr shared file type resource is legitimately called from a course resource link in a course (via the
 * mod/resource/type/mrcutejr/resource.class.php->display() function).  This class completely depends on the user being
 * logged in and so it calls require_login() in the constructor; you should only instantiate this class when a user is
 * already logged in.  The way this class works is that the display() function in mrcutejr/resource.class.php generates
 * a type of "token" specific to the logged in user+course+rid which can then be used to retrieve the file resource.
 *
 * This class/script requires PEAR.php and Xtea.php.  I've included xtea in this project under the filename of
 * Crypt_Xtea.php (I renamed the file for consistancy - typically class files should have the same name as the class
 * they contain).
 *
 * @author Leo Thiessen from www.bydistance.com
 */
class mrcutejr_auth {
    
    /**
     * Our re-useable crypt object based on PEAR Xtea.php
     * @var crypt_key object
     */
    var $crypt = null;

    /**
     * This is used as the key for the encryption/decryption.  This value is modified by the constructor, however you
     * can change it here to make the key more unique to your particular moodle install, if you would like.
     * @var string
     */
    var $crypt_key = 'mrjr';//value modified in constructor - 

    /**
     * Used to make encrypted string url safe while working around a flash flashvars bug - see comments inside the
     * encrypt() function code for details.  This contains 2 arrays: ['base64char'] is an array of chars to replace,
     * while ['urlsafe'] are the replacement values.  I can't forsee the need to change values, however should it be
     * necessary, please do so carefully; understand the encrypt() and decrypt() functions & read up on base64 first.
     * @var array of arrays
     */
    var $urlsafe_base64_char_map = array(
        "base64char" => array( '+',  '/',  '=', '*'),
        "urlsafe"    => array('_p', '_s', '_e', '~')
    );
    
    /**
     * Constructor.
     * Initializes mrcutejr_auth object; note that this will call require_login().
     */
    function mrcutejr_auth($course_id, $resource_id) {
        global $USER;
        require_login();
        $this->crypt = new Crypt_Xtea();//re-usable
        //make the key unique to this script, the course id, the username and course-specific resource id
        $this->crypt_key = md5($this->crypt_key.$course_id.$USER->username.$resource_id);
    }

    /**
     * Generates a user-specific "token" which can be decrypted via mrcutejr_auth->decrypt().
     * @param string $realreference a mrcutejr "real reference" (from the db table)
     * @return string user-specific encrypted version of the passed in string
     */
    function encrypt($realreference) {
        global $USER;
        //1. use user info as a kind of "salt"
        $str = $USER->id.$USER->username.$realreference.(intval($USER->id)*2);
        //2. encrypt
        $encryptedstring = $this->crypt->encrypt(strrev($str), $this->crypt_key);
        //3. make safe to use in url - flash has flashvar probs with %, so we make urlsafe differently...
        $base64str = base64_encode($encryptedstring);
        //4. base64 is almost urlsafe but not quite, so we replace the remaining "unsafe" characters
        $urlstring = str_replace(
            $this->urlsafe_base64_char_map['base64char'],
            $this->urlsafe_base64_char_map['urlsafe'],
            $base64str
        );
        return $urlstring;
    }

    /**
     * Decrypts a string encrypted by mrcutejr_auth->encrypt(); note that encrypted strings are user-specific
     * @param string $encryptedrealreference - the encrypted string
     * @return string decrypted string upon succesfull decryption, else false.
     */
    function decrypt($encryptedrealreference) {
        global $USER;
        //4. re-insert base64 characters, see encrypt() comments
        $base64str = str_replace(
            $this->urlsafe_base64_char_map['urlsafe'],
            $this->urlsafe_base64_char_map['base64char'],
            $encryptedrealreference//aka $urlstring from encrypt()
        );
        //3. reverse our "url encoding"
        $encryptedstring = base64_decode($base64str);
        //2. decrypt
        $decryptedstring = strrev($this->crypt->decrypt($encryptedstring, $this->crypt_key));
        //1. parse (extract) the relevant portion of the string
        if(strpos($decryptedstring,$USER->id.$USER->username)===0) {
            $str = str_replace($USER->id.$USER->username,'',$decryptedstring);
            $useriddoubled = "".(intval($USER->id)*2);
            $idposition = strlen($str) - strlen($useriddoubled);
            if(strrpos($str, $useriddoubled) === $idposition) {
                return substr($str, 0, $idposition);
            }
        }
        return false;//failed parsing
    }

}//END CLASS
?>
