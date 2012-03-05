<?php
require_once('test-case.php');

/**
 * Tests about when an event occurs
 */
class TestSubmissionEventOccurs extends BfcTestCase {
    // Festival start & end
    protected $festival_start_sql = '2012-06-02';
    protected $festival_end_sql   = '2012-06-19';

    // start & end, in the format the date parser expects
    protected $festival_start = 'June 2';
    protected $festival_end   = 'June 19';

    // 1 day before & after festival
    protected $festival_before = 'June 1';
    protected $festival_after = 'June 20';

    protected $festival_middle = 'June 13';

    function setUp() {
        parent::setUp();

        update_option('bfc_festival_start_date', $this->festival_start_sql);
        update_option('bfc_festival_end_date',   $this->festival_end_sql);
    }

    /* Things to test:
       - Default for new events is "during festival"
       - Value is set for edited events (once & repeating)
       - "repeating" includes recurring, scattered, and consecutive
     */

    // action = new, default = occurs once
    function test_new_occurs_once() {
        $event = new BfcEventSubmissionForTest();
        $event->populate_from_query(array(), array());
        $event->do_action();

        $this->assertEquals('new', $event->current_action());
        $this->assertTrue($event->event_occurs('once'));
        $this->assertFalse($event->event_occurs('multiple'));
    }

    // Test when action = edit, single day
    function test_edit_occurs_once() {
        $submission_create = $this->make_valid_submission();
        $submission_edit = $this->load_submission_for_edit($submission_create->event_id());

        $this->assertEquals('edit', $submission_edit->current_action());
        $this->assertTrue($submission_edit->event_occurs('once'));
        $this->assertFalse($submission_edit->event_occurs('multiple'));
    }

    // Test when action = edit, multiple dates
    function test_edit_occurs_multiple() {
        $submission_create = $this->make_valid_submission(array(
            'event_dates' => 'Every Monday in July',
        ));
        $submission_edit = $this->load_submission_for_edit($submission_create->event_id());

        $this->assertEquals('edit', $submission_edit->current_action());
        $this->assertTrue($submission_edit->event_occurs('multiple'));
        $this->assertFalse($submission_edit->event_occurs('once'));
    }

    function test_new_is_during_festival() {
        $event = new BfcEventSubmissionForTest();
        $event->populate_from_query(array(), array());
        $event->do_action();

        $this->assertEquals('new', $event->current_action());
        $this->assertTrue($event->event_during_festival());
    }

    function test_day_before_festival() {
        $submission_create = $this->make_valid_submission(array(
            'event_dates' => $this->festival_before,
        ));
        $submission_edit = $this->load_submission_for_edit($submission_create->event_id());

        $this->assertEquals('edit', $submission_edit->current_action());
        $this->assertFalse($submission_edit->event_during_festival());
    }

    function test_first_day_of_festival() {
        $submission_create = $this->make_valid_submission(array(
            'event_dates' => $this->festival_start,
        ));
        $submission_edit = $this->load_submission_for_edit($submission_create->event_id());

        $this->assertEquals('edit', $submission_edit->current_action());
        $this->assertTrue($submission_edit->event_during_festival());
    }

    function test_middle_of_festival() {
        $submission_create = $this->make_valid_submission(array(
            'event_dates' => $this->festival_middle,
        ));
        $submission_edit = $this->load_submission_for_edit($submission_create->event_id());

        $this->assertEquals('edit', $submission_edit->current_action());
        $this->assertTrue($submission_edit->event_during_festival());
    }

    function test_last_day_of_festival() {
        $submission_create = $this->make_valid_submission(array(
            'event_dates' => $this->festival_end,
        ));
        $submission_edit = $this->load_submission_for_edit($submission_create->event_id());

        $this->assertEquals('edit', $submission_edit->current_action());
        $this->assertTrue($submission_edit->event_during_festival());
    }

    function test_day_after_festival() {
        $submission_create = $this->make_valid_submission(array(
            'event_dates' => $this->festival_after,
        ));
        $submission_edit = $this->load_submission_for_edit($submission_create->event_id());

        $this->assertEquals('edit', $submission_edit->current_action());
        $this->assertFalse($submission_edit->event_during_festival());
    }
}
?>