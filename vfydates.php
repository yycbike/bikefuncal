<?php

# This is an AJAX request.  It is passed a "dates" string, which it decodes.
# It can also be passed the id of an existing event, in which case it'll merge
# any information about currently booked dates with the new "dates" list.
# It returns an XML document describing all affected dates.  Parameters are:
#
# dates=	The "dates" string to parse.
#
# id=		Optional.  The id of an existing event, whose current days
#		will be merged into the "dates" list.
#
# reload=	Optional.  If "true" then any dates that match dates= but
#		aren't already booked for id= will be marked as "Deleted"
#		instead of "Added".  event-submission.js will pass reload=true when
#		you start editing an existing event, but not when change the
#		"dates" input field.

require_once("repeat.php");
require_once("daily.php");

# Add this to WordPress' registry of AJAX actions.
add_action('wp_ajax_nopriv_verify-dates', 'bfc_verify_dates_ajax_action');
add_action('wp_ajax_verify-dates', 'bfc_verify_dates_ajax_action');

function bfc_verify_dates_ajax_action() {
    # Turn on plain text PHP errors for the remainder of the AJAX request
    ini_set('html_errors', 'Off');

    # This sends an XML response
    header("Content-type: text/xml");
    print "<?xml version=\"1.0\" encoding=\"iso-8859-1\" ?>\n";

    # Parse the "dates" parameter to generate the list of new dates
    $dayinfo = repeatdates($_POST["dates"]);
    $datestype = $dayinfo["datestype"];

    if ($datestype == 'error') {
        print "<vfydates>\n";
        print "  <error>Date not understood</error>\n";
        print "</vfydates>\n";
    } else {
        $newdates = $dayinfo["daylist"];
        $canonical = $dayinfo["canonical"];

        # If we're editing an existing event, then fetch its daily list and merge
        # it into the $newdates.
        if (isset($_POST["id"])) {
            # Fetch the old dates
            $olddates = dailystatus($_POST["id"]);

            # Merge the old and new lists.
            $newdates = mergedates($newdates, $olddates);
        } else {
            # all dates will be added.  Just merge in some new fields.
            for ($i = 1; isset($newdates[$i]); $i++) {
                $newdates[$i]["changed"] = "Y";
                $newdates[$i]["olddate"] = "N";
            }
        }

        # If this is a reload, not a change to the "dates" input, then any
        # missing dates must have been deleted instead of added.  HOWEVER since
        # it would be nice if one could extend a repeating event by reloading
        # it and saving it, without changing a bunch of dates from "Deleted" to
        # "Added", so if the list ends with a bunch of "Added" dates then leave
        # them that way.
        if (isset($_POST["reload"])) {
            for ($i = 1; isset($newdates[$i]); $i++) {
            }
            for ($i--; $i >= 1 && $newdates[$i]["status"] == "Added"; $i--) {
            }
            for (; $i >= 1; $i--) {
                if ($newdates[$i]["status"] == "Added") {
                    $newdates[$i]["status"] = "Deleted";
                    $newdates[$i]["changed"] = "N";
                    $newdates[$i]["olddate"] = "Y";
                }
            }
        }

        print "<vfydates>\n";

        # If an id was passed, include it in the response
        if (isset($_POST['id'])) {
            print "  <id>";
            print ent2ncr(esc_html($_POST['id']));
            print "</id>\n";
        }

        # Output a canonical version of the date
        printf("  <canonical>%s</canonical>\n", ent2ncr(esc_html($canonical)));

        # Classify the date type
        printf("  <datestype>%s</datestype>\n", ent2ncr(esc_html($datestype)));

        # Output the list of dates
        print "  <datelist>\n";

        foreach ($newdates as $date) {
            printf("    <date>\n");
            printf("      <timestamp>%s</timestamp>\n", ent2ncr(esc_html($date['timestamp'])));
            printf("      <hrdate>%s</hrdate>\n", ent2ncr(esc_html(date("D M j", $date["timestamp"]))));
            printf("      <suffix>%s</suffix>\n", ent2ncr(esc_html($date["suffix"])));
            printf("      <status>%s</status>\n", ent2ncr(esc_html($date["status"])));
            printf("      <exception-url>");
            if (isset($date["exceptionid"])) {
                $url = bfc_get_edit_url_for_event($date["exceptionid"]);
                print ent2ncr(esc_url($url));
            }
            print "</exception-url>\n";

            print "      <newsflash>";
            if (isset($date["newsflash"])) {
                print ent2ncr(esc_html($date["newsflash"]));
            }
            print "</newsflash>\n";
            printf("      <change>%s</change>\n", ent2ncr(esc_html($date['changed'])));
            printf("      <newdate>%s</newdate>\n", ent2ncr(esc_html($date['newdate'])));
            printf("      <olddate>%s</olddate>\n", ent2ncr(esc_html($date['olddate'])));
            print "    </date>\n";
        }
        print "  </datelist>\n";
        print "</vfydates>\n";
    }

    exit;
}

?>
