<?php
/* * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * Web Framework 1                                       *
 * BinarySEC (c) (2000-2008) / www.binarysec.com         *
 * Author: Michael Vergoz <mv@binarysec.com>             *
 * ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~         *
 *  Avertissement : ce logiciel est protégé par la       *
 *  loi du copyright et par les traités internationaux.  *
 *  Toute personne ne respectant pas ces dispositions    *
 *  se rendra coupable du délit de contrefaçon et sera   *
 *  passible des sanctions pénales prévues par la loi.   *
 *  Il est notamment strictement interdit de décompiler, *
 *  désassembler ce logiciel ou de procèder à des        *
 *  opération de "reverse engineering".                  *
 *                                                       *
 *  Warning : this software product is protected by      *
 *  copyright law and international copyright treaties   *
 *  as well as other intellectual property laws and      *
 *  treaties. Is is strictly forbidden to reverse        *
 *  engineer, decompile or disassemble this software     *
 *  product.                                             *
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

define('CORE_DATETIME_DB_D',      1);
define('CORE_DATETIME_DB_T',      2);
define('CORE_DATETIME_DB_DT',     3);
define('CORE_DATETIME_TS',        4);

class core_datetime extends wf_agg {

	public $day;
	public $month;
	public $year;
	public $hour;
	public $minute;
	public $second;

	public function loader($wf) {
		$this->wf = $wf;
	}

	public function is_valid() {
		if($this->day > 0 && $this->month > 0 && $this->year > 0
		   && !checkdate($this->month, $this->day, $this->year))
			return(false);

		if(!(($this->second >= 0) &&($this->second < 60)
		   &&($this->minute >= 0) &&($this->minute < 60)
		   &&($this->hour >= 0) &&($this->hour < 24)))
			return(false);

		return(true);
	}

	public function convert($str, $fin, $fout) {
		if($fin == $fout)
			return($str);

		if(!$this->read($str, $fin))
			return(null);
		return($this->format($fout));
	}

	public function daydiff($str, $fin) {
		$ts = $this->convert($str, $fin, CORE_DATETIME_TIMESTAMP);
		if(!$ts)
			return(null);
		return(floor((time() - $ts) / 3600 / 24));
	}

	public function read($str, $format) {
		$this->year   = 0;
		$this->month  = 0;
		$this->day    = 0;
		$this->hour   = 0;
		$this->minute = 0;
		$this->second = 0;
		$ok = false;

		switch($format) {
			/* DB DATE */
			case CORE_DATETIME_DB_D:
				if($res = strptime($str, "%Y-%m-%d")) {
					$ok = true;
					$this->year  = $res['tm_year'] + 1900;
					$this->month = $res['tm_mon']  + 1;
					$this->day   = $res['tm_mday'];
				}
				break;

			/* DB TIME */
			case CORE_DATETIME_DB_T:
				if($res = strptime($str, "%H:%M:%S")) {
					$ok = true;
					$this->hour   = $res['tm_hour'];
					$this->minute = $res['tm_min'];
					$this->second = $res['tm_sec'];
				}

			/* DB DATE + TIME */
			case CORE_DATETIME_DB_DT:
				if($res = strptime($str, "%Y-%m-%d %H:%M:%S")) {
					$ok=true;
					$this->year   = $res['tm_year'] + 1900;
					$this->month  = $res['tm_mon']  + 1;
					$this->day    = $res['tm_mday'];
					$this->hour   = $res['tm_hour'];
					$this->minute = $res['tm_min'];
					$this->second = $res['tm_sec'];
				}
				break;

			/* TIMESTAMP */
			case CORE_DATETIME_TS:
				$ok = true;
				$t = getdate(intval($str));
				$this->year   = $t['year'];
				$this->month  = $t['mon'];
				$this->day    = $t['mday'];
				$this->hour   = $t['hours'];
				$this->minute = $t['minutes'];
				$this->second = $t['seconds'];
				break;
		}
		return($ok && $this->is_valid());
	}

	public function format($format) {
		$str = null;
		switch($format) {
			/* DB DATE */
			case CORE_DATETIME_DB_D:
				$str = sprintf(
					'%04d-%02d-%02d',
					$this->year,
					$this->month,
					$this->day
				);
				break;

			/* DB TIME */
			case CORE_DATETIME_DB_T:
				$str = sprintf(
					'%02d:%02d:%02d',
					$this->hour,
					$this->minute,
					$this->second
				);
				break;

			/* DB DATE + TIME */
			case CORE_DATETIME_DB_DT:
				$str = sprintf(
					'%04d-%02d-%02d %02d:%02d:%02d',
					$this->year,
					$this->month,
					$this->day,
					$this->hour,
					$this->minute,
					$this->second
				);
				break;

			/* TIMESTAMP */
			case CORE_DATETIME_TS:
				$str = (string) mktime(
					$this->hour,
					$this->minute,
					$this->second,
					$this->month,
					$this->day,
					$this->year
				);
				break;
		}
		return($str);
	}

}
