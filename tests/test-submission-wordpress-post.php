<?php
require_once('test-case.php');

class TestSubmissionWordPressPost extends BfcTestCase {
    /**
     * The wp_query object won't look up posts by title,
     * so we have to wrap our own function to query
     * the database directly.
     */
    function query_wordpress_post_by_title($title) {
        global $wpdb;
        
        $posts_table_name = $wpdb->prefix . "posts";
        $sql = $wpdb->prepare("SELECT * FROM ${posts_table_name} " .
                              "WHERE post_title=%s",
                              $title);

        return $wpdb->get_results($sql, ARRAY_A);
    }


    /**
     * Make a WordPress post when creating a new bike event.
     */
    function test_post_is_created() {
        $submission = $this->make_valid_submission();

        $this->assertTrue($submission->has_wordpress_id());

        $post = get_post($submission->wordpress_id());
        $this->assertEquals($submission->title(),
                            $post->post_title);

        return $submission;                            
    }

    /**
     * @depends test_post_is_created
     *
     * Make sure the titles match between post & event
     */
    function test_titles_match($submission) {
        # Make sure there's only one post with this title
        $old_title = $submission->title();
        $posts = $this->query_wordpress_post_by_title($old_title);
        $this->assertEquals(count($posts), 1);

        # Make sure that one post has the WordPress ID we think it does
        $old_wordpress_id = $submission->wordpress_id();
        $this->assertEquals($posts[0]['ID'],
                            $old_wordpress_id);

        return $submission;
    }

    /**
     * @depends test_titles_match
     *
     */
    function test_title_change_propegates($submission) {
        $old_title = $submission->title();
        $old_wordpress_id = $submission->wordpress_id();

        # Now edit the post to change the title
        $new_title = $old_title . " 2";
        $new_submission = $this->update_submission($submission, array(
            'event_title' => $new_title,
        ));
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