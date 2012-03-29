<?php
	function make_crumbtrail($fileroot)
	{
		$trail = array();
		$arr = split("/", $fileroot);
				
		$trail[0]['name'] = "Objects Index";
		$trail[0]['url'] = "";
		
		$dir_url = '';
		foreach($arr as $key => $dir) {
			if ($dir != '.') {
				$dir_url .= "/$dir";
				$trail[$key+1]['name'] = $dir;
				$trail[$key+1]['url'] = '/' . $dir_url;
				if ($trail[$key+1]['name'] == '' || $trail[$key+1]['url'] == '')
				{
					unset($trail[$key+1]);
				}				
			}
		}

		$trail_html = '';
		$current_item = array_pop($trail);
		
		foreach($trail as $key => $dir) {
			//if ($key != 0) $trail_html .= " &gt;&gt; ";
			$url = $dir['url'];
			$name = $dir['name'];

			$trail_html .= "<a href=\"?goto=.$url\">$name</a> » ";
		}
		$last_item = $current_item['name'];
		return array($trail_html, $last_item);
	}
?>