<?php
require_once('test-case.php');

/* Test the BfcEventSubmission's ability to track changes
 * when updating an event.
 */
class TestSubmissionChangeLog extends BfcTestCase {
    function test_change_title() {
        $event1 = $this->make_valid_submission();

        $event2 = $this->update_submission($event1, array(
            'event_title' => $event1->title() . ' 2',
        ));

        $changes = $event2->event_args_changes();

        $this->assertEquals(1, count($changes));
        $this->assertContains('title', $changes);

        return $event2;
    }

    /**
     * @depends test_change_title
     *
     * Upon re-submit, the change log shouldn't contain an
     * entry for fields that didn't change.
     */
    function test_dont_change_title($event) {
        // update_submission() will pass the old title to
        // BfcEventSubmission->populate_from_query().
        $event = $this->update_submission($event, array());
        $changes = $event->event_args_changes();

        $this->assertEquals(0, count($changes));
    }

    function test_change_dates() {
        $event1 = $this->make_valid_submission();
        $event2 = $this->update_submission($event1, array(
            'event_dates' => 'September 30',
            'event_newsflashSep30' => '',
            'event_statusSep30' => 'Added',
        ));
        $changes = $event2->event_args_changes();

        $this->assertEquals(1, count($changes));
        $this->assertContains('dates', $changes);
    }

    function test_change_audience() {
        $event1 = $this->make_valid_submission(array(
            'event_audience' => 'A',
        ));
        $event2 = $this->update_submission($event1, array(
            'event_audience' => 'F',
        ));
        $changes = $event2->event_args_changes();

        $this->assertEquals(1, count($changes));
        $this->assertContains('audience', $changes);
    }

    /**
     * Make no changes to an event.  Ensure that nothing is flagged as
     * changing.
     */
    function test_change_nothing() {
        $event1 = $this->make_valid_submission();
        $event2 = $this->update_submission($event1, array());
        $changes = $event2->event_args_changes();

        $this->assertEquals(0, count($changes));
    }

    function test_add_newsflash() {
        $event1 = $this->make_valid_submission(array(
            'event_dates' => 'June 3-5',
        ));
        $event2 = $this->update_submission($event1, array(
            'event_newsflashJun3' => 'Dog',
            'event_newsflashJun5' => 'Pony',
        ));
        $daychanges = $event2->daily_args_changes();

        $this->assertEquals(2, count($daychanges));
        $this->assertEquals(1, count($daychanges['Jun3']));
        $this->assertEquals(1, count($daychanges['Jun5']));
        $this->assertEquals('Dog',  $daychanges['Jun3']['newsflash']);
        $this->assertEquals('Pony', $daychanges['Jun5']['newsflash']);

        return $event2;
    }

    /**
     * @depends test_add_newsflash
     */
    function test_remove_newsflash($event) {
        $event2 = $this->update_submission($event, array(
            'event_newsflashJun3' => '',
        ));
        $daychanges = $event2->daily_args_changes();

        $this->assertEquals(1, count($daychanges));
        $this->assertEquals(1, count($daychanges['Jun3']));
        $this->assertEquals('', $daychanges['Jun3']['newsflash']);
    }

    /**
     * Test adding a newsflash to a single-day event (instead of a multi-day event)
     */
    function test_add_newsflash_one_day() {
        $event = $this->make_valid_submission(array('event_dates' => 'October 8'));
        $event = $this->update_submission($event, array('event_newsflashOct8' => 'Haz newz'));

        $daychanges = $event->daily_args_changes();

        $this->assertEquals(1, count($daychanges));
        $this->assertEquals(1, count($daychanges['Oct8']));
        $this->assertEquals('Haz newz',  $daychanges['Oct8']['newsflash']);
    }
    
    function test_status_cancel() {
        $event1 = $this->make_valid_submission(array(
            'event_dates' => 'June 3-5',
        ));
        $event2 = $this->update_submission($event1, array(
            'event_statusJun3' => 'Canceled',
            'event_statusJun4' => 'Canceled',
        ));
        $daychanges = $event2->daily_args_changes();

        $this->assertEquals(2, count($daychanges));
        $this->assertEquals(1, count($daychanges['Jun3']));
        $this->assertEquals(1, count($daychanges['Jun4']));
        $this->assertEquals('Canceled', $daychanges['Jun3']['status']);
        $this->assertEquals('Canceled', $daychanges['Jun4']['status']);

        return $event2;
    }

    /**
     * @depends test_status_cancel
     */
    function test_status_as_scheduled($event2) {
        $event3 = $this->update_submission($event2, array(
            'event_statusJun4' => 'As Scheduled',
        ));
        $daychanges = $event3->daily_args_changes();

        $this->assertEquals(1, count($daychanges));
        $this->assertEquals(1, count($daychanges['Jun4']));
        $this->assertEquals('As Scheduled', $daychanges['Jun4']['status']);
    }

    /**
     * Test setting the hideemail flag. Test it because clearing it relies on default values.
     */
    function test_hideemail_set() {
        $event1 = $this->make_valid_submission();
        $event2 = $this->update_submission($event1, array('event_hideemail' => 'Y'));
        $changes = $event2->event_args_changes();

        $this->assertEquals(1, count($changes));
        $this->assertContains('hideemail', $changes);
        $this->assertEquals('1', $event2->hideemail());
        
        return $event2;
    }

    /**
     * @depends test_hideemail_set
     */
    function test_hideemail_clear($event2) {
        $event3 = $this->update_submission($event2, array('event_hideemail' => 'N'));
        $changes = $event3->event_args_changes();

        $this->assertEquals(1, count($changes));
        $this->assertContains('hideemail', $changes);
        $this->assertEquals('0', $event3->hideemail());

        return $event3;
    }

    /**
     * @depends test_hideemail_clear
     */
    function test_hideemail_no_change($event) {
        $event = $this->update_submission($event, array());
        $changes = $event->event_args_changes();

        $this->assertEquals(0, count($changes));
    }
    
    // @@@ Make a test of new exceptions

    // @@@ Make tests for changing the image
}
?>