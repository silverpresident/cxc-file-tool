<?php
/**
 * @author Edwards
 * @copyright 2015
 */
class ELI_bitfield
{
    protected $flags = 0;
    public function __construct() {
        if(func_num_args()){
            $this->flags = (int) func_get_arg(0);
        }
    }
    public function has($flag)
    {
        $flag = array_sum(func_get_args());
        return (($this->flags & $flag) == $flag);
    }
    public function set($flag)
    {
        foreach(func_get_args() as $flag) $this->flags |=(int)$flag;
        return $this;
    }
    public function clear()
    {
        $this->flags =0;
        return $this;
    }
    public function reset($flag)
    {
        $flag = array_sum(func_get_args());
        $this->flags = $flag;
        return $this;
    }
    public function toggle($flag)
    {
        $flag = array_sum(func_get_args());
        $this->flags ^= $flag;
        return $this;
    }
    public function invert()
    {
        if(func_num_args())
            $this->toggle(func_get_arg(0));
        else
            $this->flags = ~$this->flags;
        return $this;
    }
    public function setBit($bit,$value=true)
    {
        $flag = (int)$bit;
        if($value)
        {
          $this->flags |= $flag;
        }
        else
        {
          $this->flags &= ~$flag;
        }
        return $this;
    }
    public function toValue()
    {
        return $this->flags;
    }
    public function toBinary($length=0)
    {
        $binary = decbin($this->flags);
        if($length && $length > strlen($binary)){
            return str_pad($binary, $length, '0', STR_PAD_LEFT);
        }
        return $binary;
    }
    public function __toString() {
        return $this->toValue();
    }
    public function __call($name, $arguments) {
        $name =strtolower($name);
        if($name=='unset' ||$name=='remove' ){
            $flag = array_sum($arguments);
            $this->flags &= ~$flag;
            return $this;
        }
    }



}
?>