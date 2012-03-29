<script type="text/javascript">
    //<![CDATA[
    function toggle_category_menu(id) {
        if(document.getElementById('cat'+id).style.display == 'block') {
            document.getElementById('cat'+id).style.display = 'none';
            document.getElementById('img'+id).src = '<?php echo $CFG->wwwroot; ?>/theme/conel/pix/t/switch_plus.gif';
        } else {
            document.getElementById('cat'+id).style.display = 'block';
            document.getElementById('img'+id).src = '<?php echo $CFG->wwwroot; ?>/theme/conel/pix/t/switch_minus.gif';
        }
    }

    function restore_category_menu(id) {
        // get the selected element
        var elem = document.getElementById('cat'+id);
        if(elem) {
            // while not at the top of the menu
            while(elem.id != 'category_browser') {
                if(elem.nodeName == 'UL') {
                    // open the container
                	elem.style.display = 'block';
                	// resolve the category id
                	id = elem.id.replace(/cat/,'');
                	// fetch the corresponding image
                	img = document.getElementById('img'+id);
                    // toggle that image
                    if(img && img.alt != 'empty_nav') {
                        img.src = '<?php echo $CFG->wwwroot; ?>/theme/conel/pix/t/switch_minus.gif';
                    }
                }
                elem = elem.parentNode;
            }
        }
    }
    //]]>
</script>