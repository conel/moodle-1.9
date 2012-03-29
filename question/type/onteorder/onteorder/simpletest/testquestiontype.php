<?php
/**
 * Unit tests for this question type.
 *
 * @copyright &copy; 2008 Micha� Zaborowski
 * @author michal.zaborowski@byd.pl
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package onte_questiontypes
 *//** */
    
require_once(dirname(__FILE__) . '/../../../../config.php');

global $CFG;
require_once($CFG->libdir . '/simpletestlib.php');
require_once($CFG->dirroot . '/question/type/onteorder/questiontype.php');

class onteorder_qtype_test extends UnitTestCase {
    var $qtype;
    
    function setUp() {
        $this->qtype = new onteorder_qtype();
    }
    
    function tearDown() {
        $this->qtype = null;    
    }

    function test_name() {
        $this->assertEqual($this->qtype->name(), 'onteorder');
    }
    
    // TODO write unit tests for the other methods of the question type class.
}

?>
