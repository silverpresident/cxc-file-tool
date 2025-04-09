<?php
/**
 * @author Edwards
 * @copyright 2010
 */

class HTML_output extends HTML_element_nameable
{
    protected $tag = 'output';
    public function __toString() {
        $r = array();
        $r[] = $this->getOpenTag();
        $r[] = $this->innerHTML();
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