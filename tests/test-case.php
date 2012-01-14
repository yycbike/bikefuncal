<?php

/**
 * Generic base class for other tests.
 */
abstract class BfcTestCase extends WPTestCase {
    function setUp() {
        parent::setUp();
        bfc_install();

        // Create the mandatory pages: New Event and Monthly Calendar
        $this->insert_post_or_die(array(
                                      'post_title' => 'New Event',
                                      'post_content' => '[bfc_event_submission]',
                                      'post_status' => 'publish',
                                      'post_type'   => 'page',
                                        ));

        $this->insert_post_or_die(array(
                                      'post_title' => 'Monthly Calendar',
                                      'post_content' => 'calendar goes here',
                                      'post_status' => 'publish',
                                      'post_type'   => 'page',
                                        ));

        // Set values for the options
        update_option('bfc_festival_start_date', '2012-06-02');
        update_option('bfc_festival_end_date', '2012-06-19');
        update_option('bfc_drinking_age', '19');
        update_option('bfc_city', 'Vancouver');
        update_option('bfc_province', 'BC');
        update_option('bfc_calendar_email', 'test-velolove-calendar@bogushost.ca');
    }

    function tearDown() {
        parent::tearDown();
    }

    /**
     * Try to make a WordPress post. Die if it fails.
     */
    function insert_post_or_die($options) {
        $result = wp_insert_post($options);

        // The return value of wp_insert_post() depends on the value of
        // $wp_error.
        global $wp_error;
        if ($wp_error) {
            if ($result !== null) {
                die("Couldn't create a page");
            }
        }
        else {
            if ($result === 0) {
                die("Couldn't create a page");
            }
        }
        
    }

    /**
     * Fiddle with the arguments, to ensure they're passed to
     * BfcEventSubmission->populate_from_query() in the same way
     * that WordPress and the browser do.
     */
    function simulate_query_vars(&$args) {
        // Empty values are removed from $wp_query->query_vars[], so simulate
        // that removal for the unit tests.
        foreach (array_keys($args) as $arg_name) {
            if ($args[$arg_name] === null ||
                $args[$arg_name] === '') {

                unset($args[$arg_name]);
            }
        }

        // Checkboxes are not reported at all if the value is false.
        $checkbox_arg_names = array('event_hideemail', 'submission_suppress_email', 'event_emailforum');
        foreach ($checkbox_arg_names as $arg_name) {
            if (isset($args[$arg_name])) {
                // the value will be Y/N if this is coming in from other test cases
                // (passing what the browser would pass). It will be 1/0 (as strings) if
                // it's being copied over from the previous BfcEventSubmission object,
                // because of BfcEventSubmission::convert_data_type().
                if ($args[$arg_name] === 'N' || $args[$arg_name] === '0') {
                    unset($args[$arg_name]);
                }
                else if ($args[$arg_name] === '1') {
                    $args[$arg_name] = 'Y';
                }
                else if ($args[$arg_name] === 'Y') {
                    // Let it go through
                }
                else {
//                    die("Bad value for $arg_name: $args[$arg_name]");
                    throw new Exception("Bad vlue for $arg_name: '$args[$arg_name]'");
                }
            }
        }
    }

    /**
     * Create a valid event submission. It comes with enough default arguments
     * to pass the validator. You can pass in extra arguments to supplement or
     * override the defaults. The arguments simulate what gets passed in
     * to $wp_query->query_vars[].
     */
    function make_valid_submission($extra_args = array(), $files_args = array()) {
        $submission = new BfcEventSubmissionForTest;
        $submission_args = array(
            'submission_action' => 'create',
            'event_dates' => 'October 8',
            'event_audience'    => 'G',
            // Generate a unique title to avoid clashing with anything else
            // in the database that might have that title.
            'event_title' => 'Title ' . uniqid(),
            'event_descr' => 'Description',
            'event_name' => 'Earl',
            'event_email' => 'hi@hello.ca',
            'event_eventtime' => '19:15:00',
            );
        $submission_args = array_merge($submission_args, $extra_args);
        $submission_args = array_merge(
            $submission_args,
            $this->make_date_args($submission_args['event_dates']));

        $this->simulate_query_vars($submission_args);

        $submission->populate_from_query($submission_args, $files_args);
        $submission->do_action();

        return $submission;
    }

    /**
     * Do an 'update' action on $old_submission, changing some of the
     * values, as specified in $new_query_args.
     */
    function update_submission($old_submission, $new_query_args, $new_files_args = array()) {
        $args = array(
            'submission_action' => 'update',
            'submission_event_id' => $old_submission->event_id(),
            'event_editcode' => $old_submission->db_editcode(),
        );
    
        // Keep all the old event_args values.
        // Unlike daily_args [see below], $old_submission->event_args() is always instantiated.
        $old_event_args = $old_submission->event_args();
        foreach ($old_event_args as $field_name => $value) {
            // The field names we got from $old_submission need to have
            // 'event_' prepended.
            $query_field_name = 'event_' . $field_name;
            $args[$query_field_name] = $value;
        }

        // We can't use $old_submission->daily_args() to get the caldaily args, because
        // if $old_submission was loaded with load_submission_for_edit(), they're not
        // instantiated. Load from the database.
        $daylist = dailystatus($old_submission->event_id());
        foreach ($daylist as $day) {
            $args['event_newsflash' . $day['suffix']] = $day['newsflash'];
            // Use status not eventstatus, to get the long-form text of the status,
            // like the web form submits.
            $args['event_status'    . $day['suffix']] = $day['status'];
        }

        // Overwrite some of the old values with new values.
        foreach ($new_query_args as $query_field_name => $value) {
            $args[$query_field_name] = $value;
        }

        $this->simulate_query_vars($args);

        $new_submission = new BfcEventSubmissionForTest();
        $new_submission->populate_from_query($args, $new_files_args);
        $new_submission->do_action();

        return $new_submission;
    }

    // Return filename & mime type of one of the dummy images
    function dummy_image_info($image) {
        switch ($image) {
            case 'png-1':
                return array(
                    'name' => 'dummy-event-image-1.png',
                    'mime-type' => 'image/png',
                             );
                
            case 'jpg-1':
                return array(
                    'name' => 'dummy-event-image-1.jpg',
                    'mime-type' => 'image/jpeg',
                             );

            default:
                die();
                break;
        }
    }

    // Make a copy of the $_FILES array, suitable for passing into
    // the BfcEventSubmission.
    function make_files_args($image = 'png-1') {
        $image_info = $this->dummy_image_info($image);

        // Make a temp copy of the dummy event image
        $temp_filename = tempnam(sys_get_temp_dir(), 'bfc-unit-test-');
        $original_filename = __DIR__ . '/' . $image_info['name'];
        $result = copy($original_filename, $temp_filename);
        if (!$result) {
            die("Can't copy $original_filename to $temp_filename");
        }

        // Fake a copy of the $_FILES array.
        return array(
            'event_image' => array(
                'error' => UPLOAD_ERR_OK,
                'name'  => $image_info['name'],
                'tmp_name' => $temp_filename,
                'type' => $image_info['mime-type'],
                'size' => filesize($original_filename),
            ),
        );
    }

    // Return width of one of the dummy images
    function imagewidth($image) {
        $info = $this->dummy_image_info($image);
        list($imagewidth, $imageheight) = getimagesize(__DIR__ . '/' . $info['name']);
        return $imagewidth;
    }

    // Return height of one of the dummy images
    function imageheight($image) {
        $info = $this->dummy_image_info($image);
        list($imagewidth, $imageheight) = getimagesize(__DIR__ . '/' . $info['name']);
        return $imageheight;
    }

    // Make arguments for newsflash and status
    function make_date_args($dates) {
        $dayinfo = repeatdates($dates);
        $this->assertNotEquals($dayinfo['datestype'], 'error');

        $args = array();
        foreach ($dayinfo['daylist'] as $day) {
            $args['event_newsflash' . $day['suffix']] = '';
            $args['event_status'    . $day['suffix']] = 'Added';
        }

        return $args;
    }

    /**
     * Simulate an 'edit' action on an existing event.
     */
    function load_submission_for_edit($event_id) {
        $submission = new BfcEventSubmissionForTest;
        $submission_args = array(
            'submission_action' => 'edit',
            'submission_event_id' => $event_id,
            'event_editcode' => bfc_get_editcode_for_event($event_id),
        );
        $submission->populate_from_query($submission_args, array());
        $submission->do_action();

        return $submission;
    }

    /**
     * Delete an existing event
     */
    function delete_event($event_id, $editcode) {
        $del_submission = new BfcEventSubmissionForTest;
        $del_submission_args = array(
            'submission_action' => 'delete',
            'submission_event_id' => $event_id,
            'event_editcode' => $editcode,
           );

        $this->simulate_query_vars($del_submission_args);
        $del_submission->populate_from_query($del_submission_args, array());
        $del_submission->do_action();
    }

    /*
     * Produce an event and its exception, for use in other tests.
     */
    function make_event_and_exception() {
        $main_event = $this->make_valid_submission(array(
            // No title -- let make_valid_submission() generate one.
            'event_dates' => 'August 5 - 10',
            'event_audience' => 'G',
            'event_descr' => "My event is so rad!\n\nYou'll have a great time!",
            'event_hidephone'  => 'Y',
            'event_contact'    => 'Smoke signals',
            'event_phone'      => '555-FILM',
            'event_eventtime'  => '07:00:00',
            'event_eventduraton' => '120',
        ));
        $main_event = $this->update_submission($main_event, array(), $this->make_files_args());
        
        $main_event = $this->update_submission($main_event, array(
            'event_statusAug5' => 'Exception',
        ));

        $exceptions = $main_event->get_exceptions();

        $this->assertEquals(1, count($exceptions));
        $exception = $this->load_submission_for_edit($exceptions[0]['exceptionid']);

        return array($main_event, $exception);
    }
}    
    