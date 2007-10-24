<?php
	/**
	* Datetime class that contains common date/time functions
	* @author Joseph Engo <jengo@phpgroupware.org>
	* @author Mark Peters <skeeter@phpgroupware.org>
	* @copyright Copyright (C) 2000,2001 Joseph Engo, Mark Peters
	* @copyright Portions Copyright (C) 2000-2004 Free Software Foundation, Inc. http://www.fsf.org/
	* @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
	* @package phpgwapi
	* @subpackage utilities
	* @version $Id: class.datetimefunctions.inc.php,v 1.1 2006/11/10 13:33:49 sigurdne Exp $
	*/

	$d1 = strtolower(@substr(PHPGW_API_INC,0,3));
	$d2 = strtolower(@substr(PHPGW_SERVER_ROOT,0,3));
	$d3 = strtolower(@substr(PHPGW_APP_INC,0,3));
	if($d1 == 'htt' || $d1 == 'ftp' || $d2 == 'htt' || $d2 == 'ftp' || $d3 == 'htt' || $d3 == 'ftp')
	{
		echo 'Failed attempt to break in via an old Security Hole!<br>'."\n";
		exit;
	}
	unset($d1);
	unset($d2);
	unset($d3);
		

	/**
	* Datetime class that contains common date/time functions
	*
	* @package phpgwapi
	* @subpackage utilities
	*/
	class datetimefunctions
	{
		var $tz_offset;
		var $days = Array();
		var $gmtnow = 0;
		var $users_localtime;
		var $cv_gmtdate;

		function datetimefunctions()
		{
			$this->tz_offset = 3600 * intval(@$GLOBALS['phpgw_info']['user']['preferences']['common']['tz_offset']);
			print_debug('datetime::datetime::gmtnow',$this->gmtnow,'api');

			$error_occured = True;
			// If we already have a GMT time, no need to do this again.
			if(!$this->gmtnow)
			{
				if(isset($GLOBALS['phpgw_info']['server']['tz_offset']))
				{
					$this->gmtnow = time() - (intval($GLOBALS['phpgw_info']['server']['tz_offset']) * 3600);
					//echo "<p>set via tz_offset=".$GLOBALS['phpgw_info']['server']['tz_offset'].": gmtnow=".date('Y/m/d H:i',$this->gmtnow)."</p>\n";
				}
				else
				{
					$this->gmtnow = time() - ($this->getbestguess() * 3600);
					//echo "<p>set via bestguess=".$this->getbestguess().": gmtnow=".date('Y/m/d H:i',$this->gmtnow)."</p>\n";
				}
			}
			$this->users_localtime = time() + $this->tz_offset;
		}
		
		function getntpoffset()
		{
			$error_occured = False;
			if(!@is_object($GLOBALS['phpgw']->network))
			{
				$GLOBALS['phpgw']->network = createObject('phpgwapi.network');
			}
			$server_time = time();

			if($GLOBALS['phpgw']->network->open_port('129.6.15.28',13,5))
			{
				$line = $GLOBALS['phpgw']->network->bs_read_port(64);
				$GLOBALS['phpgw']->network->close_port();

				$array = explode(' ',$line);
				// host: 129.6.15.28
				// Value returned is 52384 02-04-20 13:55:29 50 0 0   9.2 UTC(NIST) *
				print_debug('Server datetime',time(),'api');
				print_debug('Temporary NTP datetime',$line,'api');
				if ($array[5] == 4)
				{
					$error_occured = True;
				}
				else
				{
					$date = explode('-',$array[1]);
					$time = explode(':',$array[2]);
					$this->gmtnow = mktime(intval($time[0]),intval($time[1]),intval($time[2]),intval($date[1]),intval($date[2]),intval($date[0]) + 2000);
					print_debug('Temporary RFC epoch',$this->gmtnow,'api');
					print_debug('GMT',date('Ymd H:i:s',$this->gmtnow),'api');
				}
			}
			else
			{
				$error_occured = True;
			}
			
			if($error_occured == True)
			{
				return $this->getbestguess();
			}
			else
			{
				return intval(($server_time - $this->gmtnow) / 3600);
			}
		}

		function gethttpoffset()
		{
			$error_occured = False;
			if(!@is_object($GLOBALS['phpgw']->network))
			{
				$GLOBALS['phpgw']->network = createObject('phpgwapi.network');
			}
			$server_time = time();

			$filename = 'http://132.163.4.213/timezone.cgi?GMT';
			$file = $GLOBALS['phpgw']->network->gethttpsocketfile($filename);
			if(!$file)
			{
				return $this->getbestguess();
			}
			$time = strip_tags($file[55]);
			$date = strip_tags($file[56]);

			print_debug('GMT DateTime',$date.' '.$time,'api');
			$dt_array = explode(' ',$date);
			$temp_datetime = $dt_array[0].' '.substr($dt_array[2],0,-1).' '.substr($dt_array[1],0,3).' '.$dt_array[3].' '.$time.' GMT';
			print_debug('Reformulated GMT DateTime',$temp_datetime,'api');
			$this->gmtnow = $this->convert_rfc_to_epoch($temp_datetime);
			print_debug('this->gmtnow',$this->gmtnow,'api');
			print_debug('server time',$server_time,'api');
			print_debug('server DateTime',date('D, d M Y H:i:s',$server_time),'api');
			return intval(($server_time - $this->gmtnow) / 3600);
		}

		function getbestguess()
		{
			print_debug('datetime::datetime::debug: Inside getting from local server','api');
			$server_time = time();
			// Calculate GMT time...
			// If DST, add 1 hour...
			//  - (date('I') == 1?3600:0)
			$this->gmtnow = $this->convert_rfc_to_epoch(gmdate('D, d M Y H:i:s',$server_time).' GMT');
			return intval(($server_time - $this->gmtnow) / 3600);
		}

		function convert_rfc_to_epoch($date_str)
		{
			$comma_pos = strpos($date_str,',');
			if($comma_pos)
			{
				$date_str = substr($date_str,$comma_pos+1);
			}

			// This may need to be a reference to the different months in native tongue....
			$month= array(
				'Jan' => 1,
				'Feb' => 2,
				'Mar' => 3,
				'Apr' => 4,
				'May' => 5,
				'Jun' => 6,
				'Jul' => 7,
				'Aug' => 8,
				'Sep' => 9,
				'Oct' => 10,
				'Nov' => 11,
				'Dec' => 12
			);
			$dta = array();
			$ta = array();

			// Convert "15 Jul 2000 20:50:22 +0200" to unixtime
			$dta = explode(' ',$date_str);
			$ta = explode(':',$dta[4]);

			if(substr($dta[5],0,3) <> 'GMT')
			{
				$tzoffset = substr($dta[5],0,1);
				$tzhours = intval(substr($dta[5],1,2));
				$tzmins = intval(substr($dta[5],3,2));
				switch ($tzoffset)
				{
					case '-':
						(int)$ta[0] += $tzhours;
						(int)$ta[1] += $tzmins;
						break;
					case '+':
						(int)$ta[0] -= $tzhours;
						(int)$ta[1] -= $tzmins;
						break;
				}
			}
			return mktime($ta[0],$ta[1],$ta[2],$month[$dta[2]],$dta[1],$dta[3]);
		}

		function get_weekday_start($year,$month,$day)
		{
			$weekday = $this->day_of_week($year,$month,$day);
			switch($GLOBALS['phpgw_info']['user']['preferences']['calendar']['weekdaystarts'])
			{
				// Saturday is for arabic support
				case 'Saturday':
					$this->days = Array(
						0 => 'Sat',
						1 => 'Sun',
						2 => 'Mon',
						3 => 'Tue',
						4 => 'Wed',
						5 => 'Thu',
						6 => 'Fri'
					);
					switch($weekday)
					{
						case 6:
							break;
						case 0:
								if ($day == 1)
								{
								 	if ($month == 1)
									{
								  		--$year;
								  		$month = 12;
								 	}
								 	else
								 	{
								 		--$month;
								 	}
								 	$day = $this->days_in_month($month,$year);
								}
								else
								{
									--$day;
								}
							break;
						default:
								if ($day <= ($weekday + 1))
								{
								 	if ($month == 1)
									{
								  		--$year;
								  		$month = 12;
								 	}
								 	else
								 	{
								 		--$month;
								 	}
								 	$day = $this->days_in_month($month,$year) - $weekday;
								}
								else
								{
									$day -= ($weekday + 1);
								}
					}
					break;
				case 'Monday':
					$this->days = Array(
						0 => 'Mon',
						1 => 'Tue',
						2 => 'Wed',
						3 => 'Thu',
						4 => 'Fri',
						5 => 'Sat',
						6 => 'Sun'
					);
					switch($weekday)
					{
						case 1:
							break;
						case 0:
								if ($day <= 6)
								{
								 	if ($month == 1)
									{
								  		--$year;
								  		$month = 12;
								 	}
								 	else
								 	{
								 		--$month;
								 	}
								 	$day = $this->days_in_month($month,$year) + ($day - 6);
								}
								else
								{
									$day -= 6;
								}
							break;
						default:
								if ($day <= ($weekday == 0) ? 6 : ($weekday-1))
								{
						 			if ($month == 1)
									{
						  				--$year;
						  				$month = 12;
						 			}
						 			else
						 			{
						 				--$month;
						 			}
						 			$day = $this->days_in_month($month,$year) + ($day - (($weekday == 0) ? 6 : ($weekday-1)));
					   			}
					   			else
					  			{
									$day -= ($weekday-1);
								}
					}
					break;
				case 'Sunday':
				default:
					$this->days = Array(
						0 => 'Sun',
						1 => 'Mon',
						2 => 'Tue',
						3 => 'Wed',
						4 => 'Thu',
						5 => 'Fri',
						6 => 'Sat'
					);
						if ($day <= $weekday)
						{
						 	if ($month == 1)
							{
						  		--$year;
						  		$month = 12;
						 	}
						 	else
						 	{
						 		--$month;
						 	}
						 	$day = $this->days_in_month($month,$year) + ($day - $weekday);
						}
						else
						{
							$day -= $weekday;
						}
			}
			$sday = mktime(2,0,0,$month,$day,$year);
			return $sday - 7200;
		}

		function is_leap_year($year)
		{
			if ((intval($year) % 4 == 0) && (intval($year) % 100 != 0) || (intval($year) % 400 == 0))
			{
				return 1;
			}
			else
			{
				return 0;
			}
		}

		function days_in_month($month,$year)
		{
			$days = Array(
				1	=>	31,
				2	=>	28 + $this->is_leap_year(intval($year)),
				3	=>	31,
				4	=>	30,
				5	=>	31,
				6	=>	30,
				7	=>	31,
				8	=>	31,
				9	=>	30,
				10	=>	31,
				11	=>	30,
				12	=>	31
			);
			return $days[intval($month)];
		}

		function date_valid($year,$month,$day)
		{
			return checkdate(intval($month),intval($day),intval($year));
		}

		function time_valid($hour,$minutes,$seconds)
		{
			if(intval($hour) < 0 || intval($hour) > 24)
			{
				return False;
			}
			if(intval($minutes) < 0 || intval($minutes) > 59)
			{
				return False;
			}
			if(intval($seconds) < 0 || intval($seconds) > 59)
			{
				return False;
			}

			return True;
		}

		function day_of_week($year,$month,$day)
		{
			if($month > 2)
			{
				$month -= 2;
			}
			else
			{
				$month += 10;
				$year--;
			}
			$day = (floor((13 * $month - 1) / 5) + $day + ($year % 100) + floor(($year % 100) / 4) + floor(($year / 100) / 4) - 2 * floor($year / 100) + 77);
			return (($day - 7 * floor($day / 7)));
		}
	
		function day_of_year($year,$month,$day)
		{
			$days = array(0,31,59,90,120,151,181,212,243,273,304,334);

			$julian = ($days[$month - 1] + $day);

			if($month > 2 && $this->is_leap_year($year))
			{
				$julian++;
			}
			return($julian);
		}

		/**
		* Get the number of days between two dates
		*
		* @author Steven Cramer/Ralf Becker
		* @param $m1 - Month of first date
		* @param $d1 - Day of first date
		* @param $y1 - Year of first date
		* @param $m2 - Month of second date
		* @param $d2 - Day of second date
		* @param $y2 - Year of second date
		* @return integer Date 2 minus Date 1 in days
		* @internal the last param == 0, ensures that the calculation is always done without daylight-saveing
		*/
		function days_between($m1,$d1,$y1,$m2,$d2,$y2)
		{
			return intval((mktime(0,0,0,$m2,$d2,$y2,0) - mktime(0,0,0,$m1,$d1,$y1,0)) / 86400);
		}

		function date_compare($a_year,$a_month,$a_day,$b_year,$b_month,$b_day)
		{
			$a_date = mktime(0,0,0,intval($a_month),intval($a_day),intval($a_year));
			$b_date = mktime(0,0,0,intval($b_month),intval($b_day),intval($b_year));
			if($a_date == $b_date)
			{
				return 0;
			}
			elseif($a_date > $b_date)
			{
				return 1;
			}
			elseif($a_date < $b_date)
			{
				return -1;
			}
		}

		function time_compare($a_hour,$a_minute,$a_second,$b_hour,$b_minute,$b_second)
		{
			// I use the 1970/1/2 to compare the times, as the 1. can get via TZ-offest still 
			// before 1970/1/1, which is the earliest date allowed on windows
			$a_time = mktime(intval($a_hour),intval($a_minute),intval($a_second),1,2,1970);
			$b_time = mktime(intval($b_hour),intval($b_minute),intval($b_second),1,2,1970);
			if($a_time == $b_time)
			{
				return 0;
			}
			elseif($a_time > $b_time)
			{
				return 1;
			}
			elseif($a_time < $b_time)
			{
				return -1;
			}
		}

		function makegmttime($hour,$minute,$second,$month,$day,$year)
		{
			return $this->gmtdate(mktime($hour, $minute, $second, $month, $day, $year));
		}

		function localdates($localtime)
		{
			$date = Array('raw','day','month','year','full','dow','dm','bd');
			$date['raw'] = $localtime;
			$date['year'] = intval($GLOBALS['phpgw']->common->show_date($date['raw'],'Y'));
			$date['month'] = intval($GLOBALS['phpgw']->common->show_date($date['raw'],'m'));
			$date['day'] = intval($GLOBALS['phpgw']->common->show_date($date['raw'],'d'));
			$date['full'] = intval($GLOBALS['phpgw']->common->show_date($date['raw'],'Ymd'));
			$date['bd'] = mktime(0,0,0,$date['month'],$date['day'],$date['year']);
			$date['dm'] = intval($GLOBALS['phpgw']->common->show_date($date['raw'],'dm'));
			$date['dow'] = $this->day_of_week($date['year'],$date['month'],$date['day']);
			$date['hour'] = intval($GLOBALS['phpgw']->common->show_date($date['raw'],'H'));
			$date['minute'] = intval($GLOBALS['phpgw']->common->show_date($date['raw'],'i'));
			$date['second'] = intval($GLOBALS['phpgw']->common->show_date($date['raw'],'s'));
		
			return $date;
		}

		function gmtdate($localtime)
		{
			return $this->localdates($localtime - $this->tz_offset);
		}

		/**
		 * Convert a date from one format to another
		 * 
		 * @param string $date Date in source format representation
		 * @param string $formatSource Format of the passed date
		 * @param string $formatTarget Target date format
		 * @return string Date in target format representation
		 */
		function convertDate($date, $formatSource, $formatTarget)
		{
			// get format separator character
			$formatSourceSepChar = substr($formatSource,1,1);
			$formatSourceArray   = explode($formatSourceSepChar, $formatSource);
			$dateSourceArray     = explode($formatSourceSepChar, $date);

			$keyNum = count($formatSourceArray);
			$valNum = count($dateSourceArray);
			if($keyNum != $valNum)
			{
				return false;
			}

			$map_date = array();
			for($i=0; $i<$keyNum; $i++)
			{
				$key = $formatSourceArray[$i];
				$val = $dateSourceArray[$i];

				if($key == 'M')
				{
					for($j=1; $j <=12; $j++)
					{
						if(date('M',mktime(0,0,0,$j,1,2000)) == $val)
						{
							$map_date['m'] = $j;
						}
					}
				}
				else
				{
					$map_date[strtolower($key)] = intval($val);
				}
			}
			return date($formatTarget, mktime(0,0,0,$map_date['m'], $map_date['d'], $map_date['y']));
		}

	}
?>