<?php
/**
 * @author Edwards
 * @copyright 2015
 */

class x_html_pattern{
    /** Capital letter followed by Letters, apostrophe and hyphen */
    const HTML_PATTERN_NAME = "^[A-Z]+[A-z\&apos;\-]*$";
    /** Capital letter followed by Letters, apostrophe, hyphen and space*/
    const HTML_PATTERN_FULLNAME = "^[A-Z]+[A-z\&apos;\-\s]*$";
    /** Letters, numbers and hyphen */
    const HTML_PATTERN_CODE = '^[A-z\d\-]*$';
    /** Letters followed by Letters, numbers, dot and hyphen */
    const HTML_PATTERN_USERNAME = '^[A-z]+[A-z\d\.\-]*$';
    /** Letters and space*/
    const HTML_PATTERN_APLHA = '^[A-z\s]*$';
    /** Letters  space and numbers */
    const HTML_PATTERN_APLHANUM = '^[A-z\d\s]*$';
    //const HTML_PATTERN_WORD = '^[A-z]*$';
    //const HTML_PATTERN_WORDNUM = '^[\w]*$';
    //const HTML_PATTERN_NAME = '';
    //const HTML_PATTERN_NAME = '';
    //var date = /^(\d{1,2})[\-/](\d{1,2})[\-/](\d{4})$/,
    //time = /^(\d{1,2})\:(\d{1,2})\:(\d{1,2})$/,
	//'unsigned': /^\d+$/,
	//'integer' : /^[\+\-]?\d*$/,
	//'real'    : /^[\+\-]?\d*\.?\d*$/,
	//'email'   : /^[\w-\.]+\@[\w\.-]+\.[a-z]{2,4}$/,
	//'phone'   : /^[\d\.\s\-]+$/,
    public function __get($name) {
        if(method_exists($this,$name)){
            return $this->$name();
        }
        if(property_exists($this,$name)){
            return $this->$name;
        }
        $name =strtoupper($name);
        if(defined("static::$name")){
            return constant("static::$name");
        }
    }
    public function __call($name, $arguments) {
        return $this->__get($name);
    }
    public static function __callStatic($name, $arguments) {
        if(method_exists(__CLASS__,$name)){
            return self::$name();
        }
        $name =strtoupper($name);
        if(defined("static::$name")){
            return constant("static::$name");
        }
    }


}

class x_html_const{
    protected $enc_plain = 'text/plain';
    protected $enc_formdata = 'multipart/form-data';
    protected $enc_urlencode = 'application/x-www-form-urlencoded';
    protected $enc_filedata = 'multipart/form-data';
    
    
    public function __get($name) {
        if(method_exists($this,$name)){
            return $this->$name();
        }
        if(property_exists($this,$name)){
            return $this->$name;
        }
        return null;
    }
    public function __call($name, $arguments) {
        return $this->__get($name);
    }
    public static function __callStatic($name, $arguments) {
        if(method_exists(__CLASS__,$name)){
            return self::$name();
        }
    }


}