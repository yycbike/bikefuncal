<?php
# Functions for showing an admin menu.

add_action('admin_menu', 'bfc_plugin_menu');
function bfc_plugin_menu() {
    add_menu_page('Bike Fun Cal', # Page title
                     'Bike Fun Cal', # Menu title
                     'manage_options', # capability
                     'bfc-top',   # menu slug
                     'bfc_options');  # function callback

    add_submenu_page('bfc-top', # parent
                     'Edit Known Venues', # title
                     'Venues',
                     'manage_options', # capability
                     'bfc-venues',   # menu slug
                     'bfc_venues');  # function callback

}

function bfc_options() {
    echo "<p>More options will go here someday</p>";
}

# Display the known-venues list for editing
function bfc_venues() {

    # We can just call this directly; no futzing around with making it part
    # of the footer.
    bfc_load_venue_list_javascript();
    
?>

<h2>Known-Venue List</h2>

<p>todo: Explain what this is good for</p>

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
<!-- @@@ todo: add locked -->
</td>

<td>
<input id=add_venue type=submit value='add'>
</td>

<td id=new_venue_result></td>
</tr>

</tfoot>

</table>

<p>Todo: Explain what <em>locked</em> is.</p>

<?php    
}

function bfc_get_known_venues() {
    global $wpdb;
    global $caladdress_table_name;

    $sql = "select * from ${caladdress_table_name} order by locname ASC";
    $venues = $wpdb->get_results($sql, ARRAY_A);

    $json = json_encode($venues);

    echo $json, "\n";

    exit;
}
add_action('wp_ajax_get-known-venues',
           'bfc_get_known_venues');

function bfc_edit_venue() {
    $result = array();

    $args = array();

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
        $mandatory_fields = array('canon', 'address',
            'locname', 'locked');
        $arg_types = array('%s', '%s', '%s', '%d');
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
            $args[$field] = $_POST[$field];
        }
    }

    # convert boolean from string to int
    $args['locked'] = $args['locked'] == 'true' ? 1 : 0;

    # Find canonical name, if needed
    if ($_POST['type'] == 'create') {
        $args['canon'] = canonize($args['locname']);
        $arg_types[] = '%s';
    }

    # @@@ If this is an update, do we need to change
    # canon?

    # Do the database update
    global $wpdb;
    global $caladdress_table_name;

    if ($_POST['type'] == 'update') {
        $where = array('canon' => $args['canon']);
        $where_types = array('%s');

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

    print json_encode($result);
    exit;
}
add_action('wp_ajax_edit-venue',
           'bfc_edit_venue');


#
# Load the JavaScript code that the event submission page needs.
#
# This is designed to be run in the wp_footer action.
function bfc_load_venue_list_javascript() {
    # @@@ Evan isn't sure how to do this without hard-coding
    # 'bikefuncal' into the URL.
    $js_url = plugins_url('bikefuncal/venue.js');

    wp_register_script('venue', $js_url, array('jquery'));
    wp_localize_script('venue',
                       'BikeFunAjax',
                       array( 'ajaxurl' => admin_url( 'admin-ajax.php' ) ) );

    wp_print_scripts('venue');
}



?>