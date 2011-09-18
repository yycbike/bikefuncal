<?php
require_once('test-case.php');

/*
 * Extend BfcEventSubmission with some methods that allow the
 * unit tests to peer inside and examine its internals. Also,
 * allow us to fake handing file uploads.
 */
class BfcEventSubmissionForTest extends BfcEventSubmission {
    // Upgrade from protected to public
    public function is_editcode_valid() {
        return parent::is_editcode_valid();
    }

    public function event_args_changes() {
        return $this->event_args_changes;
    }

    public function event_args() {
        return $this->event_args;
    }

    public function daily_args() {
        return $this->daily_args;
    }

    public function daily_args_changes() {
        return $this->daily_args_changes;
    }

    public function move_uploaded_file($from, $to) {
        return rename($from, $to);
    }

    public function db_editcode() {
        return $this->db_editcode;
    }

    public function user_editcode() {
        return $this->user_editcode;
    }
}

?>