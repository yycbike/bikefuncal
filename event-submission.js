// JavaScript that goes along with event-submission-form.php
//
// It handles the AJAX requests used to update the form & show
// previews. It should also do form validation, someday.
//
// In the new code, we avoid putting JavaScript in the PHP or HTML
// files. It all lives here. Instead of attaching code via the
// onclick="..." HTML syntax, we attach it using jQuery and
// event handlers. 

(function($) {
    // Return TRUE if we are editing an existing event
    function is_existing_event() {
        var action = $('#submission_action').val();
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

        if (value == "Added" || value == "Skipped" 
            || value == "Deleted" || value == "Exception") {
            fields[0].style.display = "none";
        }
        else {
            fields[0].style.display = "inline";
        }
    }

    function add_remove_tip_row(value, controls_row) 
    {
        var hint = null;
        switch (value) {
        case 'Skipped':
            hint = 'The event won\'t be listed on this date.';
            break;
        case 'Canceled':
            hint = 'Use the newsflash to explain why the event is canceled.';
            break;
        case 'Exception':
            // \u2014 = &mdash;
            var hint1 = 'Use an exception if this date is not like the others \u2014 ' +
                'if it has a different start time, location, etc. '

            // Default hint, for when there's not a link to the exception.
            var hint2 = 'To edit the exception, first update this event. Then you\'ll be ' +
                'given a link where you can edit the exception.';
            controls_row.find('[data-is-exception]').each(function() {
                // If the controls row has a link to the exception, reference it in the hint
                hint2 = 'Edit the exception with the link above.';
            });

            hint = hint1 + hint2;
            break;
        default:
            hint = null;
            break;
        }

        if (hint == null) {
            // If the following row is a hint row, remove it.
            var next_row = controls_row.next();
            if (next_row.hasClass('dates-table-tip')) {
                next_row.remove();
            }
        }
        else {
            var next_row = controls_row.next();
            var tip_row;
            if (next_row.hasClass('dates-table-tip')) {
                tip_row = next_row;
            }
            else {
                tip_row = $('<tr class=dates-table-tip></tr>');
                controls_row.after(tip_row);
            }

            var td = $('<td colspan=3></td>');
            td.text(hint);

            tip_row.empty();
            tip_row.append(td);
        }
    }

    // Generate an option for a daily status field
    function vfydatesoption(select, option_value, current_value)
    {
        var titles = {
            'Added': 'Occurs normally',
            'Skipped': 'No listing for this date',
            'As Scheduled': 'Occurs normally',
            'Canceled': 'Marked as canceled on calendar',
            'Deleted': 'Removed from calendar',
            'Exception': 'Occurs at different time, location, etc.'
        };

        var option = $('<option></option>');
        option.val(option_value);
        option.text(option_value);
        option.attr('title', titles[option_value]);
        select.append(option);

        // Mark this option as selected in the SELECT
        // element, if it should be.
        if (option_value == current_value) {
            select.val(option_value);
        }
    }

    function show_date_error(message)
    {
        $('#submission_dates_spinner').hide();

        var datelist = $('#datelist');
        datelist.empty();
        datelist.append( $('<em>').text(message) );
        datelist.show();
    }

    function display_dates(xmlDom)
    {
        $('#submission_dates_spinner').hide();

        var mydatelist = document.getElementById("datelist");

        tmp = xmlDom.getElementsByTagName("error");
        if (tmp.length != 0) {
            var error_message = tmp[0].firstChild.nodeValue;
            show_date_error(error_message);

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
            // The table of dates & statuses (and possibly newsflashes)
            var table = $("<table class=datelist></table>");
            var header_row = $("<tr></tr>");
            header_row.append($("<th class=dates>Date</th>"));
            header_row.append($("<th class=dates>Status</th>"));
            if (is_existing_event()) {
                header_row.append($("<th class=dates>Newsflash</th>"));
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

                var row = $("<tr></tr>");

                if (change == "Y") {
                    row.addClass("changeddate");
                }
                else {
                    row.addClass("unchangeddate");
                }

                var datecell = $("<td>" + hrdate + "</td>");
                row.append(datecell);

                var statuscell = $("<td></td>");
                if (status == "Exception") {
                    var input = $("<input />");
                    input.attr({
                        type: 'hidden',
                        name: 'event_status' + suffix,
                        value: 'Exception',
                    });
                    statuscell.append(input);
                    
                    // Exceptions don't have a newsflash. Make this cell fill the next column.
                    statuscell.attr('colspan', 2);

                    statuscell.text('Exception ');
                    var editlink = $("<a></a>");
                    editlink.addClass('edit-exception');
                    editlink.text('(Edit)');
                    editlink.attr('href', exception_url);
                    editlink.attr('data-is-exception', true);
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
                    var status_select = $("<select></select>");
                    status_select.attr({
                        'name' : 'event_status' + suffix,
                    });

                    // We have to go through gyrations to make a copy of the
                    // suffix variable. Otherwise all the status selectors
                    // will have the same value of suffix.
                    // See http://stackoverflow.com/q/1779847
                    var on_change = (function(suffix_copy, row_copy) {
                        return function() {
                            vfydatesnewsflash(this.value, suffix_copy);
                            add_remove_tip_row(this.value, row_copy);
                        };
                    }(suffix, row));
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
                    var newsflash_cell = $("<td></td>");

                    var newsflash_input = $("<input></input>");
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

                // Append a tip row after this row, if needed
                add_remove_tip_row(status, row);
            }
            $(mydatelist).empty();
            var table_container = $('<div id=datelist-container class=new-event-controls></div>');
            table_container.append(table);
            $(mydatelist).
                append('<h3 class=event-submission-label>Statuses</h3>').
                append(table_container);
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
        var value;
        var is_once = $('#submission_event_occurs_once_festival').attr('checked') ||
            $('#submission_event_occurs_once_other').attr('checked');
        if (is_once) {
            value = $('#submission_dates_once').val();
        }
        else {
            value = $('#submission_dates_multiple').val();
        }

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

        if ($('#submission_event_id')) {
            id = $('#submission_event_id').val();
            ajax_params['id'] = id;
        }

        if (reload) {
            ajax_params['reload'] = 'y';
        }

        jQuery.ajax({
            'type': 'POST',
            'url': BikeFunAjax.ajaxurl,
            'dataType': 'xml',
            'data': ajax_params,

            'success': display_dates,
            'error': function() { show_date_error("The dates you entered weren't understood."); }

        });

        $('#submission_dates_spinner').show();
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
        var durations = $('#event_eventduration');
        durations.find('option').each(function(idx, domElement) {
            var option = $(domElement);
            option.attr('data-duration-text', option.text());
        });
    }

    // Update the list of durations to reflect the chosen eventtime.
    function tweakdurations() {
        var durations = $('#event_eventduration');
        var times     = $('#event_eventtime');
        var startTime = times.val();

        durations.find('option').each(function(idx, domElement) {
            var option = $(domElement);
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

        // Set the widths of the two controls to be identical, 
        // to make them look nicer.
        durations.width(times.width());
    }

    function display_preview(preview_content)
    {
        $('#submission_preview_spinner').hide();

        var preview = document.getElementById('preview-container');

        preview.innerHTML = preview_content;

        // disable any links in the preview
        $('a', preview).each(function(index, element) {
            element.href = '#';
        });

        descramble_emails();
    }

    var preview_timer = null;
    function set_preview_timer() {
        if (preview_timer !== null) {
            window.clearTimeout(preview_timer);
        }

        preview_timer = window.setTimeout(update_preview, 750);
        $('#submission_preview_spinner').show();
    }

    // Update the preview
    function update_preview() {
        set_hidden_dates_field();

        // Build up a list of arguments to send
        var ajax_params = {
              'action': 'preview-event-submission',
        };

        $('form#event-submission-form input[type=text]').
            each(function(index, element) {

            ajax_params[element.name] = element.value;
        });

        $('form#event-submission-form input[type=email]').
            each(function(index, element) {

            ajax_params[element.name] = element.value;
        });

        $('form#event-submission-form input[type=url]').
            each(function(index, element) {

            ajax_params[element.name] = element.value;
        });

        $('form#event-submission-form input[type=hidden]').
            each(function(index, element) {

            ajax_params[element.name] = element.value;
        });

        $('form#event-submission-form textarea').
            each(function(index, element) {

            ajax_params[element.name] = element.value;
        });

        $('form#event-submission-form select').
            each(function(index, element) {

            var value = element.options[element.selectedIndex].value;

            ajax_params[element.name] = value;
        });

        $('form#event-submission-form input[type=radio]').
            each(function(index, element) {

            // One of the elements in the radio group will be checked,
            // so we'll always report a value.
            if (element.checked) {
                ajax_params[element.name] = element.value;
            }
        });

        $('form#event-submission-form input[type=checkbox]').
            each(function(index, element) {

            // We need to send integers. If we send "true" or "false",
            // they go as strings and the PHP on the other end
            // does the wrong thing.
            ajax_params[element.name] = element.checked ? 1 : 0;
        });

        // Process the results as plain text, so we don't have
        // to worry about validating them as XML.
        $.post(
            BikeFunAjax.ajaxurl,
            ajax_params,
            display_preview,
            'text'
            );    
    }

    function animate_once_multiple(old_div, new_div) {
        var speed = 400;
        var once_multiple_container = $('#once-multiple-container');

        var old_height = old_div.height();
        var new_height = new_div.height();

        if (old_height > new_height) {
            // Lock in the larger size for now, animate the shrinking later.
            once_multiple_container.height( once_multiple_container.height() );
        }
        else {
            // Before showing the larger size, animate the growth of the
            // space.
            once_multiple_container.animate(
                {height: new_height}, 
                speed,
                function () {
                    // Change height back to auto, so the div can grow & shrink
                    once_multiple_container.height('auto');
                });
        }

        old_div.fadeOut(speed, function() {
            new_div.fadeIn(speed, function() {

                if (old_height > new_height) {
                    // Animate to the height of the new div
                    once_multiple_container.animate(
                        {height: new_height},
                        speed,
                        function () {

                            // Change height back to auto, so the div can grow & shrink
                            once_multiple_container.height('auto');
                        });
                }
            });
        });
    }

    function toggle_occurs_once_multiple() {
        var occurs_multiple = $('#occurs-multiple');
        var occurs_once = $('#occurs-once');

        var is_once = $('#submission_event_occurs_once_festival').attr('checked') ||
            $('#submission_event_occurs_once_other').attr('checked');

        if (is_once) {
            animate_once_multiple(occurs_multiple, occurs_once);

            $('#submission_dates_once').attr('required', true);
            $('#submission_dates_multiple').attr('required', false);
        }
        else {
            animate_once_multiple(occurs_once, occurs_multiple);

            $('#submission_dates_once').attr('required', false);
            $('#submission_dates_multiple').attr('required', true);
        }
    }

    function toggle_event_during_festival() {
        var radDuringFestival = $('#submission_event_occurs_once_festival');
        if (radDuringFestival.attr('checked')) {
            // Show one or two months, depending on whether the start & end dates have
            // the same month.
            var isSingleMonth = (BikeFunAjax.festivalStartMonth == BikeFunAjax.festivalEndMonth);
            var numberOfMonths = isSingleMonth ? 1 : 2;

            // Limit the calendar to dates during the festival
            $('#submission_dates_once').
                datepicker('option', 'minDate', BikeFunAjax.festivalStartDate).
                datepicker('option', 'maxDate', BikeFunAjax.festivalEndDate). 
                datepicker('option', 'numberOfMonths', numberOfMonths);

        }
        else {
            // Limit the calendar to today - +364 days
            $('#submission_dates_once').
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

    function set_hidden_dates_field() {
        var dates_val;

        var is_once = $('#submission_event_occurs_once_festival').attr('checked') ||
            $('#submission_event_occurs_once_other').attr('checked');

        if (is_once) {
            dates_val = $('#submission_dates_once').val();
        }
        else {
            dates_val = $('#submission_dates_multiple').val();
        }

        $('#event_dates').val(dates_val);
    }

    // Initialize event handlers
    $(document).ready(function() {
        // When the multiple date text input changes, look up the recurring dates
        $('#submission_dates_multiple').blur(function() {
            verifydates(false);
            return false;
        });
        $('#submission_dates_show').click(function() {
            verifydates(false);
            return false;
        });
        $('#submission_dates_once').change(function() {
            verifydates(false);
            return false;
        });

        // Update list of multiple dates. No-op if single date, or dates
        // not specified.
        verifydates(false);

        // Adjust durations to include end times (if start time is specified)
        save_durations();
        tweakdurations();

        $('#event_eventtime').focus(tweaktime);
        $('#event_eventtime').change(tweakdurations);

        // If we're showing the radio button for changing images,
        // select it when selecting a new file.
        var change_image_radio = $('#submission_image_action_change');
        if (change_image_radio) {
            $('#event_image').change(function() {
                change_image_radio.attr('checked', true);
            });
        }

        // If there's a delete button, confirm deletion.
        var delete_button = $('#delete_button');
        if (delete_button) {
            delete_button.click(function(event) {
                message = "Are you sure you want to delete this event?";
                return confirm(message);
            });
        }

        // Configure calendar for "occurs once"
        $('#submission_dates_once').datepicker({
            'showOn': 'both',
            'buttonText': 'Choose',

            'dateFormat': 'DD, MM d', // e.g. Monday, October 8

            'hideIfNoPrevNext': true,

            'minDate': '0D', // today
            'maxDate': '364D', // +1 year

            'onClose': set_preview_timer
        });

        // Toggle the visibility of once/multiple, and set the
        // bounds for the date picker.
        var on_change_event_occurs = function() {
            toggle_occurs_once_multiple();
            toggle_event_during_festival();
            verifydates(false);
        }
        $('#submission_event_occurs_once_festival').change(on_change_event_occurs);
        $('#submission_event_occurs_once_other').change(on_change_event_occurs);
        $('#submission_event_occurs_multiple').change(toggle_occurs_once_multiple);
        on_change_event_occurs();

        // Code to run on submit
        $('#event-submission-form').submit(set_hidden_dates_field);

        // Show/hide tips for event description
        make_toggleable_tips($('#event_descr_more'), $('#event_descr_show_more'));
        make_toggleable_tips($('#dates_multiple_more'), $('#dates_multiple_show_more'));

        // Update the preview on changes. Since the .change() event doesn't fire until blur,
        // also set the timer on keydown().
        $('#event-submission-form input[type=text]').
            change(set_preview_timer).keydown(set_preview_timer);
        $('#event-submission-form input[type=email]').
            change(set_preview_timer).keydown(set_preview_timer);
        $('#event-submission-form input[type=url]').
            change(set_preview_timer).keydown(set_preview_timer);
        $('#event-submission-form textarea').
            change(set_preview_timer).keydown(set_preview_timer);
        $('#event-submission-form select').change(set_preview_timer);
        $('#event-submission-form input[type=radio]').change(set_preview_timer);
        $('#event-submission-form input[type=checkbox]').change(set_preview_timer);

        // Update the preview now. Even if the event is new and there's not content,
        // we want to draw the preview box and make it look nicer.
        update_preview();
    });

})(jQuery);

