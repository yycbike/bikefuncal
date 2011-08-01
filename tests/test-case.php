<?php
# Generic base class for other tests.
#
class BfcTestCase extends WPTestCase {
    function setUp() {
        parent::setUp();
        bfc_install();
    }

    function tearDown() {
        parent::tearDown();
    }
    
    function make_valid_submission($extra_arguments = array()) {
        $submission = new BfcEventSubmissionForTest;
        $submission_args = array(
            'submission_action' => 'create',
            'event_dates' => 'October 8',
            'event_audience'    => 'G',
            // Generate a unique title to avoid clashing with anything else
            // in the database that might have that title.
            'event_title' => 'Title ' . uniqid(),

            'event_eventtime' => '19:15:00',
            );
        $submission_args = array_merge($submission_args, $extra_arguments);
        $submission_args = array_merge(
            $submission_args,
            $this->make_date_args($submission_args['event_dates']));

        $submission->populate_from_query($submission_args, array());
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

    function update_submission_with_file($submission) {
        // Make a temp copy of the dummy event image
        $temp_filename = tempnam(sys_get_temp_dir(), 'bfc-unit-test-');
        $original_filename = __DIR__ . '/dummy-event-image.png';
        $result = copy($original_filename, $temp_filename);
        if (!$result) {
            die("Can't copy $original_filename to $temp_filename");
        }

        // Fake a copy of the $_FILES array.
        $files_args = array(
            'event_image' => array(
                'error' => UPLOAD_ERR_OK,
                'name'  => 'dummy-event-image.png',
                'tmp_name' => $temp_filename,
                'type' => 'image/png',
                'size' => 1446,
            ),
        );
        
        return $this->update_submission($submission, array(), $files_args);
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

    ######################################################
    # Test cases that test the functions provided in this file.
    function test_valid_submission_is_valid() {
        $valid_submission = $this->make_valid_submission();
        $this->assertTrue($valid_submission->is_valid());
        $this->assertTrue($valid_submission->has_event_id());
    }
}    
    