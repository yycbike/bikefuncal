<?php
# This file comes from the old (pre-wordpress) code. It defines
# a bunch of constants for customizing the site. Much of this could
# be turned into a WordPress admin panel.
#
# It also has a few utility functions.

# Relative directory where images are stored, other than event-specific images
define("IMAGES", "images");

# Relative directory where standard header and footer, and other ..., are stored
define("INCLUDES", "include");

# Image height limits for the online calendar, and preload threashold
define("RIGHTHEIGHT", 200);
define("LEFTHEIGHT", 125);
define("PRELOAD", 100);		# Preload all days if no alldays cookie is set and number of events is under this threashold

# Info about locality
define("OCITY", "Vancouver");	# City where events are mainly held
define("OPROV", "BC");		# State or Province code where events are mainly held
define("OFAGE", 19);		# Local age of adult in State or Province where events are mainly held
define("AREACODE", "604");	# Default area code where events are mainly held

# Info about organization that's behind the palooza event
define("ONAME", "Velolove");		# Full name of organization
define("ONAME_SHORT", "Velolove");	# Short name of organization (used in [Xxxx Cal])

# Absolute URL of where OPAGE, CPAGE, and PPAGE reside
# define("CALURL", "http://localhost/~shift/velolove/cal/");
# This could be split into two: one for PPAGE and the other for OPAGE & CPAGE
define("CALURL", "http://www.velolove.bc.ca/");

# Relative URL of site entry landing / home pages
define("OPAGE", "view3week.php");	# Orgainization landing page
define("CPAGE", "view3week.php");	# Regulare calendar landing page
define("PPAGE", "viewpalooza.php");	# Palooza festival landing page

# Organization and palooza header and footer pages (located in INCLUDES)
define("OHEADER", "header1.php");	# Header for organization site entry / pages
define("PHEADER", "header2.php");	# Header for palooza site entry / pages
define("OFOOTER", "footer.php");	# Footer for organization site entry / pages
define("PFOOTER", "footer.php");	# Footer for palooza site entry / pages

# Organization personality email address
# Encoding the @ sign and dot is enough to fool many spam bots, but users can still click on the e-mail address
define("OIMAIL", "info&#x40;velolove&#x2e;bc&#x2e;ca");		# Email address for general information
define("OCMAIL", "calendar&#x40;velolove&#x2e;bc&#x2e;ca");	# Email address for calendar & calendar forum crew
define("ODMAIL", "donate&#x40;velolove&#x2e;bc&#x2e;ca");	# Email address for donations
define("OSMAIL", "sponsor&#x40;velolove&#x2e;bc&#x2e;ca");	# Email address to become a sponsor
define("OVMAIL", "volunteer&#x40;velolove&#x2e;bc&#x2e;ca");	# Email address to volunteer
define("OWMAIL", "webadmin&#x40;velolove&#x2e;bc&#x2e;ca");	# Email address for web site crew

# Info about the palooza event. In addition to the values here,
# you may have to do some editing to the year-specific version the
# festival calendar page (e.g., "viewpalooza.php").
define("PNAME", "Velopalooza");			# Name of the festival
define("PTEXT", "palooza");			# Text in calling URL that decides landing page and site personality
define("PSTART", "2011-06-03");			# Start date, in MySQL format
define("PEND", "2011-06-19");			# End date, in MySQL format
define("PSTART_MONTHDAY", "June 3");		# Start date, in "Month Day" format
define("PDAYS", 17);				# Duration in days
define("PDATES", "June 3-19");			# Dates, in "Month Day-Day" format
define("PDATES_LONG", "June 3 to June 19");	# Dates, in a longer format
define("PSMALL", "images/palooza_poster_small.jpg"); # Relative URL of the medium poster image
define("PLARGE", "images/palooza_poster.jpg");	# Relative URL of the full-size poster image

# Palooza festival email address
# Encoding the @ sign and dot is enough to fool many spam bots, but users can still click on the e-mail address
define("PIMAIL", "info&#x40;velopalooza&#x2e;ca");	# Email address for palooza information
define("PCMAIL", "calendar&#x40;velopalooza&#x2e;ca");	# Email address for calendar & calendar forum crew
define("PDMAIL", "donate&#x40;velopalooza&#x2e;ca");	# Email address for donations
define("PSMAIL", "sponsor&#x40;velopalooza&#x2e;ca");	# Email address to become a sponsor
define("PVMAIL", "volunteer&#x40;velopalooza&#x2e;ca");	# Email address to volunteer
define("PWMAIL", "webadmin&#x40;velopalooza&#x2e;ca");	# Email address for web site crew

# This function determins the landing page and site's personality
function ptext($ptext)
{ return stripos(strtolower($_SERVER['HTTP_HOST']),strtolower($ptext)); }


# Convert a time string from "hh:mm:ss" format to "h:mmpm" format
function hmmpm($hhmmss)
{
    $h = substr($hhmmss, 0, 2) + 0; # to convert to an integer
    $m = substr($hhmmss, 3, 2);
    if ($h == 0) {
	$ampm = "am";
	$h = 12;
    } else if ($h < 12) {
	$ampm = "am";
    } else if ($h == 12) {
	$ampm = "pm";
    } else {
	$ampm = "pm";
	$h -= 12;
    }
    return $h.":".$m.$ampm;
}

# Return the result of adding minutes to a given time of day
function endtime($eventtime, $eventduration)
{
    # parse the "h:mmpm" or "hh:mmpm" time, and convert to minutes
    if (strlen($eventtime) == 7)
    {
	$h = substr($eventtime, 0, 2);
	$m = substr($eventtime, 3, 2);
	$ampm = substr($eventtime, 5, 2);
    }
    else
    {
	$h = substr($eventtime, 0, 1);
	$m = substr($eventtime, 2, 2);
	$ampm = substr($eventtime, 4, 2);
    }
    if ($h == 12 && $ampm == "am")
	$h = 0;
    else if ($h != 12 && $ampm == "pm")
	$h += 12;
    $m += $h * 60;

    # add the duration minutes
    $m += $eventduration;

    # break it down into hours and minutes again
    $h = floor($m / 60) % 24;
    $m = $m % 60;

    # convert it back into an "h:mmpm" or "hh:mmpm" string
    if ($h == 0) {
	$ampm = "am";
	$h = 12;
    } else if ($h < 12) {
	$ampm = "am";
    } else if ($h == 12) {
	$ampm = "pm";
    } else {
	$ampm = "pm";
	$h -= 12;
    }
    if ($m < 10)
	$m = "0".$m;
    return $h.":".$m.$ampm;	
}

# Mangle an email address.  The result is an HTML string that uses images
# and superfluous tags to make the email address very hard for a spammer
# to harvest, but it still looks fairly normal on the screen.
function mangleemail($email)
{
    if ($email == "")
	return "";
    $mangle = str_replace("@", "<img border=0 src=\"".IMAGES."/at.gif\" alt=\" at \">", $email);
    $mangle = str_replace(".com", "<img border=0 src=\"".IMAGES."/dotcom.gif\" alt=\" daht comm\">", $mangle);
    $mangle = str_replace(".org", "<img border=0 src=\"".IMAGES."/dotorg.gif\" alt=\" daht oh are gee\">", $mangle);
    $mangle = str_replace(".net", "<img border=0 src=\"".IMAGES."/dotnet.gif\" alt=\" daht nett\">", $mangle);
    $mangle = str_replace(".edu", "<img border=0 src=\"".IMAGES."/dotedu.gif\" alt=\" daht eedee you\">", $mangle);
    $mangle = str_replace(".us", "<img border=0 src=\"".IMAGES."/dotus.gif\" alt=\" daht you ess\">", $mangle);
    $mangle = substr($mangle,0,1)."<span>".substr($mangle,1)."</span>";
    return $mangle;
}

# Convert an event's description to HTML.  This involves replacing HTML
# special characters with their equivalent entities, replacing "@" with
# an image of an "at" sign (a light form of email mangling), interpreting
# asterisks as boldface markers, replacing convering URLs to links.
function htmldescription($descr)
{
    $html = preg_replace("/\n\t ]+$/", "", $descr);
    $html = str_replace("&", "&amp;", $html);
    $html = str_replace("<", "&lt;", $html);
    $html = str_replace(">", "&gt;", $html);
    $html = str_replace("  ", " &nbsp;", $html);
    $html = str_replace("\n\n", "<p>", $html);
    $html = str_replace("\n", "<br>", $html);
    $html = preg_replace("/\*([0-9a-zA-Z][0-9a-zA-Z,.!?'\" ]*[0-9a-zA-Z,.!?'\"])\*/", "<strong>$1</strong>", $html);
    $html = str_replace("@", "<img src=\"".IMAGES."/at.gif\" alt=\"[at]\">", $html);
    $html = preg_replace("/(http:\/\/[^ \t\r\n\"]*[a-zA-Z0-9\/])/", "<a href=\"$1\" class=\"smallhref\">$1</a>", $html);
    $html = preg_replace("/([^\\/])(www\\.[0-9a-zA-Z-.]*[0-9a-zA-Z-])($|[^\\/])/", "$1<a href=\"http://$2/\" class=\"smallhref\">$2</a>$3", $html);

    return $html;
}

# Return the URL for a calendar view that includes a given date.  The $date
# should be supplied in MySQL's "YYYY-MM-DD" format.  The $id indicate which
# event on that date we want to see.
function viewurl($date, $id)
{
    # If no date, then just use view3week.php
    if (!$date)
	return "view3week.php";

    # Extract the day-of-month from the date.  It'll be incorporated into
    # the returned URL.
    list($yyyy, $mm, $dd) = split("-", $date);

    # if within palooza event's range, then use that calendar
    if ($date >= constant("PSTART") && $date <= constant("PEND"))
	return constant("PPAGE")."#$dd-$id";

    # if within range of the 3-week calendar, use that
    $numweeks = 3;
    $now = getdate();
    $noon = $now[0] + (12 - $now["hours"]) * 3600; # approximately noon today
    $startdate = $noon - 86400 * $now["wday"];
    $enddate = $startdate + ($numweeks * 7 - 1) * 86400;
    $startdate = date("Y-m-d", $startdate);
    $enddate = date("Y-m-d", $enddate);
    if ($date >= $startdate && $date <= $enddate)
	return "view3week.php#$dd-$id";

    # otherwise we'll need to use the monthly calendar, and pass is a specific
    # month and year.
    return "viewmonth.php?year=$yyyy&month=$mm&startdate=$startdate&enddate=$enddate#$dd-$id";
}

#ex:set shiftwidth=4 embedlimit=99999:
?>
