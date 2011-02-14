<?php
/*
 * Plugin Name: Bike fun calendar
 */

# upgrade.php is required for the database upgrade functions
require_once(ABSPATH . "wp-admin/includes/upgrade.php");
require_once('print-event-listings.php');
require_once('repeat.php');
require_once('daily.php');
require_once('event-submission.php');
require_once('event-submission-form.php');
require_once('event-submission-result.php');
require_once('vfydates.php');
require_once('shortcodes.php');

global $wpdb;

# Names of tables in the database.
# @@@ Variable names should be prefixed with bfc_
$calevent_table_name =
    $wpdb->prefix . "bfc_calevent";
$caldaily_table_name =
    $wpdb->prefix . "bfc_caldaily";

#
# Create the tables in the WordPress database.
function bfc_install() {
    global $calevent_table_name;
    global $caldaily_table_name;

    if($wpdb->get_var("SHOW TABLES LIKE '$calevent_table_name'") != $calevent_table_name) {

        $sql = "CREATE TABLE ${calevent_table_name} (";
        $sql .= "modified TIMESTAMP,";	# when modified
        $sql .= "id INT NOT NULL PRIMARY KEY AUTO_INCREMENT,"; #unique event id
        $sql .= "name VARCHAR(255),";	# name of event organizer
        $sql .= "email VARCHAR(255),";	# email address of organizer
        $sql .= "hideemail INT(1),";	# 1=hide online, 0=show online
        $sql .= "emailforum INT(1),";	# 1=forward forum msgs to email
        $sql .= "printemail INT(1),";	# 1=show in print, 0=omit
        $sql .= "phone VARCHAR(255),";	# phone number of organizer
        $sql .= "hidephone INT(1),";	# 1=hide online, 0=show online
        $sql .= "printphone INT(1),";	# 1=show in print, 0=omit
        $sql .= "weburl VARCHAR(255),";	# URL of organizer's web site
        $sql .= "webname VARCHAR(255),";	# name of organizer's web site
        $sql .= "printweburl INT(1),";	# 1=show URL in print, 0=omit
        $sql .= "contact VARCHAR(255),";	# other contact info
        $sql .= "hidecontact INT(1),";	# 1=hide online, 0=show online
        $sql .= "printcontact INT(1),";	# 1=show in print, 0=omit
        $sql .= "title VARCHAR(255),";	# full title of the event
        $sql .= "tinytitle VARCHAR(255),";	# tiny title of the event
        $sql .= "audience CHAR(1),";	# G=general, F=family, A=adult
        $sql .= "descr TEXT,";		# full description of event
        $sql .= "printdescr TEXT,";		# shorter print description
        $sql .= "image VARCHAR(255),";	# name of image, or "" if none
        $sql .= "imageheight INT,";		# image height if any
        $sql .= "imagewidth INT,";		# image width if any
        $sql .= "dates VARCHAR(255),";	# dates, e.g. "Every Sunday"
        $sql .= "datestype CHAR(1),";	# O=one day, C=consecutive, S=scattered
        $sql .= "eventtime TIME,";		# event's start time
        $sql .= "eventduration INT,";	# event's duration, in seconds
        $sql .= "timedetails VARCHAR(255),";# other time info
        $sql .= "locname VARCHAR(255),";	# name of venue
        $sql .= "address VARCHAR(255),";	# address or cross-streets
        $sql .= "addressverified CHAR(1),";	# Y=verified, otherwise not
        $sql .= "locdetails VARCHAR(255),";	# other location info
        $sql .= "external VARCHAR(250),";	# unique (with source) identifier of imported event
        $sql .= "source VARCHAR(250),";	# source of imported event
        $sql .= "nestid INT REFERENCES calevent(id),";# id of festival containing this event
        $sql .= "nestflag VARCHAR(1),";	# F=festival, G=group, else normal event
        $sql .= "review CHAR(1)";		# Inspect/Approved/Exclude/SentEmail/Revised
        $sql .= ");";

        $result = $wpdb->query($sql);
        if ($result === false) {
            $wpdb->print_error();
            die("Couldn't create ${calevent_table_name}");
        }

        $sql = "ALTER TABLE calevent ADD KEY (source, external);";
        $result = $wpdb->query($sql);
        if ($result === false) {
            $wpdb->print_error();
            die("Couldn't alter ${calevent_table_name}");
        }
    }

    if($wpdb->get_var("SHOW TABLES LIKE '$caldaily_table_name'") != $caldaily_table_name) {

        $sql = "CREATE TABLE ${caldaily_table_name} (";
        $sql .= "modified TIMESTAMP,";	# when modified
        $sql .= "id INT REFERENCES calevent,";# which event this is for
        $sql .= "newsflash TEXT,";		# usually "", else update info
        $sql .= "eventdate DATE,";		# date of the event
        $sql .= "eventstatus VARCHAR(1),";	# A=as scheduled, S=skipped, C=canceled, E=exception
        $sql .= "exceptionid INT";		# foreign key into calevent, or 0
        $sql .= ");";
        $result = $wpdb->query($sql);
        if ($result === false) {
            $wpdb->print_error();
            die("Couldn't create ${caldaily_table_name}");
        }

        $sql = "ALTER TABLE ${caldaily_table_name} ADD KEY (eventdate);";
        $result = $wpdb->query($sql);
        if ($result === false) {
            $wpdb->print_error();
            die("Couldn't alter ${caldaily_table_name}");
        }
    }

    return true;
}
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

?>
