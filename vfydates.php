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
add_action('wp_ajax_nopriv_verify-dates', 'verify_dates');
add_action('wp_ajax_verify-dates', 'verify_dates');

function verify_dates() {
    # Turn on plain text PHP errors for the remainder of the AJAX request
    ini_set('html_errors', 'Off');

    # This sends an XML response
    header("Content-type: text/xml");
    print "<?xml version=\"1.0\" encoding=\"iso-8859-1\" ?>\n";

    # Parse the "dates" parameter to generate the list of new dates
    $dayinfo = repeatdates($_POST["dates"]);
    $canonical = $dayinfo["canonical"];
    $datestype = $dayinfo["datestype"];
    $newdates = $dayinfo["daylist"];

    if (!isset($newdates[1])) {
        # No dates?  That means we parsed it correctly but there were no
        # dates that satisfied the criteria.
        print "<vfydates>\n";
        print "  <error>No matching dates</error>\n";
        print "</vfydates>\n";
    } else  if (isset($newdates[365])) {
        # Every day matches?  That means we failed to parse anything in
        # the date.
        print "<vfydates>\n";
        print "  <error>Date not understood</error>\n";
        print "</vfydates>\n";
    } else {
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
            print $_POST['id'];
            print "</id>\n";
        }

        # Output a canonical version of the date
        print "  <canonical>$canonical</canonical>\n";

        # Classify the date type
        print "  <datestype>$datestype</datestype>\n";

        # Output the list of dates
        print "  <datelist>\n";

        foreach ($newdates as $date) {
            print "    <date>\n";
            print "      <timestamp>".$date["timestamp"]."</timestamp>\n";
            print "      <hrdate>".date("D M j", $date["timestamp"])."</hrdate>\n";
            print "      <suffix>".$date["suffix"]."</suffix>\n";
            print "      <status>".$date["status"]."</status>\n";
            print "      <exception>";
            if (isset($date["exceptionid"])) {
                # @@@ todo: obscure this, or add an authentication code or something.
                print $date["exceptionid"];
            }
            print "</exception>\n";

            
            print "      <newsflash>";
            if (isset($date["newsflash"])) {
                print htmlspecialchars($date["newsflash"]);
            }
            print "</newsflash>\n";
            print "      <change>".$date["changed"]."</change>\n";
            print "      <newdate>".$date["newdate"]."</newdate>\n";
            print "      <olddate>".$date["olddate"]."</olddate>\n";
            print "    </date>\n";
        }
        print "  </datelist>\n";
        print "</vfydates>\n";
    }

    exit;
}

?>
