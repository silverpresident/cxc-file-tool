<?php
/**
 * @author Edwards
 * @copyright 2010
 */
defined('HTML_OPT_LEGACYSELECT') OR define('HTML_OPT_LEGACYSELECT',1);
HTML::loadapi('option');
class HTML_datalist_option extends HTML_option
{
    protected $tag = 'option';
}
class HTML_datalist extends HTML_element 
{
    protected $tag = 'datalist';
    public $flags = 0;
    public function options()
    {
        return $this->nodes;
    }
    public function addOptions(Array $set)
    {
        foreach($set as $value)
        {
            $this->addOption($value);
        }
        return $this;
    }
    public function addOption($value=null) 
    {
        if($value instanceof HTML_option){
            $el = $value;
        }else{
            $el = new HTML_option($this);
            $el->value($value);
        }
        $this->nodes[]=$el;
        return $el;
    }
    public function append($innerHTML=''){
        if(strip_tags($innerHTML) == $innerHTML)
            return $this->addOption($innerHTML);
        else
        {
            $this->nodes[]=$innerHTML;
            return $this;
        }
    }
    public function add($innerHTML='')
    {
        return $this->addOption($innerHTML);
    }
    public function __toString() {
        $r = array();
        $r[] = $this->getOpenTag();
        if($this->flags == HTML_OPT_LEGACYSELECT){
            $a = $this->getAttributes();
            if(empty($a)){
                $r[] = "<select style='display:none'>";
            }else{
                $r[] = "<select $a style='display:none'>";
            }
        }
        $r[] = $this->innerHTML();
        if($this->flags == HTML_OPT_LEGACYSELECT){
            $r[] = "</select>";
        }
        $r[] = $this->getCloseTag(); 
        return implode('',$r);
    }
}