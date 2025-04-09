<?php
/**
 * @author Edwards
 * @copyright 2010
 */

class HTML_body extends HTML_element
{
    protected $tag = 'body';
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
}