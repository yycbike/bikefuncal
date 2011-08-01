<?php
namespace bike_fun_cal;
require_once('test-case.php');

/*
 * Tests for event exceptions.
 */
class TestExceptions extends BfcTestCase {
    /*
     * Produce an event and its exception, for use in other tests.
     */
    function make_event_and_exception() {
        $main_event = $this->make_valid_submission(array(
            // No title -- let make_valid_submission() generate one.
            'event_dates' => 'August 5 - 10',
            'event_audience' => 'G',
            'event_descr' => "My event is so rad!\n\nYou'll have a great time!",
            'event_printdescr' => "My event is so rad!!!",
            'event_hidephone'  => 'Y',
            'event_contact'    => 'Smoke signals',
            'event_phone'      => '555-FILM',
            'event_eventtime'  => '07:00:00',
            'event_eventduraton' => '120',
        ));
        $main_event = $this->update_submission_with_file($main_event);
        
        $main_event = $this->update_submission($main_event, array(
            'event_statusAug5' => 'Exception',
        ));

        $exceptions = $main_event->get_exceptions();

        $this->assertEquals(1, count($exceptions));
        $exception = $this->load_submission_for_edit($exceptions[0]['exceptionid']);

        return array($main_event, $exception);
    }

    function test_exceptions_copy_fields() {
        $x = $this->make_event_and_exception();
        $main_event = $x[0];
        $exception = $x[1];

        // Check that dates match. Use regexp to ignore the day of the week.
        $this->assertRegExp('/August 5/', $exception->dates());
        // One-time event.
        $this->assertEquals('O', $exception->datestype());

        $this->assertNotEquals($main_event->id(), $exception->id());

        $this->assertEquals($main_event->title(),         $exception->title());        
        $this->assertEquals($main_event->audience(),      $exception->audience());     
        $this->assertEquals($main_event->descr(),         $exception->descr());        
        $this->assertEquals($main_event->printdescr(),    $exception->printdescr());   
        $this->assertEquals($main_event->hidephone(),     $exception->hidephone());    
        $this->assertEquals($main_event->contact(),       $exception->contact());      
        $this->assertEquals($main_event->phone(),         $exception->phone());        
        $this->assertEquals($main_event->eventtime(),     $exception->eventtime());    
        $this->assertEquals($main_event->eventduration(), $exception->eventduration());
    }

    /**
     * Exception gets its own wordpress post
     */
    function test_exception_wordpress_posts() {
        $x = $this->make_event_and_exception();
        $main_event = $x[0];
        $exception = $x[1];

        $this->assertNotEquals($main_event->wordpress_id(), $exception->wordpress_id());
        $this->assertNotEquals(get_permalink($main_event->wordpress_id()),
                               get_permalink($exception->wordpress_id()));
    }

    /* Exception doesn't share an image with the original */
    function test_exception_gets_unique_image() {
        $x = $this->make_event_and_exception();
        $main_event = $x[0];
        $exception = $x[1];

        // Different filename
        $this->assertNotEquals($main_event->image(), $exception->image());
        // Same width & height
        $this->assertEquals($main_event->imagewidth(), $exception->imagewidth());
        $this->assertEquals($main_event->imageheight(), $exception->imageheight());
    }

    /* Changing exception doesn't change original */

    /* get_exceptions() returns all the exceptions we know about */

    /* Make a 2nd exception, ensure that everything is still OK */
}

?>