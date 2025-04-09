<?php

/**
 * @author Edwards
 * @copyright 2010
 */

class HTML_pre extends HTML_element
{
    protected $tag = 'pre';
    public function append($value = null)
    {
        if ($n = func_num_args()) {
            if ($n > 1) {
                foreach (func_get_args() as $v) {
                    $this->append($v);
                }
            } /*elseif (is_array($value)) {
                foreach ($value as $v) {
                    $this->append($v);
                }
            }*/ else {
                if (is_scalar($value)) {
                    $this->nodes[] = $value;
                } else {
                    $this->nodes[] = print_r($value, 1);
                }
            }
        }
        return $this;
    }
}
