/*
================================================================================

PHPObject - An Opensource Alternative to Flash Remoting

Copyright (C) 2003-2004  Sunny Hong | http://ghostwire.com
 		Modified by James Pratt 	| http://jamiep.org

This library is free software; you can redistribute it and/or
modify it under the terms of the GNU Lesser General Public
License as published by the Free Software Foundation; either
version 2.1 of the License, or (at your option) any later version.

This library is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
Lesser General Public License for more details.

You should have received a copy of the GNU Lesser General Public
License along with this library; if not, write to the Free Software
Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

License granted only if full copyright notice retained.

================================================================================

>> Please read documentation <<

================================================================================
*/

import org.jamiep.phpobject.PHPLoader
import org.jamiep.Serializer

class org.jamiep.phpobject.PHPObject
{

	private var wsdlLoc:String;
	private var _data:Array;
	private var _loader:PHPLoader;

	private static var __defaultGatewayKey;
	private static var __defaultGatewayUrl;

	// **************************
	// responders
	// can't leave them undefined
	// **************************
	public var onAbort:Function	= function(){};
	public var onError:Function	= function(i:Number,e:String):Void
	{
		trace(i+":"+e);
		// code 0: connection failure
		// code 1: server side error
		// code 2: client side error (currently happens only if method called does not exist in method list)
	}
	public var onInit:Function	= function(){};
	public var onResult:Function	= function(){};
	public var onOutput:Function	= function(o:String){};
	
	// ****************************************************
	// constructor
	// ****************************************************
	// @param1 string 'classname'
	// @param2 optional string 'gateway path'
	// @param3 optional boolean 'retrieve properties flag'
	// @param4 optional array 'initialization params'
	// ****************************************************
	public function PHPObject()
	{
		_data	= new Array();
		if (arguments.length)
		{
			init.apply(this,arguments);
		}
	}
	
	// **************************
	// init
	// **************************
	public function init()
	{
		_loader	= new PHPLoader();
		// ** _data is an array containing directives for server **
		// ** _data[0] integer: taskid
		_data[0]=0;
		// ** _data[1] string: service name
		_data[1]="";
		// ** _data[2] array: methods to invoke
		_data[2]=[];
		// ** _data[3] array: params to pass
		_data[3]=[];
		// ** _data[4] string: gateway key
		_data[4]=(_data[4]==undefined) ? "" : _data[4];
		// ** _data[5] array: credentials
		_data[5]=(_data[5]==undefined) ? "" : _data[5];
		// ** _data[6] boolean: (not in use) was a flag to indicate whether to utf8encode 
		_data[6]=false;
		// ** _data[7] boolean: flag to indicate whether to retrieve properties
		// ** _data[8] array: initialization params
		// ** _data[9] string : gateway object session token must be passed to PHP
		// ** _data[10] boolean : clean_up - delete session vars after this service call

		var svc:String = arguments[0].toString();
		// ** if service is an absolute url, we assume it is web service **
		if ((svc.indexOf("http://") == 0) || (svc.indexOf("https://") == 0))
		{
			_data[1]	= "WebServiceProxy";
			wsdlLoc	= svc;
		}
		else
		{
			if (svc!=undefined)
			{
				_data[1]	= svc;
			} else
			{
				_data[1] = "usingcredentials";
			}
			if (arguments[1].length)
			{
				_loader['gateway']	= arguments[1].toString();
			}
			// ** pass in a third parameter if you don't want to retrieve properties **
			if (arguments[2])
			{
				_data[7] = 1;
			}
			if (arguments[3]!=undefined)
			{
				_data[8] = arguments[3];
			}
		}
		// ** connects to the remote object and gets default properties **
		if ((_data[1] != undefined) )
		{
			this[_data[1]]();
		}
	}
	
	// **************************
	// public methods
	// **************************
	// abortExecute
	// **************************
	public function abortExecute():Void
	{
		// ** reset **
		_data[2] = [];
		_data[3] = [];
		if (_loader['isBusy'])
		{
			_loader['id']++;
			_loader['isBusy'] = false;
			_loader['refObj'].onAbort();
		}
	}
	
	// **************************
	// delayExecute
	// **************************
	public function delayExecute():Void
	{
		_loader['delay'] = true;
	}
	
	// **************************
	// execute
	// **************************
	public function execute():Void
	{
		if (_loader['delay'] == true)
		{
			_loader['delay'] = false;
			if (_data[2].length)
			{
				__connect(_data[2][_data[2].length-1]);
			}
		}
	}
	
	// **************************
	// getBytesLoaded
	// **************************
	public function getBytesLoaded()
	{
		if (_loader['isBusy'])
		{
			return _loader.getBytesLoaded();
		}
		else
		{
			return false;
		}
	}
	
	// **************************
	// getBytesTotal
	// **************************
	public function getBytesTotal()
	{
		if (_loader['isBusy'])
		{
			return _loader.getBytesTotal();
		}
		else
		{
			return false;
		}		
	}
	
	// **************************
	// getOutput
	// **************************
	public function getOutput():String
	{
		return _loader['output'];
	}

	// **************************
	// setCredentials
	// **************************
	public function setCredentials():Void
	{
		_data[5] = arguments;
	}

	// **************************
	// setGateway
	// **************************
	public function setGateway(u:String):Void
	{
		_loader['gateway'] = u;
	}
	
	// **************************
	// setKey
	// **************************
	public function setKey(k:String):Void
	{
		_data[4] = k;
	}
	// **************************
	// setCleanUp
	// **************************
	public function setCleanUp(k:Boolean):Void
	{
		_data[10] = k;
	}

	// **************************
	// setService
	// **************************
	public function setService():Void
	{
		init.apply(this,arguments);
	}
	

	// **************************
	// getter/setter
	// **************************
	public static function get defaultGatewayKey():String
	{
		return __defaultGatewayKey;
	}

	public static function set defaultGatewayKey(k:String):Void
	{
		__defaultGatewayKey = k;
	}

	public static function get defaultGatewayUrl():String
	{
		return __defaultGatewayUrl;
	}

	public static function set defaultGatewayUrl(u:String):Void
	{
		__defaultGatewayUrl = u;
	}
	
	// **************************
	// private methods
	// **************************
	private function __resolve(method)
	{
		// ** Watch for undefined _onResult responders **
		if (method.indexOf("_onResult") != -1)
		{
			return this.onResult;
		}
	
		// ** Watch for undefined _onError responders **
		if (method.indexOf("_onError") != -1)
		{
			return this.onError;
		}
	
		// ** Check if remote method exist **
		var methodExists:Boolean = true;
		if ( (_data[1] == "usingcredentials" && method!="usingcredentials")||(typeof(method) != "number") && (_loader['classMethods'][_data[1]]) && (_data[1] != "WebServiceProxy") )
		{
			if ((_loader['classMethods'][method.toLowerCase()] == undefined)) // ** 'classMethods' contains lowercase methodnames **
			{
				methodExists = false;
			}
		}
	
		if (methodExists)
		{
			// [START FUNCTION BLOCK]
			return function():Boolean
			{
				if ( (!_loader['isBusy']) || (_loader['delay']) )
				{
					// ** Watch for web service connector first ** 
					if ( (_data[1] == "WebServiceProxy")&&(method != "call") &&(method != "WebServiceProxy") )
					{
						var operation:String	= method;
						var params:Array		= [];
						var x = arguments.length;
						for (var i=0; i < x; i+=2)
						{
							params[arguments[i]] = arguments[i+1];
						}
						return this.call(operation,params);
					}
					// ** store method,param in array **
					if ( (method != undefined) && (method != _data[1]) )
					{
						var m = ( (typeof(method) == "number") || (!_loader['classMethods'][_data[1]]) ) ?
								method :
								_loader['classMethods'][method.toLowerCase()];	// ** convert string to integer id, lower case for case-insensitive lookup **

						_data[2].push(method);
						_data[3].push(arguments);
					}
					// ** if delay, we stop here **
					if (_loader['delay'])
					{
						return true;
					}
					// ** else we connect **
					return __connect(method);
				}
				return false;
			}
			// [END FUNCTION BLOCK]
		}
		else
		{
			onError(2,"Error - Method '" + method + "' not found");
		}
	
		return undefined;
		// ** before object is initialized, undeclared properties return as [function] **
		// ** after object init, undeclared properties return as undefined **

	}
	
	private function __connect(method):Boolean
	{
		// ** check if gateway exists, if not let's use the defaultGatewayUrl **
		_loader['gateway'] = (_loader['gateway'] == "") ? __defaultGatewayUrl : _loader['gateway'];
		// ** we proceed only if gateway exists **
		if (_loader['gateway'].length)
		{
			// ** prepare the _loader object to receive response from the gateway **
			_loader['refObj'] = this;
			// ** get the key **
			_data[4] = (_data[4] == "") ? __defaultGatewayKey : _data[4];
			// ** get task id **
			_data[0] = _loader['id'];
			_data[9] = _loader['obj_sess_token'];
			// ** send to server **
			_loader.send(this,method);
			// ** reset **
			_data[2] = [];
			_data[3] = [];
			// ** indicate success **
			return true;
		}
		else
		{
			return false;			
		}
	}
	
}
