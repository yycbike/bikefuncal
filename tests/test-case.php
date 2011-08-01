<?php
namespace bike_fun_cal;

/**
 * Generic base class for other tests.
 */
class BfcTestCase extends \WPTestCase {
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
            'event_email' => 'hi@hello.ca',

            'event_eventtime' => '19:15:00',
            );
        $submission_args = array_merge($submission_args, $extra_args);
        $submission_args = array_merge(
            $submission_args,
            $this->make_date_args($submission_args['event_dates']));

        $submission->populate_from_query($submission_args, $files_args);
        $submission->do_action();

        return $submission;
    }

    # Do an 'update' action on $old_submission, changing some of the
    # values, as specified in $new_query_args.
    function update_submission($old_submission, $new_query_args, $new_files_args = array()) {
        $old_event_args = $old_submission->event_args();
        $old_daily_args = $old_submission->daily_args();

        $args = array(
            'submission_action' => 'update',
            'submission_event_id' => $old_submission->event_id(),
            'event_editcode' => $old_submission->editcode(),
        );
    
        # Keep all the old values.
        foreach ($old_event_args as $field_name => $value) {
            # The field names we got from $old_submission need to have
            # 'event_' prepended.
            $query_field_name = 'event_' . $field_name;
            $args[$query_field_name] = $value;
        }
        foreach (array_keys($old_daily_args) as $suffix) {
            $args['event_newsflash' . $suffix] = $old_daily_args[$suffix]['newsflash'];
            $args['event_status'    . $suffix] = $old_daily_args[$suffix]['status'];
        }

        # Overwrite some of the old values with new values.
        foreach ($new_query_args as $query_field_name => $value) {
            $args[$query_field_name] = $value;
        }

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

    # Make arguments for newsflash and status
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
        );
        $submission->populate_from_query($submission_args, array());
        $submission->do_action();

        return $submission;
    }

    /********************************************************************************
     *
     * Test cases that test the functions provided in this file.
     */
    function test_valid_submission_is_valid() {
        $valid_submission = $this->make_valid_submission();
        $this->assertTrue($valid_submission->is_valid());
        $this->assertTrue($valid_submission->has_event_id());
    }
}    
    