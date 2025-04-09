<?php
/**
 * @author Edwards
 * @copyright 2010
 * @version 20130427.2
 */
defined('HTML_OPT_VALUENONE') OR define('HTML_OPT_VALUENONE',0);
defined('HTML_OPT_VALUEKEYS') OR define('HTML_OPT_VALUEKEYS',1);
defined('HTML_OPT_VALUELABEL') OR define('HTML_OPT_VALUELABEL',2);
HTML::build('label');

class HTML_input_label extends HTML_label
{
    protected $tag = 'label';
    protected $attached = null;
    public function attach()
    {
        if(func_num_args()){
            if(func_get_arg(0) instanceof HTML_input)
                $this->attached = func_get_arg(0);
            return $this;
        }else{
            return $this->attached;
        }
    }
    public function attached()
    {
        return $this->attached;
    }
    
}
class HTML_input_checkitem extends HTML_input
{
    private $label = null;
    public function label($value=null)
    {
        if(func_num_args()==0)
            return $this->label;
        else
        {
            if((null ===$value)){
                $this->label = null;
            }elseif(is_scalar($value)){
                if(!($this->label instanceof HTML_element))
                    $this->label = new HTML_input_label();
                $this->label->innerHTML($value);
                $this->label->attach($this);
            }else
            {
                $this->label = $value;
                if($this->label instanceof HTML_input_label)
                    $this->label->attach($this);
            }
            return $this;
        }
    }
    public function checked($value=null)
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
                $this->attr[__FUNCTION__] = true;
            return $this;
        }
    }
    public function renderOuter(){
        if((null !==$this->label())){
            $r[] = $this->label()->getOpenTag();
            $r[] =(string)$this;
            $r[] = $this->label()->innerHTML();
            $r[] = $this->label()->getCloseTag();
        }else{
            $r[] =(string)$this;
        }
        return implode('',$r);
    }
    
    public function setType($type)
    {
        $type = strtolower($type);
        if($type=='radio' || $type =='checkbox'){
            $this->forcetype = $type;
        }
        return $this;
    }
}
class HTML_input_radio extends HTML_input
{
    protected $options =array();
    protected $_name = '';
    protected $forcetype = 'radio';
    public $separator = '';
    
    //SERIOUS change [APRIL 2015]] in behaviour: ONLY name is propageted to all children
    public function attr($key='',$value=null)
    {#propogate to all children
        $n =func_num_args();
        if($n == 0){
            return null;
        }elseif($n==2){
            if(strtolower($key)=='name'){
                $this->setName($value);
                return $this;
            }
            if(strtolower($key)=='type'){
                $this->setType($value);
                return $this;
            }
            $this->attr[$key] = $value;
        }elseif($n==1){
            if(isset($this->attr[$key]))
                return $this->attr[$key];
            else
                return null;
        }
        return $this;
    }
    public function setName($name)
    {
        if($name){
            foreach($this->options as $item)
            {
                $item->attr('name',$name);
            }
            $this->_name = $name;
        }
        return $this;
    }
    public function setType($type)
    {
        $type = strtolower($type);
        if($type=='radio' || $type =='checkbox'){
            foreach($this->options as $item)
            {
                $item->setType($type);
            }
            $this->forcetype = $type;
        }
        return $this;
    }
    public function options()
    {
        if(func_num_args()==0)
            return $this->options;
        else
        {
            $nodeIndex=func_get_arg(0);
            return $this->options[$nodeIndex];
        } 
    }
    
    public function add($innerHTML='')
    {
        return $this->addOption($innerHTML);
    }
    public function addOptions(Array $set, $useSetKeys=HTML_OPT_VALUEKEYS)
    {
        foreach($set as $k=>$label)
        {
            if($useSetKeys==HTML_OPT_VALUEKEYS){
                $this->addOption($label,$k);
            }elseif($useSetKeys==HTML_OPT_VALUELABEL){
                $this->addOption($label,$label);
            }else{
                $this->addOption($label);
            }
        }
        return $this;
    }
    public function addOption($label,$value=null, $checked=false) 
    {
        $el = new HTML_input_checkitem( $this->forcetype);
        $el->name($this->_name);
        if(func_num_args()==1 || (null ===$value)) $value = $label;
        $el->parent($this);
        $el->value($value);
        $el->label($label);
        if(is_scalar($label)){
            $el->label()->attr('title',$label);
        }
        
        if($checked)
        {
            foreach($this->options as $item)
                $item->checked(false);
            $el->checked(true);
        }
        
        $this->options[] =$el;
        return $el;
    }
    public function value($value=null)
    {
        if(func_num_args()==0)
        {
            return $this->attr('value');
        }else
        {
            $this->attr['value'] = $value;
            return $this;
        }
    }
    public function selected($value=null){
        if(func_num_args()==0)
            return $this->checked();
        else
            return $this->checked($value);
    }
    
    public function checked($value=null)
    {
        $setThem = (func_num_args()>0); 
        foreach($this->options as $item)
        {
            if($setThem){
                if($item->value() == $value)
                {
                    $item->checked(true);
                }else
                    $item->checked(false);
            }else
            {
                if($item->checked())
                    return $item;
            }
        }
        
        if($setThem){
            if(isset($this->attr['value'])){
                if((is_bool($value) && $value) || $value == $this->attr('value')) 
                    $this->attr['checked'] = true;
                else
                    $this->attr['checked'] = false;
            }else{
                $this->attr['checked'] = (bool)$value;
            }
            return $this;
        }else
            return null;
    }
    public function __toString() {
        $r = array();
        $c = count($this->options);
        foreach($this->options as $item)
        {
            if((null !==$item->label())){
                $r[] = $item->label()->getOpenTag();
                $r[] =(string)$item;
                $r[] = $item->label()->innerHTML();
                $r[] = $item->label()->getCloseTag();
            }else{
                $r[] =(string)$item;
            }
            $r[] = $this->separator;
        }
        array_pop($r);
        if($c==0){
            $f =array('type'=>$this->forcetype);
            if($this->_name) $f['name'] =$this->_name;
            $r[] = "<input";
            $r[] = $this->getAttributes($f) . ' />'; 
            return implode(' ',$r);
        }/*elseif($c==1) //THERE really should be no such thing as a radio with 1 option
            return implode($this->separator,$r) ;*/
        else{
            $o = $f =array();
            $o[] = "<span";
            if(!isset($this->attr['class']))$f['class'] = $this->forcetype;
            $o[] = $this->getAttributes($f) .' >';
            $o[] =  implode(' ',$r);
            $o[] = '</span>';
            return  implode(' ',$o);
        }
    }
    
}
class HTML_input_checkbox extends HTML_input
{
    protected $options =array();
    protected $_name = '';
    protected $forcetype = 'checkbox';
    public $separator = '';
    
    //SERIOUS change [APRIL 2015]] in behaviour: ONLY name is propageted to all children
    public function attr($key='',$value=null)
    {#propogate to all children
        $n =func_num_args();
        if($n == 0){
            return null;
        }elseif($n==2){
            if(strtolower($key)=='name'){
                $this->setName($value);
                return $this;
            }
            if(strtolower($key)=='type'){
                $this->setType($value);
                return $this;
            }
            $this->attr[$key] = $value;
        }elseif($n==1){
            if(isset($this->attr[$key]))
                return $this->attr[$key];
            else
                return null;
        }
        return $this;
    }
    public function setName($name)
    {
        if($name){
            foreach($this->options as $item)
            {
                $item->attr('name',$name.'[]');
            }
            $this->_name = $name;
        }
        return $this;
    }
    public function setType($type)
    {
        $type = strtolower($type);
        if($type=='radio' || $type =='checkbox'){
            foreach($this->options as $item)
            {
                $item->setType($type);
            }
            $this->forcetype = $type;
        }
        return $this;
    }
    public function options()
    {
        if(func_num_args()==0)
            return $this->options;
        else
        {
            $nodeIndex=func_get_arg(0);
            return $this->options[$nodeIndex];
        } 
    }
    public function addOptions(Array $set, $useSetKeys=HTML_OPT_VALUEKEYS)
    {
        foreach($set as $k=>$label)
        {
            if($useSetKeys==HTML_OPT_VALUEKEYS){
                $this->addOption($label,$k);
            }elseif($useSetKeys==HTML_OPT_VALUELABEL){
                $this->addOption($label,$label);
            }else{
                $this->addOption($label);
            }
        }
        return $this;
    }
    public function add($innerHTML='')
    {
        return $this->addOption($innerHTML);
    }
    public function addOption($label,$value=null,$checked=false) 
    {
        $name = $this->_name?$this->_name.'[]':'';
        $el = new HTML_input_checkitem($this->forcetype);
        $el->name($name);
        if(func_num_args()==1 || (null ===$value)) $value = $label;
        $el->parent($this);
        $el->value($value);
        $el->label($label);
        if(is_scalar($label)){
            $el->label()->attr('title',$label);
        }
        $el->checked($checked);
        $this->options[] =$el;
        return $el;
    }
    public function value($value=null)
    {
        
        if(func_num_args()==0)
        {
            return $this->attr('value');
        }else
        {
            $this->attr['value'] = $value;
            return $this;
        }
    }
    public function selected($value=null){
        if(func_num_args()==0)
            return $this->checked();
        else
            return $this->checked($value);
    }
    public function checked($value=null)
    {
        $setThem = (func_num_args()>0);
        if($setThem){
            if(!is_array($value)) $value = explode(',',$value);
        }else
        {
            $r = array();
        }
        foreach($this->options as $item)
        {
            if($setThem){
                if(in_array($item->value(), $value))
                {
                    $item->checked(true);
                }
            }else {
                if($item->checked())
                    $r[] = $item;
            }
        }
        if($setThem){
            if(isset($this->attr['value'])){
                if(in_array(true, $value)||in_array($this->attr('value'), $value))
                    $this->attr['checked'] = true;
                else
                    $this->attr['checked'] = false;
            }else{
                $value = func_get_arg(0);
                $this->attr['checked'] = (bool)$value;
            }
            return $this;
        }else
            return $r;
    }
    public function __toString() {
        $r = array();
        $c = count($this->options);
        foreach($this->options as $item)
        {
            if((null !==$item->label())){
                $r[] = $item->label()->getOpenTag();
                $r[] =(string)$item;
                $r[] = $item->label()->innerHTML();
                $r[] = $item->label()->getCloseTag();
            }else{
                $r[] =(string)$item;
            }
            $r[] = $this->separator;
        }
        array_pop($r);
        
        if($c==0){
            $f =array('type'=>$this->forcetype);
            if($this->_name) $f['name'] =$this->_name; 
            $r[] = "<input";
            $r[] = $this->getAttributes($f) . ' />'; 
            return implode(' ',$r);
        }elseif($c==1){
            return implode($this->separator,$r) ;
        }else{
            $o = $f =array();
            $o[] = "<span";
            if(!isset($this->attr['class']))$f['class'] = $this->forcetype;
            $o[] = $this->getAttributes($f) .' >';
            $o[] =   implode(' ',$r);
            $o[] = '</span>';
            return  implode(' ',$o);
        }
    }
}