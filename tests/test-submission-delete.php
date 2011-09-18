<?php
require_once('test-case.php');
/**
 * Test deleting events
 */
class TestSubmissionDelete extends BfcTestCase {
    function count_calevent_by_id($event_id) {
        global $calevent_table_name;
        global $wpdb;
        $sql = $wpdb->prepare("SELECT COUNT(*) " .
                              "FROM ${calevent_table_name} " .
                              "WHERE id=%d",
                              $event_id);
        $results = $wpdb->get_results($sql, ARRAY_A);

        return $results[0]['COUNT(*)'];
    }
    function count_caldaily_by_id($event_id) {
        global $caldaily_table_name;
        global $wpdb;
        $sql = $wpdb->prepare("SELECT COUNT(*) " .
                              "FROM ${caldaily_table_name} " .
                              "WHERE id=%d",
                              $event_id);
        $results = $wpdb->get_results($sql, ARRAY_A);

        return $results[0]['COUNT(*)'];
    }

    function assert_event_deleted($event_id, $wordpress_id) {
        $this->assertEquals(0, $this->count_calevent_by_id($event_id));
        $this->assertEquals(0, $this->count_caldaily_by_id($event_id));

        // Assert that the WordPress page is also gone.
        $post = get_post($wordpress_id, ARRAY_A);
        $this->assertNull($post);
    }

    function test_event_delete_one_occurrence() {
        $event = $this->make_valid_submission();
        $this->delete_event($event->event_id(), $event->db_editcode());
        $this->assert_event_deleted($event->event_id(), $event->wordpress_id());
    }

    function test_event_delete_repeating() {
        $event = $this->make_valid_submission(array('event_dates' => 'August 5 - 10'));
        $this->delete_event($event->event_id(), $event->db_editcode());
        $this->assert_event_deleted($event->event_id(), $event->wordpress_id());
    }

    function test_event_delete_event_with_exception() {
        $x = $this->make_event_and_exception();
        $main_event = $x[0];
        $exception = $x[1];

        $this->delete_event($main_event->event_id(), $main_event->db_editcode());
        $this->assert_event_deleted($main_event->event_id(), $main_event->wordpress_id());

        // Exception survives the main event being deleted
        // @@@ Evan isn't sure if that's the best approach for the user.
        $this->assertEquals(1, $this->count_calevent_by_id($exception->event_id()));
        $this->assertEquals(1, $this->count_caldaily_by_id($exception->event_id()));
    }

    function test_event_delete_exception_without_main_event() {
        $x = $this->make_event_and_exception();
        $main_event = $x[0];
        $exception = $x[1];

        // Count how many instances there were before deleting the exception.
        $main_event_count = $this->count_caldaily_by_id($main_event->event_id());

        $this->delete_event($exception->event_id(), $exception->db_editcode());
        $this->assert_event_deleted($exception->event_id(), $exception->wordpress_id());

        $this->assertEquals(1, $this->count_calevent_by_id($main_event->event_id()));

        // Number of instances of the main event goes down by one, on account of
        // deleting the exception.
        $this->assertEquals($main_event_count - 1, $this->count_caldaily_by_id($main_event->event_id()));

        // Main event's image still exists (make sure the exception didn't delete it).
        $upload_dirinfo = wp_upload_dir();
        $image_filename = $upload_dirinfo['basedir'] . $main_event->image();
        $this->assertTrue(file_exists($image_filename));
        
    }

    function test_event_delete_removes_file() {
        $event = $this->make_valid_submission(array(), $this->make_files_args('png-1'));
        $upload_dirinfo = wp_upload_dir();
        $image_filename = $upload_dirinfo['basedir'] . $event->image();

        $this->assertTrue(file_exists($image_filename));
        $this->delete_event($event->event_id(), $event->db_editcode());
        $this->assertFalse(file_exists($image_filename));
    }
}
