<?php
/**
 * @author Edwards
 * @copyright 2010
 */
class ELI_environment
{
    static private $instance = array();
    private $data = array();

    static function build($item)
    {
        $item = strtoupper($item);
        if(!isset(self::$instance[$item])){
            $c = __CLASS__;
            //REQUEST,
            
            if($item=='SERVER'){
                self::$instance[$item] = new $c($_SERVER);
            }elseif($item=='ENV'){
                self::$instance[$item] = new $c($_ENV);
            }elseif($item=='COOKIE'){
                self::$instance[$item] = new $c($_COOKIE);
            }elseif($item=='SESSION'){
                self::$instance[$item] = new $c($_SESSION);
            }elseif($item=='HOST'){
                $p = 'SERVER_';
                $l = strlen($p);
                $A = array();
                foreach($_SERVER as $k => $v){
                    if(substr($k,0,$l)==$p){
                        $A[substr($k,$l)] =& $_SERVER[$k];
                    }
                }
                self::$instance[$item] = new $c($A);
            }elseif($item=='REMOTE'){
                $p = 'REMOTE_';
                $l = strlen($p);
                $A = array();
                foreach($_SERVER as $k => $v){
                    if(substr($k,0,$l)==$p){
                        $A[substr($k,$l)] =& $_SERVER[$k];
                    }
                }
                self::$instance[$item] = new $c($A);
            }elseif($item=='HTTP'){
                $p = 'HTTP_';
                $l = strlen($p);
                $A = array();
                foreach($_SERVER as $k => $v){
                    if(substr($k,0,$l)==$p){
                        $A[substr($k,$l)] =& $_SERVER[$k];
                    }
                }
                self::$instance[$item] = new $c($A);
            }elseif($item=='REQUEST'){
                $p = 'REQUEST_';
                $l = strlen($p);
                $A = array();
                foreach($_SERVER as $k => $v){
                    if(substr($k,0,$l)==$p){
                        $A[substr($k,$l)] =& $_SERVER[$k];
                    }
                }
                self::$instance[$item] = new $c($A);
            }elseif($item=='X'){
                $p = 'HTTP_X_';
                $l = strlen($p);
                $A = array();
                foreach($_SERVER as $k => $v){
                    if(substr($k,0,$l)==$p){
                        $A[substr($k,$l)] =& $_SERVER[$k];
                    }
                }
                self::$instance[$item] = new $c($A);
            }else{
                return false;
            }
            
        }
        return self::$instance[$item];
    }
    function isXHR()
    {
        static $r = null;
        if($r===null)
        {
            $r =  isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
        }
        return $r;
    }
    function Read($name, $default=false) {
        $name = strtoupper($name);
        if(isset($this->data[$name])||array_key_exists($name,$this->data))
            return $this->data[$name];
        return $default;
    }
    function Seek($name, $default='') {
        $name = strtoupper($name);
        if(!array_key_exists($name,$this->data)) $this->data[$name] = $default;
        return $this->data[$name];
    }
    function Assert($name, $default) {
        $name =strtoupper($name);
        if(!isset($this->data[$name]) || empty($this->data[$name]))
            $this->data[$name] = $default;
        return $this->data[$name];
    }
    function exists($name){
        $name = strtoupper($name);
        return array_key_exists($name,$this->data);
    }
    function isEmpty($name)
    {
        $name = strtoupper($name);
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
        return $this->data;
    }
    public function __construct(&$array) {
        $this->data =& $array;
        $this->data = array_change_key_case($this->data, CASE_UPPER);
		foreach($this->data as $k=>$d){
			if(strpos($k,'-')!==false){
				$nk = str_replace('-','_',$k);
				if(!isset($this->data[$nk]))
					$this->data[$nk] =& $this->data[$k];
			}
		}
    }
    public function __toString() {
        return print_r($this,1);
    }
    public function __get($name) {
        $name = strtoupper($name);
        if(isset($this->data[$name]) || array_key_exists($name,$this->data))
            return $this->data[$name];
        else
            return '';
    }
    public function __set($name, $value) {
        $name = strtoupper($name);
        /*if((null ===$value))
            unset($this->data[$name]);
        else*/
            $this->data[$name] = $value;
    }
    public function __unset($name) {
        $name = strtoupper($name);
        unset($this->data[$name]);
    }
    public function __isset($name) {
        $name = strtoupper($name);
        return isset($this->data[$name]);
    }
}
?>