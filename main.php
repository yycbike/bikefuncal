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
    # new events. These get a submission_ prefix because
    # they don't go into the database.
    $qvars[] = "submission_action";
    $qvars[] = "submission_event_id";
    $qvars[] = "submission_image_action";

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

# Create the custom post type for bfc-events
add_action('init', function() {
    register_post_type('bfc-event', array(
        'description' => 'Events in the bike fun calendar',
        'public' => true,
        'show_ui' => true,
        'show_in_menu' => 'bfc-top',
        'menu_position' => 100, # below 2nd seperator
        'supports' => array('title', 'comments'),
        'capabilities' => array(
            # Have to allow an edit_post capability, because otherwise you
            # cannot edit comments.
            'edit_post'     => 'moderate_comments',
            'publish_posts' => 'do_not_allow',
            'read_post'     => 'read',
            'delete_post'   => 'do_not_allow',
        ),
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
            'menu_name' => 'Events',
        ),
        ));

    # Customize the appearance of the post types through the admin screens.
    

    # Remove entries from the drop-down list of "bulk actions", on the
    # edit page in the admin menu.
    add_action('bulk_actions-edit-bfc-event', function($actions) {
        unset($actions['edit']);
        unset($actions['trash']);

        return $actions;
    });

    # CSS to hide certain things from the admin page.
    add_action('admin_print_styles', function() {
        global $current_screen;
        if ('edit-bfc-event' != $current_screen->id) {
            return;
        }

        ?>
        <style type="text/css">
            .add-new-h2, /* 'Add New' button at the top of the page */
            div.row-actions span /* Quick actions below an item in the event listing  */
                {
                display: none;
            }
        </style>
        <?php
    });

    # Customize the columns in the list of bike events.
    add_filter("manage_bfc-event_posts_columns", function($old_cols) {
        $new_cols = array(
            'cb' => $old_cols['cb'], # Checkbox

            # In order to customize the title, it cannot be named 'title'
            'bfc_title' => $old_cols['title'],

            'comments' => $old_cols['comments'],

            # Skip 'date' from the old columns. That is the date the event
            # was added to the calendar.
        );

        return $new_cols;
    });

    # Display custom columns in the list of bike events.
    add_action("manage_bfc-event_posts_custom_column", function($col) {
        global $post;

        if ($col == 'bfc_title' && get_post_type($post->ID) == 'bfc-event') {

            # Customizing the 'title' column lets us remove the edit link.
            printf("<a href='%s' title='View'><strong>%s</strong></a>",
                   get_permalink($post->ID),
                   esc_html($post->post_title));
        }
    });
});

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
                         function() {
                             echo "<p>TBD</p>";
                         });

    add_submenu_page('bfc-top', # parent
                     'Edit Known Venues', # title
                     'Venues',
                     'manage_options', # capability
                     'bfc-venues',   # menu slug
                     'bfc_venues');  # function callback

    add_submenu_page('bfc-top', # parent
                     'Bike Fun Cal Options', # title
                     'Options',
                     'manage_options', # capability
                     'bfc-options',   # menu slug
                     'bfc_options');  # function callback
                     
}

function bfc_options() {
    # echo "<p>More options will go here someday</p>";
    
    ?>
    <div class="wrap">
        <h2>Bike Fun Calendar Options</h2>
        <p>
        
        </p>

        <form method="post" action="options.php">
            <?php settings_fields( 'bikefuncal-options' ); ?>

            <p>
            Festival Start Date <em>(ex: 2011-06-02)</em>:
            <input type='text' name='bfc_festival_start_date' value='<?php echo get_option('bfc_festival_start_date'); ?>'
            </p>

            <p>
            Festival End Date:
            <input type='text' name='bfc_festival_end_date' value='<?php echo get_option('bfc_festival_end_date'); ?>'
            </p>

            <p>
            Drinking Age:
            <input type='text' name='bfc_drinking_age' value='<?php echo get_option('bfc_drinking_age'); ?>'
            </p>

            <br>
            <input type='submit' value='save'>
        </form>                
    </div>
    <?php
}

function bfc_event_content_filter($content) {
    if (get_post_type() == 'bfc-event') {
        # This is a calendar event.
        # Show the event listing in the body.

        global $calevent_table_name;
        global $caldaily_table_name;
        global $wpdb;
        global $wp_query;

        $sql = <<<END_QUERY
SELECT *
FROM ${calevent_table_name}, ${caldaily_table_name}
WHERE ${calevent_table_name}.id = ${caldaily_table_name}.id AND
      ${calevent_table_name}.wordpress_id = %d
END_QUERY;
        $sql = $wpdb->prepare($sql, $wp_query->post->ID);
        $records = $wpdb->get_results($sql, ARRAY_A);
        $num_records = count($records);

        if ($num_records == 1) {
            # WordPress wants filters to return their content as a string,
            # not output it with print statements. But all of the existing code
            # uses print statements, and changing it to use strings would be a
            # hassle. Fortunately, PHP's output buffering (OB) functions can capture
            # print statements into a string.
            ob_start();
            ob_implicit_flush(0);
            fullentry($records[0],
                      'event-page',
                      TRUE);    # include images
            $listing = ob_get_contents();
            ob_end_clean();

            # Add a link for editing comments
            if (current_user_can('moderate_comments')) {
                $comments_url = admin_url(sprintf('edit-comments.php?p=%d', $wp_query->post->ID));
                $listing .= "<p><a href='${comments_url}'>Edit Comments</a></p>";
            }

            # For bfc-event posts, there is no content, so we
            # don't need to do anything with the $content
            # variable.
            return $listing;
        }
        else {
            die("Couldn't load event listing");
        }
    }
    else {
        # This is some other kind of page. Don't mess with it.
        return $content;
    }
}
add_filter('the_content', 'bfc_event_content_filter');


add_action('admin_init', 'bfc_options_init');
function bfc_options_init() {
    register_setting('bikefuncal-options', 'bfc_festival_start_date', 'bfc_sanitize_festival_date');
    register_setting('bikefuncal-options', 'bfc_festival_end_date',   'bfc_sanitize_festival_date');
    register_setting('bikefuncal-options', 'bfc_drinking_age');
}

function bfc_sanitize_festival_date($input) {
    $date = strtotime($input);
    if ($date === false) {
        return "";
    }
    else {
        return $input;
    }
}



?>
