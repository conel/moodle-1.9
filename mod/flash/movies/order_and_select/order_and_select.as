//!-- UTF8
//
//    Part of a Flash Text Segmenting Activity that communicates grades and results 
//    to the Moodle LMS.
//    Copyright (C) 2004, 2005  James Pratt
//    Contact  : me@jamiep.org http://jamiep.org
//
//    Developed for release under GPL,
//    funded by AGAUR, Departament d'Universitats, Recerca i Societat de la
//    Informació, Generalitat de Catalunya.
//
//    This program is free software; you can redistribute it and/or modify
//    it under the terms of the GNU General Public License as published by
//    the Free Software Foundation; either version 2 of the License, or
//    (at your option) any later version.
//
//    This program is distributed in the hope that it will be useful,
//    but WITHOUT ANY WARRANTY; without even the implied warranty of
//    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//    GNU General Public License for more details.
//
//    You should have received a copy of the GNU General Public License
//    along with this program; see flash/license.txt;
//      if not, write to the Free Software
//    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA 
import TextOrderQuestion
import TextSelectionQuestion

var newDepth:Number=1;
var currentQuestion:Question;
var now : Date;
var time: Number;



displayTextField=function (name : String, textToDisplay : String, x : Number , y : Number , width : Number , height : Number ) 
{
	_root.createTextField(name, _root.newDepth++, x, y, width, height); 
	
 	_root[name].wordWrap=true;
	_root[name].multiline=true;
	_root[name].html=true;	
	_root[name].background=false;
	_root[name].border=false;
	_root[name].htmlText=textToDisplay;
}


_root.onEnterFrame=function() { 
	if (currentQuestion.onEnterFrame!=undefined)
	{
		currentQuestion.onEnterFrame();
	};
}

showInitialising=function() { //waiting for config data from server
	qIter=0;
	firstButtonX=Math.round((Stage.width-158)/2);
	scndButtonX=(Math.round((Stage.width-firstButtonX)/2)+firstButtonX-79);
    if ((scndButtonX -firstButtonX)<158)
    {
        scndButtonX=firstButtonX+160;
        
    }
	displayTextField("introBox", "<font size='32'><p align='center'>Getting questions...</p></font>", 5, 5, Stage.width -10, 345);
	_root.attachMovie('answerButtonSym','answerButton', _root.newDepth++, {_x: firstButtonX, _y: (Stage.height-44), buttonAction : checkAnswer, _visible : false});
	_root.attachMovie('buttonSym','nextBtn', _root.newDepth++, {_x: scndButtonX, _y: (Stage.height-44), _visible : false});
	_root.answerButton.textBox.text="Check Answer";
};
showGotConfig=function() {
	introBox.htmlText=moodleService.config['intro'];
	_root.nextBtn._visible=true;
	_root.nextBtn.textBox.text="Start";
	nextBtn.buttonAction = function() {
		showQuestion();
	}
};
showQuestion=function() {
	introBox.removeTextField();
	feedback.removeMovieClip();

	if (currentQuestion!=undefined)
	{
		currentQuestion.cleanUp();
	};
	if (moodleService.config[qIter]['q_type']=='Text Selection')
	{
		currentQuestion=new TextSelectionQuestion(moodleService.config[qIter]);
	} else
	{
		currentQuestion=new TextOrderQuestion(moodleService.config[qIter]);
	};


	_root.createTextField("questionNoBox",_root.newDepth++, 5, (Stage.height-40), 175, 35);
	_root['questionNoBox'].background=false;
	_root['questionNoBox'].border=false;
	_root['questionNoBox']._alpha=50;
	var my_fmt:TextFormat = new TextFormat();
	my_fmt.align = "center";
	my_fmt.size=20;
	_root['questionNoBox'].setNewTextFormat(my_fmt);
	_root['questionNoBox'].text="Question "+(qIter+1)+" of "+moodleService.q_no;
	
	answerButton._visible=true;
	answerButton.textBox.text="Check Answer";
	answerButton.buttonAction = function() {
		showCheckingAnswer();
	}
	nextBtn._visible=false;
	now = new Date();
	time=now.getTime();
};
showCheckingAnswer=function() {
	now= new Date();
	time=now.getTime() - time;
	_root.answerButton._visible=false;
	var answerObject=currentQuestion.answer();
	answerObject.time=time/1000;
	moodleService.answer(qIter, answerObject);
};
showFeedback=function(result) { //result should be an array of two values right / wrong and a message
	if (!result[0] && moodleService.config['allowretry']=="1")
	{
		_root.answerButton._visible=true;
		_root.answerButton.textBox.text="Try Again";
		_root.answerButton.buttonAction = function() {
			showQuestion();
		}
	}
	currentQuestion.feedback(result);
	nextBtn._visible=true;
	if (qIter==moodleService.q_no-1)
	{
		_root.nextBtn.textBox.text="Finish";
		_root.nextBtn.buttonAction = moodleService.cleanUp;
	} else
	{
		_root.nextBtn.textBox.text="Next";
		nextBtn.buttonAction = function() {
			qIter++;
			showQuestion();
		}
	}	
}

moodleService.onInit = function()
{
	showGotConfig();
}


moodleService.answer_onResult = showFeedback;
moodleService.init();
showInitialising();
