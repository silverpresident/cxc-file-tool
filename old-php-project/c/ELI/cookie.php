<?php
/**
 * @author Edwards
 * @copyright 2013
 * @version 20140402
 * 
 * cookie name to avoid containing:    . =
 */
class ELI_cookie implements ArrayAccess, Iterator, Countable
{
    static private $instance = array();
    private $class = '';
    private $_options = array();
    
    static function setLifeTime($time=0, $relative=false){
        if($time ==0){
            $time = time() + 3600;
        }elseif($time < 0){
            $time = time() + $time;
        }else{
            if($relative) $time = time() + $time;
        }
        $this->_options['lifetime'] = $time;
    }
    static function setPath($path='/'){
        $this->_options['path'] = $path;
    }
    static function setSecure($secure=false){
        $this->_options['secure'] = $secure;
    }
    static function setDomain($dom='', $allowSubDomains=true){
        if(empty($dom)){
            #empt string is valid for localhost only
            $dom = (strpos($_SERVER['HTTP_HOST'],'.') !== false) ? $_SERVER['HTTP_HOST']:'';
        }
        // Fix the domain to accept domains with and without 'www.'.
        if (strtolower(substr($dom, 0, 4)) == 'www.')  $dom = substr($dom, 4);
        if($allowSubDomains && substr($dom,0,1)!='.') $dom = '.' . $dom;
        
        // Remove port information.
        $Port = strpos($dom, ':');
        if ($Port !== false)  $dom = substr($dom, 0, $Port);
        
        $this->_options['domain']= $dom;
    }
    static function build($class='')
    {
        $item = empty($class)?'*':$class;
        if(!isset(self::$instance[$item])){
            $c = __CLASS__;
            self::$instance[$item] = new $c($item);
        }
        return self::$instance[$item];
    }
    function setClass($itemClass)
    {
        $this->class = $itemClass;
    }
    public function __construct($itemClass='') {
        if(func_num_args()){
            $this->setClass(func_get_arg(0));
        }
       $this->_options = session_get_cookie_params();
    }
    public function __toString() {
        if($this->class){
            $p = $this->class;
            $l = strlen($p);
            $A = array();
            foreach($_COOKIE as $k => $v){
                if(substr($k,0,$l)==$p){
                    $A[substr($k,$l)] = $v;
                }
            }
            return print_r($A,1);
        }else{
            return print_r($_COOKIE,1);
        }
    }
    public function __get($name) {
      return $this->Read($name);
    }
    public function __set($name, $value) {
        return $this->write($name,$value);
    }
    public function __unset($name) {
        $this->write($name,null);
    }
    public function __isset($name) {
        $name = "{$this->class}{$name}";
        return isset($_COOKIE[$name]);
    }
    function Read($name, $default=false) {
        $name = "{$this->class}{$name}";
        if(isset($_COOKIE[$name]) || array_key_exists($name,$_COOKIE))
            return $_COOKIE[$name];
        return $default;
    }
    function Write($name, $value=null) {
        if(func_num_args()>=3){
            $tm = (int)func_get_arg(2); 
            if($tm < time()) $value = null;
        }else{
            $tm = $this->_options['lifetime'];
        }
        $key = "{$this->class}{$name}";
        if((null ===$value)){
            #if cookie is in the original set received [=$HTTP_COOKIE_VARS]] (not the current set $_COOKIE)
            if(isset($HTTP_COOKIE_VARS[$key]))setcookie($key, null,1, $this->_options['path'], $this->_options['domain'], $this->_options['secure']);            
            unset($_COOKIE[$key]);
            return false;
        }else{
            $_COOKIE[$key] = $value;
            setcookie($key, $value,$tm, $this->_options['path'], $this->_options['domain'], $this->_options['secure']);
            return $_COOKIE[$key];
        }
    }
    function Seek($name, $default='') {
        $key = "{$this->class}{$name}";
        if(!array_key_exists($key,$_COOKIE)) $this->write($name,$default);
        return $_COOKIE[$key];
    }
    function Assert($name, $default) {
        $key = "{$this->class}{$name}";
        if(!isset($_COOKIE[$key]) || empty($_COOKIE[$key]))
            $this->write($name,$default);
        return $_COOKIE[$key];
    }
    function exists($name){
        $name = "{$this->class}{$name}";
        return array_key_exists($name,$_COOKIE);
    }
    function isEmpty($name)
    {
        $name = "{$this->class}{$name}";
        return empty($_COOKIE[$name]);
    }
    function delete($name)
    {
        $this->write($name,null);
    }
    function toArray(){
        if($this->class){
            $p = $this->class;
            $l = strlen($p);
            $A = array();
            foreach($_COOKIE as $k => $v){
                if(substr($k,0,$l)==$p){
                    $A[substr($k,$l)] = $v;
                }
            }
            return $A;
        }else{
            return $_COOKIE;
        }
    }

    
    public function offsetSet($offset,$value) {
        if ($offset == '') {
            //name required
        }else {
            return $this->write($offset,$value);
        }
    }

    public function offsetExists($offset) {
        return $this->__isset($offset);
    }

    public function offsetUnset($offset) {
        return $this->__unset($offset);
    }

    public function offsetGet($offset) {
        return $this->Read($offset,null);
    }

    public function rewind() {
        return reset($_COOKIE);
    }

    public function current() {
        return current($_COOKIE);
    }

    public function key() {
        return key($_COOKIE);
    }

    public function next() {
        return next($_COOKIE);
    }

    public function valid() {
        return key($_COOKIE) !== null;
    }    

    public function count() {
        return count($this->toArray());
    }
}
?>