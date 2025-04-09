<?php
/**
 * @author Edwards
 * @copyright 2010
 */
 
class ELI_object
{
    protected $data = array();
    function Read($name, $default=false) {
        $name = strtolower($name);
        if(isset($this)){
            if(isset($this->data[$name])||array_key_exists($name,$this->data))
                return $this->data[$name];
            
            return $default;
        }else{
            if(isset(self::$data[$name]))
                return self::$data[$name];
            else
                return $default;
        }
    }
    function Seek($name, $default='') {
        $name = strtolower($name);
        if(!array_key_exists($name,$this->data)) $this->data[$name] = $default;
        return $this->data[$name];
    }
    function Assert($name, $default) {
        $name =strtolower($name);
        if(!isset($this->data[$name]) || empty($this->data[$name]))
            $this->data[$name] = $default;
        return $this->data[$name];
    }
    function Exists($name){
        $name = strtolower($name);
        return array_key_exists($name,$this->data);
    }
    function isEmpty($name)
    {
        $name = strtolower($name);
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
    function all(){
        trigger_error('deprecated');
        return $this->data;
    }
    function toArray(){
        return $this->data;
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
        if((null ===$value))
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
        return print_r($this,1);
    }
    public function __call($name, $arguments) {
        trigger_error("Call to method which does not exists $name");
        return false;
    }
    static public function __callStatic($name, $arguments) {
        trigger_error("Call to static method which does not exists $name");
        return false;
    }
}
?>