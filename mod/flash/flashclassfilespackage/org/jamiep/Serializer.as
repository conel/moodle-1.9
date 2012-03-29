/*
 * THIS SOFTWARE IS PROVIDED "AS IS" AND ANY EXPRESSED OR IMPLIED WARRANTIES, INCLUDING,
 * BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A
 * PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE REGENTS OR CONTRIBUTORS BE
 * LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY,
 * WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING
 * IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * @license FREEWARE
 * @copyright (c) 2003 sephiroth.it
 * Modified by James Pratt me@jamiep.org
 *
 * SerializerClass is the base class for retreiving complex data type
 * from and to PHP pages using the built-in serializer/unserializer
 * php functions
 * Thanks to Rainer Becker [rainer.becker@pixelmotive.de] for the string bug fixing
*/
class org.jamiep.Serializer
{
	static var className:String = "org.jamiep.Serializer";
	static var classVersion:String = "2.1.4";
	public var instanceName:String = "Serializer";
	private var buffer:String;
	/**
	* @method serialize
	* @description serialize an actionscript object into a serialized string
	* @return String
	* @param what Object
	*/
	public function serialize(what:Object):String{
		var buffer:String = new String("");
		buffer = serialize_internal("", what);
		return buffer;
	}; // serialize
	
	// private method 'serialize_internal'
	private function serialize_internal(buf:String, struct_c:Object):String{
		if(!isNaN(struct_c) && struct_c.__proto__ != Boolean.prototype && struct_c.__proto__ != Date.prototype){
			var struct = new Number(struct_c)
		} else {
			var struct = struct_c
		}
		switch(struct.__proto__){
			case Boolean.prototype:
				buf = buf add "b:" add int(struct) add ";"
				break
			case null:
			case undefined:
				buf = buf add "N;"
				break
			case Number.prototype:
				if(struct >= 1e+15){
					buf = buf add "d:" add struct add ";"
				} else {
					if(struct%1==0){
						buf = buf add "i:" add struct add ";"
					} else {
						buf = buf add "d:" add struct add ";"
					}
				}
				break
			case String.prototype:
				buf = buf add "s:" add calcLength(struct) add ":\"" add struct add "\";"
				break;
			//case Function.prototype: this give a compiler error in flash 7.0.2
			case Date.prototype:
				buf = buf add "s:" add length(struct) add ":\"" add struct add "\";"
				break
			case Object.prototype:
			case Array.prototype:
				var c:Number = 0
				var s:String = new String("");
				if (struct.__proto__ == Array.prototype)
				{
					buf = buf add "a:"
				}
				else
				{
					buf = buf add "O:8:\"stdClass\":"		
				}
				for(var a in struct){
					s += this.serialize_internal("", a) add this.serialize_internal("", struct[a]);
					c += 1
				}
				buf = buf add c add ":{" add s add "}"
				break
			default:
				buf = buf add "i:0;"
		}
		return buf
	}; // serialize_internal
	
	private function calcLength(struct:String)
	{
		var c;
		var result=0;
		var l = struct.length;
		for (var i=0; i < l; i++)
		{
			c = (struct.charCodeAt(i));
			if(c<128) {
				result += 1;
			} else if (c<2048) {
				result += 2;
			} else {
				result += 3;
			}
		}
		return result;
	}
	
	// public method 'unserialize'
	public function unserialize(what:String):Object{
		this.buffer = what
		return unserialize_internal(what);
	}; // unserialize
	
	// private unserialize_internal
	private function unserialize_internal(obj:String):Object{
		var _type:String = obj.charAt(0);
		var _value:Array;
		switch(_type){
			case "d":
				_value = parse_double();
				break;
			case "i":
				_value = parse_int();
				break;
			case "b":
				_value = parse_boolean();
				break;
			case "s":
				_value = parse_string();
				break;
			case "a":
				_value = parse_array();
				break;
			case "O":
				_value = parse_object();
				break;
			case "N":
			default:
				break;
		}
		if(_value[0] != -1){
			this.buffer = this.buffer.substr (this.buffer.indexOf (";", _value[0]) + 1);
		}
		return _value[1];
	}; // unserialize_internal

	private function parse_array():Array{	
		var count:Number    = 0;
		var len:Number      = this.getArrayLength();
		var tempArray:Array = new Array();
		while (count < len) {
			var value:Array = getNext ();
			tempArray[value[0]] = value[1];
			count++;
		}
		this.buffer = this.buffer.substr (1);
		return new Array(-1, tempArray);
	}; // parse_array
	
	
	private function parse_object():Array{
		var obj_value:Array = this.parse_string();
		//search from length of the string to the next colon
		this.buffer = this.buffer.substr (this.buffer.indexOf (":", obj_value[0]));
		var obj_name:String = obj_value[1];
		var count:Number    = 0;
		var len:Number      = this.getObjectLength();
		var tempObject:Object = new Object();
		while (count < len) {
			var value:Array = getNext ();
			tempObject[value[0]] = value[1];
			count++;
		}
		this.buffer = this.buffer.substr (1);
		return new Array(-1, tempObject);
	}; // parse_object
	
	
	private function parse_string ():Array	{
		var len:Number = this.getStringLength ();
		var lenc:Number = this.getCStringLength ();
		if (len eq lenc){
			var value:String = this.buffer.substr (length (len) + 4, len).toString ();
		} else	{
			var value:String = this.buffer.substr (length (len) + 4, lenc).toString ();
		}
		return new Array (length (len) + 4 + lenc, value);
	};	// parse_string
	
	private function parse_boolean():Array{
		var len:Number = this.getLength();
		var value:Boolean = len < 1 ? false : true;
		return new Array(length(len.toString()), value);
	}; // parse_boolean
	
	private function parse_double():Array{
		var len:Number = this.getFloatLength();
		var value:Number = len
		return new Array(length(len.toString()), value);
	}; // parse_double
	
	private function parse_int():Array{
		var len:Number = this.getLength();
		var value:Number = len;
		return new Array(length(len.toString()), value);
	}; // parse_int
	
	private function getStringLength():Number{
		var len:Number = parseInt(this.buffer.substr(2, this.buffer.indexOf(":", 3) - 2));
		return len;
	}; // getStringLength
	
	// getCStringLength
	private function getCStringLength ():Number {
		var colon:Number = this.buffer.indexOf (":", 3); // index of second colon
		var ss:Number = colon+2; // index of start of string
		var len:Number = parseInt (this.buffer.substr (2, colon - 2));
		var i:Number;
		var j:Number = len; //char length of string to be calculated
		var c:Number;
		var cstr = this.buffer;
		for (i = ss; i < (ss + j); i++)
		{
			c=cstr.charCodeAt(i);
			if (c<128) {
				//do nothing
			}else if (c<2048) {
				j = j-1;
			} else {
				j = j-2;
			}
		}
		return j;
	};	// getCStringLength
	private function getLength():Number{
		var len:Number = parseInt(this.buffer.substr(2, this.buffer.indexOf(";", 3) - 2));
		return len;
	}; // getLength
	
	private function getFloatLength():Number{
		var len:Number = parseFloat(this.buffer.substr(2, this.buffer.indexOf(";", 3) - 2));
		return len;
	}; // getLength	
	
	private function getArrayLength():Number{
		var len:Number = parseInt (this.buffer.substr (2, (this.buffer.indexOf (':', 2) - this.buffer.indexOf (':', 1)) + 1));
		this.buffer = this.buffer.substr (this.buffer.indexOf(':', 2) + 2);
		return len;
	} // getArrayLength	

	private function getObjectLength():Number{
		var len:Number = parseInt(this.buffer.substr (1, this.buffer.indexOf (':', 1)));
		this.buffer = this.buffer.substr (this.buffer.indexOf (':', 1) + 2);
		return len;
	} // getObjectLength	
	
	private function getNext (str:String):Array {
		var value = this.unserialize_internal(this.buffer);
		var data  = this.unserialize_internal(this.buffer);
		return new Array(value, data);
	}; // getNext
}

