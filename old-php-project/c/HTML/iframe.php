<?php
/**
 * @author Edwards
 * @copyright 2015
 */
class HTML_iframe extends HTML_element_nameable
{
    protected $tag = 'iframe';
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