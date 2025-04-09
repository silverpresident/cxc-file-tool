<?php
/**
 * @author Edwards
 * @copyright  2014
 */
class ELI_event
{
    protected $edata = null;
    protected $data = array();
    protected $handlers = array();
    protected $receivers = array();
    protected $stopped = null;
    protected $cancelled = null;
    protected $received = 0;
    
    public function __construct($type, $origin) {
        $this->data['eventtype'] = $type;
        $this->data['target'] =$this->data['source'] = $origin;
        $this->data['timestamp'] = time(); 
    }

    public function __call($name, $arguments) {
        $name = strtolower($name);
        if(substr($name,0,2)=='is'){
            switch(substr($name,2)){
            case 'propagationstopped':
            case 'stopped': return $this->stopped();
            case 'handled': return $this->handled();
            case 'cancelled': return $this->cancelled();
            case 'received': return $this->received;
            case 'unhandled': return count($this->handlers())==0;
            }
        }
        switch($name){
            case 'stoppropagation':
            return $this->stop();
            case 'result':
            return $this->__get('result');
        }
    }
    public function __get($name) {
        $name = strtolower($name);
        if(isset($this->data[$name]) || array_key_exists($name,$this->data))
            return $this->data[$name];
        if(method_exists($this,$name))
            return $this->$name();
        
        if(substr($name,0,2)=='is'){
            return $this->__call($name,array());
        }
        return '';
    }
    public function __set($name, $value) {
        $name = strtolower($name);
        if($name == 'eventtype') return;
        if($name == 'source') return;
        
        if((null ===$value))
            unset($this->data[$name]);
        else
            $this->data[$name] = $value;
    }
    public function __unset($name) {
        $name = strtolower($name);
        unset($this->data[$name]);
    }
    public function __isset($name) {
        $name = strtolower($name);
        return isset($this->data[$name]);
    }


    public function handled(){
        return count($this->handlers()) || $this->stopped;
    }
    public function received(){
        return $this->received;
    }
    public function setHandled($by){
        $this->handlers[] = $by;
    }
    public function setReceiver($name, $result=null){
        $this->receivers[] = array($name,$result);
        if($result){
            $this->received = count($this->receivers);
            $this->data['result'] = $result;
        }
    }
    public function stop(){
        $this->stopped = true;
    }
    public function stopped(){
        return $this->stopped;
    }
    public function cancel(){
        $this->cancelled = true;
    }
    public function cancelled(){
        return $this->cancelled;
    }
    
    public function which(){
        return strtoupper($this->eventtype);
    }
    public function where(){
        return strtolower($this->source);
    }
    
    /* PROPERIES added after original relase**/
    public function setData($data ){
        $this->edata = $data;
    }
    public function getData( ){
        return $this->edata;
    }
    public function setTarget($data){
        $this->data['target'] = $data;
    }  
} 