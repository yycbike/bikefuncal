<?php
require_once('test-case.php');
/**
 * Test the BfcEventSubmission's file attachments.
 */
class TestSubmissionImage extends BfcTestCase {
    /**
     * By default, events have no image
     */
    function test_new_event_has_no_image() {
        $event = $this->make_valid_submission();
        
        $this->assertFalse($event->has_image());
        $this->assertFalse($event->has_imagewidth());
        $this->assertFalse($event->has_imageheight());
    }

    /**
     * Add an image when first creating an event
     */
    function test_add_image_on_create() {
        $files_args = $this->make_files_args('png-1');
        // Don't pass in an image action, because there's no prior image.
        $event = $this->make_valid_submission(array(), $files_args);

        $this->assertTrue($event->has_image());
        $this->assertTrue($event->has_imagewidth());
        $this->assertTrue($event->has_imageheight());

        $this->assertEquals('png', pathinfo($event->image(), PATHINFO_EXTENSION));
        $this->assertEquals($event->imagewidth(),  $this->imagewidth('png-1'));
        $this->assertEquals($event->imageheight(), $this->imageheight('png-1'));
    }

    /**
     * Add an image when updating an existing, image-less event
     */
    function test_add_image_on_update() {
        $event = $this->make_valid_submission();

        $files_args = $this->make_files_args('png-1');
        // Don't pass in an image action, because there's no prior image.
        $event = $this->update_submission($event, array(), $files_args);
        
        $this->assertTrue($event->has_image());
        $this->assertTrue($event->has_imagewidth());
        $this->assertTrue($event->has_imageheight());

        $this->assertEquals('png', pathinfo($event->image(), PATHINFO_EXTENSION));
        $this->assertEquals($event->imagewidth(),  $this->imagewidth('png-1'));
        $this->assertEquals($event->imageheight(), $this->imageheight('png-1'));
    }

    /**
     * Change an image attached to an event
     */
    function test_change_image() {
        $event1 = $this->make_valid_submission(array(), $this->make_files_args('png-1'));

        // image action = change, because event already has an image
        $query_args = array('submission_image_action' => 'change');
        $event2 = $this->update_submission($event1, $query_args, $this->make_files_args('jpg-1'));

        $this->assertTrue($event2->has_image());
        $this->assertEquals('jpg', pathinfo($event2->image(), PATHINFO_EXTENSION));
        $this->assertEquals($event2->imagewidth(),  $this->imagewidth('jpg-1'));
        $this->assertEquals($event2->imageheight(), $this->imageheight('jpg-1'));
    }

    function test_delete_image() {
        $event1 = $this->make_valid_submission(array(), $this->make_files_args('png-1'));

        $query_args = array('submission_image_action' => 'delete');
        $event2 = $this->update_submission($event1, $query_args, array());

        $this->assertEquals('', $event2->image());
        $this->assertEquals(0,  $event2->imagewidth());
        $this->assertEquals(0,  $event2->imageheight());
    }

    /**
     * Ensure that an image action of keep doesn't change the image
     */
    function test_keep_image() {
        $event1 = $this->make_valid_submission(array(), $this->make_files_args('png-1'));
        $query_args = array('submission_image_action' => 'keep');
        $event2 = $this->update_submission($event1, $query_args, array());

        $this->assertEquals($event1->image(), $event2->image());
        $this->assertEquals($event1->imagewidth(), $event2->imagewidth());
        $this->assertEquals($event1->imageheight(), $event2->imageheight());
    }
}

?>