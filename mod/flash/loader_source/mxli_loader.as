//    Part of a Flash Text Segmenting Activity that communicates grades and results 
//    to the Moodle LMS.
//    Copyright (C) 2004, 2005  James Pratt
//    Contact  : me@jamiep.org http://jamiep.org
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

#include "loader_common.as"
function setup(){
	moodleDebugger.addText("All files loaded successfully.\n");
	moodleDebugger.showMovie();
	moodleService.onInit=moodleService.onResult=function()
	{
		_level1.nav.nextBtn._visible=true;
	}
	_level1.QuizTrackWatcher = function(prop, oldVal, newVal) {
		moodleDebugger.addText("QuizTracker inited!");
		//
		_level0.QuizTrack=newVal;
		newVal.initNewPage=function (num)
		{
			_level1.nav.nextBtn._visible=false;
			moodleDebugger.addText("initNewPage called");
			//moodleDebugger.addText(_level1.SessionArray[] +'  '+ _level1.session);
			if (!_level1.SessionArray.length)
			{
				//no results yet. First page :
				moodleService.init();
			} else
			{
				wholeArray=_level1.SessionArray[_level1.session];
				answer={	time : wholeArray['stopTime']- wholeArray['startTime'],
							answer : wholeArray['student_response'],
							interaction_id : wholeArray['interaction_id'],
							interaction_type : wholeArray['interaction_type']};
				moodleDebugger.addText("_level1.QuizTrack.Quest_Frames : "+_level1.QuizTrack.Quest_Frames.join(', '));
				moodleDebugger.addText("_level1.QuizTrack.quest_num : "+_level1.QuizTrack.quest_num);
				moodleService.answer(_level1.QuizTrack.Quest_Frames[(_level1.QuizTrack.quest_num)-2]-1,
									 answer,((wholeArray['result']=='C')?100:0));
											
			}
		};
		//last page
		newVal.oldInitSubmitScore=newVal.initSubmitScore;
		newVal.initSubmitScore=function ()
		{
			_level1.nav.nextBtn._visible=false;
			wholeArray=_level1.SessionArray[_level1.session];
			answer={	time : wholeArray['stopTime']- wholeArray['startTime'],
						answer : wholeArray['student_response'],
						interaction_id : wholeArray['interaction_id'],
						interaction_type : wholeArray['interaction_type']};
			//moodleService.setCleanUp(true);//last call to service
			_level1.nav.duplicateMovieClip('lastNav',20000, {_x:_level1.nav._x,
												   					_y:_level1.nav._y,
																	_visible:false});
			_level1.QuizTrack.oldSetNewPage=_level1.QuizTrack.setNewPage;
			if (_level1.QuizTrack.results_page==true)
			{
				_level1.QuizTrack.setNewPage=function()
				{
					_level1.QuizTrack.oldSetNewPage();
					_level1.lastNav._visible=true;
					_level1.lastNav.totQuest="";
					_level1.lastNav.curQuest="Done";
					_level1.lastNav.updateFrame=null;
					
					_level1.QuizTrack.setNewPage=moodleService.cleanUp;
					
				}
			} else
			{
				_level1.lastNav._visible=true;
				_level1.lastNav.totQuest="";
				_level1.lastNav.curQuest="Done";
				_level1.lastNav.updateFrame=null;
				
				_level1.QuizTrack.setNewPage=moodleService.cleanUp;
					
				
			}
			moodleService.answer(_level1.QuizTrack.Quest_Frames[_level1.QuizTrack.quest_num-1]-1,
								 answer,((wholeArray['result']=='C')?100:0));
			_level1.QuizTrack.oldInitSubmitScore();
		};


		  // Return the value of newVal.
	  return newVal;
	}
	if (_level1.QuizTrack==undefined)
	{
		_level1.watch("QuizTrack", _level1.QuizTrackWatcher);
	} else
	{
		//if it is already defined we will trigger the watcher ourselves
		_level1.QuizTrackWatcher(null, null, _level1.QuizTrack);
	}
	
		

}



initLoad();