<?php
	// Paul Holden 17th July, 2007
	// this file contains definitions of the languages that can be translated

	$abbrev = array('|'  => ' ' . get_string('config_langto', 'block_translate') . ' ',
			'en' => 'English',
			'fr' => 'Français',
			'de' => 'Deutsch',
			'it' => 'Italiano',
			'es' => 'Español',
			'pt' => 'Portuguese');

	$short = array_keys($abbrev);
	$long = array_values($abbrev);

	$langs = array('en|de', 'en|fr', 'en|it', 'en|es', 'en|pt', 'de|en', 'fr|en', 'it|en', 'es|en', 'pt|en');

?>
