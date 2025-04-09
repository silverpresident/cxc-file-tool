<?php
/**
 * @author Edwards
 * @copyright 2010
 */

class HTML_link extends HTML_element
{
    protected $tag = 'link';
    public function href($url='')
    {
        if(func_num_args()){
            $a = func_get_args();
            $url = implode('/',$a);
            return $this->attr(__FUNCTION__,$url);
        }
        return $this->attr(__FUNCTION__);
    }
    public function target($value=null)
    {
        if(func_num_args()==0)
            return $this->attr(__FUNCTION__);
        else{
            $l=strtolower($value);
            if(in_array($l,array('_blank','_self','_parent','_top')))
            {
                $this->attr[__FUNCTION__] = $l;
                return $this;
            }else
                return $this->attr(__FUNCTION__,$value);
        }
    }
}
class HTML_a extends HTML_link
{
    protected $tag = 'a';
    
}
class HTML_area extends HTML_link
{
    protected $tag = 'area';
    
}
?>