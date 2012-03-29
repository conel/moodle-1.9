/**
 * jQuery Banner Rotator 
 * Copyright (c) 2011 Allan Ma (http://codecanyon.net/user/webtako)
 * Version: 1.5.9 (07/20/2011)
 */
;(function($) {
	$.fn.wtRotator = function(params) {
		var INSIDE = "inside";
		var OUTSIDE = "outside";
		var PREV = 0;
		var NEXT = 1;
		var ALIGN = {"TL":0, "TC":1, "TR":2, "BL":3, "BC":4, "BR":5};
		
		var ei = 0;
		var EFFECTS = {			
			"block.top":ei++,
			"block.right":ei++,
			"block.bottom":ei++,
			"block.left":ei++,
			"block.drop":ei++,
			"diag.fade":ei++,
			"diag.exp":ei++,
			"rev.diag.fade":ei++,
			"rev.diag.exp":ei++,
			"block.fade":ei++,
			"block.exp":ei++,
			"block.top.zz":ei++,
			"block.bottom.zz":ei++,
			"block.left.zz":ei++,
			"block.right.zz":ei++,
			"spiral.in":ei++,
			"spiral.out":ei++,
			"vert.tl":ei++,
			"vert.tr":ei++,
			"vert.bl":ei++,
			"vert.br":ei++,
			"fade.left":ei++,
			"fade.right":ei++,	
			"alt.left":ei++,
			"alt.right":ei++,
			"blinds.left":ei++,
			"blinds.right":ei++,
			"vert.random.fade":ei++,
			"horz.tl":ei++,
			"horz.tr":ei++,
			"horz.bl":ei++,
			"horz.br":ei++,
			"fade.top":ei++,
			"fade.bottom":ei++,
			"alt.top":ei++,
			"alt.bottom":ei++,
			"blinds.top":ei++, 
			"blinds.bottom":ei++,
			"horz.random.fade":ei++,
			"none":ei++,
			"fade":ei++,
			"h.slide":ei++,
			"v.slide":ei++,
			"random":ei++
		};
		var TEXT_EFFECTS = {"fade":0, "down":1, "right":2, "up":3, "left":4, "none":5}
		
		var LIMIT = 250;
		var BLOCK_SIZE = 75;
		var STRIPE_SIZE = 50;
		var DEFAULT_DELAY = 5000;
		var DURATION = 800;
		var ANIMATE_SPEED = 600;
		var TOOLTIP_DELAY = 600;
		var UPDATE_TEXT = "updatetext";
		
		//Vertical Stripes
		function VertStripes(rotator, areaWidth, areaHeight, stripeSize, bgColor, duration, delay) {
			var $stripes;
			var $arr;
			var total;
			var intervalId = null;
			
			//init stripes
			var init = function() {
				total = Math.ceil(areaWidth/stripeSize);
				if (total > LIMIT) {
					stripeSize = Math.ceil(areaWidth/LIMIT);
					total = Math.ceil(areaWidth/stripeSize);
				}
				var divs = "";
				for (var i = 0; i < total; i++) {
					divs += "<div class='vpiece' id='" + i + "'></div>";
				}					
				rotator.addToScreen(divs);
				
				$stripes = rotator.$el.find("div.vpiece");
				$arr = new Array(total);
				$stripes.each(
					function(n) {						
						$(this).css({left:(n * stripeSize), height: areaHeight});
						$arr[n] = $(this);
					}
				);
			}

			//clear animation
			this.clear = function() {
				clearInterval(intervalId);
				$stripes.stop(true).css({"z-index":2, opacity:0});
			}

			//display content
			this.displayContent = function($img, effect) {
				setPieces($img, effect);
				if (effect == EFFECTS["vert.random.fade"]) {
					animateRandom($img);
				}
				else {
					animate($img, effect);
				}
			}			
			
			//set image stripes
			var setPieces = function($img, effect) {
				switch (effect) {
					case EFFECTS["vert.tl"]:
					case EFFECTS["vert.tr"]:
						setVertPieces($img, -areaHeight, 1, stripeSize, false);
						break;
					case EFFECTS["vert.bl"]:
					case EFFECTS["vert.br"]:
						setVertPieces($img, areaHeight, 1, stripeSize, false);
						break;
					case EFFECTS["alt.left"]:
					case EFFECTS["alt.right"]:
						setVertPieces($img, 0, 1, stripeSize, true);
						break;
					case EFFECTS["blinds.left"]:
					case EFFECTS["blinds.right"]:
						setVertPieces($img, 0, 1, 0, false);
						break;
					default:
						setVertPieces($img, 0, 0, stripeSize, false);
				}
			}
			
			//set vertical stripes
			var setVertPieces = function($img, topPos, opacity, width, alt) {
				var imgSrc = $img.attr("src");
				var tOffset = 0;
				var lOffset = 0;
				if (rotator.autoCenter()) {
					tOffset = (areaHeight - $img.height())/2;
					lOffset = (areaWidth - $img.width())/2;
				}
				$stripes.each(
					function(n) {
						var xPos =  ((-n * stripeSize) + lOffset);
						if (alt) {
							topPos = (n % 2) == 0 ? -areaHeight: areaHeight;
						}
						$(this).css({background:bgColor + " url('"+ imgSrc +"') no-repeat", backgroundPosition:xPos + "px " + tOffset + "px", opacity:opacity, top:topPos, width:width, "z-index":3});
					});
			}
			
			//animate stripes			
			var animate = function($img, effect) {
				var start, end, incr, limit;
				switch (effect) {
					case EFFECTS["vert.tl"]:   case EFFECTS["vert.bl"]: 
					case EFFECTS["fade.left"]: case EFFECTS["blinds.left"]: 
					case EFFECTS["alt.left"]:
						start = 0;
						end = total - 1;
						incr = 1;
						break;
					default:
						start = total - 1;
						end = 0;
						incr = -1;
				}
				
				intervalId = setInterval(
					function() {
						$($stripes.get(start)).animate({top:0, opacity:1, width:stripeSize}, duration, rotator.easing(),
							function() {
								if ($(this).attr("id") == end) {
									rotator.setComplete($img);
								}
							}
						);
						if (start == end) {
							clearInterval(intervalId);
						}
						start += incr;
					}, delay);
			}
			
			//animate random fade 
			var animateRandom = function($img) {		
				shuffleArray($arr);
				var i = 0;
				var count = 0;
				intervalId = setInterval(
					function() {
						$arr[i++].animate({opacity:1}, duration, rotator.easing(),
								function() {
									if (++count == total) {
										rotator.setComplete($img);
									}
								});
						if (i == total) {
							clearInterval(intervalId);
						}
					}, delay);
			}
			
			init();
		}
		
		//Horizontal Stripes
		function HorzStripes(rotator, areaWidth, areaHeight, stripeSize, bgColor, duration, delay) {
			var $stripes;
			var $arr;
			var total;
			var intervalId = null;
			
			//init stripes
			var init = function() {			
				total = Math.ceil(areaHeight/stripeSize);
				if (total > LIMIT) {
					stripeSize = Math.ceil(areaHeight/LIMIT);
					total = Math.ceil(areaHeight/stripeSize);
				}
				var divs = "";
				for (var j = 0; j < total; j++) {
					divs += "<div class='hpiece' id='" + j + "'><!-- --></div>";
				}				
				rotator.addToScreen(divs);
				
				$stripes = rotator.$el.find("div.hpiece");
				$arr = new Array(total);
				$stripes.each(
					function(n) {
						$(this).css({top:(n * stripeSize), width: areaWidth});
						$arr[n] = $(this);
					}							 
				);
			}

			//clear animation
			this.clear = function() {
				clearInterval(intervalId);
				$stripes.stop(true).css({"z-index":2, opacity:0});
			}

			//display content
			this.displayContent = function($img, effect) {
				setPieces($img, effect);
				if (effect == EFFECTS["horz.random.fade"]) {
					animateRandom($img);
				}
				else {
					animate($img, effect);
				}
			}			
			
			//set image stripes
			var setPieces = function($img, effect) {
				switch (effect) {
					case EFFECTS["horz.tr"]:
					case EFFECTS["horz.br"]:
						setHorzPieces($img, areaWidth, 1, stripeSize, false);
						break;
					case EFFECTS["horz.tl"]:
					case EFFECTS["horz.bl"]:
						setHorzPieces($img, -areaWidth, 1, stripeSize, false);
						break;
					case EFFECTS["alt.top"]:
					case EFFECTS["alt.bottom"]:
						setHorzPieces($img, 0, 1, stripeSize, true);
						break;
					case EFFECTS["blinds.top"]:
					case EFFECTS["blinds.bottom"]:
						setHorzPieces($img, 0, 1, 0, false);
						break;
					default:
						setHorzPieces($img, 0, 0, stripeSize, false);
				}
			}
			
			//set horizontal stripes
			var setHorzPieces = function($img, leftPos, opacity, height, alt) {
				var imgSrc = $img.attr("src");
				var tOffset = 0;
				var lOffset = 0;
				if (rotator.autoCenter()) {
					tOffset = (areaHeight - $img.height())/2;
					lOffset = (areaWidth - $img.width())/2;
				}
				$stripes.each(
					function(n) {
						var yPos = ((-n * stripeSize) + tOffset);
						if (alt) {
							leftPos = (n % 2) == 0 ? -areaWidth: areaWidth;
						}
						$(this).css({background:bgColor + " url('"+ imgSrc +"') no-repeat", backgroundPosition:lOffset + "px " + yPos + "px", opacity:opacity, left:leftPos, height:height, "z-index":3});  
					});
			}
			
			//animate stripes			
			var animate = function($img, effect) {
				var start, end, incr;
				switch (effect) {
					case EFFECTS["horz.tl"]:  case EFFECTS["horz.tr"]: 
					case EFFECTS["fade.top"]: case EFFECTS["blinds.top"]: 
					case EFFECTS["alt.top"]:
						start = 0;
						end = total - 1;
						incr = 1;
						break;
					default:
						start = total - 1;
						end = 0;
						incr = -1;
				}
				
				intervalId = setInterval(
					function() {
						$($stripes.get(start)).animate({left:0, opacity:1, height:stripeSize}, duration, rotator.easing(),
							function() {
								if ($(this).attr("id") == end) {
									rotator.setComplete($img);
								}
							}
						);
						if (start == end) {
							clearInterval(intervalId);
						}
						start += incr;
					}, delay);
			}
			
			//animate random fade 
			var animateRandom = function($img) {		
				shuffleArray($arr);
				var i = 0;
				var count = 0;
				intervalId = setInterval(
					function() {
						$arr[i++].animate({opacity:1}, duration, rotator.easing(),
								function() {
									if (++count == total) {
										rotator.setComplete($img);
									}
								});
						if (i == total) {
							clearInterval(intervalId);
						}
					}, delay);
			}
			
			init();
		}
		
		//class Blocks
		function Blocks(rotator, areaWidth, areaHeight, blockSize, bgColor, duration, delay) {
			var $blockArr;
			var $blocks;
			var $arr;
			var numRows;
			var numCols;
			var total;
			var intervalId;
			
			//init blocks
			var init = function() {
				numRows = Math.ceil(areaHeight/blockSize);
				numCols = Math.ceil(areaWidth/blockSize);
				total = numRows * numCols;
				if (total > LIMIT) {
					blockSize = Math.ceil(Math.sqrt((areaHeight * areaWidth)/LIMIT));
					numRows = Math.ceil(areaHeight/blockSize);
					numCols = Math.ceil(areaWidth/blockSize);
					total = numRows * numCols;
				}
				
				var divs = "";
				for (var i = 0; i < numRows; i++) {					
					for (var j = 0; j < numCols; j++) {
						divs += "<div class='block' id='" + i + "-" + j + "'></div>";
					}
				}
				rotator.addToScreen(divs);
				$blocks = rotator.$el.find("div.block");
				$blocks.data({tlId:"0-0", trId:"0-"+(numCols - 1), blId:(numRows - 1)+"-0", brId:(numRows - 1)+"-"+(numCols - 1)});
				
				var k = 0;
				$arr = new Array(total);
				$blockArr = new Array(numRows);
				for (var i = 0; i < numRows; i++) {
					$blockArr[i] = new Array(numCols);
					for (var j = 0; j < numCols; j++) {
						$blockArr[i][j] = $arr[k++] = $blocks.filter("#" + (i + "-" + j)).data("top", i * blockSize);
					}
				}				
			}
			
			//clear blocks
			this.clear = function() {
				clearInterval(intervalId);
				$blocks.stop(true).css({"z-index":2, opacity:0});
			}
			
			//display content
			this.displayContent = function($img, effect) {
				switch (effect) {
					case EFFECTS["diag.fade"]:
						setBlocks($img, 0, blockSize, 0);
						diagAnimate($img, {opacity:1}, false);
						break;
					case EFFECTS["diag.exp"]:
						setBlocks($img, 0, 0, 0);
						diagAnimate($img, {opacity:1, width:blockSize, height:blockSize}, false);
						break;
					case EFFECTS["rev.diag.fade"]:
						setBlocks($img, 0, blockSize, 0);
						diagAnimate($img, {opacity:1}, true);
						break;
					case EFFECTS["rev.diag.exp"]:
						setBlocks($img, 0, 0, 0);
						diagAnimate($img, {opacity:1, width:blockSize, height:blockSize}, true);
						break;
					case EFFECTS["block.fade"]:
						setBlocks($img, 0, blockSize, 0);
						randomAnimate($img);
						break;
					case EFFECTS["block.exp"]:
						setBlocks($img, 1, 0, 0);
						randomAnimate($img);
						break; 
					case EFFECTS["block.drop"]:
						setBlocks($img, 1, blockSize, -(numRows * blockSize));
						randomAnimate($img);
						break;
					case EFFECTS["block.top.zz"]: 
					case EFFECTS["block.bottom.zz"]:					
						setBlocks($img, 0, blockSize, 0);
						horzZigZag($img, effect);
						break;
					case EFFECTS["block.left.zz"]: 
					case EFFECTS["block.right.zz"]:
						setBlocks($img, 0, blockSize, 0);
						vertZigZag($img, effect);
						break;
					case EFFECTS["spiral.in"]:
						setBlocks($img, 0, blockSize, 0);
						spiral($img, false);
						break;
					case EFFECTS["spiral.out"]:
						setBlocks($img, 0, blockSize, 0);
						spiral($img, true);
						break;
					default:
						setBlocks($img, 1, 0, 0);
						dirAnimate($img, effect);
				}
			}
			
			//set blocks 
			var setBlocks = function($img, opacity, size, tPos) {
				var tOffset = 0;
				var lOffset = 0;
				if (rotator.autoCenter()) {
					tOffset = (areaHeight - $img.height())/2;
					lOffset = (areaWidth - $img.width())/2;
				}
				var imgSrc = $img.attr("src");
				for (var i = 0; i < numRows; i++) {							
					for (var j = 0; j < numCols; j++) {
						var tVal = ((-i * blockSize) + tOffset);
						var lVal = ((-j * blockSize) + lOffset);
						$blockArr[i][j].css({background:bgColor + " url('"+ imgSrc +"') no-repeat", backgroundPosition:lVal + "px " + tVal + "px", opacity:opacity, top:(i * blockSize) + tPos, left:(j * blockSize), width:size, height:size, "z-index":3});
					}					
				}
			}
			
			//diagonal effect
			var diagAnimate = function($img, props, rev) {
				var $array = new Array(total);
				var start, end, incr, lastId;
				var diagSpan = (numRows - 1) + (numCols - 1);
				if (rev) {				
					start = diagSpan;
					end = -1;
					incr = -1;
					lastId = $blocks.data("tlId");
				}
				else {
					start = 0;
					end = diagSpan + 1;
					incr = 1;
					lastId = $blocks.data("brId");
				}
				
				var count = 0;
				while (start != end) {
					i = Math.min(numRows - 1, start);
					while(i >= 0) {			
						j = Math.abs(i - start);
						if (j >= numCols) {
							break;
						}
						$array[count++] = $blockArr[i][j];
						i--;
					}
					start+=incr;
				}
				
				count = 0;
				intervalId = setInterval(
					function() {
						$array[count++].animate(props, duration, rotator.easing(),
								function() {
									if ($(this).attr("id") == lastId) {
										rotator.setComplete($img);
									}
								});
						if (count == total) {
							clearInterval(intervalId);
						}			
					}, delay);
			}

			//vertical zig zag effect
			var vertZigZag = function($img, effect) {
				var fwd = true;
				var i = 0, j, incr, lastId;
				if (effect == EFFECTS["block.left.zz"]) {
					lastId = (numCols%2 == 0) ? $blocks.data("trId") : $blocks.data("brId");
					j = 0;
					incr = 1;
				}
				else {
					lastId = (numCols%2 == 0) ? $blocks.data("tlId") : $blocks.data("blId");
					j = numCols - 1;
					incr = -1;
				}
				
				intervalId = setInterval(
					function() {
						$blockArr[i][j].animate({opacity:1}, duration, rotator.easing(),
								function() {
									if ($(this).attr("id") == lastId) {
										rotator.setComplete($img);
									}});
						
						if ($blockArr[i][j].attr("id") == lastId) {
							clearInterval(intervalId);
						}
						
						(fwd ? i++ : i--);
						if (i == numRows || i < 0) {
							fwd = !fwd;
							i = (fwd ? 0 : numRows - 1);
							j+=incr;
						}						
					}, delay);
			}
			
			//horizontal zig zag effect
			var horzZigZag = function($img, effect) {
				var fwd = true;
				var i, j = 0, incr, lastId;
				if (effect == EFFECTS["block.top.zz"]) {
					lastId = (numRows%2 == 0) ? $blocks.data("blId") : $blocks.data("brId");
					i = 0;
					incr = 1;
				}
				else {
					lastId = (numRows%2 == 0) ? $blocks.data("tlId") : $blocks.data("trId");
					i = numRows - 1;
					incr = -1;
				}
				
				intervalId = setInterval(
					function() {
						$blockArr[i][j].animate({opacity:1}, duration, rotator.easing(),
								function() {
									if ($(this).attr("id") == lastId) {
										rotator.setComplete($img);
									}});
						
						if ($blockArr[i][j].attr("id") == lastId) {
							clearInterval(intervalId);
						}
						
						(fwd ? j++ : j--);
						if (j == numCols || j < 0) {
							fwd = !fwd;
							j = (fwd ? 0 : numCols - 1);
							i+=incr;
						}						
					}, delay);
			}
			
			//vertical direction effect
			var dirAnimate = function($img, effect) {
				var $array = new Array(total);
				var lastId;
				var count = 0;
				switch (effect) {
					case EFFECTS["block.left"]:
						lastId = $blocks.data("brId");
						for (var j = 0; j < numCols; j++) {
							for (var i = 0; i < numRows; i++) {
								$array[count++] = $blockArr[i][j];
							}
						}
						break;
					case EFFECTS["block.right"]:
						lastId = $blocks.data("blId");
						for (var j = numCols - 1; j >= 0; j--) {
							for (var i = 0; i < numRows; i++) {
								$array[count++] = $blockArr[i][j];
							}
						}					
						break;
					case EFFECTS["block.top"]:
						lastId = $blocks.data("brId");
						for (var i = 0; i < numRows; i++) {
							for (var j = 0; j < numCols; j++) {
								$array[count++] = $blockArr[i][j];
							}
						}					
						break;
					default:
						lastId = $blocks.data("trId");
						for (var i = numRows - 1; i >= 0; i--) {
							for (var j = 0; j < numCols; j++) {
								$array[count++] = $blockArr[i][j];
							}
						}
				}
				count = 0;
				intervalId = setInterval(
					function() {
						$array[count++].animate({width:blockSize, height:blockSize}, duration, rotator.easing(),
								function() {
									if ($(this).attr("id") == lastId) {
										rotator.setComplete($img);
									}
								});
						if (count == total) {
							clearInterval(intervalId);
						}
					}, delay);
			}
			
			//random block effect
			var randomAnimate = function($img) {
				shuffleArray($arr);
				var i = 0;
				count = 0;
				intervalId = setInterval(
					function() {
						$arr[i].animate({top:$arr[i].data("top"), width:blockSize, height:blockSize, opacity:1}, duration, rotator.easing(),
								function() {
									if (++count == total) {
										rotator.setComplete($img);
									}
								});
						i++;
						if (i == total) {
							clearInterval(intervalId);
						}
					}, delay);
			}
			
			//spiral effect
			var spiral = function($img, spiralOut) {			
				var i = 0, j = 0;
				var rowCount = numRows - 1;
				var colCount = numCols - 1;
				var dir = 0;
				var limit = colCount;
				var $array = new Array();
				while (rowCount >= 0 && colCount >=0) {
					var count = 0; 
					while(true) { 
						$array[$array.length] = $blockArr[i][j];
						if ((++count) > limit) {
							break;
						}
						switch(dir) {
							case 0:
								j++;
								break;
							case 1:
								i++;
								break;
							case 2:
								j--;
								break;
							case 3:
								i--;
						}
   					} 
					switch(dir) {
						case 0:
							dir = 1;
							limit = (--rowCount);
							i++;
							break;
						case 1:
							dir = 2;
							limit = (--colCount);
							j--;
							break;
						case 2:
							dir = 3;
							limit = (--rowCount);
							i--;
							break;
						case 3:
							dir = 0;
							limit = (--colCount);
							j++;
					}
				}
				if ($array.length > 0) {
					if (spiralOut) {
						$array.reverse();
					}
					var end = $array.length - 1;
					var lastId = $array[end].attr("id");
					var k = 0;
					intervalId = setInterval(
						function() {
							$array[k].animate({opacity:1}, duration, rotator.easing(),
								function() {
									if ($(this).attr("id") == lastId) {
										rotator.setComplete($img);
									}
								});
							if (k == end) {
								clearInterval(intervalId);
							}	
							k++;
						}, delay);
				}
			}
			
			init();
		}
		
		//class Rotator
		function Rotator($obj, opts) {
			//set options
			var screenWidth =  	getPosNumber(opts.width, 825);
			var screenHeight = 	getPosNumber(opts.height, 300);
			var margin = 		getNonNegNumber(opts.button_margin, 4);
			var globalEffect = 	opts.transition.toLowerCase();
			var duration =   	getPosNumber(opts.transition_speed, DURATION);
			var globalDelay = 	getPosNumber(opts.delay, DEFAULT_DELAY);
			var rotate = 		opts.auto_start;
			var cpPos =			opts.cpanel_position.toLowerCase();
			var cpAlign = 		opts.cpanel_align.toUpperCase();
			var buttonWidth =  	getPosNumber(opts.button_width, 24);
			var buttonHeight =	getPosNumber(opts.button_height, 24);
			var displayThumbs = opts.display_thumbs;
			var displayDBtns = 	opts.display_dbuttons;
			var displayPlayBtn =opts.display_playbutton;
			var displayNumbers = opts.display_numbers;
			var displayThumbImg = opts.display_thumbimg;
			var displayTimer =	opts.display_timer;
			var cpMouseover = 	opts.cpanel_mouseover;
			var textMousover = 	opts.text_mouseover;
			var pauseMouseover =opts.mouseover_pause;			
			var tipType = 		opts.tooltip_type.toLowerCase();
			var textEffect = 	opts.text_effect.toLowerCase();
			var textSync =		opts.text_sync;
			var playOnce =		opts.play_once;
			var autoCenter =	opts.auto_center;
			var easing =		opts.easing;
			
			var numItems;
			var currIndex;
			var prevIndex;
			var delay;
			var vStripes;
			var hStripes;
			var blocks;
			var timerId;
			var blockEffect;
			var hStripeEffect;
			var vStripeEffect;
			var dir;
			var $rotator;
			var $screen;
			var $strip;
			var $mainLink;
			var $textBox;
			var $preloader;
			var $cpWrapper;
			var $cpanel;
			var $thumbPanel;
			var $thumbs;
			var $buttonPanel;
			var $playBtn;
			var $prevBtn;
			var $nextBtn;
			var $timer;
			var $tooltip;
			var $items;
			var $innerText;
			this.$el = $obj;
			
			//init rotator
			this.init = function() {
				$rotator = 	$obj.find(".wt-rotator");
				$screen = 	$rotator.find("div.screen");
				$cpanel 	= $rotator.find("div.c-panel");
				$buttonPanel= $cpanel.find("div.buttons");
				$thumbPanel = $cpanel.find("div.thumbnails");
				$thumbs 	= $thumbPanel.find(">ul:first>li");
				$tooltip = $("<div id='rotator-tooltip'></div>");
				timerId = null;
				currIndex = 0;
				prevIndex = -1;
				numItems = $thumbs.size();
				$items = new Array(numItems);
				blockEffect = hStripeEffect = vStripeEffect = false;
				checkEffect(EFFECTS[globalEffect]);
				//init components
				initScreen();
				initItems();
				initButtons();
				initCPanel();
				initTimerBar();
				
				$rotator.css({width:screenWidth, height:screenHeight + (cpPos == OUTSIDE ? $cpWrapper.outerHeight(): 0)});
				
				if (textMousover) {
					$rotator.hover(displayText, hideText);
				}
				else {
					$rotator.bind(UPDATE_TEXT, updateText);
				}
				
				//init transition components
				var bgColor = $screen.css("background-color");
				if (vStripeEffect) {
					vStripes = new VertStripes(this, screenWidth, screenHeight, getPosNumber(opts.vert_size, STRIPE_SIZE), bgColor, duration, getPosNumber(opts.vstripe_delay, 75));
				}
				if (hStripeEffect) {
					hStripes = new HorzStripes(this, screenWidth, screenHeight, getPosNumber(opts.horz_size, STRIPE_SIZE), bgColor, duration, getPosNumber(opts.hstripe_delay, 75));
				}
				if (blockEffect) {
					blocks = new Blocks(this, screenWidth, screenHeight, getPosNumber(opts.block_size, BLOCK_SIZE), bgColor, duration, getPosNumber(opts.block_delay, 25));
				}
				//init image loading
				loadImg(0);
				
				//display initial image
				loadContent(currIndex);
			}
			
			//set complete
			this.setComplete = function($img) {
				showContent($img);
			}
			
			//add to screen
			this.addToScreen = function(content) {
				$mainLink.append(content);
			}
			
			//get auto center
			this.autoCenter = function() {
				return autoCenter;
			}
			
			//get easing
			this.easing = function() {
				return easing;
			}
			
			//init screen
			var initScreen = function() {
				var content =  "<div class='desc'><div class='inner-bg'></div><div class='inner-text'></div></div>\
								<div class='preloader'></div>\
								<div id='timer'></div>";
				$screen.append(content);
				$textBox 	= $screen.find("div.desc");
			 	$preloader 	= $screen.find("div.preloader");
				$screen.css({width:screenWidth, height:screenHeight});
				$innerText = $textBox.find("div.inner-text");
				
				$strip = $("<div id='strip'></div>");
				if (globalEffect == "h.slide") {
					$screen.append($strip);
					$strip.css({width:2*screenWidth, height:screenHeight});
					$thumbs.removeAttr("effect");
				}
				else if (globalEffect == "v.slide"){
					$screen.append($strip);
					$strip.css({width:screenWidth, height:2*screenHeight});
					$thumbs.removeAttr("effect");
				}
				else {
					$screen.append("<a href='#'></a>");
					$mainLink = $screen.find(">a:first");
				}
			}
			
			//init control panel
			var initCPanel = function() {	
				if (displayThumbs || displayDBtns || displayPlayBtn) {
					var maxWidth = screenWidth - ($buttonPanel.width() + margin);
					if ($thumbPanel.width() > maxWidth) {
						$thumbPanel.width(maxWidth);
					}
					if (cpPos == INSIDE) {
						$cpanel.css({"margin-top":margin, "margin-right":0, "margin-bottom":margin, "margin-left":0});
						var cpHeight = $cpanel.outerHeight(true);
						switch (ALIGN[cpAlign]) {
							case ALIGN["TL"]:
								setInsideCP(0, -cpHeight);
								setCPanel("left");
								break;
							case ALIGN["TC"]:
								setInsideCP(0, -cpHeight);
								setCPanel("center");
								break;
							case ALIGN["TR"]:
								setInsideCP(0, -cpHeight);
								setCPanel("right");
								break;
							case ALIGN["BL"]:
								setInsideCP((screenHeight - cpHeight), screenHeight);
								setCPanel("left");
								break;
							case ALIGN["BC"]:
								setInsideCP((screenHeight - cpHeight), screenHeight);
								setCPanel("center");
								break;
							default:
								setInsideCP((screenHeight - cpHeight), screenHeight);
								setCPanel("right");
						}
						
						if (cpMouseover) {
							$rotator.hover(displayCPanel, hideCPanel);
						}
					}
					else {
						$cpanel.wrap("<div class='outer-cp'></div>");
						$cpWrapper = $rotator.find(".outer-cp");
						$cpWrapper.css({"padding-top":margin, "padding-bottom":margin, height:$cpanel.height()});
						switch (ALIGN[cpAlign]) {
							case ALIGN["TL"]:
								setOutsideCP(true);
								setCPanel("left");
								break;
							case ALIGN["TC"]:
								setOutsideCP(true);
								setCPanel("center");
								break;
							case ALIGN["TR"]:
								setOutsideCP(true);
								setCPanel("right");
								break;
							case ALIGN["BL"]:
								setOutsideCP(false);
								setCPanel("left");
								break;
							case ALIGN["BC"]:
								setOutsideCP(false);
								setCPanel("center");
								break;
							default:
								setOutsideCP(false);
								setCPanel("right");
						}
					}
					$cpanel.css("visibility", "visible").click(preventDefault);
				}
			}
			
			var setInsideCP = function(yPos, offset) {
				$cpanel.data({offset:offset, pos:yPos}).css({top:(cpMouseover ? offset : yPos)});
			}
			
			var setOutsideCP = function(top) {
				if (top) {
					$cpWrapper.css({"border-top":"none", top:0});
					$screen.css("top", $cpWrapper.outerHeight());
				}
				else {
					$cpWrapper.css({"border-bottom":"none", top:screenHeight});
					$screen.css("top",0);
				}
				$cpanel.css("top", margin);
			}
			
			//set control panel attributes
			var setCPanel = function(align) {
				if (align == "center") {
					$cpanel.css("left",Math.round((screenWidth - $cpanel.outerWidth(true) - margin)/2));
					$thumbPanel.css("float","left");
					$buttonPanel.css("float","left");
					$cpanel.prepend($thumbPanel);
				}
				else if (align == "left") {
					$cpanel.css("left",margin);
					$thumbPanel.css("float","left");
					$buttonPanel.css("float","left");
				}
				else {
					$cpanel.css("right",0);
					$thumbPanel.css("float","right");
					$buttonPanel.css("float","right");
				}
				if (displayThumbs) {
					$cpanel.height($thumbPanel.height());
				}
				else {
					$cpanel.height($buttonPanel.height());
				}
			}
			
			//init buttons
			var initButtons = function() {
				$playBtn 	= $buttonPanel.find("div.play-btn");
				$prevBtn 	= $buttonPanel.find("div.prev-btn");
				$nextBtn 	= $buttonPanel.find("div.next-btn");
				var props = {"margin-right":margin, width:buttonWidth, height:buttonHeight};
				//config directional buttons
				if (displayDBtns) {
					$prevBtn.css(props).click(prevImg).mouseover(buttonOver).mouseout(buttonOut).mousedown(preventDefault);
					$nextBtn.css(props).click(nextImg).mouseover(buttonOver).mouseout(buttonOut).mousedown(preventDefault);
				}
				else {
					$prevBtn.hide();
					$nextBtn.hide();
				}
				
				//config play button
				if (displayPlayBtn) {
					if (rotate) {
						$playBtn.addClass("pause");
					}			
					$playBtn.css(props).click(togglePlay).mouseover(buttonOver).mouseout(buttonOut).mousedown(preventDefault);
				}
				else {
					$playBtn.hide();
				}
				
				if (pauseMouseover) {
					$rotator.hover(pause, play);
				}
			}			
			
			//init timer bar
			var initTimerBar = function() {
				$timer = $screen.find("#timer").data("pct", 1);
				if (displayTimer) {
					var align = opts.timer_align.toLowerCase();
					$timer.css("visibility", "visible");
					$timer.css("top", align == "top" ? 0 : screenHeight - $timer.height());
				}
				else {
					$timer.hide();
				}
			}
			
			//init items
			var initItems = function() {
				var padding = $innerText.outerHeight() - $innerText.height();
				$thumbs.each(
					function(n) {
						var $imgLink = $(this).find(">a:first");
						var itemEffect = EFFECTS[$(this).attr("effect")];
						if (itemEffect == undefined || itemEffect ==  EFFECTS["h.slide"] || itemEffect ==  EFFECTS["v.slide"]) {
							itemEffect = EFFECTS[globalEffect];
						}
						else {
							checkEffect(itemEffect);
						}
						$(this).data({imgurl:$imgLink.attr("href"), caption:$imgLink.attr("title"), effect:itemEffect, delay:getPosNumber($(this).attr("delay"), globalDelay)});
						
						initTextData($(this), padding);
						$items[n] = $(this);
						
						if (displayNumbers) {
							$(this).append(n+1);
						}
					}
				);
				$innerText.css({width:"auto", height:"auto"}).html("");
				$textBox.css("visibility", "visible");
				
				if (displayThumbImg) {
					$thumbs.addClass("image");
					$thumbs.find(">a:first").removeAttr("title").find(">img").removeAttr("alt");
				}
						
				if (opts.shuffle) {
					shuffleItems();
				}
				
				if (displayThumbs) {					
					$thumbs.css({width:buttonWidth, height:buttonHeight, "line-height":buttonHeight + "px", "margin-right":margin})
						   .mouseover(itemOver).mouseout(itemOut).mousedown(preventDefault);
					$thumbPanel.height($thumbs.outerHeight(true)).click(selectItem);   
					if (tipType == "text" || tipType == "image") {
						initTooltip();
					}
				}
				else {
					$thumbs.hide();
				}
			}			
			
			//init text data
			var initTextData = function($item, padding) {				
				var $p = $item.find(">div:hidden");
				var textWidth =  getPosNumber(parseInt($p.css("width")) - padding, 300);
				var textHeight = getPosNumber(parseInt($p.css("height")) - padding, 0);
				$innerText.width(textWidth).html($p.html());
				if (textHeight < $innerText.height()) {
					textHeight = $innerText.height();
				}
				$item.data("textbox", {x:$p.css("left"), y:$p.css("top"), w:textWidth + padding, h:textHeight + padding + 1, color:$p.css("color"), bgcolor:$p.css("background-color")});
			}
			
			//init tool tip
			var initTooltip = function() {
				$("body").append($tooltip);
				
				var upClass, downClass;
				if (tipType == "text") {
					$tooltip.append("<div class='tt-txt'></div>");
					upClass = "txt-up";
					downClass = "txt-down";
					$thumbs.mouseover(showTooltip).mouseout(hideTooltip).bind("mousemove", moveTooltip);
				}
				else if (tipType == "image") {
					initImgTip();
					upClass = "img-up";
					downClass = "img-down";
					$thumbs.mouseover(showImgTooltip).mouseout(hideTooltip);
				}
				
				switch (ALIGN[cpAlign]) {
					case ALIGN["TL"]: case ALIGN["TC"]: case ALIGN["TR"]:
						$tooltip.data("bottom",true).addClass(downClass);
						break;
					default:
						$tooltip.data("bottom",false).addClass(upClass);
				}
				
				if (jQuery.browser.msie && parseInt(jQuery.browser.version) <= 6) {
					$tooltip.css("background-image", "none").children().css("margin",0);
				}
			}
			
			//show image tooltip
			var showImgTooltip = function(e) {
				var $img = $items[$(this).index()].data("ttImg");
				if ($img != undefined) {
					$tooltip.find(">img").hide();
					$img.show();
					if ($img[0].complete || $img[0].readyState == "complete") {	
						var yOffset = $tooltip.data("bottom") ? $(this).outerHeight() : -$tooltip.outerHeight();
						var offset = $(this).offset();
						$tooltip.css({top:offset.top + yOffset, left:offset.left + (($(this).outerWidth() - $tooltip.outerWidth())/2)})
								.stop(true, true).delay(TOOLTIP_DELAY).fadeIn(300);
					}
				}
			}
			
			//show tooltip
			var showTooltip = function(e) {
				var caption = $items[$(this).index()].data("caption");
				if (caption != "") {					
					$tooltip.find(">div.tt-txt").html(caption);
					var yOffset = $tooltip.data("bottom") ? 0 : -$tooltip.outerHeight(true);
					$tooltip.css({top:e.pageY + yOffset, left:e.pageX}).stop(true, true).delay(TOOLTIP_DELAY).fadeIn(300);
				}
			}
			
			//tooltip move
			var moveTooltip = function(e) {
				var yOffset = $tooltip.data("bottom") ? 0 : -$tooltip.outerHeight(true);
				$tooltip.css({top:e.pageY + yOffset, left:e.pageX});
			}
			
			//hide tooltip
			var hideTooltip = function() {
				$tooltip.stop(true, true).fadeOut(0);
			}
			
			//display control panel
			var displayCPanel = function() {
				$cpanel.stop(true).animate({top:$cpanel.data("pos"), opacity:1}, ANIMATE_SPEED);
			}
			
			//hide control panel
			var hideCPanel = function() {
				$cpanel.stop(true).animate({top:$cpanel.data("offset"), opacity:0}, ANIMATE_SPEED);
			}
			
			//select list item
			var selectItem = function(e) {
				var $item = $(e.target);
				if ($item[0].nodeName != "LI") {
					$item = $item.parents("li").eq(0);
				}
				var i = $item.index();
				if (i > -1 && i != currIndex) {	
					dir = i < currIndex ? PREV : NEXT; 
					resetTimer();
					prevIndex = currIndex;
					currIndex = i;
					loadContent(currIndex);
					hideTooltip();
				}
				return false;
			}
			
			//on item mouseover
			var itemOver = function() {
				$(this).addClass("thumb-over");
			}
			
			//on item mouseout
			var itemOut = function() {
				$(this).removeClass("thumb-over");
			}
			
			//go to previous image
			var prevImg = function() {
				dir = PREV;
				resetTimer();
				prevIndex = currIndex;
				currIndex = (currIndex > 0) ? (currIndex - 1) : (numItems - 1);
				loadContent(currIndex);
				return false;
			}
			
			//go to next image
			var nextImg = function() {
				dir = NEXT;
				resetTimer();
				prevIndex = currIndex;
				currIndex = (currIndex < numItems - 1) ? (currIndex + 1) : 0;
				loadContent(currIndex);
				return false;
			}
			
			//play/pause
			var togglePlay = function() {
				rotate = !rotate;
				$(this).toggleClass("pause", rotate);
				rotate ? startTimer() : pauseTimer();
				return false;
			}
			
			//play
			var play = function() {
				rotate = true;
				$playBtn.toggleClass("pause", rotate);
				startTimer();
			}

			//pause
			var pause = function() {
				rotate = false;
				$playBtn.toggleClass("pause", rotate);
				pauseTimer();
			}
			
			//pause on last item
			var pauseLast = function(i) {
				if (i == numItems - 1) {
					rotate = false;
					$playBtn.toggleClass("pause", rotate);
				}
			}
					
			//on button over
			var buttonOver = function() {
				$(this).addClass("button-over");
			}
			
			//on button out
			var buttonOut = function() {
				$(this).removeClass("button-over");
			}
			
			//update text box
			var updateText = function(e) {
				if (!$textBox.data("visible")) {
					$textBox.data("visible", true);
					var text = $items[currIndex].find(">div:first").html();
					if (text && text.length > 0) {			
						var data = $items[currIndex].data("textbox");
						$innerText.css("color",data.color);
						$textBox.find(".inner-bg").css({"background-color":data.bgcolor, height:data.h-1});
						switch(TEXT_EFFECTS[textEffect]) {
							case TEXT_EFFECTS["fade"]:
								fadeInText(text, data);
								break;
							case TEXT_EFFECTS["down"]:
								expandText(text, data, {width:data.w, height:0}, {height:data.h});
								break;
							case TEXT_EFFECTS["right"]:
								expandText(text, data, {width:0, height:data.h}, {width:data.w});
								break;
							case TEXT_EFFECTS["left"]:
								expandText(text, data, {"margin-left":data.w, width:0, height:data.h}, {width:data.w, "margin-left":0});
								break;
							case TEXT_EFFECTS["up"]:
								expandText(text, data, {"margin-top":data.h, height:0, width:data.w}, {height:data.h, "margin-top":0});
								break;
							default:
								showText(text, data);
						}
					}					
				}
			}
			
			//reset text box
			var resetText = function() {
				$textBox.data("visible", false).stop(true, true);
				switch(TEXT_EFFECTS[textEffect]) {
					case TEXT_EFFECTS["fade"]:
					case TEXT_EFFECTS["down"]:
					case TEXT_EFFECTS["right"]:
					case TEXT_EFFECTS["left"]:
					case TEXT_EFFECTS["up"]:
						if (jQuery.browser.msie) {
							$innerText.css("opacity",0);
						}
						$textBox.fadeOut(ANIMATE_SPEED, function() { $(this).css("display", "none"); });
						break;
					default:
						$textBox.css("display", "none");
				}
			}
			
			//expand text effect
			var expandText = function(text, data, props1, props2) {
				$innerText.css("opacity",1).html("");
				$textBox.stop(true, true).css({display:"block", top:data.y, left:data.x, "margin-top":0, "margin-left":0}).css(props1).animate(props2, ANIMATE_SPEED, 
					function () {  
						$innerText.html(text);
					});  
			}
			
			//fade in text effect
			var fadeInText = function(text, data) {
				$innerText.css("opacity",1).html(text);
				$textBox.css({top:data.y, left:data.x, width:data.w, height:data.h})
						.stop(true, true).fadeIn(ANIMATE_SPEED, function() {
																	if (jQuery.browser.msie) {
																		$innerText[0].style.removeAttribute('filter'); 
																	}});  
			}
			
			//show text effect
			var showText = function(text, data) {
				$textBox.stop(true).css({display:"block", top:data.y, left:data.x, width:data.w, height:data.h});  
				$innerText.html(text);
			}
			
			//display text panel on mouseover
			var displayText = function() {
				$rotator.unbind(UPDATE_TEXT).bind(UPDATE_TEXT, updateText).trigger(UPDATE_TEXT);
			}

			//hide text panel on mouseovers
			var hideText = function() {
				$rotator.unbind(UPDATE_TEXT);
				resetText();
			}
			
			//load current content
			var loadContent = function(i) {
				if (playOnce) {
					pauseLast(i);
				}
				
				//select thumb
				$thumbs.filter(".curr-thumb").removeClass("curr-thumb");
				$($thumbs.get(i)).addClass("curr-thumb");
				
				//set delay
				delay =	$items[i].data("delay");
				
				//reset text
				resetText();
				if (!textSync) {
					$rotator.trigger(UPDATE_TEXT);
				}
				
				//set link
				if ($mainLink) {
					var $currLink = $items[i].find(">a:nth-child(2)");
					var href = $currLink.attr("href");
					if (href) {
						$mainLink.unbind("click", preventDefault).css("cursor", "pointer").attr({href:href, target:$currLink.attr("target")});
					}
					else {
						$mainLink.click(preventDefault).css("cursor", "default");
					}
				}
				
				//load image
				if ($items[i].data("img")) {
					$preloader.hide();
					displayContent($items[i].data("img"));
				}	
				else {	
					//load new image
					var $img = $("<img class='main-img'/>");
					$img.load(
						function() {
							$preloader.hide();
							storeImg($items[i], $(this));
							displayContent($(this));
						}
					).error(
						function() {
							alert("Error loading image");
						}
					);
					$preloader.show();					
					$img.attr("src", $items[i].data("imgurl"));
				}	    
			}
			
			//display content
			var displayContent = function($img) {
				//clear
				if (vStripeEffect) {
					vStripes.clear();
					setPrevious();
				}
				if (hStripeEffect) {
					hStripes.clear();
					setPrevious();
				}
				if (blockEffect) {
					blocks.clear();
					setPrevious();
				}
				
				//get effect number
				var effect = $items[currIndex].data("effect");
				if (effect == EFFECTS["none"] || effect == undefined) {
					showContent($img);
					return;
				}
				else if (effect == EFFECTS["fade"]) {
					fadeInContent($img);
					return;
				}
				else if (effect == EFFECTS["h.slide"]) {
					slideContent($img, "left", screenWidth);
					return;
				}
				else if (effect == EFFECTS["v.slide"]) {
					slideContent($img, "top", screenHeight);
					return;
				}
				
				if (effect == EFFECTS["random"]) {
					effect = Math.floor(Math.random() * (ei - 5));
				}
				
				if (effect <= EFFECTS["spiral.out"]) {
					blocks.displayContent($img, effect);
				}
				else if (effect <= EFFECTS["vert.random.fade"]) {
					vStripes.displayContent($img, effect);
				}
				else {
					hStripes.displayContent($img, effect);
				}
			}
			
			//set previous
			var setPrevious = function() {
				if (prevIndex >= 0) {
					var currSrc = $mainLink.find("img#curr-img").attr("src");
					var prevSrc = $items[prevIndex].data("imgurl");
					if (currSrc != prevSrc) {
						$mainLink.find("img.main-img").attr("id","").hide();
						var $img = $mainLink.find("img.main-img").filter(function() { return $(this).attr("src") == prevSrc; });
						$($img.get(0)).show();
					}
				}
			}
			
			//display content (no effect)
			var showContent = function($img) {
				if (textSync) {
					$rotator.trigger(UPDATE_TEXT);
				}
				$mainLink.find("img.main-img").attr("id","").hide();
				$img.attr("id", "curr-img").show();
				startTimer();
			}
			
			//display content (fade effect)
			var fadeInContent = function($img) {
				$mainLink.find("img#curr-img").stop(true, true);
				$mainLink.find("img.main-img").attr("id","").css("z-index", 0);
				$img.attr("id", "curr-img").stop(true, true).css({opacity:0,"z-index":1}).show().animate({opacity:1}, duration, easing,
					function() {
						$mainLink.find("img.main-img:not('#curr-img')").hide();
						if (textSync) {
							$rotator.trigger(UPDATE_TEXT);
						}
						startTimer();
					}
				);
			}
			
			//slide content
			var slideContent = function($currImg, pos, moveby) {
				$strip.stop(true,true);
				var $prevImg = $("#curr-img", $strip);
				if ($prevImg.size() > 0) {
					$strip.find(".main-img").attr("id","").parents(".content-box").css({top:0,left:0});
					$currImg.attr("id", "curr-img").parents(".content-box").show();
					var $img, dest;
					if (dir == PREV) {
						$strip.css(pos, -moveby);
						$img = $prevImg;
						dest = 0;
					}
					else {
						$img = $currImg;
						dest = -moveby;
					}
					$img.parents(".content-box").css(pos,moveby);
					var prop = (pos == "top") ? {top:dest} : {left:dest};
					$strip.stop(true,true).animate(prop, duration, easing,
										function() {
											$strip.find(".main-img:not('#curr-img')").parents(".content-box").hide();
											$img.parents(".content-box").css({top:0,left:0});
											$strip.css({top:0,left:0});
											if (textSync) {
												$rotator.trigger(UPDATE_TEXT);
											}
											startTimer();
										});
				}
				else {
					$strip.css({top:0,left:0});
					$strip.find(".main-img").parents(".content-box").hide().css({top:0,left:0});
					$currImg.attr("id", "curr-img").parents(".content-box").show();
					if (textSync) {
						$rotator.trigger(UPDATE_TEXT);
					}
					startTimer();
				}
			}
			
			//init img tip
			var initImgTip = function(i) {				
				for (var i = 0; i < numItems; i++) {	
					var $item = $items[i];
					var $img = $item.find(">a:first>img");
					if ($img.size() == 1) {
						$img.addClass("tt-img");
						$tooltip.append($img);
						$item.data("ttImg", $img);
					}
				}
			}
			
			//load image
			var loadImg = function(loadIndex) {
				try {
					var $item = $items[loadIndex];
					var $img = $("<img class='main-img'/>");					
					$img.load(function() {
								if (!$item.data("img")) {
									storeImg($item, $(this));
								}
								loadIndex++
								if (loadIndex < numItems) {
									loadImg(loadIndex);
								}
							})
						.error(function() {
								//error loading image, continue next
								loadIndex++
								if (loadIndex < numItems) {
									loadImg(loadIndex);
								}
							});
					$img.attr("src", $item.data("imgurl"));	
				}
				catch(ex) {}
			}
			
			//process & store image
			var storeImg = function($item, $img) {
				if (globalEffect == "h.slide" || globalEffect == "v.slide") {
					$strip.append($img);
					centerImg($img);
					var $div = $("<div class='content-box'></div>").css({width:screenWidth, height:screenHeight});
					$img.wrap($div);
					$img.css("display","block");
					var $link = $item.find(">a:nth-child(2)");
					if ($link) {
						$img.wrap($link);
					}
				}
				else {
					$mainLink.append($img);
					centerImg($img);
				}
				$item.data("img", $img);
			}
			
			//center image
			var centerImg = function($img) {
				if (autoCenter && $img.width() > 0 && $img.height() > 0) {
					var tDiff = (screenHeight - $img.height())/2;
					var lDiff = (screenWidth  - $img.width())/2
					var top = 0, left = 0, vPad = 0, hPad = 0;
					if (tDiff > 0) {
						vPad = tDiff;
					}
					else if (tDiff < 0) {
						top = tDiff;
					}				
					if (lDiff > 0) {
						hPad = lDiff;
					}
					else if (lDiff < 0) {
						left = lDiff;
					}
					$img.css({top:top, left:left, "padding-top":vPad, "padding-bottom":vPad, "padding-left":hPad, "padding-right":hPad});
				}
			}
			
			//start timer
			var startTimer = function() {
				if (rotate && timerId == null) {
					var duration = Math.round($timer.data("pct") * delay);
					$timer.animate({width:(screenWidth+1)}, duration, "linear");
					timerId = setTimeout(nextImg, duration);
				}
			}
			
			//reset timer
			var resetTimer = function() {
				clearTimeout(timerId);
				timerId = null;
				$timer.stop(true).width(0).data("pct", 1);
			}
			
			//pause timer
			var pauseTimer = function() {
				clearTimeout(timerId);
				timerId = null;
				var pct = 1 - ($timer.width()/(screenWidth+1));
				$timer.stop(true).data("pct", pct);
			}
			
			//shuffle items
			var shuffleItems = function() {			
				for (var i = 0; i < $items.length; i++) {
					var ri = Math.floor(Math.random() * $items.length);
					var temp = $items[i];
					$items[i] = $items[ri];
					$items[ri] = temp;
				}
			}
			
			//check effect
			var checkEffect = function(num) {
				if (num == EFFECTS["random"]) {
					blockEffect = hStripeEffect = vStripeEffect = true;
				}
				else if (num <= EFFECTS["spiral.out"]) {
					blockEffect = true;
				}
				else if (num <= EFFECTS["vert.random.fade"]) {
					vStripeEffect = true;
				}
				else if (num <= EFFECTS["horz.random.fade"]) {
					hStripeEffect = true;
				}
			}
			
			//prevent default behavior
			var preventDefault = function() {
				return false;
			}
		}		
			
		//get positive number
		var getPosNumber = function(val, defaultVal) {
			if (!isNaN(val) && val > 0) {
				return val;
			}
			return defaultVal;
		}

		//get nonnegative number
		var getNonNegNumber = function(val, defaultVal) {
			if (!isNaN(val) && val >= 0) {
				return val;
			}
			return defaultVal;
		}
		
		//shuffle array
		var shuffleArray = function(arr) {
			var total =  arr.length;
			for (var i = 0; i < total; i++) {
				var ri = Math.floor(Math.random() * total);
				var temp = arr[i];
				arr[i] = arr[ri];
				arr[ri] = temp;
			}	
		}
		
		var defaults = { 
			width:825,
			height:300,
			button_width:24,
			button_height:24,
			button_margin:4,			
			auto_start:true,
			delay:DEFAULT_DELAY,
			transition:"fade",
			transition_speed:DURATION,
			cpanel_position:INSIDE,
			cpanel_align:"BR",
			timer_align:"top",
			display_thumbs:true,
			display_dbuttons:true,
			display_playbutton:true,
			display_imgtooltip:true,
			display_numbers:true,
			display_thumbimg:false,
			display_timer:true,
			mouseover_pause:false,
			cpanel_mouseover:false,
			text_mouseover:false,
			text_effect:"fade",
			text_sync:true,
			tooltip_type:"text",
			shuffle:false,
			play_once:false,
			auto_center:false,
			block_size:BLOCK_SIZE,
			vert_size:STRIPE_SIZE,
			horz_size:STRIPE_SIZE,
			block_delay:25,
			vstripe_delay:75,
			hstripe_delay:75,
			easing:""
		};
		
		var opts = $.extend({}, defaults, params);
		return this.each(
			function() {
				var rotator = new Rotator($(this), opts);
				rotator.init();
			}
		);
	}
})(jQuery);