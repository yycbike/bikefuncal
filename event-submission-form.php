<?php

# Output a line for the time selector
function bfc_generatetime($event_submission, $time, $label)
{
    $formatted = hmmpm($time);

    printf("<option value=\"%s\" ", esc_attr($time));
    $event_submission->print_selected_for_eventtime($time);
    printf(">%s %s</option>\n",
           esc_html($formatted), esc_html($label));
}

// Output all the lines for the time selector
function bfc_generate_all_times($event_submission) {
    bfc_generatetime($event_submission, "00:30:00", "Just after midnight");
    bfc_generatetime($event_submission, "01:00:00", "");
    bfc_generatetime($event_submission, "01:30:00", "");
    bfc_generatetime($event_submission, "02:00:00", "");
    bfc_generatetime($event_submission, "02:30:00", "");
    bfc_generatetime($event_submission, "03:00:00", "");
    bfc_generatetime($event_submission, "03:30:00", "");
    bfc_generatetime($event_submission, "04:00:00", "");
    bfc_generatetime($event_submission, "04:30:00", "");
    bfc_generatetime($event_submission, "05:00:00", "");
    bfc_generatetime($event_submission, "05:30:00", "Early morning");
    bfc_generatetime($event_submission, "05:45:00", "");
    bfc_generatetime($event_submission, "06:00:00", "");
    bfc_generatetime($event_submission, "06:15:00", "");
    bfc_generatetime($event_submission, "06:30:00", "");
    bfc_generatetime($event_submission, "06:45:00", "");
    bfc_generatetime($event_submission, "07:00:00", "");
    bfc_generatetime($event_submission, "07:15:00", "");
    bfc_generatetime($event_submission, "07:30:00", "Mid-morning");
    bfc_generatetime($event_submission, "07:45:00", "");
    bfc_generatetime($event_submission, "08:00:00", "");
    bfc_generatetime($event_submission, "08:15:00", "");
    bfc_generatetime($event_submission, "08:30:00", "");
    bfc_generatetime($event_submission, "08:45:00", "");
    bfc_generatetime($event_submission, "09:00:00", "");
    bfc_generatetime($event_submission, "09:15:00", "");
    bfc_generatetime($event_submission, "09:30:00", "");
    bfc_generatetime($event_submission, "09:45:00", "");
    bfc_generatetime($event_submission, "10:00:00", "Late morning");
    bfc_generatetime($event_submission, "10:15:00", "");
    bfc_generatetime($event_submission, "10:30:00", "");
    bfc_generatetime($event_submission, "10:45:00", "");
    bfc_generatetime($event_submission, "11:00:00", "");
    bfc_generatetime($event_submission, "11:15:00", "");
    bfc_generatetime($event_submission, "11:30:00", "");
    bfc_generatetime($event_submission, "11:45:00", "");
    bfc_generatetime($event_submission, "12:00:00", "Noon");
    bfc_generatetime($event_submission, "12:15:00", "");
    bfc_generatetime($event_submission, "12:30:00", "");
    bfc_generatetime($event_submission, "12:45:00", "");
    bfc_generatetime($event_submission, "13:00:00", "");
    bfc_generatetime($event_submission, "13:15:00", "");
    bfc_generatetime($event_submission, "13:30:00", "");
    bfc_generatetime($event_submission, "13:45:00", "");
    bfc_generatetime($event_submission, "14:00:00", "Early Afternoon");
    bfc_generatetime($event_submission, "14:15:00", "");
    bfc_generatetime($event_submission, "14:30:00", "");
    bfc_generatetime($event_submission, "14:45:00", "");
    bfc_generatetime($event_submission, "15:00:00", "");
    bfc_generatetime($event_submission, "15:15:00", "");
    bfc_generatetime($event_submission, "15:30:00", "");
    bfc_generatetime($event_submission, "15:45:00", "");
    bfc_generatetime($event_submission, "16:00:00", "Late Afternoon");
    bfc_generatetime($event_submission, "16:15:00", "");
    bfc_generatetime($event_submission, "16:30:00", "");
    bfc_generatetime($event_submission, "16:45:00", "");
    bfc_generatetime($event_submission, "17:00:00", "");
    bfc_generatetime($event_submission, "17:15:00", "");
    bfc_generatetime($event_submission, "17:30:00", "");
    bfc_generatetime($event_submission, "17:45:00", "");
    bfc_generatetime($event_submission, "18:00:00", "Early Evening");
    bfc_generatetime($event_submission, "18:15:00", "");
    bfc_generatetime($event_submission, "18:30:00", "");
    bfc_generatetime($event_submission, "18:45:00", "");
    bfc_generatetime($event_submission, "19:00:00", "");
    bfc_generatetime($event_submission, "19:15:00", "");
    bfc_generatetime($event_submission, "19:30:00", "");
    bfc_generatetime($event_submission, "19:45:00", "");
    bfc_generatetime($event_submission, "20:00:00", "Mid-Evening");
    bfc_generatetime($event_submission, "20:15:00", "");
    bfc_generatetime($event_submission, "20:30:00", "");
    bfc_generatetime($event_submission, "20:45:00", "");
    bfc_generatetime($event_submission, "21:00:00", "");
    bfc_generatetime($event_submission, "21:15:00", "");
    bfc_generatetime($event_submission, "21:30:00", "");
    bfc_generatetime($event_submission, "21:45:00", "");
    bfc_generatetime($event_submission, "22:00:00", "Late Evening");
    bfc_generatetime($event_submission, "22:15:00", "");
    bfc_generatetime($event_submission, "22:30:00", "");
    bfc_generatetime($event_submission, "22:45:00", "");
    bfc_generatetime($event_submission, "23:00:00", "");
    bfc_generatetime($event_submission, "23:15:00", "");
    bfc_generatetime($event_submission, "23:30:00", "");
    bfc_generatetime($event_submission, "23:45:00", "");
    bfc_generatetime($event_submission, "23:59:00", "Midnight-ish");

}

// Print help for the edit fields. Themes can override the help.
function bfc_event_form_help($for) {
    $help = array(
        'for' => $for,
        'content' => '',
        'class' => 'help',
    );

    // Set default values for the help.
    if ($for === 'event_descr') {
        $help['content'] = <<<END_HTML
            Be sure to mention any fees, otherwise people will assume it&apos;s free.        
END_HTML;

    }
    else if ($for === 'event_locname') {
        $help['content'] = 'e.g., "Science World" or "Arbutus Coffee Shop"';
    }
    else if ($for === 'event_address') {
        $help['content'] = 'Address or cross streets';
    }
    else if ($for === 'event_locdetails') {
        $help['content'] = 'e.g., "Meet at the gazebo" or "in the NW corner of the park"';
    }
    else if ($for === 'submission_dates_multiple') {
        $help['content'] = <<<END_HTML
          Enter the dates when your ride occurs. Examples:
          <ul>
            <li>Last Friday of every month
            <li>Mondays in June
            <li>Tuesdays and Thursdays, May to September
            <li>June 3 - 5
          </ul>
          <a id='dates_multiple_show_more'>Show more tips</a>

          <div id='dates_multiple_more_help' style="visibility:hidden;">
              blah blah blah blah blah.
          </div>
        
END_HTML;

    }
    else if ($for === 'event_eventduration') {
        $help['content'] = 'Not sure? Leave it unspecified.';
    }
    else if ($for === 'event_timedetails') {
        $help['content'] = 'e.g., "Meet at 6, ride at 7" or "Leave at 10:00 sharp!"';
    }
    else if ($for === 'event_name') {
        $help['content'] = 'Aliases are OK';
    }
    else if ($for === 'event_email') {
        $help['content'] = "We'll send you a link to edit this event. We won't spam you.";
    }
    else if ($for === 'event_hideemail') {
        $help['content'] = "It's protected against spam-harvesting robots";
    }
    else if ($for === 'event_weburl') {
        $help['content'] = 'e.g., www.your-site.ca. If you made a Facebook event, put it here.';
    }

    // Let the theme override the defaults
    $help = apply_filters('bfc_event_form_help', $help);

    if ($help['content'] !== '') {
        printf("<div class='%s'>", esc_attr($help['class']));
        // Don't call esc_html on the content, because it has HTML tags in it.
        print $help['content'];
        print '</div>';
    }
}


// Print the event submission/editing form
// 
// $event_submission -- A BfcEventSubmission object.
function bfc_print_event_submission_form($event_submission) {
?>

    <div class="new-event-form">

    <?php if (!$event_submission->is_valid()) { ?>
    <div class="error-messages">
      <h3>Oops! Please fix these problems</h3>
      <?php $event_submission->print_errors() ?>
    </div>                                         
    <?php } # endif -- is valid ?>

    <form action="<?php print esc_attr(get_permalink()); # go back to this form when submitting ?>"
          method="post"
          enctype="multipart/form-data"
          id="event-submission-form"
          >

    <h3>Describe Your Ride</h3>
    <div class="new-event-category">

    <h4>Title</h4>
    <div class="new-event-controls">
      <input type="text" name="event_title" required <?php $event_submission->print_title(); ?> >
      <?php bfc_event_form_help('event_title') ?>
    </div>

    <h4>Audience</h4>
    <div class="new-event-controls">
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
                 <label for="audience_adult">Adults (<?php print esc_html(get_option('bfc_drinking_age'))  ?>+ only)</label>
        </div>
        <?php bfc_event_form_help('event_audience') ?>
    </div>
                                                 
    <h4>Description</h4>
    <div class="new-event-controls">
      <textarea name="event_descr" required><?php $event_submission->print_descr()?></textarea>
      <?php bfc_event_form_help('event_descr') ?>
    </div>

    <h4>Image</h4>
    <div class='new-event-controls'>
        <?php if ($event_submission->has_image()) { ?>            
            <div>
            <input type='radio' name='submission_image_action'
                id='submission_image_action_keep' value='keep' checked>
            <label for='submission_image_action_keep'>Keep current image</label>
            </div>

            <div>
            <input type='radio' name='submission_image_action'
                id='submission_image_action_change' value='change'>
            <label for='submission_image_action_change'>Change to</label>
            <input type='file' size='60' name='event_image' id='event_image'>
            </div>

            <div>
            <input type='radio' name='submission_image_action'
                id='submission_image_action_delete' value='delete'>
            <label for='submission_image_action_delete'>Remove the image</label>
            </div>
        <?php } else { ?>
            <input type="file" name="event_image">
        <?php } ?>
    </div>
    
    </div><!-- .new-event-category (describe your ride) -->

    <h3>Meeting Place</h3>
    <div class="new-event-category">
    <h4>Place Name</h4>
    <div class="new-event-controls">
      <input type="text" id="event_locname" name="event_locname"
             <?php $event_submission->print_locname()?>>
      <?php bfc_event_form_help('event_locname') ?>
    </div>             

    <h4>Address</h4>
    <div class="new-event-controls">
      <input type="text" id="event_address" name="event_address" required
           <?php $event_submission->print_address()?>>
      <?php bfc_event_form_help('event_address') ?>
  </div>

    <h4>Details</h4>
    <div class="new-event-controls">
      <input type="text" name="event_locdetails" <?php $event_submission->print_locdetails()?>>
      <?php bfc_event_form_help('event_locdetails') ?>
    </div>

    </div><!-- .new-event-category (meeting place) -->

    <h3>Date & Time</h3>
    <div class="new-event-category">

    <h4>The event occurs</h4>
    <div class='new-event-controls'>
        <div>
            <input type='radio' name='submission_event_occurs'
                <?php $event_submission->print_checked_for_event_occurs('once'); ?>
                id='submission_event_occurs_once' value='once'>
            <label for='submission_event_occurs_once'>One time</label>
        </div>
        <div>
            <input type='radio' name='submission_event_occurs'
                <?php $event_submission->print_checked_for_event_occurs('multiple'); ?>
                id='submission_event_occurs_multiple' value='multiple'>
            <label for='submission_event_occurs_multiple'>More than once (repeating)</label>
        </div>
    </div><!-- new-event-controls -->

    <div id='occurs-once'>
      <h4></h4>
      <div class='new-event-controls'>
        <input type='checkbox' name='submission_event_during_festival'
               <?php $event_submission->print_checked_for_event_during_festival(); ?>
               id='submission_event_during_festival'>
        <label for='submission_event_during_festival'>
          The event occurs during <?php print esc_html(get_option('bfc_festival_name')); ?>
        </label>
      </div>

      <h4>Date</h4>
      <div class='new-event-controls'>
        <input type='text'
               name='submission_dates_once'
               id='submission_dates_once'
          <?php if ($event_submission->event_occurs('once')) $event_submission->print_dates();  ?>
        >
        <?php bfc_event_form_help('submission_dates_once') ?>
      </div><!-- .new-event-controls -->

    </div><!-- #occurs-once -->

    <div id='occurs-multiple'>
      <h4>Dates</h4>
      <div class='new-event-controls'>
        <input type="text"
               id="submission_dates_multiple"
               name="submission_dates_multiple"
               <?php if ($event_submission->event_occurs('multiple')) $event_submission->print_dates(); ?>
               >

        <?php bfc_event_form_help('submission_dates_multiple'); ?>
        <div id="datelist"></div>
      </div><!-- new-event-controls -->
    </div><!-- occurs-multiple -->

    <h4>Time</h4>
    <div class="new-event-controls">
	<select name="event_eventtime" id="event_eventtime">         
	  <option value="">Choose a time</option>
	  <?php bfc_generate_all_times($event_submission); ?>
	</select>
        <?php bfc_event_form_help('event_eventtime') ?>
    </div>
    
    <h4>Duration</h4>
    <div class="new-event-controls">
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
      <?php bfc_event_form_help('event_eventduration') ?>
    </div>

    <h4>Time Details</h4>
    <div class="new-event-controls">
        <input type="text" name="event_timedetails" <?php $event_submission->print_timedetails(); ?>>
    </div>
    </div><!-- .new-event-category (date & time) -->

    <h3>Who</h3>
    <div class="new-event-category">

    <h4>Your Name</h4>
    <div class="new-event-controls">
      <input type="text" name="event_name" required <?php $event_submission->print_name(); ?>>
      <?php bfc_event_form_help('event_name') ?>
    </div>

    <h4>E-Mail</h4>
    <div class='new-event-controls'>
      <input type="email" name="event_email" required <?php $event_submission->print_email(); ?>>
      <?php bfc_event_form_help('event_email') ?>

       <!-- @@@ The old code has a checkbox here about sending forum e-mails
            to this address. We can turn that on later, when we add forums. -->

        <p>
        <input type="checkbox" name="event_hideemail" value="Y"
               id=event_hideemail
               <?php $event_submission->print_checked_for_hideemail() ?>
               >
          <label for=event_hideemail>
            Don't publish my e-mail address online
          </label>
          <?php bfc_event_form_help('event_hideemail'); ?>
        </p>
    </div>
    
    <h4>Web Site</h4>
    <div class='new-event-controls'>
      <input type="url" name="event_weburl" <?php $event_submission->print_weburl(); ?>>
      <?php bfc_event_form_help('event_weburl') ?>
    </div>

    <h4>Phone Number</h4>
    <div class="new-event-controls">
      <input type="text" name="event_phone" <?php $event_submission->print_phone(); ?>>
      <?php bfc_event_form_help('event_phone') ?>
    </div>

    <h4>Other Info</h4>
    <div class="new-event-controls">
      <input type="text" name="event_contact" <?php $event_submission->print_contact(); ?>>
      <?php bfc_event_form_help('event_contact') ?>
    </div>

    </div><!-- .new-event-category (who) -->

    <?php
    if ($event_submission->has_admin_comment()) {
    ?>
      <h3>Comments</h3>
      <p>Briefly tell the ride leader what you, the administrator, are changing about their event.
      <br>
      <textarea name='submission_comment'></textarea>
      </p>

      <p>
      <input type='checkbox' name='submission_suppress_email' id='submission_suppress_email'>
      <label for='submission_suppress_email'>
      This change is minor (don't e-mail the ride leader).
      </label>
      </p>

      <input type='hidden' name='submission_changed_by_admin' value='true'>

    <?php
    } // end if, has admin comment
    ?>
          

    <?php
    if ($event_submission->has_event_id()) {
    ?>
        <input type="hidden" id="submission_event_id" name="submission_event_id"
            value="<?php print esc_attr($event_submission->event_id()); ?>">
    <?php
    } # end if
    ?>

    <?php
    if ($event_submission->has_editcode()) {
    ?>
        <input type="hidden" id="event_editcode" name="event_editcode"
            value="<?php print esc_attr($event_submission->editcode()); ?>">
    <?php
    } # end if
    ?>

    <input type='hidden' id='event_dates' name='event_dates'>
    
    <div class='new-event-actions'>
        <input type="submit" id="submission_action" name="submission_action"
             value="<?php print esc_attr($event_submission->next_action()); ?>">

        <?php
        if ($event_submission->has_delete()) {
        ?>
            <input type="submit" id="delete_button" name="submission_action"
                value="delete">
        <?php
        } # end if
        ?>    
    </div>

    <h2>Preview</h2>
    <div id="preview"></div>         

    </form>         
    </div><!-- .new-event-form>
         
<?php
}         
?>