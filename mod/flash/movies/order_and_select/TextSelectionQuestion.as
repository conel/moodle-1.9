import FPtextPane.*;
import Question;
class TextSelectionQuestion extends Question{
	private var pane:MovieClip;
	private var question:MovieClip;

	public function TextSelectionQuestion(configdata : Array)
	{
		var style:Object = {contour:0x666666, underbars:0xaaaaaa, bars:0xdddddd, back:0xfcfcfc, backoff:0xeeeeee, background:0xffffff};
		//
		if (configdata['embedfonts']=="1")
		{
			question=_root.attachMovie(FPtextPane.symbolName,"pane",_root.newDepth++);
			//initComponent(style:Object,w:Number,h:Number, outline:Boolean, mouseweel:Boolean):Void
			question.initComponent(style, Stage.width-2, Math.floor((Stage.height-55)/2), true, true);
			//have a box for the question and the selectable text
			question.goBottom=false;
			question.btext.embedFonts=false;
			question.addText(configdata.question);
			pane=_root.attachMovie(FPtextPane.symbolName,"pane",_root.newDepth++);
			//initComponent(style:Object,w:Number,h:Number, outline:Boolean, mouseweel:Boolean):Void
			pane.initComponent(style, Stage.width-2, Math.floor((Stage.height-55)/2), true, true);
			pane.btext.condenseWhite = true;
			pane._y=Math.floor((Stage.height-50)/2)+5;
			pane.goBottom=false;
			pane.btext.embedFonts=true;
			var startIndex:Number=0;
		} else
		{
			//we'll have the text and question in the same box.
			pane=_root.attachMovie(FPtextPane.symbolName,"pane",_root.newDepth++);
			//initComponent(style:Object,w:Number,h:Number, outline:Boolean, mouseweel:Boolean):Void
			pane.initComponent(style, Stage.width-2, Stage.height-50, true, true);
			pane.btext.condenseWhite = true;
			pane.goBottom=false;
			pane.btext.embedFonts=false;
			pane.addText(configdata.question);
			pane.addText('<p align="center">-------------------</p>');
			var startIndex:Number=pane.btext.text.length;
		}
		pane.addText(configdata.text);
		pane.formatWord=function(wordIndexNo : Number)
		{
			var fmt = new TextFormat();
			fmt.url = "asfunction:textClicked,"+wordIndexNo;
			if (this.wordsArray[wordIndexNo].selected)
			{
				fmt.color = 0x00FF00;
			} else {
				fmt.color = 0x000000;
			};
			this.btext.setTextFormat(this.wordsArray[wordIndexNo].startIndex, this.wordsArray[wordIndexNo].endIndex, fmt);
		};
		var textWithoutHTML:String=pane.btext.text;
		pane.wordsArray=new Array();
		var lastIndex:Number=startIndex;
		var nextArrayIndex:Number;
		for (var i=startIndex; i<textWithoutHTML.length; i++)
		{
			switch (textWithoutHTML.charAt(i)){
				case ' ' :
				case '\n' :
				case '\r':
				case '\t' :
					if (lastIndex!=(i-1)) // ignore repeated white space
					{
						  nextArrayIndex=pane.wordsArray.push({word: textWithoutHTML.substring(lastIndex, i),
																	startIndex : lastIndex, 
																	endIndex : i, 
																	selected : false});
						  pane.formatWord(nextArrayIndex-1); 
					}
					lastIndex=i;
				  break;
				default :
			};
		};
		if (lastIndex!=i-1) // don't forget anything on the end
		{
			nextArrayIndex=pane.wordsArray.push({word: textWithoutHTML.substring(lastIndex, i),
															startIndex : lastIndex, 
															endIndex : i, 
															selected : false});
			pane.formatWord(nextArrayIndex-1); 
		}
		pane.textClicked=function(wordIndex : String)
		{
			var wordIndexNo=Number(wordIndex);
			this.wordsArray[wordIndexNo].selected= (!this.wordsArray[wordIndexNo].selected);
			this.formatWord(wordIndexNo);
		}
		
	}

	
	public function cleanUp()
	{
		pane.removeMovieClip();
	}
	private function freezeMovies()
	{
		pane.textClicked=null;
	}

	public function answer() : Object
	{
		freezeMovies();
		//send results to server :
		var answer=new Array();
		for (var wordIter=0; wordIter<pane.wordsArray.length; wordIter++)
		{
			
			if (pane.wordsArray[wordIter].selected)
			{
				answer.push(wordIter);
			}
		};

		return ({answer : answer});
	}
	public function feedback(result : Array)
	{
		if (result[0])
		{
			_root.attachMovie("rightWithMsgMC", "feedback", _root.newDepth++, {_x:Math.round((Stage.width-150)/2), _y:Math.round(Stage.height /2), _alpha:65});
		} else
		{
			_root.attachMovie("wrongWithMsgMC", "feedback", _root.newDepth++, {_x:Math.round((Stage.width-70)/2), _y:Math.round(Stage.height /2)+20, _alpha:65});
		};
		_root.feedback.textBox.text=result[1];
	}
	public function onEnterFrame() 
	{

	}

}