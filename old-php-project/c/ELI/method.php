<?php
/**
 * @author Edwards
 * @copyright 2010
 * @version 20140907.1
 */
class ELI_method implements ArrayAccess, Iterator, Countable
{
    static private $instance = array();
    static private $sanitized = false;
    private $data = array();
    private $identity=null;
    
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
    /**
     * Similar to parse_str. Returns false if the query string or URL is empty. Because we're not parsing to 
     * variables but to array key entries, this function will handle ?[]=1&[]=2 "correctly."
     *
     * @return array Similar to the $_GET formatting that PHP does automagically.
     * @param string $url A query string or URL 
     * @param boolean $qmark Find and strip out everything before the question mark in the string
    */
    static function parse_query_string($url, $qmark=true)
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
            
            $value = self::string_pair($token, "=", "");
            $token = urldecode($token);
            $value = urldecode($value);
            if (preg_match('/^([^\[]*)(\[.*\])$/', $token, $matches)) {
                self::parse_query_string_array($urlVars, $matches[1], $matches[2], $value);
            } else {
                $urlVars[$token] = $value;
            }
        }
        return $urlVars;
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
    static function parse_query_string_array(&$result, $k, $arrayKeys, $value)
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
    static function string_pair(&$a, $delim='=', $default=false)
    {
        $n = strpos($a, $delim);
        if ($n === false) return $default;
        $result = substr($a, $n+strlen($delim));
        $a = substr($a, 0, $n);
        return $result;
    }
    static function build($item)
    {
        $item = strtoupper($item);
        if(!isset(self::$instance[$item])){
            $c = __CLASS__;
            if($item=='METHOD'){
                $rm = (isset($_SERVER['REQUEST_METHOD']))?$_SERVER['REQUEST_METHOD']:'';
                if($rm =='GET' || $rm=='POST'){
                    $item = $rm;
                }else{
                    $item = count($_POST)?'POST':'GET';
                }
            }
            if($item=='GET'){
                self::$instance[$item] = new $c($_GET);
            }elseif($item=='POST'){
                if(!self::$sanitized){
                    $_POST = self::sanitize($_POST);
                    self::$sanitized = true;
                }
                self::$instance[$item] = new $c($_POST);
            }elseif($item=='REQUEST'){
                self::$instance[$item] = new $c($_REQUEST);
            }elseif($item=='GETPOST'){
                $A = array();
                foreach($_GET as $k=>$v){
                    $A[$k] =& $_GET[$k];
                }
                if(!self::$sanitized){
                    $_POST = self::sanitize($_POST);
                    self::$sanitized = true;
                }
                foreach($_POST as $k=>$v){
                    $A[$k] =& $_POST[$k];
                }
                self::$instance[$item] = new $c($A);
            }elseif($item=='POSTGET'){
                $A = array();
                if(!self::$sanitized){
                    $_POST = self::sanitize($_POST);
                    self::$sanitized = true;
                }
                foreach($_POST as $k=>$v){
                    $A[$k] =& $_POST[$k];
                }
                foreach($_GET as $k=>$v){
                    $A[$k] =& $_GET[$k];
                }
                self::$instance[$item] = new $c($A);
            }elseif($item=='REFGET'){
                $r = isset($_SERVER["HTTP_REFERER"])? $_SERVER["HTTP_REFERER"]:'';
                $A = explode('?',$r);
                $r = (!empty($A[1]))? $A[1]:'';
                $A = array();
                parse_str($r,$A);
                self::$instance[$item] = new $c($A);
            }elseif($item=='INPUT'){
                $INPUT = array();
                $inputdata =@file_get_contents("php://input");
                $INPUT = self::parse_query_string($inputdata);
                
                /*$l = ceil(strlen($inputdata)/3);
                $tl = ini_get('max_input_vars');
                ini_set('max_input_vars',$l);
                @parse_str($inputdata,$INPUT);
                ini_set('max_input_vars',$tl);*/
                self::$instance[$item] = new $c($INPUT);
            }else{
                return false;
            }
            self::$instance[$item]->identity = $item;
        }
        return self::$instance[$item];
    }
    function getFiles(){
        return ELI::uploadedfiles();
    }
    function getIdentity(){
        return $this->identity;
    }
    function isXHR()
    {
        static $r = null;
        if($r===null)
        {
            $r =  isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
        }
        return $r;
    }
    function Read($name, $default=false) {
        $name = $this->change_case($name);
        if(isset($this->data[$name])||array_key_exists($name,$this->data))
            return $this->data[$name];
        
        return $default;
    }
    function Seek($name, $default='') {
        $name = $this->change_case($name);
        if(!array_key_exists($name,$this->data)) $this->data[$name] = $default;
        return $this->data[$name];
    }
    function Assert($name, $default) {
        $name =$this->change_case($name);
        if(!isset($this->data[$name]) || empty($this->data[$name]))
            $this->data[$name] = $default;
        return $this->data[$name];
    }
    function exists($name){
        $name = $this->change_case($name);
        return array_key_exists($name,$this->data);
    }
    function isEmpty($name)
    {
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
            return array_change_key_case($this->data, CASE_LOWER);
        else
            return $this->data;
    }
    public function __construct(&$array) {
        $this->data =& $array;
        $this->data = array_change_key_case($this->data, CASE_UPPER);
		foreach($this->data as $k=>$d){
			if(strpos($k,'-')!==false){
				$nk = str_replace('-','_',$k);
				if(!isset($this->data[$nk]))
					$this->data[$nk] =& $this->data[$k];
			}
		}
    }
    public function __toString() {
        return print_r($this,1);
    }

    public function __get($name) {
        $name = $this->change_case($name);
        if(isset($this->data[$name]))
            return $this->data[$name];
        else
            return '';
    }
    public function __set($name, $value) {
        $name = $this->change_case($name);
        if((null ===$value))
            unset($this->data[$name]);
        else
            $this->data[$name] = $value;
    }
    public function __unset($name) {
        $name = $this->change_case($name);
        unset($this->data[$name]);
    }
    public function __isset($name) {
        $name = $this->change_case($name);
        return isset($this->data[$name]);
    }
    
    public function offsetSet($offset,$value) {
        if ($offset == '') {
            $this->data[] = $value;
        }else {
            $offset = $this->change_case($offset);
            $this->data[$offset] = $value;
        }
    }

    public function offsetExists($offset) {
        $offset = $this->change_case($offset);
        return isset($this->data[$offset]);
    }

    public function offsetUnset($offset) {
        $offset = $this->change_case($offset);
        unset($this->data[$offset]);
    }

    public function offsetGet($offset) {
        $offset = $this->change_case($offset);
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
        return key($this->data) !== null;
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
        if($this->_case === CASE_LOWER) return strtolower($value);
        return strtoupper($value);
    }
    
}
?>