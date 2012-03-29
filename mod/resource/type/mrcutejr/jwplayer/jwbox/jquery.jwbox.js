jQuery.fn.center = function () {
	this.css("position","fixed");
	this.css("top", ( jQuery(window).height() - this.outerHeight() ) / 2 + "px");
	this.css("left", ( jQuery(window).width() - this.outerWidth() ) / 2 + "px");
    return this;
}

jQuery.jwbox = {
	lightbox	:	null,
	player	: null,
	toggle	: function(context) {
		if (!jQuery.jwbox.lightbox) {
				jQuery.jwbox.lightbox = jQuery(".jwbox_hidden", context);
				jQuery.jwbox.center();
				jQuery("#jwbox_background").fadeIn("fast");
				jQuery.jwbox.lightbox.css("display", "block")
				jQuery.jwbox.center();
				jQuery("#jwbox_background").fadeTo(0, 0.8);
				jQuery("object", context).each(function(){
					jQuery.jwbox.player = document.getElementById(this.id);
				});
		} else if ((context.className == 'jwbox_content')) {
		} else {
			try {
				jQuery.jwbox.player.sendEvent("STOP");
				jQuery.jwbox.player = null;
			} catch (err) {
			}
			jQuery.jwbox.lightbox.css("display","none");
			jQuery.jwbox.lightbox = null;
			jQuery("#jwbox_background").fadeOut("fast");
		}
	},
	center	: function() {
		if (jQuery.jwbox.lightbox) {
			jQuery.jwbox.lightbox.center();
		}
	}
}

jQuery(document).keyup(function(event){
    if (event.keyCode == 27 && jQuery.jwbox.lightbox) {
		jQuery.jwbox.toggle(jQuery("#jwbox_background"));
    }
});

jQuery(document).ready(function () {
	jQuery("body").append('<div id="jwbox_background">&nbsp;</div>');
	jQuery(".jwbox").click(function () {jQuery.jwbox.toggle(this); return false;});
	jQuery("#jwbox_background").click(function () {jQuery.jwbox.toggle(this); return false;});
	jQuery(".jwbox_content").click(function () {jQuery.jwbox.toggle(this); return false;});
	jQuery(window).resize(function() {jQuery.jwbox.center();});
});