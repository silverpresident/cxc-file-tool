<?php
/**
 * @author Edwards
 * @copyright  2011
 */
class ELI_cachefile
{
    static protected $VERSION = 1;
    static public function version(){return static::$VERSION ;}
    protected $path = null;
    protected $ttl = 60;
    protected $time = 0;
    protected $data = null;
    protected $state = 0;
    protected $expires = null;
    protected $until_midnight = 0;
    protected $until =0;
    public function __call($name, $arguments) {
        $name = strtolower($name);
        
        //all these depend on an existing file
        if(empty($this->path)) return null;
        
        switch($name)
        {
            case 'file_exists':
            case 'exists': return file_exists($this->path);
            case 'mtime': return filemtime($this->path);
            case 'data': $this->read(); return $this->data;
            case 'time': return $this->time;
            case 'unlink': return $this->delete();
        }
    }
    public function __get($name) {
        $name = strtolower($name);
        switch($name)
        {
            case 'file_exists':
            case 'exists': return $this->exists();
            case 'expired': return $this->expired();
            case 'valid': return $this->valid();
            case 'file':
            case 'filename':
            case 'filepath': 
            case 'path': return $this->path;
            case 'expires': return $this->expires;
            case 'ttl': return $this->ttl;
            case 'minutes': return $this->ttl/60;
            case 'hours': return $this->ttl/3600;
            case 'time': return $this->time;
            case 'data': return $this->data();
            case 'mtime': return $this->mtime();
            case 'age': return $this->age();
            case 'until_midnight': return $this->until_midnight;
        }
    }
    public function __set($name, $value) {
        $name = strtolower($name);
        switch($name)
        {
            case 'ttl':  $this->ttl = (int)$value; break;
            case 'minutes':  $this->ttl = (60 * (int)$value); break;
            case 'hours':  $this->ttl = (3600 * (int)$value); break;
            case 'time':  $this->time = (int)$value; break;
            case 'data':  $this->putContents($value); break;
            case 'until_midnight': $this->until_midnight = $value;
        }
    }
    public function __construct() {
        if(func_num_args()){
            $this->setFile(func_get_arg(0));
        }
    }
    function setFile($file) {
        if($this->path != $file){
            $this->path = $file;
            $this->state = 0;
            $this->data = null;
            $this->time = time();
        }
        return $this;
    }
    function setTTL($seconds) {
        $this->ttl = (int)$seconds;
        return $this;
    }
    function expired() {
        if(empty($this->path)) return null;
        
        if(file_exists($this->path)){
            $this->read();
            if (time() > $this->expires) {
                return true;
            }
        }
        return false;
    }
    function valid($unlink=true) {
        if(empty($this->path)) return null;
        if($this->expired()){
            if($unlink && file_exists($this->path)) @unlink($this->path);
            return false;
        }
        return true;
    }
    function delete() {
        if(empty($this->path)) return null;
        if(file_exists($this->path)) @unlink($this->path);
        return true;
    }
    function age() {
        if(empty($this->path)) return null;
        if($this->state!=1) return 0;
        return time() - $this->mtime;
    }
    function until($d='midnight tomorrow') {
        if(!($d instanceof \DateTime))
        {
            $d = date_create($d);
            if(!$d) $d = date_create('midnight tomorrow');
        }
        $this->until = $d->format('U');
    }
    function getData(){
        if(empty($this->path)) return null;
        if($this->expired()){
            return null;
        }
        return $this->data;
    }
    function save() {
        if(empty($this->path)) return null;
        
        $x = array();
        
        
        if($this->until_midnight){
            $x['until_midnight'] = $this->until_midnight;
            $x['at'] = mktime(0, 0, 0, date("m")  , date("d")+1, date("Y"));
        }elseif($this->until){
            $x['until'] = $this->until;
            if($this->until > time())
                $x['at'] = $this->until;
            else
                $x['at'] = $this->time + 3600;
        }else{
            $x['at'] = $this->time + $this->ttl;
        }
        $x['ttl'] = $this->ttl;
        $x['version'] = static::version();
        //$x['headers'] = $headers;
        $x['data'] = $this->data;
        $filename = $this->path;
        $data = serialize($x);
        $h = fopen($filename, 'w');
        if (!$h) {
            throw new Exception('Could not write to file. FILENAME: '.$filename);
        }
        if (flock($h, LOCK_EX))
        { // do an exclusive lock
            ftruncate($h, 0); // truncate file
        }
        if (fwrite($h, $data) === false) {
            throw new Exception('Could not write to file');
        }
        fclose($h);
        $this->expires = $x['at'];
        $this->state =1;
        return true;
    }
    
    function read() {
        if($this->state==1) return true;
        $filename = $this->path;
        if (!file_exists($filename) || !is_readable($filename)) return false;
 
        $data = file_get_contents($filename);
        $data = @unserialize($data);
        if (!$data) {
            $this->expires = 0;
            $this->ttl = 0;
            $this->data ='';
            return false;
        }
        $this->expires = $data['at'];
        $this->ttl = $data['ttl'];
        $this->data = $data['data'];
        $this->state =1;
        return true;
    }
    function putContents($data) {
        $this->data = (string)$data;
        return $this;
    }
}
?>