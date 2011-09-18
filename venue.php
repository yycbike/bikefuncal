<?php

/**
 * Functions related to the known-venues list
 */


/**
 * Display the known-venues list for editing
 */
function bfc_venues_admin_page() {

    // We can just call this directly; no futzing around with making it part
    // of the footer.
    bfc_load_venue_list_javascript();
    
?>

<div class='wrap'>
    
<h2>Known-Venue List</h2>

<p>
When people are adding a ride to the calendar, the venue name (and address) can be filled in if the
database knows about that venue. Add known venues here.
</p>

<p>
Don't worry about the <em>Lock</em> field. Just leave it alone.
</p>

<table id='known-venues'>
<thead>
<tr>
<th>Name of Business or Park</th>
<th>Address</th>
<th>Lock</th>
<th>Edit</th>
<th><!-- results of save go here --></th>
</tr>
<thead>

<tbody>
<!-- this gets filled in via ajax -->
</tbody>

<tfoot>
<tr>
<td colspan=3>
<strong>Add a new venue:</strong>
</td>
</tr>

<tr>
<td>
<input type=text id=venue_name>
</td>

<td>
<input type=text id=venue_address>
</td>

<td>
<input type=checkbox id=venue_locked checked>
</td>

<td>
<input id=add_venue type=submit value='add'>
</td>

<td id=new_venue_result></td>
</tr>

</tfoot>

</table>

</div><!-- wrap -->
     
<?php    
}

add_action('wp_ajax_get-known-venues', 'bfc_get_known_venues_ajax_action');
function bfc_get_known_venues_ajax_action() {
    // Don't bother checking a nonce, because this is a nondestructive
    // action.

    global $wpdb;
    global $caladdress_table_name;

    $sql = "select * from ${caladdress_table_name} order by locname ASC";
    $venues = $wpdb->get_results($sql, ARRAY_A);

    // Convert locked to a boolean
    foreach ($venues as &$venue) {
        $venue['locked'] = ($venue['locked'] == 1);
    }
    
    $json = json_encode($venues);

    echo $json, "\n";

    exit;
}

add_action('wp_ajax_edit-venue', 'bfc_edit_venue_ajax_action');
function bfc_edit_venue_ajax_action() {
    $result = array();
    $args = array();

    if (!wp_verify_nonce($_POST['nonce'], 'bfc-venue')) {
        $result['status'] = 0;
        print json_encode($result);
        exit;
    }
    $result['nonce'] = wp_create_nonce('bfc-venue');

    if (!isset($_POST['type'])) {
        $result['status'] = 0;
        print json_encode($result);
        exit;
    }

    if ($_POST['type'] == 'create') {
        $mandatory_fields = array('address',
            'locname', 'locked');
        $arg_types = array('%s', '%s', '%d');
    }
    else if ($_POST['type'] == 'update') {
        $mandatory_fields = array('id', 'address',
            'locname', 'locked');
        $arg_types = array('%d', '%s', '%s', '%d');
    }
    else {
        $result['status'] = 0;
        print json_encode($result);
        exit;
    }
    
    # Make sure we have all the data we need
    foreach ($mandatory_fields as $field) {
        if (!isset($_POST[$field])) {
            $result['status'] = 0;
            print json_encode($result);
            exit;
        }
        else {
            $args[$field] = stripslashes($_POST[$field]);

            # Data validation & whitespace cleanup
            $args[$field] = trim($args[$field]);
            if ($args[$field] == '') {
                $result['status'] = 0;
                if ($field == 'address') {
                    $result['error'] = "Can't save: Address is blank.";
                }
                else if ($field == 'locname') {
                    $result['error'] = "Can't save: Name is blank.";
                }
                print json_encode($result);
                exit;
            }
        }
    }

    # convert boolean from string to int
    $args['locked'] = $args['locked'] == 'true' ? 1 : 0;

    # Do the database update
    global $wpdb;
    global $caladdress_table_name;

    if ($_POST['type'] == 'update') {
        $where = array('id' => $args['id']);
        $where_types = array('%d');

        $wpdb->update($caladdress_table_name,
                      $args,
                      $where,
                      $arg_types,
                      $where_types);

        $result['status'] = 1;
    }
    else if ($_POST['type'] == 'create') {
        $wpdb->insert($caladdress_table_name,
                      $args,
                      $arg_types);
        $result['status'] = 1;
    }
    else {
        $result['status'] = 0;
    }

    # @@@ Turn off printing of errors, and instead return
    # errors as part of the JSON.

    # debugging:
    #$result['query'] = $wpdb->last_query;

    print json_encode($result);
    exit;
}

add_action('wp_ajax_delete-venue', 'bfc_delete_venue_ajax_action');
function bfc_delete_venue_ajax_action() {
    $result = array();

    if (!wp_verify_nonce($_POST['nonce'], 'bfc-venue')) {
        $result['status'] = 0;
        print json_encode($result);
        exit;
    }
    $result['nonce'] = wp_create_nonce('bfc-venue');

    if (!isset($_POST['id'])) {
        $result['status'] = 0;
        print json_encode($result);
        exit;
    }

    global $wpdb;
    global $caladdress_table_name;
    $sql = $wpdb->prepare("DELETE FROM ${caladdress_table_name} " .
                          "WHERE id = %d", $_POST['id']);

    $query_result = $wpdb->query($sql);

    $result['status'] = ($query_result !== false);

    # @@@ Turn off printing of errors, and instead return
    # errors as part of the JSON.

    # debugging:
    #$result['query'] = $wpdb->last_query;

    print json_encode($result);
    exit;
}

#
# Load the JavaScript code that the event submission page needs.
#
# This is designed to be run in the wp_footer action.
function bfc_load_venue_list_javascript() {
    # WordPress ships with a crappy old version of jquery hotkeys. It breaks
    # when binding return. Use the newer version.
    $hotkeys_js_url = plugins_url('bikefuncal/jquery.hotkeys/jquery.hotkeys.js');
    wp_register_script('non-broken-hotkeys', $hotkeys_js_url, array('jquery'));

    # @@@ Evan isn't sure how to do this without hard-coding
    # 'bikefuncal' into the URL.
    $venue_js_url = plugins_url('bikefuncal/venue.js');
    
    wp_register_script('venue', $venue_js_url, array('jquery', 'non-broken-hotkeys'));
    wp_localize_script('venue',
                       'BikeFunAjax',
                       array( 'ajaxurl' => admin_url( 'admin-ajax.php' ),
                              'nonce'   => wp_create_nonce('bfc-venue'),
                       ) );

    wp_print_scripts('venue');
}

# Return an associative array of location names 
# mapped to addresses.
function bfc_venue_list() {
    global $wpdb;
    global $caladdress_table_name;

    $sql = "select locname, address from ${caladdress_table_name}";
    $venues = $wpdb->get_results($sql, ARRAY_A);

    $assoc_venues = Array();
    foreach ($venues as $venue) {
        $assoc_venues[$venue['locname']] = $venue['address'];
    }

    return $assoc_venues;
}

?>