<?php
/**
 * @author Edwards
 * @copyright 2010
 */

class HTML_img extends HTML_element
{
    protected $tag = 'img';
    protected function isVoidElement() { return true; }
    public function src($url='')
    {
        if(func_num_args()){
            $a = func_get_args();
            $url = implode('/',$a);
            return $this->attr(__FUNCTION__,$url);
        }
        return $this->attr(__FUNCTION__);
    }
}