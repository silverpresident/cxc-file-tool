<?php
/**
 * @author Edwards
 * @copyright 2015
 *  * 
 * 
 * 
 */
namespace ELIX;

//todo
class unitConverter{
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
        
    }
    public static function percentof($percent,$whole,$precision=2){
        return self::percent($percent,$whole,$precision);
    }
    public static function inversePercentOf($percent,$whole,$precision=2){
        return self::inversePercent($percent,$whole,$precision);
    }
    public static function get(){
        return new unitConverter();
    }
    
    
    
    private $value = 0;
    private $unit = '';
    public function __get($name) {
        $name = strtolower($name);
        
        if(in_array($name,array('mm'))){
            $fx = "get{$name}";
            return $this->$fx();
        }
        return null;
    }
    public function __set($name, $value) {
        $name = strtolower($name);
        
        if(in_array($name,array('mm'))){
            $fx = "set{$name}";
            return $this->$fx($value);
        }
    }


    
    
    
    
    
    
    
    
    
}


if($this->k == 72) //working in inches
        {
            if ( stristr($size,'px') ) $size *= 0.010416667; //pixels
            elseif ( stristr($size,'cm') ) $size *= 0.393700787; //centimeters
            elseif ( stristr($size,'mm') ) $size *= 0.039370079; //millimeters
            elseif ( stristr($size,'pc') ) $size *= 0.166666667; //PostScript picas 
            elseif ( stristr($size,'pt') ) $size *= 0.013888889; //72dpi
            elseif ( stristr($size,'em') ) $size *= 0.166044;
            elseif ( stristr($size,'ex') ) $size *= 0.166044 /2;
            elseif ( stristr($size,'%') )
            {
            $size += 0; //make "90%" become simply "90" 
            $size *= $maxsize/100;
            }
            /*if ( stristr($size,'in') ) */ return (0+$size);
        }
        if($this->k == 1) //working in points
        {
            if ( stristr($size,'px') ) $size *= 0.75; //pixels
            elseif ( stristr($size,'cm') ) $size *= 28.346; //centimeters
            elseif ( stristr($size,'mm') ) $size *= 2.8346; //millimeters
            elseif ( stristr($size,'in') ) $size *= 72; //inches 
            elseif ( stristr($size,'pc') ) $size *= 12; //PostScript picas
            elseif ( stristr($size,'em') ) $size *= 12;
            elseif ( stristr($size,'ex') ) $size *= 6;
            elseif ( stristr($size,'%') )
            {
            $size += 0; //make "90%" become simply "90" 
            $size *= $maxsize/100;
            }
            /*if ( stristr($size,'pt') ) */ return (0+$size);
        }
        
        if($this->k == 72/25.4) //working in mm
        {
            if ( stristr($size,'px') ) $size *= 0.264583333; //pixels
            elseif ( stristr($size,'cm') ) $size *= 10; //centimeters
            elseif ( stristr($size,'in') ) $size *= 25.4; //inches 
            elseif ( stristr($size,'pc') ) $size *= 38.1/9; //PostScript picas 
            elseif ( stristr($size,'pt') ) $size *= 25.4/72; //72dpi
            elseif ( stristr($size,'em') ) $size *= 4.2175176;
            elseif ( stristr($size,'ex') ) $size *= 4.2175176/2;
            elseif ( stristr($size,'%') )
            {
            $size += 0; //make "90%" become simply "90" 
            $size *= $maxsize/100;
            }
            /*if ( stristr($size,'mm') ) */ return (0+$size);
        }
        if($this->k == 72/2.54) //working in cm
        {
            if ( stristr($size,'px') ) $size *= 0.0264583333; //pixels
            elseif ( stristr($size,'mm') ) $size *= .1; //centimeters
            elseif ( stristr($size,'in') ) $size *= 2.54; //inches 
            elseif ( stristr($size,'pc') ) $size *= 3.81/9; //PostScript picas 
            elseif ( stristr($size,'pt') ) $size *= 2.54/72; //72dpi
            elseif ( stristr($size,'em') ) $size *= 0.42175176;
            elseif ( stristr($size,'ex') ) $size *= 0.42175176/2;
            elseif ( stristr($size,'%') )
            {
            $size += 0; //make "90%" become simply "90" 
            $size *= $maxsize/100;
            }
            /*if ( stristr($size,'cm') ) */ return (0+$size);
        }
        
        function ConvertSize($size=5,$maxsize=0){
// Depends of maxsize value to make % work properly. Usually maxsize == pagewidth
  //Identify size (remember: we are using 'mm' units here)
  if ( stristr($size,'px') ) $size *= 0.2645; //pixels
  elseif ( stristr($size,'cm') ) $size *= 10; //centimeters
  elseif ( stristr($size,'mm') ) $size += 0; //millimeters
  elseif ( stristr($size,'in') ) $size *= 25.4; //inches 
  elseif ( stristr($size,'pc') ) $size *= 38.1/9; //PostScript picas 
  elseif ( stristr($size,'pt') ) $size *= 25.4/72; //72dpi
  elseif ( stristr($size,'%') )
  {
  	$size += 0; //make "90%" become simply "90" 
  	$size *= $maxsize/100;
  }
  else $size *= 0.2645; //nothing == px
  
  return $size;
}


if($unit=='pt')
		$this->k = 1;
	elseif($unit=='mm')
		$this->k = 72/25.4;
	elseif($unit=='cm')
		$this->k = 72/2.54;
	elseif($unit=='in')
		$this->k = 72;
	else
	