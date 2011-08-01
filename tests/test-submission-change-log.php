<?php
namespace bike_fun_cal;
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

    // @@@ Make a test of new exceptions

    // @@@ Make tests for changing the image
}
?>