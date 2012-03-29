class Question {
	// parent class for all questions
	// never used itself, you need to extend this class
	public function Question(configdata : Array)
	{
		//constructor displays q
	}
	public function cleanUp()
	{
		//called to remove graphical elements from the stage after done 
		// with q
	}

	public function onEnterFrame()
	{
		//use it if you need it
	
	}
	public function answer() : Object
	{
		// return an answer object
		//can have more than one property, all props passed to moodle and stored
		// in db.
		//Answer checking done by php
		var answerString='';
		return ({answer : answerString});
	}
	public function feedback(result : Array)
	{
		if (result[0])
		{
			_root.attachMovie("rightMC", "feedback", _root.newDepth++, {_x:200, _y:200, _alpha:65});
		} else
		{
			_root.attachMovie("wrongMC", "feedback", _root.newDepth++, {_x:240, _y:220, _alpha:65});
		};
	}
}