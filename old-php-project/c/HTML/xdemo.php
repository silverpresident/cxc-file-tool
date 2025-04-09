<?php
/**
 * @author Edwards
 * @copyright 2010
 * @version 20130501.1
 */
HTML::build('pre');
class HTML_xdemo extends HTML_pre
{
    public function getOpenTag()
    {
        $this->addClass('demo');
        return parent::getOpenTag();
    }
}