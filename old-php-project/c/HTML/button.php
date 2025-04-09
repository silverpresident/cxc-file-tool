<?php
/**
 * @author Edwards
 * @copyright 2010
 */

class HTML_button extends HTML_element_nameable
{
    protected $tag = 'button';
    public function formenctype($value=null)
    {
        if(func_num_args()==0)
            return $this->attr(__FUNCTION__);
        else{
            $l=strtolower($value);
            if(in_array($l,array('text/plain','multipart/form-data','application/x-www-form-urlencoded'))){
                $this->attr[__FUNCTION__] = $l;
                return $this;
            }elseif(empty($value))
                return $this->attr(__FUNCTION__,$value);
        }
    }
    public function formtarget($value=null)
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
    public function formmethod($value='POST'){
        if(func_num_args()==0){
            return $this->attr(__FUNCTION__);
        }else{
            $value=strtoupper($value);
            if(in_array($value,array('POST','GET')))
                $this->attr(__FUNCTION__,$value);
            
            return $this;
        }
    }
    public function formaction($url='')
    {
        if(func_num_args()){
            $a = func_get_args();
            $url = implode('/',$a);
            return $this->attr(__FUNCTION__,$url);
        }
        return $this->attr(__FUNCTION__);
    }
}

?>