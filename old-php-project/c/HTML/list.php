<?php
/**
 * @author Edwards
 * @copyright 2010
 */
class HTML_li extends HTML_element 
{
    protected $tag = 'li';
}
abstract class HTML_list extends HTML_element 
{
    protected $childNodeClass = HTML_li::class;
    
    public function create($object)
    {
        $el = HTML::build($object);
        $this->append($el);
        return $el;
    }
    public function add($value=''){
        return $this->append($value);
    }
    public function prepend($value=''){
        if(is_array($value)){
            $value = array_reverse($value);
            $el = array();
            foreach($value as $item)
                $el[] = $this->prepend($item);
        }else{
            if($value instanceof $this->childNodeClass){
                $el = $value;
            }else{
                $el = new $this->childNodeClass($this);
                $el->append($value);
            }
            parent::prepend($el);
        }
        return $el;
    }
    public function append($value='')
    {
        if(is_array($value)){
            $el = array();
            foreach($value as $item)
                $el[] = $this->append($item);
        }else{
            if($value instanceof $this->childNodeClass){
                $el = $value;
            }else{
                $el = new $this->childNodeClass($this);
                $el->append($value);
            }
            $this->nodes[]=$el;
        }
        return $el;
    }
    public function innerHTML($value=null)
    {
        if(func_num_args()) throw new Exception('Cannot set LIST innerHTML');
        $r= array();
        foreach($this->nodes as $li)
            $r[] = (string)$li;
        return implode("\n",$r);
    }
    public function __toString() {
        $r = array();
        $ih =   $this->innerHTML();
        $r[] = $this->getOpenTag();
        $r[] = $ih;
        $r[] = $this->getCloseTag(); 
        return implode('',$r);
    }
}
class HTML_ul extends HTML_list 
{
    protected $tag = 'ul';
}
class HTML_ol extends HTML_list 
{
    protected $tag = 'ol';
}