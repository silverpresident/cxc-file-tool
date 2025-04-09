<?php
/**
 * @author Edwards
 * @copyright 2010
 * @version 20130504.1
 */
HTML::loadapi('input');
class HTML_form extends HTML_element_nameable
{
    const ENCODING_PLAIN = 'text/plain';
    const ENCODING_FORMDATA = 'multipart/form-data';
    const ENCODING_FILEDATA = 'multipart/form-data';
    const ENCODING_URLENCODE = 'application/x-www-form-urlencoded';
    
    protected $tag = 'form';
    public function action($url='')
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
    public function enctype($value='application/x-www-form-urlencoded'){
        if(func_num_args()==0){
            return $this->attr('enctype');
        }else{
            $value=strtolower($value);
            if(in_array($value,array('text/plain','multipart/form-data','application/x-www-form-urlencoded')))
                $this->attr('enctype',$value);
            if($value == 'multipart/form-data'){
                $this->method('POST');
            }
            return $this;
        }
    }
    public function method($value='POST'){
        if(func_num_args()==0){
            return $this->attr('method');
        }else{
            $value=strtoupper($value);
            if(in_array($value,array('POST','GET')))
                $this->attr('method',$value);
            
            return $this;
        }
    }
    public function createGroup($label=''){
        $el = HTML::build('formgroup');
        if(func_num_args()){
            $el->label()->append($label);
        }
        $this->nodes[] = $el;
        return $el;
    }
    public function createFieldset($legend=''){
        $el = $this->create('fieldset');
        if($legend)$el->legend($legend);
        return $el;
    }
    public function fieldset($legend=''){
        $el = $this->create('fieldset');
        if($legend)$el->legend($legend);
        return $el;
    }
}
?>