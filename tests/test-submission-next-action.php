<?php
class TestSubmissionNextAction extends BfcTestCase {
    function test_default_action_is_new() {
        $submission = new BfcEventSubmission;
        $submission->populate_from_query(array(), array());

        $this->assertEquals('new', $submission->current_action());

        return $submission;
    }

    /**
     * @depends test_default_action_is_new
     */
    function test_action_after_new($submission) {
        $this->assertEquals('create', $submission->next_action());
        
        return $submission;
    }

    /**
     * Given an invalid 'create' event, ensure that the
     * action switches to 'edit', and that the next action is
     * 'create' not 'update'
     */
    function test_invalid_create_leads_to_edit() {
        $submission = new BfcEventSubmission;
        $args = array(
            'submission_action' => 'create',
            # Don't pass in any argument for dates, which
            # will cause validation to fail.
            );
        $submission->populate_from_query($args, array());
        $submission->do_action();

        $this->assertFalse($submission->is_valid());
        $this->assertEquals($submission->current_action(), 'edit');
        $this->assertEquals($submission->next_action(),    'create');
    }

    /**
     * Given an existing event, an invalid 'update' should fail and
     * return to the edit action. The next action should be 'update'.
     */
    function test_invalid_update() {
        $valid_submission = $this->make_valid_submission();
        $invalid_submission = $this->update_submission($valid_submission, array(
            /* Cause validation to fail, due to no dates */
            'event_dates' => '',
        ));                                                           

        $this->assertFalse($invalid_submission->is_valid());
        $this->assertEquals($invalid_submission->current_action(),
                            'edit');
        $this->assertEquals($invalid_submission->next_action(),
                            'update');
    }
}

?>