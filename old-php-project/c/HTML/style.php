<?php
/**
 * @author Edwards
 * @copyright 2010
 */

class HTML_style extends HTML_element
{
    protected $tag = 'style';
    protected $_inner_join_with ="\n";
    public function __toString() {
        $r = array();
        $r[] = $this->getOpenTag();
        $temp = $this->innerHTML();
        $r[] = $temp;
        $r[] = $this->getCloseTag();
        $sep = ($temp=='')?'':"\n";  
        return implode($sep,$r);
    }
    public function addMedia($media=''){
        $el = new HTML_style_selector($this);
        $el->media($media);
        $this->nodes[] =$el;
        return $el;
    }
    public function addSelector($selector=''){
        $el = new HTML_style_selector($this);
        $el->selector($selector);
        $this->nodes[] =$el;
        return $el;
    }
    public function add($node = null){
        $el = new HTML_style_selector($this);
        $this->nodes[] =$el;
        return $el;
    }
    
    
}
class HTML_style_selector{
    protected $nodes  = array();
    protected $media = '';
    protected $selector = '';
    public function addSelector($selector=''){
        $el = new HTML_style_selector($this);
        $el->selector($selector);
        $this->nodes[] =$el;
        return $el;
    }
    public function add($property='',$value=''){
        $prop = new HTML_style_property($this);
        
        $this->nodes[]=$prop;
        $n = func_num_args();
        if($n == 2){
            $prop->property($property);
            $prop->value($value);
        }
        if($n == 1){
            $property =trim($property);
            $ex = explode(':',$property);
            $property = $ex[0];
            $prop->property($property);
            if(isset($ex[1]))
                $prop->value($ex[1]);
        }
        return $prop;
    }
    public function append($prop=''){
        $this->nodes[]=$prop;
        return $this;
    }
    public function declaration($property='',$value=''){
        $n = func_num_args();
        if($n == 2){
            $this->add($property,$value);
        }
        if($n == 1){
            $this->add($property);
        }
        return $this;
    }
    public function media($value=''){
        if(func_num_args()){
            $this->media = $value;
            return $this;
        }
        return $this->media;
    }
    public function selector($value=''){
        if(func_num_args()){
            $this->selector = implode(', ', func_get_args());
            return $this;
        }
        return $this->selector;
    }
    public function _and($selector=''){
        if(func_num_args()){
            $this->selector .= ', '. implode(', ', func_get_args());
            return $this;
        }
        return $this->selector;
    }
    private function _class($value=''){
        if(func_num_args()){
            $this->selector = '.'.$value;
            return $this;
        }
        return $this->selector;
    }
    public function id($value=''){
        if(func_num_args()){
            $this->selector = '#'.$value;
            return $this;
        }
        return $this->selector;
    }
    public function __toString() {
        $r = array();
        $indent = '';
        if($this->media){
            $r[] = "@media $this->media {";
            $indent = ' ';
        }
        if($this->selector){
            $r[] = "{$indent}{$this->selector} {";
            $indent .= ' ';
        }
        foreach($this->nodes as $i){
            $r[] = $indent . ((string)$i);
        }
        if($this->selector){
            $r[] = "{$indent}}";
        }   
        if($this->media){
            $r[] = "}";
        }
        return implode("\n",$r);
    }
    public function __call($name, $arguments) {
        $name = strtolower($name);
        $n = count($arguments);
        if(in_array($name,array('property'))){
            if($n == 2){
                return $this->declaration($arguments[0],$arguments[1]);
            }
            if($n == 1){
                return $this->declaration($arguments[0]);
            }
            return $this->declaration();
        }
        if(in_array($name,array('adddeclaration','addproperty'))){
            if($n == 2){
                return $this->add($arguments[0],$arguments[1]);
            }
            if($n == 1){
                return $this->add($arguments[0]);
            }
            return $this->add();
        }
        if($name=='class'){
            if($n){
                return $this->_class(implode(' ', $arguments));
            }
            return $this->_class();
        }
        if($name=='and'){
            if($n){
                return $this->_and(implode(' ', $arguments));
            }
            return $this->_and();
        }
        return $this;
    }
    
    public function __get($name) {
        if(method_exists($this,$name)){
            return $this->$name();
        }
        return '';
    }
    public function __set($name,$value) {
        if(method_exists($this,$name)){
            return $this->$name($value);
        }
    }
}
class HTML_style_property{
    protected $property = '';
    protected $value = '';
    public function __toString() {
        if(strlen($this->property) && strlen($this->value)){
            return "$this->property: $this->value;";
        }
        return '';
    }
    public function __get($name) {
        if(method_exists($this,$name)){
            return $this->$name();
        }
        return '';
    }
    public function __set($name,$value) {
        if(method_exists($this,$name)){
            return $this->$name($value);
        }
    }
    public function property()
    {
        if(func_num_args()){
            $this->property = trim(func_get_arg(0));
            return $this;
        }
        return $this->property;
    }
    public function value()
    {
        if(func_num_args()){
            $this->value = trim(implode(' ', func_get_args()));
            $this->value = rtrim($this->value,';');
            return $this;
        }
        return $this->value;
    }
}
