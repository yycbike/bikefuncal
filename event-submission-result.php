<?php
namespace bike_fun_cal;

# Print the results of submitting an event
# 
# $event_submission -- A BfcEventSubmission object.
function print_event_submission_result($event_submission) {

$edit_url = get_edit_url_for_event($event_submission->event_id(),
                                       $event_submission->editcode());

$permalink_url = get_permalink($event_submission->wordpress_id());                                       
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


<p>
The link to view your event is here. Share it with your friends!
<br>
<a href="<?php print $permalink_url; ?>">
<?php print $permalink_url;  ?>
</a>
</p>

<?php
if ($event_submission->current_action() == 'update') {
    $event_submission->print_changes();
}    

$exceptions = $event_submission->get_exceptions();
if (count($exceptions) > 0) {
    print "<p>";
    print "Here are the exceptions to this event:";
    print "</p>";

    print "<ul>";

    foreach ($exceptions as $exception) {
        print "<li>";

        $edit_url = get_edit_url_for_event($exception['exceptionid']);
        print "<a href='${edit_url}'>";
        print date("l, F j", strtotime($exception['sqldate']));
        print "</a>";

        print "</li>";
    }
}

?>

</div>


<?php
}
?>
