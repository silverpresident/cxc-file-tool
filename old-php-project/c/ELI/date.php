<?php
/**
 * @author Edwards
 * @copyright  2013
 */
class ELI_date extends DateTime
{
    static function relativedate($secs) {
		$second = 1;
		$minute = 60;
		$hour = 60*60;
		$day = 60*60*24;
		$week = 60*60*24*7;
		$month = 60*60*24*7*30;
		$year = 60*60*24*7*30*365;
 
		if ($secs <= 0) { $output = "now";
		}elseif ($secs > $second && $secs < $minute) { $output = round($secs/$second)." second";
		}elseif ($secs >= $minute && $secs < $hour) { $output = round($secs/$minute)." minute";
		}elseif ($secs >= $hour && $secs < $day) { $output = round($secs/$hour)." hour";
		}elseif ($secs >= $day && $secs < $week) { $output = round($secs/$day)." day";
		}elseif ($secs >= $week && $secs < $month) { $output = round($secs/$week)." week";
		}elseif ($secs >= $month && $secs < $year) { $output = round($secs/$month)." month";
		}elseif ($secs >= $year && $secs < $year*10) { $output = round($secs/$year)." year";
		}else{ $output = " more than a decade"; }
 
		if ($output <> "now"){
			$output = (substr($output,0,2)<>"1 ") ? $output."s" : $output;
		}
		return $output;
	}
    public function format($format='') {
        if(empty($format)){
                if(parent::format('Ymd')==date('Ymd')){
                    return parent::format('g:i a') . ' (Today)'; 
                }elseif(parent::format('Ymd')==date_create('tomorrow')->format('Ymd')){
                    return parent::format('g:i a') . ' (Tomorrow)'; 
                }elseif(parent::format('Ymd')==date_create('yesterday')->format('Ymd')){
                    return parent::format('g:i a') . ' (Yesterday)'; 
                }elseif(parent::format('YmW')==date('YmW')){
                    return parent::format('l, g:i a'); 
                }elseif(parent::format('Y')==date('Y')){
                    return parent::format('F j (l)');
                }else{
                    return parent::format('F j, Y'); 
                }
        }else{
            return parent::format($format);
        }
    }
    public function __construct($time, $timezone=NULL) {
        if(is_object($time)) $time = (string)$time;
        parent::__construct($time, $timezone);
    }

    public function __invoke($obj) {
        return new self($obj);
    }
    public function __toString() {
        return $this->format();
    }
} 
  
?>