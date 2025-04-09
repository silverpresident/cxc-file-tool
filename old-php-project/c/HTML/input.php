<?php
/**
 * @author Edwards
 * @copyright 2010
 */
class HTML_input_generic extends HTML_element_nameable{
    protected $tag = 'input';
    protected $forcetype = '';
    protected $attr  = array('type'=>'text');
    protected function isVoidElement() { return true; }
    public function __construct() {
        if(func_num_args() && func_get_arg(0)){
            $param = func_get_arg(0);
            if($param instanceof HTML_element )
                $this->parent = $param;
            elseif(is_scalar($param)){
                $this->forcetype = $param;
                $this->attr('type',$this->forcetype);
            }
        }
    }
    public function setType($type)
    {
        $type = strtolower($type);
        $this->forcetype = $type;
        return $this;
    }
    public function __toString() {
        $r = array();
        $a = (!empty($this->forcetype))?array('type'=>$this->forcetype):'';
        
        $r[] = "<input";
        $r[] = $this->getAttributes($a) . '>'; 
        return implode(' ',$r);
    }
    public function value($value=null)
    {
        if(func_num_args()==0)
            return $this->attr(__FUNCTION__);
        else{
            return $this->attr(__FUNCTION__,$value);
        }
    }
}
class HTML_input extends HTML_input_generic
{
    static function build($type,$name='')
    {
        return HTML::input($type,$name);
    }
}



class HTML_input_text extends HTML_input
{
    //TODO deprecate for HTML::getPattern()
    
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
    protected $forcetype = 'text';
}
class HTML_input_password extends HTML_input_text
{
    protected $forcetype = 'password';
    private $showToggle = false;
    public function showToggle($value=true)
    {
        $key = __FUNCTION__;
        if((null ===$value))
            return $this->$key;
        else
        {
            $this->$key = $value;
            return $this;
        }
    }
    public function __toString() {
        $r = array();
        
        $id = $this->id();
        if($this->showToggle && empty($id))
        {
            $n = $this->name();
            $this->id("id_$n");
            $id = $this->id();
        }
        $r[] = parent::__toString();
        if($this->showToggle)
        {
            $r[] = "<span class='form-pw-unmask' title='Unmask password'><input type='checkbox' onClick=\"if(this.checked)
            {this.form.$id.type='text'}else{this.form.$id.type='password';}
            \"/>*</span>";
        } 
        return implode(' ',$r);
    }
    
}
/*class HTML_input_email extends HTML_input_text
{
    protected $forcetype = 'email';
}
class HTML_input_url extends HTML_input_text
{
    protected $forcetype = 'url';
}
class HTML_input_tel extends HTML_input_text
{
    protected $forcetype = 'tel';
}
class HTML_input_search extends HTML_input_text
{
    protected $forcetype = 'search';
}


class HTML_input_number extends HTML_input
{
    protected $forcetype = 'number';
    public function max($value=null)
    {
        if(func_num_args()==0)
            return $this->attr(__FUNCTION__);
        else{
            return $this->attr(__FUNCTION__,$value);
        }
    }
    public function min($value=null)
    {
        if(func_num_args()==0)
            return $this->attr(__FUNCTION__);
        else{
            return $this->attr(__FUNCTION__,$value);
        }
    }
    public function step($value=null)
    {
        if(func_num_args()==0)
            return $this->attr(__FUNCTION__);
        else{
            return $this->attr(__FUNCTION__,$value);
        }
    }
}
class HTML_input_range extends HTML_input_number
{
    protected $forcetype = 'range';
}


class HTML_input_color extends HTML_input
{
    protected $forcetype = 'color';
}

class HTML_input_file extends HTML_input
{
    protected $forcetype = 'file';
    public function accept($value=null)
    {
        if(func_num_args()==0)
            return $this->attr(__FUNCTION__);
        else{
            return $this->attr(__FUNCTION__,$value);
        }
    }
}

class HTML_input_hidden extends HTML_input
{
    protected $forcetype = 'hidden';
}
*/
    /*date - Selects date, month and year
month - Selects month and year
week - Selects week and year
time - Selects time (hour and minute)
datetime - Selects time, date, month and year (UTC time)
datetime-local - Selects time, date, month and year (local time)

text area
select
*/
#dates
/*class HTML_input_date extends HTML_input
{
    protected $forcetype = 'date';
    
}
class HTML_input_time extends HTML_input_date
{
    protected $forcetype = 'time';
}
class HTML_input_datetime extends HTML_input_date
{
    protected $forcetype = 'datetime';
}
class HTML_input_week extends HTML_input_date
{
    protected $forcetype = 'week';
}
class HTML_input_year extends HTML_input_date
{
    protected $forcetype = 'year';
}
class HTML_input_month extends HTML_input_date
{
    protected $forcetype = 'month';
}
class HTML_input_datetimelocal extends HTML_input_date
{
    protected $forcetype = 'datetime-local';
}
*/
#form sender
class HTML_input_formsender extends HTML_input
{
    protected $forcetype = 'submit';
    public function pattern($value=null){}
    public function formenctype($value=null)
    {
        if(func_num_args()==0)
            return $this->attr(__FUNCTION__);
        else{
            $l=strtolower($value);
            if(in_array($l,array('text/plain','multipart/form-data','application/x-www-form-urlencoded'))){
                $this->attr[__FUNCTION__] = $l;
                return $this;
            }elseif(empty($value))
                return $this->attr(__FUNCTION__,$value);
        }
    }
    public function formtarget($value=null)
    {
        if(func_num_args()==0)
            return $this->attr(__FUNCTION__);
        else{
            
            $l=strtolower($value);
            if(in_array($l,array('_blank','_self','_parent','_top')))
            {
                $this->attr[__FUNCTION__] = $l;
                return $this;
            }else
                return $this->attr(__FUNCTION__,$value);
        }
    }
    public function formmethod($value='POST'){
        if(func_num_args()==0){
            return $this->attr(__FUNCTION__);
        }else{
            $value=strtoupper($value);
            if(in_array($value,array('POST','GET')))
                $this->attr(__FUNCTION__,$value);
            
            return $this;
        }
    }
    public function formaction($url='')
    {
        if(func_num_args()){
            $a = func_get_args();
            $url = implode('/',$a);
            return $this->attr(__FUNCTION__,$url);
        }
        return $this->attr(__FUNCTION__);
    }
    /*::image and submit
     -formnovalidate
     */
}
class HTML_input_submit extends HTML_input_formsender
{
    protected $forcetype = 'submit';
}
class HTML_input_image extends HTML_input_formsender
{
    protected $forcetype = 'image';
    public function src($url='')
    {
        if(func_num_args()){
            $a = func_get_args();
            $url = implode('/',$a);
            return $this->attr(__FUNCTION__,$url);
        }
        return $this->attr(__FUNCTION__);
    }
}
class HTML_input_button extends HTML_input_formsender
{
    protected $forcetype = 'button';
}
class HTML_input_reset extends HTML_input
{
    protected $forcetype = 'reset';
}

$f =__DIR__ . DIRECTORY_SEPARATOR . "inputRC.php";
include_once($f);
