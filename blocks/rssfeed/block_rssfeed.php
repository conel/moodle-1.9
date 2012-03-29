<?php

/// Dieser Block zeigt die neuesten Dateien und Aktivit�ten aller art seit dem letzten Login
/// und darüber hinaus an. Außerdem wird ein RSS Feed (../../rss.php) zur Verfügung gestellt.

class block_rssfeed extends block_base {

	function init() {
		$this->title = get_string("title","block_rssfeed","javascript:rss_info();");
		$this->version = 2008070100;
	}

	function instance_allow_config() {
    	return true;
	}	

	function get_content() {
		/// We are going to measure execution times
        #$starttime =  microtime();

		global $USER;
		global $COURSE;
		global $CFG;
		global $max_items;
		
		$max_items=10; // Max items per course to show

		if ($this->content !== NULL) {
			return $this->content;
		}

		$this->content = new stdClass;

		if($USER->id == 0) {
			// User nicht eingeloggt -> Fehlermeldung & Abbruch
			$this->content->text = get_string("login","block_rssfeed");
			return $this->content;
		}
		$this->content->text = "
		<script language=\"JavaScript\">
		function rss_info() {
			alert('".get_string('info','block_rssfeed')."');
		}
		</script>
		";
		#$this->content->text .= get_string("header","block_rssfeed");
		$this->content->footer = '
		<tr><td colspan=2 height=20></td></tr>
		<tr>
			<td><img src=\''.$CFG->wwwroot.'/theme/'.$CFG->theme.'/pix/i/rss.gif\' /></td>
			<td><a href="'.$CFG->wwwroot.'/rss.php?u=' . $USER->id . '">'.get_string("get_feed","block_rssfeed").'</a></td>
		</tr>
		</table>
		';

		$c = $this->get_recent_changes($USER->id);
	
		$this->content->text .= "
		<table>
		";
		foreach($c as $key => $kurse) {
			$this->content->text .= "
			<tr>
				<td>
					<img src='$CFG->pixpath/i/group.gif' />
				</td>
				<td>
					<a href='".$CFG->wwwroot."/course/view.php?id={$kurse[0][4]}'>" . $kurse[0][3] . "</a>
				</td>
			</tr>";
			$i=0;
			foreach($kurse as $file) {
				if($i > $max_items) { break; }
				$i++;
				//Dateityp checken
				if(isset($file[7])) { // Es handelt sich um eine Datei, die aus einem freigegebenen Ordner eingelesen wurde.
					// checke ob es ein Icon f�r diesen Dateityp gibt.
					if(file_exists($CFG->dirroot."/pix/f/".$file[7].".gif")) {
						$icon=$CFG->pixpath."/f/".$file[7].".gif";
					} else {
						$icon=$CFG->pixpath."/f/unknown.gif";
					}
				} else { // Es handelt sich um eine Aktivit�t oder um eine direkt verlinkte Datei. Nehme das entsprechende Mod-Icon
					$icon=$CFG->pixpath."/mod/".$file[6]."/icon.gif";
				}

				if($file[5] == "new") { // Datei ist nach dem letzten Login hinzugef�gt worden -> Fettschrift
					$this->content->text .= "
					<tr>
						<td width=20></td>
						<td>
							<table>
								<tr>
									<td><img src='$icon' /></td>
									<td><a href=\"".$file[1]."\" title=\"" . $file[0] . "\" style='font-weight:bold;'>" . $this->fit_string($file[0], 20) . "</a></td>
								</tr>
							</table>
						</td>
					</tr>
					";
				} else {
					$this->content->text .= "
					<tr>
						<td width=20></td>
						<td>
							<table>
								<tr>
									<td><img src='$icon' /></td>
									<td><a href=\"".$file[1]."\" title=\"" . $file[0] . "\">" . $this->fit_string($file[0], 20) . "</a></td>
								</tr>
							</table>
						</td>
					</tr>					
					";
				}
			}
		}
		#$this->content->text .= "</table><br />";
		/// Show times
        #$this->content->text .= "Exec time: ".microtime_diff($starttime, microtime())."secs";
		
		// Block ausgeben
		return $this->content;
	}

	// Funktion um die Dateinamen gek�rzt darzustellen
	function fit_string($string, $length) {

		if(strlen($string) <= $length) {
			return $string;
		}

		$str1 = substr($string, 0, $length-8);
		$str2 = substr($string, -5);

		return $str1 . "..." . $str2;
	}

	// sucht die für den eingeloggten user relevanten KurseIDs heraus
	function get_recent_changes($userid) {

		global $COURSE;

		$courses = get_records_sql("
		SELECT
		c.instanceid AS id, crs.fullname AS name
		FROM
		mdl_context AS c
		JOIN
		mdl_role_assignments AS r
		ON
		r.contextid = c.id
		JOIN
		mdl_course AS crs
		ON
		crs.id = c.instanceid
		WHERE
		r.userid = {$userid}
		AND
		c.contextlevel = '50'");

		// Show only the acutal course if the user visits the course page
		// Show all courses if the user visits the main page
		// ID(1) ==> Main Page
		if($COURSE->id > 1) {
			unset($courses);
			$courses[$COURSE->id]->name = $COURSE->fullname;
			$courses[$COURSE->id]->id = $COURSE->id;
		}

		$c = $this->get_new_files($courses);

		return $c;
	}

	// Komplettes File-Array nach Timestamp sortieren
	function cmp_time($a,$b) {
		if($a[2] == $b[2]) {
			return 0;
		}
		return ($a[2] > $b[2]) ? -1 : 1;
	}

	function ordner_auslesen($ordner,$array) {
			
		global $CFG;
		global $USER;
		
		$result=array();
		$handle = opendir($CFG->dataroot.'/'.$ordner);
			
		while(false !== ($file = readdir($handle))) {
			if($file != "." && $file != "..") {
				$name=$CFG->dataroot.'/'.$ordner.'/'.$file;
				if(is_dir($name)) { // Unterordner gefunden -> rekursiv auslesen
					$ar = $this->ordner_auslesen($ordner.$file.'/',$array);
					$i=0;
					foreach($ar as $unterordner) {
						$result[] = $unterordner;
					}
				} else {
					$pathinfo=pathinfo($CFG->dataroot.'/'.$ordner.'/'.$file);
					$tmp=substr($ordner,strpos($ordner,"/")+1);
					if(substr($tmp,0,8) != "moddata/") { // verhindert das auslesen des moddata ordners
						if(filemtime($CFG->dataroot.'/'.$ordner.'/'.$file) > time()-3600*24*7) {
							// Datei ganz neu
							array_push($result,array($file,$CFG->wwwroot.'/file.php/'.$ordner.$file,filemtime($CFG->dataroot.'/'.$ordner.'/'.$file),$array[2],(int) $array[1],"old","",$pathinfo['extension']));
						} elseif($USER->lastlogin < filemtime($CFG->dataroot.'/'.$ordner.'/'.$file)) {
							// Datei ganz neu
							array_push($result,array($file,$CFG->wwwroot.'/file.php/'.$ordner.$file,filemtime($CFG->dataroot.'/'.$ordner.'/'.$file),$array[2],(int) $array[1],"new","",$pathinfo['extension']));
						} 
					}
				}
			}
		}
		closedir($handle);
		return $result;
	}

	// Funktion die die neuen Aktivit�ten und Dateien (sichtbare) aus dem Log filtert
	function get_new_files($courseids) {

		global $CFG;
		global $USER;
		global $max_items;

		$new_files=array();
		$tmp_directorys=array();

		foreach($courseids as $course) {
			// Filtere interessante Einträge aus den Logdaten
			$logdata = get_records_sql("SELECT max(time),time,url,info,course,module FROM ".$CFG->prefix."log WHERE (action LIKE '%add%' OR action LIKE '%update%') AND module!='course' AND module IN ('assignment', 'chat', 'choice', 'data', 'forum', 'glossary', 'hotpot', 'journal', 'lams', 'lesson', 'quiz', 'resource', 'scorm', 'survey', 'wiki', 'workshop') AND course=".$course->id." AND action != 'update grades' AND action NOT LIKE '%entry%' GROUP BY info,url ORDER BY time DESC LIMIT ".$max_items);

			foreach($logdata as $log_entry) {

				if($log_entry->module == "resource") {
					$res = get_record_sql("SELECT name,type,reference FROM ".$CFG->prefix.$log_entry->module." WHERE id=".$log_entry->info);
				} else {
					$res = get_record_sql("SELECT name FROM ".$CFG->prefix.$log_entry->module." WHERE id=".$log_entry->info);
				}
				preg_match('~.*?id=([\d]+)~',$log_entry->url,$mid); // ID für mdl_course_modules aus der URL ziehen
				$modul = get_record_sql("SELECT added,visible FROM ".$CFG->prefix."course_modules WHERE id=".$mid[1]);
				if($res->type == "directory" AND $modul->visible == 1) {
					// Sichtbarer ordner gefunden array(url,kursid,kursname)
					array_push($tmp_directorys,array($res->reference,$log_entry->course,$course->name));
				} elseif($modul->visible == 1) {
					if($USER->lastlogin < $modul->added) {
						// Datei ganz neu
						array_push($new_files,array($res->name,$CFG->wwwroot.'/mod/'.$log_entry->module.'/'.$log_entry->url,(int) $modul->added,$course->name,$course->id,"new",$log_entry->module));
					} elseif($modul->added > time()-3600*24*7) {
						// Datei nicht ganz neu
						array_push($new_files,array($res->name,$CFG->wwwroot.'/mod/'.$log_entry->module.'/'.$log_entry->url,(int) $modul->added,$course->name,$course->id,"old",$log_entry->module));
					} 
				}
			}
			
			if(empty($this->config->conf_forum)) {
				// Füge Attachments aus Foren hinzu
				$forum_attachments=get_records_sql("
				SELECT fp.id, fd.forum, fp.attachment, fp.modified FROM mdl_forum_discussions fd
				JOIN mdl_forum_posts fp
				ON fd.id= fp.discussion
				WHERE fd.course={$course->id}
				AND attachment != ''");
				
				foreach($forum_attachments as $attachment) {
					if($USER->lastlogin < $attachment->modified) {
						// Datei ganz neu http://localhost/moodle192/file.php/2/moddata/forum/1/5/resource_player_doc.pdf
						array_push($new_files,array($attachment->attachment,$CFG->wwwroot."/file.php/{$course->id}/moddata/forum/{$attachment->forum}/{$attachment->id}/{$attachment->attachment}",(int) $attachment->modified,$course->name,$course->id,"new",'forum'));
					} elseif($attachment->modified > time()-3600*24*7) {
						// Datei nicht ganz neu
						array_push($new_files,array($attachment->attachment,$CFG->wwwroot."/file.php/{$course->id}/moddata/forum/{$attachment->forum}/{$attachment->id}/{$attachment->attachment}",(int) $attachment->modified,$course->name,$course->id,"old",'forum'));
					} 	
				}
			}
			
			if(empty($this->config->conf_glossar)) {
				// Füge Attachments aus Glossaren hinzu
				$glossary_attachments=get_records_sql("
				SELECT ge.id as entryid,g.id as glossaryid,ge.timemodified,ge.attachment FROM mdl_glossary g
				JOIN mdl_glossary_entries ge
				ON g.id = ge.glossaryid
				WHERE g.course = {$course->id}
				AND ge.attachment != ''");
				
				foreach($glossary_attachments as $attachment) {
					if($USER->lastlogin < $attachment->timemodified) {
						// Datei ganz neu http://localhost/moodle192/file.php/2/moddata/forum/1/5/resource_player_doc.pdf
						array_push($new_files,array($attachment->attachment,$CFG->wwwroot."/file.php/{$course->id}/moddata/glossary/{$attachment->glossaryid}/{$attachment->entryid}/{$attachment->attachment}",(int) $attachment->timemodified,$course->name,$course->id,"new",'glossary'));
					} elseif($attachment->timemodified > time()-3600*24*7) {
						// Datei nicht ganz neu
						array_push($new_files,array($attachment->attachment,$CFG->wwwroot."/file.php/{$course->id}/moddata/glossary/{$attachment->glossaryid}/{$attachment->entryid}/{$attachment->attachment}",(int) $attachment->timemodified,$course->name,$course->id,"old",'glossary'));
					} 	
				}
			}
			
		}


		// Gehe Dateien in sichtbaren Ordnern durch und adde Sie zur Liste
		foreach($tmp_directorys as $dir) {
			$tmp=$this->ordner_auslesen($dir[1].'/'.$dir[0],$dir);
			foreach($tmp as $value) {
				array_push($new_files,$value);
			}
		}

		//Sort out duplicate entries
		$new_new_files = array();
		foreach($new_files as $new_file) {
			$duplicate = 0;
			foreach($new_new_files as $new_new_file) {
				$ares = array_diff($new_file, $new_new_file);
				if(empty($ares)) {
					$duplicate = 1;
				}
			}
			if(!$duplicate) {
				$new_new_files[] = $new_file;
			}
		}

		//In Kursgruppen sortieren
		foreach($new_new_files as $file) {
			$filespercourse[$file[4]][] = $file;
		}

		//Innerhalb der Kursgruppen nach Zeit sortieren
		foreach($filespercourse as $file) {
			usort($file, array($this,'cmp_time'));
			$very_new_files[]=$file;
		}
		
		return $very_new_files;
	}
	
}
?>
