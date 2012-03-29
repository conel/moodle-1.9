<?php
	class Display
	{		
		function printDirectoryLink($text, $get_vars)
		{
			global $object_config;
			
			$return = $this->printLink($text, $get_vars, "{$object_config->pics}/folder_icon.gif");
			return $return;
		}
		
		function printMaterialLink($text, $get_vars)
		{
			global $object_config;
			
			$return = $this->printLink($text, $get_vars, "$object_config->pics/cubes.gif");
			return $return;
		}
		
		function printLink($text, $get_vars, $img)
		{
			$get_txt = '';
			foreach($get_vars as $key => $value)
			{
				$get_txt .= "&$key=$value";
			}
			$get_txt = ltrim($get_txt, '&');
			return "<img src='$img' width=16 height=16 class='icon' /> <a href=\"?$get_txt\">$text</a>";
		}
		
		function wrapLink($link_html)
		{
			return '<li>' . $link_html . '</li>';
		}
		
		function printItemLink($material_root, $item)
		{
			global $object_config;
			$cube = "<img src='$object_config->pics/cube.gif' width=16 height=16 class='icon' />";
			$return = '';
			$title = $item->title;
			
			if(isset($item->resourcehref)) {
				if (isset($_GET['current-material'])) {
					//echo $_GET['current-material'] ." ".urldecode($item->resourcehref);
					
					if (strpos(urldecode(strtr($_GET['current-material'], '\\', '/')), urldecode(strtr($item->resourcehref,'\\','/'))) !== false) {
						$cube = "<img src='$object_config->pics/active_cube.gif'width=16 height=16 class='icon active-icon' />";
					}
				}
			
				$url = "?object-display=true&current-material=$item->resourcehref";
				if (isset($_GET['id'])) $url .= "&id=$_GET[id]";
				if (isset($_GET['material-root'])) $url .= "&material-root={$_GET['material-root']}";
				if (isset($_GET['imsfile'])) $url .= "&imsfile={$_GET['imsfile']}";
	
				return "$cube <a href='$url'>$title</a>";
			} else {
				return "<img src='$object_config->pics/cubes.gif' width=16 height=16 class='icon' /> $title";
			}
		}
	
	};

?>