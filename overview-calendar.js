function display_popup(popup_content) {
    var options = {
        'overlayClose': true,
        'opacity': 20,
    };
    jQuery.modal(popup_content, options);
}

jQuery(document).ready(function() {
    jQuery('.event-title a').each(function(index, element) {
        element = jQuery(element);
        element.click(function() {
            var ajax_params = {
                'action': 'event-popup',
                'id': element.attr('data-id'),
                'date': element.attr('data-date'),
            };

            // Get the results back as text, so we don't have
            // to worry about validating them as XML
            jQuery.post(
                BikeFunAjax.ajaxurl,
                ajax_params,
                display_popup, 
                'text');

            return false;
        });
    });
});
