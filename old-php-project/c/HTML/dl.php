<?php
/**
 * @author Edwards
 * @copyright 2015
 */
class HTML_dt extends HTML_element 
{
    protected $tag = 'dt';
}
class HTML_dd extends HTML_element 
{
    protected $tag = 'dd';
}
class HTML_dl extends HTML_element 
{
    protected $tag = 'dl';
    public function add($dt='',$dd=''){
        $elt = new HTML_dt($this);
        $eld = new HTML_dd($this);
        $elt->append($dt);
        $eld->append($dd);
        $this->nodes[]=$elt;
        $this->nodes[]=$eld;
        return array($elt,$eld);
    }
    public function create($object)
    {
        $el = HTML::build($object);
        $this->append($el);
        return $el;
    }
    public function prepend($value=''){
        if(is_array($value)){
            $value = array_reverse($value);
            $el = array();
            foreach($value as $item)
                $el[] = $this->prepend($item);
        }else{
            $elt = $eld = null;
            if(func_num_args()>1){
                $value2 = func_get_arg(1);
                if(($value2 instanceof HTML_dt) || ($value2 instanceof HTML_dd)){
                    $eld = $value;
                }else{
                    $eld = new HTML_dd($this,$value);
                }
            }
            
            if(($value instanceof HTML_dt) || ($value instanceof HTML_dd)){
                $elt = $value;
            }else{
                $elt = new HTML_dt($this,$value);
            }
            if($eld){
                parent::prepend($eld);
            }
            parent::prepend($elt);
            return array($elt,$eld);
        }
        return $el;
    }
    public function append($value='')
    {
        if(is_array($value)){
            $el = array();
            foreach($value as $item)
                $el[] = $this->append($item);
        }else{
            $elt = $eld = null;
            
            if(($value instanceof HTML_dt) || ($value instanceof HTML_dd)){
                $elt = $value;
            }else{
                $elt = new HTML_dt($this,$value);
            }
            
            if(func_num_args()>1){
                $value2 = func_get_arg(1);
                if(($value2 instanceof HTML_dt) || ($value2 instanceof HTML_dd)){
                    $eld = $value;
                }else{
                    $eld = new HTML_dd($this,$value);
                }
            }
            
            $this->nodes[]=$elt;
            if($eld){
                $this->nodes[]=$eld;
            }
            return array($elt,$eld);
        }
        return $el;
    }
    public function innerHTML($value=null)
    {
        if(func_num_args()) throw new Exception('Cannot set LIST innerHTML');
        $r= array();
        foreach($this->nodes as $li)
            $r[] = (string)$li;
        return implode("\n",$r);
    }
    public function __toString() {
        $r = array();
        $ih =   $this->innerHTML();
        $r[] = $this->getOpenTag();
        $r[] = $ih;
        $r[] = $this->getCloseTag(); 
        return implode('',$r);
    }
}