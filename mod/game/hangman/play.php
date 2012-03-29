<?php  // $Id: play.php,v 1.6 2009/01/05 12:23:11 bdaloukas Exp $

// This files plays the game hangman

function game_hangman_continue( $id, $game, $attempt, $hangman, $newletter, $action)
{
	global $USER;
	if( $attempt != false and $hangman != false){
		if( ($action == 'nextword') and ($hangman->finishedword != 0)){
			//finish with one word and continue to another
			if( !set_field( 'game_hangman', 'finishedword', 0, 'id', $hangman->id)){
				error( "game_hangman_continue: Can't update game_hangman");
			}
		}else
		{
			return game_hangman_play( $id, $game, $attempt, $hangman);
		}
	}
	
	$updatehangman = (($attempt != false) and ($hangman != false));
		
	$textlib = textlib_get_instance();

	//new game
    srand ((double)microtime()*1000000);
    
    //I try 10 times to find a new question
    for($i=1; $i <= 10; $i++)
    {
		$rec = game_question_shortanswer( $game, $game->param7);
		
		if( $rec === false){
			continue;
		}

        $answer = game_upper( $rec->answertext, $game->language);
        
        $answer2 = $answer;
        if( $game->param7){
            //Have to delete space
            $answer2 = str_replace( ' ', '', $answer2);
        }
        if( $game->param8){
            //Have to delete -
            $answer2 = str_replace( '-', '', $answer2);
        }
        if( $game->language == ''){
            $game->language = game_detectlanguage( $answer2);
        }
        $allletters = game_getallletters( $answer2, $game->language);
		
        if( $allletters === false){
            continue;
        }
        
        if( $game->param7){
            $allletters .= '_';
        }        
        if( $game->param8){
            $allletters .= '-';
        }        
		
		if( $game->param7 == false){   
		    //I don't allow spaces
    		if( strpos( $answer, " ")){
	    		continue;
	    	}
	   }
		
	    if( $attempt == false){
		    $attempt = game_addattempt( $game);
    	}
		        
		$_GET[ 'newletter'] = '';
		
		$query->attemptid = $attempt->id;
		$query->gameid = $game->id;
		$query->userid = $USER->id;
		$query->sourcemodule = $game->sourcemodule;
		$query->questionid = $rec->questionid;
		$query->glossaryentryid = $rec->glossaryentryid;
		$query->attachment = $rec->attachment;
		$query->questiontext = addslashes( $rec->questiontext);
		$query->score = 0;
		$query->timelastattempt = time();
		$query->answertext = $answer;
		$query->answerid = $rec->answerid;
		if( !($query->id = insert_record( 'game_queries', $query))){
			print_object( $query);
			error( "game_hangman_continue: Can't insert to table game_queries");
		}
		
		$newrec->id = $attempt->id;
		$newrec->queryid = $query->id;
		if( $updatehangman == false){
			$newrec->maxtries = $game->param4;
			if( $newrec->maxtries == 0){
				$newrec->maxtries = 1;
			}
			$newrec->finishedword = 0;
			$newrec->corrects = 0;
		}
		
		$newrec->allletters = $allletters;
		
		$letters = '';
		if( $game->param1){
			$letters .= $textlib->substr( $answer, 0, 1);
		}
		if( $game->param2){
			$letters .= $textlib->substr( $answer, -1, 1);
		}
		$newrec->letters = $letters;

		if( $updatehangman == false){
			if( !game_insert_record(  'game_hangman', $newrec)){
				error( 'game_hangman_continue: error inserting in game_hangman');
			}	
		}else
		{
			if( !update_record(  'game_hangman', $newrec)){
				error( 'game_hangman_continue: error updating in game_hangman');
			}
			$newrec = get_record_select( 'game_hangman', "id=$newrec->id");
		}
        game_hangman_play( $id, $game, $attempt, $newrec);
        return;
    }
    
	error( get_string( 'hangman_nowords', 'game'));
}

function game_hangman_onfinishgame( $game, $attempt, $hangman)
{
	$score = $hangman->corrects / $hangman->maxtries;

	game_updateattempts( $game, $attempt, $score, true);

	if( !set_field( 'game_hangman', 'finishedword', 0, 'id', $hangman->id)){
		error( "game_hangman_onfinishgame: Can't update game_hangman");
	}
}

function hangman_existall( $str, $strfind)
{
	$textlib = textlib_get_instance();
	
    $n = $textlib->strlen( $str);
    for( $i=0; $i < $n; $i++)
    {
		$pos = $textlib->strpos( $strfind, $textlib->substr( $str, $i, 1));
        if( $pos === false)
            return false;
    }
  
    return true;
}

function game_hangman_play( $id, $game, $attempt, $hangman, $onlyshow=false, $showsolution=false)
{
	global $CFG;
	
	$query = get_record( 'game_queries', 'id', $hangman->queryid);
    $max=6;		// maximum number of wrong
    hangman_showpage( $done, $correct, $wrong, $max, $word_line, $word_line2, $links,  $game, $attempt, $hangman, $query, $onlyshow, $showsolution);
	
    if (!$done)
    {
        if ($wrong>6){
            $wrong=6;
        }
		if( $game->param3 == 0){
			$game->param3 = 1;
		}
        echo "\r\n<BR/><img src=\"".$CFG->wwwroot.'/mod/game/hangman/'.$game->param3.'/hangman_'.$wrong.'.jpg"';
		$message  = sprintf( get_string( 'hangman_wrongnum', 'game'), $wrong, $max);
		echo ' ALIGN="MIDDLE" BORDER="0" HEIGHT="100" alt="'.$message.'"/>';
		
        if ($wrong >= $max){
			//This word is incorrect. If reach the max number of word I have to finish else continue with next word
			hangman_oninncorrect( $id, $word_line, $query->answertext, $game, $attempt, $hangman);
        }else
		{
            $i = $max-$wrong;
			echo ' '.get_string( 'hangman_restletters_'.($i > 1 ? 'many' : 'one'), 'game', $i);

            echo "<br/><font size=\"5\">\n$word_line</font>\r\n";
			if( $word_line2 != ''){
				echo "<br/><font size=\"5\">\n$word_line2</font>\r\n";
			}

			if( $hangman->finishedword == false){
				echo "<br/><br/><BR/>".get_string( 'hangman_letters', 'game').$links."\r\n";
			}
        }
	}else
	{
		//This word is correct. If reach the max number of word I have to finish else continue with next word
		hangman_oncorrect( $id, $word_line, $game, $attempt, $hangman, $query);
	}
	
	echo "<br/><br/>".get_string( 'hangman_grade', 'game').' : '.round( $query->percent * 100).' %';
	if( $hangman->maxtries > 1){
		echo '<br/><br/>'.get_string( 'hangman_gradeinstance', 'game').' : '.round( $query->percent * 100).' %';
	}
	
	if( $game->bottomtext != ''){
		echo '<br><br>'.$game->bottomtext;
	}
}
function hangman_showpage(&$done, &$correct, &$wrong, $max, &$word_line, &$word_line2, &$links, $game, &$attempt, &$hangman, &$query, $onlyshow, $showsolution)
{
	global	$USER, $CFG;
	
	$word = $query->answertext;
	
	$textlib = textlib_get_instance();
	
	if( array_key_exists( 'newletter', $_GET)){
		$newletter = $_GET[ 'newletter'];
	}else
	{
		$newletter = '';
	}
	if( $newletter == '_'){
	    $newletter = ' ';
	}

    $letters = $hangman->letters;
    if( $newletter != NULL)
    {
		if( $textlib->strpos( $letters,$newletter) === false){
			$letters .= $newletter;
		}
    }

    $links="";

    $alpha = $hangman->allletters;
    $wrong = 0;
		
    if( $game->param5){
        $s = trim( game_filtertext( $query->questiontext, $game->course));
        if( $s != '.' and $s <> ''){
    		echo "<br/><b>".$s.'</b>';
        }
		if( $query->attachment != ''){
            $file = "{$CFG->wwwroot}/file.php/$game->course/moddata/$query->attachment";
		    echo "<img src=\"$file\" />";
		}
		echo "<br/><br/>";
	}

    $word_line = $word_line2 = "";
	
	$len = $textlib->strlen( $word);
	
	$done = 1;
	$answer = '';
    for ($x=0; $x < $len; $x++)
    {
		$char = $textlib->substr( $word, $x, 1);
		
		if( $showsolution){
			$word_line2 .= ( $char == " " ? '&nbsp; ' : $char);
			$done = 0;
		}
		
		if ( $textlib->strpos($letters, $char)  === false){
			$word_line.="_<font size=\"1\">&nbsp;</font>\r\n";
			$done = 0;
			$answer .= '_';
		}else		
		{
			$word_line .= ( $char == " " ? '&nbsp; ' : $char);
			$answer .= $char;
		}
    }

    $correct = 0;

    $len_alpha = $textlib->strlen($alpha);
	$fontsize = 5;

    for ($c=0; $c < $len_alpha; $c++)
    {
		$char = $textlib->substr( $alpha, $c, 1);
		
		if ( $textlib->strpos($letters, $char) === false)
		{
			//User didn't select this character
			$params = 'id='.$_GET['id'].'&amp;newletter='.urlencode( $char);
			if( $onlyshow or $showsolution){
				$links .= $char;
			}else
			{
				$links .= "<font size=\"$fontsize\"><a href=\"attempt.php?$params\">$char</a></font>\r\n";
			}
			continue;
		}
		
		if ( $textlib->strpos($word, $char) === false)
		{
			$links .= "\r\n<font size=\"$fontsize\" color=\"red\">$char </font>";
			$wrong++;
		}else
		{
			$links .= "\r\n<B><font size=\"$fontsize\">$char </font></B> ";
			$correct++;
		}
	}

	$finishedword = ($done or $wrong >= 6);
	$finished = false;

	$updrec->id = $hangman->id;
	$updrec->letters = $letters;
	if( $finishedword){
		if( $hangman->finishedword == 0){
			//only one time per word increace the variable try
			$hangman->try = $hangman->try + 1;
			if( $hangman->try > $hangman->maxtries){
				$finished = true;
			}
			if( $done){
				$hangman->corrects = $hangman->corrects + 1;
				$updrec->corrects = $hangman->corrects;
			}
		}

		$updrec->try = $hangman->try;
		$updrec->finishedword = 1;
		
	}

	$query->percent = (50 - $wrong * 50 / 6 + $correct * 50) /  $textlib->strlen( $word);
	$query->percent /= 100;
	if( $query->percent > 1){
		$query->percent = 1;
	}

	if( $onlyshow or $showsolution){
		return;
	}
	
	if( !update_record( 'game_hangman', $updrec)){
		error( "hangman_showpage: Can't update game_hangman id=$updrec->id");
	}
	
	if( $done){
		$score = 1;
	}else if( $wrong >= 6){
		$score = 0;
	}else
	{
		$score = -1;
	}
	
	game_updateattempts( $game, $attempt, $score, $finished);
	game_update_queries( $game, $attempt, $query, $score, $answer);
}

//This word is correct. If reach the max number of word I have to finish else continue with next word
function hangman_oncorrect( $id, $word_line, $game, $attempt, $hangman, $query)
{  	
	echo "<BR/><BR/><font size=\"5\">\n$word_line</font>\r\n";
	
	echo '<p><BR/><font size="5" color="green">'.get_string( 'hangman_win', 'game').'</font><BR/><BR/></p>';
	if( $query->answerid){
		$feedback = get_field( 'question_answers', 'feedback', 'id', $query->answerid);
		if( $feedback != ''){
			echo "$feedback<br>";
		}
	}

	game_hangman_show_nextword( $id, $game, $attempt, $hangman);
}

function hangman_oninncorrect( $id, $word_line, $word, $game, $attempt, $hangman)
{
	$textlib = textlib_get_instance();
	
	echo "\r\n<BR/><BR/><font size=\"5\">\n$word_line</font>\r\n";

	echo '<p><BR/><font size="5" color="red">'.get_string( 'hangman_loose', 'game').'</font><BR/><BR/></p>';
	
	if( $game->param6){
		//show the correct answer
		$term=( $textlib->strpos($word, ' ') != false ? 'phrase' : 'word');
		echo '<br/>'.get_string( 'hangman_correct_'.$term, 'game');
		echo '<B>'.$word."</B><BR/><BR/>\r\n";
	}
	
	game_hangman_show_nextword( $id, $game, $attempt, $hangman, true);	
}

function game_hangman_show_nextword( $id, $game, $attempt, $hangman)
{
	global $CFG;
	
	echo '<br/>';
	if( ($hangman->try < $hangman->maxtries) or ($hangman->maxtries == 0)){
		//continue to next word
		$params = "id=$id&action2=nextword\">".get_string( 'nextword', 'game').'</a> &nbsp; &nbsp; &nbsp; &nbsp;'; 
		echo "<a href=\"$CFG->wwwroot/mod/game/attempt.php?$params";
	}else
	{
		game_hangman_onfinishgame( $game, $attempt, $hangman);
		echo "<a href=\"$CFG->wwwroot/mod/game/attempt.php?id=$id\">".get_string( 'nextgame', 'game').'</a> &nbsp; &nbsp; &nbsp; &nbsp; ';
	}
	
	if (! $cm = get_record("course_modules", "id", $id)) {
		error("Course Module ID was incorrect id=$id");
	}

	echo "<a href=\"$CFG->wwwroot/course/view.php?id=$cm->course\">".get_string( 'finish', 'game').'</a> ';
}

?>
