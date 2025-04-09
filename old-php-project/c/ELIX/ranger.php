<?php
/**
 * @author Edwards
 * @copyright 2015
 * 
 * this class provides tools for  the  server
 */
namespace ELIX;

class RANGER implements \ArrayAccess, \Iterator, \Countable{
    protected $label='', $label_plural='';
    protected $items =array();
    private $group=0;
    public function setLabel($singular,$plural='') {
        $this->label = $singular;
        if($plural)
            $this->label_plural = $plural;
        else
            $this->label_plural = $singular.'s';
        return $this;
    }
    public function addRange($start,$end,$step=1) {
        $p = 0;
        if(strpos($step,'.')===false){
            $step = (int)$step;
        }else{
            $p = strlen($step)-(strpos($step,'.')+1);
            $step = (float)$step;
        }
        if(strpos($start,'.')===false){
            $start = (int)$start;
        }else{
            $p = max($p,strlen($start)-(strpos($start,'.')+1));
            $start = (float)$start;
        }
        if(strpos($end,'.')===false){
            $end = (int)$end;
        }else{
            $p = max($p,strlen($end)-(strpos($end,'.')+1));
            $end = (float)$end;
        }
        
        $s = ($start > $end)?-$step:$step; 
        $this->group++;
        for($i=$start;$i<=$end;$i+=$s){
            $l = $i==1? $this->label:$this->label_plural;
            $v = round($i,$p);
            $it = new ranger_item($v,"$v $l",$this->group);
            $this->items[] = $it;
        }
        return $this;
    }
    public function addValue($value) {
        
        if(strpos($value,'.')===false){
            $value = (int)$value;
        }else{
            $value = (float)$value;
        }
        $l = $value==1? $this->label:$this->label_plural;
        $it = new ranger_item($value,"$value $l",$this->group);
        $this->items[] = $it;
        return $it;
    }
    public function getGroup() {
        return $this->group;
    }
    public function newGroup() {
        return ($this->group++);
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
    
}
class ranger_item{
    protected $v,$l,$g;
    public function __construct($value,$label,$group=0) {
        $this->v = $value;
        $this->l = (string)$label;
        $this->g = $group;
    }

    public function __set($name, $value) {
        $name =strtolower($name);
        if($name == 'label'){
            $this->l = (string)$value;
        }
    }
    public function __get($name) {
        $name =strtolower($name);
        if($name == 'value'){
            return $this->v;
        }
        if($name == 'label'){
            return $this->l;
        }
        if($name == 'group'){
            return $this->g;
        }
        return '';
    }

}