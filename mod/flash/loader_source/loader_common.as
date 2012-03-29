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

import org.jamiep.phpobject.*
import FPtextPane.*;

this._xscale=100;
this._yscale=100;



PHPObject.defaultGatewayKey = "secret";
PHPObject.defaultGatewayUrl = POGatewayURL;
_global.moodleService = new PHPObject();
moodleService.setCredentials(POMovieSess);
_level0.depthCount=1;
POVersion=10;//used to prevent caching
moodleService.cleanUp=function(goto){
	if (PODoneURL!="")
	{
		if (goto!=undefined)
		{
			PODoneURL+=('&goto='+escape(goto));
		}
		getURL(PODoneURL);
	}
	PODoneURL="";
};
//white background to expand to fill the stage :
this.createEmptyMovieClip("square_mc", -1000);
this.square_mc.beginFill(0xFFFFFF);
this.square_mc.moveTo(0, 0);
this.square_mc.lineTo(Stage.width, 0);
this.square_mc.lineTo(Stage.width, Stage.height);
this.square_mc.lineTo(0, Stage.height);
this.square_mc.lineTo(0, 0);
this.square_mc.endFill();
//
style = {contour:0x666666, underbars:0xaaaaaa, bars:0xdddddd, back:0xfcfcfc, backoff:0xeeeeee, background:0xffffff};
//
_global.moodleDebugger=this.attachMovie(FPtextPane.symbolName,"debugBox",_level0.depthCount++, {_x:0, _y:55});

moodleDebugger.initComponent(style, Stage.width-2, Stage.height-85, true, true);
moodleDebugger.addText("<b>Moodle Content Loader Version "+ver/100+"</b>");
moodleDebugger.addText("Flash Player Version :<b> "+System.capabilities.version+"</b>");
moodleDebugger.addText("Operating System :<b> "+System.capabilities.os+"</b>");
moodleDebugger.goBottom=true;
moodleService.onError=function(errNo, err) {
		switch (errNo) {
		case 0 :
		  err=("Moodle Service Connection Failure :")+err;
		  break;
		case 1 :
		  err=("Error sent from Moodle Service :")+err;
		  break;
		case 2 :
		  err=("The method you called does not exist :")+err;
		  break;
		}
		moodleDebugger.addText("<font color=\"#FF0000\">"+err+"</font>\n");
		moodleDebugger.showDebug(true);
}
moodleService.onOutput=function(output) {
		moodleDebugger.addText("<b>Output from Service Script : </b>\n"+output);
		//moodleDebugger.showDebug();
}
moodleDebugger.showDebug=function(fatalError){
	_level0.depthCount++;
	
	if (undefined==_level0.linkBox)
	{
		_level0.createTextField("linkBox", _level0.depthCount, 0, Stage.height-30, Stage.width, 30);
		_level0.linkBox.html=true;
	};
	_level0.depthCount++;
	if (undefined==_level0.titleBox)
	{
		_level0.createTextField("titleBox", _level0.depthCount, 0, 0, Stage.width, 50);
		_level0.titleBox.html=true;
	};
	if (fatalError==true)
	{
		_level0.linkBox.htmlText="<font size='22'><p align='center'><u><font color=\"#0000FF\"><a href=\"asfunction:cleanUp,\">Quit Movie</a></font></u></p></font>";
		_level0.titleBox.htmlText="<p align='center'><font size='40'>Error!</font></p>";
	} else
	{
		_level0.linkBox.htmlText="<font size='22'><p align='center'>You can <u><font color=\"#0000FF\"><a href=\"asfunction:showMovie,\">Go Back to Movie</a></font></u> or <u><font color=\"#0000FF\"><a href=\"asfunction:cleanUp,\">Quit Movie</a></font></u></p></font>";
		_level0.titleBox.htmlText="<p align='center'><font size='40'>Debug Window.</font></p>";
	}
    moodleDebugger._width=Stage.width-2;
    moodleDebugger._height=Stage.height-85;
	
	//this.addText("You can <u><font color=\"#0000FF\"><a href=\"asfunction:showMovie,\">Go Back to Movie</a></font></u> or <u><font color=\"#0000FF\"><a href=\"asfunction:cleanUp,\">Quit Movie</a></font></u>");
	_level0._visible=true;
	_level1._visible=false;
};
moodleDebugger.showMovie=function(){
	_level0._visible=false;
	_level1._visible=true;
};
_level0.showMovie=moodleDebugger.showMovie;

moodleDebugger.cleanUp=moodleService.cleanUp;

_level0.cleanUp=moodleDebugger.cleanUp;
function loadFile(fileURL,exit, holder){
	var fileExtension = fileURL.substr(-3);
	//make previously loaded movie clip invisible, we only want to see the last
	if (_level0.depthCount!=1)
	{
		_root["holder_"+depthCount]._visible=false;
	};
	if (holder=='h')
	{
		_level0.depthCount++;
		_root.createEmptyMovieClip("holder_"+depthCount,depthCount);
		var newHolder = _root["holder_"+depthCount];
	};
	if (holder=='l')
	{
		loadMovie(fileURL, "_level1");
		loadObj= _level1;
	} else 
	{
		var loadObj = newHolder;
		newHolder.loadMovie(fileURL);
	};
	var initObject = {
		_x: 5,
		_y: 40,
		target: loadObj,
		loadExit: exit,
		visible: false,
		fileURL : fileURL,
		holder : holder
	};
	_root.attachMovie("loader","loader",500, initObject);
}
function unloadFile(){
	if(typeof(holder) != undefined){
		holder.removeMovieClip();
	}
	if(typeof(sound_1) != undefined){
		sound_1.stop();
		delete sound_1;
	}
}
//POFonts="hgsoeikakupoptai,msmincho";
if (POFonts!='')
{
	fonts= POFonts.split(",");
} else
{
	fonts=new Array();
}
//fonts = ["hgsoeikakupoptai", "msmincho"];
files=new Array();
exitFunctions=new Array();
holders=new Array();
for (index in fonts)
{
	files.push(fonts[index]+"_lib.swf", fonts[index]+".swf");
	exitFunctions.push("initLoad", "initLoad");
	holders.push('h', 'h'); //h is a holder movie clip on _level0
}
files.push(POMovieURL);
exitFunctions.push("setup");
holders.push('l');//l is a new _level
function initLoad(){
	if(files.length >= 1){
		var currentFile = files.shift();
		var currentExit = exitFunctions.shift();
		var currentHolder = holders.shift();
		loadFile(currentFile, currentExit, currentHolder);
        if (files.length== 0)
        {
		    moodleDebugger.addText("Loading main movie file.\n");
        } else
        {
		    moodleDebugger.addText("Loading font file.\n");
        }
	} else {
		moodleDebugger.addText("Loading done."+"\n");
	}
}
function remove(){
	holder.unloadMovie();
	moodleDebugger.addText("movie unloaded"+"\n");
	initLoad();
}

//an attempt to override trace function  : doesn't seem to work
/*ASSetPropFlags(_global, null, 2, 5);
trace=moodleDebugger.addText;*/


function myOnKeyDown() {
	  // 68 is key code for D
	  if (Key.getCode() == 68 && Key.isDown(Key.CONTROL)) {
		moodleDebugger.showDebug();	
	}
}
var myListener:Object = new Object();
myListener.onKeyDown = myOnKeyDown;
Key.addListener(myListener);
