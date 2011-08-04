<?php
namespace bike_fun_cal;
require_once('test-case.php');

/**
 * Tests of editcodes
 */
class TestSubmissionEditcode extends BfcTestCase {
    /**
     * Create an event and ensure that it has both editcodes
     */
    function test_create_makes_both_editcodes() {
        $event = $this->make_valid_submission();

        // Make sure we have the db_editcode. (This peers into the internals of
        // the class.)
        $this->assertType('string', $event->db_editcode());
        $this->assertNotEquals(NULL, $event->db_editcode());
        $this->assertGreaterThan(0, strlen($event->db_editcode()));

        // Make sure we have the user_editcode. (This peers into the internals of
        // the class.)
        $this->assertType('string', $event->user_editcode());
        $this->assertNotEquals(NULL, $event->user_editcode());
        $this->assertGreaterThan(0, strlen($event->user_editcode()));

        // Make sure the public-facing editcode methods do the right thing
        //var_dump($event);
        //var_dump($event->is_editcode_valid());
        $this->assertTrue($event->is_editcode_valid(), 'is_editcode_valid() should be true');
        $this->assertType('string', $event->editcode());
        $this->assertNotEquals(NULL, $event->editcode());
        $this->assertGreaterThan(0, strlen($event->editcode()));

        return $event;
    }

    /**
     * @depends test_create_makes_both_editcodes
     *
     * Ensure that the editcode is present in the URL for editing an event.
     */
    function test_edit_url_includes_editcode($event) {
        $edit_url = get_edit_url_for_event($event->event_id(), $event->editcode());

        $this->assertTrue(strpos($edit_url, $event->editcode()) !== false);

        return $event;
    }

    /**
     * @depends test_create_makes_both_editcodes
     *
     * When loading an event for edit, make sure that both editcodes are successfully
     * loaded.
     */
    function test_edit_loads_editcode($created_event) {
        // load_submission_for_edit() passes in the user editcode
        $edit_event = $this->load_submission_for_edit($created_event->event_id());

        // Make sure we have the db_editcode. (This peers into the internals of
        // the class.)
        $this->assertType('string', $edit_event->db_editcode());
        $this->assertNotEquals(NULL, $edit_event->db_editcode());
        $this->assertGreaterThan(0, strlen($edit_event->db_editcode()));

        // Make sure we have the user_editcode. (This peers into the internals of
        // the class.)
        $this->assertType('string', $edit_event->user_editcode());
        $this->assertNotEquals(NULL, $edit_event->user_editcode());
        $this->assertGreaterThan(0, strlen($edit_event->user_editcode()));

        // Make sure the public-facing editcode methods do the right thing
        //var_dump($edit_event);
        //var_dump($edit_event->is_editcode_valid());
        $this->assertTrue($edit_event->is_editcode_valid(), 'is_editcode_valid() should be true');
        $this->assertType('string', $edit_event->editcode());
        $this->assertNotEquals(NULL, $edit_event->editcode());
        $this->assertGreaterThan(0, strlen($edit_event->editcode()));
    }
}

?>
