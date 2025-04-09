<?php
/**
 * @author Edwards
 * @copyright 2010
 * @version 20130504.1
 */
HTML::loadapi('label');
class HTML_formgroup extends HTML_element
{
    protected $tag = 'div';
    private $label = null;
    private $controls = null;
    public function label($value=null)
    {
        if($this->label === null){
            $this->label = new HTML_label($this);
            array_unshift($this->nodes,$this->label);
        }
        if(func_num_args()==0){
            return $this->label;
        }else
        {
            if((null ===$value)){
                $this->label = null;
            }elseif(is_scalar($value)){
                $this->label->innerHTML($value);
            }elseif($value instanceof HTML_label)
            {
                $this->label = $value;
            }else
            {
                $this->label->innerHTML($value);
            }
            return $this->label;
        }
    }
    public function controls()
    {
        if($this->controls === null){
            $this->controls = new HTML_div($this);
            $this->nodes[] = $this->controls;
        }
            
        if(func_num_args()==0){
            return $this->controls;
        }else
        {
            $value = func_get_arg(0);
            if(is_array($value)){
                foreach($value as $v) $this->controls($v);
            }/*elseif(is_scalar($value)){
                $this->controls->append($value);
            }*/else
            {
                $this->controls->append($value);
            }
            return $this->controls;
        }
    }
    
    public function createHidden($name, $value='')
    {
        $el = HTML::input('hidden',$name);
        $el->parent($this);
        $el->value($value);
        $this->nodes[] = $el;
        return $el;
    }
}
?>