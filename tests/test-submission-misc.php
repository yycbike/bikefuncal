<?php
namespace bike_fun_cal;
require_once('test-case.php');

/**
 * Test various things that don't have better homes.
 */
class TestSubmissionMisc extends BfcTestCase {
    function test_valid_submission_is_valid() {
        $valid_submission = $this->make_valid_submission();
        $this->assertTrue($valid_submission->is_valid());
        $this->assertTrue($valid_submission->has_event_id());
    }

}

?>