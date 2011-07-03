<?php
class TestSubmissionWordPressPost extends BfcTestCase {
    function setUp() {
        parent::setUp();
        # Do stuff here
        bfc_install();
    }

    function tearDown() {
        # Do stuff here
        parent::tearDown();
    }

    function test_post_is_created() {
        $submission = $this->make_valid_submission();

        $this->assertTrue($submission->has_wordpress_id());

        $post = get_post($submission->wordpress_id());
        $this->assertEquals($submission->title(),
                            $post->post_title);
    }

    # The wp_query object won't look up posts by title,
    # so we have to wrap our own function to query
    # the database directly.
    function query_wordpress_post_by_title($title) {
        global $wpdb;
        
        $posts_table_name = $wpdb->prefix . "posts";
        $sql = $wpdb->prepare("SELECT * FROM ${posts_table_name} " .
                              "WHERE post_title=%s",
                              $title);

        return $wpdb->get_results($sql, ARRAY_A);
    }

    # @@@ Break this up into several test cases
    function test_changing_post_title() {
        $submission = $this->make_valid_submission();

        # Make sure there's only one post with this title
        $old_title = $submission->title();
        $posts = $this->query_wordpress_post_by_title($old_title);
        $this->assertEquals(count($posts), 1);

        # Make sure that one post has the WordPress ID we think it does
        $old_wordpress_id = $submission->wordpress_id();
        $this->assertEquals($posts[0]['ID'],
                            $old_wordpress_id);

        # Now edit the post to change the title
        $new_title = $submission->title() . " 2";
        $update_args = array(
            'submission_action' => 'update',
            'submission_event_id' => $submission->event_id(),
            'event_editcode' => $submission->editcode(),
            'event_dates' => $submission->dates(),
            'event_title' => $new_title,
            );
        $new_submission = new BfcEventSubmission;
        $new_submission->populate_from_query($update_args, array());
        $new_submission->do_action();
        $new_submission->print_errors();
        $this->assertTrue($new_submission->is_valid());

        # Make sure there's no post with the old title
        $posts = $this->query_wordpress_post_by_title($old_title);
        $this->assertEquals(count($posts), 0);

        # Make sure there's only one post with the new title
        $posts = $this->query_wordpress_post_by_title($new_title);
        $this->assertEquals(count($posts), 1);

        # Make sure the new post has the old WordPress ID
        $this->assertEquals($posts[0]['ID'],
                            $old_wordpress_id);

        # Make sure the new submission has the same WordPress ID
        $this->assertEquals($new_submission->wordpress_id(),
                            $posts[0]['ID']);
    }
}
?>