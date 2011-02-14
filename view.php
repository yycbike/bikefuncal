<?php
# This is a fragment of PHP code.  It defines functions for generating a
# days' tiny entries (used in the weekly grid at the top of many views)
# and full entries (used in the body below the grid).
#
# IMPORTANT: THIS FILE MUST NOT OUTPUT ANYTHING!  It is included in some
# pages which alter the header.
#
# It is sensitive to the following HTTP parameters (which would be passed to
# the page that includes this file):
#
#   p=...	p=y to use printer-friendly formatting.
#
#   i=...	i=n to inhibit event images.
#
# It uses the following global variables, which should be declared in the
# file that includes this one:
#
#   $conn	A connection to the MySQL server
#
#   $imageover	Used to handle images that overlap entries.  It should be
#		initialized to 0
#
# In addition, it assumes the following CSS classes are defined:
#
#   div.tiny    { font-size: xx-small; font-stretch: ultra-condensed; }
#   div.tinier  { font-size: xx-small; font-stretch: ultra-condensed; }
#   dt.canceled { text-decoration: line-through; }
#   dd.canceled { text-decoration: line-through; }


# Return an address that google maps can more reliably parse (and displays with some consistency)
function addrparseprep($address)
{
    
    # if $address ends in " BC" or ",BC" or "+BC" (which includes ending in ", BC")
      # parse as is
    # if $address ends in "Vancouver" and there's a "," anywhere in $address
      # add ", BC" to end then parse
    # if $address ends in "Vancouver" 
      # add " BC" to end then parse
    # if there's a "," anywhere in $address
      # add ", Vancouver, BC" to end then parse
    # otherwise 
      # add " Vancouver BC" to end then parse

    if ( strtoupper(substr($address,strlen($address)-strlen(constant("OPROV"))-1)) == " ".strtoupper(constant("OPROV")) 
      || strtoupper(substr($address,strlen($address)-strlen(constant("OPROV"))-1)) == ",".strtoupper(constant("OPROV")) 
      || strtoupper(substr($address,strlen($address)-strlen(constant("OPROV"))-1)) == "+".strtoupper(constant("OPROV")) )
	    $address = trim($address);

    elseif ( strtoupper(substr($address,strlen($address)-strlen(constant("OCITY")))) == strtoupper(constant("OCITY")) 
      || stristr($address,",") )
	    $address = trim($address).', '.strtoupper(constant("OPROV"));

    elseif ( strtoupper(substr($address,strlen($address)-strlen(constant("OCITY")))) == strtoupper(constant("OCITY")) )
	    $address = trim($address).' '.strtoupper(constant("OPROV"));

    elseif ( stristr($address,",") )
	    $address = trim($address).', '.constant("OCITY").', '.strtoupper(constant("OPROV"));

    else
	    $address = trim($address).' '.constant("OCITY").' '.strtoupper(constant("OPROV"));

    return $address;
}

# Return the URL for bus/train trip planner, or NULL if unreachable
function transiturl($sqldate, $eventtime, $address)
{
    return NULL;
}

function class_for_special_day($thisdate) {
    # For Pedalpalooza, the Portland calendar highlights
    # special events, such as MCBF, Father's Day, and the Solstice.
    # If we wanted to do that, we could do so here.
    return "";
}

# Output the TD for one day in the overview calendar
function overview_calendar_day($thisdate, $preload_alldays) {

    # If grand finale then use a background image, else plain background
    $dayofmonth = date("j",$thisdate);

    # If today is special...
    $class = class_for_special_day($thisdate);

    # Highlight today's date.
    if (date("Y-m-d", time()) == date("Y-m-d", $thisdate)) {
        $class .= " today";
    }
    
    print "<td id=\"cal$dayofmonth\" class=\"${class}\">\n";

    # For debugging
    #print "<p>" . date("Y-m-d h:m:s", $thisdate) . "</p>";
    
    # Output this day's tinytitles
    $sqldate = date("Y-m-d", $thisdate);
    print "<a href=\"#".date("Fj",$thisdate)."\" ";
    print "title=\"".date("M j, Y", $thisdate)."\" ";
    print "class=\"date\" ";
    if (!$preload_alldays) {
        # If the days aren't all being loaded, add JS to load them
        # when the day is clicked.
        print "onclick=\"loadday('$sqldate', true, 0); return false;\"";
    }
    print ">";
    print date("j", $thisdate);
    print "</a>\n";
    tinyentries($sqldate, TRUE, $preload_alldays );
    print "</td>\n";
}   

# This function is used in the weekly grid portion of the calendar,
# to skip multiple days in the column.  If a large number of days
# are to be skipped, it may put something useful in there.
function calendar_quote($days)
{
#     if ($days == 1)
#         print "<td>&nbsp;</td>\n";
#     else if ($days > 3 && file_exists("Quotations")) {
#         mt_srand ((double) microtime() * 1000000);
#         $lines = file("Quotations");
#         $line_number = mt_rand(0,sizeof($lines)-1);
#         $quotation = htmlspecialchars($lines[$line_number]);
#         $quotation = preg_replace(
#             '/^(.*)~/',
#             '<span class=quotation-text>$1</span><br>--',
#             $quotation);
#         $length = strlen($lines[$line_number]);
#         $class = "quotation ";
#         if ($length / $days > 80) {
#             $class .= "size-0 ";
#         }
#         else if ($length / $days > 50) {
#             $class .= "size-1 ";
#         }
#         else if ($length / $days > 35) {
#             $class .= "size-2 ";
#         }
#         else {
#             $class .= "size-3 ";
#         }
#         print "<td colspan=$days class=\"$class\">$quotation</td>\n";
#     } else {
#         print "<td colspan=$days>&nbsp;</td>\n";
#     }

    print "<td colspan=$days>&nbsp;</td>\n";
}

# Generate the inset that goes in the palooza
# calendar. This is the text that explains all
# ages & adult rides.
function palooza_overview_calendar_inset($days) {
?>    
      <td colspan="<?= $days ?>" class="palooza-overview-calendar-inset">
	<span class="palooza-date">
        <?php
          print substr(constant("PDATES"),0,4);
          print "&nbsp;";
          print substr(constant("PSTART"),0,4)
        ?>
        </span>
        <br>
	<a href="explain/audience.html" target="_BLANK" onClick="window.open('explain/audience.html', 'audience', 'width=600, height=500, menubar=no, status=no, location=no, toolbar=no, scrollbars=yes'); return false;">
	  <span class="family-friendly">Family Friendly events have <strong>green</strong> times</span>
          <br>
	  <span class="adults-only">Adult Only (19+) events have <strong>red</strong> times</span>
	</a>
	<p>In all cases, you are encouraged to read the detailed event
        descriptions below.  If you still aren't sure whether an event
	is appropriate for you, then contact the event organizer.
        </p>
      </td>
<?php
}

# Print an overview calendar, that lists the events in a grid.
#
# $startdate, $enddate -- The range of dates to show in the calendar.
# $for -- "palooza" or "cal". Is this calendar for a palooza?
# $preload_alldays -- TRUE if all days are loaded onto the page;
#     FALSE if the days will be dynamically loaded.
function overview_calendar(
    $startdate, $enddate, $for, $preload_alldays) {
?>

  <table class="grid">
    <tr>
      <th class="weeks">Sunday</th>
      <th class="weeks">Monday</th>
      <th class="weeks">Tuesday</th>
      <th class="weeks">Wednesday</th>
      <th class="weeks">Thursday</th>
      <th class="weeks">Friday</th>
      <th class="weeks">Saturday</th>
   </tr>
    <tr>
    <?php

    # If month doesn't start on Sunday, then skip earlier days
    $weekday = getdate($startdate);
    $weekday = $weekday["wday"];
    if ($weekday != 0) {
        if ($for == "cal") {
            calendar_quote($weekday);
        }
        else {
            palooza_overview_calendar_inset($weekday);
        }
    }

    # Loop through each day between $startdate and $enddate.
    # We can't just increment $thisdate forward by 86400
    # (seconds per day) because that causes trouble around
    # daylight savings time (but I'm not sure why...).
    # So we we increment $day_of_month.
    $day_of_month = date('d', $startdate);
    do {
        # It's OK to call this with, for example,
        # the 32nd of July; PHP turns that into
        # 1st of August. Also, 32 Dec 2010 becomes
        # 1 Jan 2011.
        $thisdate = mktime(0, 0, 0, #hms
                           date('m', $startdate),
                           $day_of_month,
                           date('Y', $startdate));
        
	# Start new row each week
	if (date("D", $thisdate) == "Sun") {
	    print "</tr><tr>\n";
        }

        overview_calendar_day($thisdate, $preload_alldays);

        $day_of_month++;

        # Check the date 2 ways. Sometimes $thisdate has an
        # h:m:s component, and will be greater than $enddate, even
        # if they're on the same day.
    } while ( ($thisdate <= $enddate) &&
              (date('Y-m-d', $thisdate) != date('Y-m-d', $enddate)) );


    $last_day = date('w', $enddate);
    # If the calendar doesn't end on Saturday
    if ($last_day != 6) { 
        calendar_quote(7 - $last_day);
    }
?>
    </tr>
  </table>

    
<?php    
}

# Generate the HTML for all entries in a given day, in the tiny format
# used in the weekly grid near the top of the page.
function tinyentries($day, $exclude = FALSE, $loadday = FALSE)
{
    global $conn;
    $dayofmonth = substr($day, -2);

    # Find events that are not exceptions or skipped
    $query = <<<END_QUERY
SELECT calevent.id, newsflash,title, tinytitle, eventtime,
       audience, eventstatus, descr, review
FROM calevent, caldaily
WHERE calevent.id=caldaily.id AND
      eventdate   =  "${day}" AND
      eventstatus <> "E"      AND
      eventstatus <> "S"     
ORDER BY eventtime

END_QUERY;
    $result = mysql_query($query, $conn) or die(mysql_error());
    while ($record = mysql_fetch_array($result)) {
	if ($exclude && $record["review"] == "E")
	    continue;
	$id = $record["id"];
	$tinytitle = htmlspecialchars($record["tinytitle"]);
	$title = htmlspecialchars($record["title"]);
        
        # CSS classes
        $titleclass = "event-tiny-title ";
        $timeclass  = "";
        
	if ($record["eventstatus"] == "C") {
	    $eventtime = "Cancel";
            $titleclass .= "canceled ";
	} else {
	    $eventtime = hmmpm($record["eventtime"]);
	}

	if ($record["audience"] == "F") {
            $timeclass .= "family-friendly ";
            
	} elseif ($record["audience"] == "G") {
            # Nothing to do here
	} else {
            $timeclass .= "adults-only ";
	}
        
	if ($record["newsflash"] != "") {
            $titleclass .= "newsflash ";
        }

        if ($tinytitle == "MCBF") {
	    print "<div>";
        }
	else if (strlen(strtok($tinytitle, " ")) < 10) {
	    print "<div class=\"tiny\">";
        }
	else {
	    print "<div class=\"tinier\">";
        }
        
	if ($loadday) {
	    $onclick = " onclick=\"loadday('$day', ".($exclude?"true":"false").", $id); return false;\"";
        }
	else {
	    $onclick = "";
        }
        
	print "<a href=\"#${dayofmonth}-${id}\" title=\"${title}\" $onclick>";
        print "<span class=\"${titleclass}\">";
	print "<strong class=\"${timeclass}\">${eventtime}</strong>";
	if (strpos($record["descr"], "\$") != FALSE) {
	    print "&nbsp;<strong>\$\$</strong>";
        }
	print "&nbsp;${tinytitle}</span></a></div>";
    }
}

# Print the event listings that go below the calendar.
function event_listings($startdate,
                        $enddate,
                        $preload_alldays,
                        $for_printer,
                        $include_images) {

    $today = strtotime(date("Y-m-d"));
    $tomorrow = $today + 86400;

    for ($thisdate = $startdate;
         $thisdate <= $enddate;
         $thisdate += 86400) {
        
        # Use a fancy graphical devider for screen,
        # a plain HR for printer.
	if (!$for_printer) {
	    print "<div class=hr></div>\n";
        }
	else {
	    print "<hr>\n";
        }
        
	print "<h2 class=weeks>";
        print "<a class=\"datehdr\" name=\"".date("Fj",$thisdate)."\">";
        print date("l F j", $thisdate);
        print "</a></h2>\n";
        
	$ymd = date("Y-m-d", $thisdate);
	print "<div id='div${ymd}'>\n";

        # If the events for this day should be loaded
	if ($thisdate == $today ||
            $thisdate == $tomorrow ||
            $for_printer ||
            $preload_alldays) {
	    fullentries(date("Y-m-d", $thisdate),
                        TRUE,
                        $for_printer,
                        $include_images);  
        }
	else {
	    print "<span class=\"loadday\" ";
            print "onClick=\"loadday('$ymd', true);\">";
            print "Click here to load this day's events";
            print "</span>\n";
        }
	print "</div>\n";
    }
}

# Generate the HTML entry for a single event
function fullentry($record, $for_printer, $include_images)
{
    global $conn;
    global $imageover;

    # 24 hours ago.  We compare timestamps to this in order to
    # detect recently changed entries.
    $yesterday = date("Y-m-d H:i:s", strtotime("yesterday"));

    # extract info from the record
    $id = $record["id"];
    $title = htmlspecialchars(strtoupper($record["title"]));
    if ($record["eventstatus"] == "C") {
	$eventtime = "CANCELED";
	$eventduration = 0;
    } else {
	$eventtime = hmmpm($record["eventtime"]);
	$eventduration = $record["eventduration"];
    }
    
    $dayofmonth = substr($record["eventdate"], -2);
    $timedetails = $record["timedetails"];
    
    if ($record["audience"] == "F") {
	$badge = "ff.gif";
	$badgealt = "FF";
	$badgehint = "Family Friendly";
    }
    if ($record["audience"] == "G") {
	$badge = "";
	$badgealt = "";
	$badgehint = "";
    }
    if ($record["audience"] == "A") {
	$badge = "beer.gif";
	$badgealt = constant("OFAGE")."+";
	$badgehint = "Adult Only (".constant("OFAGE")."+)";
    }
    
    $address = htmlspecialchars($record["address"]);
    if ($record["locname"]) {
	$address = htmlspecialchars($record["locname"]).", $address";
    }
    $locdetails = htmlspecialchars($record["locdetails"]);
    $descr = htmldescription($record["descr"]);
    $newsflash = htmlspecialchars($record["newsflash"]);
    $name = htmlspecialchars(ucwords($record["name"]));
    $email = $record["hideemail"] ? "" : htmlspecialchars($record["email"]);
    $email = mangleemail($email);
    $phone = $record["hidephone"] ? "" : htmlspecialchars($record["phone"]);
    $contact = $record["hidecontact"] ? "" : htmlspecialchars($record["contact"]);
    $weburl = $record["weburl"];
    $webname = $record["webname"];
    if ($webname == "" || $forprinter) {
        # If they left out the name for their web site, or if
        # this is being shown for printing, show the URL insetad of the
        # site name.
	$webname = $weburl;
    }
    $webname = htmlspecialchars($webname);
    
    $forum = mysql_query("SELECT modified FROM calforum WHERE id=${id} ORDER BY modified DESC", $conn) or die(mysql_error());
    $forumimg = "images/forum.gif";
    $forumqty = mysql_num_rows($forum);
    $forumtitle = "$forumqty message".($forumqty == 1 ? "" : "s");
    if ($forumqty > 0)
    {
	$msg = mysql_fetch_array($forum);
	$msgmod = $msg["modified"];
	# Format the timestamp -- varies with SQL ver.
	if (strlen($msgmod) == 14) {
	    # older MySQL uses YYYYMMDDhhmmss format
	    $msgmod = substr($msgmod, 0, 4) . "-"
		    . substr($msgmod, 4, 2) . "-"
		    . substr($msgmod, 6, 2) . " "
		    . substr($msgmod, 8, 2) . ":"
		    . substr($msgmod, 10, 2) . ":"
		    . substr($msgmod, 12, 2);
	}
	$forumtitle = "$forumtitle, newest ".substr($msgmod, 0, 10);

	if (strcmp($msgmod, $yesterday) > 0) {
	    $forumimg = "images/forumflash.gif";
        }
    }

    # get the image info
    $image = "";
    if ($record["image"] && $include_images) {
	$t = pathinfo($record["image"]);
	$t = $t["extension"];
	$image = "eventimages/$id.$t";
	$imageheight = $record["imageheight"];
	$imagewidth = $record["imagewidth"];
    }
    
    if ($eventtime == "CANCELED") {
	$class = "canceled";
    }
    else {
        $class = "";
    }
    
    print "<dt class=\"${class}\">";

    # If the image should be right-aligned, do that.
    if ($image && $imageover <= 0 && $imageheight > RIGHTHEIGHT / 2) {
        # Put the image's width & height in bounds
	if ($imageheight > RIGHTHEIGHT) {
	    $imagewidth = $imagewidth * RIGHTHEIGHT / $imageheight;
	    $imageheight = RIGHTHEIGHT;
	}
        
	print "\n";
        print "<img src=\"$image\" height=$imageheight width=$imagewidth align=\"right\" alt=\"\" class=\"ride-image\">";
        print "\n";
    }
    print "<a name=\"${dayofmonth}-${id}\" class=\"eventhdr $class\">${title}</a>\n";
    print "<a href=\"#${dayofmonth}-{$id}\"><img border=0 src=\"images/chain.gif\" alt=\"Link\" title=\"Link to this event\"></a>\n";
    
    if ($_COOKIE["havemore"] == "bikefun") {
	print "<a href=\"calform.php?edit=".obscure($id)."\"><img src=\"images/edit.gif\" alt=\"[edit]\" border=0></a>\n";
    }
    
    if ($badge != "") {
        print "<img align=left src=\"".IMAGES."/$badge\" alt=\"$badgealt\" title=\"$badgehint\">\n";
    }

    print "</dt><dd>";

    # If the image should be left-aligned, do that.
    if ($image && ($imageover > 0 || $imageheight <= RIGHTHEIGHT / 2)) {
        # Put the image's width & height in bounds
	if ($imageheight > LEFTHEIGHT) {
	    $imagewidth = $imagewidth * LEFTHEIGHT / $imageheight;
	    $imageheight = LEFTHEIGHT;
	}
        
        print "<img src=\"$image\" height=$imageheight width=$imagewidth align=\"left\" alt=\"\" class=\"ride-image\">";
        print "\n";
    }

    
    print "<div class=\"$class\">";
    print '<a href="http://maps.google.com/?q='.urlencode(addrparseprep($record["address"])).'" target="_BLANK">'.$address.'</a>';
    
    if (! $for_printer) {
	$url = transiturl($record["eventdate"],
                          $record["eventtime"],
                          $record["address"]);
	if ($url) {
	    print " <a href=\"$url\" target=\"_BLANK\" title=\"Transit Trip Planner\"><img alt=\"By Bus\" src=\"images/bus.gif\" border=0></a>";
        }
    }
    
    if ($locdetails != "") {
        print " ($locdetails)";
    }
    print "</div>\n";
    print "$eventtime";
    if ($eventtime == "CANCELED" && $newsflash != "") {
	print " <span class=newsflash>$newsflash</span>";
    }
    if ($eventtime != "CANCELED") {
	if ($eventduration != 0)
	    print " - ".endtime($eventtime,$eventduration);
	if ($timedetails != "") print ", $timedetails";
	if ($record["datestype"] == "C" || $record["datestype"] == "S")
	    print ", ${record[dates]}";
    }
    print "<div class=\"$class\">\n";
    print "<em>$descr</em>\n";
    if ($newsflash != "" && $eventtime != "CANCELED") {
	print "<span class=newsflash>$newsflash</span>";
    }
    print "<br>$name";

    if (!strpbrk(substr(trim($name),strlen(trim($name))-1),".,:;-")) print ",";
    if ($email != "") print " $email";
 
    if ($weburl != "") print ", <a href=\"$weburl\">$webname</a>";
    if ($contact != "") print ", ".mangleemail($contact);
    if ($phone != "") print ", $phone";
    if (!$for_printer) {
	print "&nbsp;&nbsp;<a href=\"calforum.php?id=$id\" title=\"$forumtitle\"><img border=0 src=\"$forumimg\" alt=\"[forum]\"></a>\n";
    }
    print "</div></dd>\n";

    # if this event has no image, then the next event's
    # image can be left-aligned.
    if ($image == "" || $imageover > 0 || $imageheight <= RIGHTHEIGHT / 2)
	$imageover = 0;
    else
	$imageover = $imageheight - RIGHTHEIGHT / 2;
}

# Generate the HTML for all entries in a given day, in the full format
# used in the lower part of the page.
function fullentries($day,
                     $exclude = FALSE,
                     $forprinter = FALSE,
                     $include_images = TRUE)
{
    global $conn;
    global $imageover;

    # The day separator line is about 20 pixels high.  We can
    # reduce $imageover by that much.
    $imageover -= 20;

    # for each event on this day...
    
    # Find events that are not exceptions or skipped.
    $query = <<<END_QUERY
SELECT *
FROM calevent, caldaily
WHERE calevent.id = caldaily.id AND
      eventdate = "${day}" AND
      eventstatus <> "E" AND
      eventstatus <> "S"
ORDER BY eventtime
END_QUERY;
    $result = mysql_query($query, $conn) or die(mysql_error());
    if (mysql_num_rows($result) > 0) {
	print ("<dl>\n");
    }
    
    while ($record = mysql_fetch_array($result)) {
	if (!$exclude || $record["review"] != "E") {
	    fullentry($record, $forprinter, $include_images);
        }
    }
    
    if (mysql_num_rows($result) > 0) {
	print ("</dl>\n");
    }
}
#ex:set sw=4 embedlimit=60000:
?>
