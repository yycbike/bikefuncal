<?php
/* Export a calendar in InDesign Tagged Text
 *
 */
add_action('template_redirect', 'bfc_template_redirect');
function bfc_template_redirect() {
    if ($_SERVER['REQUEST_URI']==='/calendar.txt') {
        //header('Content-type: text/plain', true, 200);
        header('Content-type: application/force-download', true, 200);
        header('Pragma: no-cache');
        header('Expires: 0');

        bfc_print_indesign_calendar();

        exit();
    }
}

function bfc_print_with_parastyle($style, $text) {
    printf('<ParaStyle:%s>', $style);
    print $text;
    print "\r\n";
}

function bfc_print_indesign_calendar() {
    global $calevent_table_name;
    global $caldaily_for_listings_table_name;
    global $caldaily_num_days_for_listings_table_name;
    global $wpdb;

    $query = <<<END_SQL
        SELECT *
        FROM ${calevent_table_name} JOIN ${caldaily_for_listings_table_name} USING(id)
        WHERE eventdate >= %s AND eventdate <= %s
        ORDER BY
            eventdate ASC,
            eventtime ASC,
            title ASC
END_SQL;

    // Important! Because this is declared as ASCII-WIN,
    // InDesign will expect us to use \r\n for all newlines.
    print "<ASCII-WIN>\r\n";

    $query = $wpdb->prepare($query,
        get_option('bfc_festival_start_date'),
        get_option('bfc_festival_end_date'));
    $records = $wpdb->get_results($query, ARRAY_A);
    $today = null;
    foreach ($records as $record) {
        // Skip everything excepte As-Scheduled
        if ($record['eventstatus'] !== 'A') {
            continue;
        }

        if ($today !== $record['eventdate']) {
            $today = $record['eventdate'];
            bfc_print_with_parastyle('Date', date('l, F j', strtotime($today)));
        }

        bfc_print_with_parastyle('Title', $record['title']);

        $location  = $record['locname'] !== ''    ? $record['locname']    . '. ' : '';
        $location .= $record['address'] !== ''    ? $record['address']    . '. ' : '';
        $location .= $record['locdetails'] !== '' ? $record['locdetails'] . '. ' : '';
        bfc_print_with_parastyle('Location', $location);

        $time  = hmmpm($record['eventtime']);
        $time .= $record['timedetails'] !== '' ? '. ' . $record['timedetails'] : '';
        bfc_print_with_parastyle('Time', $time);
    }
        
}




/* Display the page for exporting the calendar */
function bfc_export_admin_page() {
?>
<div class='wrap'>
<h2>Export Calendar to InDesign</h2>
<p>
You can export the <?php echo esc_html(get_option('bfc_festival_name')); ?> calendar to InDesign,
to use as a starting point for making a print calendar. To do this, follow these steps:
</p>

<ol>
  <li>Set up paragraph styles in your InDesign document. You need styles with these names
      (capitalization matters): Date, Title, Location, Time. The styles don&apos;t have to be
      perfect; you can change them later. Basically, if you import without defining these styles,
      then InDesign will throw out the text&apos;s styling. But if you create styles first, InDesign
      retains the style info.
  <li>Create a text frame where the calendar entries will go.
  <li><a href='/calendar.txt'>Download</a> the calendar text.
  <li>Import the text into InDesign with File | Place.
  <li>Make edits. The export is just a starting point. You&apos;ll still have to review the calendar,
      tweak some things, and add brief descriptions for the rides.
</ol>

<p><a style='font-size: 140%' href='/calendar.txt'>Download Calendar</a></p>

</div>

<?php
}

?>