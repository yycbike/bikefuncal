<?php
/*
Plugin Name: Bike fun calendar
*/

# For DB upgrade functions
require_once(ABSPATH . "wp-admin/includes/upgrade.php");

global $wpdb;

$calevent_table_name =
    $wpdb->prefix . "bfc_calevent";
$caldaily_table_name =
    $wpdb->prefix . "bfc_caldaily";


function bfc_add_to_table($table_name, $fields, $args, $debugging_defaults) {

    # Defaults come first, so they're overridden by $args
    $full_args = array_merge($debugging_defaults, $args);

    global $wpdb;
    $wpdb->insert($table_name,
                  $full_args);

    return $wpdb->insert_id;
}

function bfc_add_event($event_args, $event_dates) {
    # @@@ need to add image, imagewidth, and imageheight
    $event_fields = Array(
        "name" => "string",
        "email" => "string",
        "hideemail" => "int",
        "emailforum" => "int",
        "printemail" => "int",
        "phone" => "string",
        "hidephone" => "int",
        "printphone" => "int",
        "weburl" => "string",
        "webname" => "string",
        "printweburl" => "int",
        "contact" => "string",
        "hidecontact" => "int",
        "printcontact" => "int",
        "title" => "string",
        "tinytitle" => "string",
        "audience" => "string",
        "descr" => "string",
        "printdescr" => "string",
        "dates" => "string",
        "datestype" => "string",
        "eventtime" => "string",
        "eventduration" => "string",
        "timedetails" => "string",
        "locname" => "string",
        "address" => "string",
        "addressverified" => "string",
        "locdetails" => "string",
        "review" => "string",
        );

    # Default values for fields. For when this is used
    # as a test function.
    $event_debugging_defaults = Array(
        "hideemail" => 1,
        "emailforum" => 0,
        "printemail" => 0,
        "hidephone"  => 1,
        "printphone" => 0,
        "printweburl" => 0,
        "hidecontact" => 0,
        "printcontact" => 0,
        "audience" => 'G',
        "datestype" => 'O',
        "addressverified" => 0,
        "review" => 'I',
        );


    global $calevent_table_name, $caldaily_table_name;
    $id = bfc_add_to_table($calevent_table_name,
                           $event_fields,
                           $event_args,
                           $event_debugging_defaults);


    foreach ($event_dates as $date) {
        $caldaily_args = Array(
            "id" => $id,
            "eventdate" => $date,
            "eventstatus" => "A", 
            );

        $caldaily_debugging_defaults = Array();

        bfc_add_to_table($caldaily_table_name,
                         Array(), # no fields for now
                         $caldaily_args,
                         $caldaily_debugging_defaults);
    }

}

function bfc_add_test_event() {
    $event_args = Array(
        "name" => "Evan",
        "email" => "hi@hello.com",
        "title" => "Awesomest Bike Ride Ever!",
        "tinytitle" => "Awesome Ride",
        "descr"    => "Stuff stuff stuff",
        );

    bfc_add_event($event_args, Array("2011-02-14"));
}

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

?>
