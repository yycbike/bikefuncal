// E-mail mangling

function rot13(text) {
    return text.replace(/[a-zA-Z]/g, function(c){
        return String.fromCharCode((c <= "Z" ? 90 : 122) >= (c = c.charCodeAt(0) + 13) ? c : c - 26);
    });
}

function descramble_emails() {
    jQuery('a.scrambled-email').each(function(index, element) {
        element = jQuery(element);
        var before_at = element.attr('data-ba');
        var after_at = element.attr('data-aa');
        var address = rot13(before_at) + '@' + rot13(after_at);
        var url = 'mailto:' + address;
        element.attr('href', url);
        element.text(address);
    });
}

jQuery(document).ready(function() {
    descramble_emails();
});
