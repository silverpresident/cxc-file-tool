<?php
/**
 * @author Edwards
 * @copyright 2010
 * @version 20140830.1
 */

class HTML_details extends HTML_element
{
    protected $tag = 'details';
    public function summary($append=''){
        if($append instanceof HTML_summary){
            $el = $append;
            $this->nodes[] = $el;
        }else{
            $el = $this->create('summary');
            if($append)$el->append($append);
        }
        return $el;
    }
}