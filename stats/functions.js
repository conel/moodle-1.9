jQuery(document).ready(function(){

    jQuery('#show_key').click(function(event) {
        event.preventDefault();
        if (jQuery('#show_key').html() == 'show key') {
            jQuery('#show_key').html('hide key');
        } else {
            jQuery('#show_key').html('show key');
        }
        jQuery('#type_key').toggle();
    });

    jQuery('.update_link_trends').each(function(index) {
        jQuery(this).click(function(event) { 
            jQuery.blockUI({message: '<h1 style="text-transform:capitalize;">Updating... please wait</h1>'});
			var load_title = document.title;
			document.title = '[UPDATING...] ' + load_title;
            event.preventDefault();

            var get_url  = jQuery(this).attr('href');
            get_url = get_url.replace(/index.php/g, "update_stat.php"); // get_url to be used by ajax

            var link_type = jQuery(this).attr('name');
            if (link_type == 'current') {
                var stat_td  = jQuery(this).parent().siblings();
            } else {
                var stat_td  = jQuery(this).parent().closest('tr').children();
            }
            var stat_tr  = jQuery(this).parent().closest('tr');

            // To show updated image after ajax stat update
            var currentTime = new Date();
            var month = currentTime.getSeconds();
            var stat_graph = jQuery('#stat_graph').attr('src') + "&nocache=" + month;

            // Show loading gif
            jQuery(this).parent().siblings().filter('.td_class').ajaxStart(function() {
                jQuery(this).html('');
                jQuery(this).addClass('ajax_loading');
            });

            jQuery.ajax({
               type: "GET",
               url: get_url,
               success: function(html) {
                   stat_td.detach()
                   stat_tr.prepend(html);
                   jQuery('#stat_graph').attr('src',stat_graph); // reload graph image
                   jQuery.unblockUI();
				   	document.title = load_title;
               }
             });


        });
    });

    jQuery('.update_link_comparisons').each(function(index) {
        jQuery(this).click(function(event) { 
            jQuery.blockUI({message: '<h1 style="text-transform:capitalize;">Updating... please wait</h1>'});
            event.preventDefault();

            stat_td = '';
            var stat_td = jQuery(this).parent();
            var page_url = jQuery(this).attr('href');
            var get_url = jQuery(this).attr('href');
            get_url = get_url.replace(/compare.php/g, "update_stat.php"); // get_url to be used by ajax
            get_url = get_url + '&page=compare';

            // To show updated image after ajax stat update
            var currentTime = new Date();
            var month = currentTime.getSeconds();
            var compare_graph = jQuery('#compare_graph').attr('src') + "&nocache=" + month;

            // Show loading gif
            stat_td.html('');
            stat_td.addClass('ajax_loading');

            jQuery.ajax({
               type: "GET",
               url: get_url,
               success: function(html) {
                   stat_td.removeClass('ajax_loading');
                   stat_td.html(html);
                   jQuery('#compare_graph').attr('src',compare_graph); // reload graph image
                   jQuery.unblockUI();
               }
            });
        });
    });

    // Disabled unchecked filters
    var dir_checked = (jQuery('#filter_directorate').is(':checked'));
    var sch_checked = (jQuery('#filter_school').is(':checked'));
    var cur_checked = (jQuery('#filter_curriculum_area').is(':checked'));
    var did = jQuery('#menudid').val();
    var sid = jQuery('#menusid').val();
    var cid = jQuery('#menucid').val();

    if (sch_checked == false && sid == 0) jQuery('#menusid').attr('disabled','disabled');
    if (cur_checked == false && cid == 0) jQuery('#menucid').attr('disabled','disabled');
    if (dir_checked == false && did == 0) jQuery('#menudid').attr('disabled','disabled');

    // When clicking filters we need to hide show relevant drop-downs
    jQuery('#filter_directorate').click(function(event) {
        jQuery('#menudid').removeAttr('disabled');
        jQuery('#menusid').attr('disabled','disabled');
        jQuery('#menucid').attr('disabled','disabled');

        jQuery('#menudid').attr('value',0);
        jQuery('#menucourse_id').attr('value', 0);

        jQuery('#select_directorates').show();
        jQuery('#select_schools').hide();
        jQuery('#select_curriculum_areas').hide();
        jQuery('#select_course_id').hide();

        // Compare actions
        jQuery('#select_subcategories').hide();
        jQuery('#menusubcat').attr('disabled','disabled');
        jQuery('#select_subsubcategories').hide();
        jQuery('#menusubsubcat').attr('disabled','disabled');

    });
    jQuery('#filter_school').click(function(event) {
        // Trend actions
        jQuery('#menusid').removeAttr('disabled');
        jQuery('#menudid').attr('disabled','disabled');
        jQuery('#menucid').attr('disabled','disabled');

        jQuery('#menusid').attr('value',0);
        jQuery('#menucourse_id').attr('value', 0);

        jQuery('#select_schools').show();
        jQuery('#select_directorates').hide();
        jQuery('#select_curriculum_areas').hide();
        jQuery('#select_course_id').hide();

        // Compare actions
        jQuery('#select_subcategories').hide();
        jQuery('#menusubcat').attr('disabled','disabled');
        jQuery('#select_subsubcategories').hide();
        jQuery('#menusubsubcat').attr('disabled','disabled');
    });
    jQuery('#filter_curriculum_area').click(function(event) {
        // Trend actions
        jQuery('#menucid').removeAttr('disabled');
        jQuery('#menudid').attr('disabled','disabled');
        jQuery('#menusid').attr('disabled','disabled');

        jQuery('#menucid').attr('value',0);
        jQuery('#menucourse_id').attr('value', 0);

        jQuery('#select_curriculum_areas').show();
        jQuery('#select_directorates').hide();
        jQuery('#select_schools').hide();
        jQuery('#select_course_id').hide();

        // Compare actions
        jQuery('#select_subcategories').hide();
        jQuery('#menusubcat').attr('disabled','disabled');
        jQuery('#select_subsubcategories').hide();
        jQuery('#menusubsubcat').attr('disabled','disabled');
    });
    jQuery('#filter_all').click(function(event) {
        jQuery('#menucid').attr('disabled','disabled');
        jQuery('#menudid').attr('disabled','disabled');
        jQuery('#menusid').attr('disabled','disabled');

        jQuery('#select_curriculum_areas').hide();
        jQuery('#select_directorates').hide();
        jQuery('#select_schools').hide();
        jQuery('#select_course_id').hide();
		
		jQuery('#select_subcategories').hide();
		jQuery('#select_subsubcategories').hide();
		
        jQuery('#menucourse_id').attr('value', 0);
    });

     jQuery('#menucid').change(function(event) {
        jQuery('#menucourse_id').attr('disabled', 'disabled');
        jQuery('#menucourse_id').attr('value', 0);
        jQuery('#menucid').removeAttr('disabled');
        jQuery('#menudid').attr('disabled','disabled');
        jQuery('#menusid').attr('disabled','disabled');
        // Compare actions
        jQuery('#menusubcat').attr('disabled','disabled');
        jQuery('#menusubsubcat').attr('disabled','disabled');
        jQuery('#stat_filters').submit();
    });
     jQuery('#menudid').change(function(event) {
        jQuery('#menucourse_id').attr('disabled', 'disabled');
        jQuery('#menucourse_id').attr('value', 0);
        jQuery('#menudid').removeAttr('disabled');
        jQuery('#menucid').attr('disabled','disabled');
        jQuery('#menusid').attr('disabled','disabled');
        // Compare actions
        jQuery('#menusubcat').attr('disabled','disabled');
        jQuery('#menusubsubcat').attr('disabled','disabled');
        jQuery('#stat_filters').submit();
    });
     jQuery('#menusid').change(function(event) {
        jQuery('#menucourse_id').attr('disabled', 'disabled');
        jQuery('#menucourse_id').attr('value', 0);
        jQuery('#menusid').removeAttr('disabled');
        jQuery('#menudid').attr('disabled','disabled');
        jQuery('#menucid').attr('disabled','disabled');
        // Compare actions
        jQuery('#menusubcat').attr('disabled','disabled');
        jQuery('#menusubsubcat').attr('disabled','disabled');
        jQuery('#stat_filters').submit();
    });

    jQuery('#menusubcat').change(function(event) {
        jQuery('#menusubsubcat').attr('value', 0);
        jQuery('#menusubsubcat').attr('disabled','disabled');
        jQuery('#stat_filters').submit();
    });

});
