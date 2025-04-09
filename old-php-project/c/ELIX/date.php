<?php
/**
 * @author Edwards
 * @copyright 2015
 * 
 * 
 * CODE PARTIALS FROM
 *  - https://github.com/bigfishtv/partial-date/blob/master/src/Bigfish/PartialDate.php
 * 
 */
namespace ELIX;

class DATE{
    public function formatMinutes($minutes){
        $minutes = (int)$minutes;
        $h = floor($minutes / 60);
        $minutes = $minutes % 60;
        $ha =array();
        if($h){
            $ha[] = $h>1?"$h hours":"$h hour";
        }
        if($minutes){
            $ha[] = $minutes>1?"$minutes minutes":"$minutes minute";
        }
        return implode(' and ',$ha);
    }
    public function getPartialDate($date){
        return new partial_date($date);
    }
    public function getPartialTime($time){
        return new partial_time($time);
    }
    public function getAgeCalculator($dob='now',$now='now'){
        return  new age_calulator($dob,$now);
    }
    static function getRelativeDate($seconds) {
        $secs = (int)$seconds;
		static $minute = 60;
		static $hour = 3600;
		static $day = 86400;
		static $week = 604800;
		$month = $day*30;
		$year = $day*365;
 
		if ($secs <= 1) { $output = "now";
		}elseif ($secs <= $minute) { $output = $secs." seconds";
		}elseif ($secs <= $hour) { $output = round($secs/$minute)." minutes";
		}elseif ($secs <= $day) { $output = round($secs/$hour)." hours";
		}elseif ($secs <= $week) { $output = round($secs/$day)." days";
		}elseif ($secs <= $month) { $output = round($secs/$week)." weeks";
		}elseif ($secs <= $year) { $output = round($secs/$month)." months";
		}elseif ($secs <= $year*10) { $output = round($secs/$year)." years";
		}else{ $output = " more than a decade"; }
        
        if(substr($output,0,2)=='1 '){
            $output = substr($output,0,-1);
        }
 
		return $output;
	}
    static function get($time, $timezone=NULL){
        return new dateobject($time, $timezone);
    }
    static function dateRange( $first, $last, $step = '+1 day', $format = 'Y-m-d' ) {
    
    	$dates = array();
    	$current = strtotime( $first );
    	$last = strtotime( $last );
        
        if ($current < $last){
            $temp = strtotime( $step, $current );
            if ($temp <= $current){
                throw new DomainException('Invalid step');
            }
            while( $current <= $last ) {
        		$dates[] = date( $format, $current );
        		$current = strtotime( $step, $current );
        	}
        } else {
            $temp = strtotime( $step, $current );
            if ($temp >= $current){
                throw new DomainException('Invalid step');
            }
            while( $current >= $last ) {
        		$dates[] = date( $format, $current );
        		$current = strtotime( $step, $current );
        	}
        }
    	return $dates;
    }
}
class dateobject extends \DateTime
{
    
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
class partial_date{
    
    protected $parts =array();
    public function __construct($dateString = null) {
		$this->parse($dateString);
	}
    public function __get($name) {
        if(isset($this->parts[$name])){
            return $this->parts[$name];
        }
        return '';
    }
    public function __call($name, $arguments) {
        if(isset($this->parts[$name])){
            return $this->parts[$name];
        }
        return '';
    }


    public function parse($dateString ='') {
        $this->parts =array();
        
        $dateString = trim($dateString);
        $this->parts['K'] = $dateString;
        $year = $month = $day = '';
        
		// matches SQL format YYYY-MM-DD
		if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $dateString, $matches)) {
            $year = $matches[1];
            $month = $matches[2];
            $day = $matches[3];
		}else if (preg_match('/^\d{4}$/', $dateString, $matches)) {
		  // matches Aus Date format YYYY
			$year = $dateString;
		}else if (preg_match('/^(\d{1,2})\/(\d\d(\d\d)?)$/', $dateString, $matches)) {
			// matches Aus Date format MM/YY and MM/YYYY
            $year = $matches[2];
			$month = $matches[1];
		}else  if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d\d(\d\d)?)$/', $dateString, $matches)) {
			// matches Aus Date format DD/MM/YYYY
            $year = $matches[3];
			$month = $matches[2];
			$day = $matches[1];
			$iday = (int)$day;
            $imonth = (int)$month;
            if($imonth > 12 && $iday < 13){
                $month = $matches[1];
                $day = $matches[2];
            }
		}else{
            $dateString = str_replace(array('-','/','.','\\'),'',trim($dateString));
            $year = substr($dateString,0,4);
            $month = substr($dateString,4,2);
            $day = substr($dateString,6,2);
		}
                
        if(strlen($year) < 4){
            $year = substr(date('Y') - ($year - date('y') > 15 ? 100 : 0), 0, 2) . $year;
        }
        
        $iday = (int)$day;
        $imonth = (int)$month;
        $iyear = (int)$year;
        
        
        $d =array();
        $d[] = $iyear?$iyear:date('Y');
        $d[] = $imonth?$imonth:date('m');
        $d[] = $iday?$iday:date('d');
        $DT =date_create(implode('-',$d));
        if($DT ===false){
            $DT = date_create();
        }
        
        $this->parts['k'] = $DT->format('Y-m-d');
        
        $this->parts['d'] = $day;
        $this->parts['j'] = $iday;
        $arr = array('D','l','N','S','w','z','W');
        if($iday){
            foreach($arr as $k){
                $this->parts[$k] = $DT->format($k);
            }
            if(!$iyear){
                $this->parts['W'] = '';
                $this->parts['z'] = '';
            }
            if(!$imonth){
                $this->parts['D'] = '';
                $this->parts['l'] = '';
                $this->parts['N'] = '';
                $this->parts['S'] = '';
                $this->parts['w'] = '';
            }
        }else{
            foreach($arr as $k){
                $this->parts[$k] = '';
            }
        }
        
        $this->parts['m'] = $month;
        $this->parts['n'] = $imonth;
        $arr = array('F','M','t');
        if($imonth){
            foreach($arr as $k){
                $this->parts[$k] = $DT->format($k);
            }
            if(!$iyear){
                $this->parts['t'] = '';
            }
        }else{
            foreach($arr as $k){
                $this->parts[$k] = '';
            }
        }
        $this->parts['Y'] = $year;
        $this->parts['y'] = substr($year,-2);
        $arr = array('L','o');
        if($iyear){
            
            foreach($arr as $k){
                $this->parts[$k] = $DT->format($k);
            }
            if(!$iyear){
                $this->parts['t'] = '';
            }
        }else{
            foreach($arr as $k){
                $this->parts[$k] = '';
            }
        }
        $this->parts['c'] = trim("$year-$month-$day",'-');
        $this->parts['q'] = sprintf('%s-%s-%s', $iyear, str_pad($imonth, 2, '0', STR_PAD_LEFT), str_pad($iday, 2, '0', STR_PAD_LEFT));
    	
        return $this;
    }
    public function __toString() {
        return $this->parts['c'];
    }
    /**
     * 
     * all specifiers work like php DATE but
     * K - return orignal passed string (trimmed)
     * k - return approximate date used
     * c - return formatted partial date
     * q - SQL format ( Format a partial date into SQL Format. YYYY-MM-DD.)
     * 
     */
    public function format($format='') {
        $ss = str_split($format);
        $r ='';
        $inEscape = false;
        foreach($ss as $s){
            if($s ==' ' || $inEscape){
                $r .= $s;
                $inEscape = false;
            }elseif($s =='\\' ){
                $inEscape = true;
            }else{
                if(isset($this->parts[$s])){
                    $r .= $this->parts[$s];
                }
            }
        }
        return $r;
    }


}

class partial_time{
    
    protected $parts =array();
    public function __construct($timeString = null) {
		$this->parse($timeString);
	}
    public function __get($name) {
        if(isset($this->parts[$name])){
            return $this->parts[$name];
        }
        return '';
    }
    public function __call($name, $arguments) {
        if(isset($this->parts[$name])){
            return $this->parts[$name];
        }
        return '';
    }


    public function parse($timeString ='') {
        $this->parts =array();
        
        $timeString = trim($timeString);
        $this->parts['K'] = $this->parts['k'] = $timeString;
        $hour = $minute = $second = '';
        
		// matches SQL format HH:MM:SS
		if (preg_match('/^(\d{1,2}):(\d{1,2}):(\d{1,2})$/', $timeString, $matches)) {
            $hour = $matches[1];
            $minute = $matches[2];
            $second = $matches[3];
		}else if (preg_match('/^\d{1,2}$/', $timeString, $matches)) {
		  // matches Time format HH
			$hour = $timeString;
		}else if (preg_match('/^(\d{1,2}):(\d{1,2})$/', $timeString, $matches)) {
            // matches Time format HH:MM
            $hour = $matches[1];
            $minute = $matches[2];
		}else if (preg_match('/^(\d{1,2}).(\d{1,2})$/', $timeString, $matches)) {
            // matches Time format MM.SS
            $minute = $matches[1];
            $second = $matches[2];
		}else{
            $timeString = str_replace(array('-','/','.','\\'),'',trim($timeString));
            $hour = substr($timeString,0,2);
            $minute = substr($timeString,2,2);
            $second = substr($timeString,4,2);
		}
        
        $isecond = (int)$second;
        $iminute = (int)$minute;
        $ihour = (int)$hour;
        $mhour = $ihour %24;
        $is_am = false;
        
        if(stripos($timeString,'am')){
            $this->parts['a'] = 'am';
            $is_am = true;
        }else if(stripos($timeString,'pm')){
            $this->parts['a'] = 'pm';
            if($ihour < 12){
                $ihour +=12;
            }
        }else if( $mhour < 12){
            $this->parts['a'] = 'am';
            $is_am = true;
        }else{
            $this->parts['a'] = 'pm';
        }
        $this->parts['A'] = strtoupper($this->parts['a']);
        
        
        $arr = array('B','g','G','h','H','i','s','u','v','e','I','O','P','T','Z','c','r','U');
        foreach($arr as $k){
            $this->parts[$k] = '';
        }
        if($hour){
            $is_24 = ($ihour==0) || ($ihour>12);
            $is_real = ($ihour < 24);
            if($is_24){
                $hour24 = $ihour;
                if($ihour == 0){
                    $hour12 = 12;
                }else if ($ihour < 13){
                    $hour12 = $ihour;
                }else if ($ihour < 24){
                    $hour12 = $ihour-12;
                } else {
                    $hour12 = $ihour;
                }
            }else{
                $hour12 = $ihour;
                $hour24 = $ihour+12;
                if($hour24 == 24){
                    if($is_am){
                        $hour24 =0;
                    }else{
                        $hour24 =12;
                    }
                }
                
            }
            $this->parts['G'] = $hour24;
            $this->parts['H'] = str_pad($hour24, 2, '0', STR_PAD_LEFT);
            $this->parts['g'] = $hour12;
            $this->parts['h'] = str_pad($hour12, 2, '0', STR_PAD_LEFT);
            
        }
        if($minute){
            $this->parts['i'] = str_pad($iminute, 2, '0', STR_PAD_LEFT);
        }
        if($second){
            $this->parts['s'] = str_pad($isecond, 2, '0', STR_PAD_LEFT);
        }
        $this->parts['c'] = trim("$hour:$minute:$second",':');
        $this->parts['q'] = trim(sprintf('%s:%s:%s', $ihour, str_pad($iminute, 2, '0', STR_PAD_LEFT), str_pad($isecond, 2, '0', STR_PAD_LEFT)),':');
    	
        return $this;
    }
    public function __toString() {
        return $this->parts['c'];
    }
    /**
     * 
     * all specifiers work like php DATE but
     * K - return orignal passed string (trimmed)
     * k - return approximate time used
     * c - return formatted partial time
     * q - SQL format ( Format a partial date into SQL Format. HH:MM:SS.)
     * 
     */
    public function format($format='') {
        $ss = str_split($format);
        $r ='';
        $inEscape = false;
        foreach($ss as $s){
            if($s ==' ' || $inEscape){
                $r .= $s;
                $inEscape = false;
            }elseif($s =='\\' ){
                $inEscape = true;
            }else{
                if(isset($this->parts[$s])){
                    $r .= $this->parts[$s];
                } else {
                    $r .= $s;
                }
            }
        }
        return $r;
    }


}


class age_calulator{
    private $dob;
    private $now;
    private $months;
    private $y;
    private $m;
    public function __construct($dob,$now='now') {
        $this->setDob($dob);
        $this->setNow($now);
    }
    public function __get($name) {
        if(method_exists($this,$name)){
            return $this->$name();
        }
        return '';
    }

    protected function recalc(){
        static $mde = null;
        if($mde === null){
            $d = date_create();
            $mde = method_exists($d,'diff');
        }
        if(empty($this->dob) || empty($this->now)){
            $this->y = 0;
            $this->m = 0;
            $this->months = 0;
            return;
        }
        $now = $this->now;
        $dob = $this->dob;
        if( $mde){
            $interval = $now->diff($dob);
            $yrs = $interval->y;
            $mth = $interval->m;
			if($mth ==0 && $interval->d>1) $mth =1;
			elseif($mth && $interval->d>1 && $interval->d<31) $mth++;
			if($mth>=12){ $yrs++; $mth -= 12; }
            
        }else{
            $dyear = $dob->format('Y');$dmonth=$dob->format('m');$dday=$dob->format('d');
            $year  = $now->format('Y');$month =$now->format('m');$day =$now->format('d');
            
            $t = mktime(0,0,0,$month,$day,$year);
            $dt = mktime(0,0,0,$dmonth,$dday,$dyear);
            $diff = $t -$dt;
            $days = floor($diff/(60*60*24));
            
            $yrs = floor($days / 365);
            $diff = $days % 365;
            
            $mth = floor($diff / 30);
            if($mth>=12)
            {
                $yrs += floor($mth /12);
                $mth = $mth % 12; 
            }
        }
        $this->y = (int)$yrs;
        $this->m = (int)$mth;
        $this->months = ($this->y * 12) + $this->m;
    }
    public function setDob($date){
        if(empty($date)){
            $this->dob = null;
        }else{
            $date = trim($date);
            $d = date_create($date);
            if($d === false){
                $d = date_create();
            }
            $this->dob = $d;
        }
        if($this->now)$this->recalc();
    }
    public function setNow($date){
        $date = trim($date);
        $d = date_create($date);
        if($d === false){
            $d = date_create();
        }
        $this->now = $d;
        if($this->dob)$this->recalc();
    }
    
    public function age(){
        $y = $this->year();
        $m = $this->month();
        return trim("{$y} {$m}");
    }
    public function months(){
        return $this->months;
    }
    public function m(){
        return $this->m;
    }
    public function y(){
        return $this->y;
    }
    public function year(){
        $yrs = $this->y;
        if($yrs) $y = ($yrs==1)?"{$yrs} yr" : "{$yrs} yrs";
        else     $y = '';
		return $y;
    }
    public function month(){
        $mth = $this->m;
		if($mth) $m = ($mth==1)?"{$mth} mth": "{$mth} mths";
		else 	 $m ='';
        return $m;
    }
    public function __toString() {
        return $this->age();
    }
    public function toString() {
        return $this->age();
    }
}