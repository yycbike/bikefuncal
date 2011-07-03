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
        $submission = new BfcEventSubmission;
        $submission_args = array(
            'submission_action' => 'create',
            'event_dates' => 'October 8',
            'event_audience'    => 'G',
            # Generate a unique title to avoid clashing with anything else
            # in the database that might have that title.
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
    function update_submission($old_submission, $new_query_args) {
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

        $new_submission = new BfcEventSubmission();
        $new_submission->populate_from_query($args, array());
        $new_submission->do_action();

        return $new_submission;
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

    ######################################################
    # Test cases that test the functions provided in this file.
    function test_valid_submission_is_valid() {
        $valid_submission = $this->make_valid_submission();
        $this->assertTrue($valid_submission->is_valid());
        $this->assertTrue($valid_submission->has_event_id());
    }
}    
    