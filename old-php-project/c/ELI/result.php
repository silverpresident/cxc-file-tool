<?php
/**
 * @author Edwards
 * @copyright 2010
 * @version 20140402
 */
 
class ELI_result implements ArrayAccess, Iterator, Countable
{
    protected $items = array();
    
    public function __toString() {
        $r=array();
        foreach($this->items as $it){
            $r[] = (string)$it;
        }
        return implode(' ', $r);
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
        reset($this->items);
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
    public function has() {
        if(func_num_args()){
            $type = func_get_arg(0);
            foreach($this->items as $it){
                if($it->type == $type) return true;
            }
        }
        return count($this->items);
    }
    public function hasSuccess() {
        $type = 1;
        foreach($this->items as $it){
            if($it->type == $type) return true;
        }
        return false;
    }
    public function hasError() {
        $type = 2;
        foreach($this->items as $it){
            if($it->type == $type) return true;
        }
        return false;
    }
    public function hasInfo() {
        $type = 4;
        foreach($this->items as $it){
            if($it->type == $type) return true;
        }
        return false;
    }
    public function hasWarning() {
        $type = 8;
        foreach($this->items as $it){
            if($it->type == $type) return true;
        }
        return false;
    }
    
    public function __get($name) {
        $name = strtolower($name);
        if(method_exists($this,$name))
            return $this->$name();
        return null;
    }
    public function add($msg, $type=0) {
        $it = new ELI_result_item;
        $it->type = $type;
        $it->message = $msg;
        $this->items[] = $it;
        return $it;
    }
    public function addSuccess($msg) {
        return $this->add($msg,1);
    }
    public function addError($msg) {
        return $this->add($msg,2);
    }
    public function addInfo($msg) {
        return $this->add($msg,4);
    }
    public function addWarning($msg) {
        return $this->add($msg,8);
    }
    
    public function clear() {
        if(func_num_args()){
            $type = func_get_arg(0);
            foreach($this->items as $k => $it){
                if($it->type == $type) unset($this->items[$k]);
            }
        }else{
            $this->items = array();
        }
        return $this;
    }
    public function clearSuccess() {
        return $this->clear(1);
    }
    public function clearError() {
        return $this->clear(2);
    }
    public function clearInfo() {
        return $this->clear(4);
    }
    public function clearWarning() {
        return $this->clear(8);
    }
}
class ELI_result_item
{
    public $type;
    public $message;
    public function getMessage() {
        return $this->message;
    }
    public function isSuccess() {
        return ($this->type ==1);
    }
    public function isError() {
        return ($this->type ==2);
    }
    public function isInfo() {
        return ($this->type ==4);
    }
    public function isWarning() {
        return ($this->type ==8);
    }
    public function __toString() {
        return $this->message; 
    }
}
?>