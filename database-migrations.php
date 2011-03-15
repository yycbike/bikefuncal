<?php
# Functions related to installing the datbase, and migrating between versions.

global $bfc_db_version;
$bfc_db_version = 1;

# Names of tables in the database.
# @@@ Variable names should be prefixed with bfc_
global $calevent_table_name;
$calevent_table_name =
    $wpdb->prefix . "bfc_calevent";
global $caldaily_table_name;
$caldaily_table_name =
    $wpdb->prefix . "bfc_caldaily";



#
# Create the tables in the WordPress database.
function bfc_install_db_1() {
    global $calevent_table_name;
    global $caldaily_table_name;
    global $wpdb;

    if (!isset($calevent_table_name)) {
        die("Error: Can't install DB before table names are set");
    }
    
    # Make the calevent table
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

    $sql = "ALTER TABLE ${calevent_table_name} ADD KEY (source, external);";
    $result = $wpdb->query($sql);
    if ($result === false) {
        $wpdb->print_error();
        die("Couldn't alter ${calevent_table_name}");
    }

    # Make the caldaily table
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

    return true;
}



function bfc_install() {
    $db_version = get_option('bfc_db_version');

    if ($db_version === false) {
        # Database isn't installed yet

        $db_version = 0;
    }
    else {
        $db_version = (int) $db_version;
    }

    $install_functions = array('bfc_install_db_1');

    for ($db_level = 0; $db_level < count($install_functions); $db_level++) {
        $success = $install_functions[$db_level]();
        if (!$success) {
            return false;
        }
        update_option('bfc_db_version', $db_level);
    }

    return true;
}


?>