<?php
/**
 * @author President
 * @copyright 2015
 * @version 20150822
 */
namespace ELIX;

class Image
{
    private $image = null;
    protected $data =  array();

    /**
     * OBJECT_image::version()
     * used to get a stamp for cacheing
     * @return - separated entity tag
     */
    function __get($name) {
        $name = strtolower($name);
        if($name=='length') $name='size';
        if(in_array($name,array('exists'))){
            
        }
        if(array_key_exists($name,$this->data))
            return $this->data[$name];
        else
            return '';
    }
    public function __isset($name) {
        $name = strtolower($name);
        return isset($this->data[$name]);
    }
    public function __destruct() {
        if(is_resource($this->image))
            @imagedestroy($this->image);
    }
    function version()
    {
        $e[] = __CLASS__;
        $e[] = '2';
        
        $temp = array();
        $arr = array('Q','H','W','mime','mtime','size');
        foreach($arr as $k)
            if(isset($this->data[$k]))$temp[] = $this->data[$k];
        //$temp[] = $this->getWidth();
        //$temp[] = $this->getHeight();
        
        $e[] = md5(implode('-',$temp));
        $e[] = md5(file_get_contents($this->file) ); //digest of file
        return implode('-', $e);
    }
    function setQuality($quality=0)
    {
        $this->data['Q'] = $quality;
        return $this;
    }
    public function __construct() {
        if(func_num_args()){
            $a = func_get_arg(0);
            if(file_exists($a)){
                $this->LoadFromFile($a);
            }elseif($a){
                $this->LoadFromString($a);
            }
        }
    }
    function LoadFromString($imagedata){
        $this->image = imagecreatefromstring($imagedata);
        $this->data =array();
        $this->data['exists'] = false;
        $this->data['fileexists'] =  'N';
        $this->data['Q'] = 0;
        $this->data['file'] = '';
        $this->data['imagedata'] = $imagedata;
        $this->data['size'] = strlen($imagedata);
        if(function_exists('getimagesizefromstring')){
            $image_data = @getimagesizefromstring($imagedata);
              $this->data['image_type'] = $image_data[2];
              $this->data['OH'] = $this->data['H'] =  $image_data[1];
              $this->data['OW'] =  $this->data['W'] =  $image_data[0];
              $this->data['mime'] =  $image_data['mime'];
              unset($image_data);
        }else{
            $this->data['image_type'] = '';
            $this->data['OH'] = $this->data['H'] =  imagesy($this->image);
            $this->data['OW'] = $this->data['W'] =  imagesx($this->image);
            $this->data['mime'] =  '';
            
        }
        return $this;
    }
    function LoadFromFile($filename)
    {
        $fe = file_exists($filename) and is_file($filename) and is_readable($filename);
        $this->data =array();
        $this->data['exists'] = $fe;
        $this->data['fileexists'] = $fe?'Y':'N';
        $image_data = @getimagesize($filename);
          $this->data['image_type'] = $image_data[2];
          $this->data['Q'] = 0;
          $this->data['OH'] = $this->data['H'] =  $image_data[1];
          $this->data['OW'] =  $this->data['W'] =  $image_data[0];
          $this->data['mime'] =  $image_data['mime'];
          $this->data['file'] = $filename;
          unset($image_data);
          
          $data =@stat($filename);
          if(!$data )
          {
            $data=array();
            $data['size'] =0;
            $data['mtime'] =time();
          }
          
          foreach(array('atime','mtime','ctime','size') as $k){
                if(isset($data[$k])) $this->data[$k] = $data[$k];
            }
            
          $data = @pathinfo($this->data['file']);
            $this->data['filename'] =$data['filename'];
            if(isset($data['extension'])){
                $this->data['extension'] =$data['extension'];
            }
            $this->data['basename'] =$data['basename'];
          
          
          
          if( $this->image_type == IMAGETYPE_JPEG ) {
             $this->image = imagecreatefromjpeg($filename);
          } elseif( $this->image_type == IMAGETYPE_GIF ) {
             $this->image = imagecreatefromgif($filename);
          } elseif( $this->image_type == IMAGETYPE_PNG ) {
             $this->image = imagecreatefrompng($filename);
          }
          return $this;
    }
    
    function save($filename,$compression=90, $image_type=null,  $permissions=null) {
        if((null ===$image_type)) $image_type=$this->image_type;
      if( $image_type == IMAGETYPE_JPEG ) {
         imagejpeg($this->image,$filename,$compression);
      } elseif( $image_type == IMAGETYPE_GIF ) {
         imagegif($this->image,$filename);         
      } elseif( $image_type == IMAGETYPE_PNG ) {
         imagepng($this->image,$filename);
      }   
      if( $permissions != null) {
         chmod($filename,$permissions);
      }
   }
   function toString($quality=0,$image_type=null ){
        ob_start();
        $this->output($quality,$image_type);
        $image_data = ob_get_contents();
        ob_end_clean();
        return $image_data;
   }
   function output($quality=0,$image_type=null ) 
   {
        if((null ===$image_type)) $image_type=$this->image_type;
        
        if((null ===$quality) || $quality<=0)
        {
            if($this->data['Q']>=0)
                $quality=$this->data['Q'];
            else
                $quality=0;
        }
        
        if( $image_type == IMAGETYPE_GIF )
        { $quality=0; //does not support quality
        }else
        {
            if( $image_type == IMAGETYPE_PNG )
            {
                if($quality<0)$quality=0;
                if($quality>100)$quality=100;
                //png uses a 0:no compress to 9:full compress
                $t = 9 *   ($quality/100);
                $quality=9-$t;
            } else
            {
                if($quality<0)$quality=0;
                if($quality>100)$quality=100;                
            }

        }
        
        if($quality==0)
        {
              if( $image_type == IMAGETYPE_JPEG ) {
                 imagejpeg($this->image);
              } elseif( $image_type == IMAGETYPE_GIF ) {
                 imagegif($this->image);         
              } elseif( $image_type == IMAGETYPE_PNG ) {
                 imagepng($this->image);
              }              
        }else
        {
              if( $image_type == IMAGETYPE_JPEG ) {
                 imagejpeg($this->image,NULL,$quality);
              } elseif( $image_type == IMAGETYPE_GIF ) {
                 imagegif($this->image,NULL);         
              } elseif( $image_type == IMAGETYPE_PNG ) {
                 imagepng($this->image,NULL,$quality);
              }
        }
 
   }
    function getWidth() {
        return imagesx($this->image);
    }
    function getHeight() {
        return imagesy($this->image);
    }
    function resizeToHeight($height) {
        $height = (int)$height;
        if($this->getHeight() != $height){
            $ratio = $height / $this->getHeight();
            $width = $this->getWidth() * $ratio;
            $this->resize($width,$height);
        }
        return $this;
    }
    function resizeToWidth($width) {
        $width = (int)$width;
        if($this->getWidth() != $width){
            $ratio = $width / $this->getWidth();
            $height = $this->getHeight() * $ratio;
            $this->resize($width,$height);
        }
        return $this;
    }
    function scale($scale) {
        if(is_float($scale)){
            if(($scale >=-1) && $scale <= 1){
                $factor = $scale;
            }else{
                $factor = $scale/100;
            }
        }else{
            $scale = (float)$scale;
            $factor = $scale/100;
        }
        
        if($factor != 0){
            $width = $this->getWidth();
            $height = $this->getHeight();
            if($factor < 0){
                $width = $width - ($width * abs($factor));
                $height = $height - ($height * abs($factor));
            }else{
                $width = $width * $factor;
                $height = $height * $factor;
            }
            $this->resize($width,$height);
        }
        return $this;
    }
    function scaleXY($scaleX,$scaleY) {
        if(is_float($scaleX)){
            if(($scaleX >=-1) && $scaleX <= 1){
                $factorX = $scaleX;
            }else{
                $factorX = $scaleX/100;
            }
        }else{
            $scaleX = (float)$scaleX;
            $factorX = $scaleX/100;
        }
        if(is_float($scaleY)){
            if(($scaleY >=-1) && $scaleY <= 1){
                $factorY = $scaleY;
            }else{
                $factorY = $scaleY/100;
            }
        }else{
            $scaleY = (float)$scaleY;
            $factorY = $scaleY/100;
        }
        if(($factorX != 0) || ($factorY != 0)){
            $width = $this->getWidth();
            $height = $this->getHeight();
            if($factorX < 0){
                $width = $width - ($width * abs($factorX));
            }else{
                $width = $width * $factorX;
            }
            if($factorY < 0){
                $height = $height - ($height * abs($factorY));
            }else{
                $height = $height * $factorY;
            }
            $this->resize($width,$height);
        }
        return $this;
    }
    
    function resize($width,$height) {
        $height = (int)$height;
        $width = (int)$width;
        if($width < 0){
            $width=$this->getWidth()-$width;
        }
        if($width==0){
            $width=$this->getWidth();
        }
        if($height < 0 ){
            $height=$this->getHeight()-$height;
        }
        if($height==0){
            $height=$this->getHeight();
        }
        if(($width != $this->getWidth())||($height != $this->getHeight())){
            $new_image = imagecreatetruecolor($width, $height);
            imagecopyresampled($new_image, $this->image, 0, 0, 0, 0, $width, $height, $this->getWidth(), $this->getHeight());
            imagedestroy($this->image);
            $this->image = $new_image;
            $this->data['H'] = $this->getHeight();
            $this->data['W'] = $this->getWidth();
        }
        return $this;
    }
    function cropToSize($x,$y,$width,$height)
    {
        $height = (int)$height;
        $width = (int)$width;
        if($width < 0){
            $width=$this->getWidth()-$width;
        }
        if($width==0){
            $width=$this->getWidth();
        }
        if($height < 0 ){
            $height=$this->getHeight()-$height;
        }
        if($height==0){
            $height=$this->getHeight();
        }
        if(($width != $this->getWidth())||($height != $this->getHeight())){
            $new_image = imagecreatetruecolor($width, $height);
            imagecopyresampled($new_image, $this->image, 0, 0, $x, $y, $width, $height, $width, $height);
            imagedestroy($this->image);
            $this->image = $new_image;
            $this->data['H'] = $this->getHeight();
            $this->data['W'] = $this->getWidth();
        }
        return $this;  
    } 
    function stampImage($stamp, $x=0,$y=0,$width=0,$height=0, $opacity=50){
        if($x<0){
            $x = imagesx($this->image) + $x;
        }
        if($y<0){
            $y = imagesy($this->image) + $y;
        }
        if($height==0) $height = $this->getHeight();
        if($width==0) $width = $this->getWidth();
        // Merge the stamp onto our photo with an opacity (transparency) of 50%
        imagecopymerge($this->image, $stamp, $x, $y, 0, 0, imagesx($stamp), imagesy($stamp), $opacity);
        
        return $this;
    }
}
if (!function_exists('getimagesizefromstring')) {
      function getimagesizefromstring($string_data)
      {
         $uri = 'data://application/octet-stream;base64,'  . base64_encode($string_data);
         return getimagesize($uri);
      }
}