<?php
/**
 * @author Edwards
 * @copyright 2014
 * 
 * http://www.w3.org/Protocols/rfc2616/rfc2616-sec14.html#sec14.9
 * 
 * this class is intended primarily to handle RESPONSE directives or pare reuest directives
 * 

    cache-request-directive =
           "no-cache"                          ; Section 14.9.1
         | "no-store"                          ; Section 14.9.2
         | "max-age" "=" delta-seconds         ; Section 14.9.3, 14.9.4
         | "max-stale" [ "=" delta-seconds ]   ; Section 14.9.3
         | "min-fresh" "=" delta-seconds       ; Section 14.9.3
         | "no-transform"                      ; Section 14.9.5
         | "only-if-cached"                    ; Section 14.9.4
         | cache-extension                     ; Section 14.9.6
     cache-response-directive =
           "public"                               ; Section 14.9.1
         | "private" [ "=" <"> 1#field-name <"> ] ; Section 14.9.1
         | "no-cache" [ "=" <"> 1#field-name <"> ]; Section 14.9.1
         | "no-store"                             ; Section 14.9.2
         | "no-transform"                         ; Section 14.9.5
         | "must-revalidate"                      ; Section 14.9.4
         | "proxy-revalidate"                     ; Section 14.9.4
         | "max-age" "=" delta-seconds            ; Section 14.9.3
         | "s-maxage" "=" delta-seconds           ; Section 14.9.3
         | cache-extension                        ; Section 14.9.6
    cache-extension = token [ "=" ( token | quoted-string ) ]
             
 * static class
 *  ::get($type) $type = CACHECONTROL, PRAGMA, BOTH default CACHECONTROL
 *  ::CACHECONTROL()
 *  ::PRAGMA()
 *  ::build($str) $str = the string to use
 *  implement all functions of the instance class on a STATIC basis that works on the current $_SERVER cache controll value
 * 
 * INSTANCE
 *  ->noCache() : is the no cache flag set 
 * 
 */
namespace ELIX;

class cacheControl
{
    const ONE_HOUR = 3600;
    const ONE_DAY = 86400;
    const ONE_WEEK = 604800;
    
    static function cacheControl() {
        $v = isset($_SERVER['HTTP_CACHE_CONTROL'])?$_SERVER['HTTP_CACHE_CONTROL']:'';
        return self::build($v);
    }
    static function pragma() {
        $v = isset($_SERVER['HTTP_PRAGMA'])?$_SERVER['HTTP_PRAGMA']:'';
        return self::build($v);
    }
    static function build($string) {
        $o = new self($string);
        return $o;
    }/*
    static function get($type='CACHECONTROL') {
        $test = strtoupper($type);
        if($test =='BOTH'){
            $v[] = isset($_SERVER['HTTP_CACHE_CONTROL'])?$_SERVER['HTTP_CACHE_CONTROL']:'';
            $v[] = isset($_SERVER['HTTP_PRAGMA'])?$_SERVER['HTTP_PRAGMA']:'';
            $v = array_filter($v);
            return self::build(implode(',',$v));
        }elseif($test =='PRAGMA'){
            return self::pragma();
        }elseif($test =='CACHECONTROL' || $test =='CACHE-CONTROL'){
            return self::cacheControl();
        }else{
            return self::build($type);
        }
    }*/
    
    #INSTANCE items
    protected $cachedirective = '';
    protected $parsed = array();
    public function __construct($cache_control='') {
        if($cache_control)$this->setDirective($cache_control);
    }
    public function clear() {
        $this->cachedirective = '';
        $this->parsed = array();
        return $this;
    }
    public function reset($cache_control='') {
        if(func_num_args()==0){
            $cache_control = $this->cachedirective;
        }
        $this->setDirective($cache_control);
        return $this;
    }
    protected function setDirective($cache_control){
        $this->cachedirective = $cache_control;
        $cache_control_parsed = array();
        if(trim($cache_control) == ''){
            $this->parsed = $cache_control_parsed;
            return $this;
        }
        if((strpos($cache_control,'"') === false)){
            $cache_control_array = explode(',', $cache_control);
            $cache_control_array = array_map('trim', $cache_control_array);
            
            foreach ($cache_control_array as $value) {
                if (strpos($value, '=') !== FALSE) {
                    $temp = array();
                    parse_str($value, $temp);
                    $cache_control_parsed += $temp;
                }
                else {
                    $cache_control_parsed[$value] = TRUE;
                }
            }
            
        }else{
            $cache_control = ltrim($cache_control);
            $last = 0;
            $l =strlen($cache_control);
            for($i=0;$i<=$l;$i++){
                $c = substr($cache_control,$i,1);
                $value = '';
                if($c==','){
                    $value = substr($cache_control,$last, $i-$last); 
                    $value = trim($value,', ');
                    $last = $i+1;
                }else if ($c == '"'){
					$x = strpos($cache_control,'"',$i+1);
					if($x === false){
						$x = $l;
					}else{
						$x++;
					}
					$value = substr($cache_control,$last, $x-$last);
					$i = $x;
					$last=$i+1;
                }else if($i == $l){
					$value = substr($cache_control,$last); 
					$value = trim($value,', ');
				}
                if($value){
                    if (strpos($value, '=') !== FALSE) {
                        $temp = array();
                        parse_str($value, $temp);
						foreach($temp as $k=>$v){
							$temp[$k] = trim($v,'"');
						}						
                        $cache_control_parsed += $temp;
                    }
                    else {
                        $cache_control_parsed[$value] = TRUE;
                    }
                }
            }
        }
        $cache_control_parsed = array_change_key_case($cache_control_parsed,CASE_LOWER);
        foreach($cache_control_parsed as $k=>$v){
            if(in_array($k,array('max-age','s-maxage','min-fresh','max-stale',
                                'pre-check','post-check'))){
                $cache_control_parsed[$k] = (int)$v;
            }
        }
        $this->parsed = $cache_control_parsed;
        return $this;
    }
    public function __toString() {
        return $this->toString();
    }
    public function toArray() {
        return $this->parsed;
    }
    public function toString() {
        $a =array();
        foreach($this->parsed as $k=>$v){
            if(in_array($k,array('max-age','s-maxage','min-fresh','max-stale',
                                'pre-check','post-check'))){
                $a[] = "$k=$v";
            }elseif(is_bool($v)){
                if($v) $a[] = $k;
            }else if($v===0 || $v===1){
                if(in_array($k,array('no-cache','no-store',
                                        'no-transform','only-if-cached',
                                        'public','private','must-revalidate','proxy-revalidate'))){
                    if($v) $a[] = $k;
                }else{
                    $a[] = "$k=\"{$v}\"";
                }
            }elseif($v===''){
                $a[] = "$k=\"\"";
            }elseif($v || ($v==='0')){
                if(is_int($v) ){
                    $a[] = "$k=$v";
                }else{
                    $a[] = "$k=\"{$v}\"";
                }
            }
        }
        
        return implode(', ',$a);
    }
    public function __call($name, $arguments) {
        $name =strtolower($name);
        $name =str_replace('_','',$name);
        if($name =='public' || $name=='private'){
            $name = 'set'.$name;
        }
        if($name =='ispublic' || $name=='isprivate'){
            $name = 'set'.substr($name,2);
        }
        if(method_exists($this,$name)){
            return call_user_func_array(array($this,$name),$arguments);
        }
    }
#STRICTLY BOOLEAN
    public function noStore() { 
        $key = 'no-store';
        if(func_num_args()){
            $this->parsed[$key] = !!func_get_arg(0);
            return $this;
        }
        if(isset($this->parsed[$key])){
            return $this->parsed[$key];
        }
        return false;
    }
    public function noTransform() { 
        $key = 'no-transform';
        if(func_num_args()){
            $this->parsed[$key] = !!func_get_arg(0);
            return $this;
        }
        if(isset($this->parsed[$key])){
            return $this->parsed[$key];
        }
        return false;
    }
    public function onlyIfCached() { 
        $key = 'only-if-cached';
        if(func_num_args()){
            $this->parsed[$key] = !!func_get_arg(0);
            return $this;
        }
        if(isset($this->parsed[$key])){
            return $this->parsed[$key];
        }
        return false;
    }
    public function setPublic() { 
        $key = 'public';
        if(func_num_args()){
            $this->parsed[$key] = !!func_get_arg(0);
            return $this;
        }
        if(isset($this->parsed[$key])){
            return $this->parsed[$key];
        }
        return false;
    }
    public function mustRevalidate() { 
        $key = 'must-revalidate';
        if(func_num_args()){
            $this->parsed[$key] = !!func_get_arg(0);
            return $this;
        }
        if(isset($this->parsed[$key])){
            return $this->parsed[$key];
        }
        return false;
    }
    public function proxyRevalidate() { 
        $key = 'proxy-revalidate';
        if(func_num_args()){
            $this->parsed[$key] = !!func_get_arg(0);
            return $this;
        }
        if(isset($this->parsed[$key])){
            return $this->parsed[$key];
        }
        return false;
    }
#Strictly Integer (delta-seconds )
    public function maxAge() { 
        $key = 'max-age';
        if(func_num_args()){
            $this->parsed[$key] = (int)func_get_arg(0);
            return $this;
        }
        if(isset($this->parsed[$key])){
            return $this->parsed[$key];
        }
        return false;
    }
    public function sMaxAge() { 
        $key = 's-max-age';
        if(func_num_args()){
            $this->parsed[$key] = (int)func_get_arg(0);
            return $this;
        }
        if(isset($this->parsed[$key])){
            return $this->parsed[$key];
        }
        return false;
    }
    public function maxStale() { 
        $key = 'max-stale';
        if(func_num_args()){
            $this->parsed[$key] = (int)func_get_arg(0);
            return $this;
        }
        if(isset($this->parsed[$key])){
            return $this->parsed[$key];
        }
        return false;
    }
    
    public function minFresh() { 
        $key = 'min-fresh';
        if(func_num_args()){
            $this->parsed[$key] = (int)func_get_arg(0);
            return $this;
        }
        if(isset($this->parsed[$key])){
            return $this->parsed[$key];
        }
        return false;
    }
    public function postCheck() { 
        $key = 'post-check';
        if(func_num_args()){
            $this->parsed[$key] = (int)func_get_arg(0);
            return $this;
        }
        if(isset($this->parsed[$key])){
            return $this->parsed[$key];
        }
        return false;
    }
    public function preCheck() { 
        $key = 'pre-check';
        if(func_num_args()){
            $this->parsed[$key] = (int)func_get_arg(0);
            return $this;
        }
        if(isset($this->parsed[$key])){
            return $this->parsed[$key];
        }
        return false;
    }
#variable type
    public function noCache() { 
        $key = 'no-cache';
        if(func_num_args()){
            $value = func_get_arg(0);
            if(is_bool($value)){
                $this->parsed[$key] = $value;
            }else if($value===1 || $value===0){
                $this->parsed[$key] = (bool)$value;
            }else{
                $this->parsed[$key] = (string)$value;
            }
            return $this;
        }
        if(isset($this->parsed[$key])){
            return $this->parsed[$key];
        }
        return false;
    }
    public function setPrivate() {
        $key = 'private';
        if(func_num_args()){
            $value = func_get_arg(0);
            if(is_bool($value)){
                $this->parsed[$key] = $value;
            }else if($value===1 || $value===0){
                $this->parsed[$key] = (bool)$value;
            }else{
                $this->parsed[$key] = (string)$value;
            }
            return $this;
        }
        if(isset($this->parsed[$key])){
            return $this->parsed[$key];
        }
        return false;
    }
    public function extension($token,$value=null) {
        $key = strtolower($token);
        if(func_num_args()==1){
            if(isset($this->parsed[$key])){
                return $this->parsed[$key];
            }
            return null;
        }else{
            if(in_array($key,array('max-age','s-maxage','min-fresh','max-stale',
                                'pre-check','post-check'))){
                $this->parsed[$key] = (int)$value;
            }else if(is_bool($value)){
                $this->parsed[$key] = $value;
            }else{
                $this->parsed[$key] = (string)$value;
            }
            return $this;
        }
    }
    public function set($token,$value) {
        $key = strtolower($token);
        if($value === null){
            unset($this->parsed[$key]);
        }else{
            if(in_array($key,array('max-age','s-maxage','min-fresh','max-stale',
                                'pre-check','post-check'))){
                $value = (int)$value;
            }
            $this->parsed[$key] = $value;
        }
        return $this;
    }
    
    public function get($token,$default=null) {
        $key = strtolower($token);
        if(isset($this->parsed[$key])){
            return $this->parsed[$key];
        }
        return $default;
    }
    public function has($value) { 
        if(isset($this->parsed[$key])){
            return true;
        }
        return false;
    }
    /*public function parse($key, $terminators=','){
        if(!$key) return '';
        $rest = stristr($this->cachedirective,$key);
        if($rest===false) return '';
        $l = strlen($key);
        $rest = substr($rest,$l);
        $rest = trim($rest,'= ');
        if(!$rest) return '';
        if($rest[0] == '"' || $rest[0]=="'"){
            $n = $rest[0];
            $rest = trim($rest,$n);
            if(strpos($rest,$n)===false) $rest .= $n; //this line ensure the rest of string is return if the QUOTE is incomplete
        }else{
            $n = ',';
        }
        $value = stristr($rest,$n,true);
        if($value ===false) return '';
        return $value;
    }
    public function split(){
        preg_match_all("/([^,= ]+)[=]*([^,=]*)/", $this->cachedirective, $output_array);
        $a=array();
        foreach($output_array[1] as $k=>$v){
            $a[$v] =$output_array[2][$k] ;
        }
        return $a;
    }*/
    
}
