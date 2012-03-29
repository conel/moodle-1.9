<?php
	// Paul Holden 17th July, 2007
	// this file is called from the block using AJAX techniques
	
	include_once('../../config.php');
	
	function translate($langpair, $text) {
		$ch = curl_init(sprintf('http://google.com/translate_t?langpair=%s&text=%s', urlencode($langpair), urlencode($text)));
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($ch, CURLOPT_TIMEOUT, 15);
		curl_setopt($ch, CURLOPT_PROXY, PROXY_SERVER);
		curl_setopt($ch, CURLOPT_PROXYPORT, PROXY_PORT);
		curl_setopt($ch, CURLOPT_PROXYUSERPWD, PROXY_USERNAME .":". PROXY_PASSWORD);
		$html = curl_exec($ch);
		// uncomment this to see the html curl is returning (Google translate result)
		//var_dump($html);

		curl_close($ch);
	
		// nkowald - 2010-04-28 (Nein Nein Nein!) - for some reason pattern does not match with double quotes around result_box id. I'm hungry.
		$pattern = '/<span id=result_box class="short_text">(.*?)<\/span>/s';
		preg_match($pattern, $html, $out);
		return utf8_encode($out[1]);
	}

	$lang = $_GET['lang'];
	$text = $_GET['text'];

	echo translate($lang, $text);
?>
