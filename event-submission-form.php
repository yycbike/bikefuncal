<?php

// Output a line for the time selector
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
        'tag' => 'div',
    );

    // Set default values for the help.
    if ($for === 'event_descr') {
        $help['content'] = <<<END_HTML
            <div>
              Be sure to mention any fees, otherwise people will assume it&apos;s free.
            </div>
            <div>
              <a id='event_descr_show_more'>More tips</a>
            </div>
            <div id='event_descr_more' style='display: none;'>
                <div>
                You may also want to mention: distance, hills, pace, if the ride is a loop.
                </div>

                <div>
                Formattting:
                </div>
                <ul>
                  <li>Put a blank line between paragraphs.
                  <li>Make bold with asterisks: <span style='font-family: monospace;'>*bring a light*</span>
                      becomes <span style='font-weight: bold'>bring a light</span>
                  <li>Include <span style='font-family: monospace'>http://</span> in your links; this makes them clickable.
                      Yes:&nbsp;<span style='font-family: monospace'>http://cyclepalooza.ca</span>.
                      No:&nbsp;<span style='font-family: monospace'>cyclepalooza.ca</span>
                </ul>
            </div>
END_HTML;

    }
    else if ($for === 'event_locname') {
        $help['content'] = 'e.g., "Science World" or "Arbutus Coffee Shop"';
    }
    else if ($for === 'event_address') {
        $help['content'] = 'Address or cross streets.';
    }
    else if ($for === 'event_locdetails') {
        $help['content'] = 'e.g., "Meet at the gazebo" or "in the NW corner of the park"';
    }
    else if ($for === 'submission_dates_multiple') {
        $help['content'] = <<<END_HTML
          Enter the dates when your event occurs.
          <br>
          E.g., "First Thursdays" or "Mondays, May to September"
          <div>
            <a id='dates_multiple_show_more'>More tips</a>
          </div>
          <div id='dates_multiple_more' style='display: none;'>
            <table>
              <thead>
                <tr><th>Kind of Recurrence</th><th>Examples</th></tr>
              </thead>
              <tbody>
                <tr>
                  <td rowspan=4>
                    For an event with no end date, enter the days it occurs on.
                  </td>
                  <td>
                    First Thursdays <br>
                  </td>
                </tr>
                <tr><td>Last Fridays</td></tr>
                <tr><td>Every Monday</td></tr>
                <tr><td>Tuesdays and Thursdays</td></tr>

                <tr>
                  <td rowspan=2>
                    For an event that does not go on forever, enter the start and end dates.
                  </td>

                  <td>
                    First Thursdays, May to September
                  </td>
                </tr>
                <tr><td>Tuesdays and Thursdays in July and August</td></tr>

                <tr>
                  <td>For daily events, enter the start and end dates</td>
                  <td>October 8 - 10</td>
                </tr>

                <tr>
                  <td>If the dates of your event don't follow a pattern, enter the dates separated by commas.</td>
                  <td>June 7, June 9, June 16</td>
              </tbody>
            </table>
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
        $help['content'] = 'Aliases are OK, last name not required.';
    }
    else if ($for === 'event_email') {
        $help['content'] = "We'll send you a link to edit this event. We won't spam you.";
    }
    else if ($for === 'event_hideemail') {
        $help['class'] .= ' hideemail';
        $help['content'] = "It's protected against spam-harvesting robots";
        $help['tag'] = 'span';
    }
    else if ($for === 'event_weburl') {
        $help['content'] = 'e.g., www.your-site.ca. If you made a Facebook event, put it here.';
    }
    else if ($for === 'intro') {
        $help['class'] = '';
        $help['tag'] = 'p';
        $help['content'] = <<<END_HTML
          Still planning your ride? Get tips from the <a href='/the-ride-guide'>ride guide</a>
          or write to <a href='mailto:calendar@cyclepalooza.ca'>calendar@cyclepalooza.ca</a>.
END_HTML;
    }

    // Let the theme override the defaults
    $help = apply_filters('bfc_event_form_help', $help);

    if ($help['content'] !== '') {
        printf("<%s class='%s'>", esc_attr($help['tag']), esc_attr($help['class']));
        // Don't call esc_html on the content, because it has HTML tags in it.
        print $help['content'];
        printf('</%s>', esc_attr($help['tag']));
    }
}


// Print the event submission/editing form
//
// $event_submission -- A BfcEventSubmission object.
function bfc_print_event_submission_form($event_submission) {
?>
    <div class="new-event-form">

    <?php bfc_event_form_help('intro'); ?>

    <?php if (!$event_submission->is_valid()) { ?>
    <div class="error-messages">
      <?php $event_submission->print_errors() ?>
    </div>
    <?php } # endif -- is valid ?>

    <form action="<?php print esc_attr(get_permalink()); // go back to this form when submitting ?>"
          method="post"
          enctype="multipart/form-data"
          id="event-submission-form"
          novalidate <?php // HTML5 browsers do undesirable validation things, like
                           // requiring URLs to start with http:. Disable that. ?>
          >

    <h2 class='event-submission-section'>Event Description</h2>
    <div class="new-event-category">

    <h3 class='event-submission-label <?php $event_submission->print_error_class_for('title'); ?>'>
      <span>Title</span>
    </h3>
    <div class="new-event-controls">
      <input type="text" class="fullwidth" name="event_title" required
        maxlength=80
        <?php $event_submission->print_title(); ?> >
      <?php bfc_event_form_help('event_title') ?>
    </div>

    <h3 class='event-submission-label  <?php $event_submission->print_error_class_for('descr'); ?> '>
      <span>Description</span>
    </h3>
    <div class="new-event-controls">
      <textarea name="event_descr" id="event_descr" class="fullwidth" required><?php $event_submission->print_descr()?></textarea>
      <?php bfc_event_form_help('event_descr') ?>
    </div>

    <h3 class='event-submission-label  <?php $event_submission->print_error_class_for('audience'); ?> '>
      <span>Audience</span>
    </h3>
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

    <div class='event-submission-label'>
      <h3>Image</h3>
      <div class='optional'>optional</div>
    </div>
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

    <h2 class='event-submission-section'>Meeting Place</h2>
    <div class="new-event-category">

    <div class='event-submission-label'>
      <h3>Place name</h3>
      <div class=optional>optional</div>
    </div>
    <div class="new-event-controls">
      <input type="text" id="event_locname" name="event_locname"
             class="narrower" maxlength=256
             <?php $event_submission->print_locname()?>>
      <?php bfc_event_form_help('event_locname') ?>
    </div>

    <h3 class='event-submission-label  <?php $event_submission->print_error_class_for('address'); ?> '>
      <span>Address</span>
    </h3>
    <div class="new-event-controls">
      <input type="text" id="event_address" name="event_address" required
             class="narrower" maxlength=256
             <?php $event_submission->print_address()?>>
      <?php bfc_event_form_help('event_address') ?>
  </div>

    <div class='event-submission-label'>
      <h3>Details</h3>
      <div class=optional>optional</div>
    </div>
    <div class="new-event-controls">
      <input type="text" name="event_locdetails" class="narrower"
        maxlength=256
        <?php $event_submission->print_locdetails()?>
        >
      <?php bfc_event_form_help('event_locdetails') ?>
    </div>

    </div><!-- .new-event-category (meeting place) -->

    <h2 class='event-submission-section'>Date & Time</h2>
    <div class="new-event-category">

    <h3 class='event-submission-label'>Event occurs</h3>
    <div class='new-event-controls'>
        <div>
            <input type='radio' name='submission_event_occurs'
                <?php $event_submission->print_checked_for_event_occurs('once_festival'); ?>
                id='submission_event_occurs_once_festival' value='once_festival'>
            <label for='submission_event_occurs_once_festival'>
              <?php
                 printf('Once, during %s (%s - %s)',
                        esc_html(get_option('bfc_festival_name')),
                        date('F j', strtotime(get_option('bfc_festival_start_date'))),
                        date('F j', strtotime(get_option('bfc_festival_end_date'))));
               ?>
            </label>
        </div>
        <div>
            <input type='radio' name='submission_event_occurs'
                <?php $event_submission->print_checked_for_event_occurs('once_other'); ?>
                id='submission_event_occurs_once_other' value='once_other'>
            <label for='submission_event_occurs_once_other'>
              Once, on another date
            </label>
        </div>
        <div>
            <input type='radio' name='submission_event_occurs'
                <?php $event_submission->print_checked_for_event_occurs('multiple'); ?>
                id='submission_event_occurs_multiple' value='multiple'>
            <label for='submission_event_occurs_multiple'>More than once (repeating)</label>
        </div>
    </div><!-- new-event-controls -->

    <!-- Container div that helps animate the transition between occurs once & occurs multiple -->
    <div id='once-multiple-container' style='clear: both;'>

      <div id='occurs-once'>
        <h3 class='event-submission-label  <?php $event_submission->print_error_class_for('dates'); ?> '>
          <span>Date</span>
        </h3>
        <div class='new-event-controls'>
          <input type='text'
                 name='submission_dates_once'
                 id='submission_dates_once'
                 maxlength=256
                 <?php if ($event_submission->event_occurs('once')) $event_submission->print_dates();  ?>
                 >
          <?php bfc_event_form_help('submission_dates_once') ?>
        </div><!-- .new-event-controls -->

        <div style='clear: both;'></div>
      </div><!-- #occurs-once -->

      <div id='occurs-multiple'>
        <h3 class='event-submission-label  <?php $event_submission->print_error_class_for('dates'); ?> '>
          <span>Dates</span>
        </h3>
        <div class='new-event-controls'>
          <input type='text'
                 id='submission_dates_multiple'
                 name='submission_dates_multiple'
                 class='narrower'
                 maxlength=256
                 <?php if ($event_submission->event_occurs('multiple')) $event_submission->print_dates(); ?>
                 >
          <input type='button' id='submission_dates_show' value='Show Dates'>
          <img src='<?php print plugins_url('bikefuncal/images/ajax-loader.gif'); ?>'
               id='submission_dates_spinner'
               style='display: none;'
               >

          <?php bfc_event_form_help('submission_dates_multiple'); ?>
        </div><!-- new-event-controls -->

        <div style='clear: both;'></div>
      </div><!-- occurs-multiple -->
    </div>

    <div id="datelist"></div>

    <h3 class='event-submission-label  <?php $event_submission->print_error_class_for('eventtime'); ?> '>
      <span>Start time</span>
    </h3>
    <div class="new-event-controls">
	<select name="event_eventtime" id="event_eventtime">
	  <option value="">Choose a time</option>
	  <?php bfc_generate_all_times($event_submission); ?>
	</select>
        <?php bfc_event_form_help('event_eventtime') ?>
    </div>

    <div class='event-submission-label'>
      <h3>End time</h3>
      <div class=optional>optional</div>
    </div>
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

    <div class='event-submission-label'>
      <h3>Time details</h3>
      <div class=optional>optional</div>
    </div>
    <div class="new-event-controls">
      <input type="text" name="event_timedetails" class="narrower"
        maxlength=256 <?php $event_submission->print_timedetails(); ?>>
      <?php bfc_event_form_help('event_timedetails') ?>
    </div>
    </div><!-- .new-event-category (date & time) -->

    <h2 class='event-submission-section'>Contact Info</h2>
    <div class="new-event-category">

    <h3 class='event-submission-label  <?php $event_submission->print_error_class_for('name'); ?> '>
      <span>Organizer name</span>
    </h3>
    <div class="new-event-controls">
      <input type="text" name="event_name" class="narrower" required
        maxlength=256 <?php $event_submission->print_name(); ?>>
      <?php bfc_event_form_help('event_name') ?>
    </div>

    <h3 class='event-submission-label  <?php $event_submission->print_error_class_for('email'); ?> '>
      <span>E-Mail</span>
    </h3>
    <div class='new-event-controls'>
      <input type="email" name="event_email" class="narrower" required
        maxlength=256 <?php $event_submission->print_email(); ?>>
      <?php bfc_event_form_help('event_email') ?>
        <div class='email-controls'>
          <input type="checkbox" name="event_emailforum" value="Y"
                 id=event_emailforum
                 <?php $event_submission->print_checked_for_emailforum() ?>
                 >
            <label for=event_emailforum>
              Mail me when people comment on this event
            </label>
        </div>
        <div>
          <input type="checkbox" name="event_hideemail" value="Y"
                 id=event_hideemail
                 <?php $event_submission->print_checked_for_hideemail() ?>
                 >
            <label for=event_hideemail>
              Don't publish my e-mail address online
            </label>
            <br>
            <?php bfc_event_form_help('event_hideemail'); ?>
        </div>
    </div>

    <div class='event-submission-label   <?php $event_submission->print_error_class_for('weburl'); ?> '>
      <h3><span>Web site</span></h3>
      <div class=optional>optional</div>
    </div>
    <div class='new-event-controls'>
      <input type="url" name="event_weburl" class="narrower"
        maxlength=256 <?php $event_submission->print_weburl(); ?>>
      <?php bfc_event_form_help('event_weburl') ?>
    </div>

    <div class='event-submission-label'>
      <h3>Other info</h3>
      <div class=optional>optional</div>
    </div>
    <div class="new-event-controls">
      <input type="text" name="event_contact" class="narrower"
        maxlength=256 <?php $event_submission->print_contact(); ?>>
      <?php bfc_event_form_help('event_contact') ?>
    </div>

    </div><!-- .new-event-category (who) -->

    <?php
    if ($event_submission->has_admin_comment()) {
    ?>
      <h2 class='event-submission-section'>Comments</h2>
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

    <h2 class=event-submission-preview>
      Preview
      <img src='<?php print plugins_url('bikefuncal/images/ajax-loader.gif'); ?>'
           id='submission_preview_spinner'
           style='display: none;'
           >
    </h2>
    <div id="preview-container"></div>

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

    </form>
    </div><!-- .new-event-form -->

<?php
}
?>
