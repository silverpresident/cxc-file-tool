<?php
/**
 * @author President
 * @copyright 2010
 * @version 20140402
 */



class ELI_image
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
        
        if(isset($this->data[$name]) || array_key_exists($name,$this->data))
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
    }
    public function __construct() {
        if(func_num_args()){
            $a = func_get_arg(0);
            $this->LoadFromFile($a);
        }
    }
    function LoadFromString($imagedata){
        $this->image = imagecreatefromstring($imagedata);
        $this->data['exists'] = false;
        $this->data['fileexists'] =  'N';
        $this->data['image_type'] = '';
        if(!isset($this->data['Q']))$this->data['Q'] = 0;
        $this->data['OH'] = $this->data['H'] =  imagesy($this->image);
        $this->data['OW'] =  $this->data['W'] =  imagesx($this->image);
        $this->data['mime'] =  '';
        $this->data['file'] = '';
        $this->data['imagedata'] = $imagedata;
        $this->data['size'] = strlen($imagedata);
        
         
        
    }
    function LoadFromFile($filename)
    {
        $fe = file_exists($filename) and is_file($filename) and is_readable($filename);
        $this->data =array();
        $this->data['exists'] = $fe;
        $this->data['fileexists'] = $fe?'Y':'N';
        $image_data = @getimagesize($filename);
          $this->data['image_type'] = $image_data[2];
          if(!isset($this->data['Q']))$this->data['Q'] = 0;
          $this->data['OH'] = $this->data['H'] =  $image_data[1];
          $this->data['OW'] =  $this->data['W'] =  $image_data[0];
          $this->data['mime'] =  $image_data['mime'];
          $this->data['file'] = $filename;
          unset($image_data);
          $data =@stat($filename);
          if(!$data )
          { 
            $data['size'] =0;
            $data['mtime'] =time();
          }
          
          
          foreach(array('atime','mtime','ctime','size') as $k){
                if(isset($data[$k])) $this->data[$k] = $data[$k];
            }
            
          $xFilenameArray = explode('.', $filename);
          
          $data = pathinfo($this->data['file']);
            $this->data['filename'] =$data['filename'];
            if(isset($data['extension'])){
                $this->data['extension'] =$data['extension'];
            }else{
                $c=count($xFilenameArray)-1;
                if($c){
                    $this->data['extension'] =$xFilenameArray[$c];
                }
            }
            $this->data['basename'] =$data['basename'];
          
          
          
          $c=count($xFilenameArray)-1;
          if($c!=0)
          {
            $c--;
            $xFilenameArray[$c] = $xFilenameArray[$c] . "_temp";  
          }else
            $xFilenameArray[$c] = "temp_".$xFilenameArray[$c] ;  
          
          $this->data['_file'] = implode('.',$xFilenameArray);
          
           
          if( $this->image_type == IMAGETYPE_JPEG ) {
             $this->image = imagecreatefromjpeg($filename);
          } elseif( $this->image_type == IMAGETYPE_GIF ) {
             $this->image = imagecreatefromgif($filename);
          } elseif( $this->image_type == IMAGETYPE_PNG ) {
             $this->image = imagecreatefrompng($filename);
          }    
    }
    
    function save($filename, $image_type=null, $compression=90, $permissions=null) {
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
      $ratio = (float)$height / (float)$this->getHeight();
      $width = $this->getWidth() * $ratio;
      $this->resize($width,$height);
      
   }
   function resizeToWidth($width) {
      $ratio = (float)$width / (float)$this->getWidth();
      $height = $this->getheight() * $ratio;
      $this->resize($width,$height);
      
   }
   function scale($scale) {
      $width = $this->getWidth() * $scale/100;
      $height = $this->getheight() * $scale/100; 
      $this->resize($width,$height);
   }
   function resize($width,$height) {
   if($width<=0) $width=2;
   if($height<=0) $height=2;
      $new_image = imagecreatetruecolor($width, $height);
      imagecopyresampled($new_image, $this->image, 0, 0, 0, 0, $width, $height, $this->getWidth(), $this->getHeight());
      //unset($this->image);
      imagedestroy($this->image);
      $this->image = $new_image;
      $this->data['H'] = $this->getHeight();
      $this->data['W'] = $this->getWidth();
   }
   function cropToSize($x,$y,$width,$height)
    {
        if(empty($width)) $width = $this->getWidth();
        if(empty($height)) $height = $this->getHeight();
        $new_image = imagecreatetruecolor($width, $height);
        $r = imagecopyresampled($new_image, $this->image, 0, 0, $x, $y, $width, $height, $width, $height);
        imagedestroy($this->image);
        $this->image = $new_image;
        $this->data['H'] = $this->getHeight();
        $this->data['W'] = $this->getWidth();
        return $r;  
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


    }
}
?>