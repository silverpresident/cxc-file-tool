<?php
/**
 * @author Edwards
 * @copyright 2010
 * @version 20130501.1
 */

class HTML_xcontainer extends HTML_element
{
    protected $tag = '';
    public function getOpenTag()
    {
        $a = $this->getAttributes();
        if(empty($a)){
            return '';
        }else{
            $a = str_replace(array('--','>'),array('-- ',' >'),$a);
            return "<!-- $a -->";
        }
    }
    public function getCloseTag()
    {
        return '';
    }
}