<?php

/**
 * @author President
 * @copyright 2010
 * @version 20140402
 */
class ELI_emailprotect
{
    protected $version = '1.0'; //use for etag refresh
    protected $data =  array('email'=>'','type'=>1,'usepng'=>true);
    function __get($name) {
        $name = strtolower($name);
        
        if(method_exists($this,$name))
            return $this->$name();
        if($name =='len' || $name=='size'|| $name=='length'){
            return strlen($this->email);
        }
        if(isset($this->data[$name]) || array_key_exists($name,$this->data))
            return $this->data[$name];
        
        return null;
    }
    public function __set($name, $value) {
        $name = strtolower($name);
        if($name=='bg'||$name=='bgcolor'){
            $name = 'background';
        }
        $this->data[$name] = $value;
    }

    public function __isset($name) {
        $name = strtolower($name);
        return isset($this->data[$name]);
    }
    public function __toString() {
        if($this->type ==1){
            return $this->imageDataUri();
        }elseif($this->type ==2){
            return $this->script();
        }
        elseif($this->type ==0){
            return $this->text();
        }
        elseif($this->type ==4){
            return $this->img();
        }
        return '';
    }

    public function __construct() {
        if($n = func_num_args()){
            $a = func_get_arg(0);
            $this->email = $a;
            if($n > 1) $this->type = (int)func_get_arg(1);
        }
    }
    function imagehref(){
        return $this->imageDataUri();
    }
    function imageDataUri(){
        ob_start();
        $im = $this->getImage();
        if($this->usePNG){
            $mt = 'image/png';
            imagepng($im,NULL,9);
        }else{
            $mt = 'image/jpeg';
            imagejpeg($im,NULL,10);
        }
        ImageDestroy($im);
        $imageData = ob_get_clean();
        $imageData = base64_encode($imageData);
        return  'data:'.$mt.';base64,'.$imageData;
    }
    function img(){
        $a= $this->text();
        //$p = $this->imagehref();
        $p = $this->imageDataUri();
        $l = strlen($this->key);
        return "<img src='$p' alt=\"$a\" />";
        
    }
    function text(){
        return str_replace(array('@','.'), array(' [at] ','[dot]'),$this->email);
    }
    private function getFontSize(){
        $font_size = $this->font_size;
        if($font_size< 10  ) $font_size =10;
        if($font_size> 32  ) $font_size =32;
        return $font_size;
    }
    private function getFont(){
        $font_size = $this->font;
        if(is_string($font_size)){
            if(file_exists($font_size)){
                $r = imageloadfont($font_size);
                if($r > 5) $font_size = $r;
                else $font_size = 0;
            }
        }
        $font_size = (int)$font_size;
        if($font_size< 1 ) $font_size =4;
        return $font_size;
    }
    private function getTTFont(){
        $font_size = $this->font;
        if(is_string($font_size)){
            if(file_exists($font_size)){
                $tb = imagettfbbox(12, 0, $font_size,' ');
                if($tb !== false) return $font_size;
            }
        }
        return false;
    }
    private function getBgColor(){
        $t = $this->getColor();
        $c = $this->background;
        if(!$c){
            if(($t['g'] < 100) && ( $t['b']< 100) && ($t['r'] > 150))
                $c ='FFFFFF';
            else
                $c ='FF0000';
        }
        elseif(is_numeric($c) && strlen($c) != 6){
            $c = dechex($c);
        }
        $c = trim($c,'# ');
        $c = str_pad($c, 6, 'F', STR_PAD_LEFT);
        
        $a =array();
        $a['b'] = hexdec(substr($c,4,2));
        $a['g'] = hexdec(substr($c,2,2));
        $a['r'] = hexdec(substr($c,0,2));
        return $a;
    }
    private function getColor(){
        $c = $this->color;
        if(!$c) $c ='000000';
        elseif(is_numeric($c) && strlen($c) != 6){
            $c = dechex($c);
        }
        $c = trim($c,'# ');
        $c = str_pad($c, 6, 0, STR_PAD_LEFT);
        $a =array();
        $a['b'] = hexdec(substr($c,4,2));
        $a['g'] = hexdec(substr($c,2,2));
        $a['r'] = hexdec(substr($c,0,2));
        return $a;
    }
    function getImage(){
        
        
        $font = $this->getFont();
        $font_tt = $this->getTTFont();
        $font_size = $this->getFontSize();
        
        if($font_tt){
            $tb = imagettfbbox($font_size, 0, $font_tt,$this->email);
            if($tb !== false){
                $w = abs($tb[2]-$tb[0]);
                $h = abs($tb[5]-$tb[3]);
            }else{
                $font_tt = false;
            }
        }
        if($font_tt ===false){
            $w = imagefontwidth($font) * (strlen($this->email)+1);
            $h = imagefontheight($font);
        }
        
        $im = @imagecreatetruecolor($w,$h);
        if(!$im){
            throw new Exception("Cannot Initialize new GD image stream");
            return;
        }
        
        $b = $this->getBgColor();
        $c = $this->getColor();
        $bg = imagecolorallocate($im, $b['r'], $b['g'], $b['b']);
        $fg = imagecolorallocate($im, $c['r'], $c['g'], $c['b']);
        
        imagefill($im, 0, 0, $bg);
        imagecolortransparent($im, $bg);
        
        if($font_tt){
            $r = imagettftext($im, $font_size, 0, 0,0,$fg,$font_tt,$this->email);
            if($r === false)
                imagestring($im, $font, 0, 0,  $this->email, $fg);
        }else{
            imagestring($im, $font, 0, 0,  $this->email, $fg);
        }
        return $im;
    }
    function image($headers=true)
    {
        if(!$this->email) 
        {
            trigger_error('email not set.' );
            return false;
        }
        
        if(ob_get_length()) @ob_clean();
        if($headers){
            $d = $this->data;
            $d['version'] = $this->version;
            ksort($d);
            $etag = md5(implode(':',$d));
            //TODO: fix etag
            header("Etag: $etag");
            //header("Expires: Mon, 26 Jul 1997 05:00:00 GMT"); 
            //header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT"); 
            //header("Cache-Control: max-age=0, no-store, no-cache, must-revalidate, private"); 
            //header("Cache-Control: post-check=0, pre-check=0", false);
        }
        $im = $this->getImage();
        if($this->usePNG){
            if($headers)header ('Content-type: image/png');
            imagepng($im,NULL,6);
        }else{
            if($headers)header ('Content-type: image/jpeg');
            imagejpeg($im,NULL,60);
        }
        ImageDestroy($im);
    }
    protected function getUsername(){
        $i = strpos($this->email,'@');
        if($i===false) return $this->email;
        return substr($this->email,0,$i);
    }
    protected function getDomain(){
        $i = strpos($this->email,'@');
        if($i===false) return '';
        return substr($this->email,$i+1);
    }
    function script()
    {
        $email = $this->email;
        if(!$email) return '';
        if($this->getDomain()=='') return $email;
        
        $var = "em" . chr(rand(97,122)). rand(111,9998);
        $codes = array('a'=>'&#97;','e'=>'&#101;','i'=>'&#105;','o'=>'&#111;','u'=>'&#117;','j'=>'&#106;','.'=>'&#46;','s'=>'&#115;');
        $email =strtolower($email);
        
        
        $nemail1 ='';
        $nemail2 ='';
        
        //segment 0
        $seg=$this->getUsername();
        $l = strlen($seg)-1;
        $t = $l-3;
        $nemail1 = $seg[0];
        for($i=1;$i<=$l;$i++)
        {
            $c = substr($seg,$i,1);
            if(array_key_exists($c,$codes))
                $nemail1 .= $codes[$c];
            elseif($i>=$t){
                $nemail1 .= '&#' . ord($c).';';
            }
                $nemail1 .= $c;
        } 
        //segment 1
        $seg=$this->getDomain();
        $l = strlen($seg)-1;
        for($i=0;$i<=$l;$i++)
        {
            $c = substr($seg,$i,1);
            if(array_key_exists($c,$codes))
                $nemail2 .= $codes[$c];
            else
                $nemail2 .= $c;
        }
        
        $img = $this->imagehref();
        
        return "<script type='text/javascript'>
        (function() {
            var prefix = 'm&#97;&#105;lt&#111;:';
            var path = 'hr' + 'ef' + '=';
            var $var = '$nemail1' + '&#64;';
            $var = $var + '$nemail2' ;
            document.write( '<a ' + path + '\'' + prefix + $var + '\'' +'>' );
            document.write( $var + '<\/a>');
        }());
             
     </script><noscript><img src='$img'/></noscript>";
        
     
    }
    function send(){
        $this->image(true);
    }
    function output()    
    {
        $this->image(false);
    }   
}

?>