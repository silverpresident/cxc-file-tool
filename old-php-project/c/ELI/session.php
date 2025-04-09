<?php
/**
 * @author Edwards
 * @copyright 2010
 * @version 20140402
 */
if(!isset($_SESSION))session_start();
class ELI_session
{
    static private $instance = array();
    private $data = array();
    private $class = '';
    
    static function build($class='')
    {
        $item = empty($class)?'*':$class;
        if(!isset(self::$instance[$item])){
            $c = __CLASS__;
            self::$instance[$item] = new $c($item);
        }
        return self::$instance[$item];
    }
    function Read($name, $default=false) {
        $name = "{$this->class}{$name}";
        if(isset($_SESSION[$name]) || array_key_exists($name,$_SESSION))
            return $_SESSION[$name];
        return $default;
    }
    function Seek($name, $default='') {
        $name = "{$this->class}{$name}";
        if(!array_key_exists($name,$_SESSION)) $_SESSION[$name] = $default;
        return $_SESSION[$name];
    }
    function Write($name, $value='') {
        $name = "{$this->class}{$name}";
        $_SESSION[$name] = $value;
        return $_SESSION[$name];
    }
    function Assert($name, $default) {
        $name = "{$this->class}{$name}";
        if(!isset($_SESSION[$name]) || empty($_SESSION[$name]))
            $_SESSION[$name] = $default;
        return $_SESSION[$name];
    }
    function exists($name){
        $name = "{$this->class}{$name}";
        return array_key_exists($name,$_SESSION);
    }
    function isEmpty($name)
    {
        $name = "{$this->class}{$name}";
        return empty($_SESSION[$name]);
    }
    function delete($name)
    {
        $name = "{$this->class}{$name}";
        unset($_SESSION[$name]);
    }
    
    function toArray(){
        if($this->class){
            $p = $this->class;
            $l = strlen($p);
            $A = array();
            foreach($_SESSION as $k => $v){
                if(substr($k,0,$l)==$p){
                    $A[substr($k,$l)] = $v;
                }
            }
            return $A;
        }else{
            return $_SESSION;
        }
    }
    function setClass($itemClass)
    {
        $this->class = $itemClass;
    }
    public function __construct($itemClass='') {
        if(func_num_args()){
            $this->setClass(func_get_arg(0));
        }
    }
    public function __toString() {
        if($this->class){
            $p = $this->class;
            $l = strlen($p);
            $A = array();
            foreach($_SESSION as $k => $v){
                if(substr($k,0,$l)==$p){
                    $A[substr($k,$l)] = $v;
                }
            }
            return print_r($A,1);
        }else{
            return print_r($_SESSION,1);
        }
    }
    public function __get($name) {
      return $this->Read($name);
    }
    public function __set($name, $value) {
        return $this->write($name,$value);
    }
    public function __unset($name) {
        $name = "{$this->class}{$name}";
        unset($_SESSION[$name]);
    }
    public function __isset($name) {
        $name = "{$this->class}{$name}";
        return isset($_SESSION[$name]);
    }
}
?>