<?php
/**
 * @author Edwards
 * @copyright 2010
 */

class HTML_option extends HTML_element
{
    protected $tag = 'option';
    public function __construct($list=null,$label='',$value='') {
        $this->parent = $list;
        if(func_num_args()>=2)
            $this->value($value);
        if(func_num_args()>=1)
            $this->innerHTML($label);
    }
    public function checked($value=null){
        if(func_num_args()==0)
            return $this->selected();
        else
            return $this->selected($value);
    }
    public function selected($value=null)
    {
        if(func_num_args()==0)
        {
            if(isset($this->attr[__FUNCTION__]))
                return true;
            else
                return false;
        }else{
            if(empty($value)||$value==false)
                unset($this->attr[__FUNCTION__]);
            else
                $this->attr[__FUNCTION__] = $value;
        }
    }
    /*public function value($value=null)
    {
        if(func_num_args()==0)
            return $this->attr(__FUNCTION__);
        else{
            return $this->attr(__FUNCTION__,$value);
        }
    }*/
}