<?php
	include_once("config.php");
	
	class IMSNavBuilder {
		var $stack = array();
		var $root;
		var $resources = array();
		var $char_data = '';
		var $lang_string = '';
		var $xml;
		var $title = '';
		var $description = '';
		var $needs_nav = true;
		var $in_general = false;
		
		var $printItemLink = '';
		var $printItemClass = '';
		
		function IMSNavBuilder()
		{	
			$this->xml = xml_parser_create();
			xml_set_element_handler($this->xml, "start_tag", "end_tag");
			xml_set_character_data_handler($this->xml, "char_data");
			xml_set_object($this->xml, $this);
		}
		
		function buildNav($file)		//filesystem root of imsmanifest.xml
		{
						
			unset($stack);
			$stack = array();
			$xml = $this->decode_utf16(file_get_contents($file));
					
			//die($file . '-----------' . $xml);
			/*print_r($xml); die();*/
			//$xml = strtr($xml, /*array_flip(get_html_translation_table(HTML_SPECIALCHARS))*/ array("&#39;"=>""));
			xml_parse($this->xml, $xml);
			xml_parser_free($this->xml);
			$this->root =& $this->stack[0];
			unset($this->stack);
			$stack = array('');
			
			if ($this->title == '') {
				$this->title = $this->root->children[$this->root->default]->title;
			}
			
			//echo '<pre>';
			//print_r($this->stack);
			//echo '</pre>';
			// prevent fatal error in testing
			//if (!$this->root) return;

			$this->root->linkResources($this->resources);

			reset($this->root->children[$this->root->default]->children);
			list($key, $value) = each($this->root->children[$this->root->default]->children);
			
			if((count($this->root->children[$this->root->default]->children) == 1) && (isset($value->resourcehref))) 
			{
				$this->needs_nav = false;
			}
			
			return $this->root;
		}
					
		function printNav($material_root)	// web root of the object materials.
		{	
			global $object_config, $object_display;
			$return = '';
			
			//if(array_key_exists('VLE_NAVIGATED', $this->root->children)) $organization =& $this->root->children['VLE_NAVIGATED'];
			//else 
			$organization =& $this->root->children[$this->root->default];
			
			$title = $this->title;
			$return .= "<ul>";
			$return .= "<li><img src='{$object_config->pics}/cubes.gif' class='icon' /> $title";
			$return .= "<ul>";
			foreach($organization->children as $child_item)
			{
				$title = $child_item->title;
				$return .= "<li>";
				$return .= $object_display->printItemLink($material_root, $child_item);
				$return .= $child_item->printItem($material_root);
				$return .= "</li>";		
			}
			$return .= "</ul></ul>";		
			return $return;
		}
		
		function get_startpoint()
		{
			$return = reset($this->root->children[$this->root->default]->children);
			while (!isset($return->resourcehref) && isset($return))
			{
				$return = reset($return->$children);
			}
			
			return $return->resourcehref;
		}
		
		function decode_utf16($str) {
            $c0 = ord($str[0]);
            $c1 = ord($str[1]);
    
            if ($c0 == 0xFF && $c1 == 0xFE) {
                $be = false;
            } else if ($c0 == 0xFE && $c1 == 0xFF) {
                $be = true;
            } else {
                return $str;
            }
    
            $str = substr($str, 2);
            $len = strlen($str);
            $dec = '';
            for ($i = 0; $i < $len; $i += 2) {
                $c = ($be) ? ord($str[$i]) << 8 | ord($str[$i + 1]) : ord($str[$i + 1]) << 8 | ord($str[$i]);
                if ($c >= 0x0001 && $c <= 0x007F) {
                    $dec .= chr($c);
                } else if ($c > 0x07FF) {
                    $dec .= chr(0xE0 | (($c >> 12) & 0x0F));
                    $dec .= chr(0x80 | (($c >>  6) & 0x3F));
                    $dec .= chr(0x80 | (($c >>  0) & 0x3F));
                } else {
                    $dec .= chr(0xC0 | (($c >>  6) & 0x1F));
                    $dec .= chr(0x80 | (($c >>  0) & 0x3F));
                }
            }
            return $dec;
        }

		function start_tag($parser, $name, $attr)
		{
			
			//echo "<p>Start Tag: $name, The stack contains:</p><pre>";
			//print_r($this->stack);
			//echo '</pre>';
			
			
			switch($name)
			{
				case "ORGANIZATIONS":
					$this->stack[0] = new Organizations;
					if(isset($attr['DEFAULT'])) $this->stack[0]->default = $attr['DEFAULT'];
					
					
					
					
					break;
					
				case "ORGANIZATION":
					$newnode = new Organization;
					if(isset($attr['IDENTIFIER'])) $newnode->identifier = $attr['IDENTIFIER'];
					if(isset($attr['STRUCTURE'])) $newnode->structure = $attr['STRUCTURE'];
					
					//echo '<p>ORGANIZATION</p><pre>';
					//print_r($this->stack);
					//echo '</pre>';
					
					$parent =& $this->stack[count($this->stack)-1];

					$parent->addChild($newnode);
					$this->stack[count($this->stack)] =& $newnode;

					//print_r(end($stack));
					break;
					
				case "ITEM":
					$newnode = new Item;
					if(isset($attr['IDENTIFIER'])) $newnode->identifier = $attr['IDENTIFIER'];
					if(isset($attr['IDENTIFIERREF'])) $newnode->identifierref = $attr['IDENTIFIERREF'];
					if(isset($attr['ISVISIBLE'])) $newnode->isvisible = $attr['ISVISIBLE'];
					
					$parent =& $this->stack[count($this->stack)-1];
					$parent->addChild($newnode);
					$this->stack[count($this->stack)] =& $newnode;
					//echo '<p>ITEM</p><pre>';
					//print_r($this->stack);
					//echo '</pre>';
					break;
					
				case "RESOURCE":
					if(isset($attr['IDENTIFIER']) && isset($attr['HREF']))
					{
						$this->resources[$attr['IDENTIFIER']] = strtr($attr['HREF'],'\\','/');
					}
					break;
					
				case "IMSMD:GENERAL":
					$this->in_general = true;
					break;
					
				default:
					break;
			}
		}
		
		function char_data($parser, $data)
		{
			$this->char_data = $data;
		}
		
		function end_tag($parser, $name)
		{	
						
			switch($name)
			{
				case "ORGANIZATIONS":
					break;
					
				case "ORGANIZATION":
				case "ITEM":
					array_pop($this->stack);
					break;
					
				case "TITLE":
				  $index = count($this->stack)-1; //if there are no items in the stack make sure not to create a negative index (for CourseGenie packages)
				  if ($index < 0) $index = 0;
					$this->stack[$index]->title = $this->char_data;
					break;
					
				case "IMSMD:LANGSTRING":
					$this->lang_string = $this->char_data;
					break;
					
				case "IMSMD:TITLE":
					if ($this->in_general) $this->title = $this->lang_string;
					break;
					
				case "IMSMD:DESCRIPTION":
					if ($this->in_general) $this->description = $this->lang_string;
					break;
					
				case "IMSMD:GENERAL":
					$this->in_general = false;
					break;
					
				default: break;
			}
		}	
		
	}
	
	class IMSNode {
		var $first_child = 0;
		var $children = array();
		var $number = 0;
	
		function addChild(&$node)
		{
			if(isset($node->identifier))
			{
				$this->children[$node->identifier] =& $node;
			}
			else
			{
				$this->children[$this->number] =& $node;
				$this->number++;
			}
			if ($this->children == array()) $first_child =& $node;
		}
		
		
		function linkResources($resources)
		{
			if(isset($this->identifierref))
			{
				if(array_key_exists($this->identifierref, $resources))
				{
					$this->resourcehref = strtr($resources[$this->identifierref],'\\','/');
				}
			}

			reset($this->children);
			while(list($key) = each($this->children))
			{
				$child =& $this->children[$key];
				$child->linkResources($resources);
			}
		}
	}
	
	class Organizations extends IMSNode
	{
		var $default;
	}
	
	class Organization extends IMSNode
	{
		var $identifier;
		var $structure;
		var $title;
	}

	class Item extends IMSNode
	{
		var $identifier;
		var $identifierref;
		var $isvisible;
		var $title;
		var $resourcehref;
		
		function printItem($material_root)
		{
			global $object_display;
			
			$ret = "<ul>";
			foreach($this->children as $child_item)
			{
				$title = $child_item->title;
				$ret .= "<li>";
				$ret .= $object_display->printItemLink($material_root, $child_item);
				$ret .= $child_item->printItem($material_root);
				$ret .= "</li>";		
			}
			$ret .= "</ul>";
			return $ret;
		}
	}
?>