import Question;
class TextOrderQuestion extends Question{
	private var startX:Number;
	private var textBlockMovies : Array;
	private var qNo: Number;
	private var seperator;

	public function TextOrderQuestion(configdata : Array)
	{
		displayTextField("questionBox", configdata['question'], 5, 5, Stage.width -10, 345);
		textBlockMovies=new Array();
		seperator=configdata['seperator'];
		if (configdata['text'].length)
		{
			var my_fmt:TextFormat = new TextFormat();
			if (configdata['font']!="")
			{
				my_fmt.font=configdata['font'];
			} else
			{
				my_fmt.font='_sans';
			}
			var totalWidth=20;
			var height=0;
			var actualTotalWidth:Number=0;
            var maxHeight=Stage.height - 245;
			for (var i=0; i<configdata['text'].length; i++)
			{
				var initObject:Object=new Object();
				// Create a text field just large enough to display the text.
				initObject.textFieldWidth=my_fmt.getTextExtent(configdata['text'][i]).width+4;
				initObject._x=randRange(0, (Stage.width - initObject.textFieldWidth));
				initObject._y=randRange(0, Stage.height);
				initObject.finalY=185;
				initObject.rate=10;
				initObject.dragging=false;
				_root.attachMovie("textBlock", "textItem"+i, _root.newDepth++, initObject);
				
				_root["textItem"+i].onPress = function()
				{
					this.startDrag();
					this.dragging=true;
					this.textBox.backgroundColor=0xFFFFFF;
					this._alpha=70;
				};
				_root["textItem"+i].onRelease = function()
				{
					this.stopDrag();
					this.dragging=false;
					this.textBox.backgroundColor=0xCCCCCC;
					this._alpha=100;
				};
	
				textBlockMovies.push("textItem"+i);
				if (configdata['font']!="")
				{
					_root["textItem"+i].textBox.embedFonts=true;
				}
				_root["textItem"+i].textBox.background=true;
				_root["textItem"+i].textBox.border=true;
				_root["textItem"+i].textBox.backgroundColor=0xCCCCCC;
				_root["textItem"+i].textBox.text = configdata['text'][i];
			}
            //size characters to fill available width and height :
            my_fmt.size=129;
            do {
                actualTotalWidth=0;
                height=0;
                my_fmt.size--;
                for (var i=0; i<textBlockMovies.length; i++)
                {
                    _root[textBlockMovies[i]].textBox.setTextFormat(my_fmt);
                    _root[textBlockMovies[i]].textBox.autoSize='left';
                    actualTotalWidth+=_root[textBlockMovies[i]].textBox._width+5;
                    height=((_root[textBlockMovies[i]].textBox._height)>height)?(_root[textBlockMovies[i]].textBox._height):height;
                }
            }while (((actualTotalWidth > (Stage.width - 10)) || (height > maxHeight) )&& my_fmt.size > 10);
			startX=Math.round((Stage.width-actualTotalWidth) / 2);
			positionMovies();
		}
	}
	public function cleanUp()
	{
		_root['questionBox'].removeTextField();
		for (var movieIter in textBlockMovies)
		{
			_root[textBlockMovies[movieIter]].removeMovieClip();
		}
	}

	public function onEnterFrame()
	{
		var newTextBlockMovies=textBlockMovies.concat();//make a new copy of the contents of the array, not a copy by reference
		for (var movieIter in textBlockMovies)
		{
			if (!_root[textBlockMovies[movieIter]].dragging)
			{
				var diffX=_root[textBlockMovies[movieIter]]._x - _root[textBlockMovies[movieIter]].finalX;
				var diffY=_root[textBlockMovies[movieIter]]._y - _root[textBlockMovies[movieIter]].finalY;
				

				var rate;
				if (diffX ==0 || diffY==0)
				{
					rate=Math.round(1.41*_root[textBlockMovies[movieIter]].rate);
				} else
				{
					rate=_root[textBlockMovies[movieIter]].rate;
				}
				if (Math.abs(diffX) > rate)
				{
					_root[textBlockMovies[movieIter]]._x = (diffX > 0)? (_root[textBlockMovies[movieIter]]._x - rate) : (_root[textBlockMovies[movieIter]]._x + rate);
				} else if (diffX!=0)
				{
					_root[textBlockMovies[movieIter]]._x = _root[textBlockMovies[movieIter]].finalX;
				};
				if (Math.abs(diffY) > rate)
				{
					_root[textBlockMovies[movieIter]]._y = (diffY > 0)? (_root[textBlockMovies[movieIter]]._y - rate) : (_root[textBlockMovies[movieIter]]._y + rate);
				} else if (diffY!=0)
				{
					_root[textBlockMovies[movieIter]]._y = _root[textBlockMovies[movieIter]].finalY;
				};
		
			} else
			{
				var xLeft=-10000;
				var shiftTo=textBlockMovies.length;
				for (var movieIter2=0; movieIter2<textBlockMovies.length; movieIter2++)
				{
					var dragged=_root[textBlockMovies[movieIter]];
					var otherMovie=_root[textBlockMovies[movieIter2]];
					if (dragged.hitTest(otherMovie) &&
						dragged.getDepth() < otherMovie.getDepth())
					{
						dragged.swapDepths(otherMovie);
					};
					if (dragged._x >= xLeft && dragged._x < otherMovie.finalX ) 
					{
						shiftTo=movieIter2;
					}
					xLeft=otherMovie.finalX;
				};
				if (shiftTo!=movieIter)
				{
					var toShift=textBlockMovies[movieIter];
					if (shiftTo<movieIter)
					{
						newTextBlockMovies.splice(movieIter,1);//remove element
						newTextBlockMovies.splice(shiftTo, 0, toShift);
					} else
					{
						newTextBlockMovies.splice(movieIter,1);//remove element
						newTextBlockMovies.splice(shiftTo-1, 0, toShift);
					}
				}
				
			}
	
		}
	
		textBlockMovies=newTextBlockMovies;
		positionMovies();
	
	}
	public function answer() : Object
	{
		var seperate: String;
		freezeMovies();
		//send results to server :
		var toCheck=new Array();
		for (var movieIter=0; movieIter<textBlockMovies.length; movieIter++)
		{
			toCheck.push(_root[textBlockMovies[movieIter]].textBox.text);
		};
		if (seperator=='Character' ||
			seperator=='Multi Byte Character'  )
		{ 
			seperate='';
		} else
		{
			seperate=seperator;
		}
		var answerString=toCheck.join(seperate);
		return ({answer : answerString});
	}
	public function feedback(result : Array)
	{
		if (result[0])
		{
			_root.attachMovie("rightMC", "feedback", _root.newDepth++, {_x:Math.round((Stage.width-150)/2), _y:Math.round(Stage.height /2), _alpha:65});
		} else
		{
			_root.attachMovie("wrongMC", "feedback", _root.newDepth++, {_x:Math.round((Stage.width-70)/2), _y:Math.round(Stage.height /2)+20, _alpha:65});
		};
	}
	private function displayTextField(name : String, textToDisplay : String, x : Number , y : Number , width : Number , height : Number ) 
	{
		_root.createTextField(name, _root.newDepth++, x, y, width, height); 
		
		_root[name].wordWrap=true;
		_root[name].multiline=true;
		_root[name].html=true;	
		_root[name].background=false;
		_root[name].border=false;
		_root[name].htmlText=textToDisplay;
	}
	private function randRange(min:Number, max:Number):Number {
	  var randomNum:Number = Math.round(Math.random()*(max-min))+min;
	  return randomNum;
	}

	private function positionMovies()
	{
		var nextX=startX;
		for (var movieIter=0; movieIter<textBlockMovies.length; movieIter++)
		{
			_root[textBlockMovies[movieIter]].finalX=nextX;
			nextX=nextX+(_root[textBlockMovies[movieIter]].textBox._width)+5;
		}
	
	}
	
	private function freezeMovies()
	{
		for (var movieIter in textBlockMovies)
		{
			_root[textBlockMovies[movieIter]].onPress=function(){};
		}
	}
}