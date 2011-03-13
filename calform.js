// include/calform.js
//
// This file is included in calform.php.  It provides a lot of the
// field-checking functions.  This file really isn't useful for anything
// else.  I only bothered to put them in a separate file to reduce the
// size of calform.php, and to make the error line numbers reported by
// the browser correspond to actual line numbers, unaffected by PHP
// processing.
//
// Generally, most fields call fix(this.name) when the field's value has
// been completely entered (i.e., for onChange/onClick events, and maybe
// for onBlur/OnFocus events).  Some also call fixdelayed(this.name) for
// partially-entered values (i.e., onMouseUp/onKeyUp events).  A few of
// the simpler fields may just call previewonline().


// The flash() function makes an input field's color be red for a fraction of
// a second, then revert to black.  This is used to warn users when the value
// they're entering has been modified conform to some limit.
var flashwhat = null;
var flashtimer;
function flash(what)
{
    if (flashwhat != null) {
	    clearTimeout(flashtimer);
	    if (what != flashwhat)
		    flashwhat.style.color = "red";
    }
    what.style.color = "red";
    flashwhat = what;
    flashtimer = setTimeout('flashwhat.style.color="black"; flashwhat=null;', 333);
}

// Return TRUE if we are editing the page
function editparameter() {
    var action = jQuery('#calform_action').val();
    return action == "update";
}

// Adjust the title value.
function fixtitle(partial)
{
    var title = document.forms.cal.title.value;

    // limit it to 50 chars
    if (title.length > 50)
	title = title.substr(0, 50);

    // if given in all uppercase, then convert to mixed case
    if (!partial && title.length > 4 && title.search(/[a-z]/) < 0) {
	var newtitle = title.charAt(0);
	for (i = 1; i < title.length; i++) {
	    before = title.charAt(i - 1);
	    if (before >= "A" && before <= "Z") {
		newtitle = newtitle + title.charAt(i).toLowerCase();
	    } else {
		newtitle = newtitle + title.charAt(i);
	    }
	}
	title = newtitle;
    }

    // if any changes were made, store it and flash it
    if (title != document.forms.cal.title.value)
    {
	document.forms.cal.title.value = title;
	flash(document.forms.cal.title);
    }
}


function isstopword(word)
{
    switch (word) {
	case "a": return true;
	case "an": return true;
	case "and": return true;
	case "at": return true;
	case "by": return true;
	case "of": return true;
	case "on": return true;
	case "the": return true;
	case "to": return true;
    }
    return false;
}

// Adjust the tinytitle value
function fixtinytitle(partial)
{
    var title = document.forms.cal.title.value;
    var field = document.forms.cal.tinytitle;
    var tinytitle = field.value;
    var newtiny;

    // If title is non-empty, and tinytitle is empty or appears to be derived
    // from title, then derive it again by taking words from title.
    if (title != "" && (title.length < tinytitle.length || title.substr(0, tinytitle.length) == tinytitle))
    {
	if (title.length <= 20)
	    field.value = title;
	else {
	    var words = title.split(" ");
	    var newtitle;

	    // try to avoid using a stopword in the first word of the tinytitle
	    while (words.length > 0 && isstopword(words[0])) {
		words.shift();
	    }
	    newtitle = words.length == 0 ? "" : words.shift();

	    // copy as many other words as possible, skipping stopwords
	    while (words.length > 0 && newtitle.length + 1 + words[0].length < 20) {
		if (isstopword(words[0]))
		    words.shift();
		else
		    newtitle = newtitle + " " + words.shift();
	    }
	    field.value = newtitle;
	}
    }

    // If tinytitle has grown past 20 chars, then clip it and flash the field.
    if (tinytitle.length > 20)
    {
	    tinytitle = tinytitle.substr(0, 20);
	    document.forms.cal.tinytitle.value = tinytitle;
	    flash(document.forms.cal.tinytitle);
    }
}


// Adjust the value of weburl
function fixweburl(partial)
{
    var field = document.forms.cal.weburl;
    if (!partial && field.value != "") {
	if (field.value.indexOf(":") < 0) {
	    field.value = "http://" + field.value + "/";
	}
    }
}



// Adjust the webname value
function fixwebname(partial)
{
    var webname;

    // If webname is empty, derive it from weburl
    if (!partial && document.forms.cal.webname.value == "") {
	webname = document.forms.cal.weburl.value;
	if (webname.substr(0, 7).toLowerCase() == "http://")
	    webname = webname.substr(7);
	if (webname.substr(-1) == "/")
	    webname = webname.substr(0, webname.length - 1);
	document.forms.cal.webname.value = webname;
    }
}


// Adjust the formatting of the phone field.
function fixphone()
{
    var field = document.forms.cal.phone;
    var digits = "";

    // Extract the digits from the phone number as entered by the user
    for (i = 0; i < field.value.length; i++) {
	ch = field.value.substr(i,1);
	if (ch >= "0" && ch <= "9") {
	    digits = digits.concat(ch);
	}
    }

    // If no area code was given, use the default one
    if (digits.length == 7) {
	digits = areacode() + digits;
    }

    // The phone number with area code should be 10 digits long.  If so,
    // then store it.  Otherwise, leave the phone number as it was entered.
    if (digits.length == 10) {
	field.value = digits.substr(0,3) + "-" + digits.substr(3,3) +
		    "-" + digits.substr(6,4);
    }
}



// This is set to null, or one of the textarea fields
var whoHasFocus = null;

// Fix the description.
function fixdescr(partial)
{
    var field = document.forms.cal.descr;
    var newvalue = field.value;

    // Don't allow tab characters.
    newvalue = newvalue.replace(/\t/g," ");

    // Don't allow more than two newlines in a row (one blank line)
    if (!partial)
	newvalue = newvalue.replace(/\r?\n\r?\n(\r?\n)+/g,"\n\n");

    // Strip trailing whitespace, unless we are (or may be) still editing it
    if (!partial)
	newvalue = newvalue.replace(/[ \r\n]+$/, "");

    // Limit the length to 600 characters.
    if (newvalue.length >= 600)
	newvalue = newvalue.substr(0, 599);
    if (newvalue != field.value) {
	// find out where the change occured so we can move the cursor there
	for (i = 0; i < newvalue.length && i < field.value.length && newvalue.charAt(i) == field.value.charAt(i); i++) {
	}
	field.value = newvalue;
	if (whoHasFocus == field) {
	    try {
		SetCursorPos(window, field, i);
	    } catch (err) {
		// ignore
	    }
	    flash(field);
	}
    }
}


// Adjust the value of the printdescr field.
function fixprintdescr(partial)
{
    var descr = document.forms.cal.descr.value;
    var field = document.forms.cal.printdescr;
    var newvalue = field.value;

    // The default value is derived from descr
    if (whoHasFocus != field &&
	(newvalue.length > descr.length ||
	descr.replace(/[ \r\n\t][ \r\n\t]+/g," ").substr(0, newvalue.length) == newvalue))
    {
	// use as many whole sentences as will fit in 120 characters.
	newvalue = descr;
	while (newvalue.length > 120)
	{
	    period = newvalue.lastIndexOf(". ");
	    exclamation = newvalue.lastIndexOf("! ");
	    if (exclamation > period)
		period = exclamation;
	    exclamation = newvalue.lastIndexOf("? ");
	    if (exclamation > period)
		period = exclamation;
	    if (period >= 0)
		newvalue = newvalue.substr(0, period + 1);
	    else
		break;
	}
    }

    // Don't allow tabs, newlines, multiple spaces, or leading spaces.  Also
    // don't allow trailing spaces unless we are (or may be) editing the value
    newvalue = newvalue.replace(/[\t\n\r]/g," ");
    newvalue = newvalue.replace(/   */g, " ");
    newvalue = newvalue.replace(/^ /, "");
    if (!partial)
	newvalue = newvalue.replace(/ $/, "");

    // Limit the length to 120 characters.
    if (newvalue.length > 120)
	newvalue = newvalue.substr(0, 120);
    if (newvalue != field.value) {
	// find out where the change occured so we can move the cursor there
	for (i = 0; i < newvalue.length && i < field.value.length && newvalue.charAt(i) == field.value.charAt(i); i++) {
	}
	field.value = newvalue;
	if (whoHasFocus == field) {
	    SetCursorPos(window, field, i);
	    flash(field);
	}
    }
}

function fixlocdetails(partial)
{
    if (partial)
	return;

    // strip off leading and trailing spaces and parentheses
    var value = document.forms.cal.locdetails.value;
    value = value.replace(/^[ (]*/,"");
    if (!partial)
	value = value.replace(/[ )]*$/,"");
    if (value != document.forms.cal.locdetails.value)
	document.forms.cal.locdetails.value = value;
}

// This is used by verifyvenue() to show the status of verification
//    ""	No status; not checked yet.
//    venue...  Checking the locname, which may supply an address
function showverify(result)
{
    var textspan = document.getElementById("verifiedstatus");
    var text = "";
    switch (result) {
	case "":
	    text = "";
	    break;
	case "venue...":
	    text = "<span style=\"text-decoration: blink;\">Looking up venue...</span>";
	    break;
    }
    textspan.innerHTML = text;
}

// This tries to lookup a locname value.  If successful, it will fill in the
// locname field (with the official version of the venue name), the address,
// the addressverified hidden input, and the area field.
var oldvenue = "foo";
function verifyvenue(value)
{
    // Guard against superfluous calls
    if (value == oldvenue)
	    return;
    oldvenue = value;

    if (value == "") {
	showverify("");
    } else {
	// send a request to bycycle to parse this
	var conn = XmlHttp.create();
	var url = "vfyvenue.php?locname=" + encodeURIComponent(value);
	conn.open("GET", url, true);
	conn.onreadystatechange = function() {
	    // if we've received the whole text...
	    if (conn.readyState == 4) {
		dom = XmlDocument.create();
		dom.loadXML(conn.responseText);

		// was it recognized?
		results = dom.getElementsByTagName("result");
		if (results.length > 0) {
		    switch (results[0].firstChild.nodeValue) {
			case "Verified":
			    // Set both the venue name and the address
			    results = dom.getElementsByTagName("locname");
			    oldvenue = document.forms.cal.locname.value = results[0].firstChild.nodeValue;
			    results = dom.getElementsByTagName("address");
			    oldaddress = document.forms.cal.address.value = results[0].firstChild.nodeValue;
			    break;

			case "Known":
			    // Set the address but not the venue name.  This
			    // is because the venue name hasn't been checked
			    // by the calendar crew yet so it could be bad,
			    // and it's hard for the user to correct a venue
			    // name that came down from the server.
			    results = dom.getElementsByTagName("address");
			    oldaddress = document.forms.cal.address.value = results[0].firstChild.nodeValue;
			    break;

			case "Unknown":
			    showverify("");
			    // Leave it as-is.
			    break;
		    }
		} else {
		    showverify("");
		}
		debug("]");
	    }
	}
	showverify("venue...");
	debug(" [verifyvenue");
	conn.send(null);
    }
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
        if (editparameter() != "") {
            message_text += " and newsflash";
        }
        message_text += " of each date.\n";
        var message = jQuery("<p>" + message_text + "</p>");

        // The table of dates & statuses (and possibly newsflashes)
        var table = jQuery("<table class=datelist></table>");
        var header_row = jQuery("<tr></tr>");
        header_row.append(jQuery("<th class=dates>Date</th>"));
        header_row.append(jQuery("<th class=dates>Status</th>"));
        if (editparameter() != "") {
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

                status_select.change(previewonline);
                
                statuscell.append(status_select);
            }
            row.append(statuscell);

            if (editparameter() != "") {
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
                newsflash_input.blur(previewonline);

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


// This function is called with "" as a parameter if the form is currently in
// a state that can be submitted -- all required fields filled in.  Otherwise
// the parameter should decribe what's missing.  This function shows the
// parameter text next to the [Submit] button, and enables/disables that
// button as appropriate.
function submittable(whynot)
{
    document.getElementById("hint").innerHTML = whynot;
    if (whynot == "")
	    document.getElementById("submit").disabled = false;
    else
	    document.getElementById("submit").disabled = true;
}

// Inspect the data that's currently in the form, and determine whether
// any critical information is missing.  This function should be called
// after any potentially important piece of information is changed.
function chksubmittable(partial)
{
    var cal = document.forms.cal;
    var emailparts = cal.email.value.split("@");
    if (cal.title.value == "") {
	submittable("You must supply a title.");
    } else if (cal.descr.value == "") {
	submittable("You must supply a description.");
    } else if (cal.address.value == "") {
	submittable("You must supply an address.");
    } else if (cal.dates.value == "") {
	submittable("You must choose a date.");
    } else if (cal.eventtime.value == "") {
	submittable("You must choose a time.");
    } else if (cal.name.value == "") {
	submittable("You must supply a name.  Even a fake name will do.");
    } else if (cal.email.value == "") {
	submittable("You must supply an email address.");
    } else if (emailparts.length != 2 || emailparts[1].indexOf(".") < 0) {
	if (!partial)
	    submittable("The email address you supplied is malformed.");
    } else {
	submittable("");
    }

    return !document.getElementById("submit").disabled;
}

// This is used by the tweakprintwho() function to add a single <option> to
// the printwho <select> list.
function tpwhelper(value, name, text, text2)
{
    var option = document.createElement("option");
    option.value = value;
    if (text2 == null)
    {
	    if (text == "")
		    return;
	    option.text = name + ", " + text;
    }
    else
    {
	    if (text == "" || text2 == "")
		    return;
	    option.text = name + ", " + text + ", " + text2;
    }

    try {
	    // Standards-compliant version
	    document.forms.cal.printwho.add(option, null);
    } catch(ex) {
	    // Microsoft's non-standard version
	    document.forms.cal.printwho.add(option);
    }
}

/* return the longest value in options */
function tpwlongest(options)
{
    var longest = "";
    var i;
    for (i = 0; i < options.length; i++) {
	    if (options[i].value.length > longest.length)
		    longest = options[i].value;
    }

    return longest;
}

// When any of the contact information changes, this function is called to
// update the list of possible PRINTED contact information strings.
function tweakprintwho(first)
{
    var cal = document.forms.cal;
    var name = cal.name.value;
    var sel = cal.printwho;
    var current = sel.options[sel.selectedIndex].value;
    var oldlongest = tpwlongest(sel.options);
    var newlongest;

    // wipe out all but the first option
    while (sel.options.length > 1)
	    sel.remove(sel.options.length - 1);

    // first option is always just name, unless no name has been entered
    // yet in which case we use "Name"
    if (name == "")
	    name = "Name";
    sel.options[0].text = name;

    // other options are all combinations of one or two non-empty fields
    tpwhelper("NP", name, cal.phone.value);
    tpwhelper("NE", name, cal.email.value);
    tpwhelper("NW", name, cal.weburl.value);
    tpwhelper("NC", name, cal.contact.value);
    tpwhelper("NPE", name, cal.phone.value, cal.email.value);
    tpwhelper("NPW", name, cal.phone.value, cal.weburl.value);
    tpwhelper("NPC", name, cal.phone.value, cal.contact.value);
    tpwhelper("NEW", name, cal.email.value, cal.weburl.value);
    tpwhelper("NEC", name, cal.email.value, cal.contact.value);
    tpwhelper("NWC", name, cal.weburl.value, cal.contact.value);

    /* if more info has been added since last check, then use the new
     * longest info in the default.  Exception: during initialization,
     * we keep the old value (if any).
     */
    newlongest = tpwlongest(sel.options);
    if (first)
	    current = printsel();
    else if (newlongest.length > oldlongest.length)
	    current = newlongest;

    // if the previously selected item is still valid, continue using it
    for (i = 0; i < sel.options.length; i++)
    {
	    if (sel.options[i].value == current)
	    {
		    sel.selectedIndex = i;
		    return;
	    }
    }
} 


// This is used to debug the pages.  It appends text to a <span> that is
// normally invisible; after 10 seconds, it reverts in invisibility again
var debugtimer = null;
function debug(text)
{
    // if debugging isn't enabled, do nothing
    if (!debugenabled())
	return;

    // if we already had a debugtimer going, clobber it
    if (debugtimer != null) {
	clearTimeout(debugtimer);
	debugtimer = null;
    }

    // append the text to the debug span
    document.getElementById("debug").innerHTML += text;

    // arrange for the debug span to be cleared in 10 seconds
    debugtimer = setTimeout("document.getElementById('debug').innerHTML = ''", 10000);
}


// This is used to keep track of a timer for delayed fixes.
var fixtimer = null;

// This is the master fix function.  It adjusts all inputs, including the
// status of the [submit] button.  The "partial" parameter is true if a
// field's value might not have been fully entered yet.
function fixeither(name, partial)
{
    debug(" (" + name + ", " + (partial?"true":"false"));

    // If there's a timer, stop it.
    if (fixtimer != null) {
	clearTimeout(fixtimer);
	fixtimer = null
    }

    // Fix all fields.  The order is significant -- fields that can be derived
    // from other fields should be fixed last.
    fixtitle(partial);
    fixtinytitle(partial);
    fixdescr(partial);
    fixprintdescr(partial);
    fixlocdetails(partial);
    fixweburl(partial);
    fixwebname(partial);
    fixphone();

    // Some fields affect the layout of other fields
    tweakdurations();
    tweakprintwho(false);

    // Allow some fields to verify themselves by sending requests.
    if (!partial) {
	verifyvenue(document.forms.cal.locname.value);
	verifydates(document.forms.cal.dates.value, false);
    }

    // Update the preview
    if (!partial) {
	previewonline();
    }

    // Check whether the form is now submittable.
    chksubmittable(partial);

    debug(")");
}

function fix(name)
{
    fixeither(name, false);
}

// Arrange for fix(true) to be called later.
function fixdelayed(name)
{
    // If a timer was already in progress, cancel it
    if (fixtimer != null)
	clearTimeout(fixtimer);

    // Start a new timer
    fixtimer = setTimeout("fixeither(\""+name+"\", true);", 2000);
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
//
// @@@ Change the name. (The name came from the old code,
// and it doesn't make sense to Evan.)
function previewonline() {

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
    jQuery('#event-submission-form input[type=text]').blur(previewonline);
    jQuery('#event-submission-form textarea').blur(previewonline);
    jQuery('#event-submission-form select').change(previewonline);
    jQuery('#event-submission-form input[type=radio]').change(previewonline);
    jQuery('#event-submission-form input[type=checkbox]').change(previewonline);

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
});


