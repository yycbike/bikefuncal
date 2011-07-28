<?php
// Utility functions
//
// These could probably be moved into other, more appropriate, files.

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
    if ($email == "") {
	return "";
    }

    $image_dir = plugins_url('bikefuncal/images');

    $mangle = str_replace("@", "<img border=0 src='${image_dir}/at.gif' alt=' at '>", $email);
    $mangle = str_replace(".com", "<img border=0 src='${image_dir}/dotcom.gif' alt=' daht comm'>", $mangle);
    $mangle = str_replace(".org", "<img border=0 src='${image_dir}/dotorg.gif' alt=' daht oh are gee'>", $mangle);
    $mangle = str_replace(".net", "<img border=0 src='${image_dir}/dotnet.gif' alt=' daht nett'>", $mangle);
    $mangle = str_replace(".edu", "<img border=0 src='${image_dir}/dotedu.gif' alt=' daht eedee you'>", $mangle);
    $mangle = str_replace(".us", "<img border=0 src='${image_dir}/dotus.gif' alt=' daht you ess'>", $mangle);
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
    $html = str_replace("@", "<img src=\"images/at.gif\" alt=\"[at]\">", $html);
    $html = preg_replace("/(http:\/\/[^ \t\r\n\"]*[a-zA-Z0-9\/])/", "<a href=\"$1\" class=\"smallhref\">$1</a>", $html);
    $html = preg_replace("/([^\\/])(www\\.[0-9a-zA-Z-.]*[0-9a-zA-Z-])($|[^\\/])/", "$1<a href=\"http://$2/\" class=\"smallhref\">$2</a>$3", $html);

    return $html;
}

#ex:set shiftwidth=4 embedlimit=99999:
?>
