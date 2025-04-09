<?php
/**
 * @author Edwards
 * @copyright 2010
 */
defined('HTML_OPT_VALUENONE') OR define('HTML_OPT_VALUENONE',0);
defined('HTML_OPT_VALUEKEYS') OR define('HTML_OPT_VALUEKEYS',1);
defined('HTML_OPT_VALUELABEL') OR define('HTML_OPT_VALUELABEL',2);
HTML::loadapi('option');
class HTML_select_option extends HTML_option
{
    protected $tag = 'option';
}
class HTML_select_optgroup extends HTML_element
{
    protected $tag = 'optgroup';
    public static function buildOption()
    {
        return new HTML_option();
    }
    public function add($element=null) 
    {
        if(func_num_args()){
            if($element instanceof HTML_option){
                $this->nodes[] =$element;
                return $element;
            }else{
                return $this->addOption($element);
            }
        }
        return $this->addOption();
    }
    public function addOptions(Array $set, $useSetKeys=HTML_OPT_VALUEKEYS)
    {
        if(func_num_args()==2 && is_bool($useSetKeys)){
            $useSetKeys = $useSetKeys?HTML_OPT_VALUEKEYS:HTML_OPT_VALUELABEL;
        }
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

    public function addOption($label='',$value=null,$selected=false,$atTop=false)
    {
        $el = new HTML_option($this);
        if($n = func_num_args())   $el->append($label);
        if($n>1) $el->value($value);
        if($n>2) $el->selected($selected);
        if($n>3 && $atTop){
            array_unshift($this->nodes,$el);
        }else{
            $this->nodes[] =$el;
        }
        return $el;
    }
    public function addOptionAtTop($label='',$value=null,$selected=false) 
    {
        return $this->addOption($label,$value,$selected,true);
    }
    public function prepend($label='')
    {
        if( ($label instanceof HTML_option))
        {
            parent::prepend($label);
            return $this;
        }else{
            $el = new HTML_option($this);
            $el->append($label);
            if(func_num_args()>1) $el->value(func_get_arg(1));
            if(func_num_args()>2) $el->selected(func_get_arg(2));
            parent::prepend($el);
            return $el;
        }
    }
    public function append($label='')
    {
        if( ($label instanceof HTML_option))
        {
            $this->nodes[] =$label;
            return $this;
        }else{
            $el = new HTML_option($this);
            $el->append($label);
            if(func_num_args()>1) $el->value(func_get_arg(1));
            if(func_num_args()>2) $el->selected(func_get_arg(2));
            $this->nodes[] =$el;
            return $el;
        }
    }
    public function options()
    {
        return $this->nodes;
    }
    public function innerHTML($value=null)
    {
        $r = array();
        foreach($this->nodes as $item)
        {
            $r[] = (string)$item;
        }
        return implode("\n  ",$r);
    }
    
}
class HTML_select extends HTML_element_nameable
{
    protected $tag = 'select';
    public static function buildOption()
    {
        return new HTML_option();
    }
    public static function buildOptGroup()
    {
        return new HTML_select_optgroup();
    }
    public function options()
    {
        return $this->nodes;
    }
    public function type()
    {
        return $this->attr('multiple')?'select-multiple':'select-one';
    }
    public function item($nodeIndex=0)
    {
        if(func_num_args()==0)
            return $this->nodes;
        else
        {
            return $this->nodes[$nodeIndex];
        } 
    }
    public function addOptionGroup($label){
        return $this->addOptGroup($label);
    }
    public function addOptGroup($label) 
    {
        $el = new HTML_select_optgroup($this);
        $el->label($label);
        $this->nodes[] =$el;
        return $el;
    }
    public function addOption($label='',$value=null,$selected=false,$atTop=false) 
    {
        $el = new HTML_option($this);
        if($n = func_num_args())   $el->append($label);
        if($n>1) $el->value($value);
        if($n>2) $el->selected($selected);
        if($n>3 && $atTop){
            array_unshift($this->nodes,$el);
        }else{
            $this->nodes[] =$el;
        }
        return $el;
    }
    public function addOptionAtTop($label='',$value=null,$selected=false) 
    {
        return $this->addOption($label,$value,$selected,true);
    }
    public function addOptions(Array $set, $useSetKeys=HTML_OPT_VALUEKEYS)
    {
        if(func_num_args()==2 && is_bool($useSetKeys)){
            $useSetKeys = $useSetKeys?HTML_OPT_VALUEKEYS:HTML_OPT_VALUELABEL;
        }
        foreach($set as $k=>$label)
        {
            if($useSetKeys==HTML_OPT_VALUEKEYS){
                if (is_array($label)){
                    $og = $this->addOptGroup($k);
                    $og->addOptions($label,$useSetKeys);
                    $r[] = $og;
                } else{
                    $r[] = $this->addOption($label,$k);
                }
            }elseif($useSetKeys==HTML_OPT_VALUELABEL){
                $this->addOption($label,$label);
            }else{
                $this->addOption($label);
            }
        }
        return $this;
    }
    public function add($element='') 
    {
        if(func_num_args()){
            if($element instanceof HTML_select_optgroup || $element instanceof HTML_option){
                $this->nodes[] =$element;
                return $element;
            }else{
                return $this->addOption($element);
            }
        }
        return $this->addOption();
    }
    public function prepend($label='')
    {
        if(($label instanceof HTML_select_optgroup) || 
            ($label instanceof HTML_option))
        {
            parent::prepend($label);
            return $this;
        }else{
            $el = new HTML_option($this);
            if($label) $el->append($label);
            if(func_num_args()>1) $el->value(func_get_arg(1));
            if(func_num_args()>2) $el->selected(func_get_arg(2));
            parent::prepend($el);
            return $this;
        }
    }
    public function append($label='')
    {
        if( ($label instanceof HTML_select_optgroup) || $label instanceof HTML_option)
        {
            $this->nodes[] =$label;
            return $this;
        }else{
            $el = new HTML_option($this);
            $el->append($label);
            if(func_num_args()>1) $el->value(func_get_arg(1));
            if(func_num_args()>2) $el->selected(func_get_arg(2));
            $this->nodes[] =$el;
            return $this;
        }
    }
    public function defaultValue($value=null)
    {
        if(func_num_args()==0)
        {
            $r=array();
            $item =$this->selected();
            if(count($item)){
                foreach($item as $i)
                    $r[]=$i->value();
            }else{
                if(count($this->nodes)){
                    $item = $this->nodes[0];
                    if($item instanceof HTML_select_optgroup)
                    {
                        $x = $item->options();
                        if(count($x)) 
                            $item = $x[0];
                        else
                            return null;
                    }
                    $r[]=$item->value();
                }else
                    return null;
            }
            return implode(",",$r); 
        }else
        {
            $this->selected($value);
            return $this;
        }
    }
    /**
     * HTML_select::value()
     * 
     * @param mixed $value
     * @return comm separed list of values
     */
    public function value($value=null)
    {
        if(func_num_args()==0)
        {
            $item =$this->selected();
            if(count($item)==0) return null;
            
            $r=array();
            foreach($item as $i)
                $r[]=$i->value();
            
            return implode(",",$r); 
        }else
        {
            $this->selected($value);
            return $this;
        }
    }
    public function checked($value=null){
        if(func_num_args()==0)
            return $this->selected();
        else
            return $this->selected($value);
    }
    public function hasValue($value=null)
    {
        foreach($this->nodes as $item)
        {
            if($item instanceof HTML_select_optgroup)
            {
                $x = $item->options();
                foreach($x as $it)
                {
                    if($it->value() == $value) return true;
                }
            }else
            {
                if($item->value() == $value) return true;
            }
        }
        return false;
    }
    /**
     * HTML_select::selected()
     * 
     * @param mixed $value
     * @return array of HTML_option
     */
    public function selected($value=null)
    {
        $setThem = (func_num_args()>0);
        if($setThem){
            if(!is_array($value)) $value = explode(',',$value);
            $multiple = $this->multiple();
        }else{
            $r = array();
        }
            
        foreach($this->nodes as $item)
        {
            if($item instanceof HTML_select_optgroup)
            {
                $x = $item->options();
                foreach($x as $it)
                {
                    if($setThem){
                        if(in_array($it->value(), $value))
                        {
                            $it->selected(true);
                        }else
                            if(!$multiple)$it->selected(false);
                    }else
                    {
                        if($it->selected())$r[] = $it;
                    }
                }
            }else
            {
                if($setThem){
                    if(in_array($item->value(), $value))
                    {
                        $item->selected(true);
                    }else
                        if(!$multiple)$item->selected(false);
                } else
                {
                    if($item->selected())
                        $r[] = $item;
                }
            }
        }
        if($setThem) 
            return $this;
        else
            return $r;
    }
    public function innerHTML($value=null)
    {
        $r = array();
        foreach($this->nodes as $item)
        {
            $r[] = (string)$item;
        }
        return implode("\n",$r);
    }
}