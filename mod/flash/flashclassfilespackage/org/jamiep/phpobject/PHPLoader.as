// **************************
// PHPLoader
// **************************
// An internal class for 
// storing connection info
// For use with PHPObject
// **************************

import org.jamiep.Serializer;

class org.jamiep.phpobject.PHPLoader extends LoadVars
{
	public var classMethods:Array	= [];
	public var delay:Boolean	= false;
	public var gateway:String	= "";
	public var id:Number		= 0;
	public var isBusy:Boolean	= false;
	
	public var serializer:Serializer;

	private var refObj;
	private var refObjSnapShot;
	
	public function PHPLoader()
	{
		this.serializer = new Serializer();
	}
	
	public function onData(s:String):Void
	{
		isBusy = false;
		if (s!=undefined)
		{
			var i = s.substr(0,s.indexOf("O")); // ** get the id sent by server **
			if ((i == id) || (i == 0))
			{
				// ** check that return message is for current task id **
				parse(s.substr(i.length),i);
			}
			// ** else silently discard, must be because task aborted **			
		}
		else
		{
			// ** connection failure **
			refObj.onError(0,"Connection Failure");
		}
	}

	private function parse(s, i)
	{
		s		= this.serializer.unserialize(s);
		//copy properties sent from server into refObj
		unpack(s,refObj);
		//store a copy of refObj to compare the changed version with later so that we
		//can only send changes back to the host
		refObjSnapShot=copy(refObj);
		if (this['output'].length)
		{
			refObj.onOutput(this['output']);
		}
		if (this['serverError'].length)
		{
			refObj.onError(1,this['serverError']); // ** server side error **
		}
		else if (i==0)
		{
			refObj.onInit();
		}
		else
		{
			for (j in this['serverResults'])
			{
				methodName=this['serverResults'][j]['method'];
				refObj[methodName + "_onResult"](this['serverResults'][j]['result']);
			}
		}
		
	}

	private function unpack(s:Object,d:Object):Void
	{
		for (var i in s)
		{
			if ( i == "_loader" )
			{
				// ** the instance is named _loader **
				for (var j in s[i])
				{
					// ** we only do this one level deep **
					d[i][j] = s[i][j];
					if (j == "classMethods") // ** this happens only for the initialization call **
					{
						// ** flag - indicates 'classMethods' has been loaded **
						d[i][j][d['_data'][1]] = true;
					}
				}
			}
			else
			{
				d[i] = s[i];
			}
		}
	}
	
	private function pack(o:Object):Object
	//return an object with copies / array and object references pointing to all of o's properties other than the _loader property.
	//don't copy any functions
	{
		var	s = {};
		for (var i in o)
		{
			if (( i != "_loader" ) && (typeof(o[i]) != "function"))
			{
				s[i] = o[i];
			}
		}
		return s;
	}
	private function copy(o:Object):Object
	//return a copy of a complex data structure
	//this routine is necessary since normally in Flash
	//arrays and objects are copied by reference
	{
		if (o.__proto__ == Array.prototype)
		{
			var s= new Array();
			
		} else
		{
			var s = new Object();
		}
		for (var i in o)
		{
			if (i !='_loader')
			{
					
				if (typeof(o[i])=="object" || typeof(o[i])=="movieclip " )
				{
					s[i] = copy(o[i]);
				} else if (typeof(o[i])!="function" )
				{
					s[i]=o[i];
				}
			}
		}
		return s;
	}
	private function diff(newO:Object, oldO:Object):Object
	{
		if (newO.__proto__ == Array.prototype)
		{
			var s= new Array();
			
		} else
		{
			var s = new Object();
		}
		for (var i in newO)
		{
			if (typeof(newO[i])=="object" || typeof(newO[i])=="movieclip " )
			{
				if (is_diff(newO[i], oldO[i]))
				{
					s[i] = newO[i];
				}
			} else
			{
				if (newO[i]!=oldO[i]) {s[i]=newO[i]};
			}
		}
		return s;
	}
	private function is_diff(newO:Object, oldO:Object):Boolean
	//comparison needs to be done recursively since Flash can't compare complex 
	//structures with '!=' for example
	{
		for (var i in newO)
		{
			if (typeof(newO[i])=="object" || typeof(newO[i])=="movieclip " )
			{
				if (is_diff(newO[i], oldO[i]))  {return true};
			} else
			{
				if (newO[i]!=oldO[i]) {return true};
			}
		}
		return false;
	}
	

	public function send(o:Object,m:String)
	{
		o		= pack(o);
		tmp=copy(o._data);
		
		//only send what has changed back to the server
		o		= diff(o, refObjSnapShot);
		
		//we need to send these every time though
		o._data=tmp; 
		if (o._data[0]!=0) //if the object has already been instantiated :
		{
			//don't send again :
			delete(o._data[8]);//constructor params
			delete(o._data[1]);//service name
		}
			
		
		var s	= new XML();	
		// ** serialize the object and send to the gateway **
		s.dataToSend=this.serializer.serialize(o);
		s.toString = function () {return this.dataToSend;};
		s.contentType	= "text/plain";
		s.sendAndLoad(gateway,this,"POST");
		if (o._data[1] != m) // ** method name **
		{	// ** we don't hog the line if just initing the object **
			isBusy	= true;
		}
		else
		{
			id++;	// ** same as setting to 1, indicates that init has begun **
		}
	}
	
}
