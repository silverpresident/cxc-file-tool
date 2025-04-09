<?php
/**
 * @author Edwards
 * @copyright  2011
 * version 3.0
 * last revised: 2015-04-05
 * DEPRECATED for elix request path
 */

class ELI_path implements ArrayAccess, Iterator, Countable
{
    protected $data = array();
    protected $query = '';
    protected $extension = '';
    public function join($with= DIRECTORY_SEPARATOR){
        $x = array_filter($this->data);
        return implode($with,$x);
    }
    public function pageTranslate($to,Array $anyof){
        $x = '';
        if($this->page){
            if(!is_array($anyof)){
                if(func_num_args()>2){
                    $anyof = func_get_args();
                    array_shift($anyof);
                }else{
                    $anyof = str_replace('/',',',$anyof);
                    $anyof =explode(',',$anyof);
                }
            }
            if(in_array($this->page,$anyof)){
                $x = $this->page;
                $this->data[0] = $to;
            }
        }
        return $x;
    }
    public function pageSplit($if='.php'){
        $x = '';
        if($this->page){
            $i = strripos($this->page,$if);
            if($i){
                $p = substr($this->page,0,$i);
                $x = substr($this->page,$i);
                $this->data[0] = $p;
            }
        }
        return $x;
    }
    public function unique(){
        //remove repeated itedm eg  'page/apple/apple/pie' becomes 'page/apple/pie'
        $c = count($this->date);
        if($c < 2) return ;
        for($i = $c-1;$i>1; $i--){
            $p = $i-1;
            if($this->data[$i] == $this->data[$p]){
                unset($this->data[$i]);
            }
        }
    }
    public function trim($data=array()){
        //remove items from top element
        if(!is_array($data)){
            if(func_num_args()>1){
                $data = func_get_args();
            }else{
                $data =array($data);
            }
        }
        $data = array_filter($data);
        $i = 0;
        if(count($this->data) && isset($this->data[0])){
            while(in_array($this->data[0],$data)){
                array_shift($this->data);
                if(!isset($this->data[0])) break;
                $i++;
            }
        }
        return $i;
    }
    public function __construct() {
        if(func_num_args()){
            $a1 = func_get_arg(0);
            $a2 = (func_num_args()>1)?func_get_arg(1):false;
            if(is_bool($a1) && is_bool($a2) && $a2==false){
                $a = $_SERVER["REQUEST_URI"];
                $b = $a1;
            }elseif($a1 === null||$a1===''){
                $a = $_SERVER["REQUEST_URI"];
                $b = $a2;
            }else{
                $a = $a1;
                $b = $a2;
            }
            $this->setRoute($a,$b);
        }else
            $this->setRoute($_SERVER["REQUEST_URI"]);
    }
    public function setRoute($path,$respectCase = false) {
        if($respectCase)
            $r = trim((trim($path)),'/');
        else
            $r = trim(strtolower(trim($path)),'/');
        $x = explode('?',$r);
        if(!empty($x[1])) $this->query = $x[1];
        $x = explode('/',$x[0]);
        $this->data = array_filter($x);
        $p = $this->page();
        if($i = strrpos($p,'.')){
            $this->extension = substr($p,$i);
        }else{
            $this->extension = '';
        }
    }
    
    public function promote(){
        return array_shift($this->data);
    }
    public function promoteIf($data){
        if(!isset($this->data[0])) return false;
        if(is_array($data)){
            if(in_array($this->data[0],$data)){
                return array_shift($this->data);
            }
        }elseif(func_num_args()>1){
            $data = func_get_args();
            if(in_array($this->data[0],$data)){
                return array_shift($this->data);
            }
        }elseif($this->data[0] == $data){
                return array_shift($this->data);
        }
        return false;
    }
    public function page(){
        if(isset($this->data[0]))
            return $this->data[0];
        else
            return '';
    }
    public function subpage(){
        if(isset($this->data[1]))
            return $this->data[1];
        else
            return '';
    }
    public function query(){
        return $this->query;
    }
    public function extension(){
        return $this->extension;
    }
    public function param($index){
        if(isset($this->data[$index]))
            return $this->data[$index];
        else
            return '';
    }
    function Read($name, $default=false) {
        $name = strtolower($name);
        switch($name){
            case 'query': return $this->query;
            case 'extension': return $this->extension;
            case 'page': $name = 0; break;
            case 'subpage': $name = 1; break;
            default:
                if(substr($name,0,5)=='param'){
                    $name = (int)substr($name,5);
                    $name--;;
                }
        }
        
        if(isset($this->data[$name]))
            return $this->data[$name];
        else
            return $default;
    }
    function Seek($name, $default='') {
        $name = strtolower($name);
        switch($name){
            case 'extension': 
                if($this->extension =='') $this->extension = $default;
            return $this->extension;
            
            case 'page': $name = 0; break;
            case 'subpage': $name = 1; break;
            default:
                if(substr($name,0,5)=='param'){
                    $name = (int)substr($name,5);
                    $name--;
                }
        }
        if(!isset($this->data[$name]))
            $this->data[$name] = $default;
        return $this->data[$name];
    }
    function exists($name){
        return $this->__isset($name);
    }
    function isEmpty($name)
    {
        $name = strtolower($name);
        switch($name){
            case 'extension': 
                return ($this->extension ==='');
            return $this->extension;
            case 'page': $name = 0; break;
            case 'subpage': $name = 1; break;
            default:
                if(substr($name,0,5)=='param'){
                    $name = (int)substr($name,5);
                    $name--;
                }
        }
        return empty($this->data[$name]);
    }
    function delete($name)
    {
        $this->__set($name,null);
    }
    function toArray(){
        if(func_num_args() ==1){
            if(is_array(func_get_arg(0)))
                $this->data = func_get_arg(0);
        }
        return $this->data;
    }
    /*function all(){
        trigger_error('deprecated');
        if(func_num_args() ==1){
            if(is_array(func_get_arg(0)))
                $this->data = func_get_arg(0);
        }
        return $this->data;
    }*/
    public function __get($name) {
        $name = strtolower($name);
        if(!isset($this->data[$name])){
            if(method_exists(__CLASS__,$name))
                return $this->$name();
            elseif(substr($name,0,5)=='param'){
                $name = (int)substr($name,5);
                $name--;
                return $this->param($name);
            }
        }
        if(isset($this->data[$name]))
            return $this->data[$name];
        else
            return '';
    }
    public function __set($name, $value) {
        $name = strtolower($name);
        switch($name){
            
            case 'extension': 
                $this->extension = $value;
            return $this->extension;
            case 'page': 
                $name = 0;
                 if(!is_null($value)){
                    array_unshift($this->data,$value);
                 }
                 if($value === ''){
                    $this->data =array();
                    return ;
                 }
            break;
            case 'subpage': 
                $name = 1;
                if(!is_null($value)){
                    array_unshift($this->data,'');
                    if(isset($this->data[1])) $this->data[0] = $this->data[1];
                    //$this->data[1] = $value
                 }
                 if($value === ''){
                    $p = isset($this->data[0])?$this->data[0]:'';
                    $this->data =array($p);
                    return ;
                 }
            break;
            default:
                if(substr($name,0,5)=='param'){
                    $name = (int)substr($name,5);
                    $name--;
                }
        }
        if((null ===$value))
            unset($this->data[$name]);
        else
            $this->data[$name] = $value;
    }
    public function __unset($name) {
        $this->__set($name,null);
    }
    public function __isset($name) {
        $name = strtolower($name);
        switch($name){
            case 'page': $name = 0; break;
            case 'subpage': $name = 1; break;
            default:
                if(substr($name,0,5)=='param'){
                    $name = (int)substr($name,5);
                    $name--;
                }
        }
        return isset($this->data[$name]);
    }
    public function __toString() {
        return print_r($this,1);
    }
    
    /** Iterators */
    public function offsetSet($offset,$value) {
        if ($offset == '') {
            $this->data[] = $value;
        }else {
            if(strtolower($offset)=='page') $offset = 0;
            if(strtolower($offset)=='subpage') $offset = 1;
            $this->data[$offset] = $value;
        }
    }

    public function offsetExists($offset) {
        if(strtolower($offset)=='page') $offset = 0;
        if(strtolower($offset)=='subpage') $offset = 1;
        return isset($this->data[$offset]);
    }

    public function offsetUnset($offset) {
        if(strtolower($offset)=='page') $offset = 0;
        if(strtolower($offset)=='subpage') $offset = 1;
        unset($this->data[$offset]);
    }

    public function offsetGet($offset) {
        if(strtolower($offset)=='page') $offset = 0;
        if(strtolower($offset)=='subpage') $offset = 1;
        return isset($this->data[$offset]) ? $this->data[$offset] : null;
    }

    public function rewind() {
        reset($this->data);
    }

    public function current() {
        return current($this->data);
    }

    public function key() {
        return key($this->data);
    }

    public function next() {
        return next($this->data);
    }

    public function valid() {
        return key($this->items) !== null;
    }    

    public function count() {
        return count($this->data);
    }
}
?>