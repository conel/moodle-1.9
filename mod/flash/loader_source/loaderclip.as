function checkLoad(obj) {
	if (holder=='l')
	{
		target=_level1;
	};

	var lBytes = target.getBytesLoaded();
	var tBytes = target.getBytesTotal();
	target._visible=visible;
	
	var percentLoaded :Number= Math.floor((lBytes/tBytes)*100);
	bar._width = percentLoaded;
	if (tBytes>0 && !isNaN(percentLoaded))
	{
		percent.text = percentLoaded+"% of "+Math.floor(tBytes/1024)+"KB  loaded.";
	}else 
	{
		percent.text =("Requesting File  ..");
		
	} 
	if (lBytes>=tBytes && tBytes>0) {
		clearInterval(checkProgress);
		_parent[loadExit]();
		obj.removeMovieClip();
		moodleDebugger.addText(fileURL+" loaded successfully!");
	}
	updateAfterEvent();
}
checkLoad(this);
checkProgress = setInterval(checkLoad, 50,this);
stop();
