/**
 * Slideshow - a slideshow <http://www.electricprism.com/aeron/slideshow/> and
 * 		  smoothslideshow <http://smoothslideshow.jondesign.net/> fusion.
 *
 * Copyright (c) 2007 Olmo Maldonado
 * 
 * From version 3.0.3, fixes and enanchements from Djamil Legato
 */

var Slideshow = new Class({

	version: '3.0.3',
		
	options: {
		captions: true,
		showTitleCaption: true,
		classes: ['prev', 'next', 'active'],
		duration: [2000, 4000],
		path: '/',
		navigation: false,
		pan: 100,
		resize: true,
		thumbnailre: [/\./, 't.'],
		transition: Fx.Transitions.Sine.easeInOut,
		type: 'fade',
		zoom: 50,
		loadingDiv: true,
		removeDiv: true
	},
	
	styles: {
		caps: {
			div: {
				opacity: 0,
				position: 'absolute',
				width: '100%',
				margin: 0,
				left: 0,
				bottom: 0,
				height: 40,
				background: '#333',
				color: '#fff',
				textIndent: 0		
			},
			
			h2: {
				color: 'red',
				padding: 0,
				fontSize: '80%',
				margin: 0,
				margin: '2px 5px',
				fontWeight: 'bold'
			},
			
			p: {
				padding: 0,
				fontSize: '60%',
				margin: '2px 5px',
				color: '#eee'
			}
		}	
	},
	
	initialize: function(el, options) {
		this.setOptions($merge({
			onClick: this.onClick.bind(this)
		}, options));
		
		if(!this.options.images) return;
		this.options.pan = this.mask(this.options.pan);
		this.options.zoom = this.mask(this.options.zoom);
		
		this.el = $(el).empty();
		
		this.caps = {
			div: new Element('div', {
				styles: this.styles.caps.div,
				'class': 'captionDiv'
			}),
			h2: new Element('h2', {
				styles: this.styles.caps.h2,
				'class': 'captionTitle'
			}),
			p: new Element('p', {
				styles: this.styles.caps.p,
				'class': 'captionDescription'
			})
		};
		
		this.fx = [];

		var trash = new ImageLoader(this.el, this.options.images, {
			loadingDiv: this.options.loadingDiv,
			onComplete: this.start.bind(this),
			path: this.options.path,
			removeDiv: this.options.removeDiv
		});
	},
	
	start: function() {
		this.imgs = $A(arguments);
		this.a = this.imgs[0].clone().set({			
			styles: {
				display: 'block',
				position: 'absolute',
				left: 0,
				'top': 0,
				zIndex: 1
			}
		}).injectInside(this.el);
		
		var obj = this.a.getCoordinates();
		this.height = this.options.height || obj.height;
		this.width = this.options.width || obj.width;
		
		this.el.setStyles({
			display: 'block',
			position: 'relative',
			width: this.width
		});
		
		this.el.empty();
		this.el.adopt((new Element('div', {
			events: {
				'click': this.onClick.bind(this)
			},
			styles: {
				display: 'block',
				overflow: 'hidden',
				position: 'relative',
				width: this.width,
				height: this.height
			}
		})).adopt(this.a));
		
		this.resize(this.a, obj);
		this.b = this.a.clone().setStyle('opacity', 0).injectAfter(this.a);
		
		this.timer = [0, 0];
		this.navigation();
		
		this.direction = 'left';
		this.curr = [0,0];
		
		$(document.body).adopt(new Element('div', {
			id: 'hiddenDIV',
			styles: {
				visibility: 'hidden',
				height: 0,
				width: 0,
				overflow: 'hidden',
				opacity: 0
			}
		}));
		this.loader = this.imgs[0];
		$('hiddenDIV').adopt(this.loader);
		this.load();
	},
	
	load: function(fast) {
		if ($time() > this.timer[0]) {
			this.img = (this.curr[1] % 2) ? this.b : this.a;
			this.img.setStyles({
				opacity: 0,
				width: 'auto',
				height: 'auto',
				zIndex: this.curr[1]
			});
			
			var url = this.options.images[this.curr[0]].url;
			this.img.setStyle('cursor', (url != '#' && url != '') ? 'pointer' : 'default');
			
			this.img.setProperties({
				src: this.loader.src,
				title: this.loader.title,
				alt: this.loader.alt
			});
			
			this.resize(this.img, this.loader);
			
			if(fast){
				this.img.setStyles({
					top: 0,
					left: 0,
					opacity: 1
				});
				
				this.captions();
				this.loaded();			
				return;
			}
			
			this.captions();
			this[this.options.type.test(/push|wipe/) ? 'swipe' : 'kens']();
			this.loaded();
		} else {
			this.timeout = this.load.delay(100, this);
		}
	},
	
	loaded: function() {
		if(this.ul) {
			this.ul.getElements('a[name]').each(function(a, i) {
				a[(i === this.curr[0] ? 'add' : 'remove') + 'Class'](this.options.classes[2]);
			}, this);
		}
		
		this.direction = 'left';
		this.curr[0] = (this.curr[0] + 1) % this.imgs.length;
		this.curr[1]++;
		
		this.timer[0] = $time() + this.options.duration[1] + (this.options.type.test(/fade|push|wipe/) ? this.options.duration[0] : 0);		
		this.timer[1] = $time() + this.options.duration[0];
		
		this.loader = this.imgs[this.curr[0]];
		$('hiddenDIV').empty().adopt(this.loader);
		this.load();
	},
	
	kens: function() {
		this.img.setStyles({
			bottom: 'auto',
			right: 'auto',
			left: 'auto',
			top: 'auto'
		});
		
		var arr = ['left top', 'right top', 'left bottom', 'right bottom'].getRandom().split(' ');
		arr.each(function(p) {
			this.img.setStyle(p, 0);
		}, this);
		
		var zoom = this.options.type.test(/zoom|combo/) ? this.zoom() : {};
		var pan = this.options.type.test(/pan|combo/) ? this.pan(arr) : {};
		
		this.fx.push(this.img.effect('opacity', {duration: this.options.duration[0]}).start(1));
		this.fx.push(this.img.effects({duration: this.options.duration[0] + this.options.duration[1]}).start($merge(zoom, pan)));
	},
	
	zoom: function() {
		var n = Math.max(this.width / this.loader.width, this.height / this.loader.height);
		var z = (this.options.zoom === 'rand') ? Math.random() + 1 : (this.options.zoom.toInt() / 100.0) + 1;
		
		var eh = Math.ceil(this.loader.height * n);
		var ew = Math.ceil(this.loader.width * n);
		
		var sh = (eh * z).toInt();
		var sw = (ew * z).toInt();

		return {height: [sh, eh], width: [sw, ew]};
	},
	
	pan: function(arr) {
		var ex = this.width - this.img.width, ey = this.height - this.img.height;
		var p = this.options.pan === 'rand' ? Math.random() : Math.abs((this.options.pan.toInt() / 100) - 1);
		var sx = (ex * p).toInt(), sy = (ey * p).toInt();

		var x = this.width / this.loader.width > this.height / this.loader.height;
		var obj = {};
		obj[arr[x ? 1 : 0]] = x ? [sy, ey] : [sx, ex];
		return obj;
	},

	swipe: function() {
		var arr, p0 = {}, p1 = {}, x;
		this.img.setStyles({
			left: 'auto',
			right: 'auto',
			opacity: 1
		}).setStyle(this.direction, this.width);
		
		if(this.options.type === 'wipe') {
			this.fx.push(this.img.effect(this.direction, {
				duration: this.options.duration[0],
				transition: this.options.transition
			}).start(0));			
		} else {
			arr = [this.img, this.curr[1] % 2 ? this.a : this.b];
			p0[this.direction] = [this.width, 0];
			p1[this.direction] = [0, -this.width];
			
			if(arr[1].getStyle(this.direction) === 'auto') {
				x = this.width - arr[1].getStyle('width').toInt();
				
				arr[1].setStyle(this.direction, x);
				arr[1].setStyle(this.direction === 'left' ? 'right' : 'left', 'auto');
				
				p1[this.direction][0] = x;
			}
			
			this.fx.push(new Fx.Elements(arr, {
				duration: this.options.duration[0],
				transition: this.options.transition
			}).start({
				'0': p0,
				'1': p1
			}));
		}
	},
	
	captions: function(img) {
		img = img || this.img;
		if(!this.options.captions || (!img.title && !img.alt)) return;

		this.el.getFirst().adopt(this.caps.div.adopt(this.caps.h2, this.caps.p));
		
		(function () {
			if (this.options.showTitleCaption) this.caps.h2.setHTML(img.title);
			this.caps.p.setHTML(img.alt);
			this.caps.div.setStyle('zIndex', img.getStyle('zIndex')*2 || 10);
			
			this.capsHeight = this.capsHeight || this.options.captionHeight || this.caps.div.offsetHeight;
			
			var fx = this.caps.div.effects().set({'height': 0}).start({
				opacity: 0.7,
				height: this.capsHeight
			});
			
			(function(){
				fx.start({
					opacity: 0,
					height: 0
				});
			}).delay(1.00*(this.options.duration[1] - this.options.duration[0]));
		}).delay(0.75*(this.options.duration[0]), this);
	},
	
	navigation: function() {
		if(!this.options.navigation) return;
		var i, j, atemp;
		var fast = this.options.navigation.test(/fast/) ;
		this.ul = new Element('ul');
		var li = new Element('li'), a = new Element('a');
		
		if (this.options.navigation.test(/arrows/)) {
			this.ul.adopt(li.clone()
				.adopt(a.clone()
					.addClass(this.options.classes[0])
					.addEvent('click', function() {
						if (fast || $time() > this.timer[1]) {	
							$clear(this.timeout);
					
							// Clear the FX array only for fast navigation since this stops combo effects
							if(fast) {
								this.fx.each(function(fx) {
									fx.time = 0;
									fx.options.duration = 0;
									fx.stop(true);
								});
							}
		
							this.direction = 'right';
							this.curr[0] = (this.curr[0] < 2) ? this.imgs.length - (2 - this.curr[0]) : this.curr[0] - 2;
							this.timer = [0];
							
							this.loader = this.imgs[this.curr[0]];
							this.load(fast);
						}
					}.bind(this))
				)
			);
		}
		
		if (this.options.navigation.test(/arrows\+|thumbnails/)) {
			for (i = 0, j = this.imgs.length; i < j; i++) {
				atemp = a.clone().setProperty('name', i);
				if (this.options.navigation.test(/thumbnails/)) atemp.setStyle('background-image', 'url(' + this.imgs[i].src + ')');
				if(i === 0) a.className = this.options.classes[2];
				
				atemp.onclick = function(i) {
					if(fast || $time() > this.timer[1]) {
						$clear(this.timeout);
						
						if (fast) {
							this.fx.each(function(fx) {
								fx.time = 0;
								fx.options.duration = 0;
								fx.stop(true);
							});
						}
					
						this.direction = (i < this.curr[0] || this.curr[0] === 0) ? 'right' : 'left';
						this.curr[0] = i;
						this.timer = [0];			
						
						this.loader = this.imgs[this.curr[0]];							
						this.load(fast);
					}
				}.pass(i, this);
		
				this.ul.adopt(li.clone().adopt(atemp));
			}
		}
		
		if (this.options.navigation.test(/arrows/)) {
			this.ul.adopt(li.clone()
				.adopt(a.clone()
					.addClass(this.options.classes[1])
					.addEvent('click', function() {
						if (fast || $time() > this.timer[1]) {	
							$clear(this.timeout);
		
							// Clear the FX array only for fast navigation since this stops combo effects
							if (fast) {
								this.fx.each(function(fx) { 
									fx.time = 0;
									fx.options.duration = 0;
									fx.stop(true); 
								});
							}
		
							this.timer = [0];					
		
							this.load(fast);
						}
					}.bind(this))
				)
			);
		}

		this.ul.injectInside(this.el);
	},

	onClick: function(e) {
		e = new Event(e).stop();
		var cur = this.curr[1] % this.imgs.length;
		var index = this.curr[1] == 0 ? 1 : cur == 0 ? this.imgs.length : cur;
		var url = this.options.images[index - 1].url;
		if(url == '#' || url == '') return;
		window.location.href = url;
	},

	mask: function(val, set, lower, higher) {
		if(val != 'rand') {
			val = val.toInt();
			val = isNaN(val) || val < lower || val > higher ? set : val;
		}
		
		return val;
	},
	
	resize: function(obj, to) {
		var n;
		if(this.options.resize) {
			n = Math.max(this.width / to.width, this.height / to.height);
			obj.setStyles({
				height: Math.ceil(to.height*n),
				width: Math.ceil(to.width*n)
			});
		}
	}
});
Slideshow.implement(new Options);

/**
 * ImageLoader, Image preloader with progress reporting, with small 
 * 		changes by Olmo Maldonado, <http://olmo-maldonado.com/> (denoted by 
 * 		// at the end of the line)
 * 
 * 
 * @author tomocchino, <http://www.tomocchino.com/>
 *
 */
var ImageLoader = new Class({
	
	version:'.5-olmo-ver',
	
	options: {
		loadingDiv    : false,
		loadingPrefix : 'loading images: ',
		loadingSuffix : '',
		path		  : '',
		removeDiv	  : true
	},
	
	initialize: function(container, sources, options){
		this.setOptions(options);
		this.loadingDiv = (this.options.loadingDiv) ? $(container) : false;
		this.images     = [];
		this.index      = 0;
		this.total      = sources.length;
		
		if(this.loadingDiv) {
			this.loadingText = new Element('div').injectInside(this.loadingDiv);
			this.progressBar = new Element('div', {
				styles: {
					width: 100,
					padding: 1,
					margin: '5px auto',
					textAlign: 'left',
					overflow: 'hidden',
					border: 'solid 1px #333'
				}
			}).adopt(new Element('div', {
				styles: {
					width: '0%',
					height: 10,
					backgroundColor: '#333'
				}
			})).injectInside(this.loadingDiv);
		}
		
		this.loadImages.delay(200, this, [sources]);
	},
	
	reset: function() {
		this.index = 0;
		if(this.loadingDiv) {
			this.progressBar.getFirst().setStyle('width', '0%');
			this.loadingText.setHTML(this.options.loadingPrefix);
		}
	},
	
	loadImages: function(sources) {
		var self = this;
		this.reset();
		this.images  = [];
		this.sources = sources;
		
		this.timer = setInterval(this.loadProgress.bind(this), 100);
		for(var i = 0, j = sources.length; i < j; i++) {
			this.images[i] = new Asset.image((this.sources[i].path || this.options.path) + this.sources[i].file, {
				title: self.sources[i].title,
				alt: self.sources[i].desc,
				'onload'  : function(){ self.index++; },
				'onerror' : function(){ self.index++; self.images.splice(i,1); },
				'onabort' : function(){ self.index++; self.images.splice(i,1); }
			});
		}
	},
	
	loadProgress: function() {
		if(this.loadingDiv) {
			this.loadingText.setHTML(this.options.loadingPrefix + this.index + '/' + this.total + this.options.loadingSuffix);
			this.progressBar.getFirst().setStyle('width', (!this.total ? 0 : this.index.toInt()*100 / this.total) + '%');
		}

		if(this.index >= this.total) {
			this.loadComplete();
		} 
	},
	
	loadComplete: function(){
		$clear(this.timer);
		if(this.loadingDiv) {
			this.loadingText.setHTML('Loading Complete');
			
			if(this.options.removeDiv) {
				this.loadingText.empty().remove();
				this.progressBar.empty().remove();
			}
		}
		this.fireEvent('onComplete', this.images);
	},
	
	cancel: function(){
		$clear(this.timer);
	}
	
});

ImageLoader.implement(new Events, new Options);
