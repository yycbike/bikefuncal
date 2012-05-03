<?php
require_once('test-case.php');

/*
 * Tests for parsing repeating events. Make sure they can run w/o spewing errors.
 *
 * Todo: Add tests for 'except'
 */
class TestRepeat extends BfcTestCase {
    function test_first_thursdays() {
        $dayinfo = repeatdates('First Thursdays');

        $this->assertEquals($dayinfo['datestype'], 'scattered');
        $this->assertEquals($dayinfo['canonical'], 'First Thursday of every month');

        // Should produce events for about every month of the year. The exact number depends on
        // when this test is run, so check to make sure we're in the ballpark.
        $this->assertGreaterThanOrEqual(11, count($dayinfo['daylist']));
        $this->assertLessThanOrEqual(13, count($dayinfo['daylist']));
    }

    function test_tuedays_and_thursdays_may_to_september() {
        $dayinfo = repeatdates('Tuesdays and Thursdays, May to September');

        $this->assertEquals($dayinfo['datestype'], 'scattered');
        $this->assertEquals($dayinfo['canonical'], 'Every Tuesday and Thursday in May - September');

        // Figure out a range of how often the event could occur, given four- or five-week months.
        $months = 5;
        $minweeks = 4;
        $maxweeks = 5;

        $min = 2 * ($months * $minweeks);
        $max = 3 * ($months * $maxweeks);

        $this->assertGreaterThanOrEqual($min, count($dayinfo['daylist']));
        $this->assertLessThanOrEqual($max, count($dayinfo['daylist']));
    }


    function test_last_fridays() {
        $dayinfo = repeatdates('Last Fridays');

        $this->assertEquals($dayinfo['datestype'], 'scattered');
        $this->assertEquals($dayinfo['canonical'], 'Last Friday of every month');

        $this->assertGreaterThanOrEqual(11, count($dayinfo['daylist']));
        $this->assertLessThanOrEqual(12, count($dayinfo['daylist']));
    }

    function test_every_monday() {
        $dayinfo = repeatdates('Every Monday');

        $this->assertEquals($dayinfo['datestype'], 'scattered');
        $this->assertEquals($dayinfo['canonical'], 'Every Monday');

        $this->assertGreaterThanOrEqual(50, count($dayinfo['daylist']));
        $this->assertLessThanOrEqual(53, count($dayinfo['daylist']));
    }

    function test_tuesdays_and_thursdays() {
        $dayinfo = repeatdates('Tuesdays and Thursdays');

        $this->assertEquals($dayinfo['datestype'], 'scattered');
        $this->assertEquals($dayinfo['canonical'], 'Every Tuesday and Thursday');

        $this->assertGreaterThanOrEqual(2 * 50, count($dayinfo['daylist']));
        $this->assertLessThanOrEqual(2 * 53, count($dayinfo['daylist']));
    }

    function test_first_thursdays_may_to_sept() {
        $dayinfo = repeatdates('First Thursdays, May to September');

        $this->assertEquals($dayinfo['datestype'], 'scattered');
        $this->assertEquals($dayinfo['canonical'], 'First Thursday in May - September');

        $this->assertGreaterThanOrEqual(5, count($dayinfo['daylist']));
        $this->assertLessThanOrEqual(6, count($dayinfo['daylist']));
    }
}

?>