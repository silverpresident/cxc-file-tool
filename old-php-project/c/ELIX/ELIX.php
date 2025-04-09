<?php
namespace{
class ELIX
{
    const VERSION = '1.0';
    
    static function loadapi($name)
    {
        $f =__DIR__ . DIRECTORY_SEPARATOR . "{$name}.php";
        if(file_exists($f)) include_once($f);
    }
    static function exists($name)
    {
        return file_exists(__DIR__ . DIRECTORY_SEPARATOR . "{$name}.php");
    }
    static function __callStatic($name, $arguments) {
        $name =strtolower($name);
        self::loadapi($name);
        $name =strtoupper($name);
        $class = __CLASS__ . '\\' . $name;
        $reflect  = new ReflectionClass($class);
        $instance = $reflect->newInstanceArgs($arguments);
        return $instance;
    }
    static function SessionHandler($key='elix', $name = 'ELIX_SESSION', $cookie = array(), $path='')
    {
        static $session =null;
        if($session === null ){
            self::loadapi('sessionhandler');
            $session = new \ELIX\SessionHandler($key, $name, $cookie);
            ini_set('session.save_handler', 'files');
            session_set_save_handler($session, true);
            if($path)session_save_path($path);
        }
        /*
        if the project that uses ths imediately on getting thisy you should 
$session->start();
if ( ! $session->isValid(5)) {
    $session->destroy();
}

        */
        return $session;
    }
}
}
namespace ELIX{
class PropertyBag{
    public function prototype () { return 'object'; }
    function id()
    {
        return false;
    }
    protected $data = array();
    public function __invoke($obj) {
        if(is_array($obj))$this->merge($obj);
    }
    public function merge($data){
        if(is_array($data)){
            $this->data = array_merge($this->data,$data);
        }
    }
    public function read($name, $default=false) {
        $name = strtolower($name);
        
        if(isset($this->data[$name])||array_key_exists($name,$this->data))
            return $this->data[$name];
        
        return $default;
    }
    public function seek($name, $default='') {
        $name = strtolower($name);
        if(!array_key_exists($name,$this->data))
            $this->data[$name] = $default;
        return $this->data[$name];
    }
    public function assert($name, $default) {
        $name =strtolower($name);
        if(!isset($this->data[$name]) || empty($this->data[$name]))
            $this->data[$name] = $default;
        return $this->data[$name];
    }
    public function exists($name){
        $name = strtolower($name);
        return array_key_exists($name,$this->data);
    }
    public function toArray(){
        $a = $this->data;
        if(func_num_args()){
            $f = func_get_arg(0);
            if(is_array($f)){
                $f = array_map('strtolower', $f);
                foreach($a  as $name=>$v){
                    if(!in_array($name,$f)) unset($a[$name]);
                }
            }
        }
        return $a;
    }
    public function __construct($data=array()) {
        if(func_num_args() && is_array($data))
            $this->data = array_change_key_case($data,CASE_LOWER);
    }
    public function __get($name) {
        $name = strtolower($name);
        if(isset($this->data[$name]) || array_key_exists($name,$this->data))
            return $this->data[$name];
        if(method_exists($this,$name))
            return $this->$name();
        return '';
    }
    public function __set($name, $value) {
        $name = strtolower($name);
        if($value===null)
            unset($this->data[$name]);
        else
            $this->data[$name] = $value;
    }
    public function __unset($name) {
        $name = strtolower($name);
        unset($this->data[$name]);
    }
    public function __isset($name) {
        $name = strtolower($name);
        return isset($this->data[$name]);
    }
    public function __toString() {
        if(method_exists($this,'tostring')) return $this->toString();
        $c = get_called_class();
        return "@ $c ($this->id)";
    }
    public function __call($name, $arguments) {
        $name =strtolower($name);
        if($name == 'tostring' ) return $this->__toString();
        if(isset($this->data[$name]) || array_key_exists($name,$this->data)) return $this->data[$name];
        
        $c = get_called_class();
        trigger_error("method $c -> $name which does not exist");
        return '';
    }
    static public function __callStatic($name, $arguments) {
        $c = get_called_class();
        trigger_error("static method $c :: $name which does not exist");
        return '';
    }
}
    
}