<?php
/*
 * Plugin Name: Bike fun calendar
 */

// upgrade.php is required for the database upgrade functions
require_once(ABSPATH . "wp-admin/includes/upgrade.php");
require_once('print-event-listings.php');
require_once('repeat.php');
require_once('common.php');
require_once('daily.php');
require_once('database-migrations.php');
require_once('event-submission.php');
require_once('event-submission-form.php');
require_once('event-submission-result.php');
require_once('event-delete-result.php');
require_once('import-old-calendar.php');
require_once('shortcodes.php');
require_once('search.php');
require_once('venue.php');
require_once('vfydates.php');

// bfc_install() lives in database-migrations.php,
// but __FILE__ has to be this file -- the main
// plugin file.
register_activation_hook(__FILE__,'bfc_install');

// Before a query parameter can be used with WordPress, it needs to be registered.
// (Otherwise WP will ignore it.) So here we register all the query parameters that
// we'll use in the plugin.
//
// Except that AJAX requests seem to have access to $_POST[], but other requests
// don't have access to $_GET[] or $_REQUEST[]. Evan isn't sure why that is. But
// the result is that parameters from AJAX requests don't need to be listed here.
//
// We try to prefix the parameters with things like "cal" or "event", to avoid conflicts
// with any parametrs WordPress may use internally.
add_filter('query_vars', 'bfc_query_vars_filter');
function bfc_query_vars_filter($qvars) {
    // Query vars for browsing the calendar.
    $qvars[] = 'calyear';
    $qvars[] = 'calmonth';

    // Query vars for making a new event.
    // Prefix with "event_" to avoid conflicts with
    // builtin WP query vars.
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

    // Query vars for managing the form for creating
    // new events. These get a submission_ prefix because
    // they don't go into the database.
    $qvars[] = "submission_action";
    $qvars[] = "submission_event_id";
    $qvars[] = "submission_image_action";
    $qvars[] = "submission_comment";
    $qvars[] = "submission_suppress_email";
    $qvars[] = "submission_changed_by_admin";

    // Tell WordPress about the query vars for an event's newsflash & status.
    // But because these variables have a suffix with the day of the event
    // (like Jan19), we have to tell it about every day.
    for ($day_of_month = 1; $day_of_month <= 366; $day_of_month++) {
        // Use a leap year, so we get Feb. 29.
        // It doesn't matter which leap year we use, since we never
        // print the year.
        $year = 2008;

        // Iterate through every day in the year.
        // mktime() does the right thing when passing in
        // a "day of month" that's out of bounds. (The 366th of
        // January becomes December 31.)
        $thisdate = mktime(0, 0, 0, //h:m:s
                           1, // January
                           $day_of_month,
                           $year); 
        $suffix = date("Mj", $thisdate);

        $qvars[] = "event_newsflash" . $suffix;
        $qvars[] = "event_status" . $suffix;
    }
    
    return $qvars;
};

// Create the custom post type for bfc-events
add_action('init', 'bfc_init_action');
function bfc_init_action() {
    // Make a new calendar editor role. Someone who can work on the calendar, but not
    // much else.
    $cal_editor = get_role('bfc_calendar_editor');
    if ($cal_editor === null) {
        $cal_editor = add_role('bfc_calendar_editor', 'Calendar Editor', array());
    }
    $cal_editor->add_cap('edit_posts');
    $cal_editor->add_cap('delete_posts');
    $cal_editor->add_cap('read');
    $cal_editor->add_cap('moderate_comments');

    // Add the custom capabilities
    $role_names = array('administrator', 'editor', 'author', 'bfc_calendar_editor');
    foreach ($role_names as $role_name) {
        $role = get_role($role_name);

        // Edit events without being given an editcode
        $role->add_cap('bfc_edit_others_events');

        // Edit the known-venues list (add, update, delete)
        $role->add_cap('bfc_edit_known_venues');
    }
                 
    register_post_type('bfc-event', array(
        'description' => 'Events in the bike fun calendar',
        'public' => true,
        'show_ui' => true,
        'show_in_menu' => 'bfc-top',
        'menu_position' => 100, // below 2nd seperator
        'supports' => array('title', 'comments'),
        'capabilities' => array(
            // Have to allow an edit_post capability, because otherwise you
            // cannot edit comments.
            'edit_post'     => 'bfc_edit_others_events',
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

    // The rest of the function customizes the appearance of the post types through the admin screens.
        
    // Remove entries from the drop-down list of "bulk actions", on the
    // edit page in the admin menu.
    add_action('bulk_actions-edit-bfc-event', 'bfc_edit_bfc_event_action');
    function bfc_edit_bfc_event_action($actions) {
        unset($actions['edit']);
        unset($actions['trash']);

        return $actions;
    }

    // CSS to hide certain things from the admin page.
    add_action('admin_print_styles', 'bfc_admin_print_styles_action');
    function bfc_admin_print_styles_action() {
        global $current_screen;
        if ('edit-bfc-event' != $current_screen->id) {
            return;
        }

        ?>
        <style type="text/css">
            .add-new-h2, /* 'Add New' button at the top of the page */
            select[name=m], /* Drop-down of months. Hide it because month means month event was created, not when the
                             * event is.
                             */
            input#post-query-submit, /* submit button for above */                             
            div.row-actions span /* Quick actions below an item in the event listing  */
                {
                display: none;
            }
        </style>
        <?php
    }

    // Customize the columns in the list of bike events.
    add_filter("manage_bfc-event_posts_columns", 'bfc_event_posts_columns_filter');
    function bfc_event_posts_columns_filter($old_cols) {
        $new_cols = array(
            'cb' => $old_cols['cb'], // Checkbox

            // In order to customize the title, it cannot be named 'title'
            'bfc_title' => $old_cols['title'],

            'comments' => $old_cols['comments'],

            // Skip 'date' from the old columns. That is the date the event
            // was added to the calendar.
        );

        return $new_cols;
    };

    // Display custom columns in the list of bike events.
    add_action("manage_bfc-event_posts_custom_column", 'bfc_event_posts_custom_column_action');
    function bfc_event_posts_custom_column_action($col) {
        global $post;

        if ($col == 'bfc_title' && get_post_type($post->ID) == 'bfc-event') {

            // Customizing the 'title' column lets us remove the edit link.
            printf("<a href='%s' title='View'><strong>%s</strong></a>",
                   esc_url(get_permalink($post->ID)),
                   esc_html($post->post_title));
        }
    };

    // There is a drop-down of common actions. Remove the new event link
    // from it.
    add_filter('favorite_actions', 'bfc_favorite_actions_filter');
    function bfc_favorite_actions_filter($actions) {
        $new_link = 'post-new.php?post_type=bfc-event';
        if (isset($actions[$new_link])) {
            unset($actions[$new_link]);
        }
        return $actions;
    };
}

// Change links for editing bike events to go to the regular edit page,
// not the one WordPress generated.
add_filter('get_edit_post_link', 'bfc_get_edit_post_link_filter',
           10, 3); // 10 = priority (default); 3 = pass in all 3 arguments.
function bfc_get_edit_post_link_filter($url, $post_id, $context) {
    if (get_post_type($post_id) == 'bfc-event') {
        return bfc_get_edit_url_for_wordpress_id($post_id);
    }
    else {
        return $url;
    }
}

// Add options to the WordPress admin menu
add_action('admin_menu', 'bfc_admin_menu_action');
function bfc_admin_menu_action() {
    add_menu_page('Bike Fun Cal', // Page title
                  'Bike Fun Cal', // Menu title
                  'bfc_edit_known_venues', // capability
                  'bfc-top',   // menu slug
                  'bfc_top_admin_page');

    add_submenu_page('bfc-top', // parent
                     'Edit Known Venues', // title
                     'Venues',
                     'bfc_edit_known_venues', // capability
                     'bfc-venues',   // menu slug
                     'bfc_venues_admin_page');  // function callback

    add_submenu_page('bfc-top', // parent
                     'Bike Fun Cal Options', // title
                     'Options',
                     'manage_options', // capability
                     'bfc-options',   // menu slug
                     'bfc_options_admin_page');  // function callback

    add_submenu_page('bfc-top', //parent
                     'Import Database from Old Site', //title
                     'Import',
                     'manage_options', //capability
                     'bfc-import', // menu slug
                     'bfc_import_admin_page'); // function callback
}

function bfc_top_admin_page() {
    echo "<p>TBD</p>";
}

function bfc_options_admin_page() {
    // Attach javascript
    add_action('admin_footer', 'bfc_admin_footer_action');
    function bfc_admin_footer_action() {
        // Register the google maps API.
        wp_register_script('google-maps', 'http://maps.googleapis.com/maps/api/js?sensor=false', null);

        wp_register_script('bfc-options',
                           plugins_url('bikefuncal/options.js'),
                           array('google-maps'));

        wp_print_scripts('bfc-options');
    };

    ?>
    <div class="wrap">
        <h2>Bike Fun Calendar Options</h2>
        <p>
        
        </p>

        <form method="post" action="options.php">
            <?php settings_fields( 'bikefuncal-options' ); ?>

            <h3>Festival</h3>
            <p>This plugin assumes you have a Pedalpalooza-type festival. Enter the dates of the next festival here.</p>
        
            <p>
            Festival Start Date:
            <input type='text' name='bfc_festival_start_date' value='<?php echo esc_attr(get_option('bfc_festival_start_date')); ?>'>
            <em>YYYY-MM-DD (e.g. 2011-06-02)</em>        
            </p>

            <p>
            Festival End Date:
            <input type='text' name='bfc_festival_end_date' value='<?php echo esc_attr(get_option('bfc_festival_end_date')); ?>'>
            <em>YYYY-MM-DD</em>
            </p>

            <h3>Location</h3>
            <p>
            Where is the bike fun happening? Entering this info helps locate addresses. For example, if a user enters an
            address of "750 Hornby" we want the Google Map to go to 750 Hornby in Vancouver, and not 750 Hornby in Yakima,
            WA or Bonner, ID, or the other cities with Hornby streets.
            </p>

            <p>
            Your City:
            <input type='text' id='bfc_city' name='bfc_city' value='<?php echo esc_attr(get_option('bfc_city')); ?>'>
            <em>e.g., Vancouver</em>
            </p>

            <p>
            Your Province (or State):
            <input type='text' id='bfc_province' name='bfc_province' value='<?php echo esc_attr(get_option('bfc_province')); ?>'>
            <em>e.g., BC. Leave this blank if your country doesn't have provinces.</em>
            </p>

            <p>
            Your Country:
            <input type='text' id='bfc_country' name='bfc_country' value='<?php echo esc_attr(get_option('bfc_country')); ?>'>
            <em>e.g., Canada</em>

            <!-- These will get filled in by the AJAX from google that looks up the lat & long based on the location -->
            <input type='hidden' id='bfc_latitude' name='bfc_latitude' value='<?php echo esc_attr(get_option('bfc_latitude')); ?>'>
            <input type='hidden' id='bfc_longitude' name='bfc_longitude' value='<?php echo esc_attr(get_option('bfc_longitude')); ?>'>

            <!-- Report Google's AJAX results to the user -->
            <div id='bfc_latlong_results'>
            </div>

            <p>

    <h3>Organization</h3>
            <p>Information about the people putting on the festival.</p>
        
            <p>
            Calendar crew e-mail address:
            <input type='text' name='bfc_calendar_email' value='<?php echo esc_attr(get_option('bfc_calendar_email')); ?>'>
            <em>The people at this address help when ride leaders have problems making an event. And when people create an event, they get an
            e-mail from this address.
            </em>
            </p>
        
            <h3>Misc.</h3>

            <p>
            Drinking Age:
            <input type='text' name='bfc_drinking_age' value='<?php echo esc_attr(get_option('bfc_drinking_age')); ?>'>
            <em>People coming on adults-only rides need to be at least this old</em>
            </p>
            <p>
            <input type='submit' value='save'>
            </p>
        </form>                
    </div>
    <?php
}

function bfc_import_admin_page() {
    // $wp_query isn't set here (maybe b/c this is an admin page). Have to
    // check $_POST instead.

    if (isset($_POST['bfc_import']) &&
        $_POST['bfc_import'] == 'do-import') {

        bfc_import();
    }
    else {
    
    ?>
    <p>Use this <strong>once</strong> to import the old site.
    You should know what you're doing here!
    </p>

    <form method='post'>
    <input type='hidden' name='bfc_import' value='do-import'>
    <input type='submit' value='Import Old Site'>
    </form>

    <?php
    } // end if
    
}

/**
 * When showing a post of type bfc-event, show the event details as the body of the page.
 *
 * the_content is used when displaying the page for the event. the_excerpt is used
 * in search results. In both cases, show the event details.
 */
add_filter('the_content', 'bfc_the_content_filter');
add_filter('the_excerpt', 'bfc_the_content_filter');
function bfc_the_content_filter($content) {
    if (get_post_type() == 'bfc-event') {
        // This is a calendar event.
        // Show the event listing in the body.

        global $calevent_table_name;
        global $caldaily_table_name;
        global $wpdb;
        global $wp_query;

        $sql = <<<END_QUERY
SELECT *
FROM ${calevent_table_name}, ${caldaily_table_name}
WHERE ${calevent_table_name}.id = ${caldaily_table_name}.id AND
      ${calevent_table_name}.wordpress_id = %d
ORDER BY ${caldaily_table_name}.eventdate
LIMIT 1

END_QUERY;
        $sql = $wpdb->prepare($sql, $wp_query->post->ID);
        $records = $wpdb->get_results($sql, ARRAY_A);
        $num_records = count($records);

        if ($num_records == 1) {
            // WordPress wants filters to return their content as a string,
            // not output it with print statements. But all of the existing code
            // uses print statements, and changing it to use strings would be a
            // hassle. Fortunately, PHP's output buffering (OB) functions can capture
            // print statements into a string.
            ob_start();
            ob_implicit_flush(0);
            fullentry($records[0],
                      'event-page',
                      TRUE);    // include images
            $listing = ob_get_contents();
            ob_end_clean();

            // Add a link for editing comments
            if (current_user_can('moderate_comments')) {
                $comments_url = admin_url(sprintf('edit-comments.php?p=%d', esc_attr($wp_query->post->ID)));
                $listing .= "<p><a href='${comments_url}'>Edit Comments</a></p>";
            }

            // For bfc-event posts, there is no content, so we
            // don't need to do anything with the $content
            // variable.
            return $listing;
        }
        else {
            die("Couldn't load event listing");
        }
    }
    else {
        // This is some other kind of page. Don't mess with it.
        return $content;
    }
}

add_action('admin_init', 'bfc_admin_init_action');
function bfc_admin_init_action() {
    register_setting('bikefuncal-options', 'bfc_festival_start_date', 'bfc_sanitize_festival_date');
    register_setting('bikefuncal-options', 'bfc_festival_end_date',   'bfc_sanitize_festival_date');
    register_setting('bikefuncal-options', 'bfc_drinking_age');
    register_setting('bikefuncal-options', 'bfc_city');
    register_setting('bikefuncal-options', 'bfc_province');
    register_setting('bikefuncal-options', 'bfc_country');
    register_setting('bikefuncal-options', 'bfc_latitude');
    register_setting('bikefuncal-options', 'bfc_longitude');
    register_setting('bikefuncal-options', 'bfc_calendar_email');
}

/**
 * Ensure that the festival date is a well-formatted date.
 */
function bfc_sanitize_festival_date($input) {
    $date = strtotime($input);
    if ($date === false) {
        return "";
    }
    else {
        return $input;
    }
}

// Use pretty URLs for editing events
add_filter('rewrite_rules_array', 'bfc_rewrite_rules_array_filter');
function bfc_rewrite_rules_array_filter($rules) {
    $newrules = array();
    $newrules['edit/(\d+)/(\w+)$'] = 'index.php?pagename=event&submission_action=edit&submission_event_id=$matches[1]&event_editcode=$matches[2]';
    return $newrules + $rules;
}

add_filter('wp_loaded', 'bfc_wp_loaded_filter');
function bfc_wp_loaded_filter() {
    $rules = get_option('rewrite_rules');

    if (!isset($rules['edit/(\d+)/(\w+)$'])) {
        global $wp_rewrite;
        $wp_rewrite->flush_rules();
    }
}

?>
