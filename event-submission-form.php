<?php

# Output a line for the time selector
function generatetime($event_submission, $time, $label)
{
    $formatted = hmmpm($time);

    print "<option value=\"$time\" ";
    $event_submission->print_selected_for_eventtime($time);
    print ">$formatted $label</option>\n";
}


# Print the event submission/editing form
# 
# $event_submission -- A BfcEventSubmission object.
function bfc_print_event_submission_form($event_submission) {
?>

    <div class="new-event-form">

    <?php if (!$event_submission->is_valid()) { ?>
    <div class="error-messages">
      <h3>Oops! Please fix these problems</h3>
      <?php $event_submission->print_errors() ?>
    </div>                                         
    <?php } # endif -- is valid ?>


    <form action="<?php print get_permalink(); # go back to this form when submitting ?>"
          method="post"
          enctype="multipart/form-data"
          id="event-submission-form"
          >

    <h3>What</h3>
    <div class="new-event-category">

    <h4>Event Title</h4>
    <input type="text" name="event_title" <?php $event_submission->print_title(); ?> >

    <h4>Tiny Title</h4>
    <input type="text" name="event_tinytitle" <?php $event_submission->print_tinytitle(); ?>>

    <h4>Audience</h4>
    <div>
      <input type="radio" name="event_audience"
             <?php $event_submission->print_checked_for_family_audience() ?>
             id="audience_family" value="F">
      <label for="audience_family">Family-Friendly</label>
    </div>
    <div>
      <input type="radio" name="event_audience"
             <?php $event_submission->print_checked_for_general_audience() ?>
             id="audience_general" value="G">
      <label for="audience_general">General</label>
    </div>
    <div>
      <input type="radio" name="event_audience"
             <?php $event_submission->print_checked_for_adult_audience() ?>
             id="audience_adult" value="A">
      <label for="audience_adult">19+ only</label>
    </div>

    <h4>Description</h4>
    <textarea name="event_descr"><?php $event_submission->print_descr()?></textarea>

    </div>

    <h3>Where</h3>
    <div class="new-event-category">
    <h4>Venue Name</h4>
      <input type="text" id="event_locname" name="event_locname"
             <?php $event_submission->print_locname()?>>

    <h4>Address</h4>
    <input type="text" id="event_address" name="event_address" 
           <?php $event_submission->print_address()?>>

    <h4>Location Details</h4>
    <input type="text" name="event_locdetails" <?php $event_submission->print_locdetails()?>>

    </div>

    <h3>When</h3>
    <div class="new-event-category">

    <h4>Date(s)</h4>
    <input type="text"
           id="event_dates"
           name="event_dates" <?php $event_submission->print_dates(); ?>>

    <p>
    Enter something like <em>July 15</em> or <em>Every Thursday</em>.
    Dates like 2011-07-15 do not work.
    </p>

    <div id="datelist"></div>

    <h4>Time</h4>

	<select name="event_eventtime" id="event_eventtime">         
	  <option value="">Choose a time</option>
	  <?php
	    generatetime($event_submission, "00:30:00", "Just after midnight");
	    generatetime($event_submission, "01:00:00", "");
	    generatetime($event_submission, "01:30:00", "");
	    generatetime($event_submission, "02:00:00", "");
	    generatetime($event_submission, "02:30:00", "");
	    generatetime($event_submission, "03:00:00", "");
	    generatetime($event_submission, "03:30:00", "");
	    generatetime($event_submission, "04:00:00", "");
	    generatetime($event_submission, "04:30:00", "");
	    generatetime($event_submission, "05:00:00", "");
	    generatetime($event_submission, "05:30:00", "Early morning");
	    generatetime($event_submission, "05:45:00", "");
	    generatetime($event_submission, "06:00:00", "");
	    generatetime($event_submission, "06:15:00", "");
	    generatetime($event_submission, "06:30:00", "");
	    generatetime($event_submission, "06:45:00", "");
	    generatetime($event_submission, "07:00:00", "");
	    generatetime($event_submission, "07:15:00", "");
	    generatetime($event_submission, "07:30:00", "Mid-morning");
	    generatetime($event_submission, "07:45:00", "");
	    generatetime($event_submission, "08:00:00", "");
	    generatetime($event_submission, "08:15:00", "");
	    generatetime($event_submission, "08:30:00", "");
	    generatetime($event_submission, "08:45:00", "");
	    generatetime($event_submission, "09:00:00", "");
	    generatetime($event_submission, "09:15:00", "");
	    generatetime($event_submission, "09:30:00", "");
	    generatetime($event_submission, "09:45:00", "");
	    generatetime($event_submission, "10:00:00", "Late morning");
	    generatetime($event_submission, "10:15:00", "");
	    generatetime($event_submission, "10:30:00", "");
	    generatetime($event_submission, "10:45:00", "");
	    generatetime($event_submission, "11:00:00", "");
	    generatetime($event_submission, "11:15:00", "");
	    generatetime($event_submission, "11:30:00", "");
	    generatetime($event_submission, "11:45:00", "");
	    generatetime($event_submission, "12:00:00", "Noon");
	    generatetime($event_submission, "12:15:00", "");
	    generatetime($event_submission, "12:30:00", "");
	    generatetime($event_submission, "12:45:00", "");
	    generatetime($event_submission, "13:00:00", "");
	    generatetime($event_submission, "13:15:00", "");
	    generatetime($event_submission, "13:30:00", "");
	    generatetime($event_submission, "13:45:00", "");
	    generatetime($event_submission, "14:00:00", "Early Afternoon");
	    generatetime($event_submission, "14:15:00", "");
	    generatetime($event_submission, "14:30:00", "");
	    generatetime($event_submission, "14:45:00", "");
	    generatetime($event_submission, "15:00:00", "");
	    generatetime($event_submission, "15:15:00", "");
	    generatetime($event_submission, "15:30:00", "");
	    generatetime($event_submission, "15:45:00", "");
	    generatetime($event_submission, "16:00:00", "Late Afternoon");
	    generatetime($event_submission, "16:15:00", "");
	    generatetime($event_submission, "16:30:00", "");
	    generatetime($event_submission, "16:45:00", "");
	    generatetime($event_submission, "17:00:00", "");
	    generatetime($event_submission, "17:15:00", "");
	    generatetime($event_submission, "17:30:00", "");
	    generatetime($event_submission, "17:45:00", "");
	    generatetime($event_submission, "18:00:00", "Early Evening");
	    generatetime($event_submission, "18:15:00", "");
	    generatetime($event_submission, "18:30:00", "");
	    generatetime($event_submission, "18:45:00", "");
	    generatetime($event_submission, "19:00:00", "");
	    generatetime($event_submission, "19:15:00", "");
	    generatetime($event_submission, "19:30:00", "");
	    generatetime($event_submission, "19:45:00", "");
	    generatetime($event_submission, "20:00:00", "Mid-Evening");
	    generatetime($event_submission, "20:15:00", "");
	    generatetime($event_submission, "20:30:00", "");
	    generatetime($event_submission, "20:45:00", "");
	    generatetime($event_submission, "21:00:00", "");
	    generatetime($event_submission, "21:15:00", "");
	    generatetime($event_submission, "21:30:00", "");
	    generatetime($event_submission, "21:45:00", "");
	    generatetime($event_submission, "22:00:00", "Late Evening");
	    generatetime($event_submission, "22:15:00", "");
	    generatetime($event_submission, "22:30:00", "");
	    generatetime($event_submission, "22:45:00", "");
	    generatetime($event_submission, "23:00:00", "");
	    generatetime($event_submission, "23:15:00", "");
	    generatetime($event_submission, "23:30:00", "");
	    generatetime($event_submission, "23:45:00", "");
	    generatetime($event_submission, "23:59:00", "Midnight-ish");
	  ?>
	</select>

    <h4>Duration</h4>
    <select name="event_eventduration" id="event_eventduration">
        <option value="0" <?php $event_submission->print_selected_for_duration("0") ?> >Unspecified</option>
        <option value="30" <?php $event_submission->print_selected_for_duration("30") ?> >30 minutes</option>
        <option value="60" <?php $event_submission->print_selected_for_duration("60") ?> >60 minutes</option>
        <option value="90" <?php $event_submission->print_selected_for_duration("90") ?> >90 minutes</option>
        <option value="120" <?php $event_submission->print_selected_for_duration("120") ?> >2 hours</option>
        <option value="150" <?php $event_submission->print_selected_for_duration("150") ?> >2.5 hours</option>
        <option value="180" <?php $event_submission->print_selected_for_duration("180") ?> >3 hours</option>
        <option value="240" <?php $event_submission->print_selected_for_duration("240") ?> >4 hours</option>
        <option value="300" <?php $event_submission->print_selected_for_duration("300") ?> >5 hours</option>
        <option value="360" <?php $event_submission->print_selected_for_duration("360") ?> >6 hours</option>
        <option value="420" <?php $event_submission->print_selected_for_duration("420") ?> >7 hours</option>
        <option value="480" <?php $event_submission->print_selected_for_duration("480") ?> >8 hours</option>
        <option value="600" <?php $event_submission->print_selected_for_duration("600") ?> >10 hours</option>
        <option value="720" <?php $event_submission->print_selected_for_duration("720") ?> >12 hours</option>
    </select>        

    <h4>Time Details</h4>
    <input type="text" name="event_timedetails" <?php $event_submission->print_timedetails(); ?>>

    </div>

    <h3>Who</h3>
    <div class="new-event-category">

    <h4>Your Name</h4>
    <input type="text" name="event_name" <?php $event_submission->print_name(); ?>>

    <h4>Your Phone Number</h4>
    <input type="text" name="event_phone" <?php $event_submission->print_phone(); ?>>
    <p>
    <input type="checkbox" name="event_hidephone" value="Y"
           id=event_hidephone
           <?php $event_submission->print_checked_for_hidephone() ?>
           >
      <label for=event_hidephone>         
        Don't publish my phone number online
      </label>
    </p>

    <h4>Your E-Mail Address</h4>
    <input type="text" name="event_email" <?php $event_submission->print_email(); ?>>

    <!-- The old code has a checkbox here about sending forum e-mails
         to this address. We can turn that on later, when we add forums. -->
    
    <p>
    <input type="checkbox" name="event_hideemail" value="Y"
           id=event_hideemail
           <?php $event_submission->print_checked_for_hideemail() ?>
           >
      <label for=event_hideemail>
        Don't publish my e-mail address online
      </label>
    </p>
    
    <h4>Your Web Site's URL</h4>
    <input type="text" name="event_weburl" <?php $event_submission->print_weburl(); ?>>

    <h4>Your Web Site's Name</h4>
    <input type="text" name="event_webname" <?php $event_submission->print_webname(); ?>>

    <h4>Other Contact Info</h4>
    <input type="text" name="event_contact" <?php $event_submission->print_contact(); ?>>
    <p>
    <input type=checkbox id=event_hidecontact name=event_hidecontact value=Y
           <?php $event_submission->print_checked_for_hidecontact() ?>
           >

      <label for=event_hidecontact>
        Do not include this in the online calendar
      </label>
    </p>

    </div>

    <h3>Image</h3>
    <div class="new-event-category">
    Image file:
    <input type="file" name="event_image">
    </div>


    <?php
    if ($event_submission->has_event_id()) {
    ?>
        <input type="hidden" id="calform_event_id" name="calform_event_id"
            value="<?php print $event_submission->event_id(); ?>">
    <?php
    } # end if
    ?>


    <?php
    if ($event_submission->has_editcode()) {
    ?>
        <input type="hidden" id="event_editcode" name="event_editcode"
            value="<?php print $event_submission->editcode(); ?>">
    <?php
    } # end if
    ?>


    <input type="submit" id="calform_action" name="calform_action"
         value="<?php print $event_submission->next_action(); ?>">

    <?php
    if ($event_submission->has_delete()) {
    ?>
        <input type="submit" id="delete_button" name="calform_action"
            value="delete">
    <?php
    } # end if
    ?>    


    <h2>Preview</h2>
    <div id="preview">
    
    </div>         
         
    </div>
         
<?php
}         
?>