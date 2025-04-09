<?php
/**
 * @author Edwards
 * @copyright 2015
 *  * 
 * 
 * 
 */
namespace ELIX;
class log_null{
    public function __call($name, $arguments) {
        return false;
    }
    public function __get($name) {
        return false;
    }
}
class log_dummy{
    private $parent,$tag;
    public function __construct($parent,$tag='') {
        $this->parent = $parent;
        $this->tag = $tag;
    }
    public function __call($name, $arguments) {
        $this->parent->log("DUMMY LOG| $this->tag::$name() => ",  $arguments);
    }
}
class log
{
    static protected $VERSION = 1;
    static public function version(){return static::$VERSION ;}
    
    protected $path = null;
    protected $write = 0;
    public $autohead = true;
    
    public function __call($name, $arguments) {
        $name = strtolower($name);
        
        //all these depend on an existing file
        if(empty($this->path)) return null;
        
        switch($name)
        {
            case 'file_exists':
            case 'exists': return file_exists($this->path);
            case 'mtime': return filemtime($this->path);
        }
    }
    public function __get($name) {
        $name = strtolower($name);
        switch($name)
        {
            case 'file_exists':
            case 'exists': return $this->exists();
            case 'file':
            case 'filename':
            case 'filepath': 
            case 'path': return $this->path;
            case 'mtime': return $this->mtime();
        }
    }
    public function __construct() {
        if(func_num_args()){
            $this->setFile(func_get_arg(0));
        }
    }
    public function getNullLogger() {
        return new log_null;
    }
    
    public function getDummyLogger($tag='') {
        return new log_dummy($this,$tag);
    }
    function setFile($file) {
        if($this->path != $file){
            $this->path = $file;
        }
        return $this;
    }
    function delete() {
        if(empty($this->path)) return null;
        
        if(file_exists($this->path)){
            @unlink($this->path);
        }
        return false;
    }
    private function lwrite($var) {
        $this->write++;
        if($this->autohead && ($this->write==1)){
            $this->head();
        }
        $s = str_repeat('  ',count($this->groups));
        if($fp = @fopen($this->path,'a')){
            @flock($fp, LOCK_EX);
            @fwrite($fp,date('Y-m-d G:i:s '));
            if(is_array($var)){
                foreach($var as $v)
                    @fwrite($fp,$s . (string)$v . PHP_EOL);
            }else{
                $var =(string)$var;
                @fwrite($fp,"{$s}{$var}" . PHP_EOL);
            }
            @flock($fp, LOCK_UN);
            @fclose($fp);
        }else{
            $this->errors[] = "Could not open file: $this->path";
        }
    }
    function error_log(){
        $data = call_user_func_array(array($this,'llog'),func_get_args());
        error_log($data);
    }
    function dump($var) {
        $data = '';
        foreach(func_get_args() as $a) $data.= var_export($a,1). "\n";
        $this->lwrite(explode("\n",$data));
    }
    function printr($var) {
        $data = '';
        foreach(func_get_args() as $a) $data.= print_r($a,1). "\n";
        $this->lwrite(explode("\n",$data));
    }
    protected $groups = array();
    function group() {
        $name ='';
        if(func_num_args()){
            $name = implode(' ',func_get_args());
        }
        if($name)$this->lwrite($name);
        $this->groups[] = $name;
    }
    function groupEnd() {
        if(!count($this->groups)) return; 
        $name = array_pop($this->groups);
        if($name)$this->lwrite('//-- END OF GROUP[' . $name.']');
    }
    function loc($file =null,$line =null) {
        if(func_num_args()==0){
            $bt = debug_backtrace();
             $caller = array_shift($bt);
             $file = $caller['file'];
             $line = $caller['line'];
        }
        $data = "line $line in $file";
        $this->lwrite($data);
    }
    function head() {
        $data = '';
        $data .= (isset($_SERVER["HTTP_HOST"]))?$_SERVER["HTTP_HOST"]:'no-host';
        if(isset($_SERVER["REQUEST_URI"])) $data .= "\n".$_SERVER["REQUEST_URI"];
        if(isset($_SERVER["HTTP_REFERER"])) $data .= '[' . $_SERVER["HTTP_REFERER"] . ']';
        if(func_num_args()){
             $data .= "\n" . implode(' ',func_get_args());
        }
        
        $this->lwrite(explode("\n",$data));
    }
    private function llog(){
        if(func_num_args()==0){
            return self::debug_backtrace();
        }
        $data = '';
        foreach(func_get_args() as $errstr){
            if(!is_scalar($errstr))
            {
                $data.= print_r($errstr,1).' ';
                //$data.= var_export($errstr,1).' ';
            }else
            {
                    if(is_bool($errstr))
                        $data.= (($errstr)?'TRUE ':'FALSE ');
                    else if ($errstr ===null)
                        $data .= 'NULL ';
                    else
                        $data .= $errstr .' ';
            }
        }
        return $data;
    }
    function log(){
        $data = call_user_func_array(array($this,'llog'),func_get_args());
        $this->lwrite( explode("\n",$data));
    }
    function warn() {
        $data = call_user_func_array(array($this,'llog'),func_get_args());
        $this->lwrite( explode("\n",'[WARNING] ' .$data));
    }
    function error() {
        $data = call_user_func_array(array($this,'llog'),func_get_args());
        $this->lwrite( explode("\n",'[ERROR] ' .$data));
    }
    function debug() {
        $data = call_user_func_array(array($this,'llog'),func_get_args());
        $this->lwrite( explode("\n",'[DEBUG] ' .$data));
    }
    
    function info() {
        $data = call_user_func_array(array($this,'llog'),func_get_args());
        $this->lwrite( explode("\n",'[INFO] ' .$data));
    }
    static function debug_backtrace(){
        $trace = (func_num_args())?func_get_arg(0): debug_backtrace();
        $traceline = "\t#%2d %s(%s): %s(%s)";
        //array_shift($trace);// removes call to this function
        
        
    
        if(!isset($trace[0]['line']) && !isset($trace[0]['file'])){
            $trace[0]['line'] = '@';
            $trace[0]['file'] = '.';
            
        }
        
        $result = array();
        foreach ($trace as $key => $stackPoint) {
            if (!isset ($stackPoint['file']))
            {
                $stackPoint['file'] = '[PHP Kernel]';
            }
        
            if (!isset ($stackPoint['line']))
            {
                $stackPoint['line'] = '';
            }
        
            $stackPoint['function'] = (empty($stackPoint['class']))?$stackPoint['function']:"{$stackPoint['class']}{$stackPoint['type']}{$stackPoint['function']}";
            $args = array();
            if(isset($stackPoint['args']))
            {
                foreach($stackPoint['args'] as $k=>$arg){
                    
                    if(is_object($arg) && method_exists($arg,'__toString')){
                        $args[$k] = (string) $arg;
                    }elseif(is_array($arg)){
                        $args[$k] = print_r($arg,1);
                        $args[$k] = str_replace("Array\n(","\nArray(",$args[$k]);
                        $args[$k] = str_replace("\n","\n\t\t",$args[$k]);
                    }elseif(!is_scalar($arg)){
                        //$args[$k] = var_export($arg,1);
                        $args[$k] = print_r($arg,1);
                        if(strlen($args[$k]) > 300) $args[$k] = substr($args[$k],0,300) . "\n*** LOG DATA TRUNCATED";
                    }elseif((null ===$arg)){
                        $args[$k] = 'NULL';
                    }elseif(is_bool($arg))
                        $args[$k] = ($arg)?'TRUE':'FALSE';
                    else
                        $args[$k] = print_r($arg,1);
                }
            }
            $result[] = sprintf(
                $traceline,
                $key,$stackPoint['file'],$stackPoint['line'],$stackPoint['function'],implode(', ',$args)
            );
        }
        // trace always ends with {main}
        //$result[] = "\t#" .  ++$key . ' {main}';
        $result[] = str_replace(array('():','()'),'',sprintf($traceline,++$key,'{main}','','',''));
            
        return implode("\n",$result);
    }
}
