<?php
/**
 * @author Edwards
 * @copyright 2010
 * @version 20140402
 */
 
class ELI_collection implements ArrayAccess, Iterator, Countable
{
    protected $data = array();
    protected $items = array();
    
    public function __toString() {
        return print_r($this,1);
    }
    
    public function offsetSet($offset,$value) {
        if ($offset == '') {
            $this->items[] = $value;
        }else {
            $this->items[$offset] = $value;
        }
    }

    public function offsetExists($offset) {
        return isset($this->items[$offset]);
    }

    public function offsetUnset($offset) {
        unset($this->items[$offset]);
    }

    public function offsetGet($offset) {
        return isset($this->items[$offset]) ? $this->items[$offset] : null;
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
        /*if((null ===$value))
            unset($this->data[$name]);
        else*/
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
}
?>