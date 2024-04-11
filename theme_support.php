<?php
/**
 * Support functions for themes.
 */

if (! defined('ABSPATH')) {
    die('Access denied.');
}

require_once(__DIR__ . "/inc/i18n.inc");
use function \EPFL\WS\___;
use function \EPFL\WS\__x;

/**
 * Formats the start and end dates and times of an EPFL event,
 * according to whichever format first applies out of an ordered list.
 *
 * This function walks through $formats, looking for the first format
 * that applies to the $event's start and end dates and times. A
 * format is a %-escaped string in which sequences like "%Y1" mean
 * "the year of the event's start date and time" (where the "Y" token
 * has the same meaning as in the `DateTime::format` method, see
 * https://www.php.net/manual/en/datetime.format.php) ; likewise,
 * "%M2" would mean the three-letter name of the event's end month.
 * Sequences like "%d" (without a trailing 1 or 2 digit) mean "the
 * event's day", i.e. the event's start *and* end day, assuming the
 * event starts and ends on the same day; if that is not the case,
 * then this format doesn't apply and the next one is tried.
 *
 * @param $event A WordPress Post object with custom post type
 *               'epfl-memento' (or any Post object featuring similar
 *               post metas)
 *
 * @param $formats A format, or array of formats, to render the event
 *                 start and end times with. This function walks
 *                 through `$formats` in sequence, until it finds one
 *                 that applies i.e. one that doesn't have "short" %X
 *                 sequences without a trailing number, or one in
 *                 which all such "short" sequences render the same
 *                 for the event's start and end dates and times. If
 *                 no such format is found, the function returns NULL.
 */
function format_event_times ($event, $formats) {
  $start = date_create(
    sprintf('%s %s', get_post_meta($event->ID, "event_start_date", true),
            get_post_meta($event->ID, "event_start_time", true)));
  $end = date_create(
    sprintf('%s %s', get_post_meta($event->ID, "event_end_date", true),
            get_post_meta($event->ID, "event_end_time", true)));

  if (! is_array($formats)) {
    $formats = [$formats];
  }
  foreach ($formats as $format) {
    $format_parsed = preg_split(
      "/(?<!%)(%[A-Za-z][12]?)/",
      $format, -1, PREG_SPLIT_DELIM_CAPTURE);

    $retval = "";
    for ($i = 0; $i < count($format_parsed); $i++) {
      if ($i % 2 == 0) {
        $retval .= $format_parsed[$i];
      } else {
        $tok = [];
        if (! preg_match('/^%(.)([12]?)$/', $format_parsed[$i], $tok)) {
          throw new Exception("Unexpected format token: " . $format_parsed[$i]);
        }
        $format_letter = $tok[1];
        $start_fragment = $start->format($format_letter);
        $end_fragment   = $end->format($format_letter);

        $start_or_end = $tok[2];
        if ($start_fragment == $end_fragment) {
          $retval .= $start_fragment;
        } elseif ($start_or_end == 1) {
          $retval .= $start_fragment;
        } elseif ($start_or_end == 2) {
          $retval .= $end_fragment;
        } else {
          // This $format assumes that $start and $end render the same
          // at this $tok, which they don't. Try the next format:
          break 2;
        }
      }
    }

    return $retval;
  }
}
