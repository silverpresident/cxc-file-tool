<?php
/**
 * @author Edwards
 * @copyright 2015
 *  * 
 * 
 * 
 */
namespace ELIX;
class dataUrl
{
    public static function get($path) {
        $mime ='';
        $encode = 'base64';
        $dataUrlString ='';
        if(file_exists($path)){
            $dataUrlString = file_get_contents($path);
            $mime = ELIX::FileSystem()->getExtensionMimeType($path);
        }
        
        return new dataUrl($dataUrlString,$mime,$encode);
    }
    public static function parse($dataUrlString) {
        $mime ='';
        $encode ='';
        $prefix = substr($dataUrlString,0,5);
        if(strtolower($prefix)=='data:'){
            $dataUrlString = substr($dataUrlString,5);
        }
        $x = explode(',',$dataUrlString,2);
        if(isset($x[1])){
            $dataUrlString = $x[1];
        }
        if(isset($x[0])){
            $prefix = $x[0];
            $x = explode(';',$prefix);
            $c = count($x);
            if($c > 2){
                $encode = strtolower($x[$c-1]);
                if($encode == 'base64'){
                    array_pop($x);
                }else{
                    $encode = '';
                }
                $mime = implode(';',$x);
            }else{
                if(isset($x[0])){
                    $mime = strtolower($x[0]); 
                }
                if(isset($x[1])){
                    $encode = strtolower($x[1]); 
                }
            }
        }
        if($encode =='base64'){
            $dataUrlString = base64_decode($dataUrlString);
        }elseif($encode == ''){
            if(preg_match('/^[a-zA-Z0-9\/\r\n+]*={0,2}$/', $dataUrlString)){
                $prefix = base64_decode($dataUrlString,true);
                if($prefix !== false){
                    $dataUrlString = $prefix;
                    $encode = 'base64';
                }
            }
            
        }
        return new dataUrl($dataUrlString,$mime,$encode);
    }
    private $data,$mime,$encode;
    public function __construct($dataUrlString=null,$mime=null,$encode=null) {
        if(func_num_args()){
            $this->data = $dataUrlString;
            $this->mime = $mime;
            $this->encode = $encode;
        }
    }
    public function __toString() {
        $r = array('data:');
        if($this->mime){
            $r[] = $this->mime;
        }
        if($this->encode){
            $r[] = ';';
            $r[] = $this->encode;
        }
        $r[] = ',';
        if(strtolower($this->encode) =='base64'){
            $r[] = base64_encode($this->data);
        }else{
            $r[] = $this->data;
        }
        return implode('',$r);
    }
    public function __get($name) {
        $name = strtolower($name);
        
        if(method_exists($this,$name))
            return $this->$name();
        return '';
    }
    public function url() { return $this->__toString();}
    public function data() { return $this->data;}
    public function mime() { return $this->mime;}
    public function encode() { return $this->encode;}
}
