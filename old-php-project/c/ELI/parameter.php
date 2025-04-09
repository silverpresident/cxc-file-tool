<?php
/**
 * @author Edwards
 * @copyright 2014
 */

 
class ELI_parameter implements ArrayAccess
{
    public function __invoke($obj) {
        if(is_array($obj)){
            $this->setDefault($obj);
        }
    }

    protected $data = array();
    function setDefault($valuesArray){
        if(func_num_args()==2){
            $valuesArray = strtolower($valuesArray);
            if(!isset($this->data[$valuesArray])) $this->data[$valuesArray] = func_get_arg(1);
        }elseif(is_array($valuesArray)){
            $valuesArray = array_change_key_case($valuesArray,CASE_LOWER);
            $this->data = array_merge($valuesArray,$this->data);
        }
    }
    function setString($keyArray){
         if(is_array($keyArray)){
            $f = __FUNCTION__;
            foreach($keyArray as $key) $this->$f($key);
        }else if(func_num_args()>1){
            $f = __FUNCTION__;
            foreach(func_get_args() as $key) $this->$f($key);
        }else{
            $keyArray = strtolower($keyArray);
            if(isset($this->data[$keyArray])){
                if(is_array($this->data[$keyArray])){
                    $this->data[$keyArray] = implode(',',$this->data[$keyArray]);
                }else{
                    $this->data[$keyArray] = (string)$this->data[$keyArray];
                }
            }
        }
    }
    function setInteger($keyArray){
         if(is_array($keyArray)){
            $f = __FUNCTION__;
            foreach($keyArray as $key) $this->$f($key);
        }else if(func_num_args()>1){
            $f = __FUNCTION__;
            foreach(func_get_args() as $key) $this->$f($key);
        }else{
            $keyArray = strtolower($keyArray);
            if(isset($this->data[$keyArray])){
                if(is_array($this->data[$keyArray])){
                    foreach($this->data[$keyArray] as &$i){ $i = (int)$i; }
                }else{
                    $this->data[$keyArray] = (int)$this->data[$keyArray];
                }
            }
        }
    }
    function setFloat($keyArray){
         if(is_array($keyArray)){
            $f = __FUNCTION__;
            foreach($keyArray as $key) $this->$f($key);
        }else if(func_num_args()>1){
            $f = __FUNCTION__;
            foreach(func_get_args() as $key) $this->$f($key);
        }else{
            $keyArray = strtolower($keyArray);
            if(isset($this->data[$keyArray])){
                if(is_array($this->data[$keyArray])){
                    foreach($this->data[$keyArray] as &$i){ $i = (float)$i; }
                }else{
                    $this->data[$keyArray] = (float)$this->data[$keyArray];
                }
            }
        }
    }
    function Read($name, $default=false) {
        $name = strtolower($name);
        if(isset($this)){
            if(array_key_exists($name,$this->data))
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
        return $this->data;
    }
    public function __construct($data=array()) {
        if(func_num_args()){
            
            if($data instanceof $this) 
                $data = $data->toArray();
            elseif(is_object($data)){
                if(method_exists($data,'toarray'))
                    $data = $data->toArray();
                else
                    $data = (array) $data;
            }
            
            if( is_array($data)) $this->data = array_change_key_case($data,CASE_LOWER);
        }
    }
    public function __get($name) {
        $name = strtolower($name);
        if(isset($this->data[$name]) || array_key_exists($name,$this->data))
            return $this->data[$name];
        if(method_exists($this,$name))
            return $this->$name();
        return null;
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
        return __CLASS__ .': ' . http_build_query($this->data);
    }
    
    public function offsetSet($offset,$value) {
        if ($offset == '') {
            $this->data[] = $value;
        }else {
            if(is_string($offset))$offset = strtolower($offset);
            $this->data[$offset] = $value;
        }
    }

    public function offsetExists($offset) {
        if(is_string($offset))$offset = strtolower($offset);
        return isset($this->data[$offset]);
    }

    public function offsetUnset($offset) {
        if(is_string($offset))$offset = strtolower($offset);
        unset($this->data[$offset]);
    }

    public function offsetGet($offset) {
        if(is_string($offset))$offset = strtolower($offset);
        return isset($this->data[$offset]) ? $this->data[$offset] : null;
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