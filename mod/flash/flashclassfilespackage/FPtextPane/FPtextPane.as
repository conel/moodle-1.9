//###########################################################
//#### Flashpushers Ltd http://www.flashpushers.net #########
//#### email mako@flashpushers.net ##########################
//###########################################################
import FPlib.FP_focusManager;
import FPtextPane.FPtextPane_scrollBar;
//
class FPtextPane.FPtextPane extends MovieClip {
	//############ ATTACH ON THE FLY ###############################################
	static var symbolName:String = "__Packages.FPtextPane.FPtextPane";
	static var symbolOwner:Function = FPtextPane.FPtextPane;
	static var symbolLinked:Object = Object.registerClass(symbolName, symbolOwner);
	//############ ATTACH ON THE FLY ###############################################
	private var w, h, wo, ho, woriginal, horiginal, moview, movieh, quantizerstep, mouseWheelStep:Number;
	private var resized, resizable, drag, draggable, horizontalVisible, verticalVisible, lastfirst, textfocus, outline:Boolean;
	private var contour_c, underbars_c, bars_c, mscroll:Number;
	private var body, sco, scv, resizer, content_mc:MovieClip;
	private var componentStyle, focusmanager, HTFtarget_mc:Object;
	private var HTFcallback:String;
	//
	public var btext:TextField;
	function FPtextPane() {
		//grab the dimensions
		this.w = this._width-10;
		this.h = this._height;
		//reset the scale
		this._xscale = this._yscale=100;
		//last or fist mode
		this.lastfirst = false;
		//set the ddefault colors********************************************
		this.componentStyle = {contour:this.contour_c, underbars:this.underbars_c, bars:this.bars_c};
		//box focus var
		this.textfocus=false;
		//this.init
		this.init();
	}
	//
	//********************************************
	//// inspectable properties
	//********************************************
	//lastfirst
	[ Inspectable(name="lastfirst" type="Boolean", defaultValue="true" ) ]
	public function set prop_lastfirst(prop:Boolean) {
		this.lastfirst = prop;
	}
	public function get prop_lastfirst():Boolean {
		return (this.lastfirst);
	}
	//outline color
	[ Inspectable(name="contour/arrows/resizer" type="Color", defaultValue="#666666" ) ]
	public function set prop_style_c(prop:Number) {
		this.contour_c = prop;
	}
	public function get prop_style_c():Number {
		return (this.contour_c);
	}
	//underbars color
	[ Inspectable(name="underbars" type="Color", defaultValue="#aaaaaa" ) ]
	public function set prop_style_b(prop:Number) {
		this.underbars_c = prop;
	}
	public function get prop_style_b():Number {
		return (this.underbars_c);
	}
	//bars color
	[ Inspectable(name="scrollbars" type="Color", defaultValue="#dddddd" ) ]
	public function set prop_style_r(prop:Number) {
		this.bars_c = prop;
	}
	public function get prop_style_r():Number {
		return (this.bars_c);
	}
	//
	private function init():Void {
		//focusmanager
		this.createEmptyMovieClip("focusmanager",100);
		this.focusmanager.onRollOver=function():Void{
			//call focusmanager
			FP_focusManager.resetFocus();
			//set focus
			this._parent.focus=true;
			this._visible=false;
		}
		//reset the status
		this.clear();
		//attach scrollers
		var init = {orizontal:false};
		this.attachMovie(FPtextPane_scrollBar.symbolName, "scv", 2, init);
		//init the scrollers
		this.scv.setProp(this.w-10, 0, this.h-11);
		//create the container
		this.createTextField("btext", 1, 0, 0, this.w-12, this.h-3);
		this.btext.html = true;
		this.btext.wordWrap = true;
		this.btext.multiline = true;
		if(this.outline){
			this.btext.border = true;
			this.btext.borderColor = this.componentStyle.bars;
		}		
		this.btext.onSetFocus=function(prevFocus)
		{
			this._parent.onSetFocus(prevFocus);
		}
		//on scroller Handler
		this.btext.onScroller = function() {
			//refresh scrollers
			this._parent.scv.repositionScroll();

		};
	}
	//init component by method
	public function initComponent(style:Object,w:Number,h:Number, outline:Boolean, mouseweel:Boolean):Void{
		//
		this.w=w;
		//add text spacing!!!!!!!!!!!!!!
		this.h=h;
		//for the resizer
		this.woriginal = this.w;
		this.horiginal = this.h;
		//
		/*this.mouseWheelEnabled=mouseweel;
		this.mouseWheelStep=mouseWheelStep;
		this.quantizerstep=quantizerstep;
		//
		if (resizable!=undefined){
			this.resizable=resizable;
		}
		//set style*/
		//
		this.outline=outline;

		//
		this.componentStyle =style;
		//init
		this.init();
	}
	//set and load the content
	public function setText(texto:String):Void {
		//init the dimension of the content used from the scrollbars to reseze themselves
		this.btext.htmlText = texto;
		//reset the scrollers
		this.scv.reset();
		//last or fist mode
		this.lastfirst = false;
	}
	//
	public function addText(texto:String):Void {
		//init the dimension of the content used from the scrollbars to reseze themselves
		this.btext.htmlText += texto;
	}
	//
	public function clear():Void {
		//clear text container
		this.btext.htmlText = "";
		//reset the scrollers
		this.scv.reset();
		//
	}
public function textSubstring(first, last):String {
		return this.btext.text.substring(first, last);
	}

public function resize(w,h):Void {
		this.w=w;
		//add text spacing!!!!!!!!!!!!!!
		this.h=h;
		this.scv.setProp(this.w-10, 0, this.h-11);
		this.btext._width=this.w-12;
		this.btext._height=this.h-3;
	};
		
	//automatically refresh the scrollbars dimensions
	private function onEnterFrame():Void {
		//refresh resizer if movie clip contained change dimensions
		if (this.btext.maxscroll != this.mscroll) {
			//refresh scrollers
			this.scv.refreshDim();
			//
			this.mscroll = this.btext.maxscroll;
		}
	}
	//goBottom
	public function set goBottom(val:Boolean):Void{
		this.lastfirst=val;
	}
	public function get goBottom():Boolean{
		return(this.lastfirst);
	}
	//callBack
	public function setHTFCallback(reference:Object, callback:String):Void {
		this.HTFcallback = callback;
		this.HTFtarget_mc = reference;
	}
	//HTF callback execution
	private function HTF(param:String):Void {
		if (this.HTFcallback != undefined) {
			this.HTFtarget_mc[this.HTFcallback](param);
		}
	}
}
