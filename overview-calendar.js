// The popup library comes from here:
// http://www.ericmmartin.com/projects/simplemodal/

function rot13(text) {
    return text.replace(/[a-zA-Z]/g, function(c){
        return String.fromCharCode((c <= "Z" ? 90 : 122) >= (c = c.charCodeAt(0) + 13) ? c : c - 26);
    });
}

// Use the same set of options twice: Once when loading the
// spinner and once when loading the real content.
var BfcPopupOptions = {
    // Click the overlay to close
    'overlayClose': true,

    // Opacity of the overlay (in percent)
    'opacity': 20,
};

function update_popup(popup_content_html) {
    // Close the popup with the spinner
    jQuery.modal.close();

    // There are two ways of opening a modal window. 
    // 1. jQuery(content).modal(options);
    // 2. jQuery.modal(content, options);
    //
    // If you use way #1 here, the popup is too small and the
    // content gets clipped.
    jQuery.modal(popup_content_html, BfcPopupOptions);

    // Attach click-to-email actions
    jQuery('.leader-email > a').each(function(index, element) {
        element = jQuery(element);
        var before_at = element.attr('data-ba');
        var after_at = element.attr('data-aa');
        var address = rot13(before_at) + '@' + rot13(after_at);
        var url = 'mailto:' + address;
        element.attr('href', url);
        element.text(address);
    });

    // Attach popup to previous & next buttons
    jQuery('.event-navigation a').each(function(index, element) {
        element = jQuery(element);
        element.attr('href', '#');
        element.click(function() {
            jQuery.modal.close();
            launch_popup(element);
        });
    });
}

function launch_popup(element) {
    var ajax_params = {
        'action': 'event-popup',
        'id': element.attr('data-id'),
        'date': element.attr('data-date'),
    };

    // For now, put up a spinner popup
    var initialPopup = jQuery('<div id=cal-popup><img id=spinner></div>');
    initialPopup.find('#spinner').attr('src', BikeFunAjax.spinnerURL);
    initialPopup.modal(BfcPopupOptions);

    // Get the results back as text, so we don't have
    // to worry about validating them as XML
    jQuery.post(
        BikeFunAjax.ajaxURL,
        ajax_params,
        update_popup, 
        'text');

    return false;
}

jQuery(document).ready(function() {
    jQuery('.event-title a').each(function(index, element) {
        element = jQuery(element);
        element.click(function() {
            launch_popup(element)
        });
    });
});
