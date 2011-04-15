function add_venue(name_input, address_input, locked_input, error_result_cell) {
    var ajax_params = {
          'action'  : 'edit-venue',
          'type'    : 'create',
          'locname' : name_input.val(),          
          'address' : address_input.val(),       
          'locked'  : locked_input.attr('checked'),          
    };

    var show_result = function(result) {
        if (result.status == 1) {
            // @@@ It would be nice to flash the new
            // venue somehow...
            load_known_venues();

            // Clear out the inputs
            name_input.val('');
            address_input.val('');
            locked_input.attr('checked', true);
        }
        else {
            error_result_cell.text("Oops! This couldn't be saved");
        }
    }
    
    jQuery.post(
        BikeFunAjax.ajaxurl,
        ajax_params,
        show_result,
        'json'
        );    

}

function save_edits(canon, name, address, locked, result_cell) {
    var ajax_params = {
          'action'  : 'edit-venue',
          'type'    : 'update',
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

function delete_venue(canon, table_row, result_cell) {
    var ajax_params = {
          'action'  : 'delete-venue',
          'canon'   : canon,
    };

    var show_result = function(result) {
        if (result.status == 1) {
            result_cell.
                text('deleted!').
                fadeOut('slow', function() {
                    table_row.remove();
                });
        }
        else {
            result_cell.text("Oops! An error occurred");
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

        var locked_cell = jQuery('<td></td>');
        var locked_input = jQuery('<input type=checkbox>');
        locked_input.attr('checked', known_venues[idx].locked);
        locked_cell.append(locked_input);
        tr.append(locked_cell);

        var edit_cell = jQuery('<td></td>');
        var save_button = jQuery('<input type=submit value=save>');
        edit_cell.append(save_button);

        var delete_button = jQuery('<input type=submit value=delete>');
        edit_cell.append(delete_button);
        
        tr.append(edit_cell);

        // blank for now. results will go there later.
        var result_cell = jQuery('<td></td>');
        tr.append(result_cell);

        var do_save = (function(name_input_copy, address_input_copy,
                                locked_input_copy,
                                canon_copy, result_cell_copy) {

            return function() {
                save_edits(canon_copy, name_input_copy.val(),
                           address_input_copy.val(),
                           locked_input_copy.attr('checked'),
                           result_cell_copy);
            }

        })(name_input, address_input, locked_input, known_venues[idx].canon,
           result_cell); 
        save_button.click(do_save);
        name_input.bind('keyup', 'return', do_save);
        address_input.bind('keyup', 'return', do_save);
        
        var do_delete = (function(canon_copy, table_row_copy,
                                  result_cell_copy) {

            return function() {
                delete_venue(canon_copy,
                             table_row_copy, result_cell_copy);
            }
        })(known_venues[idx].canon, tr, result_cell);
        delete_button.click(do_delete);

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

function save_new_venue() {
    var name = jQuery('#venue_name');
    var address = jQuery('#venue_address');
    var locked = jQuery('#venue_locked');
    var result = jQuery('#new_venue_result');

    add_venue(name, address, locked, result);
}

// Initialize event handlers
//
// Have to use jQuery(), not $(), because WordPress loads jQuery
// in "no-conflicts" mode, where $() isn't defined.
jQuery(document).ready(function() {
    load_known_venues();

    jQuery('#add_venue').click(save_new_venue);
    jQuery('#venue_name').bind('keyup', 'return', save_new_venue);
    jQuery('#venue_address').bind('keyup', 'return', save_new_venue);

});
