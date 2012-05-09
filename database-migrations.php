<?php
// Functions related to installing the datbase, and migrating between versions.

// For dbDelta().
require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

// Names of tables in the database.
global $calevent_table_name;
$calevent_table_name =
    $wpdb->prefix . "bfc_calevent";

global $caldaily_table_name;
$caldaily_table_name =
    $wpdb->prefix . "bfc_caldaily";

global $caldaily_for_listings_table_name;
$caldaily_for_listings_table_name =
    $wpdb->prefix . "bfc_caldaily_for_listings";

global $caldaily_num_days_for_listings_table_name;
$caldaily_num_days_for_listings_table_name =
    $wpdb->prefix . "bfc_caldaily_num_days_for_listings";

global $caladdress_table_name;
$caladdress_table_name =
    $wpdb->prefix . "bfc_caladdress";

function bfc_install_calevent() {
    global $calevent_table_name;
    global $wpdb;

    if (!isset($calevent_table_name)) {
        die("Error: Can't install DB before table names are set");
    }
    
    // Make the calevent table
    $sql = "CREATE TABLE ${calevent_table_name} (\n";
    $sql .= "modified TIMESTAMP,\n";	# when modified
    $sql .= "id INT NOT NULL AUTO_INCREMENT,\n"; #unique event id
    $sql .= "name VARCHAR(255),\n";	# name of event organizer
    $sql .= "email VARCHAR(255),\n";	# email address of organizer
    $sql .= "hideemail INT(1),\n";	# 1=hide online, 0=show online
    $sql .= "emailforum INT(1),\n";	# 1=forward forum msgs to email
    $sql .= "printemail INT(1),\n";	# 1=show in print, 0=omit
    $sql .= "phone VARCHAR(255),\n";	# phone number of organizer
    $sql .= "hidephone INT(1),\n";	# 1=hide online, 0=show online
    $sql .= "printphone INT(1),\n";	# 1=show in print, 0=omit
    $sql .= "weburl VARCHAR(255),\n";	# URL of organizer's web site
    $sql .= "webname VARCHAR(255),\n";	# name of organizer's web site
    $sql .= "printweburl INT(1),\n";	# 1=show URL in print, 0=omit
    $sql .= "contact VARCHAR(255),\n";	# other contact info
    $sql .= "hidecontact INT(1),\n";	# 1=hide online, 0=show online
    $sql .= "printcontact INT(1),\n";	# 1=show in print, 0=omit
    $sql .= "title VARCHAR(255),\n";	# full title of the event
    $sql .= "tinytitle VARCHAR(255),\n";	# tiny title of the event
    $sql .= "audience CHAR(1),\n";	# G=general, F=family, A=adult
    $sql .= "descr TEXT,\n";		# full description of event
    $sql .= "printdescr TEXT,\n";		# shorter print description
    $sql .= "image VARCHAR(255),\n";	# name of image, or "" if none
    $sql .= "imageheight INT,\n";		# image height if any
    $sql .= "imagewidth INT,\n";		# image width if any
    $sql .= "dates VARCHAR(255),\n";	# dates, e.g. "Every Sunday"
    $sql .= "datestype CHAR(1),\n";	# O=one day, C=consecutive, S=scattered
    $sql .= "eventtime TIME,\n";		# event's start time
    $sql .= "eventduration INT,\n";	# event's duration, in seconds
    $sql .= "timedetails VARCHAR(255),\n";# other time info
    $sql .= "locname VARCHAR(255),\n";	# name of venue
    $sql .= "address VARCHAR(255),\n";	# address or cross-streets
    $sql .= "addressverified CHAR(1),\n";	# Y=verified, otherwise not
    $sql .= "locdetails VARCHAR(255),\n";	# other location info
    $sql .= "review CHAR(1),\n";		# Inspect/Approved/Exclude/SentEmail/Revised
    $sql .= "editcode CHAR(13),\n";       # Authentication token for editing the event
    $sql .= "wordpress_id INT,\n";         # WordPress post ID
    $sql .= "PRIMARY KEY  (id)\n";
    $sql .= ");\n";
    
    dbDelta($sql);
}

function bfc_install_caldaily() {
    global $caldaily_table_name;
    global $calevent_table_name;
    global $wpdb;

    // Make the caldaily table
    $sql = "CREATE TABLE ${caldaily_table_name} (\n";
    $sql .= "modified TIMESTAMP,\n";	     // when modified
    $sql .= "id INT,\n";                     // which event this is for
    $sql .= "newsflash TEXT,\n";	     // usually "", else update info
    $sql .= "eventdate DATE,\n";	     // date of the event
    $sql .= "eventstatus VARCHAR(1),\n";     // A=as scheduled, S=skipped, C=canceled, E=exception
    $sql .= "exceptionid INT,\n";	     // foreign key into calevent, or 0
    $sql .= "UNIQUE KEY  (id, eventdate)\n";
    $sql .= ");\n";
    
    dbDelta($sql);

    return true;
}


function bfc_install_caladdress() {
    global $caladdress_table_name;
    global $wpdb;

    // This is changed from the old code. The old code had a canonical
    // name for the venue that was used to automatically match the user's
    // input. Since the new code uses a drop-down list to let the user select
    // from, we don't need the canonical name. That's replaced by an ID
    // as the primary key.

    $sql = "CREATE TABLE IF NOT EXISTS ${caladdress_table_name} (\n";
    $sql .= "id INT NOT NULL PRIMARY KEY AUTO_INCREMENT,\n"; 
    $sql .= "address VARCHAR(255),\n";	// address of the venue
    $sql .= "locname VARCHAR(255),\n";	// name of the venue
    $sql .= "locked INT(1)\n";		// 1=locked, 0=automatic updates allowed
    $sql .= ");\n";

    dbDelta($sql);

    return true;
}

function bfc_install_views() {
    global $calevent_table_name;
    global $caldaily_table_name;
    global $caldaily_for_listings_table_name;
    global $caldaily_num_days_for_listings_table_name;
    global $wpdb;

    // A view to retrieve the instances of events that should be
    // shown in the calendar. Making a view does two things:
    // 1) Cuts down on the number of times we have to put these
    //    WHERE clauses into queries.
    // 2) Specifically does not query the 'modified' column.
    //    Omitting 'modified' makes it possible to use this
    //    view in a sub-query where it is joined with
    //    caldaily, since the two 'modified' columns will not
    //    clash.
    $sql = <<<END_SQL
        CREATE OR REPLACE VIEW ${caldaily_for_listings_table_name}
        AS SELECT id, newsflash, eventdate, eventstatus, exceptionid
        FROM ${caldaily_table_name}
        WHERE eventstatus <> "E" AND
              eventstatus <> "S";
END_SQL;

    $result = $wpdb->query($sql);
    if ($result === false) {
        $wpdb->print_error();
        die("Couldn't create view ${caldaily_for_listings_table_name}");
    }
    
    // A view to count the number of listable instances of an event.
    // We do this in many places, so we make a view to cut down on
    // verbosity.
    $sql = <<<END_SQL
        CREATE OR REPLACE VIEW ${caldaily_num_days_for_listings_table_name}
        AS SELECT id, count(*) as num_days
        FROM ${caldaily_for_listings_table_name}
        GROUP BY id;

END_SQL;

    $result = $wpdb->query($sql);
    if ($result === false) {
        $wpdb->print_error();
        die("Couldn't create view ${caldaily_num_days_for_listings_table_name}");
    }

    return true;
}

function bfc_install() {
    bfc_install_calevent();
    bfc_install_caldaily();
    bfc_install_caladdress();
    bfc_install_views();
}

?>
