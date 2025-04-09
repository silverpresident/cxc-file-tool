<?php
/**
 * @author President
 * @copyright 2015
 * @version 20170129
 * 
 * https://stackoverflow.com/a/15976112
 */
namespace ELIX;


class iniFile{
    static function open($file){
        return new iniFileReader($file);
    }
    static function write($file){
        return new iniFileWriter($file);
    }
    static function parse_ini_file($file){
        if(!$file){
            return array();
        }
        if(!file_exists($file)){
            return array();
        }
        $str = file_get_contents($file);
        $x = explode("\n",$str);
        foreach($x as $line){
            
            $tline = trim($line);
            if(empty($tline)){
                continue;
            }
            if(substr($tline,0,1) == ';'){
                continue;
            }
            if(substr($tline,0,1) == '['){
                continue;
            }
            
            $x = explode('=',$tline,2);
            $k = trim($x[0]);
            
            if(isset($x[1])){
                $a[$k] = $x[1];
                $x[1] = trim($x[1]);
                if(is_numeric($x[1]) && (intval($x[1]) == $x[1])){
                    $a[$k] = intval($x[1]);
                }else{
                    switch($x[1]){
                    case 'true':
                    case 'on':
                    case 'yes':
                        $a[$k]= TRUE;
                    break;
                    case 'false':
                    case 'off':
                    case 'no':
                    case 'none':
                        $a[$k]= FALSE;
                    break;
                    case 'null':
                        $a[$k]= NULL;
                    break;
                    }
                }
            }else{
                $a[$k] = '';
            }
            
        }
        return $a;
        
    }
}

class iniFileReader implements \ArrayAccess, \IteratorAggregate {
    private $data =array();
    private $file = '';
    public function __construct($ini) {
        if($ini && (file_exists($ini))){
            $this->file = $ini;
            $a = @parse_ini_file($ini);
            if($a === false){
                $a = iniFile::parse_ini_file($ini);
            }
            if(is_array($a))
                $this->data = $a;
        }
        $this->data = array_change_key_case($this->data, CASE_LOWER);
    }
    public function __destruct() {
        unset($this->data);
    }

    function __invoke($offset) {
        return $this->offsetGet($offset);
    }

    public function reset() {
        if($this->file){
            $this->__construct($this->file);
        }
    }
    public function getIterator() {
        return new \ArrayIterator($this->data);
    }

    public function offsetSet($offset, $value) {
        if (is_null($offset)) {
            $this->data[] = $value;
        } else {
            $this->data[$offset] = $value;
        }
    }

    public function offsetExists($offset) {
        return isset($this->data[$offset]);
    }

    public function offsetUnset($offset) {
        unset($this->data[$offset]);
    }

    public function offsetGet($offset) {
        return isset($this->data[$offset]) ? $this->data[$offset] : null;
    }
    /**
     * Check if a parameter was set
     *
     * Basically a wrapper around isset. 
     *
     * @see isset
     * @param string $name Parameter name
     * @return bool
     */
    public function has($name=null) {
        if(func_num_args() == 0) return !!count($this->data);
        $name = strtolower($name);
        return isset($this->data[$name]);
    }
    /**
     * Remove a parameter from the superglobals
     *
     * Basically a wrapper around unset. 
     *
     * @see isset
     * @param string $name Parameter name
     * @return bool
     */
    public function remove($name) {
        $this->delete($name);
    }
    /**
     * Access a request parameter without any type conversion
     *
     * @param string    $name     Parameter name
     * @param mixed     $default  Default to return if parameter isn't set
     * @param bool      $nonempty Return $default if parameter is set but empty()
     * @return mixed
     */
    public function param($name, $default = null, $nonempty = false) {
        $name = strtolower($name);
        if(!isset($this->data[$name])) return $default;
        $value = $this->data[$name];
        if($nonempty && empty($value)) return $default;
        return $value;
    }
    
    /**
     * Sets a parameter
     *
     * @param string $name Parameter name
     * @param mixed  $value Value to set
     */
    public function set($name, $value) {
        $this->__set($name, $value);
    }/**
     * Access a request parameter as int
     *
     * @param string    $name     Parameter name
     * @param mixed     $type     Type to cast to
     * @return cast value
     * 
     * int, integer - cast to integer
        bool, boolean - cast to boolean
        float, double, real - cast to float
        string    - cast to string
        array -  cast to array
        object - cast to object
        null,unset - cast to NULL (PHP 5)
     */
    public function cast($name, $type) {
        $name = strtolower($name);
        $type = strtolower($type);
        $value =$this->read($name,null);
        if($type=='boolean') $type ='bool';
        if($type=='double') $type ='float';
        if($type=='real') $type ='float';
        if($type=='unset') $type ='null';
        
        if($type=='bool' && is_string($value)){
            $value = substr(trim(strtolower($value)),0,4);
            $this->data[$name] = in_array($value,array('on','true','yes'));
        }else if(settype($value,$type)){
            $this->data[$name] = $value;
        }
        return $this->read($name,null);
    }
    /**
     * Access a request parameter as int
     *
     * @param string    $name     Parameter name
     * @param mixed     $default  Default to return if parameter isn't set or is an array
     * @param bool      $nonempty Return $default if parameter is set but empty()
     * @return int
     */
    public function int($name, $default = 0, $nonempty = false) {
        $name = strtolower($name);
        if(!isset($this->data[$name])) return $default;
        if(is_array($this->data[$name])) return $default;
        $value = $this->data[$name];
        if($value === '') return $default;
        if($nonempty && empty($value)) return $default;
        return (int) $value;
    }

    /**
     * Access a request parameter as string
     *
     * @param string    $name     Parameter name
     * @param mixed     $default  Default to return if parameter isn't set or is an array
     * @param bool      $nonempty Return $default if parameter is set but empty()
     * @return string
     */
    public function str($name, $default = '', $nonempty = false) {
        $name = strtolower($name);
        if(!isset($this->data[$name])) return $default;
        if(is_array($this->data[$name])) return $default;
        $value = $this->data[$name];
        if($nonempty && empty($value)) return $default;

        return (string) $value;
    }

    /**
     * Access a request parameter as bool
     *
     * Note: $nonempty is here for interface consistency and makes not much sense for booleans
     *
     * @param string    $name     Parameter name
     * @param mixed     $default  Default to return if parameter isn't set
     * @param bool      $nonempty Return $default if parameter is set but empty()
     * @return bool
     */
    public function bool($name, $default = false, $nonempty = false) {
        $name = strtolower($name);
        if(!isset($this->data[$name])) return $default;
        if(is_array($this->data[$name])) return true;
        $value = $this->data[$name];
        if($value === '') return $default;
        if($nonempty && empty($value)) return $default;
        
        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * Access a request parameter as array
     *
     * @param string    $name     Parameter name
     * @param mixed     $default  Default to return if parameter isn't set
     * @param bool      $nonempty Return $default if parameter is set but empty()
     * @return array
     */
    public function arr($name, $default = array(), $nonempty = false) {
        $name = strtolower($name);
        if(!isset($this->data[$name])) return $default;
        if(!is_array($this->data[$name])) return $default;
        if($nonempty && empty($this->data[$name])) return $default;

        return (array) $this->data[$name];
    }
    

    /**
     * Access a request parameter and make sure it is has a valid value
     *
     * Please note that comparisons to the valid values are not done typesafe (request vars
     * are always strings) however the function will return the correct type from the $valids
     * array when an match was found.
     *
     * @param string $name    Parameter name
     * @param array  $valids  Array of valid values
     * @param mixed  $default Default to return if parameter isn't set or not valid
     * @return null|mixed
     */
    public function valid($name, $valids, $default = null) {
        $name = strtolower($name);
        if(!isset($this->data[$name])) return $default;
        if(is_array($this->data[$name])) return $default; // we don't allow arrays
        $value = $this->data[$name];
        $found = array_search($value, $valids);
        if($found !== false) return $valids[$found]; // return the valid value for type safety
        return $default;
    }
    function read($name, $default=false) {
        $name = strtolower($name);
        if(isset($this->data[$name])||array_key_exists($name,$this->data))
            return $this->data[$name];
        
        return $default;
    }
    function seek($name, $default='') {
        $name = strtolower($name);
        if(!array_key_exists($name,$this->data)) $this->data[$name] = $default;
        return $this->data[$name];
    }
    function assert($name, $default) {
        $name =strtolower($name);
        if(empty($this->data[$name]))
            $this->data[$name] = $default;
        return $this->data[$name];
    }
    function exists($name){
        $name = strtolower($name);
        return array_key_exists($name,$this->data);
    }
    function isEmpty($name=null)
    {
        if(func_num_args() == 0) return !count($this->data);
        $name = strtolower($name);
        return empty($this->data[$name]);
    }
    function delete($name,$index=null)
    {
        if(func_num_args()==2 && !is_null($index)){
            if(is_array($this->data[$name])){
                if(is_Array($index))
                    foreach($index as $i){unset($this->data[$name][$i]);}
                else
                    unset($this->data[$name][$index]);
            }
        }else{
            unset($this->data[$name]);
        }
    }
    function toArray(){
        $a = $this->data;
        if(func_num_args()){
            $f = func_get_arg(0);
            if(is_array($f)){
                foreach($f as &$fvalue)
                    $fvalue = strtolower($fvalue);
  
                foreach($a  as $k=>$v){
                    $name = strtolower($k);
                    $nk = str_replace('_','-',$name);
                    if(!in_array($name,$f) && !in_array($nk,$f)) unset($a[$k]);
                }
            }
        }
        return $a;
    }
    public function __get($name) {
        $name = strtolower($name);
        $nk = str_replace('_','-',$name);
        if(isset($this->data[$name])){
            return $this->data[$name];
        }elseif(isset($this->data[$nk])){
            return $this->data[$nk];
        }else
            return '';
    }
    public function __set($name, $value) {
        $name = strtolower($name);
        if($value === null)
            $this->__unset($name);
        else{
            if(isset($this->data[$name])){
                $this->data[$name] = $value;
            }else{
                $nk = str_replace('_','-',$name);
                if(isset($this->data[$nk])){
                    $this->data[$nk] = $value;
                }else{
                    $this->data[$name] = $value;
                }
            }
        }
    }
    public function __unset($name) {
        $name = strtolower($name);
        if(strpos($name,'_')!==false){
			$nk = str_replace('_','-',$name);
			unset($this->data[$nk]);
		}
        unset($this->data[$name]);
    }
    public function __isset($name) {
        $name = strtolower($name);
        if (isset($this->data[$name])) return true;
		$nk = str_replace('_','-',$name);
		return isset($this->data[$nk]);
    }
    
}

class iniFileWriter_Section implements  \IteratorAggregate {
    protected $items = array();
    public $name = '';
    public $comment = '';
    public function __destruct() {
        unset($this->items);
    }
    public function __set($name, $value) {
        $this->addLine($name,$value);
    }

    public function getIterator() {
        return new \ArrayIterator($this->items);
    }
    public function addLine($name='',$value='',$comment='') {
        $nl = false;
        if($name){
            $lname = strtolower($name);
            foreach($this->items as $it){
                if(strtolower($it->name) == $lname){
                    $nl = $it;
                    break;
                }
            }
        }
        if($nl === false){
            $nl = new iniFileWriter_Line();
            $this->items[] = $nl;
        }
        $nl->name = $name;
        if($comment){
            $nl->comment = $comment;
            $nl->value = $value;
        }else{
            $nl->setValue($value);
        }
        return $nl;
    }
    public function __toString() {
        $a =array();
        if($this->name){
            if($this->comment){
                $a[] = "[$this->name];$this->comment";
            }else{
                $a[] = "[$this->name]";
            }
        }elseif($this->comment){
            $a[] = ";$this->comment";
        }
        foreach($this->items as $nl){
            $a[] = (string)$nl;
        }
        return implode("\n",$a);
    }
}
class iniFileWriter_Line{
    public $name = '';
    public $value = '';
    public $comment = '';
    public function setValue($str) {
        $i = strpos($str,';');
        if($i>0){
            $this->value = $str;
            $v = $c = '';
            $l = strlen($str);
            for($i=1;$i<=$l;$i++){
                if( (substr($str,$i,1)==';') && (substr($str,$i-1,1)!='\\')){
                    $this->value = substr($str,0,$i);
                    $this->comment = substr($str,$i+1);
                    break;
                }
            }
        }elseif($i===0){
            $this->comment = substr($str,1);
        }else{
            $this->value = $str;
        }
    }
    public function __toString() {
        $a=array();
        if($this->name){
            $a[] = "$this->name=";
        }
        if($this->value){
            $a[] = "$this->value";
        }
        if($this->comment){
            $a[] = " ;$this->comment";
        }
        return implode('',$a);
    }

}
class iniFileWriter extends iniFileWriter_Section{
    private $filename = '';
    public function __construct($ini=null) {
        if($ini){
            $this->filename = $ini;
            if($ini && file_exists($ini)){
                $str = file_get_contents($ini);
                $into = $this;
                $x = explode("\n",$str);
                foreach($x as $line){
                    
                    $tline = trim($line);
                    if(empty($tline)){
                        $nl = $into->addLine();
                        continue;
                    }
                    if(substr($tline,0,1) == ';'){
                        $nl = $into->addLine();
                        $nl->comment = substr($tline,1);
                        continue;
                    }
                    if(substr($tline,0,1) == '['){
                        $section = $this->addSection();
                        $into = $section;
                        $i = strpos($tline,']');
                        if($i){
                            $section->name = substr($tline,1,$i-1);
                            $r = trim(substr($tline,$i+1));
                            $section->comment = ltrim($r,';');
                        }else{
                            $section->name = substr($tline,1);
                        }
                        continue;
                    }
                    $nl = $into->addLine();
                    $x = explode('=',$tline,2);
                    $nl->name = trim($x[0]);
                    if(isset($x[1])){
                        $nl->setValue($x[1]);
                    }
                    
                }
            }
        }
    }
    public function addSection($name='',$comment='') {
        $nl = false;
        if($name){
            $lname = strtolower($name);
            foreach($this->items as $it){
                if( ($it instanceof iniFileWriter_Section) && strtolower($it->name) == $lname){
                    $nl = $it;
                    break;
                }
            }
        }
        if($nl === false){
            $nl = new iniFileWriter_Section();
            $this->items[] = $nl;
        }
        $nl->name = $name;
        $nl->comment =$comment;
        return $nl;
    }
    
    /**
     * saves to the original file if no filepath is provided
     *
     */
    public function save($filename=null) {
        $s = $this->__toString();
        if($filename){
            file_put_contents($filename,$s);
        }elseif($this->filename){
            file_put_contents($this->filename,$s);
        }else{
            trigger_error('Filename required for saving');
        }
    }
}
