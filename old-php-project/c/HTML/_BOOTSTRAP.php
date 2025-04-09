<?php
/**
 * @author Edwards
 * @copyright 2010
 */
 
HTML::build('div');
HTML::build('span');
HTML::build('label');
HTML::build('a');
HTML::build('button');

class _BOOTSTRAP{
    static function build($object,$name='')
    {
        $object=strtolower(trim($object));
        $object =str_replace('-','_',$object);
        
        $class = __CLASS__ . '_' . $object;
        if(!class_exists($class,false))
        {
            eval("class $class extends HTML_element { protected \$tag = '$object'; var \$_='1';}");
        }
        if($name)
            return new $class($name);
        else
            return new $class();
    }
}

class _BOOTSTRAP_control_group extends HTML_div{
    private $label = null;
    private $controls = null;
    public function __construct() {
        $this->class('form-group');
    }
    public function create($object)
    {
        $object = strtolower($object);
        IF(in_array($object,array('controls','label'))){
            return $this->$object();
        }else{
            return  parent::create($object);
        }
    }
    public function label($append=''){ 
        if((null ===$this->label)){
            $this->label = new _BOOTSTRAP_control_label($this);
            $this->nodes[] = $this->label;
        }
        if($append)$this->label->innerHTML($append);
        return $this->label;
    }
    public function controls($append=''){
        if((null ===$this->controls)){
            $this->controls = new _BOOTSTRAP_controls($this);
            $this->nodes[] = $this->controls;
        }
        if($append)$this->controls->append($append);
        return $this->controls;
    }
}
class _BOOTSTRAP_controls extends HTML_div{
    public function __construct() {
        if(func_num_args()){
            parent::__construct(func_get_arg(0));
        }
        $this->class('controls');
    }
}
class _BOOTSTRAP_control_label extends HTML_label{
    public function __construct() {
        if(func_num_args()){
            parent::__construct(func_get_arg(0));
        }
        $this->class('control-label');
    }
}
class _BOOTSTRAP_form_actions extends HTML_div{
    public function __construct() {
        if(func_num_args()){
            parent::__construct(func_get_arg(0));
        }
        $this->class('form-group');
    }
}
class _BOOTSTRAP_btn extends HTML_button{
    public function __construct() {
        if(func_num_args()){
            parent::__construct(func_get_arg(0));
        }
        $this->class('btn');
    }
}
class _BOOTSTRAP_button extends _BOOTSTRAP_btn{
    public function __construct() {
        if(func_num_args()){
            parent::__construct(func_get_arg(0));
        }
        $this->class('btn');
    }
}
class _BOOTSTRAP_a extends HTML_a{
    public function __construct() {
        if(func_num_args()){
            parent::__construct(func_get_arg(0));
        }
        $this->class('btn btn-link');
    }

}
class _BOOTSTRAP_icon extends HTML_element_generic{
    protected $tag = 'span';
    public function __construct() {
        if(func_num_args()){
            parent::__construct(func_get_arg(0));
        }
        $this->class('glyphicon');
    }

}
