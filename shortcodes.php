<?php
namespace bike_fun_cal;

#
# Functions for shortcodes.
#
# Reference: http://codex.wordpress.org/Shortcode_API

# Return the dates a calendar covers
function get_cal_dates($atts) {
    if ($atts['for'] == 'palooza') {
        # Start and End can be specified to show archived Paloozas
        $start = isset($atts['start']) ? $atts['start'] : get_option('bfc_festival_start_date');
        $end   = isset($atts['end'])   ? $atts['end']   : get_option('bfc_festival_end_date');

        $startdate = strtotime($start);
        $enddate   = strtotime($end);
    }
    else if ($atts['for'] == 'current') {
        # Choose the starting date.  This is always the Sunday at or before
        # today.  We'll move forward from there.
        $now = getdate();
        $noon = $now[0] + (12 - $now["hours"]) * 3600; # approximately noon today
        $startdate = $noon - 86400 * $now["wday"];

        $numweeks = 3;
        $enddate = $startdate + ($numweeks * 7 - 1) * 86400;
    }
    else if ($atts['for'] == 'month') {
        global $wp_query;

        $now = getdate();
        
        $year = isset($wp_query->query_vars['calyear']) ?
            $wp_query->query_vars['calyear'] :
            $now["year"];

        $month = isset($wp_query->query_vars['calmonth']) ?
            $wp_query->query_vars['calmonth'] :
            $now["mon"];

        $year = intval($year);
        $month = intval($month);

        $startdate = mktime(0, 0, 0, #h:m:s
                            $month,
                            1,
                            $year);
        # End the calendar at the end of this month.
        # The 0th next month is the last day of this month.
        # PHP also does the right thing at the end of year.
        $enddate   = mktime(0,0,0, # h:m:s
                            $month + 1,  
                            0,
                            $year);
    }
    else {
        die("Bad value of for: " . $atts['for']);
    }

    return array($startdate, $enddate);
}

# Print either an overview calendar or event listings.
# (The code for either is largely the same, so there's
# one function for both.)
#
# Parameters:
# $type -- What to print. Either 'overview' or 'listings'
# $atts -- The attributes passed in to the shortcode handler.
function overview_or_event_listings($type, $atts) {
    if (!isset($atts['for'])) {
        die("Did an overview calendar or event listing without specifying 'for'");
    }

    if ($atts['for'] == 'palooza') {
        $caltype = 'palooza';
    }
    else if ($atts['for'] == 'current') {
        $caltype = 'cal';
    }
    else if ($atts['for'] == 'month') {
        $caltype = 'cal';
    }
    else {
        die("Bad value of for: " . $atts['for']);
    }

    list($startdate, $enddate) = get_cal_dates($atts);

    # WordPress wants shortcodes to return their content as a string,
    # not output it with print statements. But all of the existing code
    # uses print statements, and changing it to use strings would be a
    # hassle. Fortunately, PHP's output buffering (OB) functions can capture
    # print statements into a string.
    ob_start();
    ob_implicit_flush(0);
    
    if ($type == 'overview') {
        overview_calendar($startdate,
                          $enddate,
                          $caltype,
                          TRUE); # preload all days
    }
    else if ($type == 'listings') {
        event_listings($startdate,
                       $enddate,
                       TRUE, #preload
                       FALSE,   # For printer?
                       TRUE);  # Include images?
    }
    else {
        die("Bad value of type: " . $type);
    }

    $calendar_contents = ob_get_contents();
    ob_end_clean();

    return $calendar_contents;
}

#
# Print the overview calendar that goes on a page.
#
# This assumes you'll also put an event listing on the same page.
add_shortcode('bfc_overview_calendar', 'bike_fun_cal\bfc_overview_calendar_shortcode');
function bfc_overview_calendar_shortcode($atts) {
    return overview_or_event_listings('overview', $atts);
}

#
# Print the event listings. 
add_shortcode('bfc_event_listings', 'bike_fun_cal\bfc_event_listings_shortcode');
function bfc_event_listings_shortcode($atts) {
    return overview_or_event_listings('listings', $atts);
}

#
# Print the button to navigate to the previous set of dates
# in the calendar. 
add_shortcode('bfc_cal_date_navigation', 'bike_fun_cal\bfc_cal_date_navigation_shortcode');
function bfc_cal_date_navigation_shortcode($atts) {
    if ($atts['for'] == 'current') {
        list($startdate, $enddate) = get_cal_dates($atts);
        
        if (date("F", $startdate) != date("F", $enddate)) {
            # start & end dates are on different months

            $prev_url = get_month_cal_url(date("m", $startdate), date("y", $startdate));
            $next_url = get_month_cal_url(date("m", $enddate), date("y", $enddate));

            $prev_month_name = date("F", $startdate);
            $next_month_name = date("F", $enddate);

            return "<div class=cal-link>
                    <a href='${prev_url}'>&lt;-- All of ${prev_month_name}</a>
                    </div>

                    <div class=cal-link>
                    <a href='${next_url}'>All of ${next_month_name} --&gt;</a>
                    </div>";
        }
        else {
            # Start and end dates are on the same month

            $prev_month = $startdate - (86400 * 28);
            $next_month = $enddate   + (86400 * 28);

            $prev_url = get_month_cal_url(date("m", $prev_month), date("y", $prev_month));
            $next_url = get_month_cal_url(date("m", $next_month), date("y", $next_month));
            $curr_url = get_month_cal_url(date("m", $startdate),  date("y", $startdate));

            $prev_month_name = date("F", $prev_month);
            $next_month_name = date("F", $next_month);
            $curr_month_name = date("F", $startdate);

            return "<div class=cal-link>
                    <a href='${prev_url}'>&lt;-- ${prev_month_name}</a>
                    </div>
                    
                    <div class=cal-link>
                    <a href='${curr_url}'>All of ${curr_month_name}</a>
                    </div>

                    <div class=cal-link>
                    <a href='${next_url}'>${next_month_name} --&gt;</a>
                    </div>";
        }
    }
    else if ($atts['for'] == 'month') {
        list($startdate, $enddate) = get_cal_dates($atts);

        $prev_month = mktime(0, 0, 0,
                             date('m', $startdate) - 1,
                             1,
                             date('Y', $startdate));
        $next_month = mktime(0, 0, 0,
                             date('m', $startdate) + 1,
                             1,
                             date('Y', $startdate));

        $prev_url = get_month_cal_url(date("m", $prev_month), date("y", $prev_month));
        $next_url = get_month_cal_url(date("m", $next_month), date("y", $next_month));
                                     
        $prev_month_name = date("F", $prev_month);
        $next_month_name = date("F", $next_month);

        return "<div class=cal-link>
                <a href='${prev_url}'>&lt;-- ${prev_month_name}</a>
                </div>

                <div class=cal-link>
                <a href='${next_url}'>${next_month_name} --&gt;</a>
                </div>";
    }
    else {
        die("Can't have a previous date for: " . $atts['for']);
    }
}

# A shorttag that returns the number of events. It's meant to show how many
# events are in a palooza (current or archived).
add_shortcode('bfc_cal_count', 'bike_fun_cal\bfc_cal_count_shortcode');
function bfc_cal_count_shortcode($atts) {
    # Start and End can be specified to show archived Paloozas.
    #
    # get_cal_dates() returns times as integers and we want strings,
    # so don't bother calling it.
    $start = isset($atts['start']) ? $atts['start'] : get_option('bfc_festival_start_date');
    $end   = isset($atts['end'])   ? $atts['end']   : get_option('bfc_festival_end_date');

    global $wpdb;
    global $caldaily_table_name;
    global $calevent_table_name;
    # The old code checks the review flag here, but we haven't turned that on yet.
    $sql = $wpdb->prepare("SELECT count(${caldaily_table_name}.id) " .
                          "FROM ${caldaily_table_name}, ${calevent_table_name} " .
                          "WHERE ${calevent_table_name}.id = ${caldaily_table_name}.id AND " .
                          "      eventdate >= %s   AND " .
                          "      eventdate <= %s   AND " .
                          "      eventstatus <> \"E\"  AND " .
                          "      eventstatus <> \"C\"  AND " .
                          "      eventstatus <> \"S\" ",
                          $start, $end);
                          
    $count = $wpdb->get_var($sql);
    return $count;
}

function get_month_cal_url($month = null, $year = null) {
    $edit_page_title = 'Monthly Calendar';
    $edit_page = get_page_by_title($edit_page_title);
    $url = get_permalink($edit_page->ID); 

    if ($month !== null) {
        $url .= '&calmonth=' . $month;
    }

    if ($year !== null) {
        $url .= '&calyear=' . $year;
    }

    return $url;
}

#
# Print the event sumission form (or the results)
add_shortcode('bfc_event_submission', 'bike_fun_cal\bfc_event_submission_shortcode');
function bfc_event_submission_shortcode($atts) {
    global $wp_query;
    $event_submission = new BfcEventSubmission();
    $event_submission->populate_from_query($wp_query->query_vars, $_FILES);
    $event_submission->do_action();

    if ($event_submission->page_to_show() == "edit-event") {
        // We have to load the javascript in the footer, because by now
        // the header has already been output and it's too late for that.
        add_action('wp_footer', 'bike_fun_cal\load_event_submission_form_javascript');
        print_event_submission_form($event_submission);
    }
    else if ($event_submission->page_to_show() == "event-updated") {
        print_event_submission_result($event_submission);
    }
    else if ($event_submission->page_to_show() == "event-deleted") {
        print_event_deletion_result($event_submission);
    }
    else {
        die();
    }
}

/**
 * Load the JavaScript code that the event submission page needs.
 *
 * This is designed to be run in the wp_footer action.
 */
function load_event_submission_form_javascript() {
    # First, register jquery UI (the custom version)
    $jquery_js_url = 
        plugins_url('bikefuncal/jquery-ui/js/jquery-ui-1.8.11.custom.min.js');
    wp_register_script('bfc-jquery-ui', $jquery_js_url, array('jquery'));

    $jquery_css_url =
        plugins_url('bikefuncal/jquery-ui/css/ui-lightness/jquery-ui-1.8.11.custom.css');
    wp_register_style('bfc-jquery-ui-style', $jquery_css_url, null);

    # Second, register the JS for bikefuncal
                       
    # @@@ Evan isn't sure how to do this without hard-coding
    # 'bikefuncal' into the URL.
    $event_submission_js_url = plugins_url('bikefuncal/event-submission.js');
    
    $required_scripts = array('jquery', 'bfc-jquery-ui');
    wp_register_script('event-submission',
                       $event_submission_js_url,
                       $required_scripts);

    # Create the BikeFunAjax object in the page, to send data from
    # PHP to JavaScript.
    $ajax_options = array(
        'ajaxurl' => admin_url('admin-ajax.php'),
        # We have to use l10n_print_after to pass JSON-encoded data.
        # See: http://wordpress.stackexchange.com/q/8655
        'l10n_print_after' =>
            'BikeFunAjax.venues = ' . json_encode(venue_list()) . ';',
        );
    wp_localize_script('event-submission', 'BikeFunAjax', $ajax_options);
                       
    wp_print_styles('bfc-jquery-ui-style');
    wp_print_scripts('event-submission');                       
}

/**
 * Print the days in the festival (e.g., 'June 3 to 18, 2010').
 */
add_shortcode('bfc_festival_dates', 'bike_fun_cal\bfc_festival_dates_shortcode');
function bfc_festival_dates_shortcode($atts) {
    $start = get_option('bfc_festival_start_date');
    $end   = get_option('bfc_festival_end_date');

    $startdate = strtotime($start);
    $enddate   = strtotime($end);

    $start_month = date('F', $startdate);
    $start_day   = date('j', $startdate);
    $start_year  = date('Y', $startdate);

    $end_month = date('F', $enddate);
    $end_day   = date('j', $enddate);
    $end_year  = date('Y', $enddate);

    $same_month = ($start_month == $end_month);
    if ($same_month) {
        return sprintf('%s %s to %s, %s',
                       $start_month, $start_day, $end_day, $start_year);
    }
    else {
        return sprintf('%s %s to %s %s, %s',
                       $start_month, $start_day, $end_month, $end_day, $start_year);
    }
}

/**
 * Print the number of days in the festival
 */
add_shortcode('bfc_festival_count_days', 'bike_fun_cal\bfc_festival_count_days_shortcode');
function bfc_festival_count_days_shortcode($atts) {
    $start = new \DateTime(get_option('bfc_festival_start_date'));
    $end = new \DateTime(get_option('bfc_festival_end_date'));

    $interval = $start->diff($end);

    return $interval->d;
}
