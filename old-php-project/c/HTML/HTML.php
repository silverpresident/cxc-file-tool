<?php
/**
 * @author Edwards
 * @copyright 2013
 * @version 20181224
 */


defined('HTML_ENC_PLAIN') OR define('HTML_ENC_PLAIN','text/plain');
defined('HTML_ENC_FORMDATA') OR define('HTML_ENC_FORMDATA','multipart/form-data');
defined('HTML_ENC_URLENCODE') OR define('HTML_ENC_URLENCODE','application/x-www-form-urlencoded');
defined('HTML_ENC_FILEDATA') OR define('HTML_ENC_FILEDATA','multipart/form-data');
 
HTML::loadApi('element');
//HTML::loadApi('link');

class HTML_docType{
    protected $type='html';
    protected $domain ='';
    protected $lang='EN';
    protected $flavor ='';
    protected $dtd='';
    protected $dtdversion=null;
    protected $dtdurl='';
    
    public function setHTML5(){
        $this->flavor = '';
        $this->type = 'html';
        $this->domain = '';
        $this->lang = '';
        $this->dtd ='';
        $this->dtdversion = 5;
        $this->dtdurl='';
    }
    public function setHTML($type='STRICT',$version='4.01'){
        $type = trim(strtoupper($type));
        
        $this->type = 'html';
        $this->flavor = $type;
        $this->domain = 'PUBLIC';
        $this->dtdversion = $version;
        $this->lang = 'EN';
        if($type == 'TRANSITIONAL'||$type == 'LOOSE'){
            $this->dtdurl='http://www.w3.org/TR/html4/loose.dtd';
            $this->dtd ="-//W3C//DTD HTML {$this->dtdversion} Transitional//EN";
            $this->type = 'HTML';
        }elseif($type == 'FRAMESET'){
            $this->dtdurl='http://www.w3.org/TR/html4/frameset.dtd';
            $this->dtd ="-//W3C//DTD HTML {$this->dtdversion} Frameset//EN";
            $this->type = 'HTML';
        }elseif($this->dtdversion == 2){
            $this->dtdurl='';
            $this->dtd ="-//IETF//DTD HTML 2.0//EN";
        }elseif($this->dtdversion == 3.2){
            $this->dtdurl='';
            $this->dtd ="-//W3C//DTD HTML 3.2 Final//EN";
        }elseif($this->dtdversion >= 5 || $type =='HTML5'){
            $this->setHTML5();
            $this->flavor = $type;
        }else{
            $this->type = 'HTML';
            $this->dtdurl='http://www.w3.org/TR/html4/strict.dtd';
            $this->dtd ="-//W3C//DTD HTML {$this->dtdversion}//EN";
        }
        return $this;
    }
    public function setXHTML($type='',$version=null){
        $type = trim(strtoupper($type));
        $this->flavor = $type;
        $this->type = 'html';
        $this->domain = 'PUBLIC';
        $this->lang = 'EN';
        $this->dtdversion = $version;
        
        if($type=='' && func_num_args()<2)$this->dtdversion = '1.1';
        if($type == 'TRANSITIONAL'||$type == 'LOOSE'){
            if((null ===$version)) $this->dtdversion = '1.0';
            $this->dtdurl='http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd';
            $this->dtd ="-//W3C//DTD XHTML {$this->dtdversion} Transitional//EN";
        }elseif($type == 'FRAMESET'){
            if((null ===$version)) $this->dtdversion = '1.0';
            $this->dtdurl='http://www.w3.org/TR/xhtml1/DTD/xhtml1-frameset.dtd';
            $this->dtd ="-//W3C//DTD XHTML {$this->dtdversion} Frameset//EN";
        }elseif($type == 'STRICT'){
            if((null ===$version)) $this->dtdversion = '1.0';
            $this->dtdurl='http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd';
            $this->dtd ="-//W3C//DTD XHTML {$this->dtdversion} Strict//EN";
        }elseif($type == 'BASIC'){
            if((null ===$version)) $this->dtdversion = '1.1';
            if($this->dtdversion ==1){
                $this->dtdversion = '1.0';
                $this->dtdurl= 'http://www.w3.org/TR/xhtml-basic/xhtml-basic10.dtd';
            }else{
                $this->dtdurl='http://www.w3.org/TR/xhtml-basic/xhtml-basic11.dtd';
            }
            $this->dtd ="-//W3C//DTD XHTML Basic {$this->dtdversion}//EN";
        }else{
            if((null ===$version)) $this->dtdversion = '1.1';
            $this->dtdurl='http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd';
            $this->dtd ="-//W3C//DTD XHTML {$this->dtdversion}//EN";
        }
        return $this;
    }
    public function __call($name, $arguments) {
        if(method_exists($name,$this)){
            if(!isset($arguments[0]))
                return $this->$name();
            else{
                return call_user_func_array(array($this,$name),$arguments);
            }
        }
        $name2 = 'set'.$name;
        if(method_exists($name,$this)){
            return call_user_func_array(array($this,$name2),$arguments);
        }
        $name2 =strtolower($name);
        
        if(property_exists($this,$name2)){
            if(!isset($arguments[0]))
                return $this->$name2;
            else{
                $this->$name2 = $arguments[0];
                return $this;
            }
        }
        return $this;
    }
    /*public function type() {
        $key = __FUNCTION__;
        if(func_num_args()){
            $value = func_get_arg(0);
            $this->$key = $value;
            return $this;
        }else{
            return $this->$key;
        }
    }*/
    public function __toString() {
        $a=array();
        $a[] = $this->type;
        if($this->domain) $a[] = $this->domain;
        if($this->dtd) $a[] = '"' . $this->dtd . '"';
        if($this->dtdurl) $a[] = '"' . $this->dtdurl . '"';
        
        return trim(implode(' ',array_filter($a)));
    }
     
}
class HTML_html extends HTML_element
{
    protected $tag = 'html';
    protected $head ;
    protected $body ;
    public $doctype = 'html';
    //TODO: implement doctype class
    public function docType($dtype = 'html'){
        $this->doctype = $dtype;
    }
    public function __construct() {
        $this->head = HTML::build('head');
        $this->nodes[] = $this->head;
        $this->body = HTML::build('body');
        $this->nodes[] = $this->body;
    }
    public function head() {
        return $this->head ;
    }
    public function body() {
        return $this->body;
    }
    public function create($object)
    {
        $object = strtolower($object);
        if($object=='head') return $this->head;
        if($object=='body') return $this->body;
        if(in_array($object, array('link','title','base','meta','script','style'))){
            return $this->head->create($object);
        }
        $el = HTML::build($object);
        $el->parent($this);
        $this->nodes[] = $el;
        return $el;
    }
    public function append($innerHTML=''){
        return $this->body->append($innerHTML);
    }
    public function __toString() {
        $r = array();
        $d = (empty($this->doctype))?'':' ' . trim($this->doctype);
        $r[] = "<!DOCTYPE{$d}>";
        $r[] = $this->getOpenTag();
        $r[] = $this->innerHTML();
        $r[] = '</html>';
        return implode("\n",$r);
    }
}

class HTML
{
    static $HTML_VERSION = 5;
    const VERSION = 3;
    static function loadapi($name)
    {
        $f =__DIR__ . DIRECTORY_SEPARATOR . "{$name}.php";
        if(file_exists($f)) include_once($f);
    }
    static function create($object,$name=''){
        return self::build($object,$name);
    }
    static function build($object,$name='')
    {
        $object=strtolower(trim($object));
        if(in_array($object,array('ul','ol','li')))
            self::loadapi('list');
        elseif(in_array($object,array('link','a','area')))
            self::loadapi('link');
        elseif(in_array($object,array('dd','dt','dl')))
            self::loadapi('dl');
        elseif(in_array($object,array('td','th','tr','thead','tfoot','tbody','caption','col','colgroup')))
            self::loadapi('table');
        else
            self::loadapi($object);
        
        static $input = array(/*'button',*/'checkbox','color','date','datetime','datetimelocal','datetime-local','email','file','hidden','image','month','number','password','radio','range','reset','search','submit','tel','text'/*,'time'*/,'url','week','year');
        if(in_array($object,$input)){
            return self::input($object,$name);
        }
        
        if($object=='input-button' || $object=='inputbutton'){
            return self::input('button',$name);
        }
        if($object=='submit-button' || $object=='submitbutton'){
            $el = self::build('button');
            $el->type('submit');
            if($name) $el->name($name);
            return $el;
        }
        $attrClass ='';
        if(!preg_match('/^[a-z][a-z0-9-]*$/i',$object)){
            $attrClass = $object;
            $object = 'div';
            self::loadapi($object);
        }
        if (strpos($object,'-')){
            $el =  new HTML_element_generic($object);
        } else {
            $class = __CLASS__ . '_' . $object;
            if(class_exists($class,false))
            {
                $el =  new $class();
            }else{
                $el =  new HTML_element_generic($object);
            }
        }
        if($name){
            if(method_exists($el,'name'))
                $el->name($name);
            else
                $el->id($name);
        }
        if($attrClass){
            $el->class($attrClass);
        }
        if(func_num_args()==3){
            $value = func_get_arg(2);
            $el->append($value);
        }
        return $el;
    }
    
    static function input($type,$name='')
    {
        HTML::loadApi('input');
        $object=strtolower($type);
        if(in_array($object,array('select','textarea'))){
            $el = HTML::build($object,$name);
            if(func_num_args()>2){
                $value = func_get_arg(2);
                $el->value($value);
            }
            return $el;
        }
        if($object=='datetime'){
             $object = 'datetimelocal';
             $type = 'datetime-local';
        }
        if($object=='datetime-local') $object = 'datetimelocal';    
        $class = __CLASS__ . '_input_' . $object;
        if(class_exists($class,false))
        {
            $el =  new $class();
        }else{
            $el =  new HTML_input($type);
        }
        if($name){
            $el->name($name);
        }
        if(func_num_args()>2){
            $value = func_get_arg(2);
            $el->value($value);
        }
        return $el;
    }
    static function createFragment($name=''){
        return self::buildFragment($name);
    }
    static function buildFragment($name='')
    {
        $object= 'xcontainer';
        self::loadapi($object);
        $class = __CLASS__ . '_' . $object;
        
        if(class_exists($class,false))
        {
            $el =  new $class();
        }else{
            $el =  new HTML_element_generic($object);
        }
        
        if($name)$el->id($name);
        return $el;
    }
    static function isInputType($object){
        if(self::$HTML_VERSION>=5)
            $input = array('button','checkbox','color','date','datetime','datetimelocal',
            'datetime-local','email','file','hidden','image','month','number','password',
            'radio','range','reset','search','submit','tel','text','time','url',
            'week','year');
        else
            $input = array('button','checkbox','file','hidden','image','password','radio',
            'reset','submit','text','year');
        
        return in_array($object,$input);
    }
    
    static function getPattern(){
        static $cg = null;
        if($cg===null){
            self::loadapi('_pattern');
            $cg = new x_html_pattern();
        }
        return $cg;
    }
    static function getConstants(){
        static $cg = null;
        if($cg===null){
            self::loadapi('_pattern');
            $cg = new x_html_const();
        }
        return $cg;
    }
}