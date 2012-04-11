<?php
/**
Imports the events from the old code (from Shift) into the new WordPress database.

Important things to know:
- THIS DELETES ALL EVENTS THAT ARE CURRENTLY IN THE CALENDAR
- This does *not* import the old forum messages. (Ours were 95% spam.)
- This also does not import the known-venue list. It could be improved to do so.
   
Import instructions:
1) Back up your WordPress database

2) On the old site, delete all events without days.
   - Do this with the administrator tools. You could also use the mysql client to
     do a bulk delete, if there are lots.
   - Events without days are usually created by spambots.

3) Copy the old event images to the new server
   - This is the eventimages directory, in the old code
   - Set the path below, as BFC_IMPORT_IMAGE_SRC
   - Make sure the directory is readable by the web server process

4) Backup the old calendar's database
   - $ mysqldump -h [hostname] -u [username] -p [databasename] caladdress calevent caldaily > backup.sql

5) Restore the backup into the new database
   - $ mysql -h [hostname] -u [username] -p [databasename] < backup.sql
   - You'll have two sets of tables in your database: caladdress etc. are from the
     old calendar; wp_bfc_caladdress, etc., are for the new calendar.

6) Read the code in this file to make sure it's still up to date. Check with Evan
if you're not sure.

7) Go to the WordPress admin panel and run the import.
   - The magic word is cheese (lower case)

8) After the import, go into mysql and drop these tables: caladdress, calevent, caldaily
 */

// The folder on the new server where the events from the old calendar lives.
// Must end with a slash.
define('BFC_IMPORT_IMAGE_SRC', '/home/velo/public_html/eventimages/');

function bfc_import_event($event) {
    global $wpdb;
    global $calevent_table_name;
    global $caldaily_table_name;
    $old_event_id = $event['id'];
    unset($event['id']);
    unset($event['modified']);
    unset($event['external']);
    unset($event['source']);
    unset($event['nestid']);
    unset($event['nestflag']);
    unset($event['review']);
    $event['editcode'] = uniqid();

//        print("<pre>\n");
//        var_dump($event);
//        print("</pre>\n");

    // Make a WordPress post...
    $post_props = array(
        'post_title' => $event['title'],
        'comment_status' => 'open',
        'post_type' => 'bfc-event',
        'post_status' => 'publish',
                        );
    $post_id = wp_insert_post($post_props);
    if ($post_id === false) {
        die("Couldn't create wordpress post");
        return false;
    }
    $event['wordpress_id'] = $post_id;

    // Import the old image
    if ($event['image']) {
        // source filename
        $extension = pathinfo($event['image'], PATHINFO_EXTENSION);
        // The image's filename has the event ID. But what's stored in the
        // database is the original, uploaded filename.
        $source =
            BFC_IMPORT_IMAGE_SRC . $old_event_id . '.' . $extension;
        
        // Destination filename
        $upload_dirinfo = wp_upload_dir();
        $filename = $upload_dirinfo['path'] . '/' .
            uniqid() . '.' . $extension;

        $result = copy($source, $filename);
        if ($result === false) {
            die("Couldn't copy: " . $source);
            return false;
        }

        // Make a filename that's relative to the uploads dir.
        // We can use this later to construct a URL.
        $relative_filename =
            str_replace($upload_dirinfo['basedir'], '', $filename);
        $event['image'] = $relative_filename;
    }

    $result = $wpdb->insert($calevent_table_name,
                            $event);
    $event_id = $wpdb->insert_id;
    if ($result === false) {
        return false;
    }
        
    $sql = $wpdb->prepare("SELECT * FROM caldaily WHERE id=%d", $old_event_id);
    $days = $wpdb->get_results($sql, ARRAY_A);
    foreach ($days as $day) {
        $day_info = array(
            'id' => $event_id,
            'newsflash' => $day['newsflash'],
            'eventdate' => $day['eventdate'],
            'eventstatus' => $day['eventstatus']);

        if ($day['eventstatus'] === 'E') {
            // This day is an exception. Import the exception, then
            // keep the new ID.

            $day_info['exceptionid'] =
                bfc_import_event_by_id($day['exceptionid']);
        }

        $result = $wpdb->insert($caldaily_table_name, $day_info);
        if ($result === false) {
            $wpdb->print_error();
            return false;
        }
    }

    print "<tr><td>";
    print esc_html($event['title']);
    print "</td><td>";
    print esc_html($old_event_id);
    print "</td><td>";
    print esc_html($event_id);
    print "</td></tr>";

    return $event_id;
}

function bfc_import_event_by_id($event_id) {
    global $wpdb;
    $sql = $wpdb->prepare("SELECT * FROM calevent " .
                          "WHERE id = %d",
                          $event_id);
    $events = $wpdb->get_results($sql, ARRAY_A);
    if (count($events) != 1) {
        die("Wrong number of events");
        return false;
    }

    return bfc_import_event($events[0]);
}

function bfc_import() {
    global $wpdb;

    print "<table>\n";
    print "<tr><th>Name</th><th>Old ID</th><th>New ID</th></tr>";

    $wpdb->show_errors();
    
    bfc_delete_all_old_events();

    $sql = $wpdb->prepare("SELECT * FROM calevent");
    $events = $wpdb->get_results($sql, ARRAY_A);
    foreach ($events as $event) {
        // Need to make sure this event isn't an exception
        // for another event
        $sql = $wpdb->prepare("SELECT count(*) " .
                              "FROM caldaily " .
                              "WHERE exceptionid = %d",
                              $event['id']);
        $exception_count = $wpdb->get_var($sql);

        if ($exception_count === '0') {
            $result = bfc_import_event($event);
            if ($result === false) {
                return false;
            }
        }
    }

    print "</table>\n";

    return true;
}

function bfc_delete_all_old_events() {
    global $wpdb;
    global $calevent_table_name;
    global $caldaily_table_name;
    $sql = $wpdb->prepare("SELECT * FROM $calevent_table_name");
    $events = $wpdb->get_results($sql, ARRAY_A);
    
    foreach ($events as $event) {
        wp_delete_post($event['wordpress_id']);

        if ($event['image']) {
            $upload_dirinfo = wp_upload_dir();
            $old_filename = $upload_dirinfo['basedir'] .
                $event['image'];
            
            $result = unlink($old_filename);
            if (!$result) {
                die("Can't delete image: $old_filename");
            }
        }
    }

    $wpdb->query("DELETE FROM $calevent_table_name;");
    $wpdb->query("DELETE FROM $caldaily_table_name;");
}

?>
