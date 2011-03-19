function save_edits(type, canon, name, address, locked, result_cell) {
    var ajax_params = {
          'action'  : 'edit-venue',
          'type'    : type,
          'canon'   : canon,         
          'locname' : name,          
          'address' : address,       
          'locked'  : locked,          
    };

    var show_result = function(result) {
        if (result.status == 1) {
            result_cell.
                text('saved!').
                fadeOut(3000, function() {
                    // when done, reset to visible & empty
                    result_cell.text('');
                    result_cell.fadeIn(0);
                });

            // if we made a new event, re-show the list of
            // known venues
            if (type == 'create') {
                load_known_venues();
            }
        }
        else {
            result_cell.text("Oops! This couldn't be saved");
        }
    }
    
    jQuery.post(
        BikeFunAjax.ajaxurl,
        ajax_params,
        show_result,
        'json'
        );    
}

function display_known_venues(known_venues) {
    var table = jQuery('#known-venues tbody');
    table.empty();
    for (var idx = 0; idx < known_venues.length; idx++) {
        var tr = jQuery('<tr></tr>');

        var name_cell = jQuery('<td></td>');
        var name_input = jQuery('<input type=text>');
        name_input.val(known_venues[idx].locname);
        name_cell.append(name_input);
        tr.append(name_cell);

        var address_cell = jQuery('<td></td>');
        var address_input = jQuery('<input type=text>');
        address_input.val(known_venues[idx].address);
        name_cell.append(address_input);
        tr.append(address_input);

        var locked = jQuery('<td></td>');
        // @@@ Add the lock thing
        tr.append(locked);

        var edit_cell = jQuery('<td></td>');
        var save_button = jQuery('<input type=submit value=save>');
        edit_cell.append(save_button);
        tr.append(edit_cell);

        // blank for now. results will go there later.
        var result_cell = jQuery('<td></td>');
        tr.append(result_cell);

        var do_save = (function(name_input_copy, address_input_copy,
                                canon_copy, result_cell_copy) {

            return function() {
                save_edits('update', canon_copy, name_input_copy.val(),
                           address_input_copy.val(), false,
                           result_cell_copy);
            }

        })(name_input, address_input, known_venues[idx].canon,
           result_cell); 
        save_button.click(do_save);
        
        table.append(tr);
    }
}

function load_known_venues() {
    // Build up a list of arguments to send
    var ajax_params = {
          'action': 'get-known-venues',
    };

    jQuery.post(
        BikeFunAjax.ajaxurl,
        ajax_params,
        display_known_venues,
        'json'
        );    
}


// Initialize event handlers
//
// Have to use jQuery(), not $(), because WordPress loads jQuery
// in "no-conflicts" mode, where $() isn't defined.
jQuery(document).ready(function() {
    load_known_venues();

    jQuery('#add_venue').click(function() {
        var name = jQuery('#venue_name');
        var address = jQuery('#venue_address');
        var result = jQuery('#new_venue_result');

        // @@@ do something for canon
        save_edits('create', name.val(), name.val(), address.val(),
                   true, result);

        // @@@ we should wait until the save succeeds to
        // clear out the old values.
        name.val('');
        address.val('');
    });
});
