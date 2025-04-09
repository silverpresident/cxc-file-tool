<?php

/**
 * @author President
 * @copyright 2010
 * @version 20140402
 */
class ELI_document
{
    var $version = '1.0'; //use for etag refresh
    protected $data =  array();
    function __get($name) {
        $name = strtolower($name);
        if($name=='length') $name='size';
        if($name == 'fileexists'){
            error_log(__CLASS__. '->fileexists is deprecated. Use ->exists instead. 2014-02-14.');
            return $this->exists?'Y':'N';
        }
        if(isset($this->data[$name]) || array_key_exists($name,$this->data))
            return $this->data[$name];
        
        return '';
    }
    public function __isset($name) {
        $name = strtolower($name);
        return isset($this->data[$name]);
    }

    function checkIfModified()
    {
        if(isset($_SERVER['HTTP_IF_MODIFIED_SINCE']))
        {
            if ((@strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']) == $this->modified_since)) 
            {
                header("HTTP/1.1 304 Not Modified Date Checked");
                die();
            }
        }
    }
    
    function checkETag()
    {
        if(isset($_SERVER['HTTP_IF_NONE_MATCH']))
        {
            $mtag = trim(trim($_SERVER['HTTP_IF_NONE_MATCH']),'"');
            if( $mtag == $this->etag) 
            {
                header("HTTP/1.1 304 Not Modified Etag Same");
                die();
            }
            
            ## IF etag is invalid (set and no match) the last modified cannot be valid
            $_SERVER['HTTP_IF_MODIFIED_SINCE'] = 1;
        }
    }
    public function __construct() {
        if(func_num_args()){
            $a = func_get_arg(0);
            $this->LoadFromFile($a);
        }
    }

    function LoadFromFile($filename){
        
        $fe = file_exists($filename) and is_file($filename) and is_readable($filename);
        $this->data =array();
        $this->data['exists'] = $fe;
        //$this->data['fileexists'] = $fe?'Y':'N';
        $this->data['file'] =$filename;
        if($fe){
            
            $data =@stat($filename);
            if(!$data )
            { 
                $data['size'] =0;
                $data['mtime'] =time();
            }
            foreach(array('atime','mtime','ctime','size') as $k){
                if(isset($data[$k])) $this->data[$k] = $data[$k];
            }
            
            $data = pathinfo($this->data['file']);
            $this->data['filename'] =$data['filename'];
            $this->data['extension'] =$data['extension'];
            $this->data['basename'] =$data['basename'];
            if(function_exists('mime_content_type'))
            {
                $this->data['mime'] = mime_content_type($this->data['file']);
            }elseif(function_exists('fdata_open'))
            {
                $fdata = fdata_open(FILEdata_MIME_TYPE); // return mime type ala mimetype extension
                $this->data['mime'] = fdata_file($fdata, $this->data['file']);
                fdata_close($fdata);                
            }else
            {
                $this->data['nomime'] = 1;
                $this->data['mime'] = 'application/octet-stream';                
            }
            $a =array($this->version ,  $this->data['size'] , $this->data['mime'] , $this->data['mtime'] );
            $this->data['etag']= md5(implode('-',$a));
            $d = new DateTime("@{$this->data['mtime']}"); 
            if(!$d) $d = new DateTime(); 
            $this->data['modified_since'] = $d->format('U');
            $this->data['last_modified'] = $d->format('D, d M Y H:i:s');
            //$this->data['date'] = $d;
            //
            //$this->data['expires'] =  ' 23:59:00';
            //$age = (60 * 60) * 24; //1 day
            //$this->data['age'] = $age * 3; 
            
        }
    }
    function age(){
        if($this->mtime){
            $d = new DateTime("@{$this->data['mtime']}"); 
            if(!$d) $d = new DateTime(); 
            $interval = $d->diff(date_create());
            $this->data['age'] = $interval->format('%a');
            return $this->data['age'];
        }else
            return false;
    }
    function expires($time='+1 Month'){
        if($this->mtime){
            $d = new DateTime("@{$this->data['mtime']}"); 
            if(!$d) $d = new DateTime();
            $d->modify($time);
            return $d->format('D, d M Y H:i:s');
        }else
            return false;
    }
    function save($filename,  $permissions=null) {
        copy($this->file,$filename);   
        if( $permissions != null) {
            chmod($filename,$permissions);
        }
    }
    function send(){
        $this->output();
    }
    function output()    
    {
        if(!$this->exists)
                return;
        if($this->length < 60000)
        {
            echo file_get_contents($this->file);
        }else
        {
            $fp = fopen($this->file, "rb");
            while (!feof($fp))
            {
                echo fread($fp, 65536);
                flush(); // this is essential for large downloads
            }
            fclose($fp);
        }
    }
    function get_contents()
    {
        $handle = fopen($this->file, "rb");
        $contents = stream_get_contents($handle);
        fclose($handle);
        return $contents;
    }   
}

?>