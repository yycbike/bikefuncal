<?php

add_action('template_redirect', 'bfc_template_redirect');
function bfc_template_redirect() {
    if ($_SERVER['REQUEST_URI']==='/festival.ics') {
        global $calevent_table_name;
        global $caldaily_for_listings_table_name;
        global $caldaily_num_days_for_listings_table_name;
        global $wpdb;

        $query = <<<END_SQL
            SELECT *
            FROM ${calevent_table_name} JOIN ${caldaily_for_listings_table_name} USING(id)
            WHERE eventdate >= %s AND eventdate <= %s
            ORDER BY
                eventdate ASC,
                eventtime ASC,
                title ASC
END_SQL;

        $query = $wpdb->prepare($query,
            get_option('bfc_festival_start_date'),
            get_option('bfc_festival_end_date'));
        $records = $wpdb->get_results($query, ARRAY_A);
        $today = null;

		date_default_timezone_set('America/Edmonton');

        require_once("vendor/autoload.php");

        header("Content-Type: text/calendar; charset=utf-8", true, 200);
        header("Content-Disposition: attachment; filename=festival.ics");
        header('Pragma: no-cache');
        header('Expires: 0');

        $vCalendar = new \Eluceo\iCal\Component\Calendar('cyclepalooza.com');
		$vCalendar->setName('Cyclepalooza Festival');
        foreach ($records as $record) {
            // Skip everything excepte As-Scheduled
            if ($record['eventstatus'] !== 'A') {
                continue;
            }
            $vEvent = new \Eluceo\iCal\Component\Event();

			$eventstart = new \DateTime($record['eventdate'] . 'T' . $record['eventtime']);
			//$eventstart->setTime
			$str = 'PT' . $record['eventduration'] . 'M';
			$duration = new \DateInterval($str);
			$eventend = clone $eventstart ;//->add($duration);
			//echo $record['eventduration'];
            $vEvent
                ->setDtStart($eventstart)
                ->setDtEnd($eventend->add($duration))
                ->setSummary($record['title'])
            ;
			$vEvent->setUseTimezone(true);

            $vCalendar->addComponent($vEvent);
        }

        echo $vCalendar->render();

        exit();
    }
}

?>
