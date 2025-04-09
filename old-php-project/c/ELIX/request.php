<?php
/**
 * @author Edwards
 * @copyright 2015
 * @used  Andreas Gohr <andi@splitbrain.org> INPUT class for dokuWiki
 *
 * this class provides tools for  the  request
 *
 */
namespace ELIX;
class REQUEST{
    static function is_cli(){
        return (PHP_SAPI === 'cli');
    }
    static function isRefresh(){
        return self::isSoftRefresh() || self::isHardRefresh();
    }
    static function isSecureRequest()
	{
        static $r = null;
        if($r===null)
        {
            $https = filter_input(INPUT_SERVER, 'HTTPS');
            $port = filter_input(INPUT_SERVER, 'SERVER_PORT', FILTER_VALIDATE_INT);
            $REQUEST_SCHEME = filter_input(INPUT_SERVER, 'REQUEST_SCHEME');
            $HTTP_X_FORWARDED_PROTO = filter_input(INPUT_SERVER, 'HTTP_X_FORWARDED_PROTO');
            $HTTP_X_FORWARDED_SSL = filter_input(INPUT_SERVER, 'HTTP_X_FORWARDED_SSL');
            $SCRIPT_URI = filter_input(INPUT_SERVER, 'SCRIPT_URI');

            if (($https === null) && ($REQUEST_SCHEME === null)){
                $https = getenv('HTTPS');
                $REQUEST_SCHEME = getenv('REQUEST_SCHEME');
                $HTTP_X_FORWARDED_PROTO = getenv('HTTP_X_FORWARDED_PROTO');
                $HTTP_X_FORWARDED_SSL = getenv('HTTP_X_FORWARDED_SSL');
                $SCRIPT_URI = getenv('SCRIPT_URI');
            }
            $r = false;
            if(!empty($https) && ('off' !== $https)){
                $r = true;
            }
            if(443 === $port){
                $r = true;
            }
            if('on' === $HTTP_X_FORWARDED_SSL){
                $r = true;
            }
            if('https' === $HTTP_X_FORWARDED_PROTO){
                $r = true;
            }
            if('https' === $REQUEST_SCHEME){
                $r = true;
            }
            if('https:' === substr($SCRIPT_URI,0,6)){
                $r = true;
            }

        }
        return $r;
	}
    static function isSoftRefresh(){
        static $r = null;
        if($r===null)
        {
            $r =  isset($_SERVER['HTTP_CACHE_CONTROL']) && strtolower($_SERVER['HTTP_CACHE_CONTROL']) == 'max-age=0';
        }
        return $r;
    }
    static function isHardRefresh(){
        static $r = null;
        if($r===null)
        {
            $r =  isset($_SERVER['HTTP_CACHE_CONTROL']) && strtolower($_SERVER['HTTP_CACHE_CONTROL']) == 'no-cache';
        }
        return $r;
    }

    static function isXmlHttpRequest()
    {
        static $r = null;
        if($r===null)
        {
            $r =  isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
        }
        return $r;
    }
    function is_XHR(){
        return self::isXmlHttpRequest();
    }
    static function is_SOAP()
    {
        static $r = null;
        if($r===null)
        {
            if(isset($_SERVER['HTTP_SOAPACTION'])){
                $r = true;
            }else{
                $r =  isset($_SERVER['CONTENT_TYPE']) && strtolower($_SERVER['CONTENT_TYPE']) == 'application/soap+xml';
            }
        }
        return $r;

    }

    static function getByMethod()
    {
        $m = isset($_SERVER["REQUEST_METHOD"])?$_SERVER["REQUEST_METHOD"]:'';
        if($m == '') $m = count($_POST)?'POST':'GET';
        if($m == 'METHOD') return self::get();
        if($m == 'POST') return self::post();
        if(method_exists(__CLASS__,$m)){
            return self::$m();
        }
        return self::get();
    }
    static function post()
    {
        static $a = null;
        if($a === null) $a = new REQUEST_post;
        return $a;
    }
    static function get()
    {
        static $a = null;
        if($a === null) $a = new REQUEST_get;
        return $a;
    }
    static function cookie()
    {
        static $a = null;
        if($a === null) $a = new REQUEST_cookie;
        return $a;
    }
    static function input()
    {
        static $a = null;
        if($a === null) $a = new REQUEST_input;
        return $a;
    }
    static function request()
    {
        static $a = null;
        if($a === null) $a = new REQUEST_request;
        return $a;
    }
    static function refget()
    {
        static $a = null;
        if($a === null) $a = new REQUEST_refget;
        return $a;
    }
    static function data()
    {
        static $a = null;
        if($a === null) $a = new REQUEST_data;
        return $a;
    }
    static function arguments()
    {
        static $a = null;
        if($a === null) $a = new REQUEST_cli_args;
        return $a;
    }
    static function env()
    {
        static $a = null;
        if($a === null) $a = new REQUEST_env;
        return $a;
    }
    static function headers()
    {
        static $a = null;
        if($a === null) $a = new REQUEST_headers;
        return $a;
    }
    static function server()
    {
        static $a = null;
        if($a === null) $a = new REQUEST_server;
        return $a;
    }
    /*static function host()
    {
        static $a = null;
        if($a === null) $a = new REQUEST_host;
        return $a;
    }*/
    static function session()
    {
        return ELIX::session();
    }
    static function files()
    {
        static $a = null;
        if($a === null) $a = new REQUEST_files;
        return $a;
    }
    static function path()
    {
        if(func_get_args()){
            return new REQUEST_path(func_get_arg(0));
        }
        static $a = null;
        if($a === null) $a = new REQUEST_path;
        return $a;
    }
    static function getOriginalPath()
    {
        $a = new REQUEST_path;
        return $a;
    }

    static function sanitize($array)
    {
        if((null ===$array) || !is_array($array))
            return array();
        if(!get_magic_quotes_gpc()) return $array;
        foreach($array as $k => $v)
        {
            if(is_array($v))
                $array[$k] = self::sanitize($v);
            else
            {
                if(get_magic_quotes_gpc())
                    $array[$k] = stripslashes($v);
            }
        }
        return $array;
    }

}
class REQUEST_trait{
    function is_XHR(){
        //DEpreCATE
        return REQUEST::isXmlHttpRequest();
    }
    function hasFile(){
        if(count($_FILES)){
            $F = REQUEST::files();
            foreach($F as $FL){
                if($FL->isValid()) return true;
            }
        }
        return false;
    }
    public function post(){
        return REQUEST::post();
    }
    public function get(){
        return REQUEST::get();
    }
    public function headers(){
        return REQUEST::headers();
    }
    public function files(){
        return REQUEST::files();
    }
    public function getFiltered(array $definition){
        //http://php.net/manual/en/function.filter-var-array.php
        $a =$this->toArray();
        $a = filter_var_array ( $a,$definition );
        return new REQUEST_base($a);
    }
}
class REQUEST_base extends REQUEST_trait implements \Countable{
    protected $identity=null;
    protected $data=array();

    /**
     * @var Callable
     */
    protected $filter;
/**
     * Apply the set filter to the given value
     *
     * @param string $data
     * @return string
     */
    public function __construct() {
        if(func_num_args()){
            $a = func_get_arg(0);
            if(is_array($a)) $this->data = $a;
            if(func_num_args()>1){
                $a = func_get_arg(1);
                if(is_string($a)) $this->identity = $a;
            }
        }
        $this->data = array_change_key_case($this->data, CASE_LOWER);
		/*foreach($this->data as $k=>$d){
			if(strpos($k,'-')!==false){
				$nk = str_replace('-','_',$k);
				if(!isset($this->data[$nk]))
					$this->data[$nk] =& $this->data[$k];
			}
		}*/
    }
    public function __destruct() {
        unset($this->data);
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
    public function has($name=null) {
        if(func_num_args() == 0) return !!count($this->data);
        $name = $this->change_case($name);
        return isset($this->data[$name]);
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
        if(!isset($this->data[$name])) return $default;
        $value = $this->applyfilter($this->data[$name]);
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
        if(!isset($this->data[$name]) || ($nonempty && empty($this->data[$name]))) {
            $this->set($name, $default);
        }

        return $this->data[$name];
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
        $type = strtolower($type);
        $value =$this->read($name,null);
        if($type=='boolean') $type ='bool';
        if($type=='double') $type ='float';
        if($type=='real') $type ='float';
        if($type=='unset') $type ='null';

        if($type=='bool' && is_string($value)){
            $value = substr(trim(strtolower($value)),0,4);
            $this->data[$name] = in_array($value,array('on','true','yes'));
        }else if(settype($value,$type)){
            $this->data[$name] = $value;
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
        if(!isset($this->data[$name])) return $default;
        if(is_array($this->data[$name])) return $default;
        $value = $this->applyfilter($this->data[$name]);
        if($value === '') return $default;
        if($nonempty && empty($value)) return $default;

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
        $name = $this->change_case($name);
        if(!isset($this->data[$name])) return $default;
        if(is_array($this->data[$name])) return $default;
        $value = $this->applyfilter($this->data[$name]);
        if($nonempty && empty($value)) return $default;

        return (string) $value;
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
        if(!isset($this->data[$name])) return $default;
        if(is_array($this->data[$name])) return true;
        $value = $this->applyfilter($this->data[$name]);
        if($value === '') return $default;
        if($nonempty && empty($value)) return $default;

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
        $name = $this->change_case($name);
        if(!isset($this->data[$name])) return $default;
        if(!is_array($this->data[$name])) return $default;
        if($nonempty && empty($this->data[$name])) return $default;

        return (array) $this->data[$name];
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
        $name = $this->change_case($name);
        if(!isset($this->data[$name])) return $default;
        if(is_array($this->data[$name])) return $default; // we don't allow arrays
        $value = $this->applyfilter($this->data[$name]);
        $found = array_search($value, $valids);
        if($found !== false) return $valids[$found]; // return the valid value for type safety
        return $default;
    }
    function getIdentity(){
        return $this->identity;
    }
    function getQueryString(){
        $a = $this->data;
        ksort($a);
        return http_build_query($a);
    }
    function getQueryStringRaw(){
        return http_build_query($this->data);
    }
    function isXHR()
    {//compat
        return REQUEST::isXmlHttpRequest();
    }

    function read($name, $default=false) {
        $name = $this->change_case($name);
        if(isset($this->data[$name])||array_key_exists($name,$this->data))
            return $this->applyfilter($this->data[$name]);

        return $default;
    }
    function seek($name, $default='') {
        $name = $this->change_case($name);
        if(!array_key_exists($name,$this->data)) $this->data[$name] = $default;
        return $this->applyfilter($this->data[$name]);
    }
    function assert($name, $default) {
        $name =$this->change_case($name);
        if(!isset($this->data[$name]) || empty($this->data[$name]))
            $this->data[$name] = $default;
        return $this->applyfilter($this->data[$name]);
    }
    function exists($name){
        $name = $this->change_case($name);
        return array_key_exists($name,$this->data);
    }
    function isEmpty($name=null)
    {
        if(func_num_args() == 0) return !count($this->data);
        $name = $this->change_case($name);
        return empty($this->data[$name]);
    }
    function delete($name,$index=null)
    {
        if(func_num_args()==2 && !is_null($index)){
            if(is_array($this->data[$name])){
                if(is_Array($index))
                    foreach($index as $i){unset($this->data[$name][$i]);}
                else
                    unset($this->data[$name][$index]);
            }
        }else{
            unset($this->data[$name]);
        }
    }
    function toArray(){
        if($this->_case === null)
            $a = array_change_key_case($this->data, CASE_LOWER);
        else
            $a = $this->data;
        if(func_num_args()){
            $f = func_get_arg(0);
            if(is_array($f)){
                foreach($f as &$fvalue)
                    $fvalue = strtolower($fvalue);

                foreach($a as $k=>$v){
                    $name = strtolower($k);
                    $nk = str_replace('_','-',$name);
                    if(!in_array($name,$f) && !in_array($nk,$f)) unset($a[$k]);
                }
            }
        }
        if($this->filter){
            foreach($a as $k=>$v){
                $a[$k] = $this->applyfilter($v);
            }
        }

        return $a;
    }
    public function __get($name) {
        $name = $this->change_case($name);
        $nk = str_replace('_','-',$name);
        if(isset($this->data[$name])){
            return $this->applyfilter($this->data[$name]);
        }elseif(isset($this->data[$nk])){
            return $this->applyfilter($this->data[$nk]);
        }else
            return '';
    }
    public function __set($name, $value) {
        $name = $this->change_case($name);
        if($value === null)
            $this->__unset($name);
        else{
            if(isset($this->data[$name])){
                $this->data[$name] = $value;
            }else{
                $nk = str_replace('_','-',$name);
                if(isset($this->data[$nk])){
                    $this->data[$nk] = $value;
                }else{
                    $this->data[$name] = $value;
                }
            }
        }
    }
    public function __unset($name) {
        $name = $this->change_case($name);
        if(strpos($name,'_')!==false){
			$nk = str_replace('_','-',$name);
			unset($this->data[$nk]);
		}
        unset($this->data[$name]);
    }
    public function __isset($name) {
        $name = $this->change_case($name);
        if (isset($this->data[$name])) return true;
		$nk = str_replace('_','-',$name);
		return isset($this->data[$nk]);
    }
    public function count() {
        return count($this->data);
    }
    protected $_case = null;
    public function change_key_case($value = CASE_LOWER) {
        if(func_num_args()==0) $value = ($this->_case === CASE_UPPER || $this->_case ===null )?CASE_LOWER:CASE_UPPER;
        if($value !== CASE_UPPER && $value !== CASE_LOWER) return;
        $this->_case = $value;
        $this->data = array_change_key_case($this->data, $value);
        return $this->_case;
    }
    protected function change_case($value){
        if($this->_case === CASE_UPPER) return strtoupper($value);
        return strtolower($value);
    }
}
class REQUEST_post extends REQUEST_base{
    static private $sanitized = false;
    public function __construct() {
        if(!self::$sanitized){
            $_POST = REQUEST::sanitize($_POST);
            self::$sanitized = true;
        }
        $this->data = &$_POST;
        $this->identity = 'POST';
        parent::__construct();
    }
}
class REQUEST_get extends REQUEST_base{
    public function __construct() {
        $this->data = &$_GET;
        $this->identity = 'GET';
        parent::__construct();
    }
}
class REQUEST_data extends REQUEST_base{
    public function __construct() {
        $A = array();
        if(REQUEST::is_CLI()){
            parse_str(implode('&', array_slice($argv, 1)), $A);
        }else{
            foreach($_GET as $k=>$v){
                $A[$k] =& $_GET[$k];
            }
            REQUEST::post();
            foreach($_POST as $k=>$v){
                $A[$k] =& $_POST[$k];
            }
        }
        $this->data = &$A;
        $this->identity = 'GETPOST';
        parent::__construct();
    }
}
class REQUEST_input extends REQUEST_base{
    public function __construct() {
        $_INPUT = array();
        $inputdata =@file_get_contents("php://input");
        $_INPUT = parse_query_string($inputdata);
        $this->data = $_INPUT;
        $this->identity = 'INPUT';
        parent::__construct();
    }
}
class REQUEST_refget extends REQUEST_base{
    public function __construct() {
        $r = isset($_SERVER["HTTP_REFERER"])? $_SERVER["HTTP_REFERER"]:'';
        $A = explode('?',$r);
        $r = (!empty($A[1]))? $A[1]:'';
        $A = array();
        parse_str($r,$A);
        $this->data = $A;
        $this->identity = 'REFGET';
        parent::__construct();
    }
}
class REQUEST_env extends REQUEST_base{
    public function __construct() {
        $A = $_ENV;
        $this->data = $A;
        $this->identity = 'ENV';
        parent::__construct();
    }
}
class REQUEST_server extends REQUEST_base{
    public function __construct() {
        $A = $_SERVER;
        if(!isset($A['REQUEST_TIME'])) $A['REQUEST_TIME'] = time();
        $this->data = $A;
        $this->identity = 'SERVER';
        parent::__construct();
    }
    function getRemote(){
        $A = array();
        foreach($this->data as $key=>$value){
            if(substr($key,0,7)=='remote_'){
                $A[substr($key,7)] =& $this->data[$key];
            }
        }
        $clo = new REQUEST_base($A,'REMOTE');
        return $clo;
    }
    function getHost(){
        $A = array();
        foreach($this->data as $key=>$value){
            if(substr($key,0,7)=='server_'){
                $A[substr($key,7)] =& $this->data[$key];
            }
        }
        $clo = new REQUEST_base($A,'HOST');
        return $clo;
    }
}
/*class REQUEST_host extends REQUEST_base{
    public function __construct() {
        $p = 'SERVER_';
        $l = strlen($p);
        $A = array();
        foreach($_SERVER as $key => $v){
            if(substr($key,0,$l)==$p){
                $A[substr($key,$l)] = $_SERVER[$key];
            }
        }
        $this->data = $A;
        $this->identity = 'HOST';
        parent::__construct();
    }
    implement
    ->name
    ->addr
}*/
class REQUEST_headers extends REQUEST_base{
    public function __construct() {
        if(function_exists('apache_request_headers')){
            $A = apache_request_headers();
        }else{
            $A= FALSE;
        }
        if($A === false){

            $A = array();
            foreach($_SERVER as $K=>$V){
                $a=explode('_' ,$K);
                if(array_shift($a)=='HTTP'){
                     array_walk($a,function(&$v){$v=ucfirst(strtolower($v));});
                 $A[join('-',$a)]=$V;}
            }
            if(!isset($A['Content-Type']))
            $A['Content-Type'] = (isset($_SERVER['CONTENT_TYPE']))?$_SERVER['CONTENT_TYPE']:@getenv('CONTENT_TYPE');
            if(isset($_SERVER['CONTENT_LENGTH'])) $A['Content-Length'] = $_SERVER['CONTENT_LENGTH'];
        }
        $this->data = $A;
        $this->identity = 'HEADERS';
        parent::__construct();
    }
    function getExtended(){
        $A = array();
        foreach($this->data as $key=>$value){
            if(substr($key,0,2)=='x-'){
                $A[substr($key,2)] =& $this->data[$key];
            }
        }
        $clo = new REQUEST_base($A,'EXTENDED');
        return $clo;
    }
    /*HTTP_ACCEPT_LANGUAGE:en-US;q=0.6
Array
(
    [0] => en-US;q=0.6
    [primarytag] => en
    [1] => en
    [subtag] => US
    [2] => US
    [quantifier] => 0.6
    [3] => 0.6
)
*/
    function getAcceptLanguage(){
        $r = array();
        $queue = new rPriorityQueue();
        $pattern = '/^(?P<primarytag>[a-zA-Z]{2,8})'.
            '(?:-(?P<subtag>[a-zA-Z]{2,8}))?(?:(?:;q=)'.
            '(?P<quantifier>\d\.\d))?$/';

        foreach (explode(',', $_SERVER['HTTP_ACCEPT_LANGUAGE']) as $lang) {
            $splits = array();
            if (preg_match($pattern, $lang, $splits)) {
                $queue->insert($split, isset($split['quantifier']) ? (float)$split['quantifier'] : 1.0);
            }
        }
        foreach ($queue as $mime) {
            $r[] = $mime;
        }
        return $r;
    }
    function getAccept(){
        $r = array();
        $queue = new rPriorityQueue();
        foreach (preg_split('#,\s*#', $_SERVER['HTTP_ACCEPT']) as $accept) {
            $split = preg_split('#;\s*q=#', $accept, 2);
            $queue->insert($split[0], isset($split[1]) ? (float)$split[1] : 1.0);
        }
        foreach ($queue as $mime) {
            $r[] = $mime;
        }
        return $r;
    }
    public function getBasicAuth()
	{
		if(isset($_SERVER["PHP_AUTH_USER"]) && isset($_SERVER["PHP_AUTH_PW"])) {
			$auth = array();
			$auth["username"] = $_SERVER["PHP_AUTH_USER"];
			$auth["password"] = $_SERVER["PHP_AUTH_PW"];
			return $auth;
		}

		return null;
	}
    /**
	 * Gets auth info accepted by the browser/client from $$_SERVER['PHP_AUTH_DIGEST']
	 */
	public function getDigestAuth()
	{
		$auth =array();

		if(isset($_SERVER["PHP_AUTH_DIGEST"])) {
		  $digest = $_SERVER["PHP_AUTH_DIGEST"];
			$matches = array();
			if( !preg_match_all("#(\\w+)=(['\"]?)([^'\" ,]+)\\2#", $digest, $matches, 2) ){
				return $auth;
			}
			if(is_array($matches)){
				foreach($matches as $match) {
					$auth[$match[1]] = $match[3];
				}
			}
		}

		return $auth;
	}

    public function auth_type() {
        $m = @getenv('AUTH_TYPE');
        if($m) return strtoupper($m);
        if(isset($_SERVER['AUTH_TYPE'])) return strtoupper($_SERVER['AUTH_TYPE']);
        if(isset($_ENV['AUTH_TYPE'])) return strtoupper($_ENV['AUTH_TYPE']);
        return '';
    }
    function getCharset(){
        $r = array();
        $queue = new rPriorityQueue();
        foreach (preg_split('#,\s*#', $_SERVER['HTTP_ACCEPT_CHARSET']) as $accept) {
            $split = preg_split('#;\s*q=#', $accept, 2);
            $queue->insert($split[0], isset($split[1]) ? (float)$split[1] : 1.0);
        }
        foreach ($queue as $mime) {
            $r[] = $mime;
        }
        return $r;
    }
    /**
	 * Gets most possible client IPv4 Address. This method search in _SERVER['REMOTE_ADDR'] and optionally in _SERVER['HTTP_X_FORWARDED_FOR']
	 */
	public function getClientAddress($trustForwardedHeader = true)
	{
		$address = null;
		/**
		 * Proxies uses this IP
		 */
        if ($trustForwardedHeader) {
            $address = isset($_SERVER["HTTP_X_FORWARDED_FOR"])?$_SERVER["HTTP_X_FORWARDED_FOR"]:null;
            if ($address === null) {
                $address = isset($_SERVER["HTTP_CLIENT_IP"])?$_SERVER["HTTP_CLIENT_IP"]:null;
            }
        }
        if ($address === null) {
            $address = isset($_SERVER["REMOTE_ADDR"])?$_SERVER["REMOTE_ADDR"]:null;
        }

        if(is_string($address)){
            if(strpos($address,',')){
                /**
				 * The client address has multiples parts, only return the first part
				 */
                 $e = explode(",", $address);
				return $e[0];
            }
            return $address;
        }
		return false;
	}
    /**
	 * Gets best mime/type accepted by the browser/client from _SERVER["HTTP_ACCEPT"]
	 */
	public function getBestAccept()
	{
		$c = $this->getAccept();
        if(count($c)){
            return $c[0];
        }
        return '';
	}
	public function getBestAcceptLanguage()
	{
		$c = $this->getAcceptLanguage();
        if(count($c)){
            return $c[0];
        }
        return '';
	}
	public function getBestCharset()
	{
		$c = $this->getCharset();
        if(count($c)){
            return $c[0];
        }
        return '';
	}
}
class REQUEST_cli_args extends REQUEST_base{
    public function __construct() {
        $this->data = $this->arguments();
        $this->identity = 'CLI';
        parent::__construct();
    }
    function arguments() {
        $_ARG = array();
        foreach ($argv as $arg) {
            if (preg_match('#^-{1,2}([a-zA-Z0-9]*)=?(.*)$#', $arg, $matches)) {
                $key = $matches[1];
                switch ($matches[2]) {
                    case '':
                    case 'true':
                    $arg = true;
                    break;
                    case 'false':
                    $arg = false;
                    break;
                    default:
                    $arg = $matches[2];
                }

                /* make unix like -afd == -a -f -d */
                if(preg_match("/^-([a-zA-Z0-9]+)/", $matches[0], $match)) {
                    $string = $match[1];
                    for($i=0; strlen($string) > $i; $i++) {
                        $_ARG[$string[$i]] = true;
                    }
                } else {
                    $_ARG[$key] = $arg;
                }
            } else {
                $_ARG['input'][] = $arg;
            }
        }
        return $_ARG;
    }
}
class REQUEST_cookie extends REQUEST_base{
    public function __construct() {
        $A = $_COOKIE;
        $this->data = $A;
        $this->identity = 'COOKIE';
        parent::__construct();
    }
}


class REQUEST_uploadedfile{
    private $data = array();
    public function __get($name) {
        $name = strtolower($name);
        if(array_key_exists($name,$this->data))
            return $this->data[$name];
        else if(method_exists($this,$name) && $name!='save')
            return $this->$name();
        else
            return '';
    }
    public function __set($name, $value) {
        $name = strtolower($name);
        $this->data[$name] = $value;
    }
    public function __isset($name) {
        $name = strtolower($name);
        return isset($this->data[$name]);
    }
    public function __toString() {
        return "UPLOADEDFILE ( {$this->_post_key()} )";
    }
    function toArray(){
        return $this->data;
    }
    public function __construct($f ) {
		$this->data = $f;
        return $this;
	}

	function isUploaded(){
		return $this->error != UPLOAD_ERR_NO_FILE;
	}
	function hasError(){
		return $this->isUploaded() && $this->error != UPLOAD_ERR_OK;
	}

	function isValid(){
		return !$this->hasError();
	}
    function getMimeType(){
        return strtolower($this->mime);
    }
    function sanitizedName(){
        $x  = explode('.',$this->name);
        if(count($x)>2){
            $ext = array_pop($x);
            $s = implode('_',$x) . ".{$ext}";
        }else{
            $s = $this->name;
        }
        $s = preg_replace(
                     array("/\s+/", "/[^-\.\w]+/",'/__/','/--/','/_-/','/-_/'),
                     array("_", "",'_','-','-','-'),
                     trim($s));

        $this->data['sanitizedname'] = $s;
		return $this->data['sanitizedname'];
	}
    function mime(){
        if(isset($this->data['mime'])) return $this->data['mime'];
        if(isset($this->data['type'])) return $this->data['type'];
        return '';
    }
    function extension(){
        if(strpos($this->name,'.')===false){
            //guess from mime
            $v = '';
            switch($this->mime){
                case 'image/jpg':
                case 'image/jpeg':
                $v = 'jpg'; break;
                case 'image/png':
                $v = 'png'; break;
                case 'image/gif':
                $v = 'gif'; break;
                case 'image/bmp':
                $v = 'bmp'; break;
                case 'text/plain':
                $v = 'txt'; break;
                case 'text/html':
                $v = 'html'; break;
                case 'text/javascript':
                case 'application/javascript':
                $v = 'js'; break;
                case 'text/json':
                case 'application/json':
                $v = 'json'; break;
            }
        }else{
            $x = explode(".", $this->name);
            $v = end($x);
        }
        return $v;
    }
    function _post_key(){
        if(!isset($this->data['_post_index']) || (empty($this->data['_post_index']) && ($this->data['_post_index'] !==0 && $this->data['_post_index']==='0'))){
            return "$this->_post_id";
        }else{
            return "{$this->_post_id}[{$this->_post_index}]";
        }
	}

	function path(){
		return $this->tmp_name;
	}
    function save($path){
		return @move_uploaded_file($this->tmp_name, $path);
	}
    function delete(){
		return @unlink($this->tmp_name);
	}
    function getError() {
        return $this->error;
    }
    function getErrorMessage() {
        switch ($this->error) {
            case UPLOAD_ERR_OK:
                return '';
            case UPLOAD_ERR_INI_SIZE:
                return 'The uploaded file exceeds the upload_max_filesize directive in php.ini';
            case UPLOAD_ERR_FORM_SIZE:
                return 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form';
            case UPLOAD_ERR_PARTIAL:
                return 'The uploaded file was only partially uploaded';
            case UPLOAD_ERR_NO_FILE:
                return 'No file was uploaded';
            case UPLOAD_ERR_NO_TMP_DIR:
                return 'Missing a temporary folder';
            case UPLOAD_ERR_CANT_WRITE:
                return 'Failed to write file to disk';
            case UPLOAD_ERR_EXTENSION:
                return 'File upload stopped by extension';
            default:
                return 'Unknown upload error #' . $this->error;
        }
    }
}
class REQUEST_files extends REQUEST_trait implements \ArrayAccess, \Iterator, \Countable
{
    private $_keyCache = array();
    protected $items = array();

    public function __construct() {
        if(isset($_FILES))
        {
            foreach($_FILES as $name =>$file){
                if(is_array($file['name'])){
                    foreach($file['name'] as $key=>$x){
                        $item = array(
                            'name'     => $file['name'][$key],
                            'type'     => strtolower($file['type'][$key]),
                            'tmp_name' => $file['tmp_name'][$key],
                            'error'    => $file['error'][$key],
                            'size'     => $file['size'][$key],
                        );
                        $item['_post_id'] = $name;
                        $item['_post_index'] = $key;
                        $this->items[] = new REQUEST_uploadedfile($item);
                    }
                }else{
                    $file['_post_id'] = $name;
                    $item['_post_index'] = null;
                    $this->items[] = new REQUEST_uploadedfile($file);
                }
            }
        }
        return $this;
    }
    function exists($name,$index=null){
        if(func_num_args() >1 && !is_null($index) ){
            $key = "{$name}[{$index}]";
        }else{
            $key = "{$name}";
        }
        if(!count($this->_keyCache)){
            foreach($this->items as $file){
                $this->_keyCache[$file->_post_key] = $file->_post_key;
            }
        }
        return in_array($key,$this->_keyCache);
    }
    public function __get($name) {
        $key = "{$name}";
        foreach($this->items as $file){
            if($file->_post_key == $key){
                return $file;
            }
        }
        return FALSE;
    }
    function getFile($name=null){
        foreach($this->items as $file){
            if($file->_post_key == $name){
                return $file;
            }
        }
        return FALSE;
    }
    function get($name=null,$index=null){
        if(func_num_args() ==0){
            return parent::get();
        }
        if(func_num_args() >1 && ($index !== null) ){
            $key = "{$name}[{$index}]";
        }else{
            $key = "{$name}";
        }
        return $this->getFile($key);
    }

    public function __isset($name) {
        return $this->exists($name);
    }

    public function __toString() {
        return 'FILES{}';
    }
    function toArray(){
        return $this->items;
    }
    public function offsetSet($offset,$value) {
        throw new \Exception('Setting not permitted');
    }

    public function offsetExists($offset) {
        if(is_numeric($offset))
            return isset($this->items[$offset]);
        else
            return $this->exists($name);
    }

    public function offsetUnset($offset) {
        if(is_numeric($offset))
            unset($this->items[$offset]);
        else{
            foreach($this->items as $k => $file){
                if($file->_post_key == $offset){
                    unset($this->items[$k]);
                    break;
                }
            }
        }
    }

    public function offsetGet($offset) {
        if(is_numeric($offset))
            return isset($this->items[$offset]) ? $this->items[$offset] : null;
        else{
            foreach($this->items as $k => $file){
                if($file->_post_key == $offset){
                    return $file;
                }
            }
        }
        return null;
    }

    public function rewind() {
        return reset($this->items);
    }

    public function current() {
        return current($this->items);
    }
    public function key() {
        return key($this->items);
    }
    public function next() {
        return next($this->items);
    }
    public function valid() {
        return key($this->items) !== null;
    }
    public function count() {
        return count($this->items);
    }
}
class REQUEST_path extends REQUEST_trait implements \ArrayAccess, \Iterator, \Countable
{
    protected $data = array();
    protected $query = '';
    protected $extension = '';
    public function join($with= DIRECTORY_SEPARATOR){
        $x = array_filter($this->data);
        return implode($with,$x);
    }
    public function pageTranslate($to,Array $anyof){
        $x = '';
        if($this->page){
            if(!is_array($anyof)){
                if(func_num_args()>2){
                    $anyof = func_get_args();
                    array_shift($anyof);
                }else{
                    $anyof = str_replace('/',',',$anyof);
                    $anyof =explode(',',$anyof);
                }
            }
            if(in_array($this->page,$anyof)){
                $x = $this->page;
                $this->data[0] = $to;
            }
            if ($x){
                $this->setRoute($this->join());
            }
        }
        return $x;
    }
    public function pageSplit($if='.php'){
        $x = '';
        if($this->page){
            $i = strripos($this->page,$if);
            if($i){
                $p = substr($this->page,0,$i);
                $x = substr($this->page,$i);
                $this->data[0] = $p;
            }
        }
        return $x;
    }
    public function trim($anyof=array()){
        //remove items from top element
        if(!is_array($anyof)){
            if(func_num_args()>1){
                $anyof = func_get_args();
            }else{
                $anyof =array($anyof);
            }
        }
        $anyof = array_filter($anyof);
        $i = 0;
        if(count($this->data) && isset($this->data[0])){
            while(in_array($this->data[0],$anyof)){
                array_shift($this->data);
                $i++;
                if(!isset($this->data[0])) break;
            }
        }
        return $i;
    }

    public function split($anyof=array('.php')){
        //remove extentions from all segments
        if(!is_array($anyof)){
            if(func_num_args()>1){
                $anyof = func_get_args();
            }else{
                $anyof =array($anyof);
            }
        }
        $done =false;
        $anyof = array_filter($anyof);
        if(count($this->data) && count($anyof)){
            foreach($this->data as $k=>$v){
                foreach($anyof as $if){
                    $i = strripos($v,$if);
                    if($i){
                        $this->data[$k] = substr($v,0,$i);
                        $done = true;
                    }
                }
            }
        }
        return $done;
    }
    public function removeSegment($anyof=array()){
        //remove a segment in path
        if(!is_array($anyof)){
            if(func_num_args()>1){
                $anyof = func_get_args();
            }else{
                $anyof =array($anyof);
            }
        }
        $anyof = array_filter($anyof);
        $i = 0;
        if(count($this->data) && count($anyof)){
            foreach($this->data as $k=>$v){
                if(in_array($v,$anyof)){
                    unset($this->data[$k]);
                    $i++;
                }
            }
            if($i){
                $this->data = array_values($this->data);
            }
        }
        return $i;
    }
    public function hasSegment($anyof=array()){
        //determin if a segment is in path
        if(!is_array($anyof)){
            if(func_num_args()>1){
                $anyof = func_get_args();
            }else{
                $anyof =array($anyof);
            }
        }
        $anyof = array_filter($anyof);
        $i = 0;
        if(count($this->data) && count($anyof)){
            foreach($this->data as $v){
                if(in_array($v,$anyof)){
                    return true;
                }
            }
        }
        return false;
    }
    public function findSegment($segment){
        //look up and return the location of matching segment
        if(count($this->data) && $segment){
            foreach($this->data as $k=>$v){
                if($v == $segment){
                    return $k;
                }
            }
        }
        return false;
    }
    public function __construct() {
        if(isset($_SERVER["PATH_INFO"])){
            $pi = $_SERVER["PATH_INFO"];
            if(isset($_SERVER["QUERY_STRING"])){
                $this->query = $_SERVER["QUERY_STRING"];
            }
        }elseif(isset($_SERVER["REQUEST_URI"])){
            $pi = $_SERVER["REQUEST_URI"];
        }else{
            $pi = '';
        }
        if(func_num_args()){
            $a1 = func_get_arg(0);
            $a2 = (func_num_args()>1)?func_get_arg(1):false;
            if(is_bool($a1) && is_bool($a2) && $a2==false){
                $a = $pi;
                $b = $a1;
            }elseif($a1 === null||$a1===''){
                $a = $pi;
                $b = $a2;
            }else{
                $a = $a1;
                $b = $a2;
            }
            $this->setRoute($a,$b);
        }else
            $this->setRoute($pi);
    }
    public function setRoute($path,$respectCase = false) {
        $r = ltrim((ltrim($path)),'/');
        $x = explode('?',$r);
        if(isset($x[1])){
            $this->query = $x[1];
        }
        if(!$respectCase){
            $x[0] = strtolower($x[0]);
        }
        $segments = explode('/',trim($x[0],'/'));
        foreach($segments as $k=>$v){
            if($v === ''){
                unset($segments[$k]);
            }
        }
        $this->data = array_values($segments);
        //$this->data = array_filter($segments);
        if($i = strrpos($x[0],'.')){
            $this->extension = substr($x[0],$i);
        }
    }

    public function promote(){
        return array_shift($this->data);
    }
    public function promoteIf($anyof){
        if(!isset($this->data[0])){
            return false;
        }
        if(is_array($anyof)){
            if(in_array($this->data[0],$anyof)){
                return array_shift($this->data);
            }
        }elseif(func_num_args()>1){
            $anyof = func_get_args();
            if(in_array($this->data[0],$anyof)){
                return array_shift($this->data);
            }
        }elseif($this->data[0] == $anyof){
                return array_shift($this->data);
        }
        return false;
    }
    public function page(){
        if(isset($this->data[0])){
            return $this->data[0];
        }
        return '';
    }
    public function subpage(){
        if(isset($this->data[1])){
            return $this->data[1];
        }
        return '';
    }
    public function query(){
        return $this->query;
    }
    public function extension(){
        return $this->extension;
    }
    public function param($index){
        if(isset($this->data[$index])){
            return $this->data[$index];
        }
        return '';
    }
    function sub($name, $default=false) {
        $location = $this->findSegment($name);
        if($location === false){
            return $default;
        }
        $location+=1;

        if(isset($this->data[$location])){
            return $this->data[$location];
        }
        return $default;

    }
    function read($name, $default=false) {
        $name = strtolower($name);
        switch($name){
            case 'query': return $this->query;
            case 'extension': return $this->extension;
            case 'page': $name = 0; break;
            case 'subpage': $name = 1; break;
            default:
                if(substr($name,0,5)=='param'){
                    $name = (int)substr($name,5);
                    $name--;;
                }
        }

        if(isset($this->data[$name])){
            return $this->data[$name];
        }
        return $default;
    }
    function seek($name, $default='') {
        $name = strtolower($name);
        switch($name){
            case 'extension':
                if($this->extension =='') $this->extension = $default;
            return $this->extension;

            case 'page': $name = 0; break;
            case 'subpage': $name = 1; break;
            default:
                if(substr($name,0,5)=='param'){
                    $name = (int)substr($name,5);
                    $name--;
                }
        }
        if(!isset($this->data[$name])){
            $this->data[$name] = $default;
        }
        return $this->data[$name];
    }
    function exists($name){
        return $this->__isset($name);
    }
    function isEmpty($name)
    {
        $name = strtolower($name);
        switch($name){
            case 'extension':
                return ($this->extension ==='');
            return $this->extension;
            case 'page': $name = 0; break;
            case 'subpage': $name = 1; break;
            default:
                if(substr($name,0,5)=='param'){
                    $name = (int)substr($name,5);
                    $name--;
                }
        }
        return empty($this->data[$name]);
    }
    function delete($name)
    {
        $this->__set($name,null);
    }
    function toArray(){
        if(func_num_args() ==1){
            if(is_array(func_get_arg(0)))
                $this->data = func_get_arg(0);
        }
        return $this->data;
    }
    public function __get($name) {
        $name = strtolower($name);
        if(!isset($this->data[$name])){
            if(method_exists(__CLASS__,$name))
                return $this->$name();
            elseif(substr($name,0,5)=='param'){
                $name = (int)substr($name,5);
                $name--;
                return $this->param($name);
            }
        }
        if(isset($this->data[$name])){
            return $this->data[$name];
        }
        return '';
    }
    public function __set($name, $value) {
        $name = strtolower($name);
        switch($name){

            case 'extension':
                $this->extension = $value;
            return $this->extension;
            case 'page':
                $name = 0;
                 if(!is_null($value)){
                    array_unshift($this->data,$value);
                 }
                 if($value === ''){
                    $this->data =array();
                    return ;
                 }
            break;
            case 'subpage':
                $name = 1;
                if(!is_null($value)){
                    array_unshift($this->data,'');
                    if(isset($this->data[1])) $this->data[0] = $this->data[1];
                    //$this->data[1] = $value
                 }
                 if($value === ''){
                    $p = isset($this->data[0])?$this->data[0]:'';
                    $this->data =array($p);
                    return ;
                 }
            break;
            default:
                if(substr($name,0,5)=='param'){
                    $name = (int)substr($name,5);
                    $name--;
                }
        }
        if((null ===$value))
            unset($this->data[$name]);
        else
            $this->data[$name] = $value;
    }
    public function __unset($name) {
        $this->__set($name,null);
    }
    public function __isset($name) {
        $name = strtolower($name);
        switch($name){
            case 'page': $name = 0; break;
            case 'subpage': $name = 1; break;
            default:
                if(substr($name,0,5)=='param'){
                    $name = (int)substr($name,5);
                    $name--;
                }
        }
        return isset($this->data[$name]);
    }
    public function __toString() {
        return $this->join();
    }

    /** Iterators */
    public function offsetSet($offset,$value) {
        if ($offset == '') {
            $this->data[] = $value;
        }else {
            if(strtolower($offset)=='page') $offset = 0;
            if(strtolower($offset)=='subpage') $offset = 1;
            $this->data[$offset] = $value;
        }
    }
    public function offsetExists($offset) {
        if(strtolower($offset)=='page') $offset = 0;
        if(strtolower($offset)=='subpage') $offset = 1;
        return isset($this->data[$offset]);
    }
    public function offsetUnset($offset) {
        if(strtolower($offset)=='page') $offset = 0;
        if(strtolower($offset)=='subpage') $offset = 1;
        unset($this->data[$offset]);
    }
    public function offsetGet($offset) {
        if(strtolower($offset)=='page') $offset = 0;
        if(strtolower($offset)=='subpage') $offset = 1;
        return isset($this->data[$offset]) ? $this->data[$offset] : null;
    }
    public function rewind() {
        reset($this->data);
    }
    public function current() {
        return current($this->data);
    }
    public function key() {
        return key($this->data);
    }
    public function next() {
        return next($this->data);
    }
    public function valid() {
        return key($this->items) !== null;
    }
    public function count() {
        return count($this->data);
    }


}

class REQUEST_request extends REQUEST_base{
    public function __construct() {
        $this->data = &$_REQUEST;
        $this->identity = 'REQUEST';
        parent::__construct();
    }
    /*public function __get($name) {

    }*/
    public function __call($name, $arguments) {
        error_log("unknown function call REQUEST->$name()");
        //TODO throw an error
    }
    public function isRewritten() {
      $realScriptName=$_SERVER['SCRIPT_NAME'];
      $virtualScriptName=reset(explode("?", $_SERVER['REQUEST_URI']));
      return !($realScriptName==$virtualScriptName);
    }
    public function method() {
        $m = @getenv('REQUEST_METHOD');
        if($m) return strtoupper($m);
        if(isset($_SERVER['REQUEST_METHOD'])) return strtoupper($_SERVER['REQUEST_METHOD']);
        if(isset($_ENV['REQUEST_METHOD'])) return strtoupper($_ENV['REQUEST_METHOD']);
        return '';
    }
    public function uri() {
        if(isset($_SERVER['REDIRECT_URL'])){
            $url = $_SERVER['REDIRECT_URL'];
            $q = isset($_SERVER['REDIRECT_QUERY_STRING'])?$_SERVER['REDIRECT_QUERY_STRING']:'';
            if($q) $q = '?'.$q;
            return $url . $q;
        }
        if(isset($_SERVER['REQUEST_URI'])) return $_SERVER['REQUEST_URI'];
        return '';
    }
    public function protocol() {
        return $this->scheme();
    }
    public function scheme() {
        if(isset($_SERVER['REQUEST_SCHEME'])){
            $protocol = $_SERVER['REQUEST_SCHEME'];
        }elseif(isset($_SERVER['HTTP_X_FORWARDED_PROTO'])){
            $protocol = $_SERVER['HTTP_X_FORWARDED_PROTO'];
        }else{
            $protocol = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || $_SERVER['SERVER_PORT'] == 443) ? "https" : "http";
        }
        return $protocol;
    }
    public function hostname() {
        if(isset($_SERVER['HTTP_HOST'])) return $_SERVER['HTTP_HOST'];
        if(isset($_SERVER['SERVER_NAME'])) return $_SERVER['SERVER_NAME'];
        return '';
    }
    public function port() {
        if(isset($_SERVER['SERVER_PORT'])) return $_SERVER['SERVER_PORT'];
        return '';
    }
    public function authority() {
        $host = $this->hostname();
        $port = $this->port();
        if($port) $host.=':';
        return "{$host}{$port}";
    }

    public function origin() {
        $protocol = $this->scheme();
        $host = $this->authority();
        return "{$protocol}://{$host}";
    }
    public function authoritativeURI() {
        $protocol = $this->scheme();
        $host = $this->authority();
        $uri = $this->uri();
        return "{$protocol}://{$host}{$uri}";
    }
    public function absoluteURI() {
        $protocol = $this->scheme();
        $host = $this->hostname();
        $uri = $this->uri();
        return "{$protocol}://{$host}{$uri}";
    }
    public function baseURI() {
        $host = $this->hostname();
        $uri = $this->uri();
        return "//{$host}{$uri}";
    }
    public function absoluteURL() {
        $protocol = $this->scheme();
        $host = $this->hostname();
        $uri = $this->uri();
        $x = explode('?',$uri.'?');
        $uri = $x[0];
        return "{$protocol}://{$host}{$uri}";
    }
    public function baseURL() {
        $host = $this->hostname();
        $uri = $this->uri();
        $x = explode('?',$uri.'?');
        $uri = $x[0];
        return "//{$host}{$uri}";
    }
    public function isSoftRefresh(){
        return REQUEST::isSoftRefresh();
    }
    public function isHardRefresh(){
        return REQUEST::isHardRefresh();
    }
    public function isXmlHttpRequest()
	{
		return REQUEST::isXmlHttpRequest();
	}
    public function isXHR()
	{
		return REQUEST::isXmlHttpRequest();
	}
    public function isSoap()
	{
		return REQUEST::is_SOAP();
	}
    public function isSecureRequest()
	{
		return $this->scheme() === "https";
	}
    /**
	 * Checks whether HTTP method is POST. if _SERVER["REQUEST_METHOD"]==="POST"
	 */
	public function isPost()
	{
		return $this->method() === "POST";
	}

	/**
	 * Checks whether HTTP method is GET. if _SERVER["REQUEST_METHOD"]==="GET"
	 */
	public function isGet()
	{
		return $this->method() === "GET";
	}

	/**
	 * Checks whether HTTP method is PUT. if _SERVER["REQUEST_METHOD"]==="PUT"
	 */
	public function isPut()
	{
		return $this->method() === "PUT";
	}

	/**
	 * Checks whether HTTP method is PATCH. if _SERVER["REQUEST_METHOD"]==="PATCH"
	 */
	public function isPatch()
	{
		return $this->method() === "PATCH";
	}

	/**
	 * Checks whether HTTP method is HEAD. if _SERVER["REQUEST_METHOD"]==="HEAD"
	 */
	public function isHead()
	{
		return $this->method() === "HEAD";
	}

	/**
	 * Checks whether HTTP method is DELETE. if _SERVER["REQUEST_METHOD"]==="DELETE"
	 */
	public function isDelete()
	{
		return $this->method() === "DELETE";
	}

	/**
	 * Checks whether HTTP method is OPTIONS. if _SERVER["REQUEST_METHOD"]==="OPTIONS"
	 */
	public function isOptions()
	{
		return $this->method() === "OPTIONS";
	}
    protected final function getHelper(array $source, $name = null, $defaultValue = null, $filters = null, $notAllowEmpty = false, $noRecursive = false)
	{
		$value = null;

		if ($name === null) {
			return $source;
		}

		if(array_key_exists($name,$source)){
			return $defaultValue;
		}

		if ($filters !== null ){
            if(is_string($filters)){
                $filter = filter_id($filters);
                if($filter)
                    $value = filter_var($value,$filter);
            }elseif(is_array($filters)){
                foreach($filters as $f=>$o){
                    $filter = is_string($f)?filter_id($f):(int)$f;
                    if($filter)
                    $value = filter_var($value,$filter,$o);
                }
            }
		}

		if(empty($value) && $notAllowEmpty === true) {
			return $defaultValue;
		}

		return $value;
	}
    /**
	 * Gets a variable from the $_POST superglobal applying filters if needed
	 * If no parameters are given the $_POST superglobal is returned
	 *
	 *<code>
	 *	//Returns value from $_POST["user_email"] without sanitizing
	 *	$userEmail = $request->getPost("user_email");
	 *
	 *	//Returns value from $_POST["user_email"] with sanitizing
	 *	$userEmail = $request->getPost("user_email", "email");
	 *</code>
	 */
	public function getPost($name = null, $defaultValue = null,$filters = null,  $notAllowEmpty = false, $noRecursive = false)
	{
		return $this->getHelper($_POST, $name, $defaultValue,$filters,  $notAllowEmpty, $noRecursive);
	}
    /**
	 * Gets variable from $_GET superglobal applying filters if needed
	 * If no parameters are given the $_GET superglobal is returned
	 *
	 *<code>
	 *	//Returns value from $_GET["id"] without sanitizing
	 *	$id = $request->getQuery("id");
	 *
	 *	//Returns value from $_GET["id"] with sanitizing
	 *	$id = $request->getQuery("id", "int");
	 *
	 *	//Returns value from $_GET["id"] with a default value
	 *	$id = $request->getQuery("id", null, 150);
	 *</code>
	 */
	public function getQuery($name = null, $defaultValue = null,$filters = null,  $notAllowEmpty = false, $noRecursive = false)
	{
		return $this->getHelper($_GET, $name,$defaultValue,  $filters, $notAllowEmpty, $noRecursive);
	}
    public function getBasicAuth()
	{
		if(isset($_SERVER["PHP_AUTH_USER"]) && isset($_SERVER["PHP_AUTH_PW"])) {
			$auth = array();
			$auth["username"] = $_SERVER["PHP_AUTH_USER"];
			$auth["password"] = $_SERVER["PHP_AUTH_PW"];
			return $auth;
		}

		return null;
	}
    /**
	 * Gets auth info accepted by the browser/client from $$_SERVER['PHP_AUTH_DIGEST']
	 */
	public function getDigestAuth()
	{
		$auth =array();

		if(isset($_SERVER["PHP_AUTH_DIGEST"])) {
		  $digest = $_SERVER["PHP_AUTH_DIGEST"];
			$matches = array();
			if( !preg_match_all("#(\\w+)=(['\"]?)([^'\" ,]+)\\2#", $digest, $matches, 2) ){
				return $auth;
			}
			if(is_array($matches)){
				foreach($matches as $match) {
					$auth[$match[1]] = $match[3];
				}
			}
		}

		return $auth;
	}

    public function auth_type() {
        $m = @getenv('AUTH_TYPE');
        if($m) return strtoupper($m);
        if(isset($_SERVER['AUTH_TYPE'])) return strtoupper($_SERVER['AUTH_TYPE']);
        if(isset($_ENV['AUTH_TYPE'])) return strtoupper($_ENV['AUTH_TYPE']);
        return '';
    }

    function pathinfo()
    {
        if( array_key_exists('PATH_INFO', $_SERVER) ){
            return trim($_SERVER['PATH_INFO'], '/');
        }else
        {
            $pos = strpos($_SERVER['REQUEST_URI'], $_SERVER['QUERY_STRING']);

            $asd = substr($_SERVER['REQUEST_URI'], 0, $pos - 2);
            $asd = substr($asd, strlen($_SERVER['SCRIPT_NAME']) + 1);

            return $asd;
        }
    }
    public function getPath()
	{
		return new REQUEST_path($this->pathinfo());
	}


//TODO see https://github.com/phalcon/cphalcon/blob/master/phalcon/http/request.zep
//     for many best implements

}

abstract class REQUEST_unexposed{

}
class rPriorityQueue extends \SplPriorityQueue{
    protected $serial = PHP_INT_MAX;

    public function insert($value, $priority) {
        parent::insert($value, array($priority, $this->serial--));
    }
}
/**
     * Similar to parse_str. Returns false if the query string or URL is empty. Because we're not parsing to
     * variables but to array key entries, this function will handle ?[]=1&[]=2 "correctly."
     *
     * @return array Similar to the $_GET formatting that PHP does automagically.
     * @param string $url A query string or URL
     * @param boolean $qmark Find and strip out everything before the question mark in the string
    */

function parse_query_string($url, $qmark=true)
{
    if ($qmark) {
        $pos = strpos($url, "?");
        if ($pos !== false) {
            $url = substr($url, $pos + 1);
        }
    }
    if (empty($url))
        return false;
    $tokens = explode("&", $url);
    $urlVars = array();
    foreach ($tokens as $token) {

        $value = string_pair($token, "=", "");
        $token = urldecode($token);
        $value = urldecode($value);
        if (preg_match('/^([^\[]*)(\[.*\])$/', $token, $matches)) {
            parse_query_string_array($urlVars, $matches[1], $matches[2], $value);
        } else {
            $urlVars[$token] = $value;
        }
    }
    return $urlVars;
}

    /**
    * Breaks a string into a pair for a common parsing function.
    *
    * The string passed in is truncated to the left half of the string pair, if any, and the right half, if anything, is returned.
    *
    * An example of using this would be:
    * <code>
    * $path = "Account=Balance";
    * $field = string_pair($path);
    *
    * $path is "Account"
    * $field is "Balance"
    *
    * $path = "Account";
    * $field = string_pair($path);
    *
    * $path is "Account"
    * $field is false
    * </code>
    *
    * @return string The "right" portion of the string is returned if the delimiter is found.
    * @param string $a A string to break into a pair. The "left" portion of the string is returned here if the delimiter is found.
    * @param string $delim The characters used to delimit a string pair
    * @param mixed $default The value to return if the delimiter is not found in the string
    * @desc
    */
function string_pair(&$a, $delim='=', $default=false)
    {
        $n = strpos($a, $delim);
        if ($n === false) return $default;
        $result = substr($a, $n+strlen($delim));
        $a = substr($a, 0, $n);
        return $result;
    }

    /**
     * Utility function for parse_query_string. Given a result array, a starting key, and a set of keys formatted like "[a][b][c]"
     * and the final value, updates the result array with the correct PHP array keys.
     *
     * @return void
     * @param array $result A result array to populate from the query string
     * @param string $k The starting key to populate in $result
     * @param string $arrayKeys The key list to parse in the form "[][a][what%20ever]"
     * @param string $value The value to place at the destination array key
    */
    function parse_query_string_array(&$result, $k, $arrayKeys, $value)
    {
        if (!preg_match_all('/\[([^\]]*)\]/', $arrayKeys, $matches))
            return $value;

        if (!isset($result[$k])) {
            $result[$k] = array();
        }
        $temp =& $result[$k];
        $last = array_pop($matches[1]);
        foreach ($matches[1] as $k) {
            $k = $k;
            if ($k === "") {
                $temp[] = array();
                $temp =& $temp[count($temp)-1];
            } else if (!isset($temp[$k])) {
                $temp[$k] = array();
                $temp =& $temp[$k];
            }
        }
        if ($last === "") {
            $temp[] = $value;
        } else {
            $temp[$last] = $value;
        }
    }