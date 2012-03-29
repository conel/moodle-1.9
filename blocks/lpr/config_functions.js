jQuery(document).ready(function(){

    jQuery('a.target_rename').click(function(event) {
        event.preventDefault();
        var name = jQuery(this).attr('title');
        var id = jQuery(this).attr('href');
        jQuery('#target_rename_holder').show();
        jQuery('#target_to_rename').val(name);
        jQuery('#target_to_rename').focus();
        jQuery('#target_id_rename').val(id);
    });

    jQuery('a.target_delete').click(function(event) {
        event.preventDefault();
        var name = jQuery(this).attr('title');
        var target_id = jQuery(this).attr('href');
        var answer = confirm('Are you sure you want to delete ' + name + '?'); 
        if (answer) {
            jQuery('#target_id_to_del').val(target_id);
            jQuery(this).closest('form').submit();
        }
    });

    jQuery('#target_update').change(function(event) {
        self.location = jQuery('#target_update').val();
    });
});
