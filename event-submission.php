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

    # DB id of the event. Unset when action is new.
    protected $event_id;
    
    # new    = show blank form for creating a new object
    # create = commit the results from a new 
    # edit   = show filled-in form for editing the event
    # update = commit the results from an edit
    # delete = TBD...
    protected $action;

    protected $errors = Array();

    protected $dayinfo;
    
    public function __construct() {
        global $wp_query;
        $query_vars = $wp_query->query_vars;

        # Query-supplied arguments for the caldaily form
        $daily_args = Array();

        if (isset($query_vars['calform_event_id'])) {
            $this->event_id = (int) $query_vars['calform_event_id'];
        }

        if (isset($query_vars['calform_action'])) {
            $this->action = $query_vars['calform_action'];
        }

        # If we haven't set an action, default to new.
        if (!isset($this->action)) {
            $this->action = 'new';
        }

        # If we're editing an existing object, grab it from the db.
        # Do this before we load the rest of the query vars, so that
        # user-specified values can override the database.
        if ($this->action == "edit") {
            $this->load_from_db();
        }
        
        # Go through $query_vars, find the ones named
        # event_, remove the prefix, and add them to $args.
        foreach ($query_vars as $query_name => $query_value) {
            $regex_matches = array();
            if (preg_match('/event_(newsflash|status)(.*)/',
                $query_name, $regex_matches)) {

                # This is an argument for the caldaily table.
                # File it separately from the other arguments.
                $type        = $regex_matches[1];
                $date_suffix = $regex_matches[2];

                $daily_args[$date_suffix][$type] = $query_value;
            }
            else if (substr($query_name, 0, 6) == "event_") {
                $arg_name = substr($query_name, 6);

                $this->event_args[$arg_name] = $query_value;
            }
        }

        # Default audience is "General"
        if (!isset($this->event_args['audience'])) {
            $this->event_args['audience'] = 'G';
        }

        if ($this->action == "update" || $this->action == "create") {
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
                # For updates (not creates), add these actions:
                # @@@ need to track changes to images

                $this->calculate_days($daily_args);
                $this->add_event_to_db($this->event_args,
                                       $this->dayinfo['daylist']);
            }
        }
    }

    protected function load_from_db() {
        global $calevent_table_name;
        global $caldaily_table_name;
        global $wpdb;

        if (!isset($this->event_id)) {
            die();
        }
        
        $query = <<<END_QUERY
SELECT * FROM ${calevent_table_name}
WHERE id=$this->event_id

END_QUERY;

        $results = $wpdb->get_results($query, ARRAY_A);
        
        if (count($results) != 1) {
            die("Wront number of DB results...");
        }
        else {
            $result = $results[0];
            foreach ($result as $db_key => $db_value) {
                $this->event_args[$db_key] = $db_value;
            }
        }

        # We don't have to load caldaily here. The fields get loaded
        # by the AJAX request in the submission form, and then passed
        # in as part of the form.
    }

    # Do an INSERT operation on the table
    protected function insert_into_table($table_name, $type_for_field, $args) {
        $types = Array();
        foreach ($args as $name => $value) {
            $types[] = $type_for_field[$name];
        }

        global $wpdb;

        $wpdb->insert($table_name,
                      $args,
                      $types);

        return $wpdb->insert_id;
    }

    # Do an UPDATE operation on the table
    protected function update_table($table_name, $type_for_field,
                                    $args, $where) {

        $arg_types = Array();
        foreach ($args as $name => $value) {
            $arg_types[] = $type_for_field[$name];
        }

        $where_types = Array();
        foreach ($where as $name => $value) {
            $where_types[] = $type_for_field[$name];
        }

        global $wpdb;
        $wpdb->update($table_name,
                      $args,
                      $where,
                      $arg_types,
                      $where_types);

        return;       
    }

    protected function add_event_to_db($event_args, $daylist) {
        # @@@ need to add image, imagewidth, and imageheight
        $type_for_event_field = Array(
            "id"              => "%d",
            "name"            => "%s",
            "email"           => "%s",
            "hideemail"       => "%d",
            "emailforum"      => "%d",
            "printemail"      => "%d",
            "phone"           => "%s",
            "hidephone"       => "%d",
            "printphone"      => "%d",
            "weburl"          => "%s",
            "webname"         => "%s",
            "printweburl"     => "%d",
            "contact"         => "%s",
            "hidecontact"     => "%d",
            "printcontact"    => "%d",
            "title"           => "%s",
            "tinytitle"       => "%s",
            "audience"        => "%s",
            "descr"           => "%s",
            "printdescr"      => "%s",
            "dates"           => "%s",
            "datestype"       => "%s",
            "eventtime"       => "%s",
            "eventduration"   => "%s",
            "timedetails"     => "%s",
            "locname"         => "%s",
            "address"         => "%s",
            "addressverified" => "%s",
            "locdetails"      => "%s",
            "review"          => "%s",
            );

        $type_for_daily_field = Array(
            "id"          => "%d",
            "newsflash"   => "%s",
            "eventdate"   => "%s",
            "eventstatus" => "%s",
            "exceptionid" => "%d",
            );

        global $calevent_table_name, $caldaily_table_name;

        if ($this->action == "create") {
            $this->event_id =
                $this->insert_into_table($calevent_table_name,
                                    $type_for_event_field,
                                    $event_args);
        }
        else if ($this->action == "update") {
            $where = Array('id' => $this->event_id);

            $this->update_table($calevent_table_name,
                                $type_for_event_field,
                                $event_args,
                                $where);
        }
        else {
            die("Bad value for action");
        }

        foreach ($daylist as $day) {
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
            
            #print "<pre>";
            #var_dump($caldaily_args);
            #print "</pre>";

            # Figure out if we need to to a DB UPDATE or INSERT. 
            # This logic comes from the old calsubmit.php code,
            # and I don't entirely understand it. (See that file,
            # lines 425 or 270.)
            #
            # @@@ We also need to do a delete, when the
            # status is deleted?
            $event_exists = $this->action == "update";
            $day_exists   = isset($day['status']) && 
                ($day['status'] == "Added" ||
                 $day['status'] == "Skipped" ||
                 $day['status'] == "Canceled" ||
                 $day['status'] == "As Scheduled");

            if ($event_exists && $day_exists) {
                $where = Array('id' => $this->event_id,
                               'eventdate' => $day['sqldate']);

                $this->update_table($caldaily_table_name,
                                    $type_for_daily_field,
                                    $caldaily_args,
                                    $where);
            }
            else if ($day['status'] == 'Deleted') {
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
                $this->insert_into_table($caldaily_table_name,
                                    $type_for_daily_field,
                                    $caldaily_args);
            }                                        
        }
    }

    # @@@ check for more invalid values
    protected function check_validity() {
        if (! isset($this->event_args['dates']) ) {
            $this->errors[] = "Date is missing";
        }
    }

    public function is_valid() {
        return count($this->errors) == 0;
    }
    
    # Calculate the recurring event stuff.
    protected function calculate_days($daily_args) {
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
            foreach ($this->dayinfo['daylist'] as $day) {
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

            if (isset($daily_args[$suffix]['newsflash'])) {
                $day['newsflash'] = $daily_args[$suffix]['newsflash'];
            }

            if (isset($daily_args[$suffix]['status'])) {
                $day['status'] = $daily_args[$suffix]['status'];
                $day['eventstatus'] = statusname($day['status']);
            }
        }
    }

    # @@@ Sanitize these outputs
    private function print_value_for_text_input($argname) {
        if (isset($this->event_args[$argname])) {
            printf("value=\"%s\"", $this->event_args[$argname]);
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
            print $this->event_args['descr'];
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
            $this->event_args['hideemail'] == 'Y') {

            print "checked";
        }
    }

    public function print_checked_for_hidephone() {
        if (isset($this->event_args['hidephone']) &&
            $this->event_args['hidephone'] == 'Y') {

            print "checked";
        }
    }


    public function print_checked_for_hide_contact() {
        if (isset($this->event_args['hide_contact']) &&
            $this->event_args['hide_contact'] == 'Y') {

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

    public function has_next_action() {
        return $this->action == "new" ||
            $this->action == "edit";
    }

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
    # new-event-form -- Show the form for making/updating an event
    # event-updated  -- Show the results of making/updating an event
    public function page_to_show() {
        if ($this->action == "new" ||
            $this->action == "edit") {


            return "edit-event";
        }
        else if ($this->action == "create" ||
                 $this->action == "update") {


            return "event-updated";
        }
        else {
            die("Bad action: $this->action");
        }
    }
}
?>