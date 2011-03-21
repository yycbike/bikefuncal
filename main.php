<?php
/*
 * Plugin Name: Bike fun calendar
 */

# upgrade.php is required for the database upgrade functions
require_once(ABSPATH . "wp-admin/includes/upgrade.php");
require_once('print-event-listings.php');
require_once('repeat.php');
require_once('daily.php');
require_once('database-migrations.php');
require_once('event-submission.php');
require_once('event-submission-form.php');
require_once('event-submission-result.php');
require_once('event-delete-result.php');
require_once('shortcodes.php');
require_once('venue.php');
require_once('vfydates.php');

# bfc_install() lives in database-migrations.php,
# but __FILE__ has to be this file -- the main
# plugin file.
register_activation_hook(__FILE__,'bfc_install');

# Before a query parameter can be used with WordPress, it needs to be registered.
# (Otherwise WP will ignore it.) So here we register all the query parameters that
# we'll use in the plugin.
#
# Except that AJAX requests seem to have access to $_POST[], but other requests
# don't have access to $_GET[] or $_REQUEST[]. Evan isn't sure why that is. But
# the result is that parameters from AJAX requests don't need to be listed here.
#
# We try to prefix the parameters with things like "cal" or "event", to avoid conflicts
# with any parametrs WordPress may use internally.
function calendar_queryvars($qvars) {
    # Query vars for browsing the calendar.
    $qvars[] = 'calyear';
    $qvars[] = 'calmonth';

    # Query vars for making a new event.
    # Prefix with "event_" to avoid conflicts with
    # builtin WP query vars.
    $qvars[] = "event_name";
    $qvars[] = "event_email";
    $qvars[] = "event_hideemail";
    $qvars[] = "event_emailforum";
    $qvars[] = "event_printemail";
    $qvars[] = "event_phone";
    $qvars[] = "event_hidephone";
    $qvars[] = "event_printphone";
    $qvars[] = "event_weburl";
    $qvars[] = "event_webname";
    $qvars[] = "event_printweburl";
    $qvars[] = "event_contact";
    $qvars[] = "event_hidecontact";
    $qvars[] = "event_printcontact";
    $qvars[] = "event_title";
    $qvars[] = "event_tinytitle";
    $qvars[] = "event_audience";
    $qvars[] = "event_descr";
    $qvars[] = "event_printdescr";
    $qvars[] = "event_dates";
    $qvars[] = "event_datestype";
    $qvars[] = "event_eventtime";
    $qvars[] = "event_eventduration";
    $qvars[] = "event_timedetails";
    $qvars[] = "event_locname";
    $qvars[] = "event_address";
    $qvars[] = "event_addressverified";
    $qvars[] = "event_locdetails";
    $qvars[] = "event_review";
    $qvars[] = "event_editcode";

    # Query vars for managing the form for creating
    # new events. These get a calform_ prefix because
    # they don't go into the database.
    $qvars[] = "calform_action";
    $qvars[] = "calform_event_id";

    # Tell WordPress about the query vars for an event's newsflash & status.
    # But because these variables have a suffix with the day of the event
    # (like Jan19), we have to tell it about every day.
    for ($day_of_month = 1; $day_of_month <= 366; $day_of_month++) {
        # Use a leap year, so we get Feb. 29.
        # It doesn't matter which leap year we use, since we never
        # print the year.
        $year = 2008;

        # Iterate through every day in the year.
        # mktime() does the right thing when passing in
        # a "day of month" that's out of bounds. (The 366th of
        # January becomes December 31.)
        $thisdate = mktime(0, 0, 0, #h:m:s
                           1, # January
                           $day_of_month,
                           $year); 
        $suffix = date("Mj", $thisdate);

        $qvars[] = "event_newsflash" . $suffix;
        $qvars[] = "event_status" . $suffix;
    }
    
    return $qvars;
}
add_filter('query_vars', 'calendar_queryvars');

function bfc_create_post_types() {
    register_post_type('bfc-event', array(
        'description' => 'Events in the bike fun calendar',
        'public' => true,
        'show_ui' => true,
        'show_in_menu' => true,
        'supports' => array('title', 'comments'),
        'labels' => array(
            'name' => 'Bike Events',
            'singular_name' => 'Bike Event',
            'add_new' => 'Add New (Don\'t Do This!)',
            'add_new_item' => 'Add events through the calendar, not here!',
            'edit_item' => 'Edit Event',
            'new_item'  => 'New Event',
            'view_item' => 'View Event',
            'search_items' => 'Search for Event',
            'not_found' => 'No events found',
            'not_found_in_trash' => 'No events found in trash',
            'menu_name' => 'Bike Events',
        ),
        ));
}
add_action('init', 'bfc_create_post_types');

# Return whether or not to show options for administering the calendar,
# such as editing other people's events.
#
# Right now, this is tied into the ability to edit posts. WordPress lets
# a plugin define its own set of capability levels. That could be useful
# if we ever want to have multiple kinds of admins/editors.
# See: http://codex.wordpress.org/Roles_and_Capabilities
function bfc_show_admin_options() {
    return current_user_can('edit_posts');
}

# Add options to the WordPress admin menu
add_action('admin_menu', 'bfc_plugin_menu');
function bfc_plugin_menu() {
    add_menu_page('Bike Fun Cal', # Page title
                     'Bike Fun Cal', # Menu title
                     'manage_options', # capability
                     'bfc-top',   # menu slug
                     'bfc_options');  # function callback

    add_submenu_page('bfc-top', # parent
                     'Edit Known Venues', # title
                     'Venues',
                     'manage_options', # capability
                     'bfc-venues',   # menu slug
                     'bfc_venues');  # function callback

}

function bfc_options() {
    echo "<p>More options will go here someday</p>";
}



?>
