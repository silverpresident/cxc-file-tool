<?php

/**
 * @author Edwards
 * @copyright 2010
 */

class HTML_script extends HTML_element
{
    protected $tag = 'script';
    protected $_inner_join_with = "\n";
    public function src($url = '')
    {
        if (func_num_args()) {
            $a = func_get_args();
            $url = implode('/', $a);
            return $this->attr(__function__, $url);
        }
        return $this->attr(__function__ );
    }
    public function __toString()
    {
        $r = array();
        $r[] = $this->getOpenTag();
        $temp = $this->innerHTML();
        $r[] = $temp;
        $r[] = $this->getCloseTag();
        $sep = ($temp == '') ? '' : "\n";
        return implode($sep, $r);
    }
    public function append($value = '')
    {
        if ($n = func_num_args()) {
            if ($n > 1) {
                foreach (func_get_args() as $v){
                    $this->append($v);
                }
            } elseif (is_array($value)) {
                foreach ($value as $v){
                    $this->append($v);
                }
            } else {
                $this->nodes[] = $value;
            }
        }
        return $this;
    }
}
