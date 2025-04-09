<?php
/**
 * @author Edwards
 * @copyright 2015
 *  * 
 * 
 * 
 */
namespace ELIX;

class MATH{
    /**
     * MATH::percent()
     * RETURN return round(($whole * ($percent/100))),$precision);
     * @param mixed $percent
     * @param integer $whole
     * @param integer $precision
     * @return
     */
    public static function percent($percent,$whole=1,$precision=2){
        if(strpos($whole,'.')!==false){
            $whole = (float)$whole;
        }else{
            $whole = (int)$whole;
        }
        if(strpos($percent,'.')!==false){
            $percent = (float)$percent;
        }else{
            $percent = (int)$percent;
        }
        if($percent == 0) return 0;
        if($whole   == 0) return 0;
        $r = $whole * ($percent/100);
        if($precision){
            return round($r,$precision);
        }else{
            return round($r,0);
        }
    }
    public static function percentage($part,$whole,$precision=1){
        if(strpos($whole,'.')!==false){
            $whole = (float)$whole;
        }else{
            $whole = (int)$whole;
        }
        if(strpos($part,'.')!==false){
            $part = (float)$part;
        }else{
            $part = (int)$part;
        }
        if($part == 0) return 0;
        if($whole   == 0) return 0;
        $r = 100 * ($part/$whole);
        if($precision){
            return round($r,$precision);
        }else{
            return round($r,0);
        }
    }
    public static function percentof($percent,$whole,$precision=2){
        return self::percent($percent,$whole,$precision);
    }
    public static function inversePercentOf($percent,$whole,$precision=2){
        return self::inversePercent($percent,$whole,$precision);
    }
    public static function inversePercent($percent,$whole,$precision=2){
        $r = 100 - self::percentage($percent,$whole,$precision);
        if($precision){
            return round($r,$precision);
        }else{
            return round($r,0);
        }
    }
    public static function inversePercentage($part,$whole,$precision=1){
        $r = 100 - self::percentage($part,$whole,$precision);
        if($precision){
            return round($r,$precision);
        }else{
            return round($r,0);
        }
    }
    function ordinal($number){
        // Validate and translate our input
        if ( is_numeric($number) )
        {
          // Get the last two digits (only once)
          $n = $number % 100;
        } else {
         // If the last two characters are numbers
         if ( preg_match( '/[0-9]?[0-9]$/', $number, $matches ) )
         {
           // Return the last one or two digits
           $n = array_pop($matches);
         } else {
           // Return the string, we can add a suffix to it
           return $number;
         }
        }
        // Skip the switch for as many numbers as possible.
        if ( $n > 3 && $n < 21 )
          return $number . 'th';
        
        // Determine the suffix for numbers ending in 1, 2 or 3, otherwise add a 'th'
        switch ( $n % 10 )
        {
          case '1': return $number . 'st';
          case '2': return $number . 'nd';
          case '3': return $number . 'rd';
          default:  return $number . 'th';
        }
    }
    
}