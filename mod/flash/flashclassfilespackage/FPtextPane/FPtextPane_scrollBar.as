//###########################################################
//#### Flashpushers Ltd http://www.flashpushers.net #########
//#### email mako@flashpushers.net ##########################
//###########################################################
class FPtextPane.FPtextPane_scrollBar extends MovieClip {
	//############ ATTACH ON THE FLY ###############################################
	static var symbolName:String = "__Packages.FPtextPane.FPtextPane_scrollBar";
	static var symbolOwner:Function = FPtextPane.FPtextPane_scrollBar;
	static var symbolLinked:Object = Object.registerClass(symbolName, symbolOwner);
	//############ ATTACH ON THE FLY ###############################################
	private var go, pagefire:Boolean;
	private var up, down, bbar, bmove,bscroll:MovieClip;
	private var act, dim:Number;
	private var pagedirection:String;
	//********************************************
	////constructor
	//********************************************
	function FPtextPane_scrollBar() {
		//
		this._visible=false;
		this.go = false;
		//scroll direction
		this.pagedirection="";
		//up
		this.createEmptyMovieClip("up", 3);
		this.up.createEmptyMovieClip("arrow", 1);
		//methods
		this.up.onPress = function() {
			clearInterval(this._parent.act);
			this._parent.pagefire=false;
			this._parent.act = setInterval(this._parent, "goUp", 500);
			this._parent.goUp();
			this._parent.pagefire=true;
		};
		//
		this.up.onRelease = this.up.onReleaseOutside=function () {
			clearInterval(this._parent.act);
		};
		//###########################################
		//down
		this.createEmptyMovieClip("down", 4);
		this.down.createEmptyMovieClip("arrow", 1);
		//methods
		this.down.onPress = function() {
			clearInterval(this._parent.act);
			this._parent.pagefire=false;
			this._parent.act = setInterval(this._parent, "goDown", 500);
			this._parent.goDown();
			this._parent.pagefire=true;
		};
		this.down.onRelease = this.down.onReleaseOutside=function () {
			clearInterval(this._parent.act);
		};
		//###########################################
		//bars
		this.createEmptyMovieClip("bbar", 1);
		//move up and down
		this.bbar.onPress=function(){
			//reset direction
			this._parent.pagedirection="";
			clearInterval(this._parent.act);
			this._parent.pagefire=false;
			this._parent.pageUpDown();
			this._parent.act = setInterval(this._parent, "pageUpDown", 500);
			this._parent.pagefire=true;
			//show scrollbar
			this._parent.bscroll._visible=true;
		}
		this.bbar.onRelease = this.bbar.onReleaseOutside=function () {
			clearInterval(this._parent.act);
			//
			this._parent.bscroll._visible=false;
		};
		//bars
		this.createEmptyMovieClip("bmove", 5);
		//
		this.bmove._y = 10;
		//bmove methods
		this.bmove.onPress = function() {
			this.startDrag(false, 0, 10, 0, this._parent.dim-this._height+2);
			this._parent.go = true;
		};
		this.bmove.onRelease = this.bmove.onReleaseOutside=function () {
			this.stopDrag();
			this._parent.go = false;
		};
		//shadow scroll color
		this.createEmptyMovieClip("bscroll",2);
		//color and draw elements
		this.recolor();
	}
	//********************************************
	//set initizl dimension
	//********************************************
	public function setProp(x:Number, y:Number, dim:Number):Void {
		this._x = x;
		this._y = y;
		this.dim = dim;
		//move down botton
		this.down._y = dim;
		//redraw the line
		this.bbar.clear();
		this.bbar.lineStyle(1,this._parent.componentStyle.contour,100);
		this.bbar.beginFill(this._parent.componentStyle.underbars, 100);
		this.bbar.moveTo(0, 0);
		this.bbar.lineTo(10, 0);
		this.bbar.lineTo(10,dim+10);
		this.bbar.lineTo(0, dim+10);
		this.bbar.lineTo(0, 0);
		this.bbar.endFill();
		//function for resize the bmove
		this.refreshDim();
	}
	//********************************************
	//refresh calling
	//********************************************
	private function onEnterFrame():Void {
		if (this.go) {
			this.refresh();
		}
	}
	//********************************************
	//reposition
	//********************************************
	public function repositionScroll():Void {
		if (!this.go) {
			//automatic reposition onScroll
			this.bmove._y = Math.round(((this._parent.btext.scroll-1)/((this._parent.btext.maxscroll-1)/(this.dim-this.bmove._height-8)))+10);
			//
		}
	}
	//*******************************************
	//reset
	//********************************************
	public function reset():Void {
		this.bmove._y = 10;
		this.repositionScroll();
		this.refreshDim();
	}
	//********************************************
	//refresh
	//********************************************
	public function refresh():Void {
		//move the content
		this._parent.btext.scroll = (this.bmove._y-10)*((this._parent.btext.maxscroll)/(this.dim-this.bmove._height-10))+1;
	}
	//********************************************
	//refresh dimension
	//********************************************
	public function refreshDim():Void {
		//move the content
		//check if the bars are to be visible or not
		if (((this.dim)/(this._parent.btext.textHeight-10))<1) {
			this._visible = true;
			//
			var d = Math.round(((this.dim)/this._parent.btext.textHeight)*(this.dim-10));
			//
			if (d<10) {
				d = 10;
			}
			this.bmove.clear();
			this.bmove.lineStyle(1, this._parent.componentStyle.contour, 100);
			this.bmove.beginFill(this._parent.componentStyle.bars, 100);
			this.bmove.moveTo(0, 0);
			this.bmove.lineTo(10, 0);
			this.bmove.lineTo(10, d);
			this.bmove.lineTo(0, d);
			this.bmove.lineTo(0, 0);
			this.bmove.endFill();
			//grip
			var middle=Math.round(d/2);
			this.bmove.moveTo(2,middle);
			this.bmove.lineTo(8, middle);
			this.bmove.moveTo(2,middle-2);
			this.bmove.lineTo(8, middle-2);
			this.bmove.moveTo(2,middle+2);
			this.bmove.lineTo(8, middle+2);
			//
			//restore the right position if text is setted or added
			if (this._parent.lastfirst) {
				trace("ok");
				//move text to last line
				this._parent.btext.scroll=this._parent.btext.maxscroll;
			} else {
				this.bmove._y = 10;
			}
			this.repositionScroll();
			//####################
		} else {
			this.bmove._y = 10;
			this._visible = false;
			this.repositionScroll();
		}
	}
	//********************************************
	//move with arrow down
	//********************************************
	private function goDown():Void {
		//
		if (this._parent.btext.scroll<this._parent.btext.maxscroll) {
			this._parent.btext.scroll++;
		} else {
			clearInterval(this.act);
		}
		//change fire
		if (this.pagefire){
			clearInterval(this.act);
			this.act = setInterval(this, "goDown", 50);
		}
		//repositionScroll
		this.repositionScroll();
	}
	//********************************************
	//move with arrow up
	//********************************************
	private function goUp():Void {
		if (this._parent.btext.scroll>0) {
			this._parent.btext.scroll--;
		} else {
			clearInterval(this.act);
		}
		//change fire
		if (this.pagefire){
			clearInterval(this.act);
			this.act = setInterval(this, "goUp", 50);
		}
		//repositionScroll
		this.repositionScroll();
	}
	//********************************************
	//emulate move with arrow up/down passing current pointer position
	//********************************************
	private function pageUpDown():Void {
		//check is have to go or not
		var point={x:_root._xMouse, y:_root._yMouse};
		this.globalToLocal(point);
		point.y=Math.round(point.y);
		//
		var pagerows=this._parent.btext.bottomScroll-this._parent.btext.scroll;
		//move only if mouse pointer is not over the bmove
		if (this.bmove._visible && (point.y<this.bmove._y || point.y>(this.bmove._y+this.bmove._height))){
			//
			if ((point.y<this.bmove._y) && (this.pagedirection=="up" ||  this.pagedirection=="") ){
				//set direction
				this.pagedirection="up" ;
				//move up
				if (this._parent.btext.scroll>0){
					this._parent.btext.scroll-=pagerows;
				}else{
					this._parent.btext.scroll=0;
					clearInterval(this.act);
				}
				//change fire
				if (this.pagefire){
					clearInterval(this.act);
					this.act = setInterval(this, "pageUpDown", 50);
				}
				//repositionScroll
				this.repositionScroll();
				//draw bscroll
				this.drawBscroll(10,this.bmove._y);
				//
			}else if ((point.y>(this.bmove._y+this.bmove._height)) && (this.pagedirection=="down" ||  this.pagedirection=="")){
				//set direction
				this.pagedirection="down";
				var pagerows=this._parent.btext.bottomScroll-this._parent.btext.scroll;
				//
				if ( this._parent.btext.scroll< this._parent.btext.maxscroll){
					this._parent.btext.scroll+=pagerows;
				}else{
					this._parent.btext.scroll=this._parent.btext.maxscroll;
					clearInterval(this.act);
				}
				//change fire
				if (this.pagefire){
					clearInterval(this.act);
					this.act = setInterval(this, "pageUpDown", 50);
				}
				//repositionScroll
				this.repositionScroll();
				//draw bscroll
				this.drawBscroll(this.bmove._y,this.dim);
				//
			}else{
				this.pagedirection="stop";
				this.bscroll._visible=false;
				clearInterval(this.act);
			}
		}else{
			this.pagedirection="stop";
			this.bscroll._visible=false;
			clearInterval(this.act);
		}
	}
	//recolor
	private function recolor():Void {
		//up
		this.up.clear();
		this.up.lineStyle(1, this._parent.componentStyle.contour, 100);
		this.up.beginFill(this._parent.componentStyle.bars, 100);
		this.up.moveTo(0, 0);
		this.up.lineTo(10, 0);
		this.up.lineTo(10, 10);
		this.up.lineTo(0, 10);
		this.up.lineTo(0, 0);
		this.up.endFill();
		//
		this.up.arrow.beginFill(this._parent.componentStyle.contour, 100);
		this.up.arrow.moveTo(2, 8);
		this.up.arrow.lineTo(8, 8);
		this.up.arrow.lineTo(5, 2);
		this.up.arrow.lineTo(2, 8);
		this.up.arrow.endFill();
		//down
		this.down.clear();
		this.down.lineStyle(1, this._parent.componentStyle.contour, 100);
		this.down.beginFill(this._parent.componentStyle.bars, 100);
		this.down.moveTo(0, 0);
		this.down.lineTo(10, 0);
		this.down.lineTo(10, 10);
		this.down.lineTo(0, 10);
		this.down.lineTo(0, 0);
		this.down.endFill();
		//
		this.down.arrow.beginFill(this._parent.componentStyle.contour, 100);
		this.down.arrow.moveTo(2, 2);
		this.down.arrow.lineTo(8, 2);
		this.down.arrow.lineTo(5, 8);
		this.down.arrow.lineTo(2, 2);
		this.down.arrow.endFill();
		//bars
		this.bbar.clear();
		this.bbar.lineStyle(1,this._parent.componentStyle.contour,100);
		this.bbar.beginFill(this._parent.componentStyle.underbars, 100);
		this.bbar.moveTo(0, 0);
		this.bbar.lineTo(10, 0);
		this.bbar.lineTo(10,dim+10);
		this.bbar.lineTo(0, dim+10);
		this.bbar.lineTo(0, 0);
		this.bbar.endFill();
		//
		this.refreshDim();
	}
	//draw bscroll
	private function drawBscroll(offs:Number,offe:Number):Void {
		this.bscroll.clear();
		this.bscroll.beginFill(this._parent.componentStyle.contour,100);
		this.bscroll.moveTo(0,offs);
		this.bscroll.lineTo(10,offs);
		this.bscroll.lineTo(10,offe);
		this.bscroll.lineTo(0,offe);
		this.bscroll.lineTo(0,offs);
		this.bscroll.endFill();
	}
	//********************************************
	//reposition
	//********************************************
	private function reposition():Void {
		//clear interval movements
		clearInterval(this.act);
		this.repositionScroll();
	}
}
