<?php

// This file comes from the old, pre-wordpress code (where it was named view.php).
//
// It prints out events -- the overviews and the individual listings

// Image height limits for the online calendar
define("RIGHTHEIGHT", 200);
define("LEFTHEIGHT", 125);

// Preload all days if no alldays cookie is set and number of events is under this threshold
define("PRELOAD", 100);		

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
    tinyentries($sqldate, TRUE, $preload_alldays );
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
function tinyentries($day, $exclude = FALSE, $loadday = FALSE)
{
    global $calevent_table_name;
    global $caldaily_table_name;
    global $wpdb;
    
    $dayofmonth = substr($day, -2);

    // Find events that are not exceptions or skipped
    $query = <<<END_QUERY
SELECT ${calevent_table_name}.id, newsflash,title, tinytitle, eventtime,
       audience, eventstatus, descr, review
FROM ${calevent_table_name}, ${caldaily_table_name}
WHERE ${calevent_table_name}.id=${caldaily_table_name}.id AND
      eventdate   =  %s   AND
      eventstatus <> "E"  AND
      eventstatus <> "S"     
ORDER BY eventtime

END_QUERY;
    $query = $wpdb->prepare($query, $day);
    $records = $wpdb->get_results($query, ARRAY_A);

    foreach ($records as $record) {
	if ($exclude && $record["review"] == "E") {
	    continue;
        }
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
        //printf("<div class='event-time'>%s</div>", esc_html($eventtime));
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
        
        printf("<div class='event-title'><a data-id='%d' data-date='%s' href='#'>%s</a></div>", esc_attr($id), esc_attr($day), esc_html($title));
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

// Generate the HTML entry for a single event
//
// $for is one of:
//   'listing'         -- The event listings on the calendar
//   'printer'         -- The event listings on a printer
//   'preview'         -- The preview when creating/editing an event
//   'event-page'      -- The page for the event
//
// $include_images -- TRUE to include images, FALSE to leave them out
function fullentry($record, $for, $include_images)
{
    // Check arguments
    if (!in_array($for, Array('listing', 'printer', 'preview', 'event-page'))) {
        die("Bad entry 'for': $for");
    }

    global $imageover;

    // 24 hours ago.  We compare timestamps to this in order to
    // detect recently changed entries.
    $yesterday = date("Y-m-d H:i:s", strtotime("yesterday"));

    // extract info from the record
    if ($for != 'preview') {
        $id = $record["id"];
    }
    else {
        // It's OK to use $id when creating URLs based off of the
        // event ID. In preview mode, all of the link hrefs are
        // replaced by the JavaScript preview code.
        //
        // But, use caution not to use $id for things like database
        // lookups.
        $id = 'PREVIEW';
    }
    $wordpress_id = $record["wordpress_id"];
    $title = htmlspecialchars(strtoupper($record["title"]));
    if ($record["eventstatus"] == "C") {
	$eventtime = "CANCELED";
	$eventduration = 0;
    } else {
	$eventtime = hmmpm($record["eventtime"]);
	$eventduration = $record["eventduration"];
    }
    
    $dayofmonth = substr($record["eventdate"], -2);
    $timedetails = $record["timedetails"];
    
    if ($record["audience"] == "F") {
	$badge = "ff.gif";
	$badgealt = "FF";
	$badgehint = "Family Friendly";
    }
    if ($record["audience"] == "G") {
	$badge = "";
	$badgealt = "";
	$badgehint = "";
    }
    if ($record["audience"] == "A") {
	$badge = "beer.gif";
	$badgealt = sprintf('%d+', get_option('bfc_drinking_age'));
        $badgehint = sprintf('Adult Only (%d+)', get_option('bfc_drinking_age'));
    }
    
    $address = $record["address"];
    if ($record["locname"]) {
	$address = $record["locname"] . ' , ' . $address;
    }
    $locdetails = $record["locdetails"];
    $descr = $record["descr"];
    $newsflash = $record["newsflash"];
    $name = ucwords($record["name"]);
    $email = $record["hideemail"] ? "" : $record["email"];
    $phone = $record["hidephone"] ? "" : $record["phone"];
    $contact = $record["hidecontact"] ? "" : $record["contact"];
    $weburl = $record["weburl"];
    $webname = $record["webname"];
    if ($webname == "" || $for == 'printer') {
        // If they left out the name for their web site, or if
        // this is being shown for printing, show the URL insetad of the
        // site name.
	$webname = $weburl;
    }

    // get the image info
    $image = "";
    if ($include_images && $for != 'preview' && $record["image"]) {
        // The image field has the path relative to the uploads dir.
        $upload_dirinfo = wp_upload_dir();
        $image = $upload_dirinfo['baseurl'] . $record["image"];
        
	$imageheight = $record["imageheight"];
	$imagewidth = $record["imagewidth"];
    }
    
    if ($eventtime == "CANCELED") {
	$class = "canceled";
    }
    else {
        $class = "";
    }
    
    print "<dt class=\"${class}\">";

    //////////////////////////////////////////
    // Image (if right-aligned)
    //
    if ($image && $imageover <= 0 && $imageheight > RIGHTHEIGHT / 2) {
        // Put the image's width & height in bounds
	if ($imageheight > RIGHTHEIGHT) {
	    $imagewidth = $imagewidth * RIGHTHEIGHT / $imageheight;
	    $imageheight = RIGHTHEIGHT;
	}
        
	print "\n";
        printf("<img src='%s' height='%d' width='%d' align='right' alt='' class='ride-image'>\n",
               esc_attr($image), $imageheight, $imagewidth);
    }
    
    // Don't show title & permalink on the
    // event page.
    if ($for != 'event-page') {
        //////////////////////////////////////////
        // Title
        //
        printf("<a name='%s' " .
            "class='eventhdr %s'>", esc_attr($dayofmonth . '-' . $id),
            esc_attr($class));
        print esc_html($title);
        print "</a>\n";

        //////////////////////////////////////////
        // Permalink
        //
        if ($for == 'preview') {
            $permalink = '#';
        }
        else {
            $permalink = get_permalink($record['wordpress_id']);
        }
        printf("<a href='%s'> \n", esc_url($permalink));
        $chain_url = esc_url(plugins_url('bikefuncal/images/chain.gif'));
        print "<img border=0 src=\"${chain_url}\" " .
            "alt=\"Link\" title=\"Link to this event\">\n";
        print "</a>\n";
    }

    //////////////////////////////////////////
    // Audience badge
    //
    if ($badge != "") {
        $badgeurl = plugins_url('bikefuncal/images/') . $badge;
        printf("<img align=left src='%s' alt='%s' title='%s'>\n",
               esc_url($badgeurl), esc_attr($badgealt), esc_attr($badgehint));
    }

    print "</dt>\n";
    print "<dd>";

    //////////////////////////////////////////
    // Image (if left-aligned)
    //
    if ($image && ($imageover > 0 || $imageheight <= RIGHTHEIGHT / 2)) {
        // Put the image's width & height in bounds
	if ($imageheight > LEFTHEIGHT) {
	    $imagewidth = $imagewidth * LEFTHEIGHT / $imageheight;
	    $imageheight = LEFTHEIGHT;
	}
        
        printf("<img src='%s' height='%d' width='%d' align='left' alt='' class='ride-image'>\n",
               esc_attr($image), $imageheight, $imagewidth);

    }

    //////////////////////////////////////////
    // Location
    //
    // (This div contains the location)
    printf("<div class='%s'>", esc_attr($class));

    // Street address
    $address_url = address_link($record['address']);
    printf("<a href='%s' target=\"_BLANK\">%s</a>",
           esc_url($address_url), esc_html($address));
    
    // Location details
    if ($locdetails != "") {
        printf(" (%s)", esc_attr($locdetails));
    }
    print "</div>\n";

    
    //////////////////////////////////////////
    // Time
    //
    print "<div>";
    print esc_html($eventtime);
    if ($eventtime == "CANCELED" && $newsflash != "") {
	printf(" <span class=newsflash>%s</span>", esc_html($newsflash));
    }
    if ($eventtime != "CANCELED") {
        // Print end time
	if ($eventduration != 0) {
	    print " - ";
            print esc_html(endtime($eventtime,$eventduration));
        }
        
	if ($timedetails != "") {
            print ", ";
            print esc_html($timedetails);
        }

        // Print the dates (e.g., "every Tuesday") for repeating
        // events.
	if ($record["datestype"] == "C" || $record["datestype"] == "S") {
	    print ", ";
            print esc_html($record['dates']);
        }
    }
    print "</div>";

    //////////////////////////////////////////
    // Description
    //
    printf("<div class='%s'>\n", esc_attr($class));
    printf("<em>%s</em>\n", htmldescription($descr));
    if ($newsflash != "" && $eventtime != "CANCELED") {
	printf("<span class=newsflash>%s</span>", esc_html($newsflash));
    }

    //////////////////////////////////////////
    // Contact info
    //
    print "<div class='contact-info'>\n";
    print esc_html($name);
    if (!strpbrk(substr(trim($name),strlen(trim($name))-1),".,:;-")) {
        print ",";
    }
    if ($email != "") {
        echo " ", mangleemail(esc_html($email));
    }
 
    if ($weburl != "") {
        printf(', <a href="%s">%s</a>', esc_url($weburl), esc_html($webname));
    }
    if ($contact != "") {
        print ", ";
        print mangleemail(esc_html($contact));
    }
    if ($phone != "") {
        echo ", ", esc_html($phone);
    }
    print "</div>\n";

    //////////////////////////////////////////
    // Forum link
    //
    if ($for != 'printer' && $for != 'event-page' && $wordpress_id > 0) {
        $comment_counts = wp_count_comments($wordpress_id);

        $forumimg = plugins_url("bikefuncal/images/forum.gif");
        $forumtitle =
            $comment_counts->approved .
            " message" .
            ($comment_counts->approved == 1 ? "" : "s");
        $forumurl   = htmlspecialchars(get_permalink($wordpress_id), ENT_QUOTES);

        // @@@ If there's been recent activity in the forum,
        // show forumflash.gif instead. (The old code did this,
        // but it's not ported to WP yet.)
        
        printf("<a href='%s' title='%s'>", esc_url($forumurl), esc_attr($forumtitle));
        printf("<img border=0 src='%s' alt='forum'>", esc_url($forumimg));
        print "</a>\n";
    }

    //////////////////////////////////////////
    // Edit link
    //
    // Show the edit link to admin users.
    // Except if this is a preview; then it's meaningless
    // because they're already editing.
    if (current_user_can('bfc_edit_others_events') && $for != 'preview') {
        $edit_url = bfc_get_edit_url_for_event($id, $record['editcode']);
        printf("<a href='%s'>Edit Event</a>", esc_url($edit_url));
    }

    print "</dd>\n";

    // if this event has no image, then the next event's
    // image can be left-aligned.
    if ($image == "" || $imageover > 0 || $imageheight <= RIGHTHEIGHT / 2) {
	$imageover = 0;
    }
    else {
	$imageover = $imageheight - RIGHTHEIGHT / 2;
    }
}

// Generate the HTML for all entries in a given day, in the full format
// used in the lower part of the page.
function fullentries($day,
                         $exclude = FALSE,
                         $for_printer = FALSE,
                         $include_images = TRUE)
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
FROM ${calevent_table_name}, ${caldaily_table_name}
WHERE ${calevent_table_name}.id = ${caldaily_table_name}.id AND
      eventdate = %s AND
      eventstatus <> "E" AND
      eventstatus <> "S"
ORDER BY eventtime
END_QUERY;
    $query = $wpdb->prepare($query, $day);
    $records = $wpdb->get_results($query, ARRAY_A);
    $num_records = count($records);
    
    if ($num_records > 0) {
	print ("<dl>\n");

        foreach ($records as $record) {
            if (!$exclude || $record["review"] != "E") {
                $for = $for_printer ? 'printer' : 'listing';
                fullentry($record,
                          $for,
                          $include_images);
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
        // @@@ Could also look these up by slug (with
        // get_page_by_path()), to give more flexibility
        // in the page title.
        $edit_page_title = 'New Event';
        $edit_page = get_page_by_title($edit_page_title);
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

    // Keep the code from barfing because wordpress_id
    // is undefined. It also supresses the link to the
    // forum, which is OK in the preview.
    $record["wordpress_id"] = 0;

    fullentry($record,
              'preview',
              FALSE); // include images,
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
FROM ${calevent_table_name}, ${caldaily_table_name}
WHERE ${calevent_table_name}.id = ${caldaily_table_name}.id AND
      ${calevent_table_name}.id = %d AND
      eventdate = %s AND
      eventstatus <> "E" AND
      eventstatus <> "S"
ORDER BY eventtime
END_QUERY;
    $query = $wpdb->prepare($query, $id, $sqldate);
    $records = $wpdb->get_results($query, ARRAY_A);
    if ($wpdb->num_rows != 1) {
        die();
    }
    $record = $records[0];

    print "<div class='event-info'>";

    $is_canceled = ($record['eventstatus'] == 'C');
    $cancel_class = $is_canceled ? 'cancel' : '';

    ///////////
    // Audience
    if ($record['audience'] == 'A' || $record['audience'] == 'F') {
        if ($record['audience'] == 'A') {
            $badge = 'beer.gif';
            $message = sprintf('Adults Only (%d+)', get_option('bfc_drinking_age'));
        }
        else if ($record['audience'] == 'F') {
            $badge = "ff.gif";
            $message = 'Family Friendly';
        }
        $badge_url = plugins_url('bikefuncal/images/') . $badge;
        printf("<div class=audience><img src='%s' alt='%s' title='%s'></div>\n",
               esc_url($badge_url), esc_attr($message), esc_attr($message));
    }

    //////////////
    // Title
    if ($is_canceled) {
        printf("<div class='title'>CANCELED: <span class='cancel'>%s</span></div>",
               esc_html($record['title']));
    }
    else {
        printf("<div class='title'>%s</div>", esc_html($record['title']));
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
    // - If there's no end time, use the format "[date] at [start]"
    // - If there is an end time, use the format "[date], [start] - [end]"
    //   (Use these formats because they read better)
    // 
    $date = date('l, F j', strtotime($sqldate));
    $time = hmmpm($record['eventtime']);
    print "<div class='time'>";
    if ($is_canceled) {
        print "Was: ";
        print "<span class='cancel'>";
    }
    if ($record['eventduration'] != 0) {
        // Format: [date], [start] - [end]
        printf("%s, %s - %s", esc_html($date), esc_html($time),
              esc_html(endtime($record['eventtime'], $record['eventduration'])));
    }
    else {
        printf("%s at %s", esc_html($date), esc_html($time));
    }                   
    if ($is_canceled) {
        print "</span>";
    }
    print "</div>\n";

    //////////////
    // Recurrence
    if ($record['datestype'] == 'S' || $record['datestype'] == 'C') {
        printf("<div class='time repeat %s'>Repeats: %s</div>",
               $cancel_class, esc_html($record['dates']));
    }

    /////////////////////////////
    // Ride leader & contact info
    printf("<div class='contact-info'>");
    printf("<div class='leader-name'>By: %s</div>", esc_html($record['name']));
    if ($record['email'] != '') {
        // To foil spam harvesters, disassemble the email address. Some JavaScript will put it back
        // together again.
        
        $parts = explode('@', $record['email'], 2);
        // data-aa = after at sign
        // data-ba = before at sign
        printf("<div class=leader-email><a data-aa='%s' href='#' data-ba='%s'>e-mail</a></div>",
               str_rot13($parts[1]), str_rot13($parts[0]));
    }
    if ($record['weburl'] != '') {
        $webname = $record['webname'] != '' ? $record['webname'] : $record['weburl'];
        printf("<div class='leader-website'><a href='%s'>%s</a></div>",
               esc_url($record['weburl']), esc_html($webname));
    }
    if ($record['phone'] != '') {
        printf("<div class='leader-phone'>%s</div>", esc_html($record['phone']));
    }
    if ($record['contact'] != '') {
        // Other contact info
        printf("<div class='leader-other-contact'>%s</div>", esc_html($record['contact']));
    }
    printf("</div>"); // class='contact-info'
    
    ///////////
    // Location
    printf("<div class='location'>");
    $address_html = sprintf("<a href=%s>%s</a>",
                            esc_url(address_link($record['address'])),
                            esc_html($record['address']));
    
    if ($record['locname'] != '') {
        // Show both location name and address
        printf("<div class='location-name'>At: <span class='%s'>%s</span></div>",
               $cancel_class, esc_html($record['locname']));
        printf("<div class='location-address %s'>%s</div>",
               $cancel_class, $address_html);
    }
    else {
        // Show address in place of locname
        printf("<div class='location-name'>At: <span class='%s'>%s</span></div>",
               $cancel_class, $address_html);
    }

    if ($record['locdetails'] != '') {
        printf("<div class='location-details %s'>%s</div>",
               $cancel_class, 
               esc_html($record['locdetails']));
    }

    printf("</div>"); // class='location'

    ///////////
    // Image
    if ($record['image']) {
        // The image field has the path relative to the uploads dir.
        $upload_dirinfo = wp_upload_dir();
        $image = $upload_dirinfo['baseurl'] . $record["image"];
        
        printf("<div class=event-image><img src='%s' alt=''></div>\n",
               esc_attr($image));
    }

    ////////////////////
    // Event description
    printf("<div class='event-description %s'>%s</div>\n",
           $cancel_class,
           htmldescription($record['descr']));

    ////////////
    // Permalink
    $permalink_url = get_permalink($record['wordpress_id']);
    print "<div class='permalink'>";
    printf("<a href='%s'>Permalink & Comments</a>", esc_url($permalink_url));
    print "</div>\n";
           
    exit;
}

// Add this to WordPress' registry of AJAX actions.
add_action('wp_ajax_nopriv_event-popup',
           'bfc_event_popup');
add_action('wp_ajax_event-popup',
           'bfc_event_popup');

//ex:set sw=4 embedlimit=60000:
?>
