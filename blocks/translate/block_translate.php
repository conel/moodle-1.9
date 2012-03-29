<?php
	// Paul Holden 17th July, 2007
	// this file contains the code for generating the block contents

	class block_translate extends block_base {

		var $block_langs;

		function init() {
			$this->title = get_string('formaltitle', 'block_translate');
			$this->version = 2007071700;
		}

		function instance_allow_config() {
			return true;
		}

		function get_javascript() {
			global $CFG;
			return "<script language=\"javascript\" type=\"text/javascript\" defer=\"defer\">
				function do_translate(szLang, szText) {
				  var xmlHttpReq = false;
				  var self = this;
				  var output = document.getElementById('translate_output');
				  output.style.visibility = 'hidden';
				  output.innerHTML = '';
				  if (window.XMLHttpRequest) {
				    self.xmlHttpReq = new XMLHttpRequest();
				  } else if (window.ActiveXObject) {
				    self.xmlHttpReq = new ActiveXObject(\"Microsoft.XMLHTTP\");
				  }
				  var strURL = '$CFG->wwwroot/blocks/translate/ajax_translate.php?lang=' + escape(szLang) + '&text=' + escape(szText);
				  self.xmlHttpReq.open('GET', strURL, true);
				  self.xmlHttpReq.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
				  self.xmlHttpReq.onreadystatechange = function() {
				      if (self.xmlHttpReq.readyState == 4) {
					     var str = self.xmlHttpReq.responseText;
					     output.style.visibility = 'visible';
					     output.innerHTML = str;
				      }
				    }
				  self.xmlHttpReq.send(null);
				}
				function translate() {
				  var select = document.getElementById('translate_lang');
				  var lang = select.options[select.selectedIndex].value;
				  var text = document.getElementById('translate_input').value;
				  do_translate(lang, text);
				}
			  </script>";
		}

		function get_content() {
			include('block_translate_defs.php');
			if ($this->content !== null) {
				return $this->content;
			}

			$this->block_langs = (isset($this->config->langs) ? $this->config->langs : $langs);

			$this->content = new stdClass;
			$this->content->text = $this->get_javascript();
			$this->content->text .= '<table cellspacing="2" cellpadding="2">';
			$this->content->text .= '<tr><td><textarea cols="30" rows="3" id="translate_input"></textarea></td></tr>';
			$this->content->text .= '<tr><td><select id="translate_lang">';
			foreach ($this->block_langs as $lang) {
				$this->content->text .= "<option value=\"$lang\">" . str_replace($short, $long, $lang) . '</option>';
			}
			$this->content->text .= '</select></td></tr>';
			$this->content->text .= '<tr><td><input type="submit" value="' . get_string('translate', 'block_translate') . '" onclick="translate()" /></td></tr>';
			$this->content->text .= '</table>';
			$this->content->text .= '<div id="translate_output"></div>';

			$this->content->footer = '';
			return $this->content;

		}
	}
?>
