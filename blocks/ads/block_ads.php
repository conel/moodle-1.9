<?php
class block_ads extends block_base {
	
	function init() {
		$this->title   = get_string('configtitle', 'block_ads');
		$this->version = 2010122100;
	}

	function instance_allow_config() {
		return true;
	}
	
	function get_content() {
		
		global $CFG, $USER;
		
		if ($this->content !== NULL) {
			return $this->content;
		}
		
		if ($this->config != NULL) {
		
			// Get ads from table
			$query = "SELECT * FROM mdl_block_ads WHERE instanceid = ".$this->instance->id." ORDER BY position ASC";
			$adverts = array();
			if ($ads = get_records_sql($query)) {
				$i = 0;
				foreach ($ads as $ad) {
					$adverts[$i]['id'] = $ad->id;
					$adverts[$i]['image'] = $ad->image;
					$adverts[$i]['link'] = $ad->link;
					$adverts[$i]['title'] = $ad->title;
					$i++;
				}
			}
			// TODO - Make first image come from database
			$html_text = '
			<div id="container">
				<div class="wt-rotator">
					<img id="bg-img" src="'.$CFG->wwwroot.'/blocks/ads/images/spacer.png" />';
					
					if (isset($adverts[0]['image']) && $adverts[0]['image'] != '') {
						$html_text .= '<a href="#"><img id="main-img" src="'.$CFG->wwwroot . '/file.php/1/'.$adverts[0]['image'].'" /></a>';
					} else {
						$html_text = 'No images added';
					}
					
					$html_text .= '
					<div class="desc"></div>
					<div class="preloader"><img src="'.$CFG->wwwroot.'/blocks/ads/images/ajax-loader.gif"/></div>
					<div id="tooltip"></div>
					<div class="c-panel">
						<div class="buttons">   
							<div class="prev-btn"></div>    
							<div class="play-btn"></div>    
							<div class="next-btn"></div>               
						</div>
						<div class="thumbnails">';
			
			// Get images from table
			if (count($adverts > 0)) {
				$html_text .= '<ul>';
				foreach ($adverts as $ad) {
					$html_text .= '<li>
									<a href="'.$CFG->wwwroot . '/file.php/1/'.$ad['image'].'"></a>
									<a href="'.$ad['link'].'" target="_blank" title="'.$ad['title'].'"></a>
								   </li>';
				}
				$html_text .= '</ul>';
			}
			
			// If configs aren't set: set defaults here also

			$html_text .= '</div>
					</div>
				</div>
			</div>
			<script type="text/javascript" src="'.$CFG->wwwroot.'/blocks/ads/js/jquery.wt-rotator.js"></script>
			<link rel="stylesheet" type="text/css" href="'.$CFG->wwwroot.'/blocks/ads/styles/rotator.css" media="screen" /> 
			<script type="text/javascript">
			//<![CDATA[
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
									delay:'.$this->config->display_time.',
									transition:"'.$this->config->transition.'",
									transition_speed:'.$this->config->transition_speed.',
									block_size:100,
									vert_size:50,
									horz_size:50,
									cpanel_align:"BR",
									cpanel_margin:6,
									display_thumbs:false,
									display_dbuttons:'.$this->config->back_fwd.',
									display_playbutton:'.$this->config->play_button.',
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
			//]]>
			</script>
			';
			
		}
		
		$html_footer = '';
		
		$this->content         =  new stdClass;
		$this->content->text   = $html_text;
		$this->content->footer = $html_footer;
	 
		return $this->content;
  }
}