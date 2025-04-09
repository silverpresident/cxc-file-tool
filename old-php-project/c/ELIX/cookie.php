<?php
/**
 * @author Edwards
 * @copyright 2015
 * 
 */
namespace ELIX;

class COOKIE{
    
    
    public function create($name, $value, $expire = 0, $path = null, $domain = null, $secure = false, $httponly = false)
    {
        $c = new cookie_item($name);
        $c
        ->HTTPOnly($httponly)
        ->Secure($secure)
        ->Path($path)
        ->Expire($expire);
        if($domain){
            $c->Domain($domain);
        }
        $c->Value($value);
        return $c;
    }
    public function getRogueCookies(){
        $affected =array();
        if(isset($_SERVER['HTTP_COOKIES'])){
            foreach(explode('; ',$str) as $k => $v){
                preg_match('/^(.*?)=(.*?)$/i',trim($v),$matches);
                $kv  = $matches[1];
                // Null byte check
                if (strpos($kv, '%00', 0) !== false) {
                    $affected[] = $kv;
                    continue;
                }
                // Rogue [ check
                if (substr_count($kv, '[') != substr_count($kv, ']')) {
                    $affected[] = $kv;
                    continue;
                }
            }
        }
        return $affected;
    }
    public function unsetRogueCookies(){
        if(headers_sent()){
            return;
        }
        foreach($this->getRogueCookies() as $k){
            setcookie($key, NULL);
        }
    }
    public function delete($cookieName=null)
    {
        if(func_num_args() ==0)
            $this->getManager()->delete();
        else
            $this->getManager()->delete($cookieName);
    }
    public function getManager()
    {
        static $instance = null;
        if($instance === null){
            $instance = new cookie_collection;
        }
        return $instance;
    }
    public function getCollection()
    {
        $instance = new cookie_collection;
        return $instance;
    }
}

class cookie_collection{
    private $_cookies;
    
    public function __construct()
    {
        $this->_cookies = array();
        if(isset($_SERVER['HTTP_COOKIES'])){
            foreach(explode('; ',$str) as $k => $v){
                preg_match('/^(.*?)=(.*?)$/i',trim($v),$matches);
                $c = new cookie_item(false,$matches[1],urldecode($matches[2]));
                $this->_cookies[] = $c;
            }
        }elseif(isset($_COOKIE)){
            foreach($_COOKIE as $k=>$v){
                $c = new cookie_item(false,$k,$v);
                $this->_cookies[] = $c;
            }
        }
    }
    
    public function exists($name)
    {
        foreach ($this->_cookies as $cookie)
        {
            if ($name == $cookie->getName()) { return true; }
        }
        return false;
    }
    public function add(cookie_item $c)
    {
        if(is_string($c)){
            $name = $c;
            $c = new cookie_item($name);
        }
        if (!$this->exists($c->getName()))
        {
            $this->_cookies[] = $c;
            return true;
        }
        return false;
    }

    public function create($name, $value, $expire = 0, $path = null, $domain = null, $secure = false, $httponly = false)
    {
        $c = new cookie_item($name);
        $c
        ->HTTPOnly($httponly)
        ->Secure($secure)
        ->Path($path)
        ->Expire($expire);
        if($domain){
            $c->Domain($domain);
        }
        $c->Value($value);
        $this->_cookies[] = $c;
        return $c;
    }
   
    
    private function getCookieIndex($name)
    {

         foreach ($this->_cookies as $index => $cookie)
         {
            if ($name == $cookie->getName()) { return $index; }
         }
         return -1;
    }

    public function get($name)
    {
        $index = $this->getCookieIndex($name);
        if (-1 < $index) { return $this->_cookies[$index]; }
        $c = new cookie_item($name);
        $this->_cookies[] = $c;
        return $c;
    }
    public function getAll($name=null)
    {
        if(func_num_args()==0){
            return $this->_cookies;
        }
        $a=array();
        foreach ($this->_cookies as $cookie)
        {
            if ($name == $cookie->getName()) { $a[] = $cookie; }
        }
        return $a;
    }
    public function __set($name, $value) {
        if($value === null)
            $this->delete($name);
        else
            $this->get($name)->value($value);
    }
    
    public function __get($name) {
        if(!$this->exists($name)) return null;
        return $this->get($name)->getvalue();
    }
    public function __isset($name) {
        return $this->exists($name);
    }
    public function toArray(){
        $a=array();
        foreach ($this->_cookies as $cookie)
        {
            $a[$cookie->getName()] = $cookie->getValue(); 
        }
        return $a;
    }
    public function set($name, $value) {
        $this->__set($name, $value);
    }
    function Read($name, $default=false) {
        if(!$this->exists($name)) return $default;
        return $this->get($name)->getvalue();
    }
    function Write($name, $value=null) {
        if($value === null){
            $this->delete($name);
            return null;
        }
        $cookie = $this->get($name);
        if(func_num_args()>=3){
            $tm = (int)func_get_arg(2); 
            $cookie->Expire($tm);
        }
        $cookie->setValue($value);
        return $cookie->getValue();
    }
    function Seek($name, $default='') {
        if(!$this->exists($name)){
            $this->set($name,$default);
            return $default;
        }
        return $this->get($name)->getvalue();
    }
    function Assert($name, $default) {
        if(!$this->exists($name)){
            $this->set($name,$default);
            return $default;
        }
        $cookie = $this->get($name);
        $v = $cookie->getValue();
        if(empty($v)){
            $this->set($name,$default);
            return $default;
        }
        return $this->get($name)->getvalue();
    }
    

    /**
     * Access a request parameter as int
     *
     * @param string    $name     Parameter name
     * @param mixed     $type     Type to cast to
     * @return cast value
     * 
     * int, integer - cast to integer
        bool, boolean - cast to boolean
        float, double, real - cast to float
        string    - cast to string
        array -  cast to array
        object - cast to object
        null,unset - cast to NULL (PHP 5)
     */
    public function cast($name, $type) {
        $cookie = $this->get($name);
        $type = strtolower($type);
        $value = $cookie->getValue();
        if($type=='boolean') $type ='bool';
        if($type=='double') $type ='float';
        if($type=='real') $type ='float';
        if($type=='unset') $type ='null';
        
        if($type=='bool' && is_string($value)){
            $value = substr(trim(strtolower($value)),0,4);
            $cookie->setValue(in_array($value,array('on','true','yes')));
        }else if(settype($value,$type)){
            $cookie->setValue($value);
        }
        return $cookie->getValue();
    }

    /**
     * Access a request parameter as int
     *
     * @param string    $name     Parameter name
     * @param mixed     $default  Default to return if parameter isn't set or is an array
     * @param bool      $nonempty Return $default if parameter is set but empty()
     * @return int
     */
    public function int($name, $default = 0, $nonempty = false) {
        if(!$this->exists($name)) return $default;
        $cookie = $this->get($name);
        $value = $cookie->getValue();
        if(is_array($value)) return $default;
        if($value === '') return $default;
        if($nonempty && $cookie->isEmpty()) return $default;

        return (int) $value;
    }

    /**
     * Access a request parameter as string
     *
     * @param string    $name     Parameter name
     * @param mixed     $default  Default to return if parameter isn't set or is an array
     * @param bool      $nonempty Return $default if parameter is set but empty()
     * @return string
     */
    public function str($name, $default = '', $nonempty = false) {
        if(!$this->exists($name)) return $default;
        $cookie = $this->get($name);
        $value = $cookie->getValue();
        if(is_array($value)) return $default;
        if($nonempty && empty($value)) return $default;

        return (string) $value;
    }

    /**
     * Access a request parameter and make sure it is has a valid value
     *
     * Please note that comparisons to the valid values are not done typesafe (request vars
     * are always strings) however the function will return the correct type from the $valids
     * array when an match was found.
     *
     * @param string $name    Parameter name
     * @param array  $valids  Array of valid values
     * @param mixed  $default Default to return if parameter isn't set or not valid
     * @return null|mixed
     */
    public function valid($name, $valids, $default = null) {
        
        if(!$this->exists($name)) return $default;
        $cookie = $this->get($name);
        $value = $cookie->getValue();
        if(is_array($value)) return true; // we don't allow arrays
        
        $found = array_search($value, $valids);
        if($found !== false) return $valids[$found]; // return the valid value for type safety
        return $default;
    }

    /**
     * Access a request parameter as bool
     *
     * Note: $nonempty is here for interface consistency and makes not much sense for booleans
     *
     * @param string    $name     Parameter name
     * @param mixed     $default  Default to return if parameter isn't set
     * @param bool      $nonempty Return $default if parameter is set but empty()
     * @return bool
     */
    public function bool($name, $default = false, $nonempty = false) {
        if(!$this->exists($name)) return $default;
        $cookie = $this->get($name);
        $value = $cookie->getValue();
        if(is_array($value)) return true;
        if($value === '') return $default;
        if($nonempty && $cookie->isEmpty()) return $default;
        
        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * Access a request parameter as array
     *
     * @param string    $name     Parameter name
     * @param mixed     $default  Default to return if parameter isn't set
     * @param bool      $nonempty Return $default if parameter is set but empty()
     * @return array
     */
    public function arr($name, $default = array(), $nonempty = false) {
        
        if(!$this->exists($name)) return $default;
        $cookie = $this->get($name);
        if(!is_array($cookie->getValue())) return $default;
        if($nonempty && $cookie->isEmpty()) return $default;

        return (array) $cookie->getValue();
    }
    

    public function getIdentity(){
        return 'COOKIE';
    }
   public function delete($name=null)
   {
        $all = func_num_args()==0;
        $n = $a=array();
        foreach ($this->_cookies as $cookie)
        {
            if($all){
                $cookie->delete();
                $a[] = $cookie; 
                continue;
            }
            if ($name == $cookie->getName()) { 
                $cookie->delete();
                $a[] = $cookie; 
            }else{
                $n[] = $cookie;
            }
        }
        unset($this->_cookies);
        $this->_cookies = $n;
        return $a;
   }

}
class cookie_item{
    
    private $sendable =true;
    private $cookieName = null;
    private $cookieData = null;
    
    private $cookieExpire = 0;
    private $cookiePath = '/';
    private $cookieDomain = null;
    private $cookieSecure = false;
    private $cookieHTTPOnly = false;
    public function __construct($name) {
        //($name, $value, $expiry = self::OneYear, $path = '/', HTTPOnly = false)
        
        $n = func_num_args();
        if($n > 1 && $name===false){
            $this->sendable = false;
            $this->cookieName = func_get_arg(1);
            if($n>2)$this->cookieData = func_get_arg(2);
            $this->sendable = true;
        }else{
            $this->Domain(false);
            if($n>4){
                $this->HTTPOnly(func_get_arg(4));
            }
            if($n>3){
                $this->path(func_get_arg(3));
            }
            if($n>2){
                $this->Expire(func_get_arg(2));
            }
            $this->cookieName = $name;
            if($n>1){
                $this->Value(func_get_arg(1));
            }
        }
    }

    public function __set($name, $value) {
        $name = strtolower($name);
        if($name == 'http_only') $name = 'httponly';
        if($name == 'data') $name = 'value';
        if(method_exists($this,$name)){
            $this->$name($value);
        }
    }
    public function __get($name) {
        switch($name){
            case 'name': return $this->cookieName; break;
            case 'domain': return $this->cookieDomain; break;
            case 'value': return $this->cookieData; break;
            case 'data': return $this->cookieData; break;
            case 'path': return $this->cookiePath; break;
            case 'secure': return $this->cookieSecure; break;
            case 'httponly': return $this->cookieHTTPOnly; break;
            case 'expire': return $this->cookieExpire; break;
        }
    }


    public function __call($name, $arguments) {
        $name = strtolower($name);
        switch($name){
            case 'setname': $this->name($arguments[0]); break;
            case 'setdomain': $this->domain($arguments[0]); break;
            case 'setvalue': $this->value($arguments[0]); break;
            case 'setdata': $this->value($arguments[0]); break;
            case 'setpath': $this->path($arguments[0]); break;
            case 'setsecure': $this->secure($arguments[0]); break;
            case 'sethttponly': $this->httponly($arguments[0]); break;
            case 'setexpire': $this->expire($arguments[0]); break;
            
            case 'getname': return $this->cookieName; break;
            case 'getdomain': return $this->cookieDomain; break;
            case 'getvalue': return $this->cookieData; break;
            case 'getdata': return $this->cookieData; break;
            case 'getpath': return $this->cookiePath; break;
            case 'getsecure': return $this->cookieSecure; break;
            case 'gethttponly': return $this->cookieHTTPOnly; break;
            case 'getexpire': return $this->cookieExpire; break;
        }
        return $this;
    }

    /**
     * Kill a cookie
     * @access public
     * @param string $cookieName to kill
     * @return bool true/false
     */
    public function delete()
    {
        $this->cookieData = null;
        if(empty($this->cookieName)){
            return;
        }
        if(!$this->sendable){
            unset($_COOKIE[$this->cookieName]);
            return ;
        }
       $ret = setcookie(
            $this->cookieName, 
            null, 
            1, 
            $this->cookiePath, 
            $this->cookieDomain
        );
        if ($ret){
            unset($_COOKIE[$this->cookieName]);
        }
        return this;
    }
        /**
     * Set cookie name
     * @access public
     * @param string $name cookie name
     * @return mixed obj or bool false
     */
    public function Name($name=null)
    {
        if($this->cookieName !== null){
            return $this;
        }
        if((null !==$name)){
            $this->cookieName = $name;
            if($this->cookieName && $this->cookieData) $this->send();
        }
        return $this;
    }
    
    /**
     * Set cookie value
     * @access public
     * @param string $value cookie value
     * @return bool whether the string was a string
     */
    public function Value($value=null)
    {
        if((null !==$value)){
            if(is_array($value)){
                $value = serialize($value);
            }
            $this->cookieData = $value;
            unset($data);
        }
        if($this->cookieName && $this->cookieData) $this->send();
        return $this;
    }
    
    /**
     * Set expire time
     * @access public
     * @param string $time +1 week, etc.
     * @return bool whether the string was a string
     */
    public function Expire($time=0)
    {
        $pre = substr($time,0,1);
        if($time ==-1){
            $this->cookieExpire =1893456000; // Lifetime = 2030-01-01 00:00:00
        }elseif(in_array($pre, array('+','-'))){
            $this->cookieExpire = strtotime($time);
            if($this->cookieName && $this->cookieData) $this->send();
        } elseif($time){
            $time = (int)$time;
            if($time > time()){
                $this->cookieExpire = $time;
            }else{
                $this->cookieExpire = time() + $time;
            }
            if($this->cookieName && $this->cookieData) $this->send();
        } else{
            $this->cookieExpire = 0; //session cookie
            if($this->cookieName && $this->cookieData) $this->send();
        }
        return $this;
    }
    
    /**
     * Set path of the cookie
     * @access public
     * @param string $path
     * @return object $this
     */
    public function Path($path='/')
    {
        $this->cookiePath = $path;
        if($this->cookieName && $this->cookieData) $this->send();
        return $this;
    }
    
    /**
     * Set the domain for the cookie
     * @access public
     * @param string $domain
     * @return object $this
     */
    public function Domain($domain=null)
    {
        if ($domain === false){
            if(isset($_SERVER['HTTP_HOST'])){
                $domain = $_SERVER['HTTP_HOST'];
                if ( strtolower( substr($domain, 0, 4) ) == 'www.' ) $domain = substr($domain, 4); 
                // Add the dot prefix to ensure compatibility with subdomains 
                if ( substr($domain, 0, 1) != '.' ) $domain = '.'.$domain; 
            }
        }
        if((null !==$domain)){
            // Fix the domain to accept domains with and without 'www.'. 
            if ( strtolower( substr($domain, 0, 4) ) == 'www.' ) $domain = substr($domain, 4); 
            // Remove port information. 
            $port = strpos($domain, ':'); 
            if ( $port !== false ) $domain = substr($domain, 0, $port); 
            
            $this->cookieDomain = $domain;
            if($this->cookieName && $this->cookieData) $this->send();
        }
        return $this;
    }
    
    /**
     * Whether the cookie is only available under HTTPS
     * @access public
     * @param bool $secure true/false
     * @return object $this
     */
    public function Secure($secure=false)
    {
        $this->cookieSecure = (bool)$secure;
        if($this->cookieName && $this->cookieData) $this->send();
        return $this;
    }
    
    /**
     * HTTPOnly flag, not yet fully supported by all browsers
     * @access public
     * @param bool $httponly yes/no
     * @return object $this
     */
    public function HTTPOnly($httponly=false)
    {
        $this->cookieHTTPOnly = (bool)$httponly;
        if($this->cookieName && $this->cookieData) $this->send();
        return $this;
    }
    /**
     * Create a cookie
     * @access public
     * @return bool true/false
     */
    public function send()
    {
        if(empty($this->cookieName)){
            return false;
        }
        
        if(!$this->sendable){
            $_COOKIE[$this->cookieName] = $this->cookieData;
            return ;
        }
        $ret = setcookie(
            $this->cookieName,
            $this->cookieData, 
            $this->cookieExpire, 
            $this->cookiePath, 
            $this->cookieDomain, 
            $this->cookieSecure, 
            $this->cookieHTTPOnly
        );
        if ($ret){
            $_COOKIE[$this->cookieName] = $this->cookieData;
        }
        return $ret;
    }
    /**
     * Access a request parameter as bool
     *
     * Note: $nonempty is here for interface consistency and makes not much sense for booleans
     *
     * @param string    $name     Parameter name
     * @param mixed     $default  Default to return if parameter isn't set
     * @param bool      $nonempty Return $default if parameter is set but empty()
     * @return bool
     */
    public function bool($default = false, $nonempty = false) {
        $name = $this->change_case($name);
        if($this->cookieData === null) return $default;
        if(is_array($this->cookieData)) return true;
        
        if($this->cookieData === '') return $default;
        if($nonempty && empty($this->cookieData)) return $default;
        
        return filter_var($this->cookieData, FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * Access a request parameter as array
     *
     * @param string    $name     Parameter name
     * @param mixed     $default  Default to return if parameter isn't set
     * @param bool      $nonempty Return $default if parameter is set but empty()
     * @return array
     */
    public function arr($default = array(), $nonempty = false) {
        
        if($this->cookieData === null) return $default;
        if(!is_array($this->cookieData)) return $default;
        if($nonempty && empty($this->cookieData)) return $default;

        return (array) $this->cookieData;
    }
    
    /**
     * Access a request parameter as int
     *
     * @param string    $name     Parameter name
     * @param mixed     $type     Type to cast to
     * @return cast value
     * 
     * int, integer - cast to integer
        bool, boolean - cast to boolean
        float, double, real - cast to float
        string    - cast to string
        array -  cast to array
        object - cast to object
        null,unset - cast to NULL (PHP 5)
     */
    public function cast($type) {
        
        $type = strtolower($type);
        $value =$this->cookieData;
        if($type=='boolean') $type ='bool';
        if($type=='double') $type ='float';
        if($type=='real') $type ='float';
        if($type=='unset') $type ='null';
        
        if($type=='bool' && is_string($value)){
            $value = substr(trim(strtolower($value)),0,4);
            $this->cookieData = in_array($value,array('on','true','yes'));
        }else if(settype($value,$type)){
            $this->cookieData = $value;
        }
        return $this->cookieData;
    }

    /**
     * Access a request parameter as int
     *
     * @param string    $name     Parameter name
     * @param mixed     $default  Default to return if parameter isn't set or is an array
     * @param bool      $nonempty Return $default if parameter is set but empty()
     * @return int
     */
    public function int($default = 0, $nonempty = false) {
        
        if($this->cookieData === null) return $default;
        if(is_array($this->cookieData)) return $default;
        if($this->cookieData === '') return $default;
        if($nonempty && empty($this->cookieData)) return $default;

        return (int) $this->cookieData;
    }

    /**
     * Access a request parameter as string
     *
     * @param string    $name     Parameter name
     * @param mixed     $default  Default to return if parameter isn't set or is an array
     * @param bool      $nonempty Return $default if parameter is set but empty()
     * @return string
     */
    public function str($default = '', $nonempty = false) {
        
        if($this->cookieData === null) return $default;
        if(is_array($this->cookieData)) return $default;
        if($nonempty && empty($this->cookieData)) return $default;

        return (string) $this->cookieData;
    }
    
    function seek($default='') {
        
        if($this->cookieData === null) $this->value($default);
        return $this->cookieData;
    }
    function assert($default) {
        if(  empty($this->cookieData)) $this->value($default);
        return $this->cookieData;
    }
    function exists(){
        return $this->cookieData !== null;
    }
    function isEmpty()
    {
        return empty($this->cookieData);
    }
}