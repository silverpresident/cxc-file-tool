<?php
/**
 * @author Edwards
 * @copyright 2010
 */

class HTML_comment extends HTML_element
{
    protected $tag = '!--';
    protected $nodes  = array();
    public $inScript = false;
    public function innerHTML($value = null)
    {
        if(func_num_args()==0){
            $r = array();
            foreach($this->nodes as $i)
                $r[] = (string)$i;
            $temp = implode('',$r);
            return str_replace(array('--','>'),array('-- ',' >'),$temp);
        }
        if(is_array($value))
            $this->nodes = $value;
        else 
            $this->nodes = array($value);
        return $this;
    }
    public function getOpenTag()
    {
        return "<!--";
    }
    public function getCloseTag()
    {
        if($this->inScript)
            return " //-->";//to prevent script problem
        else
            return "-->";
    }
}