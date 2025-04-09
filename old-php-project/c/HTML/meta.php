<?php
/**
 * @author Edwards
 * @copyright 2015
 */

class HTML_meta extends HTML_element_nameable
{
    protected $tag = 'meta';
    protected function isVoidElement() { return true; }
    public function http_equiv($value=''){
        if(func_num_args())
            return $this->httpequiv($value);
        else
            return $this->httpequiv();
        
    }
    public function httpequiv($value='')
    {
        if(func_num_args())
        {
            $this->attr('http-equiv',$value);
            return $this;
        }else{
            return $this->attr('http-equiv');
        }
    }
}