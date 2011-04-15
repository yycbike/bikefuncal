<?php
# Functions related to creating & updating calendar events.
#
# This is a loose port of calform.php & calsubmit.php from the old
# site.
#
# Todos:
# - Test new event creation
# - Test update of existing events

global $wpdb;

class BfcEventSubmission {
    # Arguments for events (to pass on to the database)
    protected $event_args = Array();

    # Arguments for caldaily (to pass on to the database)
    protected $daily_args = Array();

    # A locally-cached copy of the $_FILES variable.
    # Create our own copy because this object isn't always created
    # in a POST request.
    protected $post_files;

    # DB id of the event. Unset when action is new.
    protected $event_id;
    
    # new    = show blank form for creating a new object
    # create = commit the results from a new 
    # edit   = show filled-in form for editing the event
    # update = commit the results from an edit
    # delete = delete the event (with no confirmation)
    protected $action;

    # Action to take on the image. This is only used
    # when $action is create or update.
    # 
    # keep   = do nothing
    # create = create a new image for this event
    # change = change from an old image to a new image
    # delete = delete the image
    protected $image_action;

    protected $errors = Array();

    protected $dayinfo;

    # About editcodes
    #
    # In order to modify an existing event, the user needs to
    # provide both an ID and an editcode. This prvents people from
    # editing someone else's event through URL hacking.
    #
    # An editcode is required for these actions: edit, update,
    # and delete.
    protected $user_editcode;
    protected $db_editcode;

    # Information about the database fields we'll be populating.
    # Keys are names of the fields.
    #
    # - type: %s or %d, used when generating the SQL statements to
    #   populate the database.
    #
    # - missing_val: A value to use if the field is missing from
    #   $wp_query. These are not default values. Rather, this overcomes
    #   these limitations in form processing: (1) WordPress doesn't
    #   populate $wp_query->query_vars[] for values that are passed in
    #   from the form, but blank. (2) When a checkbox is unchecked, the
    #   web browser doesn't even send over form data. We have to notice
    #   it's unchecked by the lack of a value. 
    #
    protected $calevent_field_info = Array(
        "id"              => array("type" => "%d"),
        # editcode is a special case, it's generated
        # internally upon create.
        "editcode"        => array("type" => "%s"),

        # wordpress_id is generated upon create
        "wordpress_id"    => array("type" => "%d"),
        
        "name"            => array("type" => "%s", "missing_val" => ""),
        "email"           => array("type" => "%s", "missing_val" => ""),
        "hideemail"       => array("type" => "%d", "missing_val" => "N"),
        "emailforum"      => array("type" => "%d", "missing_val" => "N"),
        "printemail"      => array("type" => "%d", "missing_val" => "N"),
        "phone"           => array("type" => "%s", "missing_val" => ""),
        "hidephone"       => array("type" => "%d", "missing_val" => ""),
        "printphone"      => array("type" => "%d", "missing_val" => "N"),
        "weburl"          => array("type" => "%s", "missing_val" => ""),
        "webname"         => array("type" => "%s", "missing_val" => ""),
        "printweburl"     => array("type" => "%d", "missing_val" => "N"),
        "contact"         => array("type" => "%s", "missing_val" => ""),
        "hidecontact"     => array("type" => "%d", "missing_val" => "N"),
        "printcontact"    => array("type" => "%d", "missing_val" => "N"),
        "title"           => array("type" => "%s", "missing_val" => ""),
        "tinytitle"       => array("type" => "%s", "missing_val" => ""),
        "audience"        => array("type" => "%s", "missing_val" => ""),
        "descr"           => array("type" => "%s", "missing_val" => ""),
        "printdescr"      => array("type" => "%s", "missing_val" => ""),
        "dates"           => array("type" => "%s", "missing_val" => ""),
        # datestype is generated by the code, not input from the form,
        # so don't set a missing_val for it.
        "datestype"       => array("type" => "%s"),
        "eventtime"       => array("type" => "%s", "missing_val" => ""),
        "eventduration"   => array("type" => "%d", "missing_val" => 0),
        "timedetails"     => array("type" => "%s", "missing_val" => ""),
        "locname"         => array("type" => "%s", "missing_val" => ""),
        "address"         => array("type" => "%s", "missing_val" => ""),
        # Unlike other booleans, addressverified has type char(1)
        "addressverified" => array("type" => "%s", "missing_val" => "N"),
        "locdetails"      => array("type" => "%s", "missing_val" => ""),

        # No missing_vals for image fields, because they come through the
        # file upload process.
        "image"           => array("type" => "%s"),
        "imagewidth"      => array("type" => "%d"),
        "imageheight"     => array("type" => "%d"),

        # Evan isn't sure yet what the right missing_val is for review,
        # so leave it alone for now.
        "review"          => array("type" => "%s"),
        );

    protected $caldaily_field_info = Array(
        "id"          => array("type" => "%d"),
        "newsflash"   => array("type" => "%s", "missing_val" => ""),
        "eventstatus" => array("type" => "%s"),
        "eventdate"   => array("type" => "%s"),
        "exceptionid" => array("type" => "%d"),
        );
    
    public function __construct() {

    }

    public function populate_from_query($query_vars, $post_files) {
        $this->post_files = $post_files || Array();
        
        if (isset($query_vars['submission_event_id'])) {
            $this->event_id = (int) $query_vars['submission_event_id'];
        }

        if (isset($query_vars['submission_action'])) {
            $this->action = $query_vars['submission_action'];
        }

        # If we haven't set an action, default to new.
        if (!isset($this->action)) {
            $this->action = 'new';
        }
        
        if (isset($query_vars['submission_image_action'])) {
            $this->image_action = $query_vars['submission_image_action'];
        }
        else if (isset($this->post_files['event_image']['error']) &&
                 $this->post_files['event_image']['error'] == UPLOAD_ERR_OK) {
            # This happens the first time an image is attached.
            # (submission_image_action is only passed in when editing an
            # event with an existing image.)
            $this->image_action = 'create';
        }
        else {
            # keep does nothing (and is safe to use if there's no image)
            $this->image_action = 'keep'; 
        }

        # If we're editing an existing object, grab it from the db.
        # Do this before we load the rest of the query vars, so that
        # user-specified values can override the database.
        if ($this->action == "edit") {
            $this->load_from_db();
        }
        else if ($this->action == "update"   ||
                 $this->action == "delete") {
            $this->load_modification_fields_from_db();
        }
        
        # Process arguments for caldaily
        foreach ($query_vars as $query_name => $query_value) {
            $regex_matches = array();
            if (preg_match('/event_(newsflash|status)(.*)/',
                $query_name, $regex_matches)) {

                # This is an argument for the caldaily table.
                # File it separately from the other arguments.
                $type        = $regex_matches[1];
                $date_suffix = $regex_matches[2];

                $this->daily_args[$date_suffix][$type] = stripslashes($query_value);
            }
        }
        
        # Process arguments for calevent
        foreach ($this->calevent_field_info as $field_name => $info) {
            # These come through the file upload mechanism, so don't
            # let them come in through the normal query process.
            if ($field_name == 'image' ||
                $field_name == 'imagheight' ||
                $field_name == 'imagewidth') {

                continue;
            }

            $query_field_name = 'event_' . $field_name;

            if (isset($query_vars[$query_field_name])) {
                if ($field_name == 'editcode') {
                    $this->user_editcode = $query_vars[$query_field_name];
                }
                else {
                    $this->event_args[$field_name] = 
                        stripslashes($query_vars[$query_field_name]);
                }
            }
        }

        if ($this->action == "new") {
            $this->set_defaults();
        }

        $this->convert_data_types();
    }

    public function do_action() {
        if ($this->action == "update" || $this->action == "create") {
            $this->fill_in_missing_values();
            $this->check_validity();
            if (!$this->is_valid()) {
                # Can't create or update because the event wasn't valid.
                # Go back to editing.
                #
                # @@@ This will break when creating a new event. The
                # next action will go from "edit" to "update" -- but update
                # is wrong; we want the next action to be create.
                #
                # Maybe next_action() can check to see if an event_id already
                # exists, and choose "create" or "update" appropriately.
                $this->action = "edit";
            }
            else {
                $this->calculate_days();
                $this->do_image_action();
                $this->save_wordpress_post();
                
                $this->add_event_to_db($this->event_args);
            }
        }
        else if ($this->action == "delete") {
            
            if ($this->is_editcode_valid()) {
                $this->delete();
            }
            else {
                $this->errors[] = "You don't have permission to delete this event.";
                $this->action = "edit";
            }
        }
        else if ($this->action == 'new' || $this->action == 'edit') {
            # No actions to do; these just show a form.
        }
        else {
            die("Bad action");
        }
    }

    protected function load_from_db() {
        global $calevent_table_name;
        global $caldaily_table_name;
        global $wpdb;

        if (!isset($this->event_id)) {
            die("Event ID is not set");
        }

        $sql = $wpdb->prepare("SELECT * FROM ${calevent_table_name} " .
                              "WHERE id=%d",
                              $this->event_id);
        $results = $wpdb->get_results($sql, ARRAY_A);
        
        if (count($results) != 1) {
            die("Wrong number of DB results: " . count($results));
        }
        else {
            $result = $results[0];
            foreach ($result as $db_key => $db_value) {

                if ($db_key == 'editcode') {
                    $this->db_editcode = $db_value;
                }
                else {
                    $this->event_args[$db_key] = $db_value;
                }
            }
        }

        # We don't have to load caldaily here. The fields get loaded
        # by the AJAX request in the submission form, and then passed
        # in as part of the form.
    }

    # Load just the fields that are needed for editing or deleting this
    # event.
    protected function load_modification_fields_from_db() {
        global $calevent_table_name;
        global $caldaily_table_name;
        global $wpdb;

        if (!isset($this->event_id)) {
            die();
        }

        $sql = $wpdb->prepare("SELECT editcode, image, wordpress_id " .
                              "FROM ${calevent_table_name} " .
                              "WHERE id=%d",
                              $this->event_id);
        $results = $wpdb->get_results($sql, ARRAY_A);
        
        if (count($results) != 1) {
            die("Wront number of DB results...");
        }
        else {
            $result = $results[0];
            $this->db_editcode = $result['editcode'];
            $this->event_args['image'] = $result['image'];
            $this->event_args['wordpress_id'] = $result['wordpress_id'];
        }

        $sql = $wpdb->prepare("SELECT eventdate, exceptionid " .
                              "FROM ${caldaily_table_name} " .
                              "WHERE id=%d",
                              $this->event_id);
        $results = $wpdb->get_results($sql, ARRAY_A);
        foreach ($results as $result) {
            # @@@ This will break if the event ever recurs past one year.
            # I think the suffix should change to include a four-digit year...
            #
            # And, while we're at it, encapsulate the making of suffixes into
            # a function.
            $suffix = date("Mj", strtotime($result['eventdate']));

            $this->daily_args[$suffix]['exceptionid'] = $result['exceptionid'];
        }
    }

    # Set some values to their defaults.
    protected function set_defaults() {
        if ($this->action != 'new') {
            die();
        }

        # @@@ Add more defaults...
        $this->event_args['audience'] = 'G';
    }

    # Create values for fields that are missing because the web browser
    # didn't send them over, or because WordPress isn't reporting them
    # in the query.
    protected function fill_in_missing_values() {
        if ($this->action != 'update' && $this->action != 'create') {
            die();
        }

        # Do missing values for event_args
        foreach ($this->calevent_field_info as $field_name => $field_info) {
            if (isset($field_info['missing_val']) &&
                !isset($this->event_args[$field_name]) ) {

                $this->event_args[$field_name] = $field_info['missing_val'];
            }
        }

        # Do missing values for daily_args
        foreach ($this->daily_args as $date_suffix => &$day) {
            foreach ($this->caldaily_field_info as $field_name => $field_info) {
                if (isset($field_info['missing_val']) &&
                    !isset($day[$field_name]) ) {

                    $day[$field_name] = $field_info['missing_val'];
                }
            }
        }
    }

    # Some of the data we get in from the form has to be converted before it's
    # ready to be stored to the database. Do those conversions.
    protected function convert_data_types() {
        # For these fields, convert Y/N to integer
        $field_names = array('hideemail', 'hidephone', 'emailforum', 'printemail',
                             'printphone', 'printweburl', 'hidecontact',
                             'printcontact');

        foreach ($field_names as $field_name) {
            if (isset($this->event_args[$field_name])) {
                $old_value = $this->event_args[$field_name];
                $new_value = $old_value == "Y" ? 1 : 0;
                $this->event_args[$field_name] = $new_value;
            }
        }
    }

    protected function save_wordpress_post() {
        $post_props = array(
            'post_title' => $this->event_args['title'],
        );

        if (!isset($this->event_args['wordpress_id'])) {
            # Make a new post

            # Additional properties for making a new post.
            $post_props['comment_status'] = 'open';
            $post_props['post_type'] = 'bfc-event';
            $post_props['post_status'] = 'publish';

            $post_id = wp_insert_post($post_props);

            if ($post_id != 0) {
                $this->event_args['wordpress_id'] = $post_id;
            }
            else {
                die("Could not create a WordPress post");
            }
        }
        else {
            # Update the existing post
            $post_props['ID'] = $this->event_args['wordpress_id'];

            wp_update_post($post_props);
        }
    }
    
    # Do an INSERT operation on the table
    protected function insert_into_table($table_name, $field_info, $args) {
        $types = Array();
        foreach ($args as $name => $value) {
            $types[] = $field_info[$name]['type'];
        }

        global $wpdb;

        $wpdb->insert($table_name,
                      $args,
                      $types);

        return $wpdb->insert_id;
    }

    # Do an UPDATE operation on the table
    protected function update_table($table_name, $field_info,
                                    $args, $where) {

        $arg_types = Array();
        foreach ($args as $name => $value) {
            $arg_types[] = $field_info[$name]['type'];
        }

        $where_types = Array();
        foreach ($where as $name => $value) {
            $where_types[] = $field_info[$name]['type'];
        }

        global $wpdb;
        $wpdb->update($table_name,
                      $args,
                      $where,
                      $arg_types,
                      $where_types);

        return;       
    }

    protected function add_event_to_db($event_args) {
        global $calevent_table_name, $caldaily_table_name;

        if ($this->action == "create") {
            # Create the editcode
            $event_args['editcode'] = uniqid();
            $this->user_editcode = $event_args['editcode'];
            $this->db_editcode = $event_args['editcode'];

            $this->event_id =
                $this->insert_into_table($calevent_table_name,
                                    $this->calevent_field_info,
                                    $event_args);
        }
        else if ($this->action == "update") {
            # Do one last check on the editcode, for good measure.
            if (!$this->is_editcode_valid()) {
                die("Bad editcode");
            }

            $where = Array('id' => $this->event_id);

            $this->update_table($calevent_table_name,
                                $this->calevent_field_info,
                                $event_args,
                                $where);
        }
        else {
            die("Bad value for action");
        }

        foreach ($this->dayinfo['daylist'] as &$day) {
            $caldaily_args = Array();

            # associate caldaily with calevent
            $caldaily_args['id'] = $this->event_id;

            # Populate the other fields.
            # We don't use $day directly because it has a bunch of fields
            # used for calculation that aren't stored in the database.
            foreach(Array('newsflash', 'eventdate',
                          'eventstatus', 'exceptionid') as $key) {

                if (isset($day[$key])) {
                    $caldaily_args[$key] = $day[$key];
                }
            }

            # Sometimes we end up with a day that has an eventdate &
            # not a sqldate. Evan thinks the two are interchangable, but
            # he's not sure... See the comments at the top of repeat.php,
            # about what the fields all mean.
            if (isset($day['sqldate']) && !isset($day['eventdate'])) {
                $caldaily_args['eventdate'] = $day['sqldate'];
            }

            if ($day['status'] == 'Deleted') {
                global $wpdb;
                # We have to put $caldaily_table_name into the string,
                # because if we put it in with %s, prepare() will put
                # quotes around it, and that's a SQL error.
                $sql = $wpdb->prepare("DELETE FROM ${caldaily_table_name} " .
                                      "WHERE id=%d and eventdate=%s",
                                      $this->event_id,
                                      $day['sqldate']);

                $result = $wpdb->query($sql);
                if ($result !== 1) {
                    die("Failed to delete one of the days.");
                }
            }
            else {
                if ($day['olddate'] == 'Y') {
                    # This caldaily entry already exists; do an update

                    if ($day['status'] == 'Exception') {
                        # Do we need to make a new exception?
                        #
                        # Use -1 as a flag for 'no exception' because
                        # stupid WordPress won't let us insert null into
                        # the database. But we also have to check for null
                        # because that's what comes *out* of the database,
                        # before anything else has been set.
                        if (!isset($day['exceptionid']) ||
                            $day['exceptionid'] == null ||
                            $day['exceptionid'] == -1) {

                            $exception = $this->make_exception($day['sqldate']);
                            $caldaily_args['exceptionid'] = $exception->event_id;
                            $day['exceptionid'] = $exception->event_id;
                        }

                        # @@@ Store a list of exceptions, so we can show edit links to the user.
                    }

                    $where = Array('id' => $this->event_id,
                                   'eventdate' => $day['sqldate']);

                    $this->update_table($caldaily_table_name,
                                        $this->caldaily_field_info,
                                        $caldaily_args,
                                        $where);
                }
                else {
                    # this caldaily entry doesn't exist yet;
                    # do an insert.
                    $this->insert_into_table($caldaily_table_name,
                                        $this->caldaily_field_info,
                                        $caldaily_args);
                }
            }
        }
    }

    protected function delete() {
        global $calevent_table_name;
        global $caldaily_table_name;
        global $wpdb;

        $caldaily_query = $wpdb->prepare("DELETE FROM ${caldaily_table_name} " .
                                         "WHERE id=%d OR exceptionid=%d",
                                         $this->event_id, $this->event_id);
        $result = $wpdb->query($caldaily_query);
        if ($result === false) {
            die("Failed to delete from caldaily");
        }

        $calevent_query = $wpdb->prepare("DELETE FROM ${calevent_table_name} " .
                                         "WHERE id=%d",
                                         $this->event_id);
        $result = $wpdb->query($calevent_query);
        if ($result === false) {
            die("Failed to delete from caldaily");
        }

        $this->delete_image();

        # Delete the associated WordPres Bike Event.
        #
        # The get_post_type() test is there to prevent a bug from
        # accidentally deleting an important post (like the front
        # page).
        if (isset($this->event_args['wordpress_id']) &&
            get_post_type($this->event_args['wordpress_id']) == 'bfc-event') {

            wp_delete_post($this->event_args['wordpress_id']);
        }
    }

    protected function do_image_action() {
        if ($this->image_action == 'keep') {
            # Do nothing
        }
        else if ($this->image_action == 'create') {
            $this->attach_image();
        }
        else if ($this->image_action == 'change') {
            $this->delete_image();
            $this->attach_image();
        }
        else if ($this->image_action == 'delete') {
            $this->delete_image();
        }
        else {
            die("Bad image action");
        }
    }

    protected function attach_image() {
        if (!isset($this->post_files['event_image']['error']) ||
                 $this->post_files['event_image']['error'] != UPLOAD_ERR_OK) {

            # This shouldn't have been called
            die(); 
        }

        # Copy the file to the uploads directory.

        # @@@ If we wanted to get fancy, we could pass in the date of the
        # first event, so the upload dir would correspond to the event date,
        # not the creation date (today's date).
        $upload_dirinfo = wp_upload_dir();
        
        # This trusts that the file extension is OK on the user's machine...
        $extension = pathinfo($this->post_files['event_image']['name'],
                              PATHINFO_EXTENSION);

        # Use uniqid() for the filename because, when this runs, the event ID
        # may not have been set yet.
        $filename = $upload_dirinfo['path'] . '/' .
            uniqid() . '.' . $extension;

        move_uploaded_file($this->post_files['event_image']['tmp_name'],
                           $filename);
        list($imagewidth, $imageheight) = getimagesize($filename);

        # Make a filename that's relative to the uploads dir.
        # We can use this later to construct a URL.
        $relative_filename =
            str_replace($upload_dirinfo['basedir'], '', $filename);
        
        # Update the event_args
        $this->event_args['image'] = $relative_filename;
        $this->event_args['imageheight'] = $imageheight;
        $this->event_args['imagewidth'] = $imagewidth;

        #var_dump($this->event_args);
        
        # (We could make a WordPress attachment out of this. But what would
        # the benefit be?)
    }

    # Delete the image file for this event, if it has one.
    protected function delete_image() {
        if (isset($this->event_args['image']) &&
            $this->event_args['image'] != '') {

            $upload_dirinfo = wp_upload_dir();
            $old_filename = $upload_dirinfo['basedir'] .
                $this->event_args['image'];
            
            $success = unlink($old_filename);
            if (!$success) {
                die("Can't delete image: $old_filename");
            }

            # Because $wpdb->insert() can't handle nulls,
            # we have to set these to empty values to update
            # the database.
            $this->event_args['image'] = '';
            $this->event_args['imagewidth'] = 0;
            $this->event_args['imageheight'] = 0;
        }
    }
    
    protected function is_editcode_valid() {
        if ($this->action == "edit"   ||
            $this->action == "update" ||
            $this->action == "delete") {

            if (!isset($this->db_editcode)) {
                # This shouldn't happen.
                die("Missing DB edit code");
            }

            if (!isset($this->user_editcode) ||
                $this->user_editcode != $this->db_editcode) {

                return false;
            }
        }

        return true;
    }
    
    # @@@ check for more invalid values
    protected function check_validity() {
        # @@@ Validate the dates more carefully
        if ($this->event_args['dates'] == '') {
            $this->errors[] = "Date is missing";
        }

        # Validate the edit code
        if (!$this->is_editcode_valid()) {
            $this->errors[] = "You don't have authorization to edit this event";
        }
    }

    public function is_valid() {
        return count($this->errors) == 0;
    }
    
    # Calculate the recurring event stuff.
    protected function calculate_days() {
        if (!isset($this->event_args['dates'])) {
            die();
        }

        # parse the $dates value, and convert it to a list of specific dates
        $this->dayinfo = repeatdates($this->event_args['dates']);

        # Store the type of date (sequential, scattered, etc.)
        # But we only store the first letter in the DB.
        $this->event_args['datestype'] =
            strtoupper(substr($this->dayinfo['datestype'], 0, 1));

        if ($this->action == "update") {
            # Fetch the old dates
            $olddates = dailystatus($this->event_id);

            # Merge the old and new lists.
            $this->dayinfo['daylist'] =
                mergedates($this->dayinfo['daylist'], $olddates);
        }
        else if ($this->action == "create") {
            # all dates will be added
            foreach ($this->dayinfo['daylist'] as &$day) {
                $day['changed'] = 'Y';
                $day['olddate'] = 'N';
            }
        }
        else {
            die("Bad value for action");
        }

        # Add values for status & newsflash from the query
        # parameters. Use a reference to $day, to let us
        # modify the values.
        foreach ($this->dayinfo['daylist'] as &$day) {
            $suffix = $day['suffix'];

            if (isset($this->daily_args[$suffix]['newsflash'])) {
                $day['newsflash'] = $this->daily_args[$suffix]['newsflash'];
            }

            if (isset($this->daily_args[$suffix]['status'])) {
                $day['status'] = $this->daily_args[$suffix]['status'];
                $day['eventstatus'] = statusname($day['status']);
            }
        }
    }

    protected function make_exception($date) {
        $exception = new BfcEventSubmission();

        # Copy most (but not all) fields from this event to the exception.
        $do_not_copy = Array('dates', 'datestype',
                             'wordpress_id',
                             'image', 'imagewidth', 'imageheight');
        foreach ($this->event_args as $arg_name => $arg_value) {
            if (!in_array($arg_name, $do_not_copy)) {
                $exception->event_args[$arg_name] = $arg_value;
            }
        }

        # Don't need to set an edit code; that will happen when creating
        # the event.

        $exception->action = 'create';
        $exception->image_action = 'keep';
        $exception->event_args['dates'] = date("l, F j", strtotime($date));

        $suffix = date("Mj", strtotime($date));
        $exception->daily_args[$suffix]['status'] = 'As Scheduled';

        $exception->do_action();
     
        return $exception;
   
        # @@@ todo:
        # - Copy the image. Need to make a new copy of the file, because
        #   if they share we'd run into problems when one event (but not the other) is
        #   deleted and the file gets deleted along with it.
    }

    # Return a list of exceptions. Something like this:
    # Array( Array('date' => '2011-06-04', 'exceptionid' => 42), ... );
    public function get_exceptions() {
        $exceptions = array();

        foreach ($this->dayinfo['daylist'] as $day) {
            if ($day['status'] == 'Exception') {
                $exceptions[] = Array(
                    'sqldate' => $day['sqldate'],
                    'exceptionid'   => $day['exceptionid'],
                );
            }
        }

        return $exceptions;
    }

    private function print_value_for_text_input($argname) {
        if (isset($this->event_args[$argname])) {
            $value = htmlspecialchars($this->event_args[$argname], ENT_QUOTES);
            printf("value=\"%s\"", $value);
        }
    }

    public function print_title() {
        $this->print_value_for_text_input('title');
    }

    public function print_tinytitle() {
        $this->print_value_for_text_input('tinytitle');
    }

    public function print_locname() {
        $this->print_value_for_text_input('locname');
    }

    public function print_locdetails() {
        $this->print_value_for_text_input('locdetails');
    }

    public function print_address() {
        $this->print_value_for_text_input('address');
    }

    public function print_dates() {
        $this->print_value_for_text_input('dates');
    }

    public function print_timedetails() {
        $this->print_value_for_text_input('timedetails');
    }

    public function print_name() {
        $this->print_value_for_text_input('name');
    }
    
    public function print_phone() {
        $this->print_value_for_text_input('phone');
    }

    public function print_email() {
        $this->print_value_for_text_input('email');
    }

    public function print_weburl() {
        $this->print_value_for_text_input('weburl');
    }

    public function print_webname() {
        $this->print_value_for_text_input('webname');
    }

    public function print_contact() {
        $this->print_value_for_text_input('contact');
    }
    
    public function print_descr() {
        if (isset($this->event_args['descr'])) {
            print htmlspecialchars($this->event_args['descr'], ENT_QUOTES);
        }
    }

    private function print_checked_for_audience($audience) {
        if (isset($this->event_args['audience']) &&
            $this->event_args['audience'] == $audience) {

            print "checked";
        }
    }
    
    public function print_checked_for_family_audience() {
        $this->print_checked_for_audience('F');
    }

    public function print_checked_for_general_audience() {
        $this->print_checked_for_audience('G');
    }

    public function print_checked_for_adult_audience() {
        $this->print_checked_for_audience('A');
    }

    public function print_checked_for_hideemail() {
        if (isset($this->event_args['hideemail']) &&
            $this->event_args['hideemail']) {

            print "checked";
        }
    }

    public function print_checked_for_hidephone() {
        if (isset($this->event_args['hidephone']) &&
            $this->event_args['hidephone']) {

            print "checked";
        }
    }

    public function print_checked_for_hidecontact() {
        if (isset($this->event_args['hidecontact']) &&
            $this->event_args['hidecontact']) {

            print "checked";
        }
    }

    public function print_selected_for_eventtime($time) {
        if (!isset($this->event_args['eventtime']) && $time == "") {

            # The default value of eventtime is empty string.
            # (that's the value that represents 'Choose a time' in the
            # SELECT drop-down

            print "selected";
        }
        else if (isset($this->event_args['eventtime']) &&
                 $this->event_args['eventtime'] == $time) {

            print "selected";
        }

        # Otherwise this isn't selected. Print nothing.
    }

    public function print_selected_for_duration($duration) {
        if (!isset($this->event_args['duration']) &&
            $duration == "0") {

            # 0 (unspecified) is selected by default
            print "selected";
        }
        else if (isset($this->event_args['duration']) &&
                 $this->event_args['duration'] == $duration) {

            print "selected";
        }

        # Otherwise this isn't selected. Print nothing.
    }

    public function current_action() {
        return $this->action;
    }

    public function has_event_id() {
        return isset($this->event_id);
    }

    public function event_id() {
        return $this->event_id;
    }

    public function has_editcode() {
        return isset($this->db_editcode);
    }

    public function editcode() {
        if (!$this->is_editcode_valid()) {
            die("Missing authorization to edit this entry");
        }

        return $this->user_editcode;
    }

    public function has_delete() {
        return $this->action == "edit";
    }

    public function has_image() {
        return isset($this->event_args['image']) &&
                $this->event_args['image'] != '';
    }

    # Return the action to perform next.
    # This should only be called from the edit-event
    # page.
    public function next_action() {
        if ($this->action == "new") {
            return "create";
        }
        else if ($this->action == "edit") {
            return "update";
        }
        else {
            die("next_action() should not have been called");
        }
    }

    public function print_errors() {
        if (!$this->is_valid()) {
            print "<ul>\n";

            foreach ($this->errors as $error) {
                print "<li>";
                print $error;
                print "</li>";
            }
            
            print "</ul>\n";
        }
    }

    # What kind of page should the caller show?
    #
    # edit-event     -- Show the form for making/updating an event
    # event-updated  -- Show the results of making/updating an event
    # event-deleted  -- Show the results of deleting an event
    public function page_to_show() {
        if ($this->action == "new" ||
            $this->action == "edit") {

            return "edit-event";
        }
        else if ($this->action == "create" ||
                 $this->action == "update") {

            return "event-updated";
        }
        else if ($this->action == "delete") {
            return "event-deleted";
        }
        else {
            die("Bad action: $this->action");
        }
    }
}
?>