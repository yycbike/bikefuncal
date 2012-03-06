<?php
// This file comes from the old, pre-wordpress code (where it was named view.php).
//
// It prints out events -- the overviews and the individual listings

/**
 * Trim the words to a certain length. Use this because
 * WordPress' wp_trim_excerpt() is tightly bound with The Loop.
 * You can't use it with arbitrary text.
 * 
 * See http://wordpress.stackexchange.com/a/7400
 */
function bfc_excerpt($text, $excerpt_more)
{
    $text = strip_shortcodes( $text );

    $text = apply_filters('the_content', $text);
    $text = str_replace(']]>', ']]&gt;', $text);
    $text = strip_tags($text);
    $excerpt_length = apply_filters('excerpt_length', 55);
    // Don't use the excerpt_more filter, because it expects to be called from The Loop
    //$excerpt_more = apply_filters('excerpt_more', ' ' . '[...]');
    $words = preg_split("/[\n\r\t ]+/", $text, $excerpt_length + 1, PREG_SPLIT_NO_EMPTY);
    if ( count($words) > $excerpt_length ) {
        array_pop($words);
        $text = implode(' ', $words);
        $text = $text . $excerpt_more;
    } else {
        $text = implode(' ', $words);
    }

    return $text;
}

/**
 * Create a google maps link to the specified address.
 *
 * Reference: http://mapki.com/wiki/Google_Map_Parameters 
 */
function address_link($address) {
    // Use country-specific google maps, if appropriate
    if (strtolower(get_option('bfc_country')) == 'canada') {
        $site = 'http://maps.google.ca/';
    }
    else {
        $site = 'http://maps.google.com/';
    }

    // Start building arguments to pass to google maps
    $query_args = array(
        'q' => $address,
        't' => 'm', // Type = map
        'lci' => 'bike', // Show bike tiles
        );

    // Get the lat & long 
    $latitude = get_option('bfc_latitude');
    $longitude = get_option('bfc_longitude');
    if (strlen($latitude) > 0 && strlen($longitude) > 0) {
        // sll = search lat/long. Tells Google where to centre the
        // search coordinates.
        $query_args['sll'] = $latitude . ',' . $longitude;
    }

    return $site . '?' . http_build_query($query_args);
}

function add_date_to_permalink($permalink, $sqldate) {
    $query_args = array('date' => $sqldate);
    $query_string = http_build_query($query_args);

    // @@@ Need to handle the case where the old permalink is already a query
    return $permalink . '?' . $query_string;
}

function class_for_special_day($thisdate) {
    // For Pedalpalooza, the Portland calendar highlights
    // special events, such as MCBF, Father's Day, and the Solstice.
    // If we wanted to do that, we could do so here.
    return "";
}

// Output the TD for one day in the overview calendar
function overview_calendar_day($thisdate, $preload_alldays) {
    $dayofmonth = date("j",$thisdate);

    // If today is special...
    $class = class_for_special_day($thisdate);

    // Highlight today's date.
    if (date("Y-m-d", time()) == date("Y-m-d", $thisdate)) {
        $class .= " today";
    }
    
    printf("<td id=\"%s\" class=\"%s\">\n", esc_attr('cal' . $dayofmonth), esc_attr($class));

    // For debugging
    //print "<p>" . date("Y-m-d h:m:s", $thisdate) . "</p>";
    
    // Output this day's tinytitles
    print "<div class='date'>";
    print esc_html(date("j", $thisdate));
    print "</div>\n";
    $sqldate = date("Y-m-d", $thisdate);
    tinyentries($sqldate, 'calendar');
    print "</td>\n";
}   

// Print an overview calendar, that lists the events in a grid.
//
// $startdate, $enddate -- The range of dates to show in the calendar.
// $for -- "palooza" or "cal". Is this calendar for a palooza?
// $preload_alldays -- TRUE if all days are loaded onto the page;
//     FALSE if the days will be dynamically loaded.
function overview_calendar(
    $startdate, $enddate, $for, $preload_alldays) {
?>

  <table class="overview-calendar">
    <thead>
    <tr>
      <th class="weekday-name">Sunday</th>
      <th class="weekday-name">Monday</th>
      <th class="weekday-name">Tuesday</th>
      <th class="weekday-name">Wednesday</th>
      <th class="weekday-name">Thursday</th>
      <th class="weekday-name">Friday</th>
      <th class="weekday-name">Saturday</th>
   </tr>
   </thead>
   <tbody>
    <tr>
    <?php

    // If month doesn't start on Sunday, then skip earlier days
    $weekday = getdate($startdate);
    $weekday = $weekday["wday"];
    if ($weekday !== 0) {
        // Fill the extra space with something.
        $filter_args = array(
            // for: 'palooza' or 'cal' -- is this a palooza or a regular calendar
            'for' => $for,
            
            // Number of table columns used
            'cols' => $weekday,
            
            // Is this appearing before the start of the events, or after the end of the
            // events?
            'location' => 'before',
             );
        $output = apply_filters('bfc-overview-cal-padding', '', $filter_args);
        printf("<td colspan='%d'>", $filter_args['cols']);
        print $output;
        print '</td>';
    }

    // Loop through each day between $startdate and $enddate.
    // We can't just increment $thisdate forward by 86400
    // (seconds per day) because that causes trouble around
    // daylight savings time (but I'm not sure why...).
    // So we we increment $day_of_month.
    $day_of_month = date('d', $startdate);
    do {
        // It's OK to call this with, for example,
        // the 32nd of July; PHP turns that into
        // 1st of August. Also, 32 Dec 2010 becomes
        // 1 Jan 2011.
        $thisdate = mktime(0, 0, 0, //hms
                           date('m', $startdate),
                           $day_of_month,
                           date('Y', $startdate));
        
	// Start new row each week
	if (date("D", $thisdate) == "Sun") {
	    print "</tr><tr>\n";
        }

        overview_calendar_day($thisdate, $preload_alldays);

        $day_of_month++;

        // Check the date 2 ways. Sometimes $thisdate has an
        // h:m:s component, and will be greater than $enddate, even
        // if they're on the same day.
    } while ( ($thisdate <= $enddate) &&
              (date('Y-m-d', $thisdate) != date('Y-m-d', $enddate)) );


    $last_day = date('w', $enddate);
    // If the calendar doesn't end on Saturday
    if ($last_day !== 6) { 
        // Fill the extra space with something.
        // See above for explanation of arguments.
        $filter_args = array(
            'for' => $for,
            'cols' => 6 - $last_day,
            'location' => 'after',
             );

        $output = apply_filters('bfc-overview-cal-padding', '', $filter_args);
        printf("<td colspan='%d'>", $filter_args['cols']);
        print $output;
        print '</td>';
    }
?>
    </tr>
    </tbody>
  </table>

    
<?php    
}

// Generate the HTML for all entries in a given day, in the tiny format
// used in the weekly grid near the top of the page.
//
// For:
// - calendar
// - sidebar
function tinyentries($sqldate, $for, $current_event_wordpress_id = null)
{
    global $calevent_table_name;
    global $caldaily_table_name;
    global $wpdb;

    // Find events that are not exceptions or skipped
    $query = <<<END_QUERY
SELECT *
FROM (
    SELECT ${calevent_table_name}.id, newsflash,title, tinytitle, eventtime,
           eventdate, datestype, audience, eventstatus, descr, review, wordpress_id
    FROM ${calevent_table_name} NATURAL JOIN ${caldaily_table_name}
    WHERE eventdate   =  %s   AND
          eventstatus <> "E"  AND
          eventstatus <> "S"     
) AS find_rides
NATURAL JOIN (
    SELECT id, count(*) AS num_days
    FROM ${caldaily_table_name}
    WHERE eventstatus <> "E" AND
          eventstatus <> "S"
    GROUP BY id
) AS count_all
ORDER BY eventtime ASC, title ASC;
END_QUERY;
    $query = $wpdb->prepare($query, $sqldate);
    $records = $wpdb->get_results($query, ARRAY_A);

    foreach ($records as $record) {
        $id = $record["id"];
        $tinytitle = $record["tinytitle"];
        $title = $record["title"];

        // CSS classes
        $cssclass = "event-overview ";

        if ($record["eventstatus"] == "C") {
            $eventtime = "Canceled";
                $cssclass .= "canceled ";
        }
        else {
            $eventtime = hmmpm($record["eventtime"]);
        }

        if ($record["audience"] == "F") {
            $cssclass .= "family-friendly ";
        }
        elseif ($record["audience"] == "G") {
            $cssclass .= "general-audience ";
        }
        else {
            $cssclass .= "adults-only ";
        }

        if ($record["newsflash"] != "") {
            $cssclass .= "newsflash ";
        }

        // If a fee is mentioned in the description
        $has_fee = strpos($record['descr'], "\$") !== false;

        if ($has_fee) {
            $cssclass .= "fee ";
        }

        printf("<div class='%s'>", esc_attr($cssclass));
        printf("<div class='event-time'>");
        printf("<span class='time'>%s</span>", esc_html($eventtime));
        if ($record['audience'] == 'A') {
            printf(" <span class='audience adult'>%d+</span>", esc_html(get_option('bfc_drinking_age')));
        }
        else if ($record['audience'] == 'F') {
            printf(" <span class='audience family'>FF</span>");
        }

        if ($has_fee) {
            printf(" <span class='fee'>$$</span>");
        }

        print "</div>";

        // Print the title different ways, depending on what we're printing for
        print "<div class='event-title'>";
        if ($current_event_wordpress_id == $record['wordpress_id']) {
            // Don't link to the current event
            printf("<span class='current-event'>%s</span>", esc_html($title));
        }
        else {
            $url = get_permalink($record['wordpress_id']);
            if ($record['num_days'] > 1) {
                $url = add_date_to_permalink($url, $sqldate);
            }

            // Link to the full URL. If JavaScript decides to turn this into
            // a pop-up link, it will use the id & date attributes.
            printf("<a data-id='%d' data-date='%s' href='%s'>%s</a>",
                   esc_attr($id), esc_attr($sqldate),
                   esc_url($url), esc_html($title));
        }
        print "</div>"; // .event-title
        
        printf("</div>");
    }
}

// Print the event listings that go below the calendar.
function event_listings($startdate,
                        $enddate,
                        $preload_alldays,
                        $for_printer,
                        $include_images) {

    $today = strtotime(date("Y-m-d"));
    $tomorrow = $today + 86400;

    for ($thisdate = $startdate;
         $thisdate <= $enddate;
         $thisdate += 86400) {
        
        // Use a fancy graphical devider for screen,
        // a plain HR for printer.
        if (!$for_printer) {
            print "<div class=hr></div>\n";
        }
        else {
            print "<hr>\n";
        }

        print "<h2 class=weeks>";
        print "<a class=\"datehdr\" name=\"".esc_attr(date("Fj",$thisdate))."\">";
        print esc_html(date("l F j", $thisdate));
        print "</a></h2>\n";

        $ymd = date("Y-m-d", $thisdate);
        print "<div id='div${ymd}'>\n";

        // If the events for this day should be loaded
        if ($thisdate == $today ||
                $thisdate == $tomorrow ||
                $for_printer ||
                $preload_alldays) {
            fullentries(date("Y-m-d", $thisdate),
                                TRUE,
                                $for_printer,
                                $include_images);  
        }
        else {
            print "<span class=\"loadday\" ";
                print "onClick=\"loadday('$ymd', true);\">";
                print "Click here to load this day's events";
                print "</span>\n";
        }
        print "</div>\n";
    }
}

function bfc_entry_image($record, $for) {
    $image = '';

    if ($record['image']) {
        // The image field has the path relative to the uploads dir.
        $upload_dirinfo = wp_upload_dir();
        $image = $upload_dirinfo['baseurl'] . $record["image"];
    }
    else if ($for == 'listing') {
        $image = plugins_url('bikefuncal/images/') . "noimg-small.png";
    }

    if ($image != '') {
        printf("<div class=event-image><img src='%s' alt=''></div>\n",
               esc_attr($image));
    }
}

// Generate the HTML entry for a single event:
//
// $record: The SQL record to print
//
// $for is one of:
//   'listing'         -- The event listings on the calendar
//   'preview'         -- The preview when creating/editing an event
//   'event-page'      -- The page for the event
//
// $sqldate: If the event recurs, specify this instance of the event.
//           If unknown, pass in null.
function fullentry($record, $for, $sqldate)
{
    // Check arguments
    if (!in_array($for, Array('listing', 'preview', 'event-page'))) {
        die("Bad entry 'for': $for");
    }

    // extract info from the record
    if ($for != 'preview') {
        $id = $record["id"];
        $permalink_url = get_permalink($record['wordpress_id']);
        if ($record['num_days'] > 1) {
            $permalink_url = add_date_to_permalink($permalink_url, $sqldate);
        }
    }
    else {
        // It's OK to use $id when creating URLs based off of the
        // event ID. In preview mode, all of the link hrefs are
        // replaced by the JavaScript preview code.
        //
        // But, use caution not to use $id for things like database
        // lookups.
        $id = 'PREVIEW';
        $permalink_url = '#';
    }

    printf("<div id='%s'>", $for);
	print "<div class='event-info'>";

    $is_canceled = ($record['eventstatus'] == 'C');
    $cancel_class = $is_canceled ? 'cancel' : '';
    
    ////////////////////////
    // Audience and Fee
    if ($record['audience'] == 'A' || $record['audience'] == 'F' || strpos($record['descr'], "\$") !== false) {
        
		print "<div class=audience>";
		
		if ($record['audience'] == 'A') {
			$badge_url = plugins_url('bikefuncal/images/') . 'adult-icon.png';
            $message = sprintf('Adults Only (%d+)', get_option('bfc_drinking_age'));
			printf("<img src='%s' alt='%s' title='%s'>",
               esc_url($badge_url), esc_attr($message), esc_attr($message));
        }
        else if ($record['audience'] == 'F') {
			$badge_url = plugins_url('bikefuncal/images/') . 'family-icon.png';
            $message = 'Family Friendly';
			printf("<img src='%s' alt='%s' title='%s'>\n",
               esc_url($badge_url), esc_attr($message), esc_attr($message));
        }
    
		if (strpos($record['descr'], "\$") !== false) {
			$badge_url = plugins_url('bikefuncal/images/') . 'money-icon.png';
			$message = 'Bring Money';
			printf("<img src='%s' alt='%s' title='%s'>\n",
               esc_url($badge_url), esc_attr($message), esc_attr($message));
    	}
	
		print "</div>";
	}
	
    //////////////
    // Title
    //
    // Don't show the title on the event page, since WordPress has
    // already put one in.
    if ($for != 'event-page') {
		print '<div class=title>';
        
        if ($is_canceled) {
			printf("<h2>CANCELED: <span class='cancel'>%s</span></h2>",
				   esc_html($record['title']));
		}
		else {
			printf("<h2>%s</h2>", esc_html($record['title']));
		}

        /////////////////////////////////////////////
        // Edit link for listings (shown w/ the title)
        //
        // Show the edit link to admin users in the listing.
        if (current_user_can('bfc_edit_others_events') && $for == 'listing') {
            $edit_url = bfc_get_edit_url_for_event($id, $record['editcode']);
            printf(" <span class='edit-link'><a class='post-edit-link' href='%s'>Edit Event</a></span>",
                   esc_url($edit_url));
        }
	
        print '</div><!-- End title -->';
    }

    //////////////
    // Newsflash
    if ($record['newsflash'] != '') {
        // If the event was canceled, add the cancel class
        printf("<div class='newsflash %s'>%s</div>",
               $cancel_class, esc_html($record['newsflash']));
    }

    //////////////
    // Date & time
    //
    // Requirements:
    // - If the instance is canceled, prefix the date & time with 'Was: '
    // - If there's no end time:
    //   - If the date is known:
    //     - Use the format "[date] at [start]"
    //   - If the date is unknown:
    //     - Use the format "[start]"
    // - If there is an end time:
    //     - If the date is known
    //       - Use the format "[date], [start] - [end]"
    //     - If the date is unknown
    //       - Use the format "[start] - [end]"
    //
    // Use different formats for with/without end time, because they
    // read better.

    $has_end_time = ($record['eventduration'] != 0);

    // This will fail if $sqldate is unset or invalid. (Have to check
    // because it can come from user input.)
    $eventdate = strtotime($sqldate);
    $date = ($eventdate !== false) ? date('l, F j', $eventdate) : null;
    
    if ($date === null) {
        $date_text = '';
    }
    else {
        if ($has_end_time) {
            $date_text = sprintf('%s, ', esc_html($date));
        }
        else {
            $date_text = sprintf('%s at ', esc_html($date));
        }
    }
    
    $time = hmmpm($record['eventtime']);
    if ($has_end_time) {
        $time_text = sprintf("%s - %s", esc_html($time),
              esc_html(endtime($time, $record['eventduration'])));
    }
    else {
        $time_text = esc_html($time);
    }

    print "<div class='time'>";
    if ($is_canceled) {
        print "Was: ";
        print "<span class='cancel'>";
    }
    print $date_text;
    print $time_text;
    if ($is_canceled) {
        print "</span>"; // span.cancel
    }
    print "</div>\n";

    //////////////
    // Recurrence
    if ($record['datestype'] == 'S' || $record['datestype'] == 'C') {
        printf("<div class='time repeat %s'>Repeats: %s</div>",
               $cancel_class, esc_html($record['dates']));
    }
    
    print "<div class='event-details'>";
    
    ///////////
    // Location
    printf("<div class='location'>");
    $address_html = sprintf("<a href=%s>%s</a>",
                            esc_url(address_link($record['address'])),
                            esc_html($record['address']));
    
    print '<h3>Meet At:</h3>';
    if ($record['locname'] != '') {
        // Show both location name and address
        printf("<div class='location-name'><span class='%s'>%s</span></div>",
               $cancel_class, esc_html($record['locname']));
        printf("<div class='location-address %s'>%s</div>",
               $cancel_class, $address_html);
    }
    else {
        // Show address in place of locname
        printf("<div class='location-name'><span class='%s'>%s</span></div>",
               $cancel_class, $address_html);
    }

    if ($record['locdetails'] != '') {
        printf("<div class='location-details %s'>Notes: %s</div>",
               $cancel_class, 
               esc_html($record['locdetails']));
    }

    printf("</div>"); // class='location'

    /////////////////////////////
    // Contact info
    printf("<div class='contact-info'>");
    print '<h3>Contact Info:</h3>';
    if ($record['email'] != '') {
        // To foil spam harvesters, disassemble the email address. Some JavaScript will put it back
        // together again.
        
        $parts = explode('@', $record['email'], 2);
        // data-aa = after at sign
        // data-ba = before at sign
        printf("<div class=leader-email><a class='scrambled-email' data-aa='%s' href='#' data-ba='%s'>e-mail</a></div>",
               str_rot13($parts[1]), str_rot13($parts[0]));
    }
    if ($record['weburl'] != '') {
        // Create a shortened version of the URL for display
        $url_parts = parse_url($record['weburl']);
        $display_url = $url_parts['host'];

        // Set to true if we put ellipsis on the end of $display_url.
        $has_ellipsis = false;

        // Append the path to $display_url
        if (isset($url_parts['path'])) {
            $path = $url_parts['path'];

            // If path is too long, shorten w/ ellipsis
            if (strlen($path) > 15) {
                // Trim to 13 chars to avoid trimming just one char off the end.
                $path = substr($path, 0, 13);
                // Don't use &hellip; here (the horizointal ellipsis).
                // The browser puts in a line break, and the URL looks weird
                $path .= '...';
                $has_ellipsis = true;
            }

            $display_url .= $path;
        }
        
        // If there's a query or fragment on the URL, add ellipsis (unless
        // there are already ellipsis).
        if (!$has_ellipsis &&
            (isset($url_parts['query']) || isset($url_parts['fragment']))) {

            $display_url .= '...';
            $has_ellipsis = true;
        }

        printf("<div class='leader-website'><a target='_blank' href='%s'>%s</a></div>",
               esc_url($record['weburl']), esc_html($display_url));
    }
    if ($record['phone'] != '') {
        printf("<div class='leader-phone'>%s</div>", esc_html($record['phone']));
    }
    if ($record['contact'] != '') {
        // Other contact info
        printf("<div class='leader-other-contact'>%s</div>", esc_html($record['contact']));
    }
    printf("</div>"); // class='contact-info'
    
    print "</div><!-- End event-details -->";
    
    print "<div class='event-about'>";


    ///////////////////////
    // Image (listing only)
    if ($for == 'listing') {
        bfc_entry_image($record, $for);
    }

    ////////////////////
    // Ride leader, event description and permalink
    $descr = htmldescription($record['descr']);
    if ($for == 'listing') {
        $more = sprintf(" <a href='%s'>[more...]</a>",
                        esc_url($permalink_url));
        $descr = bfc_excerpt($descr, $more);
    }
    printf("<div class='event-description %s'>",
           $cancel_class);
		   
	printf("<div class='leader-name'>By: %s</div>", esc_html($record['name']));
		   
	printf("%s",
           $descr);
		   
    if ($for != 'event-page') {
        print "<div class='permalink'>";
        printf("<a href='%s'>Read comments and more</a>", esc_url($permalink_url));
        print "</div>\n";
    }
	
	print "</div><!-- End .event-description -->";
	
    //////////////////////////
    // Image (event page only)
    if ($for == 'event-page') {
        bfc_entry_image($record, $for);
    }


	print "</div><!-- End event-about -->";
	
    //////////////////////////
    // Edit link for event-page
    //
    // Show the edit link to admin users on the event page.
    // (But not the preview; then it's meaningless
    // because they're already editing.)
    if (current_user_can('bfc_edit_others_events') && $for == 'event-page') {
        $edit_url = bfc_get_edit_url_for_event($id, $record['editcode']);
        printf("<span class='edit-link'><a class='post-edit-link' href='%s'>Edit Event</a></span>",
               esc_url($edit_url));
    }

    /////////////////////////
    // Next & Previous events
    if ($for != 'preview') {
        global $wpdb;
        global $calevent_table_name;
        global $caldaily_table_name;
        
        $prev_sql = <<<END_SQL
            -- Find the previous event. Also, count the number of times
            -- that event occurs.

            SELECT *
            FROM
            (
                SELECT id, title, eventdate, eventtime, wordpress_id
                FROM ${calevent_table_name} NATURAL JOIN ${caldaily_table_name}
                WHERE eventstatus <> "E" AND
                      eventstatus <> "S" AND
                     (
                      eventdate < %s OR
                     (eventdate = %s AND eventtime < %s) OR
                     (eventdate = %s AND eventtime = %s AND title < %s)
                     )
                ORDER BY eventdate DESC, eventtime DESC, title DESC
                LIMIT 1
            ) AS find_previous
            NATURAL JOIN
            (
                SELECT id, count(*) AS num_days
                FROM w_bfc_calevent NATURAL JOIN w_bfc_caldaily
                WHERE eventstatus <> "E" AND
                      eventstatus <> "S"
                GROUP BY id
            ) AS count_all;

END_SQL;
$next_sql = <<<END_SQL
            -- Find the next event

            SELECT *
            FROM
            (
                SELECT id, title, eventdate, eventtime, wordpress_id
                FROM w_bfc_calevent NATURAL JOIN w_bfc_caldaily
                WHERE eventstatus <> "E" AND
                      eventstatus <> "S" AND
                     (
                      eventdate > %s OR
                     (eventdate = %s AND eventtime > %s) OR
                     (eventdate = %s AND eventtime = %s AND title > %s)
                     )
                ORDER BY eventdate ASC, eventtime ASC, title ASC
                LIMIT 1
            ) AS find_next
            NATURAL JOIN
            (
                SELECT id, count(*) AS num_days
                FROM w_bfc_calevent NATURAL JOIN w_bfc_caldaily
                WHERE eventstatus <> "E" AND
                      eventstatus <> "S"
                GROUP BY id
            ) AS count_all;
END_SQL;

        $prev_sql = $wpdb->prepare($prev_sql,
                                   $record['eventdate'],
                                   $record['eventdate'], $record['eventtime'],
                                   $record['eventdate'], $record['eventtime'], $record['title']);
        $prev_results = $wpdb->get_results($prev_sql, ARRAY_A);
        $next_sql = $wpdb->prepare($next_sql,
                                   $record['eventdate'],
                                   $record['eventdate'], $record['eventtime'],
                                   $record['eventdate'], $record['eventtime'], $record['title']);
        $next_results = $wpdb->get_results($next_sql, ARRAY_A);
 
        if(isset($prev_results[0]) || isset($next_results[0])) {
			
			print "<div class='event-navigation'>"; 
		
			if (isset($prev_results[0])) {
				$prev_record = $prev_results[0];
				$prev_url = get_permalink($prev_record['wordpress_id']);
                if ($prev_record['num_days'] > 1) {
                    $prev_date = date('Y-m-d', strtotime($prev_record['eventdate']));
                    $prev_url = add_date_to_permalink($prev_url, $prev_date);
                }
	
				if ($record['eventdate'] == $prev_record['eventdate']) {
					// On the same day, show the time
					$when = hmmpm($prev_record['eventtime']);
				}
				else {
					// On another day, show the weekday (e.g., Thursday)
					$when = date('l', strtotime($prev_record['eventdate']));
				}
				
				print "<div class='previous'>";
				printf("<div><a data-id='%d' data-date='%s' href='%s'>&lt; Previous</a></div>",
					   esc_attr($prev_record['id']), esc_attr($prev_record['eventdate']),
					   esc_attr($prev_url));
				printf("<p>%s</p><p>%s</p>", esc_html($when), esc_html($prev_record['title']));
				printf("</div>");
			}
	
			if (isset($next_results[0])) {
				$next_record = $next_results[0];
				$next_url = get_permalink($next_record['wordpress_id']);
                if ($next_record['num_days'] > 1) {
                    $next_date = date('Y-m-d', strtotime($next_record['eventdate']));
                    $next_url = add_date_to_permalink($next_url, $next_date);
                }
	
				if ($record['eventdate'] == $next_record['eventdate']) {
					// On the same day, show the time
					$when = hmmpm($next_record['eventtime']);
				}
				else {
					// On another day, show the weekday (e.g., Thursday)
					$when = date('l', strtotime($next_record['eventdate']));
				}
	
				print "<div class='next'>";
				printf("<div><a data-id='%d' data-date='%s' href='%s'>Next &gt;</a></div>",
					   esc_attr($next_record['id']), esc_attr($next_record['eventdate']),
					   esc_attr($next_url));
				printf("<p>%s</p><p>%s</p>", esc_html($when), esc_html($next_record['title']));
				printf("</div>");
			}
			
			print "</div>";
			print "</div>";
		}
    }        
}

// Generate the HTML for all entries in a given day, in the full format
// used in the lower part of the page.
function fullentries($day, $exclude = FALSE)
{
    global $calevent_table_name;
    global $caldaily_table_name;
    global $wpdb;
    
    global $imageover;

    // The day separator line is about 20 pixels high.  We can
    // reduce $imageover by that much.
    $imageover -= 20;

    // for each event on this day...
    
    // Find events that are not exceptions or skipped.
    $query = <<<END_QUERY
SELECT *
FROM (
    SELECT *
    FROM ${calevent_table_name} NATURAL JOIN ${caldaily_table_name}
    WHERE eventdate = %s AND
          eventstatus <> "E" AND
          eventstatus <> "S"
) AS find_rides
NATURAL JOIN (
    SELECT id, count(*) AS num_days
    FROM ${caldaily_table_name}
    WHERE eventstatus <> "E" AND
          eventstatus <> "S"
    GROUP BY id
) AS count_all
ORDER BY eventtime ASC, title ASC;

END_QUERY;
    $query = $wpdb->prepare($query, $day);
    $records = $wpdb->get_results($query, ARRAY_A);
    $num_records = count($records);
    
    if ($num_records > 0) {
	print ("<dl>\n");

        foreach ($records as $record) {
            if (!$exclude || $record["review"] != "E") {
                fullentry($record, 'listing', $record['eventdate']);
            }
        }

	print ("</dl>\n");
    }
}

/**
 * Get a URL for editing an event, based upon the wordpress_id for that
 * event.
 */
function bfc_get_edit_url_for_wordpress_id($wordpress_id) {
    global $wpdb;
    global $calevent_table_name;
    $sql = $wpdb->prepare("SELECT id, editcode FROM ${calevent_table_name} " .
                          "WHERE wordpress_id=%d", $wordpress_id);

    $records = $wpdb->get_results($sql, ARRAY_A);
    if ($wpdb->num_rows != 1) {
        die();
    }
    return bfc_get_edit_url_for_event($records[0]['id'], $records[0]['editcode']);
}

/**
 * Look up an event's editcode from the datbase.
 */
function bfc_get_editcode_for_event($id) {
    global $wpdb;
    global $calevent_table_name;
    $sql = $wpdb->prepare("SELECT editcode FROM ${calevent_table_name} " .
                          "WHERE id=%d",
                          $id);

    $records = $wpdb->get_results($sql, ARRAY_A);
    if ($wpdb->num_rows != 1) {
        die();
    }
    return $records[0]['editcode'];
}

/**
 * Get a URL for editing an event, based upon the id for that
 * event.
 */
function bfc_get_edit_url_for_event($id, $editcode = null) {
    if (!isset($id)) {
        die("bfc_get_edit_url_for_event: id is unset");
    }

    // No editcode provided; look it up in the database
    if ($editcode === null) {
        $editcode = bfc_get_editcode_for_event($id);
    }

    global $wp_rewrite;
    if ($wp_rewrite->using_permalinks()) {
        // If permalinks are on, use pretty URL
        return site_url(sprintf('edit/%d/%s', $id, $editcode));
    }
    else {
        $edit_page = get_page_by_path('add-event');
        $base_url = get_permalink($edit_page->ID); 

        return $base_url .
            "&submission_event_id=${id}" .
            "&submission_action=edit&" .
            "event_editcode=${editcode}";
    }
}

// This is called by the event submission form to preview the
// event listing.
function bfc_preview_event_submission() {
    // This sends a plain-text response, so no
    // header is needed.

    // Make a record out of the items in the post.
    // Remove the prefix "event_" from the names
    $record = array();
    foreach ($_POST as $query_name => $query_value) {
        if (substr($query_name, 0, 6) == "event_") {
            $arg_name = substr($query_name, 6);
            
            $record[$arg_name] = stripslashes($query_value);
        }
    }

    // These fields are not passed in, because
    // they go along with caldaily (not calevent) and we
    // haven't yet worked out how the preview works with
    // repeating events.
    $record['eventdate'] = '';
    $record['newsflash'] = '';
    $record["eventstatus"] = "A";
    $record["datestype"] = "O"; // one-time
    $record['num_days'] = 1;

    // Keep the code from barfing because wordpress_id
    // is undefined. It also supresses the link to the
    // forum, which is OK in the preview.
    $record["wordpress_id"] = 0;

    fullentry($record, 'preview', null);
    exit;
}

// Add this to WordPress' registry of AJAX actions.
add_action('wp_ajax_nopriv_preview-event-submission',
           'bfc_preview_event_submission');
add_action('wp_ajax_preview-event-submission',
           'bfc_preview_event_submission');


function bfc_event_popup() {
    // This sends a plain-text response, so no
    // header is needed.
    global $calevent_table_name;
    global $caldaily_table_name;
    global $wpdb;

    $id = $_POST['id'];
    $sqldate = $_POST['date'];

    $query = <<<END_QUERY
SELECT *
FROM (
    SELECT *
    FROM ${calevent_table_name} NATURAL JOIN ${caldaily_table_name}
    WHERE ${calevent_table_name}.id = %d AND
          eventdate = %s AND
          eventstatus <> "E" AND
          eventstatus <> "S"
) AS find_rides
NATURAL JOIN (
    SELECT id, count(*) AS num_days
    FROM ${caldaily_table_name}
    WHERE eventstatus <> "E" AND
          eventstatus <> "S"
    GROUP BY id
) AS count_all
ORDER BY eventtime ASC, title ASC
END_QUERY;
    $query = $wpdb->prepare($query, $id, $sqldate);
    $records = $wpdb->get_results($query, ARRAY_A);
    if ($wpdb->num_rows != 1) {
        die();
    }
    $record = $records[0];

    fullentry($record, 'listing', $sqldate);
           
    exit;
}

// Add this to WordPress' registry of AJAX actions.
add_action('wp_ajax_nopriv_event-popup',
           'bfc_event_popup');
add_action('wp_ajax_event-popup',
           'bfc_event_popup');

// Make a widget to show other events that happen on the same day as the current event.           
class BFC_OtherEvents_Widget extends WP_Widget {
    /** constructor */
    function BFC_OtherEvents_Widget() {
        parent::WP_Widget(false, 'Bike Fun Cal: Other Events');
    }

    /** @see WP_Widget::widget */
    function widget( $args, $instance ) {
        global $wp_query;
        if (get_post_type() == 'bfc-event') {
            $sqldate = null;
            if (isset($wp_query->query_vars['date'])) {
                $sqldate = $wp_query->query_vars['date'];
            }
            else {
                // Date wasn't passed in as part of the query,
                // have to look it up in the database
                global $wpdb;
                global $calevent_table_name;
                global $caldaily_table_name;
                $sql = <<<END_SQL
                    SELECT *
                    FROM ${calevent_table_name} NATURAL JOIN ${caldaily_table_name}
                    WHERE wordpress_id = %d AND
                          eventstatus <> "E" AND
                          eventstatus <> "S";
END_SQL;
                $sql = $wpdb->prepare($sql, $wp_query->post->ID);
                $records = $wpdb->get_results($sql, ARRAY_A);
                if (isset($records[0])) {
                    $record = $records[0];
                    // If this is a one-time event, use its date.
                    // Otherwise, it's a recurring event and we don't
                    // know which instance we're looking for.
                    if ($record['datestype'] == 'O') {
                        $sqldate = $record['eventdate'];
                    }
                }
            }

            if ($sqldate != null) {
                print "<aside class='widget other-events'>\n";
                printf("<h3 class='widget-title'>Other events on %s</h3>",
                       esc_html(date('l, F j', strtotime($sqldate))));
                tinyentries($sqldate, 'sidebar', $wp_query->post->ID);
                print "</aside>\n";
            }
        }
    }
}
function bfc_register_other_events_widget() {
    register_widget('BFC_OtherEvents_Widget');
}
add_action('widgets_init', 'bfc_register_other_events_widget');

//ex:set sw=4 embedlimit=60000:
?>
