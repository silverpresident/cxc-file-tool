<?php
/**
 * @author Edwards
 * @copyright 2015
 * 
 * this class provides tools for 
 * 
 * http://eddmann.com/posts/securing-sessions-in-php/
 */
namespace ELIX;
//if(!isset($_SESSION))session_start();

class SESSION
{
    static private $instance = array();
    protected $filter;
    protected $class = '';
    
    public function get($class='')
    {
        if(isset($this)){
            $class = "{$this->class}{$class}";
        }
        $item = empty($class)?'*':$class;
        if(!isset(self::$instance[$item])){
            if(!self::is_session_started()){
                session_start();
            }
            $c = __CLASS__;
            self::$instance[$item] = new $c($item);
        }
        return self::$instance[$item];
    }
    public function getSecure($class='',$minutes = 60)
    {
        if(isset($this)){
            $class = "{$this->class}{$class}";
        }
        $item = empty($class)?'*':$class;
        if(!isset(self::$instance[$item])){
            if(!self::is_session_started()){
                session_start();
            }
            $c = __CLASS__;
            $it = new $c($item);
            if($it->vet()) $it->setExpire("+{$minutes} minutes");
            $it->isValid($minutes);
            self::$instance[$item] = $it;
        }
        return self::$instance[$item];
    }
    static public function getSecurity()
    {
        static $c =null;
        if($c === null ){
            $c = new SESSION_SECURITY();
        }
        return $c;
    }
    public function getOneTimeOnly()
    {
        return new SESSION_OTO($this);
    }
    public function session($class='')
    {
        if(isset($this)){
            return $this->get($class);
        }
        return self::get($class);
    }
    function is_session_started()
    {
        if ( php_sapi_name() !== 'cli' ) {
            if ( version_compare(phpversion(), '5.4.0', '>=') ) {
                return session_status() === PHP_SESSION_ACTIVE ? TRUE : FALSE;
            } else {
                return session_id() === '' ? FALSE : TRUE;
            }
        }
        return FALSE;
    }
    
    protected function applyfilter($data){
        if(!$this->filter) return $data;
        return call_user_func($this->filter, $data);
    }
    /**
     * Return a filtered copy of the input object
     *
     * Expects a callable that accepts one string parameter and returns a filtered string
     *
     * @param Callable|string $filter
     * @return Input
     */
    public function filter($filter='stripctl'){
        $this->filter = $filter;
        $clone = clone $this;
        $this->filter = '';
        return $clone;
    }
    
    protected $_case = null;
    public function change_key_case($value = CASE_LOWER) {        
        if(func_num_args()==0) $value = ($this->_case === CASE_UPPER || $this->_case ===null )?CASE_LOWER:CASE_UPPER;
        if($value !== CASE_UPPER && $value !== CASE_LOWER && $value !== NULL) return;
        $this->_case = $value;
        //TODO: should change case of exiting items
        return $this->_case;
    }
    protected  function change_case($value){
        if($this->_case === null) return $value;
        if($this->_case === CASE_UPPER) return strtoupper($value);
        return strtolower($value);
    }
    
    
    public function write($name, $value='') {
        $name = $this->change_case($name);
        $name = "{$this->class}{$name}";
        $_SESSION[$name] = $value;
        return $_SESSION[$name];
    }
    public function read($name, $default=false) {
        $name = $this->change_case($name);
        $name = "{$this->class}{$name}";
        if(!is_array($_SESSION)){
            return $default;
        }
        if(isset($_SESSION[$name]) || @array_key_exists($name,$_SESSION))
            return $this->applyfilter($_SESSION[$name]);
        return $default;
    }
    public function seek($name, $default='') {
        $name = $this->change_case($name);
        $name = "{$this->class}{$name}";
        if(!@array_key_exists($name,$_SESSION)) $_SESSION[$name] = $default;
        return $this->applyfilter($_SESSION[$name]);
    }
    public function assert($name, $default) {
        $name = $this->change_case($name);
        $name = "{$this->class}{$name}";
        if(!isset($_SESSION[$name]) || empty($_SESSION[$name]))
            $_SESSION[$name] = $default;
        return $this->applyfilter($_SESSION[$name]);
    }
    public function exists($name){
        $name = $this->change_case($name);
        $name = "{$this->class}{$name}";
        return @array_key_exists($name,$_SESSION);
    }
    public function isEmpty($name)
    {
        $name = $this->change_case($name);
        $name = "{$this->class}{$name}";
        return empty($_SESSION[$name]);
    }
    public function delete($name)
    {
        $name = $this->change_case($name);
        $name = "{$this->class}{$name}";
        unset($_SESSION[$name]);
    }
    
    
    public function toArray(){
        if($this->class){
            $p = $this->class;
            $l = strlen($p);
            $A = array();
            foreach($_SESSION as $k => $v){
                if(substr($k,0,$l)==$p){
                    $name = $this->change_case(substr($k,$l));
                    $A[$name] = $v;
                }
            }
            return $A;
        }else{
            return $_SESSION;
        }
    }
    public function setClass($itemClass)
    {
        $this->class = $itemClass;
        return $this;
    }
    public function __construct($itemClass='') {
        if(func_num_args()){
            $this->setClass(func_get_arg(0));
        }
        if(!self::is_session_started()){
            session_start();
        }
    }
    public function __call($name, $arguments) {
        $c = __CLASS__;
        error_log("unknown public function $c :: $name");
    }

    public function __toString() {
        if($this->class){
            return print_r($this->toArray(),1);
        }else{
            return print_r($_SESSION,1);
        }
    }
    public function __get($name) {
      return $this->Read($name,null);
    }
    public function __set($name, $value) {
        return $this->write($name,$value);
    }
    public function __unset($name) {
        $name = $this->change_case($name);
        $name = "{$this->class}{$name}";
        unset($_SESSION[$name]);
    }
    public function __isset($name) {
        $name = $this->change_case($name);
        $name = "{$this->class}{$name}";
        return isset($_SESSION[$name]);
    }
    
    
    /**
     * Check if a parameter was set
     *
     * Basically a wrapper around isset. When called on the $post and $get subclasses,
     * the parameter is set to $_POST or $_GET and to $_REQUEST
     *
     * @see isset
     * @param string $name Parameter name
     * @return bool
     */
    public function has($name) {
        $name = $this->change_case($name);
        $name = "{$this->class}{$name}";
        return isset($_SESSION[$name]);
    }
    /**
     * Remove a parameter from the superglobals
     *
     * Basically a wrapper around unset. When NOT called on the $post and $get subclasses,
     * the parameter will also be removed from $_POST or $_GET
     *
     * @see isset
     * @param string $name Parameter name
     * @return bool
     */
    public function remove($name) {
        $this->delete($name);
    }

    /**
     * Access a request parameter without any type conversion
     *
     * @param string    $name     Parameter name
     * @param mixed     $default  Default to return if parameter isn't set
     * @param bool      $nonempty Return $default if parameter is set but empty()
     * @return mixed
     */
    public function param($name, $default = null, $nonempty = false) {
        $name = $this->change_case($name);
        $name = "{$this->class}{$name}";
        if(!isset($_SESSION[$name])) return $default;
        $value = $this->applyfilter($_SESSION[$name]);
        if($nonempty && empty($value)) return $default;
        return $value;
    }
    /**
     * Sets a parameter
     *
     * @param string $name Parameter name
     * @param mixed  $value Value to set
     */
    public function set($name, $value) {
        $this->__set($name, $value);
    }

    /**
     * Get a reference to a request parameter
     *
     * This avoids copying data in memory, when the parameter is not set it will be created
     * and intialized with the given $default value before a reference is returned
     *
     * @param string    $name Parameter name
     * @param mixed     $default If parameter is not set, initialize with this value
     * @param bool      $nonempty Init with $default if parameter is set but empty()
     * @return &mixed
     */
    public function &ref($name, $default = '', $nonempty = false) {
        $name = $this->change_case($name);
        $name = "{$this->class}{$name}";
        if(!isset($_SESSION[$name]) || ($nonempty && empty($_SESSION[$name]))) {
            $this->set($name, $default);
        }

        return $_SESSION[$name];
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
        $name = $this->change_case($name);
        $name = "{$this->class}{$name}";
        $type = strtolower($type);
        $value =$this->read($name,null);
        if($type=='boolean') $type ='bool';
        if($type=='double') $type ='float';
        if($type=='real') $type ='float';
        if($type=='unset') $type ='null';
        
        if($type=='bool' && is_string($value)){
            $value = substr(trim(strtolower($value)),0,4);
            $_SESSION[$name] = in_array($value,array('on','true','yes'));
        }else if(settype($value,$type)){
            $_SESSION[$name] = $value;
        }
        return $this->read($name,null);
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
        $name = $this->change_case($name);
        $name = "{$this->class}{$name}";
        if(!isset($_SESSION[$name])) return $default;
        if(is_array($_SESSION[$name])) return $default;
        $value = $this->applyfilter($_SESSION[$name]);
        if($value === '') return $default;
        if($nonempty && empty($value)) return $default;

        return (int) $value;
    }
    /**
     * Access a request parameter as int, increase it by $step, store it and return the new value
     * Existing parameters will be forced to integer
     *
     * @param string    $name     Parameter name
     * @param int       $step     value to increase by 
     * @return int
     */
    public function increment($name, $step = 1) {
        $name = $this->change_case($name);
        $name = "{$this->class}{$name}";
        if(isset($_SESSION[$name])){
            if(is_scalar($_SESSION[$name])){
                $value = (int)$this->applyfilter($_SESSION[$name]);
            }else{
                $value = 0;
            }
        }else{
            $value = 0;
        }
        $value += $step;
        $_SESSION[$name] = $value;
        return $value;
    }
    /**
     * Access a request parameter as int, decrease it by $step, store it and return the new value
     * Existing parameters will be forced to integer
     *
     * @param string    $name     Parameter name
     * @param int       $step     value to decrease by
     * @return int
     */
    public function decrement($name, $step=1) {
        $name = $this->change_case($name);
        $name = "{$this->class}{$name}";
        if(isset($_SESSION[$name])){
            if(is_scalar($_SESSION[$name])){
                $value = (int)$this->applyfilter($_SESSION[$name]);
            }else{
                $value = 0;
            }
        }else{
            $value = 0;
        }
        
        $value -= $step;
        $_SESSION[$name] = $value;
        return $value;
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
        $name = $this->change_case($name);
        $name = "{$this->class}{$name}";
        if(!isset($_SESSION[$name])) return $default;
        if(is_array($_SESSION[$name])) return $default;
        $value = $this->applyfilter($_SESSION[$name]);
        if($nonempty && empty($value)) return $default;

        return (string) $value;
    }

    /**
     * Access a request parameter and make sure it is has a valid value
     *
     * Please note that comparisons to the valid values are not done typesafe (request vars
     * are always strings) however the public function will return the correct type from the $valids
     * array when an match was found.
     *
     * @param string $name    Parameter name
     * @param array  $valids  Array of valid values
     * @param mixed  $default Default to return if parameter isn't set or not valid
     * @return null|mixed
     */
    public function valid($name, $valids, $default = null) {
        $name = $this->change_case($name);
        $name = "{$this->class}{$name}";
        if(!isset($_SESSION[$name])) return $default;
        if(is_array($_SESSION[$name])) return $default; // we don't allow arrays
        $value = $this->applyfilter($_SESSION[$name]);
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
        $name = $this->change_case($name);
        $name = "{$this->class}{$name}";
        if(!isset($_SESSION[$name])) return $default;
        if(is_array($_SESSION[$name])) return true;
        $value = $this->applyfilter($_SESSION[$name]);
        if($value === '') return $default;
        if($nonempty && empty($value)) return $default;
        
        return filter_var($valToCheck, FILTER_VALIDATE_BOOLEAN);

        return (bool) $value;
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
        $name = $this->change_case($name);
        $name = "{$this->class}{$name}";
        if(!isset($_SESSION[$name])) return $default;
        if(!is_array($_SESSION[$name])) return $default;
        if($nonempty && empty($_SESSION[$name])) return $default;

        return (array) $_SESSION[$name];
    }
    
    public function getIdentity(){
        return 'SESSION';
    }
    
    public function destroy()
    {
        if(isset($_ENV['DEBUG']))
        {
            $c = (isset($this) && $this->class)?"[{$this->class}]":'ALL';
            error_log("called ELIX::SESSION->destroy() $c");
        }
        if(isset($this) && $this->class){
            $l = strlen($this->class);
            foreach($_SESSION as $k => $v) {
                if(substr($k,0,$l)==$this->class)
                    unset($_SESSION[$k]);
            }
        }else{
            session_unset();
            session_destroy();
            $_SESSION = array();
            session_write_close();
            setcookie(session_name(),'',0,'/');
            session_regenerate_id(true);
            session_start();
        }
        return $this;
    }
    public function vet(){
        if(!$this->exists('_expire')) return true;
        if(!$this->_expire) return true;
        if($this->_expire ==-1 ){
            if($this->_session != session_id()){
                
                if(isset($_ENV['DEBUG']))
                {
                    $c = (isset($this) && $this->class)?"[{$this->class}]":'ALL';
                    $sid = session_id();
                    error_log("called ELIX::SESSION->vet() []  +++ session_id did not match ({$this->_session} ! {$sid})");
                }
                $this->destroy();
                return false;
            }
            return true;
        }
        if($this->_expire < time()){
            if(isset($_ENV['DEBUG']))
                {
                    $c = (isset($this) && $this->class)?"[{$this->class}]":'ALL';
                    $sid = time();
                    error_log("called ELIX::SESSION->vet() []  +++ life has expired ({$this->_expire} ! {$sid})");
                }
            $this->destroy();
            return false;
        }
        return true;
    }
    public function setExpire($expire){
        $dt=false;
        if($expire ==0 ){ //forever
            $this->_expire = 0;
            $this->_session = null;
            return $this;
        }
        if($expire == -1 ){ //browser session
            $this->_expire = -1;
            $this->_session = session_id();
            //todo: improve to use a cookie set value SO this only last until browser is closed
            return $this;
        }
        if(is_numeric($expire)){
            if($expire > time()){
                $this->_expire = $expire;
                return $this;
            }
            $dt = date_create("@{$expire}");
        }else{
            $expire = strtolower($expire);
            if(in_array($expire,array('hour','day','week','month','year'))){
                $expire = "+1 $expire";
            }
            
            $dt = date_create($expire);
        }
        if($dt===false)$dt = date_create("+1 day");
        if($dt->format('U') > time()){
            $this->_expire = $dt->format('U');
        }else{
            $this->_expire = null;
        }
        return $this;
    }
    public function isExpired($ttl = 30)
    {
        $name = '_last_activity';
        $name = "{$this->class}{$name}";
        $activity = isset($_SESSION[$name])
            ? $_SESSION[$name]
            : false;
    
        if ($activity !== false && time() - $activity > $ttl * 60) {
            return true;
        }
    
        $_SESSION[$name] = time();
    
        return false;
    }
    
    public function isFingerprint()
    {
        $name = '_fingerprint';
        $name = "{$this->class}{$name}";
        $remoteAddress=(isset($_SERVER['REMOTE_ADDR'])) ? $_SERVER['REMOTE_ADDR'] : FALSE ;
        $ua=(isset($_SERVER['HTTP_USER_AGENT'])) ? $_SERVER['HTTP_USER_AGENT'] : 'NUA' ;
        
        $hash = md5(
            $ua .
            (inet_ntop(inet_pton($remoteAddress)) & inet_ntop(inet_pton('255.255.0.0')))
        );
    
        if (isset($_SESSION[$name])) {
            return $_SESSION[$name] === $hash;
        }
    
        $_SESSION[$name] = $hash;
    
        return true;
    }
    
    public function isValid($ttl = 30)
    {
        return ! $this->isExpired($ttl) && $this->isFingerprint();
    }
}
class SESSION_SECURITY{
    static public function regenerate_id($delete_old=true){
        @session_write_close();
        @session_regenerate_id($delete_old);
        @session_start();
        return $this;
    }
    public function refresh()
    {
        return session_regenerate_id(true);
    }
    static public function setDomain($domain='/'){
        @setcookie(session_name(),'',0,$domain);
        return $this;
    }
    static public function setExpire($minutes=180){
        $minutes = (int)$minutes;
        session_cache_expire($minutes);
        return $this;
    }
    static public function setPrivate($private='private'){
        if(is_bool($private)) $private = $private?'private':'public';
        @session_cache_limiter($private);
        return $this;
    }
    //nocache, private, private_no_expire, or public
    static public function setLimiter($limiter='private'){
        @session_cache_limiter($limiter);
        return $this;
    }
    static public function close(){
        @session_write_close();
        return $this;
    }
    static public function start(){
        @session_start();
        return $this;
    }
    static public function abort(){
        if(function_exists('session_abort'))
            @session_abort ();
        else{
            if(function_exists('session_reset')){
                @session_reset ();
                $this->close();
            }
        }
        return $this;
    }
    static public function reset(){
        @session_reset();
        return $this;
    }
    public function setSessionId($session_id)
    {
        //return preg_match('/^[-,a-zA-Z0-9]{1,128}$/', $session_id) > 0;
        @session_id($session_id);
        return $this;
        
    }
    public function setSavePath($path){
        @session_save_path($path);
        return $this;
        
    }
    public function resetExpire($minutes=60){
        $lifetime = $minutes * 60;
        @setcookie(session_name(),session_id(),time()+$lifetime);
    }
    public function getFingerprint()
    {
        $remoteAddress=(isset($_SERVER['REMOTE_ADDR'])) ? $_SERVER['REMOTE_ADDR'] : FALSE ;
        $ua=(isset($_SERVER['HTTP_USER_AGENT'])) ? $_SERVER['HTTP_USER_AGENT'] : 'NUA' ;
        return md5(
            $ua .
            (inet_ntop(inet_pton($remoteAddress)) & inet_ntop(inet_pton('255.255.0.0')))
        );
    }
    
    /*static public function setCookie($key='ELIX_SESS'){
        $value = $ts .'.' . $this->getFingerprint().'.'. $ts;
        @setcookie($key,$value,0);
        $_COOKIE[$key] = $value;
        return $value;
    }*/
    
}


class SESSION_OTO
{
    protected $parent;
    public function __construct($parent) {
        $this->parent = $parent;
    }

    static private function read()
    {
        $x = $this->parent->read('__oto__');
        if(!is_array($x)) $x = array();
        return $x;
    }
    static private function write( $value=null)
    {
        if(!is_array($value)) $value = array($value);
        return $this->parent->write('__oto__',$value);
    } 
    public function consume($key='')
    {
        $x = self::Read();
        $i = array_search($key, $x);
        if($i!==false)
        {
            unset($x[$i]);
            self::write($x);
            return true;
        }
        return false;
    }
    public function valid($key)
    {
        $x = self::read();
        return in_array($key, $x);
    }
    public function register($key)
    {
        $x = self::read();
        
        if(!in_array($key, $x))
        {
            $x[] = $key;
            self::write($x);
            return true;
        }
        return false;
    }
    public function make($salt)
    {
        $x = self::read();
        $n = count($x);
        $salt = implode('-',func_get_args());
        $i =0;
        do
        {
            $key = $n.md5($salt. time().rand(0,100)).$i++;
        }while(in_array($key, $x)) ;
        
        $x[] = $key;
        self::write($x);
        return $key;
    }
}