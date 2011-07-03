<?php
# Generic base class for other tests.
#
class BfcTestCase extends WPTestCase {
    function setUp() {
        parent::setUp();
        # Do stuff here
        bfc_install();
    }

    function tearDown() {
        # Do stuff here
        parent::tearDown();
    }

    function make_valid_submission() {
        $valid_submission = new BfcEventSubmission;
        $valid_submission_args = array(
            'submission_action' => 'create',
            'event_dates' => 'October 8',
            'event_title' => 'Title ' . uniqid(),
            );
        $valid_submission->populate_from_query(
            $valid_submission_args, array());
        $valid_submission->do_action();

        $this->assertTrue($valid_submission->is_valid());
        $this->assertTrue($valid_submission->has_event_id());

        return $valid_submission;
    }

    # The test harness gets mad if the file doesn't have any
    # tests in it. So add a test to shut it up.
    function test_avoid_warnings() {

    }
}    
    