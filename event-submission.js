// JavaScript that goes along with event-submission-form.php
//
// It handles the AJAX requests used to update the form & show
// previews. It should also do form validation, someday.
//
// The analagous function in the old code is calform.js.
//
// In the new code, we avoid putting JavaScript in the PHP or HTML
// files. It all lives here. Instead of attaching code via the
// onclick="..." HTML syntax, we attach it using jQuery and
// event handlers. 

// Return TRUE if we are editing an existing event
function is_existing_event() {
    var action = jQuery('#submission_action').val();
    return action == "update";
}

// Adjust the visibility of a date's newsflash field
function vfydatesnewsflash(value, suffix)
{
    fields = document.getElementsByName("event_newsflash"+suffix);

    // Not all dates have a newsflash
    if (fields.length == 0) {
        return;
    }
    
    if (value == "Added" || value == "Skipped" || value == "Deleted") {
	fields[0].style.display = "none";
    }
    else {
	fields[0].style.display = "inline";
    }
}

// Generate an option for a daily status field
function vfydatesoption(select, option_value, current_value)
{
    var option = jQuery('<option></option>');
    option.val(option_value);
    option.text(option_value);
    select.append(option);

    // Mark this option as selected in the SELECT
    // element, if it should be.
    if (option_value == current_value) {
        select.val(option_value);
    }
}

function display_dates(xmlDom)
{
    var mydatelist = document.getElementById("datelist");
    
    tmp = xmlDom.getElementsByTagName("error");
    if (tmp.length != 0) {
        var error_message = tmp[0].firstChild.nodeValue;
        
        mydatelist.innerHTML = "<em class='date-error'>" +
            htmlspecialchars(error_message) +
            "<\em>";
        mydatelist.style.display = "block";
    }
    else {
        // if a canonical form of "dates" is returned, use it
        tmp = xmlDom.getElementsByTagName("canonical");
        if (tmp.length != 0) {
            //!!!
            olddates =  tmp[0].firstChild.nodeValue
                ? tmp[0].firstChild.nodeValue
                : "";

            //$('#event_dates').value = olddates;
            datesfield = document.getElementById('event_dates');
            datesfield.value = olddates;
        }
    }

    // Get the datestype
    tmp = xmlDom.getElementsByTagName("datestype");
    if (tmp.length != 0) {
        olddatestype = tmp[0].firstChild.nodeValue
            ? tmp[0].firstChild.nodeValue
            : "";
    }

    // Get the list of dates.  If there's more than one then
    // show the list in the "datelist" div.
    dates = xmlDom.getElementsByTagName("date");
    if (dates.length == 0) {
        mydatelist.innerHTML = "<em>No Dates<\em>";
        mydatelist.style.display = "block";
    }
    else if (dates.length == 1 &&
             dates[0].getElementsByTagName("status")[0].firstChild.nodeValue == "Added") {
        suffix = dates[0].getElementsByTagName("suffix")[0].firstChild.nodeValue;
        mydatelist.innerHTML = "<input type=hidden name=status" + suffix + " value=Added>";
        mydatelist.style.display = "none";
    }
    else {
        // The message that appears above the table
        var message_text = "In this table, you can adjust the status";
        if (is_existing_event()) {
            message_text += " and newsflash";
        }
        message_text += " of each date.\n";
        var message = jQuery("<p>" + message_text + "</p>");

        // The table of dates & statuses (and possibly newsflashes)
        var table = jQuery("<table class=datelist></table>");
        var header_row = jQuery("<tr></tr>");
        header_row.append(jQuery("<th class=dates>Date</th>"));
        header_row.append(jQuery("<th class=dates>Status</th>"));
        if (is_existing_event()) {
            header_row.append(jQuery("<th class=dates>Newsflash</th>"));
        }
        table.append(header_row);

        for (i = 0, j = 0.1; i < dates.length; i++) {
            var change = dates[i].getElementsByTagName("change")[0].firstChild.nodeValue;
            var newdate = dates[i].getElementsByTagName("newdate")[0].firstChild.nodeValue;
            var olddate = dates[i].getElementsByTagName("olddate")[0].firstChild.nodeValue;
            var hrdate = dates[i].getElementsByTagName("hrdate")[0].firstChild.nodeValue;
            var status = dates[i].getElementsByTagName("status")[0].firstChild.nodeValue;
            var suffix = dates[i].getElementsByTagName("suffix")[0].firstChild.nodeValue;
            var exception = dates[i].getElementsByTagName("exception")[0].firstChild
                ? dates[i].getElementsByTagName("exception")[0].firstChild.nodeValue
                : "";
            var newsflash = dates[i].getElementsByTagName("newsflash")[0].firstChild
                ? dates[i].getElementsByTagName("newsflash")[0].firstChild.nodeValue
                : "";

            var row = jQuery("<tr></tr>");
        
            if (change == "Y") {
                row.addClass("changeddate");
            }
            else {
                row.addClass("unchangeddate");
            }

            var datecell = jQuery("<td>" + hrdate + "</td>");
            row.append(datecell);

            var statuscell = jQuery("<td></td>");
            if (status == "Exception") {
                var input = jQuery("<input />");
                input.attr({
                    type: 'hidden',
                    name: 'status' + suffix,
                    value: 'Exception',
                });
                statuscell.append(input);

                var editlink = jQuery("<a></a>");
                editlink.text('Exception');
                editlink.attr('href', '#'); // @@@ Make a real edit link
                editlink.click(function() {
                    var message = "You are currently editing your normal event. " +
                        "This link takes you to a page where you can edit the " +
                        "exception. Any changes you were making to the normal " +
                        "event will be discarded if you do this. Are you sure " +
                        "you want to go to the exception now?";
                    return confirm(message);
                });
                statuscell.append(editlink);
            }
            else {
                // Not an exception
                var status_select = jQuery("<select></select>");
                status_select.attr({
                    'name' : 'event_status' + suffix,
                });

                // We have to go through gyrations to make a copy of the
                // suffix variable. Otherwise all the status selectors
                // will have the same value of suffix.
                // See http://stackoverflow.com/q/1779847
                var on_change = (function(suffix_copy) {
                    return function() {
                        vfydatesnewsflash(this.value, suffix_copy);
                    };
                }(suffix));
                status_select.change(on_change);

                if (olddate == "Y") {
                    if (status == "As Scheduled" || newdate == "Y") {
                        vfydatesoption(status_select, 'As Scheduled', status);
                    }
                    vfydatesoption(status_select, 'Canceled', status);
                    vfydatesoption(status_select, 'Deleted', status);

                    if (newdate == "Y") {
                        vfydatesoption(status_select, 'Exception', status);
                    }

                    if (status == "Skipped") {
                        vfydatesoption(status_select, 'Skipped', status);
                    }
                }
                else {
                    vfydatesoption(status_select, 'Added', status);
                    vfydatesoption(status_select, 'Skipped', status);
                }

                status_select.change(update_preview);
                
                statuscell.append(status_select);
            }
            row.append(statuscell);

            if (is_existing_event()) {
                var newsflash_cell = jQuery("<td></td>");

                var newsflash_input = jQuery("<input></input>");
                newsflash_input.attr({
                    'type'  : 'text',     
                    'size'  : 40,         
                    'class' : 'newsflash',
                    'name'  : 'event_newsflash'  + suffix,
                    'value' : newsflash,  
                    
                });

                if (status == "Added" ||
                    status == "Skipped" ||
                    status == "Deleted") {

                    newsflash_input.css('display', 'none');
                }
                else {
                    newsflash_input.css('display', 'inline');
                }
                newsflash_input.blur(update_preview);

                newsflash_cell.append(newsflash_input);
                row.append(newsflash_cell);
            }
            
            table.append(row);
        }
        jQuery(mydatelist).empty();
        jQuery(mydatelist).
            append(message).
            append(table);
        mydatelist.style.display = "block";
    }

    // Adjust the way dates are input.  Usually this is
    // controlled by the "form" parameter, but if the form
    // would normally use a <select> and the current value
    // is for multiple days, that won't work.

    // @@@ - This had to do with chaning between the 3 styles of forms
    // in the old code...
    //
    //adjustdatesinput(oldform);
}

// Check whether a given "dates" string is parseable.  Fetch the status of
// each day, and show this in the "datelist" div.
var olddatestype;
var olddates;
function verifydates(value, reload)
{
    // Guard against superfluous calls
    if (value == olddates && !reload) {
	return;
    }
    olddates = value;

    // locate the datelist div
    mydatelist = document.getElementById("datelist");

    // don't bother sending a message for empty "dates" value
    if (value == "") {
	mydatelist.innerHTML = "<em>No Dates<\em>";
	mydatelist.style.display = "none";
	return;
    }

    var ajax_params = {
          'action': 'verify-dates',
          'dates': value,          
    };

    if (jQuery('#calform_event_id')) {
        id = jQuery('#calform_event_id').val();
        ajax_params['id'] = id;
    }

    if (reload) {
        ajax_params['reload'] = 'y';
    }

     jQuery.post(
         BikeFunAjax.ajaxurl,
         ajax_params,
         display_dates,
         'xml'
         );
}

// Compute a 12-hour time from a 24-hour time and an offset number of minutes
function twelvehour(when, minutes)
{
    var when, h, m, ampm;

    h = Math.floor(when.substr(0,2));
    m = Math.floor(when.substr(3,2));
    m = (h * 60) + m + Math.floor(minutes);
    h = Math.floor(m / 60);
    m = m % 60;
    if (h >= 24)
	    h -= 24;
    if (h >= 12)
	    ampm = "pm";
    else
	    ampm = "am";
    if (h == 0)
	    h = 12;
    else if (h > 12)
	    h -= 12;
    when = h + ":";
    if (m < 10)
	    when = when + "0";
    when = when + m + ampm;
    return when;
}


// This is used to make the default eventtime be 7pm... but not until
// the user tries to actually edit that field.
function tweaktime()
{
    var field = document.getElementById('event_eventtime');
    if (field.value == "") {
	field.value = "19:00:00";
    }
}


// Update the list of durations to reflect the chosen eventtime.
function tweakdurations()
{
    var sel = document.getElementById('event_eventduration');
    var eventtime = document.getElementById('event_eventtime').value;
    
    var i;
    var when;

    // Adjust the descriptions of all items except first ("unspecified")
    for (i = 1; i < sel.length; i++) {
	// Replace the old ending time, if any, with the new one, if any
	label = sel.options[i].text.split(",");
	if (eventtime == "") {
	    sel.options[i].text = label[0];
	} else {
	    when = twelvehour(eventtime, sel.options[i].value);
	    sel.options[i].text = label[0] + ", ending " + when;
	}
    }
}

function display_preview(preview_content)
{
    var preview = document.getElementById('preview');

    preview.innerHTML = preview_content;

    // disable any links in the preview
    jQuery('a', preview).each(function(index, element) {
        element.href = '#';
    });                                      
}

// Update the preview
function update_preview() {

    // Build up a list of arguments to send
    var ajax_params = {
          'action': 'preview-event-submission',
    };
    
    jQuery('form#event-submission-form input[type=text]').
        each(function(index, element) {

        ajax_params[element.name] = element.value;
    });

    jQuery('form#event-submission-form textarea').
        each(function(index, element) {

        ajax_params[element.name] = element.value;
    });

    jQuery('form#event-submission-form select').
        each(function(index, element) {

        var value = element.options[element.selectedIndex].value;

        ajax_params[element.name] = value;
    });

    jQuery('form#event-submission-form input[type=radio]').
        each(function(index, element) {
                 
        // One of the elements in the radio group will be checked,
        // so we'll always report a value.
        if (element.checked) {
            ajax_params[element.name] = element.value;
        }
    });

    jQuery('form#event-submission-form input[type=checkbox]').
        each(function(index, element) {
    
        // We need to send integers. If we send "true" or "false",
        // they go as strings and the PHP on the other end
        // does the wrong thing.
        ajax_params[element.name] = element.checked ? 1 : 0;
    });

    // Process the results as plain text, so we don't have
    // to worry about validating them as XML.
    jQuery.post(
        BikeFunAjax.ajaxurl,
        ajax_params,
        display_preview,
        'text'
        );    
}

// Return a list of venues, for use in autocomplete
//
// The complete list of venues is passed in by the
// calling page, because it's small. We don't actually
// do an AJAX request to get the venues.
function venue_names() {
    var venue_names = [];
    for (var key in BikeFunAjax.venues) {
        if (BikeFunAjax.venues.hasOwnProperty(key)) {
            venue_names.push(key);
        }
    }

    return venue_names;
}

// An event handler that gets called to (possibly) fill in
// the address based on the venue.
function autocomplete_address(event, ui) {
    var locname = jQuery('#event_locname').val();

    if (BikeFunAjax.venues.hasOwnProperty(locname)) {
        var address = BikeFunAjax.venues[locname];
        jQuery('#event_address').val(address);
    }
}

// Initialize event handlers
//
// Have to use jQuery(), not $(), because WordPress loads jQuery
// in "no-conflicts" mode, where $() isn't defined.
jQuery(document).ready(function() {
    // When the event date changes, look up the recurring dates
    jQuery('#event_dates').blur(function() {
        verifydates(this.value, false);
        return false;
    });

    // Update the preview on changes.
    jQuery('#event-submission-form input[type=text]').blur(update_preview);
    jQuery('#event-submission-form textarea').blur(update_preview);
    jQuery('#event-submission-form select').change(update_preview);
    jQuery('#event-submission-form input[type=radio]').change(update_preview);
    jQuery('#event-submission-form input[type=checkbox]').change(update_preview);

    // If an event date has been specified, run verifydates() now.
    var dates = jQuery('#event_dates').val();
    if (dates != "") {
        verifydates(dates, false);
    }

    // If a time has been specified, run tweakdurations() now.
    var time = jQuery('#event_eventtime').val();
    if (time != "") {
        tweakdurations();
    }

    jQuery('#event_eventtime').focus(tweaktime);
    jQuery('#event_eventtime').change(tweakdurations);

    // If we're showing the radio button for changing images,
    // select it when selecting a new file.
    var change_image_radio = jQuery('#submission_image_action_change');
    if (change_image_radio) {
        jQuery('#event_image').change(function() {
            change_image_radio.attr('checked', true);
        });
    }

    // If there's a delete button, confirm deletion.
    var delete_button = jQuery('#delete_button');
    if (delete_button) {
        delete_button.click(function(event) {
            message = "Are you sure you want to delete this event?";
            return confirm(message);
        });
    }

    // Autocomplete the location name from the known-venues list
    var locname_field = jQuery('#event_locname');
    locname_field.autocomplete({
            source: venue_names(),

            // Ideally, we'd use the select event, but Evan couldn't
            // get that to work. Close also works, but it fires when
            // the user selects or cancels, so don't assume it's
            // always a selection.
            //
            // Also, note that we're using the autocomplete widget
            // distributed as part of jquery-ui, and not the older
            // autocomplete plugin (which has a radically different
            // API).
            //
            // See:
            // http://jqueryui.com/demos/autocomplete/#event-change
            close: autocomplete_address,
        });
});


