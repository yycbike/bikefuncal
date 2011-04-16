// When adding a new venue, we want to show a "Saved" message by the new location.
// Remember the name of the new venue in this variable.
var locname_to_flash;

function add_venue(name_input, address_input, locked_input, error_result_cell) {
    var ajax_params = {
          'action'  : 'edit-venue',
          'type'    : 'create',
          'nonce'   : BikeFunAjax.nonce,
          'locname' : name_input.val(),          
          'address' : address_input.val(),       
          'locked'  : locked_input.attr('checked'),          
    };
    locname_to_flash = name_input.val();

    var show_result = function(result) {
        if (result.status == 1) {
            load_known_venues();

            // Clear out the inputs
            name_input.val('');
            address_input.val('');
            locked_input.attr('checked', true);
        }
        else {
            var error_message;
            if (result.hasOwnProperty('error')) {
                error_message = result.error;
            }
            else {
                error_message = "Oops! This couldn't be saved";
            }

            error_result_cell.text(error_message);
        }

        // If we got a new nonce, use it.
        if (result.hasOwnProperty('nonce')) {
            BikeFunAjax.nonce = result.nonce
        }
    }
    
    jQuery.post(
        BikeFunAjax.ajaxurl,
        ajax_params,
        show_result,
        'json'
        );    
}

function save_edits(id, name, address, locked, result_cell) {
    var ajax_params = {
          'action'  : 'edit-venue',
          'type'    : 'update',
          'nonce'   : BikeFunAjax.nonce,
          'id'      : id,         
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
        }
        else {
            var error_message;
            if (result.hasOwnProperty('error')) {
                error_message = result.error;
            }
            else {
                error_message = "Oops! This couldn't be saved";
            }

            result_cell.text(error_message);
        }

        // If we got a new nonce, use it.
        if (result.hasOwnProperty('nonce')) {
            BikeFunAjax.nonce = result.nonce
        }
    }
    
    jQuery.post(
        BikeFunAjax.ajaxurl,
        ajax_params,
        show_result,
        'json'
        );    
}

function delete_venue(id, table_row, result_cell) {
    var ajax_params = {
          'action'  : 'delete-venue',
          'nonce'   : BikeFunAjax.nonce,
          'id'      : id,
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

        // If we got a new nonce, use it.
        if (result.hasOwnProperty('nonce')) {
            BikeFunAjax.nonce = result.nonce
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
    var result_cell_to_flash;

    var rows = [];
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
        if (known_venues[idx].locname == locname_to_flash) {
           result_cell_to_flash = result_cell; 
        }

        var do_save = (function(name_input_copy, address_input_copy,
                                locked_input_copy,
                                id_copy, result_cell_copy) {

            return function() {
                save_edits(id_copy, name_input_copy.val(),
                           address_input_copy.val(),
                           locked_input_copy.attr('checked'),
                           result_cell_copy);
            }

        })(name_input, address_input, locked_input, known_venues[idx].id,
           result_cell); 
        save_button.click(do_save);
        name_input.bind('keyup', 'return', do_save);
        address_input.bind('keyup', 'return', do_save);
        
        var do_delete = (function(id_copy, table_row_copy,
                                  result_cell_copy) {

            return function() {
                delete_venue(id_copy,
                             table_row_copy, result_cell_copy);
            }
        })(known_venues[idx].id, tr, result_cell);
        delete_button.click(do_delete);

        rows.push(tr);
    }

    table.empty();
    for (var idx = 0; idx < rows.length; idx++) {
        table.append(rows[idx]);
    }

    if (result_cell_to_flash) {
        result_cell_to_flash.
            text('saved!').
            fadeOut(3000, function() {
                // when done, reset to visible & empty
                result_cell_to_flash.text('').fadeIn(0);
            });
         locname_to_flash = null;
    }

    return;
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
