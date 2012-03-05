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
    jQuery('#submission_dates_spinner').hide();

    var mydatelist = document.getElementById("datelist");
    
    tmp = xmlDom.getElementsByTagName("error");
    if (tmp.length != 0) {
        var error_message = tmp[0].firstChild.nodeValue;
        jQuery(mydatelist).empty();
        jQuery(mydatelist).append(
            jQuery('<em>').text(error_message)
        );
        mydatelist.style.display = "block";

        return;
    }
    else {
        // if a canonical form of "dates" is returned, use it
        tmp = xmlDom.getElementsByTagName("canonical");
        if (tmp.length != 0) {
            //!!!
            olddates =  tmp[0].firstChild.nodeValue
                ? tmp[0].firstChild.nodeValue
                : "";

            datesfield = document.getElementById('submission_dates_multiple');
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
    if (dates.length == 1 &&
             dates[0].getElementsByTagName("status")[0].firstChild.nodeValue == "Added") {
        suffix = dates[0].getElementsByTagName("suffix")[0].firstChild.nodeValue;
        mydatelist.innerHTML = "<input type=hidden name=event_status" + suffix + " value=Added>";
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
            var exception_url = dates[i].getElementsByTagName("exception-url")[0].firstChild
                ? dates[i].getElementsByTagName("exception-url")[0].firstChild.nodeValue
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
                    name: 'event_status' + suffix,
                    value: 'Exception',
                });
                statuscell.append(input);

                var editlink = jQuery("<a></a>");
                editlink.text('Exception');
                editlink.attr('href', exception_url);
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

            if (is_existing_event() && status != "Exception") {
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
function verifydates(reload)
{
    var value = jQuery('#submission_dates_multiple').val();
    
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

    if (jQuery('#submission_event_id')) {
        id = jQuery('#submission_event_id').val();
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

    jQuery('#submission_dates_spinner').show();
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

// In the duration/end-time drop-down, save the textual
// descriptions of the durations, so we can use them later.
function save_durations() {
    var durations = jQuery('#event_eventduration');
    durations.find('option').each(function(idx, domElement) {
        var option = jQuery(domElement);
        option.attr('data-duration-text', option.text());
    });
}

// Update the list of durations to reflect the chosen eventtime.
function tweakdurations() {
    var durations = jQuery('#event_eventduration');
    var startTime = jQuery('#event_eventtime').val();
    durations.find('option').each(function(idx, domElement) {
        var option = jQuery(domElement);
        var text;
        
        // Don't adjust the text for "unspecified" (val = 0)
        if (option.val() != 0) {
            if (startTime === "") {
                // No start time, just show durations
                text = option.attr('data-duration-text');
            }
            else {
                // Show end time + duration
                when = twelvehour(startTime, option.val());
                text = when + " [" + option.attr('data-duration-text') + "]";
            }

            option.text(text);
        }
    });
}

function display_preview(preview_content)
{
    var preview = document.getElementById('preview');

    preview.innerHTML = preview_content;

    // disable any links in the preview
    jQuery('a', preview).each(function(index, element) {
        element.href = '#';
    });

    descramble_emails();
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

function toggle_occurs_once_multiple() {
    if (jQuery('#submission_event_occurs_once').attr('checked')) {
        jQuery('#occurs-multiple').hide();
        jQuery('#occurs-once').show();

        jQuery('#submission_dates_once').attr('required', true);
        jQuery('#submission_dates_multiple').attr('required', false);
    }
    else {
        jQuery('#occurs-once').hide();
        jQuery('#occurs-multiple').show();

        jQuery('#submission_dates_once').attr('required', false);
        jQuery('#submission_dates_multiple').attr('required', true);
    }
}

function toggle_event_during_festival() {
    var chkDuringFestival = jQuery('#submission_event_during_festival');
    if (chkDuringFestival.attr('checked')) {
        // Show one or two months, depending on whether the start & end dates have
        // the same month.
        var isSingleMonth = (BikeFunAjax.festivalStartMonth == BikeFunAjax.festivalEndMonth);
        var numberOfMonths = isSingleMonth ? 1 : 2;

        // Limit the calendar to dates during the festival
        jQuery('#submission_dates_once').
            datepicker('option', 'minDate', BikeFunAjax.festivalStartDate).
            datepicker('option', 'maxDate', BikeFunAjax.festivalEndDate). 
            datepicker('option', 'numberOfMonths', numberOfMonths);

    }
    else {
        // Limit the calendar to today - +364 days
        jQuery('#submission_dates_once').
            datepicker('option', 'minDate', '0D').
            datepicker('option', 'maxDate', '364D'). // +1 year
            datepicker('option', 'numberOfMonths', 1);
    }
}

function make_toggleable_tips(text, button) {
    button.click(function () {
        text.slideToggle('fast', function() {
            if (text.css('display') === 'none') {
                button.text('More tips');
            }
            else {
                button.text('Hide tips');
            }
        });
    });
}


// Initialize event handlers
//
// Have to use jQuery(), not $(), because WordPress loads jQuery
// in "no-conflicts" mode, where $() isn't defined.
jQuery(document).ready(function() {
    // When the multiple date text input changes, look up the recurring dates
    jQuery('#submission_dates_multiple').blur(function() {
        verifydates(false);
        return false;
    });
    jQuery('#submission_dates_show').blur(function() {
        verifydates(false);
        return false;
    });

    // Update the preview on changes.
    jQuery('#event-submission-form input[type=text]').blur(update_preview);
    jQuery('#event-submission-form textarea').blur(update_preview);
    jQuery('#event-submission-form select').change(update_preview);
    jQuery('#event-submission-form input[type=radio]').change(update_preview);
    jQuery('#event-submission-form input[type=checkbox]').change(update_preview);

    // Update the preview now. But only if this is an existing event,
    // otherwise there's nothing to show.
    if (is_existing_event()) {
        update_preview();
    }

    // Update list of multiple dates. No-op if single date, or dates
    // not specified.
    verifydates(false);

    // If a time has been specified, run tweakdurations() now.
    save_durations();
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

    // Configure calendar for "occurs once"
    jQuery('#submission_dates_once').datepicker({
        'showOn': 'both',
        'buttonText': 'Choose',

        'dateFormat': 'DD, MM d', // e.g. Monday, October 8

        'hideIfNoPrevNext': true,

        'minDate': '0D', // today
        'maxDate': '364D', // +1 year
    });


    // Toggle the visibility of once/multiple
    jQuery('#submission_event_occurs_once').change(toggle_occurs_once_multiple);
    jQuery('#submission_event_occurs_multiple').change(toggle_occurs_once_multiple);
    toggle_occurs_once_multiple();

    jQuery('#submission_event_during_festival').change(toggle_event_during_festival);
    toggle_event_during_festival();

    // Code to run on submit
    jQuery('#event-submission-form').submit(function() {
        var dates_val;

        if (jQuery('#submission_event_occurs_once').attr('checked')) {
            dates_val = jQuery('#submission_dates_once').val();
        }
        else {
            dates_val = jQuery('#submission_dates_multiple').val();
        }

        jQuery('#event_dates').val(dates_val);
    });

    // Show/hide tips for event description
    make_toggleable_tips(jQuery('#event_descr_more'), jQuery('#event_descr_show_more'));
    make_toggleable_tips(jQuery('#dates_multiple_more'), jQuery('#dates_multiple_show_more'));
});


