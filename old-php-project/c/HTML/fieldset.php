<?php
/**
 * @author Edwards
 * @copyright 2010
 * @version 20130504.1
 */
HTML::loadapi('input');
class HTML_fieldset extends HTML_element
{
    protected $tag = 'fieldset';
    public function legend($append=''){
        if($append instanceof HTML_legend){
            $el = $append;
            $this->nodes[] = $el;
        }else{
            $el = $this->create('legend');
            if($append)$el->append($append);
        }
        return $el;
    }
    public function createGroup($label=''){
        $el = HTML::build('formgroup');
        if(func_num_args()){
            $el->label()->append($label);
        }
        $this->nodes[] = $el;
        return $el;
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