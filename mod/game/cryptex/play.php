<?PHP

require_once( "cryptexdb_class.php");

function game_cryptex_continue( $id, $game, $attempt, $cryptexrec, $endofgame)
{
	if( $endofgame){
		game_updateattempts( $game, $attempt, -1, true);
		$endofgame = false;
	}	
	
	if( $attempt != false and $cryptexrec != false){
        $crossm = get_record_select( 'game_cross', "id=$attempt->id");
		return game_cryptex_play( $id, $game, $attempt, $cryptexrec, $crossm);
	}

	if( $attempt === false){
		$attempt = game_addattempt( $game);
	}
	
	$textlib = textlib_get_instance();

	$cryptex = new CryptexDB();

	$questions = array();
	$infos = array();

	$answers = array();
	$recs = game_questions_shortanswer( $game);
	if( $recs == false){
		error( get_string( 'cryptex_nowords', 'game'));
	}
	$infos = array();
	foreach( $recs as $rec){
	    if( $game->param7 == false){	        
    		if( $textlib->strpos( $rec->answertext, ' ')){
	    		continue;		//spaces not allowed
	    	}
	    }
		
		$rec->answertext = game_upper( $rec->answertext);
		$answers[ $rec->answertext] = game_repairquestion( $rec->questiontext);
		$infos[ $rec->answertext] = array( $game->sourcemodule, $rec->questionid, $rec->glossaryentryid);
	}

	$cryptex->setwords( $answers, $game->param1);
	
	if( $cryptex->computedata( $crossm, $crossd, $letters, $game->param2)){
		$new_crossd = array();
		foreach( $crossd as $rec)
		{
			if( array_key_exists( $rec->answertext, $infos)){
				$info = $infos[ $rec->answertext];
				
				$rec->id = 0;
				$rec->sourcemodule = $info[ 0];
				$rec->questionid = $info[ 1];
				$rec->glossaryentryid = $info[ 2];
			}
			game_update_queries( $game, $attempt, $rec, 0, '');
			$new_crossd[] = $rec;
		}
		$cryptexrec = $cryptex->save( $game, $crossm, $new_crossd, $attempt->id, $letters);
	}
	
	game_updateattempts( $game, $attempt, 0, 0);

	return game_cryptex_play( $id, $game, $attempt, $cryptexrec, $crossm);
}


function cryptex_showlegend( $legend, $title)
{
  if( count( $legend) == 0)
    return;
    
  echo "<br><b>$title</b><br>";
  foreach( $legend as $key => $line)
    echo "$key: $line<br>";
}


//q means game_queries.id
function game_cryptex_check( $id, $game, $attempt, $cryptexrec, $q, $answer)
{
	if( $attempt === false){
		game_cryptex_continue( $id, $game, $attempt, $cryptexrec);
		return;
	}

	$crossm = get_record_select( 'game_cross', "id=$attempt->id");
	$query = get_record_select( 'game_queries', "id=$q");

	$answer1 = trim( game_upper( $query->answertext));
	$answer2 = trim( game_upper( $answer));

	$textlib = textlib_get_instance();
	$len1 = $textlib->strlen( $answer1);
	$len2 = $textlib->strlen( $answer2);
	$equal = ( $len1 == $len2);
	if( $equal){
		for( $i=0; $i < $len1; $i++)
		{
			if( $textlib->substr( $answer1, $i, 1) != $textlib->substr( $answer2, $i, 1))
			{
				$equal = true;
				break;
			}
		}
	}
	if( $equal == false)
	{
		game_update_queries( $game, $attempt, $query, 0, $answer2);
		game_cryptex_play( $id, $game, $attempt, $cryptexrec, $crossm, true);
		return;
	}

	game_update_queries( $game, $attempt, $query, 1, $answer2);

	game_cryptex_play( $id, $game, $attempt, $cryptexrec, $crossm, true);
}

function game_cryptex_play( $id, $game, $attempt, $cryptexrec, $crossm, $updateattempt=false, $onlyshow=false, $showsolution=false)
{
	$textlib = textlib_get_instance();
	
	global $CFG;
	
	echo '<br>';
	
	$cryptex = new CryptexDB();
	$questions = $cryptex->load( $crossm, $mask, $corrects);

	$len = $textlib ->strlen( $mask);
	
	//count1 means there is a guested letter 
	//count2 means there is a letter that not guessed
	$count1 = $count2 = 0;
	for($i=0; $i < $len; $i++)
	{
		$c = $textlib->substr( $mask, $i, 1);
		if( $c == '1'){
			$count1++;
		}else if( $c == '2')
		{
			$count2++;
		}
	}
	if( $count1 + $count2 == 0){
		$gradeattempt = 0;
	}else
	{
		$gradeattempt = $count1 / ($count1 + $count2);
	}
	$finished = ($count2 == 0);

	if( $updateattempt){
		game_updateattempts( $game, $attempt, $gradeattempt, $finished);
	}

	if( ($onlyshow == false) and ($showsolution == false)){
		if( $finished){
			game_cryptex_onfinished( $id, $game, $attempt, $cryptexrec);
		}
	}
	echo $cryptex->display( $crossm->cols, $crossm->rows, $cryptexrec->letters, $mask, $showsolution);

	$grade = round( 100 * $gradeattempt);
	echo '<br>'.get_string( 'grade', 'game').' '.$grade.' %';

	echo "<br><br>";
	$i = 0;
	foreach( $questions as $key => $q){
		$i++;
		if( $showsolution == false){
			//When I want to show the solution a want to show the questions to.
			if( array_key_exists( $q->id, $corrects)){
				continue;
			}	
		}
		
		echo "$i. ".game_filtertext( $q->questiontext, 0);
		if( $showsolution){
			echo " &nbsp;&nbsp;&nbsp;$q->answertext<B></b>";
		}else if( $onlyshow == false){
			echo '<input type="submit" value="'.get_string( 'answer').'" onclick="OnCheck( '.$q->id.');" />';
		}
		echo "<br>\r\n";
	}
	
	if( $game->bottomtext != ''){
		echo '<br><br>'.$game->bottomtext;
	}	
	
	?>
		<script>
			function OnCheck( id)
			{
				s = window.prompt( "<?php echo get_string( 'cryptex_giveanswer', 'game'); ?>");
				
				window.location.href = "<?php echo $CFG->wwwroot.'/mod/game/attempt.php?action=cryptexcheck&id='.$id ?>&q=" + id + "&answer=" + s;
			}
		</script>
	<?php
}

function game_cryptex_onfinished( $id, $game, $attempt, $cryptexrec)
{
	global $CFG;

	if (! $cm = get_record("course_modules", "id", $id)) {
		error("Course Module ID was incorrect id=$id");
	}

	echo '<B>'.get_string( 'cryptex_win', 'game').'</B><BR>';	
	echo '<br>';	
	echo "<a href=\"$CFG->wwwroot/mod/game/attempt.php?id=$id&forcenew=1\">".get_string( 'nextgame', 'game').'</a> &nbsp; &nbsp; &nbsp; &nbsp; ';
	echo "<a href=\"$CFG->wwwroot/course/view.php?id=$cm->course\">".get_string( 'finish', 'game').'</a> ';
	echo "<br><br>\r\n";
}
