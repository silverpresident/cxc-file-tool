<?php
/**
 * @author Edwards
 * @copyright 2010
 */

class HTML_head extends HTML_element
{
    protected $tag = 'head';
    protected $charset = '';
    protected $_inner_join_with ="\n";
    public function __toString() {
        $r = array();
        $r[] = $this->getOpenTag();
        $temp = $this->innerHTML();
        $r[] = $temp;
        $r[] = $this->getCloseTag();
        $sep = ($temp=='')?'':"\n";  
        return implode($sep,$r);
    }
    public function addMeta($name='', $content='') {
        $m = $this->create('meta');
        if(func_num_args()) $m->name($name);
        if(func_num_args()>1) $m->content($content);
        return $m;
    }
    public function addLink($href='',$rel='') {
        $m = $this->create('link');
        if(func_num_args()) $m->href($href);
        if(func_num_args()>1) $m->rel($rel);
        return $m;
    }
    public function addScript($href='') {
        $m = $this->create('script');
        if(func_num_args()) $m->src($href);
        return $m;
    }
    public function charset($charset='') {
        if(func_num_args()){
            $this->charset = (string)$charset;
            return $this;
        }
        return $this->charset;
    }
    public function innerHTML($value = null)
    {
        if(func_num_args()==0){
            $r = array();
            if($this->charset){
                $r[] = "<meta charset='$this->charset' />";
            }
            foreach($this->nodes as $i)
                $r[] = (string)$i;
            return trim(implode($this->_inner_join_with,$r));
        }
        if(is_array($value))
            $this->nodes = $value;
        else 
            $this->nodes = array($value);
        return $this;
    }
}