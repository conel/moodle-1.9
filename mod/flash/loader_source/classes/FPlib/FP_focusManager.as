//###########################################################
//#### Flashpushers Ltd http://www.flashpushers.net #########
//#### email mako@flashpushers.net ##########################
//###########################################################
class FPlib.FP_focusManager{
	//vars
	static var objList:Array=new Array();
	//vars
	function FP_focusManager(){
	}
	//add a listener child
	static function addListener(listener:Object):Void{
		objList.push(listener);
		//trace("[FPfocusManager:focus: "+listener+"]");
	}
	//remove a listener child
	static function removeListener(listener:Object):Void{
		for (var i=0;i<objList.length;i++){
			if (listener==objList[i]){
				objList.splice(i,1);
			}
		}
	}
	//reset fucus
	static function resetFocus():Void{
		for (var i=0;i<objList.length;i++){
			objList[i].resetFocus();
		}
	}
}
