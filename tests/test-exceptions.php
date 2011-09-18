<?php
require_once('test-case.php');

/*
 * Tests for event exceptions.
 */
class TestExceptions extends BfcTestCase {
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
        $this->assertEquals(get_post($main_event->wordpress_id())->post_title,
                            get_post($exception->wordpress_id() )->post_title);
    }

    /* Exception doesn't share an image with the original */
    function test_exception_gets_unique_image() {
        $x = $this->make_event_and_exception();
        $main_event = $x[0];
        $exception = $x[1];

        // Both have filenames
        $this->assertGreaterThan(0, strlen($main_event->image()));
        $this->assertGreaterThan(0, strlen($exception->image()));
        
        // Filenames are different
        $this->assertNotEquals($main_event->image(), $exception->image());

        // Same width & height
        $this->assertEquals($main_event->imagewidth(), $exception->imagewidth());
        $this->assertEquals($main_event->imageheight(), $exception->imageheight());

        // Same extension
        $this->assertEquals(
            pathinfo($main_event->image(), PATHINFO_EXTENSION),
            pathinfo($exception->image(), PATHINFO_EXTENSION));
    }

    /* Exception and original have different edit codes */
    function test_exception_edit_codes() {
        $x = $this->make_event_and_exception();
        $main_event = $x[0];
        $exception = $x[1];

        // Because the action is create, user_editcode is unset. Thus is_editcode_valid() will fail.
        // So don't call it.
        $this->assertNotEquals($main_event->db_editcode(), $exception->db_editcode());
    }

    /* Changing exception doesn't change original */
    function test_change_exception_not_original() {
        $x = $this->make_event_and_exception();
        $main_event = $x[0];
        $exception = $x[1];

        $this->assertEquals($main_event->title(), $exception->title());
        $old_title = $main_event->title();
        $new_title = str_rot13($old_title);
        $exception = $this->update_submission($exception, array('event_title' => $new_title));

        $this->assertEquals($main_event->title(), $old_title);
        $this->assertEquals($exception->title(),  $new_title);
    }

    /* Changing original doesn't change exception */
    function test_change_original_not_exception() {
        $x = $this->make_event_and_exception();
        $main_event = $x[0];
        $exception = $x[1];

        $this->assertEquals($main_event->title(), $exception->title());
        $old_title = $main_event->title();
        $new_title = str_rot13($old_title);
        $main_event = $this->update_submission($main_event, array('event_title' => $new_title));

        $this->assertEquals($main_event->title(), $new_title);
        $this->assertEquals($exception->title(),  $old_title);
    }

    /* get_exceptions() returns all the exceptions we know about */

    /* Make a 2nd exception, ensure that everything is still OK */
}

?>