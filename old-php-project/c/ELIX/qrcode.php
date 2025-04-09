<?php
/**
 * @author Edwards
 * @copyright 2015
 * @used  
 * 
 * http://webcodingeasy.com/PHP-classes/QR-code-generator-class
 * http://qrserver.com/api/documentation/create-qr-code/
 * http://goqr.me/api/doc/create-qr-code/
 * http://www.phpclasses.org/browse/file/32321.html
 */
namespace ELIX;

class QrCode
{
    const PROVIDER_GOOGLE = 1;
    const PROVIDER_QRSERVER = 2;
    protected $data = array();
    
    public function __construct($data=array()) {
        $this->data['quality'] = 'M';
        $this->data['qzone'] = 4;
        $this->data['format'] = 'png';
        //$this->data['timeout'] = 20;
        if(func_num_args() && is_array($data)){
            $this->setOptions($data);
        }
            
    }
    
    public function __get($name) {
        $name = strtolower($name);
        if(isset($this->data[$name]) || array_key_exists($name,$this->data))
            return $this->data[$name];
            
        return '';
    }
    public function setOptions($data){
        $data = array_change_key_case($data,CASE_LOWER);
        if(isset($data['ecl'])) $this->setQuality( $data['ecl']);
        if(isset($data['ecc'])) $this->setQuality( $data['ecc']);
        if(isset($data['qzone'])) $this->setQuietZone( $data['qzone']);
        if(isset($data['format'])) $this->setFormat( $data['format']);
        if(isset($data['margin'])) $this->setMargin( $data['margin']);
        
        
        
        if(isset($data['size'])) $this->setSize( $data['size']);
        if(isset($data['color'])) $this->data['color'] = $data['color'];
        if(isset($data['bgcolor'])) $this->data['bgcolor'] = $data['bgcolor'];
        if(isset($data['w'])) $this->data['w'] = $data['w'];
        if(isset($data['provider'])) $this->data['provider'] = (int)$data['provider'];
        if(isset($data['quality'])) $this->setQuality( $data['quality']);
    }
    public function setEcc($ecc){
        return $this->setQuality($ecc);
    }
    public function setQuality($ecl){
        $ecl = strtoupper(substr(trim($ecl),0,1));
        if(!in_array($ecl,array('L','M','Q','H'))) $ecl = 'M';
        $this->data['quality'] = $ecl;
        return $this;
    }
    public function setFormat($value){
        $value = strtolower(substr(trim($value),0,4));
        if(!in_array($value,array('png','gif','jpeg','jpg','svg','eps'))) $value = 'png';
        $this->data['format'] = $value;
        return $this;
    } 
    public function setQuietZone($pixels){
        $pixels = (int)$pixels;
        if($pixels < 0) $pixels =0;
        if($pixels>100)$pixels = 100;
        $this->data['qzone'] = $pixels;
        return $this;
    }
    public function setMargin($pixels){
        $pixels = (int)$pixels;
        if($pixels < 0) $pixels =0;
        if($pixels>50)$pixels = 50;
        $this->data['margin'] = $pixels;
        return $this;
    }
    public function setSize($size){
        if(strpos($size,'x')){
            $x = explode(',',$size);
            $w = (int)$x[0];
            $h = (int)$x[1];
            if($w <10 && $w !=0 ) $w = 250;
            if($w > 1000 ) $w = 1000;
            if($h <10 && $h !=0 ) $h = 250;
            if($h > 1000 ) $h = 1000;
            $this->data['size'] = "{$w}x{$h}";
        }else{
            $size = (int)$size;
            if($size <10 && $size !=0 ) $size = 250;
            if($size > 1000 ) $size = 1000;
            $this->data['size'] = "{$size}x{$size}";
        }
        if($this->data['size'] == "0x0")$this->data['size'] = '';
        return $this;
    }
    
    public function setData($text){
        $this->data['data'] = $text;
        return $this;
    }
    public function setUrl($url){
        $this->data['data'] = preg_match("#^https?\:\/\/#", $url) ? $url : "http://{$url}";
        return $this;
    }
    
    //creating code with bookmark metadata 
    public function setBookmark($title, $url){ 
        $this->data['data'] = "MEBKM:TITLE:".$title.";URL:".$url.";;"; 
        return $this;
    }
    public function setSms($phone, $text){ 
        $this->data['data'] = "SMSTO:".$phone.":".$text; 
        return $this;
    } 
      
    //creating code with phone 
    public function setPhonenumber($phone){ 
        $this->data['data'] = "TEL:".$phone; 
        return $this;
    } 
    public function setTel($phone){ 
        $this->data['data'] = "TEL:".$phone; 
        return $this;
    } 
      
    //creating code with mecard metadata 
    public function setContact_info($name, $address, $phone, $email){ 
        $this->data['data'] = "MECARD:N:".$name.";ADR:".$address.";TEL:".$phone.";EMAIL:".$email.";;"; 
        return $this;
    } 
      
    //creating code wth email metadata 
    public function setEmail($email, $subject, $message){ 
        $this->data['data'] = "MATMSG:TO:".$email.";SUB:".$subject.";BODY:".$message.";;"; 
        return $this;
    } 
      
    //creating code with geo location metadata 
    public function setGeo($lat, $lon, $height){ 
        $this->data['data'] = "GEO:".$lat.",".$lon.",".$height; 
        return $this;
    } 
      
    //creating code with wifi configuration metadata 
    public function setWifi($type, $ssid, $pass){ 
        $this->data['data'] = "WIFI:T:".$type.";S:".$ssid.";P:".$pass.";;"; 
        return $this;
    } 
      
    //creating code with i-appli activating meta data 
    public function setIappli($adf, $cmd, Array $param){ 
        $param_str = ""; 
        foreach($param as $val) 
        { 
            $param_str .= "PARAM:".$val["name"].",".$val["value"].";"; 
        } 
        $this->data['data'] = "LAPL:ADFURL:".$adf.";CMD:".$cmd.";".$param_str.";"; 
        return $this;
    }
    //creating code with gif or jpg image, or smf or MFi of ToruCa files as content 
    public function setContent($type, $size, $content){ 
        $this->data['data'] = "CNTS:TYPE:".$type.";LNG:".$size.";BODY:".$content.";;"; 
        return $this;
    } 
    /**
     * qrCode::href()
     * 
     * @param mixed $data
     * @param integer $size
     * @param string $ecl
     * @param string $bgColor as hex (FFF or FFFFFF) or rgb 255-255-255
     * @param string $fgColor as $bgColor
     * @param integer $provider
     * @return
     */
    public function url(){
        $provider= $this->provider;
        if($provider != 1 && $provider!=2){
            $provider = rand(1,2);
        }
        $d = array();
        if($this->bgcolor && ($this->bgcolor==$this->color)){
            if($this->bgcolor) $provider = 2;
            $bg=$this->bgcolor;
        }else{
            $bg  = '';
        }
        if($provider == 1){
            if($this->size)$d['chs'] = "{$this->size}";
            else $d['chs'] = "250x250";
            $d['chld'] = $this->quality;
            if($this->margin)$d['chld'] .= '|'. $this->margin;
            if($this->color)$d['chco'] = $this->color;
            
            $d['chl'] = $this->getUnencodedData();
            $url = 'https://chart.googleapis.com/chart?cht=qr&' . http_build_query($d);
        }else{
            $d['ecc'] = $this->quality;
            if($bg)$d['bgcolor'] = $bg;
            if($this->size)$d['size'] = "{$this->size}";
            if($this->color)$d['color'] = $this->color;
            if($this->margin)$d['margin'] = $this->margin;
            if($this->format)$d['format'] = $this->format;
            if($this->qzone || ($this->qzone===0))$d['qzone'] = $this->qzone;
            
            $d['data'] = $this->getUnencodedData();
            $url = 'http://api.qrserver.com/v1/create-qr-code/?' . http_build_query($d);
        }
        return $url;
    }
    private function getEncodedData(){
        if(isset($this->data['data']))
            return urlencode($this->data['data']);
        else
            return '';
    }
    private function getUnencodedData(){
        if(isset($this->data['data']))
            return $this->data['data'];
        else
            return '';
    }//getting image 
    public function getImage(){
        static $images =array();
        /*ksort($this->data);
        
        foreach($this->data as &$val){
            if(is_array($val)) $val = implode(',',$val);
        }
        $key = implode('-',array_keys($this->data));
        $key .= '*'. implode('-',array_values($this->data));*/
        
        $key = $url = $this->url();
        if(isset($images[$key])) return $images[$key];
        $ch = curl_init(); 
        curl_setopt($ch, CURLOPT_URL, $url); 
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); 
        curl_setopt($ch, CURLOPT_HEADER, false); 
        curl_setopt($ch, CURLOPT_TIMEOUT, 20); 

        $response = curl_exec($ch); 
        curl_close($ch);
        if($response) $images[$key] = $response;
        return $response; 
    } 
      
    //forcing image download 
    public function download($name = 'qrcode.png'){ 
        header("Content-Disposition: attachment; filename=$name");
        switch($this->format){
        case 'eps': header('Content-Type: image/x-eps'); break;
        case 'svg': header('Content-Type: image/svg+xml');  break;
        case 'jpeg': 
        case 'jpg': header('Content-Type: image/jpeg');  break;
        case 'gif': header('Content-Type: image/gif');  break;
        default: header('Content-Type: image/png'); 
        }
        echo $this->getImage(); 
    } 
    
    //save image to server 
    public function save($path){ 
        file_put_contents($path, $this->getImage()); 
    }  
}

?>