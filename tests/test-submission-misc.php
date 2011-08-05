<?php
namespace bike_fun_cal;
require_once('test-case.php');

/**
 * Test various things that don't have better homes.
 */
class TestSubmissionMisc extends BfcTestCase {
    /**
     * Test that make_valid_submission() really makes a valid submission.
     */
    function test_valid_submission_is_valid() {
        $valid_submission = $this->make_valid_submission();
        $this->assertTrue($valid_submission->is_valid());
        $this->assertTrue($valid_submission->has_event_id());
    }

    function test_valid_url_with_http() {
        $event = $this->make_valid_submission(array('event_weburl' => 'http://www.pedalpundit.blogspot.com'));
        $this->assertTrue($event->is_valid());
    }

    function test_valid_url_with_https() {
        $event = $this->make_valid_submission(array('event_weburl' => 'https://www.facebook.com'));
        $this->assertTrue($event->is_valid());
    }

    function test_valid_url_without_http() {
        $event = $this->make_valid_submission(array('event_weburl' => 'www.pedalpundit.blogspot.com'));
        $this->assertTrue($event->is_valid());
        // Code will prefix http:// automatically
        $this->assertStringStartsWith('http://', $event->weburl());
    }

    function test_javascript_url() {
        $event = $this->make_valid_submission(array('event_weburl' => 'javascript:alert()'));
        $this->assertFalse($event->is_valid());
    }

    function test_file_url() {
        $event = $this->make_valid_submission(array('event_weburl' => 'file://stuff'));
        $this->assertFalse($event->is_valid());
    }
}

?>