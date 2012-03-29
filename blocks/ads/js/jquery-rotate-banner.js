jQuery(document).ready(function() {

var $container;
		
		jQuery(document).ready(	
			function() {
				$container = jQuery("#container");
				$container.wtRotator({
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
				});
				jQuery("#transitions").val("random").change(
					function() {
						changeTransition(jQuery(this).val());
					}
				);
				jQuery("#cpalignments").val("BR").change(
					function() {
						changeCPAlign(jQuery(this).val());
					}
				);
				jQuery("#thumbs-cb").attr("checked", "checked").change(
					function() {
						displayThumbs(jQuery(this).attr("checked"));	
					}
				);
				jQuery("#dbuttons-cb").attr("checked", "checked").change(
					function() {
						displayDButtons(jQuery(this).attr("checked"));	
					}				
				);
				jQuery("#playbutton-cb").attr("checked", "checked").change(
					function() {
						displayPlayButton(jQuery(this).attr("checked"));	
					}				
				);
				jQuery("#tooltip-cb").attr("checked", "checked").change(
					function() {
						displayTooltip(jQuery(this).attr("checked"));	
					}				
				);								
				jQuery("#text-cb").attr("checked", "").change(
					function() {
						changeDescMouseover(jQuery(this).attr("checked"));	
					}				
				);
				jQuery("#cpanel-cb").attr("checked", "").change(
					function() {
						changeCPMouseover(jQuery(this).attr("checked"));	
					}				
				);				
			}
		);
		
		function changeTransition(transition) {
			$container.updateTransition(transition);
		}
		function changeCPAlign(align) {
			$container.updateCpAlign(align);
		}
		function displayThumbs(display) {
			$container.displayThumbs(display);	
		}
		function displayDButtons(display) {
			$container.displayDButtons(display);	
		}
		function displayPlayButton(display) {
			$container.displayPlayButton(display);	
		}
		function displayTooltip(display) {
			$container.displayTooltip(display);	
		}		
		function changeDescMouseover(mouseover) {
			$container.updateMouseoverDesc(mouseover);			
		}
		function changeCPMouseover(mouseover) {
			$container.updateMouseoverCP(mouseover);
		}

});