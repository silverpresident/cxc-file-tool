<?php
/**
 * @author Edwards
 * @copyright 2010
 */
class ELI_uploadedfile{
    private $data = array();
    public function __get($name) {
        $name = strtolower($name);
        if(isset($this->data[$name]) || array_key_exists($name,$this->data))
            return $this->data[$name];
        elseif(method_exists($this,$name) && $name!='save')
            return $this->$name();
        else
            return '';
    }
    public function __set($name, $value) {
        $name = strtolower($name);
        /*if((null ===$value))
            unset($this->data[$name]);
        else*/
            $this->data[$name] = $value;
    }
    /*public function __unset($name) {
        $name = strtolower($name);
        unset($this->data[$name]);
    }*/
    public function __isset($name) {
        $name = strtolower($name);
        return isset($this->data[$name]);
    }
    public function __toString() {
        return print_r($this,1);
    }
    function toArray(){
        return $this->data;
    }
    public function __construct($f ) {
		$this->data = $f;
        return $this;
	}

	function hasError(){
		return $this->isUploaded() && $this->error != UPLOAD_ERR_OK;
	}
    function sanitizedName(){
        $x  = explode('.',$this->name);
        if(count($x)>2){
            $ext = array_pop($x);
            $s = implode('_',$x) . ".{$ext}";
        }else{
            $s = $this->name;
        }
        $s = preg_replace( 
                     array("/\s+/", "/[^-\.\w]+/",'/__/','/--/','/_-/','/-_/'), 
                     array("_", "",'_','-','-','-'), 
                     trim($s));
         
        $this->data['sanitizedname'] = $s;
		return $this->data['sanitizedname'];
	}
    
    function mime(){
        if(isset($this->data['mime'])) return $this->data['mime'];
        if(isset($this->data['type'])) return $this->data['type'];
        return '';
    }
    function extension(){
        $x = explode(".", $this->name);
        return  end($x); 
    }
	function isUploaded(){
		return $this->error != UPLOAD_ERR_NO_FILE;
	}
    function _post_key(){
        if(!isset($this->data['_post_index']) || (empty($this->data['_post_index']) && ($this->data['_post_index'] !==0 && $this->data['_post_index']==='0'))){
            return "$this->_post_id";
        }else{
            return "{$this->_post_id}[{$this->_post_index}]";
        }
	}

	function path(){
		return $this->tmp_name;
	}
    function save($path){
		return @move_uploaded_file($this->tmp_name, $path);
	}
    function delete(){
		return @unlink($this->tmp_name);
	}
    function error() {
        switch ($this->error) { 
            case UPLOAD_ERR_OK:
                return '';
            case UPLOAD_ERR_INI_SIZE: 
                return 'The uploaded file exceeds the upload_max_filesize directive in php.ini'; 
            case UPLOAD_ERR_FORM_SIZE: 
                return 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form'; 
            case UPLOAD_ERR_PARTIAL: 
                return 'The uploaded file was only partially uploaded'; 
            case UPLOAD_ERR_NO_FILE: 
                return 'No file was uploaded'; 
            case UPLOAD_ERR_NO_TMP_DIR: 
                return 'Missing a temporary folder'; 
            case UPLOAD_ERR_CANT_WRITE: 
                return 'Failed to write file to disk'; 
            case UPLOAD_ERR_EXTENSION: 
                return 'File upload stopped by extension'; 
            default: 
                return 'Unknown upload error #' . $this->error; 
        } 
    } 
}
class ELI_uploadedfiles implements ArrayAccess, Iterator, Countable
{
    private $_keyCache = array();
    protected $items = array();
    
    public function __construct() {
        if(isset($_FILES))
        {
            foreach($_FILES as $name =>$file){
                if(is_array($file['name'])){
                    foreach($file['name'] as $key=>$x){
                        $item = array(
                            'name'     => $file['name'][$key],
                            'type'     => $file['type'][$key],
                            'tmp_name' => $file['tmp_name'][$key],
                            'error'    => $file['error'][$key],
                            'size'     => $file['size'][$key],
                        );
                        $item['_post_id'] = $name;
                        $item['_post_index'] = $key;
                        $this->items[] = new ELI_uploadedfile($item);
                    }
                }else{
                    $file['_post_id'] = $name;
                    $item['_post_index'] = null;
                    $this->items[] = new ELI_uploadedfile($file);
                }
            }
        }
        return $this;
    }
    
    static function build()
    {
        static $instance=null;
        if((null ===$instance)){
            $c = __CLASS__;
            $instance = new $c();
        }
        return $instance;
    }
    
    function exists($name,$index=null){
        if(func_num_args() >1 && !is_null($index) ){
            $key = "{$name}[{$index}]";
        }else{
            $key = "{$name}";
        }
        if(!count($this->_keyCache)){
            foreach($this->items as $file){
                $this->_keyCache[$file->_post_key] = $file->_post_key;
            }
        }
        return in_array($key,$this->_keyCache);
    }
    public function __get($name) {
        $key = "{$name}";
        foreach($this->items as $file){
            if($file->_post_key == $key){
                return $file;
            }
        }
        return FALSE;
    }
    function get($name,$index=null){
        if(func_num_args() >1 && !is_null($index) ){
            $key = "{$name}[{$index}]";
        }else{
            $key = "{$name}";
        }
        foreach($this->items as $file){
            if($file->_post_key == $key){
                return $file;
            }
        }
        return FALSE;
    }
    
    public function __isset($name) {
        return $this->exists($name);
    }
    
    public function __toString() {
        return print_r($this,1);
    }
    function toArray(){
        return $this->items;
    }
    public function offsetSet($offset,$value) {
        /*if ($offset == '') {
            $this->items[] = $value;
        }else {
            $this->items[$offset] = $value;
        }*/
        error_log('Setting not permitted');
    }

    public function offsetExists($offset) {
        if(is_numeric($offset))
            return isset($this->items[$offset]);
        else
            return $this->exists($name);
    }

    public function offsetUnset($offset) {
        if(is_numeric($offset))
            unset($this->items[$offset]);
        else{
            foreach($this->items as $k => $file){
                if($file->_post_key == $offset){
                    unset($this->items[$k]);
                    break;
                }
            }
        }
    }

    public function offsetGet($offset) {
        if(is_numeric($offset))
            return isset($this->items[$offset]) ? $this->items[$offset] : null;
        else{
            foreach($this->items as $k => $file){
                if($file->_post_key == $offset){
                    return $file;
                }
            }
        }
        return null;
    }

    public function rewind() {
        reset($this->items);
    }

    public function current() {
        return current($this->items);
    }

    public function key() {
        return key($this->items);
    }

    public function next() {
        return next($this->items);
    }

    public function valid() {
        return key($this->items) !== null;
    }
    public function count() {
        return count($this->items);
    }
}