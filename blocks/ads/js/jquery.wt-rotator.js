;(function($) {
	var rotator;
	
	$.fn.updateTransition = function(transition) {
		rotator.updateTransition(transition);
	}
	
	$.fn.updateCpAlign = function(align) {
		rotator.updateCPanel(align);
	}
	
	$.fn.displayThumbs = function(display) {
		rotator.showThumbs(display);
	}
	
	$.fn.displayDButtons = function(display) {
		rotator.showDButtons(display);
	}
	
	$.fn.displayPlayButton = function(display) {
		rotator.showPlayButton(display);
	}
	
	$.fn.displayTooltip = function(display) {
		rotator.showTooltip(display);
	}
	
	$.fn.updateMouseoverCP = function(mouseover) {
		rotator.setMouseoverCP(mouseover);
	}
	
	$.fn.updateMouseoverDesc = function(mouseover) {
		rotator.setMouseoverDesc(mouseover);
	}
	
	$.fn.wtRotator = function(params) {
		var TOP_LEFT = "TL";
		var TOP_RIGHT = "TR";
		var BOTTOM_LEFT = "BL";
		var BOTTOM_RIGHT = "BR";		
	
		var TRANSITIONS = new Array(33);		
		TRANSITIONS["fade"] 		= 0;		
		TRANSITIONS["block.top"] 	= 1;
		TRANSITIONS["block.right"] 	= 2;
		TRANSITIONS["block.bottom"]	= 3;
		TRANSITIONS["block.left"] 	= 4;		
		TRANSITIONS["block.drop"]  	= 5;		
		TRANSITIONS["diag.fade"] 	= 6;
		TRANSITIONS["diag.exp"] 	= 7;
		TRANSITIONS["diag.fade.exp"] = 8;
		TRANSITIONS["block.fade"] 	 = 9;
		TRANSITIONS["block.exp"] 	= 10;		
		TRANSITIONS["vert.tl"] 		= 11;
		TRANSITIONS["vert.tr"] 		= 12;
		TRANSITIONS["vert.bl"] 		= 13;
		TRANSITIONS["vert.br"] 		= 14;		
		TRANSITIONS["fade.left"] 	= 15;	
		TRANSITIONS["fade.right"]	= 16;		
		TRANSITIONS["alt.left"]     = 17;
		TRANSITIONS["alt.right"]    = 18;
		TRANSITIONS["blinds.left"]  = 19;
		TRANSITIONS["blinds.right"] = 20;		
		TRANSITIONS["horz.tl"] 		= 21;
		TRANSITIONS["horz.tr"] 		= 22;		
		TRANSITIONS["horz.bl"] 		= 23;
		TRANSITIONS["horz.br"] 		= 24;		
		TRANSITIONS["fade.top"] 	= 25;
		TRANSITIONS["fade.bottom"]	= 26;
		TRANSITIONS["alt.top"]      = 27;
		TRANSITIONS["alt.bottom"]   = 28;
		TRANSITIONS["blinds.top"]   = 29;
		TRANSITIONS["blinds.bottom"]= 30;		
		TRANSITIONS["none"] 		= 31;
		TRANSITIONS["random"] 		= 32;
		
		var DEFAULT_DELAY = 5000;
		var TOOLTIP_DELAY = 800;
		var INTERVAL_DELAY = 100;
		var TRANSPEED = 800;
		
		//Vertical Stripes
		function VertStripes(rotator, w, h, size, bgColor, tranSpeed) {
			var areaWidth = w;
			var areaHeight = h;
			var stripeSize = size;
			var stripeArr;
			var $stripes;
			var intervalId = null;
			
			//init stripes
			var init = function() {			
				stripeArr = new Array(Math.ceil(areaWidth/stripeSize));
				
				var divs = "";
				for (var i = 0; i < stripeArr.length; i++) {
					divs += "<div class='vpiece' id='" + i + "'></div>";
				}					
				rotator.addToScreen(divs);
				
				$stripes = $("div.vpiece", rotator.$el);
				$stripes.each(
					function(n) {
						stripeArr[n] = $(this).css({left: (n * stripeSize), 
													 width: stripeSize, 
													 height: areaHeight});
					}
				);	
			}

			//clear animation
			this.clear = function() {
				clearInterval(intervalId);
				$stripes.stop().css({"z-index":2, opacity:0});
			}

			//display content
			this.displayContent = function(newImg, tran) {
				setPieces(newImg, tran);
				animate(newImg, tran);
			}			
			
			//set image stripes
			var setPieces = function(newImg, tran) {
				if (tran == TRANSITIONS["vert.tl"] || tran == TRANSITIONS["vert.tr"]) {
					setVertPieces(newImg, -areaHeight, 0, stripeSize);
				}
				else if (tran == TRANSITIONS["vert.bl"] || tran == TRANSITIONS["vert.br"]) {
					setVertPieces(newImg, areaHeight, 0, stripeSize);
				}
				else if (tran == TRANSITIONS["alt.left"] || tran == TRANSITIONS["alt.right"]) {
					setAltVertPieces(newImg, 0);
				}
				else  if (tran == TRANSITIONS["blinds.left"] || tran == TRANSITIONS["blinds.right"]) {
					setVertPieces(newImg, 0, 1, 0);
				}
				else {
					setVertPieces(newImg, 0, 0, stripeSize);
				}
			}
			
			//set vertical stripes
			var setVertPieces = function(newImg, topPos, opacity, size) {
				var newImgSrc = newImg.src;
				var tOffset = (areaHeight - newImg.height)/2;
				var lOffset = (areaWidth - newImg.width)/2;
				for (var i = 0; i < stripeArr.length; i++) {		
					var xPos =  ((-i * stripeSize) + lOffset);
					$(stripeArr[i]).css({
							"background":bgColor + " url('"+ newImgSrc +"') " + 
							xPos + "px " + tOffset + "px no-repeat",
							"backgroundPositionX":xPos + "px",
							"backgroundPositionY":tOffset + "px",
							opacity:opacity, top:topPos, width:size, "z-index":3});						
				}
			}
			
			//set alternative pos vertical stripes
			var setAltVertPieces = function(newImg, opacity) {
				var newImgSrc = newImg.src;
				var tOffset = (areaHeight - newImg.height)/2;
				var lOffset = (areaWidth - newImg.width)/2;
				for (var i = 0; i < stripeArr.length; i++) {		
					var xPos =  ((-i * stripeSize) + lOffset);
					var topPos = (i % 2) == 0 ? -areaHeight: areaHeight;
					$(stripeArr[i]).css({
							"background":bgColor + " url('"+ newImgSrc +"') " + 
							xPos + "px " + tOffset + "px no-repeat",
							"backgroundPositionX":xPos + "px",
							"backgroundPositionY":tOffset + "px",
							opacity:opacity, top:topPos, width:stripeSize, "z-index":3});						
				}
			}
			
			//animate stripes
			var animate = function(newImg, tran) {
				var lastVert = stripeArr.length - 1;
				if (tran == TRANSITIONS["vert.tl"] || tran == TRANSITIONS["vert.bl"] || 
					tran == TRANSITIONS["fade.left"] || tran == TRANSITIONS["blinds.left"] || 
					tran == TRANSITIONS["alt.left"]) {
					var i = 0;
					intervalId = setInterval(
						function() {
							$(stripeArr[i++]).animate({top: 0, opacity:1, width:stripeSize}, 
													  tranSpeed, "",
								function() {
									if ($(this).attr("id") == lastVert) {
										rotator.setComplete(newImg);
									}
								}
							);
		
							if (i == stripeArr.length) {
								clearInterval(intervalId);
							}
						}, INTERVAL_DELAY);			
				}
				else {
					var i = lastVert;
					intervalId = setInterval(
						function() {
							$(stripeArr[i--]).animate({top: 0, opacity:1, width:stripeSize}, 
													  tranSpeed, "",
								function() {
									if ($(this).attr("id") == 0) {
										rotator.setComplete(newImg);
									}
								}
							);
		
							if (i < 0) {
								clearInterval(intervalId);
							}
						}, INTERVAL_DELAY);
				}
			}
			
			init();
		}
		
		//Horizontal Stripes
		function HorzStripes(rotator, w, h, size, bgColor, tranSpeed) {
			var areaWidth = w;
			var areaHeight = h;
			var stripeSize = size;
			var stripeArr;
			var $stripes;
			var intervalId = null;
			
			//init stripes
			var init = function() {			
				stripeArr = new Array(Math.ceil(areaHeight/stripeSize));
				
				var divs = "";
				for (var j = 0; j < stripeArr.length; j++) {
					divs += "<div class='hpiece' id='" + j + "'></div>";
				}				
				rotator.addToScreen(divs);
				
				$stripes = $("div.hpiece", rotator.$el);				
				$stripes.each(
					function(n) {
						stripeArr[n] = $(this).css({top: (n * stripeSize), 
													width: areaWidth,
													height: stripeSize});
					}							 
				);
			}

			//clear animation
			this.clear = function() {
				clearInterval(intervalId);
				$stripes.stop().css({"z-index":2, opacity:0});
			}

			//display content
			this.displayContent = function(newImg, tran) {
				setPieces(newImg, tran);
				animate(newImg, tran);
			}			
			
			//set image stripes
			var setPieces = function(newImg, tran) {
				if (tran == TRANSITIONS["horz.tr"] || tran == TRANSITIONS["horz.br"]) {
					setHorzPieces(newImg, areaWidth, 0, stripeSize);
				}
				else if (tran == TRANSITIONS["horz.tl"] || tran == TRANSITIONS["horz.bl"]) {
					setHorzPieces(newImg, -areaWidth, 0, stripeSize);					
				}
				else if (tran == TRANSITIONS["alt.top"] || tran == TRANSITIONS["alt.bottom"]) {
					setAltHorzPieces(newImg, 0);
				}
				else  if (tran == TRANSITIONS["blinds.top"] || tran == TRANSITIONS["blinds.bottom"]) {
					setHorzPieces(newImg, 0, 1, 0);
				}
				else {
					setHorzPieces(newImg, 0, 0, stripeSize);					
				}
			}
			
			//set horizontal stripes
			var setHorzPieces = function(newImg, leftPos, opacity, size) {
				var newImgSrc = newImg.src;
				var tOffset = (areaHeight - newImg.height)/2;
				var lOffset = (areaWidth - newImg.width)/2;
				for (var i = 0; i < stripeArr.length; i++) {			
					var yPos = ((-i * stripeSize) + tOffset);
					$(stripeArr[i]).css({
							"background":bgColor + " url('"+ newImgSrc +"') " + 
							lOffset + "px " + yPos  + "px no-repeat",
							"backgroundPositionX":lOffset  + "px",
							"backgroundPositionY":yPos + "px",
							opacity:opacity, left:leftPos, height:size,
							"z-index":3});			  
				}
			}
			
			//set alternative pos horizontal stripes
			var setAltHorzPieces = function(newImg, opacity) {
				var newImgSrc = newImg.src;
				var tOffset = (areaHeight - newImg.height)/2;
				var lOffset = (areaWidth - newImg.width)/2;
				for (var i = 0; i < stripeArr.length; i++) {			
					var yPos = ((-i * stripeSize) + tOffset);
					var leftPos = (i % 2) == 0 ? -areaWidth: areaWidth;
					$(stripeArr[i]).css({
							"background":bgColor + " url('"+ newImgSrc +"') " + 
							lOffset + "px " + yPos  + "px no-repeat",
							"backgroundPositionX":lOffset  + "px",
							"backgroundPositionY":yPos + "px",
							opacity:opacity, left:leftPos, height:stripeSize,
							"z-index":3});			  
				}
			}
			
			//animate stripes
			var animate = function(newImg, tran) {
				
				var lastHorz = stripeArr.length - 1;
				if (tran == TRANSITIONS["horz.tl"] || tran == TRANSITIONS["horz.tr"] || 
						 tran == TRANSITIONS["fade.top"] || tran == TRANSITIONS["blinds.top"] ||
						 tran == TRANSITIONS["alt.top"]) {
					var i = 0;
					intervalId = setInterval(
						function() {
							$(stripeArr[i++]).animate({left: 0, opacity:1, height:stripeSize}, 
													  tranSpeed, "",
								function() {
									if ($(this).attr("id") == lastHorz) {
										rotator.setComplete(newImg);
									}
								}
							);
		
							if (i == stripeArr.length) {
								clearInterval(intervalId);
							}
						}, INTERVAL_DELAY);
				}
				else {
					var i = lastHorz;
					intervalId = setInterval(
						function() {
							$(stripeArr[i--]).animate({left: 0, opacity:1, height:stripeSize}, 
													  tranSpeed, "",
								function() {
									if ($(this).attr("id") == 0) {
										rotator.setComplete(newImg);
									}
								}
							);
		
							if (i < 0) {
								clearInterval(intervalId);
							}
						}, INTERVAL_DELAY);
				}
			}
			
			init();
		}
		
		//class Blocks
		function Blocks(rotator, w, h, size, bgColor, tranSpeed) {
			var blockArr;
			var $blocks;
			var blockSize = size;
			var areaWidth = w;
			var areaHeight = h;		
			var numRows;
			var numCols;
			var diagLength;
			var brId;
			var trId;
			var intervalId;
			
			//init blocks
			var init = function() {
				numRows = Math.ceil(areaHeight/blockSize);
				numCols = Math.ceil(areaWidth/blockSize);
				diagLength = (numRows - 1) + (numCols - 1) + 1;
				trId = 0 + "-" + (numCols - 1);
				brId = (numRows - 1) + "-" + (numCols - 1);
				
				var divs = "";								
				for (var i = 0; i < numRows; i++) {					
					for (var j = 0; j < numCols; j++) {
						divs += "<div class='block' id='" + i + "-" + j + "'></div>";		
					}
				}
				rotator.addToScreen(divs);
				
				blockArr = new Array(numRows);
				for (var i = 0; i < numRows; i++) {
					blockArr[i] = new Array(numCols);
					for (var j = 0; j < numCols; j++) {
						blockArr[i][j] = $("#" + (i + "-" + j), rotator.$el)
											.css({top: (i * blockSize), left: (j * blockSize), 
												  width: blockSize, height: blockSize});
					}
				}
				$blocks = $("div.block", rotator.$el);
			}
			
			//clear blocks
			this.clear = function() {
				clearInterval(intervalId);
				$blocks.stop().css({"z-index":2, opacity:0});
			}
			
			//display content
			this.displayContent = function(newImg, tran) {
				if (tran == TRANSITIONS["diag.fade"]) {
					setBlocks(newImg, 0, blockSize, 0);
					diagTL(newImg, true, false, 0);
				}
				else if (tran == TRANSITIONS["diag.exp"]) {
					setBlocks(newImg, 1, 0, 0);
					diagTL(newImg, false, true);
				}
				else if (tran == TRANSITIONS["diag.fade.exp"]) {
					setBlocks(newImg, 0, 0, 0);
					diagTL(newImg, true, true);
				}
				else if (tran == TRANSITIONS["block.fade"]) {
					setBlocks(newImg, 0, blockSize, 0);
					randomBlockFade(newImg);
				}
				else if (tran == TRANSITIONS["block.exp"]) {
					setBlocks(newImg, 1, 0, 0);
					randomBlockExp(newImg);
				} 
				else if (tran == TRANSITIONS["block.drop"]) {
					setBlocks(newImg, 1, blockSize, -(numRows * blockSize));
					blocksDrop(newImg);
				}
				else {
					setBlocks(newImg, 1, 0, 0);
					animateDir(newImg, tran);
				}
			}
			
			//set blocks 
			var setBlocks = function(newImg, opacity, size, tPos) {
				var tOffset = (areaHeight - newImg.height)/2;
				var lOffset = (areaWidth - newImg.width)/2;
				var newImgSrc = newImg.src;
				for (var i = 0; i < numRows; i++) {							
					for (var j = 0; j < numCols; j++) {
						var tVal = ((-i * blockSize) + tOffset);
						var lVal = ((-j * blockSize) + lOffset);
						$(blockArr[i][j]).css({"background":  
								" url('"+ newImgSrc +"') " + 
								lVal + "px " + tVal + "px no-repeat",
								"backgroundPositionX": lVal + "px", "backgroundPositionY": tVal + "px",
								opacity:opacity, top:(i * blockSize) + tPos, left: (j * blockSize),
								width:size, height:size, "z-index":3
							})
					}					
				}
			}
			
			//animate blocks diagonal
			var diagBR = function(newImg, fade, expand) {
				var props;
				if (fade && expand) {
					props = {opacity:1, width:blockSize, height:blockSize};
				}
				else if (fade) {
					props = {opacity:1};
				}
				else {
					props = {width:blockSize, height:blockSize};
				}
				
				
				var i, j;
				var num = diagLength - 1;
				intervalId = setInterval(
					function() {
						for (var n = num; n >= 0; n--) {
							i = n;
							j = Math.abs(i - num);
							if (i < numRows && j < numCols) {
								$(blockArr[i][j]).animate(props, tranSpeed, "",
									function() {
										if ($(this).attr("id") == "0-0") {
											rotator.setComplete(newImg);
										}
									});
							}
						}

						if (num > 0) {
							num--;
						}
						else {
							clearInterval(intervalId);
						}				
					}, INTERVAL_DELAY);				
			}
			
			//animate blocks diagonal
			var diagTL = function(newImg, fade, expand) {
				var props;
				if (fade && expand) {
					props = {opacity:1, width:blockSize, height:blockSize};
				}
				else if (fade) {
					props = {opacity:1};
				}
				else {
					props = {width:blockSize, height:blockSize};
				}
				
				var i, j;
				var num = 0;
				intervalId = setInterval(
					function() {
						for (var n = 0; n <= num; n++) {
							i = n;
							j = Math.abs(i - num);
							if (i < numRows && j < numCols) {
								$(blockArr[i][j]).animate(props, tranSpeed, "",
									function() {
										if ($(this).attr("id") == brId) {
											rotator.setComplete(newImg);
										}
									});
							}
						}

						if (num < diagLength) {
							num++;
						}
						else {
							clearInterval(intervalId);
						}				
					}, INTERVAL_DELAY);				
			}
			
			//animate directional
			var animateDir = function(newImg, tran) {	
				var delay = 0;				
				if (tran == TRANSITIONS["block.left"]) {
					var j = 0;
					intervalId = setInterval(
						function() {
							for (var i = 0; i < numRows; i++) {
								$(blockArr[i][j]).animate({opacity:1, width:blockSize, height:blockSize}, 
											tranSpeed, "",
										function() {
											if ($(this).attr("id") == brId) {
												rotator.setComplete(newImg);
											}
										});							
							}
							j++;							
							if (j == numCols) {
								clearInterval(intervalId);	
							}
						}, INTERVAL_DELAY);						
				}
				else if (tran == TRANSITIONS["block.right"]) {
					var j = numCols - 1;
					intervalId = setInterval(
						function() {
							for (var i = 0; i < numRows; i++) {
								$(blockArr[i][j]).animate({opacity:1, width:blockSize, height:blockSize}, 
											tranSpeed, "",
										function() {
											if ($(this).attr("id") == "0-0") {
												rotator.setComplete(newImg);
											}
										});							
							}
							j--;							
							if (j < 0) {
								clearInterval(intervalId);	
							}
						}, INTERVAL_DELAY
					);				
				}
				else if (tran == TRANSITIONS["block.top"]) {
					var i = 0;
					intervalId = setInterval(
						function() {
							for (var j = 0; j < numCols; j++) {
								$(blockArr[i][j]).animate({opacity:1, width:blockSize, height:blockSize},	
											tranSpeed, "",
										function() {
											if ($(this).attr("id") == brId) {
												rotator.setComplete(newImg);
											}
										});							
							}
							i++;							
							if (i == numRows) {
								clearInterval(intervalId);	
							}
						}, INTERVAL_DELAY
					);								
				}
				else {
					var i = numRows - 1;
					intervalId = setInterval(
						function() {
							for (var j = 0; j < numCols; j++) {
								$(blockArr[i][j]).animate({opacity:1, width:blockSize, height:blockSize},	
											tranSpeed, "",
										function() {
											if ($(this).attr("id") == "0-0") {
												rotator.setComplete(newImg);
											}
										});							
							}
							i--;							
							if (i < 0) {
								clearInterval(intervalId);	
							}
						}, INTERVAL_DELAY
					);			
				}				
			}
			
			//blocks drop
			var blocksDrop = function(newImg) {
				var i = numRows - 1;
				var rowDelay = numCols * INTERVAL_DELAY;
				intervalId = setInterval(
					function() {
						rowDrop(blockArr[i], i, newImg);
						i--;
						if (i < 0) {
							clearInterval(intervalId);
						}
					}, rowDelay);
			}
			
			//block row drop
			var rowDrop = function(rowArr, rowIndex, newImg) {
				var topVal = (rowIndex * blockSize);
				
				var i = 0;								
				var tempId = setInterval(
					function() {
						$(rowArr[i]).animate({top:topVal}, tranSpeed, "",
							function() {
								if ($(this).attr("id") == trId) {
									rotator.setComplete(newImg);
								}
							});	
						
							i++;
							if (i == numCols) {
								clearInterval(tempId);
							}
					}, INTERVAL_DELAY);
			}
			
			//random block fade
			var randomBlockFade = function(newImg) {					
				var count = 0;
				var total = (numRows * numCols);						
				$blocks.each(	
					function(n) {
						$(this).delay(Math.random() * tranSpeed)
							   .animate({opacity:1}, (Math.random() * tranSpeed), "",
							function() {
								count++;
								if (count == total) {
									rotator.setComplete(newImg);
								}
							}
						);
					}
				);
			}
			
			//random block expand
			var randomBlockExp = function(newImg) {
				var count = 0;
				var total = (numRows * numCols);						
				$blocks.each(	
					function(n) {
						$(this).delay(Math.random() * tranSpeed)
							   .animate({width:blockSize, height:blockSize}, 
										(Math.random() * tranSpeed), "",
							function() {
								count++;
								if (count == total) {
									rotator.setComplete(newImg);
								}
							}
						);
					}
				);				
			}
			
			init();
		}
		
		function Rotator($obj, opts) {
			//set options
			var screenWidth = opts.width;
			var screenHeight = opts.height;
			var margin = opts.button_margin;
			var globalTran = opts.transition.toLowerCase();	
			var tranSpeed = opts.transition_speed > 0 ? opts.transition_speed : TRANSPEED;
			var globalDelay = opts.delay > 0 ? opts.delay: DEFAULT_DELAY;
			var rotate = opts.auto_start;
			var bgColor = opts.background_color;
			var border = opts.border;
			var cpAlign = opts.cpanel_align;
			var buttonWidth =  opts.button_width;
			var buttonHeight = opts.button_height;
			var blockSize = opts.block_size > 0 ? opts.block_size : 100;
			var vertSize = opts.vert_size > 0 ? opts.vert_size : 50;
			var horzSize = opts.horz_size > 0 ? opts.horz_size : 50;
			var displayThumbs = opts.display_thumbs;
			var displayDBtns = opts.display_dbuttons;
			var displayPlayBtn = opts.display_playbutton;
			var displayTooltip = opts.display_tooltip;
			var displayNumber = opts.display_numbers;
			var cpMouseover = opts.cpanel_mouseover;
			var descMouseover = opts.text_mouseover;
			var cpMargin = opts.cpanel_margin;
								
			var numItems = 0;
			var currIndex = 0;
			var imgPaths;
			var imgs;
			var currDelay;			
			var currTran;
			var textPadding;
			var ttX;
			var ttY;
			var ttLeft = false;		
			var vStripes;
			var hStripes;
			var blocks;
			var timerId = null;			
			var cpPos;
			var cpOffset;
			var hover = false;
			var textDisplayed = false;
			
			var $mainScreen = $(".wt-rotator", $obj);
			var $mainImg = $("#main-img", $obj);
			var $bgImg = $("#bg-img", $obj);
			var $desc = $("div.desc", $obj);
			var $preloader = $("div.preloader", $obj);			
			var $cPanel = $("div.c-panel", $obj);
			var $thumbnails = $("div.thumbnails", $obj);
			var $thumbList = $thumbnails.find("li");
			var $buttons = $("div.buttons", $obj);	
			var $playBtn = $(".play-btn", $obj);
			var $prevBtn = $(".prev-btn", $obj);
			var $nextBtn = $(".next-btn", $obj);
			var $tooltip = $("#tooltip", $obj);
			var $tmpDesc;			
			var $currThumb;
			this.$el = $obj;
			
			//init rotator
			this.init = function() {
				$mainImg.attr("src", "https://clg.conel.ac.uk/VLE/blocks/ads/images/spacer.png");
				numItems = $thumbList.size();	
				textPadding = Number($desc.outerWidth() - $desc.width());
				
				$mainScreen.css({"background-color":bgColor, "border":border,
								  width:screenWidth, height:screenHeight});
				
				//init button controls
				initThumbs();
				initButtons();
				initCPanel();
				
				//init text panel
				$mainScreen.append("<div class='tmp-desc'></div>");
				$tmpDesc = $("div.tmp-desc", $obj);				
				
				//config preloader	
				$preloader.css({top: (screenHeight - $preloader.outerHeight())/2, 
								left:(screenWidth -  $preloader.outerWidth())/2});
				
				$mainScreen.hover(
					function(e) {
						hover = true;
						if (cpMouseover) {
							displayCPanel();
						}
						if (descMouseover) {
							displayDesc();
						}
					},
					function(e) {
						hover = false;
						if (cpMouseover) {
							hideCPanel();
						}
						if (descMouseover) {
							hideDesc();
						}
					}
				);		
				
				//init blocks & stripes
				vStripes =  new VertStripes(this, screenWidth, screenHeight, 
											vertSize, bgColor, tranSpeed);
				hStripes =  new HorzStripes(this, screenWidth, screenHeight, 
											horzSize, bgColor, tranSpeed);				
				blocks = 	new Blocks(this, screenWidth, screenHeight, 
									   		blockSize, bgColor, tranSpeed);
				
				//init image loading
				initImgLoad();
				
				//display initial image
				loadContent(currIndex);
			}
			
			//set complete
			this.setComplete = function(newImg) {
				displayDesc();
				setImgPosition(newImg);
				$mainImg.attr("src", newImg.src);
				startTimer();
			}
			
			//add to screen
			this.addToScreen = function(s) {
				$mainScreen.find(">a:first").append(s);
			}
			
			//init control panel
			var initCPanel = function() {	
				$cPanel.css({width:$buttons.outerWidth(true) + $thumbnails.outerWidth(true)});
				var marginTop = 0;
				var marginLeft = 0;
				var marginBottom = 0;
				var marginRight = 0;
				
				if (cpAlign == TOP_LEFT) {
					$cPanel.css({"margin-top":cpMargin, "margin-left":cpMargin, 
								 "margin-bottom":0, "margin-right":0});
					cpOffset = -$cPanel.outerHeight(true);
					cpPos = 0;
					$cPanel.css({top:cpPos, left:0});
					$thumbnails.css("float", "left");
					$buttons.css("float", "left");
					ttX = 0;
					ttY = 25;
					ttLeft = false;
				}
				else if (cpAlign == TOP_RIGHT) {					
					$cPanel.css({"margin-right":cpMargin, "margin-top":cpMargin,
								 "margin-bottom":0, "margin-left":0});
					cpOffset = -$cPanel.outerHeight(true);
					cpPos = 0;
					$cPanel.css({top:cpPos, left:screenWidth - ($cPanel.outerWidth(true) - margin)});
					$thumbnails.css("float", "right");
					$buttons.css("float", "right");
					ttX = 0;
					ttY = 25;
					ttLeft = true;
				}
				else if (cpAlign == BOTTOM_LEFT) {
					$cPanel.css({"margin-left":cpMargin, "margin-bottom":cpMargin,
								 "margin-top":0, "margin-right":0});
					cpOffset = screenHeight;
					cpPos = screenHeight - $cPanel.outerHeight(true);					
					$cPanel.css({top:cpPos, left:0});
					$thumbnails.css("float", "left");
					$buttons.css("float", "left");
					ttX = 0;
					ttY = -30;
					ttLeft = false;
				}
				else {					
					$cPanel.css({"margin-right":cpMargin, "margin-bottom":cpMargin,
								 "margin-top":0, "margin-left":0});
					cpOffset = screenHeight;
					cpPos = screenHeight - $cPanel.outerHeight(true);					
					$cPanel.css({top:cpPos,
								 left:screenWidth - ($cPanel.outerWidth(true) - margin)});
					$thumbnails.css("float", "right");
					$buttons.css("float", "right");	   
					ttX = 0;
					ttY = -30;
					ttLeft = true;
				}
				
				if (cpMouseover && !hover) {
					$cPanel.css({top:cpOffset});
				}
			}
			
			//init buttons
			var initButtons = function() {
				//config directional buttons
				if (displayDBtns) {					
					$prevBtn.css({"margin-right":margin, width:buttonWidth, height:buttonHeight})
							.click(imgBack)
							.mouseover(btnOver).mouseout(btnOut);
					$nextBtn.css({"margin-right":margin, width:buttonWidth, height:buttonHeight})
							.click(imgFwd)
							.mouseover(btnOver).mouseout(btnOut);					
				}
				else {
					$prevBtn.hide();
					$nextBtn.hide();
				}
				//config play button
				if (displayPlayBtn) {
					if (!rotate) {
						$playBtn.css("background-image", "url(https://clg.conel.ac.uk/VLE/blocks/ads/images/play.png)");
					}
					
					$playBtn.css({"margin-right":margin, width:buttonWidth, height:buttonHeight})
							.click(playPause)
							.mouseover(btnOver).mouseout(btnOut);
				}
				else {
					$playBtn.hide();
				}
			}
			
			//init thumbs
			var initThumbs = function() {	
				$thumbList.each(
					function(n) {
						initTextData($(this));
						$(this).css({width:buttonWidth, height:buttonHeight, 
									 "line-height":buttonHeight + "px", 
									 "margin-right":margin});
						
						if (displayNumber) {
							$(this).append(n+1);
						}
					}
				);
				
				if (displayThumbs) { 
					$thumbList.click(
							function(e) {
								stopTimer();
								currIndex = $(this).index();
								loadContent(currIndex);
								return false;
							}
						).mouseover(
							function(e) {
								$(this).addClass("thumb-over");
								if (displayTooltip) {
									var caption = $(this).find(">a:first").attr("title");
									if (caption != "") {
										$tooltip.html(caption);
										ttX = (ttLeft) ? -$tooltip.width() : 0;
										var offset = $mainScreen.offset();
										$tooltip.css({top:e.pageY + ttY - offset.top, 
													 left:e.pageX + ttX - offset.left})
												.delay(TOOLTIP_DELAY).show(0);
									}
								}
							}
						).mouseout(
							function(e) {
								$(this).removeClass("thumb-over");
								$tooltip.stop().hide();
							}
						).mousemove(
							function(e) {
								var offset = $mainScreen.offset();
								$tooltip.css({top:e.pageY + ttY - offset.top, 
											  left:e.pageX + ttX - offset.left});
							}
					);
				}
				else {
					$thumbnails.css({display:"none"}).width(0);
				}
			}
			
			//move image back
			var imgBack = function() {
				stopTimer();
				currIndex = (currIndex > 0) ? currIndex - 1 : (numItems - 1);
				loadContent(currIndex);	
			}
			
			//move image forward
			var imgFwd = function() {
				stopTimer();
				currIndex = (currIndex < numItems - 1) ? currIndex + 1 : 0;
				loadContent(currIndex);
			}
			
			//play/pause
			var playPause = function() {
				rotate = !rotate;
				if (rotate) {
					$(this).css("background-image", "url(https://clg.conel.ac.uk/VLE/blocks/ads/images/pause.png)");
					startTimer();
				}
				else {
					$(this).css("background-image", "url(https://clg.conel.ac.uk/VLE/blocks/ads/images/play.png)");			
					stopTimer();
				}
			}
			
			var btnOver = function() {
				$(this).addClass("button-over");
			}
			
			var btnOut = function() {
				$(this).removeClass("button-over");
			}
			
			//init text description data
			var initTextData = function($thumb) {
				var $p = $thumb.find(">p:first");				
				var x =  getPosNumParam($p.attr("x"), 0);
				var y =  getPosNumParam($p.attr("y"), 0);
				var txtWidth = getPosNumParam($p.attr("w"), 400);
				txtWidth -= textPadding;		
				
				$thumb.data("desc", {x:x, y:y, w:txtWidth});
			}
			
			//display description panel
			var displayDesc = function() {
				if (!textDisplayed) {
					if (descMouseover && !hover) {
						return;
					}
					textDisplayed = true;
					var data = $currThumb.data("desc");
					var text = $currThumb.find(">p:first").html();
					var txtWidth = data.w;
					$tmpDesc.css({width:txtWidth}).html(text);	
					var txtHeight = $tmpDesc.height();
					
					$desc.css({top:data.y, left:data.x, width:txtWidth, height:0}).html("");
					if (txtHeight != 0) {			
						$desc.animate({opacity:1, height:txtHeight}, "slow", "",
							function () {  
								$(this).html(text);
							}
						);  
					}						
				}
			}
			
			//hide description panel
			var hideDesc = function() {
				textDisplayed = false;
				if ($desc.html() != "") {
					$desc.stop(true).animate({height:0, opacity:0}, tranSpeed);				
				}
				else {
					$desc.stop(true).animate({opacity:0}, tranSpeed);
				}
			}
			
			var displayCPanel = function() {
				$cPanel.animate({top:cpPos, opacity:1}, "slow");
			}
			
			var hideCPanel = function() {
				$cPanel.stop(true).animate({top:cpOffset, opacity:0}, "slow");
			}
			
			//load current content
			var loadContent = function(i) {
				//get selected thumb
				$currThumb = $thumbnails.find("li:nth-child(" + (i+1) + ")");
				$thumbList.removeClass("curr-thumb");				
				$currThumb.addClass("curr-thumb");
				
				//set transition
				currTran =  $currThumb.attr("tran") != undefined ? $currThumb.attr("tran") : globalTran;
				
				//set delay
				currDelay =	getPosNumParam($currThumb.attr("delay"), globalDelay);
				
				//set url
				var urlLink = $currThumb.find(">a:last").attr("href");
				var urlTarget = $currThumb.find(">a:last").attr("target");	
				if (urlLink != undefined && urlLink != "") {					
					$mainScreen.find(">a:first").css({cursor:"pointer"})
							   .attr("href", urlLink).attr("target", urlTarget);
				}
				else {
					$mainScreen.find(">a:first").css({cursor:"default"})
							   .attr("href", "#").attr("target", "_self");
				}
				
				//hide description
				hideDesc();
				
				//load image
				if (imgs[i]) {
					$preloader.hide();
					//display stored image	
					displayContent(imgs[i]);
				}	
				else {	
					//load new image
					var currImg = new Image();		
					$(currImg).attr("src", imgPaths[i]);
					if (!currImg.complete) {
						$preloader.show();
						$(currImg).load(
							function() {
								$preloader.hide();
								imgs[i] = jQuery.extend(true, {}, this);	
								displayContent(currImg);
							}
						).error(
							function() {
								alert("Error loading image");
							}
						);
					}
					else {
						$preloader.hide();
						imgs[i] = jQuery.extend(true, {}, currImg);
						displayContent(currImg);
					}
				}	    
			}
			
			//display image
			var displayContent = function(newImg) {
				//clear
				vStripes.clear();
				hStripes.clear();
				blocks.clear();

				//get transition number
				var tranNum = TRANSITIONS[currTran];				
				
				if (tranNum == TRANSITIONS["random"]) {
					tranNum = Math.floor(Math.random() * (TRANSITIONS.length - 2));
				}
				
				if (tranNum == TRANSITIONS["none"]) {
					showContent(newImg);
				}
				else if (tranNum == TRANSITIONS["fade"]) {
					fadeInContent(newImg);
				}
				else if (tranNum < TRANSITIONS["vert.tl"]) {
					blocks.displayContent(newImg, tranNum);
				}
				else if (tranNum < TRANSITIONS["horz.tl"]) {
					vStripes.displayContent(newImg, tranNum);
				}
				else {
					hStripes.displayContent(newImg, tranNum);					
				}
			}
			
			//display image (no transition)
			var showContent = function(newImg) {
				setImgPosition(newImg);
				$mainImg.attr("src", newImg.src).show(0, 
					function() {
						displayDesc();
						startTimer();
					}
				);	
			}
			
			//display image (fade transition)
			var fadeInContent = function(newImg) {
				$bgImg.css({top:$mainImg.css("top"), left:$mainImg.css("left"),
							"padding-top":$mainImg.css("padding-top"), 
							"padding-bottom":$mainImg.css("padding-bottom"),	
							"padding-left":$mainImg.css("padding-left"), 
							"padding-right":$mainImg.css("padding-right")})
							.attr("src", $mainImg.attr("src")).show();
				$mainImg.hide();	
				
				setImgPosition(newImg);
				$mainImg.attr("src", newImg.src).fadeIn(tranSpeed, 
					function() {	
						$bgImg.hide();
						displayDesc();
						startTimer();
					}
				);	
			}
			
			//init image loading
			var initImgLoad = function() {
				imgs = new Array(numItems);
				imgPaths = new Array(numItems);
				
				//init image paths
				$thumbList.each(
					function(n){
						imgPaths[n] = $(this).find(">a:first").attr("href");
					}
				);
				
				//start image loading		
				var loadIndex = 0;
				var img = new Image();
				$(img).attr("src", imgPaths[loadIndex]);
				
				//load image complete/error event handler
				$(img).load(
					function() {
						imgs[loadIndex] = jQuery.extend(true, {}, this);	
						
						loadIndex++
						if (loadIndex < imgPaths.length) {
							$(this).attr("src", imgPaths[loadIndex]);
						}
					}).error(function() {
						//error loading image, continue next
						loadIndex++
						if (loadIndex < imgPaths.length) {
							$(this).attr("src", imgPaths[loadIndex]);
						}
					}
				);
			}
			
			//adjust image padding
			var setImgPosition = function(newImg) {
				var tMargin = (screenHeight - newImg.height)/2;
				var lMargin = (screenWidth  - newImg.width)/2
				var top = 0;
				var left = 0;
				var vertPadding = 0;
				var horzPadding = 0;
				
				if (tMargin > 0) {
					vertPadding = tMargin;
				}
				else if (tMargin < 0) {
					top = tMargin;
				}
				
				if (lMargin > 0) {
					horzPadding = lMargin;
				}
				else if (lMargin < 0) {
					left = lMargin;
				}
				
				$mainImg.css({top:top, left:left, 
							  "padding-top":vertPadding, "padding-bottom":vertPadding,
							  "padding-left":horzPadding, "padding-right":horzPadding});	
			}
			
			//start timer
			var startTimer = function() {
				if (rotate && timerId == null) {
					timerId = setTimeout(imgFwd, currDelay);
				}
			}
			
			//stop timer
			var stopTimer = function() {
				clearTimeout(timerId);
				timerId = null;
			}
			
			var getPosNumParam = function(val, defaultVal) {
				if (val != undefined && !isNaN(val)) {
					val = Number(val);
					if (val > 0) {
						return val;
					}
				}
				
				return Number(defaultVal);
			}

			this.setMouseoverCP = function(val) {
				cpMouseover = val;
				if (cpMouseover) {
					hideCPanel();					
				}
				else {
					displayCPanel();
				}
			}

			this.setMouseoverDesc = function(val) {
				descMouseover = val;
				if (descMouseover) {
					hideDesc();
				}
				else {
					displayDesc();					
				}
			}

			this.updateTransition = function(val) {
				globalTran = val;
			}
			
			this.showThumbs = function(display) {
				displayThumbs = display;
				if (displayThumbs) {
					$thumbnails.show();
				}
				else {
					$thumbnails.hide();
				}
				initCPanel();
			}
			
			this.showDButtons = function(display) {
				displayDBtns = display;
				if (displayDBtns) {
					$prevBtn.show();
					$nextBtn.show();
				}
				else {
					$prevBtn.hide();
					$nextBtn.hide();
				}
				initCPanel();
			}
			
			this.showPlayButton = function(display) {
				displayPlayBtn = display;
				if (displayPlayBtn) {
					$playBtn.show();
				}
				else {
					$playBtn.hide();
				}
				initCPanel();
			}
			
			this.showTooltip = function(display) {
				displayTooltip = display;
			}
			
			//init control panel
			this.updateCPanel = function(align) {	
				cpAlign = align;
				initCPanel();
			}
		}
		
		var defaults = { 
			width:234,
			height:214,
			background_color:"#fff",
			border:"none",
			button_width:24,
			button_height:24,
			button_margin:4,
			auto_start:true,
			delay:5000,
			transition:"fade",
			transition_speed:1000,
			block_size:100,
			vert_size:50,
			horz_size:50,
			cpanel_align:"BR",
			cpanel_margin:6,
			display_thumbs:false,
			display_dbuttons:false,
			display_playbutton:false,
			display_tooltip:false,
			display_numbers:false,
			cpanel_mouseover:false,
			text_mouseover:false
		};
		
		var opts = $.extend({}, defaults, params);		

		return this.each(
			function() {
				rotator = new Rotator($(this), opts);
				rotator.init();
			}
		);
	}
})(jQuery);
