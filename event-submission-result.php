<?php
# Print the results of submitting an event
# 
# $event_submission -- A BfcEventSubmission object.
function bfc_print_event_submission_result($event_submission) {

    $edit_url = bfc_get_edit_url_for_event($event_submission->event_id(),
                                           $event_submission->editcode());

?>


<div class="event-updated">

<h3>Event Saved!</h3>
<p>
Your changes have been saved.
</p>

<p>
To edit this event in the future, go to this URL:
<br>

<a href="<?php print $edit_url; ?>">
<?php print $edit_url; ?>
</a>

</p>

</div>


<?php
}
?>
