<?php
/**
 * @author Edwards
 * @copyright 2010
 */

class HTML_textarea extends HTML_element_nameable
{
    protected $tag = 'textarea';
    public function __toString() {
        $r = array();
        $r[] = $this->getOpenTag();
        $r[] = htmlspecialchars($this->innerHTML(),null,null,false);
        $r[] = $this->getCloseTag(); 
        return implode('',$r);
    }
    public function value($value=null)
    {
        if(func_num_args()==0)
            return $this->innerHTML();
        else{
            return $this->innerHTML($value);
        }
    }
}