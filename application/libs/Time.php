<?php


class Time {

/* The format to use when formatting a time using `Time::niceFormat()`
 *
 * The format should use the locale strings as defined in the PHP docs under
 *
 * @var string
 */
	public static $niceFormat = '%a, %b %eS %Y, %H:%M';

/**
 * The format to use when formatting a time using `Time::niceShortFormat()`
 * and the difference is between 3 and 7 days
 *
 * @var string
 */
	public static $niceShortFormat = '%B %d, %H:%M';

	public static $niceAlternateFormat = '%B %d, %Y at %I:%M %p';

	public static $niceAlternateFormat_two = '%B %d, %Y';

/**
 * Temporary variable containing the timestamp value, used internally in convertSpecifiers()
 *
 * @var integer
 */
	protected static $_time = null;



/**
 * Convert from string to time
 *
 * @var integer
 */

   public static function dateToTime($datetime="") {
   $unixdatetime = strtotime($datetime);
   return strftime(self::$niceAlternateFormat, $unixdatetime);
   }



   public static function timeToDate($datetime="") {
   $unixdatetime = strtotime($datetime);
   return strftime(self::$niceAlternateFormat_two, $unixdatetime);
   }


    public static function unixdatetimeToDate($unixdatetime="") {
   	if(empty($unixdatetime)):
   	return 'Classified';
   		else:
   return strftime(self::$niceAlternateFormat_two, $unixdatetime);
		endif;
   }



/**
 * Convert from unix time to time
 *
 * @var integer
 */
   public static function unixdatetime_to_text($unixdatetime="") {
   	if(empty($unixdatetime)):
   	return 'Classified';
   		else:
   return strftime(self::$niceAlternateFormat, $unixdatetime);
		endif;
   }

/**
 * Returns a UNIX timestamp, given either a UNIX timestamp or a valid strtotime() date string.
 *
 * @param integer|string|DateTime $dateString UNIX timestamp, strtotime() valid string or DateTime object
 * @param string|DateTimeZone $timezone Timezone string or DateTimeZone object
 * @return string Parsed timestamp
 */
  public static function fromString($dateString, $timezone = null) {
	  if (empty($dateString)) {
			return false;
		}

		$containsDummyDate = (is_string($dateString) && substr($dateString, 0, 10) === '0000-00-00');
		if ($containsDummyDate) {
			return false;
		}
		
		if (is_int($dateString) || is_numeric($dateString)) {
			$date = intval($dateString);		
		} else {
			$date = strtotime($dateString);
		}

		if ($date === -1 || empty($date)) {
			return false;
		}
		return $date;
   }
/**
 * Returns a partial SQL string to search for all records between two dates.
 *
 * @param integer|string|DateTime $begin UNIX timestamp, strtotime() valid string or DateTime object
 * @param integer|string|DateTime $end UNIX timestamp, strtotime() valid string or DateTime object
 * @param string $fieldName Name of database field to compare with
 * @param string|DateTimeZone $timezone Timezone string or DateTimeZone object
 * @return string Partial SQL string.
 */
	public static function daysAsSql($begin, $end, $fieldName, $timezone = null) {
		$begin = self::fromString($begin, $timezone);
		$end = self::fromString($end, $timezone);
		$begin = date('Y-m-d', $begin) . ' 00:00:00';
		$end = date('Y-m-d', $end) . ' 23:59:59';

		return "($fieldName >= '$begin') AND ($fieldName <= '$end')";
	}

/**
 * Returns a partial SQL string to search for all records between two times
 * occurring on the same day.
 *
 * @param integer|string|DateTime $dateString UNIX timestamp, strtotime() valid string or DateTime object
 * @param string $fieldName Name of database field to compare with
 * @param string|DateTimeZone $timezone Timezone string or DateTimeZone object
 * @return string Partial SQL string.
 */
	public static function dayAsSql($dateString, $fieldName, $timezone = null) {
		return self::daysAsSql($dateString, $dateString, $fieldName, $timezone);
	}

/**
 * Returns true if given datetime string is today.
 *
 * @param integer|string|DateTime $dateString UNIX timestamp, strtotime() valid string or DateTime object
 * @param string|DateTimeZone $timezone Timezone string or DateTimeZone object
 * @return boolean True if datetime string is today
 */
	public static function isToday($dateString, $timezone = null) {
		$timestamp = self::fromString($dateString, $timezone);
		$now = self::fromString('now', $timezone);
		return date('Y-m-d', $timestamp) == date('Y-m-d', $now);
	}

/**
 * Returns true if given datetime string is in the future.
 *
 * @param integer|string|DateTime $dateString UNIX timestamp, strtotime() valid string or DateTime object
 * @param string|DateTimeZone $timezone Timezone string or DateTimeZone object
 * @return boolean True if datetime string is in the future
 */
	public static function isFuture($dateString, $timezone = null) {
		$timestamp = self::fromString($dateString, $timezone);
		return $timestamp > time();
	}

/**
 * Returns true if given datetime string is in the past.
 *
 * @param integer|string|DateTime $dateString UNIX timestamp, strtotime() valid string or DateTime object
 * @param string|DateTimeZone $timezone Timezone string or DateTimeZone object
 * @return boolean True if datetime string is in the past
 */
	public static function isPast($dateString, $timezone = null) {
		$timestamp = self::fromString($dateString, $timezone);
		return $timestamp < time();
	}

/**
 * Returns true if given datetime string is within this week.
 *
 * @param integer|string|DateTime $dateString UNIX timestamp, strtotime() valid string or DateTime object
 * @param string|DateTimeZone $timezone Timezone string or DateTimeZone object
 * @return boolean True if datetime string is within current week
 */
	public static function isThisWeek($dateString, $timezone = null) {
		$timestamp = self::fromString($dateString, $timezone);
		$now = self::fromString('now', $timezone);
		return date('W o', $timestamp) === date('W o', $now);
	}

/**
 * Returns true if given datetime string is within this month
 *
 * @param integer|string|DateTime $dateString UNIX timestamp, strtotime() valid string or DateTime object
 * @param string|DateTimeZone $timezone Timezone string or DateTimeZone object
 * @return boolean True if datetime string is within current month
 */
	public static function isThisMonth($dateString, $timezone = null) {
		$timestamp = self::fromString($dateString, $timezone);
		$now = self::fromString('now', $timezone);
		return date('m Y', $timestamp) === date('m Y', $now);
	}

/**
 * Returns true if given datetime string is within current year.
 *
 * @param integer|string|DateTime $dateString UNIX timestamp, strtotime() valid string or DateTime object
 * @param string|DateTimeZone $timezone Timezone string or DateTimeZone object
 * @return boolean True if datetime string is within current year
 */
	public static function isThisYear($dateString, $timezone = null) {
		$timestamp = self::fromString($dateString, $timezone);
		$now = self::fromString('now', $timezone);
		return date('Y', $timestamp) === date('Y', $now);
	}

/**
 * Returns true if given datetime string was yesterday.
 *
 * @param integer|string|DateTime $dateString UNIX timestamp, strtotime() valid string or DateTime object
 * @param string|DateTimeZone $timezone Timezone string or DateTimeZone object
 * @return boolean True if datetime string was yesterday
 */
	public static function wasYesterday($dateString, $timezone = null) {
		$timestamp = self::fromString($dateString, $timezone);
		$yesterday = self::fromString('yesterday', $timezone);
		return date('Y-m-d', $timestamp) === date('Y-m-d', $yesterday);
	}

/**
 * Returns true if given datetime string is tomorrow.
 *
 * @param integer|string|DateTime $dateString UNIX timestamp, strtotime() valid string or DateTime object
 * @param string|DateTimeZone $timezone Timezone string or DateTimeZone object
 * @return boolean True if datetime string was yesterday
 */
	public static function isTomorrow($dateString, $timezone = null) {
		$timestamp = self::fromString($dateString, $timezone);
		$tomorrow = self::fromString('tomorrow', $timezone);
		return date('Y-m-d', $timestamp) === date('Y-m-d', $tomorrow);
	}

/**
 * Returns the quarter
 *
 * @param integer|string|DateTime $dateString UNIX timestamp, strtotime() valid string or DateTime object
 * @param boolean $range if true returns a range in Y-m-d format
 * @return mixed 1, 2, 3, or 4 quarter of year or array if $range true
 */
	public static function toQuarter($dateString, $range = false) {
		$time = self::fromString($dateString);
		$date = ceil(date('m', $time) / 3);
		if ($range === false) {
			return $date;
		}

		$year = date('Y', $time);
		switch ($date) {
			case 1:
				return array($year . '-01-01', $year . '-03-31');
			case 2:
				return array($year . '-04-01', $year . '-06-30');
			case 3:
				return array($year . '-07-01', $year . '-09-30');
			case 4:
				return array($year . '-10-01', $year . '-12-31');
		}
	}

/**
 * Returns a UNIX timestamp from a textual datetime description. Wrapper for PHP function strtotime().
 *
 * @param integer|string|DateTime $dateString UNIX timestamp, strtotime() valid string or DateTime object
 * @param string|DateTimeZone $timezone Timezone string or DateTimeZone object
 * @return integer Unix timestamp
 */
	public static function toUnix($dateString, $timezone = null) {
		return self::fromString($dateString, $timezone);
	}



/**
 * Returns a date formatted for Atom RSS feeds.
 *
 * @param string $dateString Datetime string or Unix timestamp
 * @param string|DateTimeZone $timezone Timezone string or DateTimeZone object
 * @return string Formatted date string
 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/time.html#TimeHelper::toAtom
 */
	public static function toAtom($dateString, $timezone = null) {
		return date('Y-m-d\TH:i:s\Z', self::fromString($dateString, $timezone));
	}



/**
 * Returns either a relative or a formatted absolute date depending
 * on the difference between the current time and given datetime.
 * $datetime should be in a *strtotime* - parsable format, like MySQL's datetime datatype.
 * Relative dates look something like this:
 *
 * - 3 weeks, 4 days ago
 * - 15 seconds ago
 *
 * Default date formatting is d/m/yy e.g: on 18/2/09
 *
 * The returned string includes 'ago' or 'on' and assumes you'll properly add a word
 * like 'Posted ' before the function output.
 *
 * NOTE: If the difference is one week or more, the lowest level of accuracy is day
 *
 * @param integer|string|DateTime $dateTime Datetime UNIX timestamp, strtotime() valid string or DateTime object
 * @param array $options Default format if timestamp is used in $dateString
 * @return string Relative time string.
 */

function timeAgoInWords($date)
 
{
 
if(empty($date)) {
 
return "No date provided";
 
}
 
$periods = array("second", "minute", "hour", "day", "week", "month", "year", "decade");
 
$lengths = array("60","60","24","7","4.35","12","10");
 
$now = time();
 
$unix_date = strtotime($date);
 
// check validity of date
 
if(empty($unix_date)) {
 
return "Bad date";
 
}
 
// is it future date or past date
 
if($now > $unix_date) {
 
$difference = $now - $unix_date;
 
$tense = "ago";
 
} else {
 
$difference = $unix_date - $now;
$tense = "from now";}
 
for($j = 0; $difference >= $lengths[$j] && $j < count($lengths)-1; $j++) {
 
$difference /= $lengths[$j];
 
}
 
$difference = round($difference);
 
if($difference != 1) {
 
$periods[$j].= "s";
 
}
 
return "$difference $periods[$j] {$tense}";
 
}



/**
 * Returns gmt as a UNIX timestamp.
 *
 * @param integer|string|DateTime $dateString UNIX timestamp, strtotime() valid string or DateTime object
 * @return integer UNIX timestamp
 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/time.html#TimeHelper::gmt
 */
	public static function gmt($dateString = null) {
		$time = time();
		if ($dateString) {
			$time = self::fromString($dateString);
		}
		return gmmktime(
			intval(date('G', $time)),
			intval(date('i', $time)),
			intval(date('s', $time)),
			intval(date('n', $time)),
			intval(date('j', $time)),
			intval(date('Y', $time))
		);
	}



}
