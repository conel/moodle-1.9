<?php  // $Id: lib.php,v 1.6.2.5 2008/06/20 20:54:37 agrabs Exp $
defined('FEEDBACK_INCLUDE_TEST') OR die('not allowed');
require_once($CFG->dirroot.'/mod/feedback/item/feedback_item_class.php');

define('FEEDBACK_TABLE_LINE_SEP', '|');

class feedback_item_table extends feedback_item_base {
    
    var $type = "table";
    
    function init() {

    }
    
    function &show_edit($item) {
        global $CFG;
        
        require_once('table_form.php');
        
        $item_form = new feedback_table_form();
        
        $item->presentation = empty($item->presentation) ? '' : $item->presentation;
        $item->name = empty($item->name) ? '' : htmlspecialchars(stripslashes_safe($item->name));
        
        $item->required = isset($item->required) ? $item->required : 0;
        if($item->required) {
            $item_form->requiredcheck->setValue(true);
        }

        $item_form->itemname->setValue($item->name);

        $sizeAndLength = explode('|',$item->presentation);
        $itemsize = isset($sizeAndLength[0]) ? $sizeAndLength[0] : 30;
        $itemlength = isset($sizeAndLength[1]) ? $sizeAndLength[1] : 5;
        $item_form->selectwith->setValue($itemsize);
        $item_form->selectheight->setValue($itemlength);
        
        return $item_form;
    }

    //liefert eine Struktur ->name, ->data = array(mit Antworten)
    function get_analysed($item, $groupid = false, $courseid = false, $where_clause = false) {
        $aVal = null;
        $aVal->data = null;
        $aVal->name = $item->name;
        //$values = get_records('feedback_value', 'item', $item->id);
        $values = feedback_get_group_values($item, $groupid, $courseid, $where_clause);
        if($values) {
            $data = array();
            foreach($values as $value) {
                $data[] = str_replace("\n", '<br />', $value->value);
            }
            $aVal->data = $data;
        }
        return $aVal;
    }

    function get_printval($item, $value) {
        
        if(!isset($value->value)) return '';
        return $value->value;
    }

    function print_analysed($item, $itemnr = '', $groupid = false, $courseid = false, $where_clause = false) {
        $values = feedback_get_group_values($item, $groupid, $courseid, $where_clause);
        if($values) {
            //echo '<table>';2
            // $itemnr++;
            echo '<tr><th colspan="2" align="left">'. $itemnr . '&nbsp;' . stripslashes_safe($item->name) .'</th></tr>';
            foreach($values as $value) {
                echo '<tr><td colspan="2" valign="top" align="left">-&nbsp;&nbsp;' . str_replace("\n", '<br />', $value->value) . '</td></tr>';
            }
            //echo '</table>';
        }
        // return $itemnr;
    }

    function excelprint_item(&$worksheet, $rowOffset, $item, $groupid, $courseid = false, $where_clause = false) {
        $analysed_item = $this->get_analysed($item, $groupid, $courseid, $where_clause);

        $worksheet->setFormat("<l><f><ro2><vo><c:green>");
        $worksheet->write_string($rowOffset, 0, stripslashes_safe($item->name));
        $data = $analysed_item->data;
        if(is_array($data)) {
            $worksheet->setFormat("<l><ro2><vo>");
            $worksheet->write_string($rowOffset, 1, $data[0]);
            $rowOffset++;
            for($i = 1; $i < sizeof($data); $i++) {
                $worksheet->setFormat("<l><vo>");
                $worksheet->write_string($rowOffset, 1, $data[$i]);
                $rowOffset++;
            }
        }
        $rowOffset++;
        return $rowOffset;
    }

    function print_item($item, $value = false, $readonly = false, $edit = false, $highlightrequire = false){
        
		if (is_array($value)) {
			$values = $value;
		}else {
			$values = explode(FEEDBACK_TABLE_LINE_SEP, $value);
		}
                           
        $align = get_string('thisdirection') == 'ltr' ? 'left' : 'right';
        
        $presentation = explode ("|", $item->presentation);
        
        //if($highlightrequire AND $item->required AND strval($value) == '') {
        if($highlightrequire && $item->required && (! $this->check_value($value, $item))) {
            $highlight = 'bgcolor="#FFAAAA" class="missingrequire"';
        }else {
            $highlight = '';
        }
        
        $requiredmark =  ($item->required == 1)?'<span class="feedback_required_mark">*</span>':'';
    ?>
        <td <?php echo $highlight; ?> valign="top" align="<?php echo $align;?>"><?php echo format_text(stripslashes_safe($item->name) . $requiredmark, true, false, false);?></td>
        
        <td valign="top" align="<?php echo $align;?>">
    
    <?php
        if($readonly) print_box_start('generalbox boxalign'.$align);
    ?>          
    
           <style>.titem table, .titem tr, .titem td {border:1px solid #000;text-align:center;} .titem td{width:200px;height:10px}</style>
           
           <table class="titem">
			   <tr>
				   <td>Area for Action Identified</td><td>Actions Taken</td><td>By Whom</td><td>Date</td><td>Impact of Action(s) Taken</td>
			   </tr>
			   <?php for($j=0;$j<5;$j++) { ?> 
				   <tr>				       			   
						<?php
							for($i=0;$i<5;$i++) {
				   
								if($readonly){
									?><td><?php echo isset($values[$i+($j*5)])?htmlspecialchars($values[$i+($j*5)]):'';?></td><?php								
								} else {
									?><td><input type="text" value="<?php echo isset($values[$i+($j*5)])?htmlspecialchars($values[$i+($j*5)]):'';?>" size="<?php echo $presentation[0];?>" maxlength="<?php echo $presentation[1];?>" name="<?php echo $item->typ . '_' . $item->id;?>[]" /></td><?php
								}
							}
						?>  			   		   					
				   </tr>
				<?php } ?>
		   </table>
    <?php
        if($readonly) print_box_end();
    ?>  
        
        </td>
    <?php
    }

    function check_value($value, $item) {

        if($item->required != 1) return true;

		foreach($value as $val) {
			if($val != '') return true;
		}
			
        return false;
    }

    function create_value($data) {
        
        //$data = addslashes(clean_text($data));
        //return $data;
        
		$vallist = $data;
        return trim($this->item_arrayToString($vallist));        
    }

    function item_arrayToString($value) {
		
        if(!is_array($value)) {
            return $value;
        }
        
        $retval = '';
        
        $arrvals = array_values($value);
        //$arrvals = clean_param($arrvals, PARAM_ALPHANUM);  //prevent sql-injection
        
        $retval = $arrvals[0];
        
        for($i = 1; $i < sizeof($arrvals); $i++) {
            //$retval .= FEEDBACK_TABLE_LINE_SEP.$arrvals[$i];
            $retval .= FEEDBACK_TABLE_LINE_SEP.addslashes(clean_text($arrvals[$i]));  //prevent sql-injection
        }
        return $retval;
    }
    
    function get_presentation($data) {
        return $data->itemsize . '|'. $data->itemmaxlength;
    }

    function get_hasvalue() {
        return 1;
    }
}
?>
