<?php
/**
 * @author Edwards
 * @copyright 2015
 * 2521217
 * 
 * RGB  R|G|B = 0 to 255
 * CMYK C|M|Y|K = 0 to 1
 * HSL  H|S|L = 0 to 1
 * H = 0 to 1 OR 1 to 360
 * S|L = 0 to 1 OR 1 to 100 
 * 
 * 
 * 
  @if (lightness($color) > 50) {
    @return #000000; // Lighter backgorund, return dark color
  } @else {
    @return #ffffff; // Darker background, return light color
  }
}
 * 
 */

 //http://www.color-hex.com/
//get a rgb.txt file to use when no match is found
namespace ELIX;

class COLOR{
    public static function get($r=null,$g=null,$b=null,$k=null){
        $n = func_num_args();
        if($n == 4 && $k===null) $n =3;
        if($n == 3 && $b===null) $n =2;
        if($n == 2 && $g===null) $n =1;
        if($n == 1 && $r===null) $n =0;
        
        if($n == 1){
            return new color_item($r);
        }
        $ci = new color_item();
        /*elseif($n == 2){
            return new color_item($r,$g);
        }else*/if($n == 3){
            $ci->setRgb($r,$g,$b);
        }elseif($n == 4){
            $ci->setCmyk($r,$g,$b,$k);
        }
        return $ci;
    }
    public static function getHSL($h=null,$s=null,$l=null){
        $ci = new color_item();
        $ci->setHsl($h,$s,$l);
        return $ci;
    }
    public static function getNamedColorsArray(){
        // All Netscape named colors
return array(
  "aliceblue" => "#f0f8ff",
  "antiquewhite" => "#faebd7",
  "aqua" => "#00ffff",
  "aquamarine" => "#7fffd4",
  "azure" => "#f0ffff",
  "beige" => "#f5f5dc",
  "bisque" => "#ffe4c4",
  "black" => "#000000",
  "blanchedalmond" => "#ffebcd",
  "blue" => "#0000ff",
  "blueviolet" => "#8a2be2",
  "brown" => "#a52a2a",
  "burlywood" => "#deb887",
  "cadetblue" => "#5f9ea0",
  "chartreuse" => "#7fff00",
  "chocolate" => "#d2691e",
  "coral" => "#ff7f50",
  "cornflowerblue" => "#6495ed",
  "cornsilk" => "#fff8dc",
  "crimson" => "#dc143c",
  "cyan" => "#00ffff",
  "darkblue" => "#00008b",
  "darkcyan" => "#008b8b",
  "darkgoldenrod" => "#b8860b",
  "darkgray" => "#a9a9a9",
  "darkgreen" => "#006400",
  "darkgrey" => "#a9a9a9",
  "darkkhaki" => "#bdb76b",
  "darkmagenta" => "#8b008b",
  "darkolivegreen" => "#556b2f",
  "darkorange" => "#ff8c00",
  "darkorchid" => "#9932cc",
  "darkred" => "#8b0000",
  "darksalmon" => "#e9967a",
  "darkseagreen" => "#8fbc8f",
  "darkslateblue" => "#483d8b",
  "darkslategray" => "#2f4f4f",
  "darkslategrey" => "#2f4f4f",
  "darkturquoise" => "#00ced1",
  "darkviolet" => "#9400d3",
  "deeppink" => "#ff1493",
  "deepskyblue" => "#00bfff",
  "dimgray" => "#696969",
  "dimgrey" => "#696969",
  "dodgerblue" => "#1e90ff",
  "firebrick" => "#b22222",
  "floralwhite" => "#fffaf0",
  "forestgreen" => "#228b22",
  "fuchsia" => "#ff00ff",
  "gainsboro" => "#dcdcdc",
  "ghostwhite" => "#f8f8ff",
  "gold" => "#ffd700",
  "goldenrod" => "#daa520",
  "gray" => "#808080",
  "green" => "#008000",
  "greenyellow" => "#adff2f",
  "grey" => "#808080",
  "honeydew" => "#f0fff0",
  "hotpink" => "#ff69b4",
  "indianred" => "#cd5c5c",
  "indigo" => "#4b0082",
  "ivory" => "#fffff0",
  "khaki" => "#f0e68c",
  "lavender" => "#e6e6fa",
  "lavenderblush" => "#fff0f5",
  "lawngreen" => "#7cfc00",
  "lemonchiffon" => "#fffacd",
  "lightblue" => "#add8e6",
  "lightcoral" => "#f08080",
  "lightcyan" => "#e0ffff",
  "lightgoldenrodyellow" => "#fafad2",
  "lightgray" => "#d3d3d3",
  "lightgreen" => "#90ee90",
  "lightgrey" => "#d3d3d3",
  "lightpink" => "#ffb6c1",
  "lightsalmon" => "#ffa07a",
  "lightseagreen" => "#20b2aa",
  "lightskyblue" => "#87cefa",
  "lightslategray" => "#778899",
  "lightslategrey" => "#778899",
  "lightsteelblue" => "#b0c4de",
  "lightyellow" => "#ffffe0",
  "lime" => "#00ff00",
  "limegreen" => "#32cd32",
  "linen" => "#faf0e6",
  "magenta" => "#ff00ff",
  "maroon" => "#800000",
  "mediumaquamarine" => "#66cdaa",
  "mediumblue" => "#0000cd",
  "mediumorchid" => "#ba55d3",
  "mediumpurple" => "#9370db",
  "mediumseagreen" => "#3cb371",
  "mediumslateblue" => "#7b68ee",
  "mediumspringgreen" => "#00fa9a",
  "mediumturquoise" => "#48d1cc",
  "mediumvioletred" => "#c71585",
  "midnightblue" => "#191970",
  "mintcream" => "#f5fffa",
  "mistyrose" => "#ffe4e1",
  "moccasin" => "#ffe4b5",
  "navajowhite" => "#ffdead",
  "navy" => "#000080",
  "oldlace" => "#fdf5e6",
  "olive" => "#808000",
  "olivedrab" => "#6b8e23",
  "orange" => "#ffa500",
  "orangered" => "#ff4500",
  "orchid" => "#da70d6",
  "palegoldenrod" => "#eee8aa",
  "palegreen" => "#98fb98",
  "paleturquoise" => "#afeeee",
  "palevioletred" => "#db7093",
  "papayawhip" => "#ffefd5",
  "peachpuff" => "#ffdab9",
  "peru" => "#cd853f",
  "pink" => "#ffc0cb",
  "plum" => "#dda0dd",
  "powderblue" => "#b0e0e6",
  "purple" => "#800080",
  "red" => "#ff0000",
  "rosybrown" => "#bc8f8f",
  "royalblue" => "#4169e1",
  "saddlebrown" => "#8b4513",
  "salmon" => "#fa8072",
  "sandybrown" => "#f4a460",
  "seagreen" => "#2e8b57",
  "seashell" => "#fff5ee",
  "sienna" => "#a0522d",
  "silver" => "#c0c0c0",
  "skyblue" => "#87ceeb",
  "slateblue" => "#6a5acd",
  "slategray" => "#708090",
  "slategrey" => "#708090",
  "snow" => "#fffafa",
  "springgreen" => "#00ff7f",
  "steelblue" => "#4682b4",
  "tan" => "#d2b48c",
  "teal" => "#008080",
  "thistle" => "#d8bfd8",
  "tomato" => "#ff6347",
  "turquoise" => "#40e0d0",
  "violet" => "#ee82ee",
  "wheat" => "#f5deb3",
  "white" => "#ffffff",
  "whitesmoke" => "#f5f5f5",
  "yellow" => "#ffff00",
  "yellowgreen" => "#9acd32"
);
    }
}
class color_item  {
    protected $r=0, $g=0,$b=0;
    protected $h=0, $s=0,$l=0;
    protected $opacity = null;
    
    protected $moduleType = 0; //0=RGB-HEX, 1=CMKY, 2=HSL
    protected $log = array();
    public function __construct($hex='') {
        if($hex)$this->setOne($hex);
    }
    public function __toString() {
        return $this->tohex();
    }

    public function __get($name) {
        $name = strtolower($name);
        
        
        if(($i = array_search($name,array('c','m','y','k'))) !== false){
            $a = $this->pureCmyk();
            return $a[$i];
        }
        if(($i = array_search($name,array('r','g','b'))) !== false){
            $a = $this->toRgbArray();
            return $a[$i];
        }
        if(($i = array_search($name,array('h','s','l'))) !== false){
            $a = $this->toHslArray();
            return $a[$i];
        }
        if(($i = array_search($name,array('cyan','magenta','yellow','key'))) !== false){
            $a = $this->pureCmyk();
            return $a[$i];
        }
        if(($i = array_search($name,array('red','green','blue'))) !== false){
            $a = $this->toRgbArray();
            return $a[$i];
        }
        if(($i = array_search($name,array('hue','saturation','lightness'))) !== false){
            $a = $this->toHslArray();
            return $a[$i];
        }
        if(($i = array_search($name,array('light'))) !== false){
            $a = $this->toHslArray();
            return $a[2];
        }
        if((substr($name,0,2)=='to') && method_exists($this,$name)){
            return $this->$name();
        }
        
        if(($i = array_search($name,array('luminosity','yiq'))) !== false){
            return $this->$name();
        }
    }
    public function toString(){return $this->toHex();}
    public function toArray(){
        $a = $this->toRgbArray();
        list($r,$g,$b) = $a;
        
        $a['r'] = $r;
        $a['g'] = $g;
        $a['b'] = $b;
        list($c,$m,$y,$k) = rgb_to_cmyk($r,$g,$b);
        $a['c'] = $c;
        $a['m'] = $m;
        $a['y'] = $y;
        $a['k'] = $k;
        
        $a['h'] = $this->h;
        $a['s'] = $this->s;
        $a['l'] = $this->l;
        $a['opacity'] = $this->opacity;
        return $a; 
    }
    
    public function toCommonColor(){return matchCommonColor($this->toHex());}
    public function toHex(){ return rgb2hex($this->r,$this->g,$this->b);}
    public function toRGB(){ return implode(',',$this->toRgbArray());}
    public function toRgbArray(){return array($this->r,$this->g,$this->b);}
    public function toCMYK(){
        list($r,$g,$b) = $this->toRgbArray();
        list($c,$m,$y,$k) = rgb_to_cmyk($r,$g,$b);
        return "$c,$m,$y,$k";
    }
    public function toHsl(){
        return implode(',',$this->toHslArray());
    }
    public function toCMYKArray(){
        list($r,$g,$b) = $this->toRgbArray();
        return rgb_to_cmyk($r,$g,$b);
    }
    public function toCss3(){
        if($this->moduleType == 2){
            if($this->opacity === null){
                return "hsl({$this->h},{$this->s}%,{$this->l}%)";
            }
            $o = round($this->opacity /100,1);
            return "hsla({$this->h},{$this->s}%,{$this->l}%,$o)";
        }else if($this->moduleType == 1){
            $temp = $this->toCMYK();
            return "cmyk($temp)";
        }
        
        if($this->opacity === null){
            $temp = $this->toHex();
            return "#{$temp}";
        }
        $temp = $this->toRGB();
        $o = round($this->opacity /100,1);
        return "rgba($temp,$o)";
    }
    public function toHslArray(){return array($this->h,$this->s,$this->l);}
    public function toShortHex(){
        $hex = $this->toHex();
        if(strlen($hex) ==3)
            return $hex;
         $r = substr($hex,0,1);
         $g = substr($hex,2,1);
         $b = substr($hex,4,1);
         if($r == substr($hex,1,1) &&
            $g == substr($hex,3,1) &&
            $b == substr($hex,5,1) ){
            return"{$r}{$g}{$b}";
        }
        return $hex;
    }
    protected function setOne($r){
        
        if(is_array($r)){
            $this->setFromArray($r);
        }elseif(is_int($r)){
            if($r < 100){
                $this->setCMYK(0,0,0,$r);
            }elseif($r < 256){
                $this->setRGB($r,$r,$r);
            }else{
                list($r,$g,$b) = hex2rgb(int2hex($r));
                $this->setRGB($r,$g,$b);
            }
        }elseif(is_string($r)){
            $r=trim(strtolower($r));
            $r=str_replace(' ','',$r);
            $l4 = substr($r,0,4);
            $l3 = substr($r,0,3);
            $r=str_replace(ARRAY('rgba','hsla','rgb','cmyk','hsl',')','('),'',$r);
      
            $c = substr_count($r,',');
            if($l3 =='hsl'){
                list($h,$s,$l,$o) = explode(',',$r);
                $this->setHsl($h,$s,$l);
                if( $l4 =='hsla'){
                    $this->opacity = $o;
                }
            }elseif($l3 =='rgb'){
                list($r,$g,$b,$o) = explode(',',$r);
                $this->setRGB($r,$g,$b);
                if( $l4 =='rgba'){
                    $this->opacity = $o;
                }
            }elseif($c == 2){
                list($r,$g,$b) = explode(',',$r);
                $this->setRGB($r,$g,$b);
            }elseif($c == 3 || $l4 =='cmyk'){
                list($r,$g,$b,$k) = explode(',',$r);
                $this->setCMYK($r,$g,$b,$k);
            }elseif(is_hex($r)){
                $this->log[] = 'is_hex';
                $l =strlen($r);
                if($l == 3){
                    list($r,$g,$b) = hex2rgb(hex3to6($r));
                    $this->setRGB($r,$g,$b);
                }elseif($l == 6){
                    list($r,$g,$b) = hex2rgb($r);
                    $this->setRGB($r,$g,$b);
                }elseif($l == 4){
                    list($r,$g,$b) = hex2rgb(hex3to6(substr($r,0,3)));
                    $this->setRGB($r,$g,$b);
                    $o = substr($r,3);
                    $this->opacity = 100*(hexdec($o)/16);
                }elseif($l == 8){
                    list($r,$g,$b) = hex2rgb(substr($r,0,6));
                    $this->setRGB($r,$g,$b);
                    $o = substr($r,6);
                    $this->opacity = 100*(hexdec($o)/256);
                }else{
                    //$this->log[] = "$r,$g,$b";
                    list($r,$g,$b) = hex2rgb($r);
                    $this->setRGB($r,$g,$b);
                }
            }else{
                $r = commonColors($r);
                list($r,$g,$b) = hex2rgb($r);
                $this->setRGB($r,$g,$b);
            }
        }elseif(is_object($r)){
            if($r instanceof $this){
                $a = $r->toArray();
                foreach(array('r','g','b','h','s','l','opacity') as $key){
                    if(isset($a[$key])){
                        $this->$key = $a[$key]; 
                    }
                }
            }else{
                $a = (array)$r;
                $this->setFromArray($a);
            }
        }
    }
    protected function setFromArray($a){
        $a = array_change_key_case($a,CASE_LOWER);
        $c = sizeof($a);
        foreach(array('r'=>'setred','red'=>'setred',
                       'g'=>'setgreen','green'=>'setgreen',
                       'b'=>'setblue','blue'=>'setblue',
                       'h'=>'sethue','hue'=>'sethue',
                       's'=>'setSaturation','saturation'=>'setSaturation',
                       'l'=>'setLightness',
                       'light'=>'setLightness','lightness'=>'setLightness',
                       
                      /* 'c'=>'setcyan','cyan'=>'setcyan',
                       'm'=>'setmagenta','magenta'=>'setmagenta',
                       'y'=>'setyellow','yellow'=>'setyellow',
                       'k'=>'setkey','black'=>'setkey','key'=>'setkey',*/
                       //'cmyk'=>'set','rgb'=>'set',
                    ) as $k=>$fx){
            if(isset($a[$k])) $this->$fx($a[$k]);
        }
        if(isset($a['hex'])){
            list($r,$g,$b) = hex2rgb($a['hex']);
            $this->setRGB($r,$g,$b);
        }
        if($c ==4){
            $a = array_values($a);
            $this->setCMYK($a[0],$a[1],$a[2],$a[3]);
        }elseif($c ==3){
            $a = array_values($a);
            $this->setRGB($a[0],$a[1],$a[2]);
        }elseif($c ==1){
            $a = array_values($a);
            $this->setOne($a[0]);
        }
    }
    private function syncFromRgb(){
        list($h,$s,$l) = rgb2hsl(array($this->r,$this->g,$this->b));
        $this->h = $h;
        $this->s = $s;
        $this->l = $l;
    }
    private function syncFromHsl(){
        if($this->h > 360){
            $this->h = $this->h % 360;
        }
        if($this->h < 0){
            $this->h = 0;
        }
        if($this->s > 100){
            $this->s = 100;
        }
        if($this->s < 0){
            $this->s = 0;
        }
        if($this->l > 100){
            $this->l = 100;
        }
        if($this->l < 0){
            $this->l = 0;
        }
        
        list($r,$g,$b) = hsl2rgb(array($this->h,$this->s,$this->l));
        $this->r = $r;
        $this->g = $g;
        $this->b = $b;
    }
    public function setRGB($r=null,$g=null,$b=null){
        $n = func_num_args();
        if($n == 3 && $b===null) $n =2;
        if($n == 2 && $g===null) $n =1;
        if($n == 1 && $r===null) $n =0;
        $this->moduleType =0;
        if($n){
            if($n==1){
                if($r < 256){
                    $this->r = $r;
                    $this->g = $r;
                    $this->b = $r;
                }else{
                    $this->setOne($r);
                }
                $this->syncFromRgb();
                return $this;
            }
            $this->r = $r;
            $this->g = $g;
            $this->b = $b;
            $this->syncFromRgb();
        }
        
        return $this;
    }
    public function setCMYK($c=null,$m=null,$y=null,$k=null){
        $n = func_num_args();
        if($n == 4 && $k===null) $n =3;
        if($n == 3 && $y===null) $n =2;
        if($n == 2 && $m===null) $n =1;
        if($n == 1 && $c===null) $n =0;
        $this->moduleType =1;
        if($n){
            if($n==1){
                $this->setOne($c);
                return $this;
            }
            list($c1,$m1,$y1,$k1) = $this->toCMYKarray();
            if($c !== null) $c1 = $c;
            if($m !== null) $m1 = $m;
            if($y !== null) $y1 = $y;
            if($k !== null) $k1 = $k;
            list($r,$g,$b) = cmyk_to_rgb($c1,$m1,$y1,$k1);
            $this->r = $r;
            $this->g = $g;
            $this->b = $b;
            $this->syncFromRgb();
        }
        return $this;
    }
    public function setHsl($h=null,$s=0,$l=0){
        $this->moduleType =2;
        if(func_num_args()){
            if(func_num_args() == 1 && is_array($h)){
                $hsl = array_values($h);
                if(!isset($hsl[1])) $hsl[1] = 0;
                if(!isset($hsl[2])) $hsl[2] = 0;
            }else{
                $hsl = array($h,$s,$l);
                if($h === null){
                    $hsl[0] = $this->h;
                }
                if($s === null){
                    $hsl[1] = $this->s;
                }
                if($l === null){
                    $hsl[2] = $this->l;
                }
                
            }
            $this->h = $hsl[0];
            $this->s = $hsl[1];
            $this->l = $hsl[2];
        }
        $this->syncFromHsl();
        return $this;
    }
    
    public function setOpacity($value){
        if($value < 0){
            $this->opacity = null;
        }else if($value == 0){
            $this->opacity = 0;
        }else if ($value < 1){
            $this->opacity = floor(100*$value);
        }else if ($value == 100){
            $this->opacity = 100;
        }else{
            $this->opacity = ($value % 100);
        }
        return $this;
    }
    public function setRed($value){
        if($value < 0){
            $value = $value % 255;
            $this->r += $value;
            if($this->r < 0){
                $this->r = 0;
            } 
        }elseif($value < 1){
            $this->r = 255 * $value;
        }else{
            $value = (int)$value;
            $this->r = $value % 255;
        }
        $this->syncFromRgb();
        return $this;
    }
    public function setGreen($value){
        if($value < 0){
            $value = $value % 255;
            $this->g += $value;
            if($this->g < 0){
                $this->g = 0;
            } 
        }elseif($value < 1){
            $this->g = 255 * $value;
        }else{
            $value = (int)$value;
            $this->g = $value % 255;
        }
        $this->syncFromRgb();
        return $this;
    }
    public function setBlue($value){
        if($value < 0){
            $value = $value % 255;
            $this->b += $value;
            if($this->b < 0){
                $this->b = 0;
            } 
        }elseif($value < 1){
            $this->b = 255 * $value;
        }else{
            $value = (int)$value;
            $this->b = $value % 255;
        }
        $this->syncFromRgb();
        return $this;
    }
    public function setHue($value){
        if($value == 0){
            $this->h = 0;
        }else if ($value < 1){
            $this->h = 360*$value;
        }else{
            $this->h = ($value % 360);
        }
        $this->syncFromHsl();
        return $this;
    }
    public function setSaturation($value){
        if($value == 0){
            $this->s = 0;
        }else if ($value < 1){
            $this->s = 100*$value;
        }else{
            $this->s = ($value % 100);
        }
        $this->syncFromHsl();
        return $this;
    }
    public function setLuminosity($value){
        return $this->setLightness($value);
    }
    public function setLightness($value){
        if($value == 0){
            $this->l = 0;
        }else if ($value < 1){
            $this->l = $value;
        }else{
            $this->l = ($value % 100);
        }
        $this->syncFromHsl();
        return $this;
    }
    
    //https://stackoverflow.com/questions/6615002/given-an-rgb-value-how-do-i-create-a-tint-or-shade
    //factor 0 to 1
    public function shade($factor=null){
        if($factor < 0){
            $this->tint(abs($factor));
        }else{
            if($factor){
                if(($factor > 1)){
                    $factor = ($factor % 100)/100;
                }
                if($this->moduleType == 2){
                    $this->l -= ($factor * 100);
                    $this->syncFromHsl();
                }else{
                    $this->r = floor($this->r * (1-$factor));
                    $this->g = floor($this->g * (1-$factor));
                    $this->b = floor($this->b * (1-$factor));
                    $this->syncFromRgb();
                }
            }else{
                if($this->moduleType == 2){
                    $this->l = $this->l/2;
                    $this->syncFromHsl();
                }else{
                    $factor = .5;
                    $this->r = floor($this->r * (1-$factor));
                    $this->g = floor($this->g * (1-$factor));
                    $this->b = floor($this->b * (1-$factor));
                    $this->syncFromRgb();
                }
            }
        }
        return $this;
    }//factor 0 to 1
    public function tint($factor =null){
        if($factor < 0){
            $this->darken(abs($factor));
        }else{
            if($factor){
                if(($factor > 1)){
                    $factor = ($factor % 100)/100;
                }
                if($this->moduleType == 2){
                    $this->l += ($factor * 100);
                    $this->syncFromHsl();
                }else{
                    $this->r = $this->r + floor((255-$this->r) * $factor);
                    $this->g = $this->g + floor((255-$this->g) * $factor);
                    $this->b = $this->b + floor((255-$this->b) * $factor);
                    $this->syncFromRgb();
                }
            }else{
                if($this->moduleType == 2){
                    $this->l += (100-$this->l)/2;
                    $this->syncFromHsl();
                }else{
                    $factor = .5;
                    $this->r = $this->r + floor((255-$this->r) * $factor);
                    $this->g = $this->g + floor((255-$this->g) * $factor);
                    $this->b = $this->b + floor((255-$this->b) * $factor);
                    $this->syncFromRgb();
                }
            }
        }
        return $this;
    }//factor 0 to 1
    public function tone($factor){
        if($factor){
            if(($factor > 1)){
                $factor = ($factor % 100)/100;
            }
            if($this->moduleType == 2){
                $this->s += ($factor * 100);
                $this->syncFromHsl();
            }else{
                $this->r = $this->r + floor((255-$this->r) * $factor);
                $this->g = $this->g + floor((255-$this->g) * $factor);
                $this->b = $this->b + floor((255-$this->b) * $factor);
                $this->syncFromRgb();
            }
        }
        return $this;
    }
    public function getOverlay($color, $factor){
        $ci = clone $this;
        if($factor){
            $factor = abs($factor);
            if(($factor > 1)){
                $factor = ($factor % 100)/100;
            }
            $co = new self($color);
            list($r,$g,$b) = $ci->toRgbArray();
            $nr = $r + floor(($co->r-$r) * $factor);
            $ng = $g + floor(($co->g-$g) * $factor);
            $nb = $b + floor(($co->b-$b) * $factor);
            $ci->setRGB($nr,$ng,$nb);
        }
        return $ci;
    }
    //https://github.com/gdkraus/wcag2-color-contrast/blob/master/wcag2-color-contrast.php
    // calculates the luminosity of an given RGB color
// the color code must be in the format of RRGGBB
// the luminosity equations are from the WCAG 2 requirements
// http://www.w3.org/TR/WCAG20/#relativeluminancedef
    public function luminosity() {
        $r = $this->r / 255; // red value
        $g = $this->g / 255; // green value
        $b = $this->b / 255; // blue value
        if ($r <= 0.03928) {
            $r = $r / 12.92;
        } else {
            $r = pow((($r + 0.055) / 1.055), 2.4);
        }
        if ($g <= 0.03928) {
            $g = $g / 12.92;
        } else {
            $g = pow((($g + 0.055) / 1.055), 2.4);
        }
        if ($b <= 0.03928) {
            $b = $b / 12.92;
        } else {
            $b = pow((($b + 0.055) / 1.055), 2.4);
        }
        $luminosity = 0.2126 * $r + 0.7152 * $g + 0.0722 * $b;
        return $luminosity;
    }
    public function yiq($lighterThan = 128){
        return (($this->r*299)+($this->g*587)+($this->b*114))/1000;
    }
    public function isLight($lighterThan = 128){
        return $this->yiq() > $lighterThan;
    }
    public function isDark($darkerThan = 128 ){
        return $this->yiq() <= $lighterThan;
    }
    public function isTint( ){
        return $this->l > 50;
    }
    public function isShade( ){
        return $this->l < 50;
    }
    //http://serennu.com/colour/rgbtohsl.php
    public function getComplementary( ){
        $ci = clone $this;
        if($this->moduleType == 2){
            $h = $this->h;
            $s = $this->s;
            $l = 100-$this->l;
            if($h == 0 && $s ==0){
                $s = 100;
            }
            $h += ($h>180)?-180:180;
            
            $ci->setHsl($h,$s,$l );
        }else{
            //inverse color
            $r = 255 - $this->r;
            $g = 255 - $this->g;
            $b = 255 - $this->b;
            $ci->setRgb($r,$g,$b);
        }
        return $ci;
    }
    public function getContrastBW( ){
        $ci = clone $this;
        $dec = hexdec($this->toHex());
        if($dec > 8388607){
            $ci->setRgb(0,0,0);
        }else{
            $ci->setRgb(255,255,255);
        }
        return $ci;
    }
    function getContrastYIQ(){
        $ci = clone $this;
    	if($this->yiq() >= 128){
            $ci->setRgb(0,0,0);
        }else{
            $ci->setRgb(255,255,255);
        }
        return $ci;
    }
    function getContrastRGB(){
        $ci = clone $this;
        $r = ($this->r < 128) ? 255 : 0;
        $g = ($this->g < 128) ? 255 : 0;
        $b = ($this->b < 128) ? 255 : 0;
        $ci->setRgb($r,$g,$b);
        return $ci;
    }
    
    //https://stackoverflow.com/questions/1177826/simple-color-variation
    //https://www.sitepoint.com/javascript-generate-lighter-darker-color/
    
    
}
function int2hex($int){
    $int = (int)$int;
    return substr(str_pad(dechex($int), 6, "0", STR_PAD_LEFT),-6);
}
function is_hex($hexValue){ 
    if(strtolower($hexValue) == dechex(hexdec($hexValue))) 
        return true; 
    return false; 
}
function hex2rgb($color,  $seperator = '')
{
    $color=strtolower(trim($color));
    if (substr($color,0,1) == '#') 
        $color = substr($color, 1);
    else if (0 === strpos($color, '&h')) {
        $color = substr($color, 2);
    }else if (0 === strpos($color, '0x')) {
        $color = substr($color, 2);
    }else if (0 === strpos($color, 'rgb')) {
        $color =  rgbFx2Hex($color);
    }
    
    if (strlen($color) >= 6){ //+2 for alpha
        $r = substr($color,0,2);
        $g = substr($color,2,2);
        $b = substr($color,4,2);
    }elseif (strlen($color) == 3||strlen($color) == 4){
        list($r, $g, $b) = array($color[0].$color[0], $color[1].$color[1], $color[2].$color[2]);
    }elseif($color===0 || $color==='0' || $color==='00'){
        $r=$g=$b = 0;
    }else
        return false;
    
    $r = hexdec($r); $g = hexdec($g); $b = hexdec($b);
    $rgbArray = array($r, $g, $b);
    return $seperator ? implode($seperator, $rgbArray) : $rgbArray;
    
}
function rgb2hex($r,$g=0,$b=0){
    if (is_array($r) && sizeof($r) == 3)
    list($r, $g, $b) = $r;

    $r = intval($r); 
    $g = intval($g);
    $b = intval($b);

   $hex = '';
   $hex .= str_pad(dechex($r<0?0:($r>255?255:$r)), 2, "0", STR_PAD_LEFT);
   $hex .= str_pad(dechex($g<0?0:($g>255?255:$g)), 2, "0", STR_PAD_LEFT);
   $hex .= str_pad(dechex($b<0?0:($b>255?255:$b)), 2, "0", STR_PAD_LEFT);

   return $hex;
}
function valhex2($val){
    $val=strtolower($val);
    if (substr($val,0,1) == '#'){
        $val = substr($val, 1);
        $val = hexdec($val);
    }else if (0 === strpos($val, '&h')) {
        $val = substr($val, 2);
        $val = hexdec($val);
    }else if (0 === strpos($val, '0x')) {
        $val = substr($val, 2);
        $val = hexdec($val);
    }else{
        $val = (int)$val;
    }
    return substr(str_pad(dechex($val), 2, "0", STR_PAD_LEFT),-2);
}
function array2hex($arr){
    if(!is_array($arr)) return '';
    
    $r = $b = $g =0;
    if (sizeof($arr) == 3)
        list($r, $g, $b) = $arr;
    else{
        if(isset($arr['r'])) $r = $arr['r'];
        elseif(isset($arr[0])) $r = $arr[0];
        if(isset($arr['g'])) $g = $arr['g'];
        elseif(isset($arr[1])) $g = $arr[1];
        if(isset($arr['b'])) $b = $arr['b'];
        elseif(isset($arr[2])) $b = $arr[2];
    }
    return rgb2hex($r,$g,$b);
}
    function rgbFx2Hex($color){
        //color in format RGB(1,2,3) or RGBA(1,2,3,.3)
        
        $color=trim(strtolower($color));
        $oc = $color;
        
        $color=str_replace(' ','',$color);
        $color=str_replace(ARRAY('rgb(','rgba(','rgb','rgba',')','('),'',$color);
        if($oc == $color && strpos($color,',')===false) return $color;
        
        $hex = '';
        $e = explode(',',substr($color,4),',0,0,0',4);
        list($r, $g, $b) = $e;                
        $hex .= str_pad(dechex($r<0?0:($r>255?255:$r)), 2, "0", STR_PAD_LEFT);
       $hex .= str_pad(dechex($g<0?0:($g>255?255:$g)), 2, "0", STR_PAD_LEFT);
       $hex .= str_pad(dechex($b<0?0:($b>255?255:$b)), 2, "0", STR_PAD_LEFT);
    
       return $hex;
    }
    function hex3to6($hex){
        if(strlen($hex) != 3) return $hex;
        $a = str_split($hex);
       return "{$a[0]}{$a[0]}{$a[1]}{$a[1]}{$a[2]}{$a[2]}";
    }


function matchCommonColor($color){
        $color =hex3to6((string)$color);
        $c=strtoupper($color);
        switch($c){
        case 'FF0000': return 'red' ;
        case '8B0000': return 'darkred';
        case 'DC143C': return 'crimson';
        case '800000': return 'maroon';
        case '8B008B': return 'darkmagenta';
        case 'FFC0CB': return 'pink';
        case 'FF69B4': return 'hotpink';
        case 'FF00FF': return 'magenta';
        
        case '008000': return 'green';
        case '006400': return 'darkgreen';
        case '90EE90': return 'lightgreen';
        case '00FF00': return 'lime';
        case '32CD32': return 'limegreen';
        case '98FB98': return 'palegreen';
        case '2E8B57': return 'seagreen';
        case '228B22': return 'forestgreen';
        case 'ADFF2F': return 'greenyellow';
        case 'FFFF00': return 'yellow';
        case 'FFFFE0': return 'lightyellow';
        
        case '00008B': return 'darkblue';
        case '0000FF': return 'blue';
        case '00FFFF': return 'cyan' ;
        case '87CEEB': return 'skyblue';
        case '000080': return 'navyblue';
        case '4169E1': return 'royalblue';
        case '008080': return 'teal';
        case '5F9EA0': return 'cadetblue';
        case 'ADD8E6': return 'lightblue';
        case '7FFFD4': return 'aquamarine';
        case '87CEFA': return 'lightskyblue';
        
        case 'E6E6FA': return 'lavender';
        case '800080': return 'purple';
        
        case 'FFFFFF': return 'white';
        case '000000': return 'black';
        case 'C0C0C0': return 'silver';
        case '808080': return 'gray';
        case 'A9A9A9': return 'darkgray';
        case 'D3D3D3': return 'lightgray';
        case '2F4F4F': return 'darkslategray';
        case '778899': return 'lightslategray';
        case '708090': return 'slategray';
        case 'A52A2A': return 'brown';
        case 'D2691E': return 'chocolate';
        case '808000': return 'olive';
        case 'F0E68C': return 'khaki';
        case 'F8F8FF': return 'ghostwhite';
        case 'F5F5F5': return 'smokewhite';
        case 'F5F5DC': return 'beige';
        case 'FAEBD7': return 'antiquewhite';
        			
        case 'FFD700': return 'gold';
        case '4B0082': return 'indigo';
        case 'FFA500': return 'orange';
        case 'FF8C00': return 'darkorange';
        case 'FFBF00': return 'amber';
        
        case 'D2B48C': return 'tan';
        case 'EE82EE': return 'violet';
        case '9400D3': return 'darkviolet'; 
        
        }
        $x = str_split($c,2);
        if($x[0] == $x[1] && $x[1]==$x[2]) return 'gray';
        return $color;
    }
    function commonColors($color){
        
        $color=str_replace(array(' ','-'),'',$color);
        $color=strtolower($color);
        switch($color){
        case 'red': return 'FF0000';
        case 'darkred': return '8B0000';
        case 'crimson': return 'DC143C';
        case 'maroon': return '800000';
        case 'darkmagenta': return '8B008B';
        case 'pink': return 'FFC0CB';
        case 'hotpink': return 'FF69B4';
        case 'magenta': case 'fuchsia':return 'FF00FF';
        
        case 'green': return '008000';
        case 'darkgreen': return '006400';
        case 'lightgreen': return '90EE90';
        case 'lime': return '00ff00';
        case 'limegreen': return '32CD32';
        case 'palegreen': return '98FB98';
        case 'seagreen': return '2E8B57';
        case 'forestgreen': return '228B22';
        case 'greenyellow': return 'ADFF2F';
        case 'yellow': return 'FFFF00';
        case 'lightyellow': return 'FFFFE0';
        
        case 'darkblue': return '00008B';
        case 'blue': return '0000FF';
        case 'aqua':case 'cyan': return '00FFFF';
        case 'skyblue': return '87CEEB';
        case 'navy':case 'navyblue': return '000080';
        case 'royalblue': return '4169E1';
        case 'teal': return '008080';
        case 'cadetblue': return '5F9EA0';
        case 'lightblue': return 'ADD8E6';
        case 'aquamarine': return '7FFFD4';
        case 'lightskyblue': return '87CEFA';
        
        case 'lavender': return 'E6E6FA';
        case 'purple': return '800080';
        
        case 'white': return 'FFFFFF';
        case 'black': return '000000';
        case 'silver': return 'C0C0C0';
        case 'gray':case 'grey': return '808080';
        case 'darkgray':case 'darkgrey': return 'A9A9A9';
        case 'lightgray':case 'lightgrey': return 'D3D3D3';
        case 'darkslategray': return '2F4F4F';
        case 'lightslategray': return '778899';
        case 'slategray': return '708090';
        case 'brown': return 'A52A2A';
        case 'chocolate': return 'D2691E';
        case 'olive': return '808000';
        case 'khaki': return 'F0E68C';
        case 'ghostwhite': return 'F8F8FF';
        case 'smoke':case 'whitesmoke':case 'smokewhite': return 'F5F5F5';
        case 'beige': return 'F5F5DC';
        case 'antiquewhite': return 'FAEBD7';
        			
        case 'gold': return 'FFD700';
        case 'indigo': return '4B0082';
        case 'orange': return 'FFA500';
        case 'darkorange': return 'FF8C00';
        case 'amber': return 'FFBF00';
        
        case 'tan': return 'D2B48C';
        case 'violet': return 'EE82EE';
        case 'darkviolet': return '9400D3'; 
        
        } 
        return $color;
    }
// http://blog.loftdigital.com/blog/cmyk-rgb-and-php
/**
 * @param int $c
 * @param int $m
 * @param int $y
 * @param int $k
 * @return object
 */
function cmyk_to_rgb($c, $m, $y, $k)
{
    $r = 255 - round(2.55 * ($c+$k)) ;
    $g = 255 - round(2.55 * ($m+$k)) ;
    $b = 255 - round(2.55 * ($y+$k)) ;
      
    if($r<0) $r = 0 ;
    if($g<0) $g = 0 ;
    if($b<0) $b = 0 ;
    
    return array($r,$g,$b);
}

function rgb_to_cmyk($r,$g,$b){
       
   $cyan    = 255 - $r;
   $magenta = 255 - $g;
   $yellow  = 255 - $b;
   $black   = min($cyan, $magenta, $yellow);
   $cyan    = @(($cyan    - $black) / (255 - $black)) * 255;
   $magenta = @(($magenta - $black) / (255 - $black)) * 255;
   $yellow  = @(($yellow  - $black) / (255 - $black)) * 255;
   return array($cyan / 255,
                $magenta / 255,
                $yellow / 255,
                $black / 255);
}


//https://stackoverflow.com/questions/1177826/simple-color-variation


// Input: array(int,int,int) being RGB color in { [0..255], [0..255], [0..255] }
// Output array(float,float,float) being HSL color in { [0 .. 360), [0 .. 100), [0 .. 100) }
function rgb2hsl($rgbtrio) {
    $r = $rgbtrio[0] / 255.0;   // Normalize {r,g,b} to [0.0 .. 1.0)
    $g = $rgbtrio[1] / 255.0;
    $b = $rgbtrio[2] / 255.0;

    $h = 0;
    $s = 0;
    $L = 0;

    $min = min($r, $g, $b);
    $max = max($r, $g, $b);
    $delta = $max - $min;
    $L = 0.5 * ( $max + $min );

    if ( $delta < 0.001 )   // This is a gray, no chroma...
    {
        $h = 0;   // ergo, hue and saturation are meaningless
        $s = 0;
    }
    else    // Chromatic data...
    {
        if ( $L < 0.5 ){
            $s = $delta / ( $max + $min );
        }  
        else{
            $s = $delta / ( 2 - $delta );
        }
        $half_max = $max / 2.0;

        $dr = ( (($max - $r) / 6.0) + $half_max ) / $max;
        $dg = ( (($max - $g) / 6.0) + $half_max ) / $max;
        $db = ( (($max - $b) / 6.0) + $half_max ) / $max;

        if ($r == $max)         $h = $db - $dg;
        elseif ($g == $max)     $h = (0.3333) + $dr - $db;
        elseif ($b == $max)     $h = (0.6666) + $dg - $dr;

        if ( $h < 0.0 ) $h += 1.0;
        if ( $h > 1.0 ) $h -= 1.0;
    }

    return array(floor($h*360), floor($s * 100), floor($L*100));
}


function Hue_2_RGB( $v1, $v2, $vH ) {
    $v1 = 0.0+$v1;
    $v2 = 0.0+$v2;
    $vH = 0.0+$vH;

    if ( $vH < 0.0 )            $vH += 1.0;
    elseif ( $vH >= 1.0 )       $vH -= 1.0;
    // 0.0 <= vH < 1.0

    if ( $vH < 0.1667 )         return ( $v1 + 6.0*$vH*($v2 - $v1) );
    elseif ( $vH < 0.5 )        return ( $v2 );
    elseif ( $vH < 0.6667 )     return ( $v1 + (4.0-(6.0*$vH ))*($v2 - $v1) );
    else                        return ( $v1 );
}

// Input: array(float,float,float) being HSL color in { [0 .. 360), [0 .. 100), [0 .. 100) }
// Output: array(int,int,int) being RGB color in { [0..255], [0..255], [0..255] }
function hsl2rgb($hsltrio) {
    $h = $hsltrio[0];
    $s = $hsltrio[1];
    $L = $hsltrio[2]/100;

    if ( $s == 0 )                       //HSL from 0 to 1
    {
        $r = $L;
        $g = $L;
        $b = $L;
    }
    else
    {
        $h = $h/360;
        $s = $s/100;
        
        if ( $hsltrio[2] < 50 ){
            $j = $L * ( 1.0 + $s );
        }
        else{
            $j = ($L + $s) - ($s * $L);
        }

        $i = (2.0 * $L) - $j;

        $r = Hue_2_RGB( $i, $j, $h + 0.3333 );
        $g = Hue_2_RGB( $i, $j, $h );
        $b = Hue_2_RGB( $i, $j, $h - 0.3333 );
    }

    return array( floor(255 * $r), floor(255 * $g), floor(255 * $b) );
}
