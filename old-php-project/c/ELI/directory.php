<?php
/**
 * @author President
 * @copyright 2014
 * @version 20140402
 */
class ELI_directory
{
    var $version = '1.0'; //use for etag refresh
    protected $data =  array();
    function __get($name) {
        
        $name = strtolower($name);
        if($name=='length') $name='size';
        
        if(isset($this->data[$name]) || array_key_exists($name,$this->data))
            return $this->data[$name];
        
        if(method_exists($this,$name)){
            return $this->$name();
        }
        
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
            $this->LoadDir($a);
        }
    }

    function LoadDir($filename){
        $filename = str_replace('\\', DIRECTORY_SEPARATOR, $filename);
        if (substr($filename, -1, 1) !== DIRECTORY_SEPARATOR) {
            $filename .= DIRECTORY_SEPARATOR;
        }

        $fe =   is_dir($filename) ;
        $this->data =array();
        $this->data['exists'] = $fe;
        //$this->data['fileexists'] = $fe?'Y':'N';
        $this->data['file'] =$filename;
        $this->data['mime'] = 'directory';
        
        if($fe){
            
            $data =@stat($filename);
            if(!$data )
            { 
                $data['mtime'] =time();
            }
            //the size provided by this is not the content size but rather the node size
            foreach(array('atime','mtime','ctime') as $k){
                if(isset($data[$k])) $this->data[$k] = $data[$k];
            }
            
            $this->data['basename'] = basename($this->data['file']);
            $this->data['dirname'] = dirname($this->data['file']);
            $this->data['filename'] = $this->data['basename'];
            
             
            $a =array($this->version ,  $this->data['mime'] , $this->data['mtime'] );
            $this->data['etag']= md5(implode('-',$a));
            $d = new DateTime("@{$this->data['mtime']}"); 
            if(!$d) $d = new DateTime(); 
            $this->data['modified_since'] = $d->format('U');
            $this->data['last_modified'] = $d->format('D, d M Y H:i:s');
            
            
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
    function size()
    {
        if(!$this->exists) return 0;
        $dir = $this->file;
        $totalSize = 0;
        $os        = strtoupper(substr(PHP_OS, 0, 3));
        // If on a Unix Host (Linux, Mac OS)
        if ($os !== 'WIN') {
            $io = popen('/usr/bin/du -sb ' . $dir, 'r');
            if ($io !== false) {
                $totalSize = intval(fgets($io, 80));
                pclose($io);
                $this->data['size'] = $totalSize;
                return $totalSize;
            }
        }
        // If on a Windows Host (WIN32, WINNT, Windows)
        if ($os === 'WIN' && extension_loaded('com_dotnet')) {
            $obj = new \COM('scripting.filesystemobject');
            if (is_object($obj)) {
                $ref       = $obj->getfolder($dir);
                $totalSize = $ref->size;
                $obj       = null;
                $this->data['size'] = $totalSize;
                return $totalSize;
            }
        }
        // If System calls did't work, use slower PHP 5
        $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir));
        foreach ($files as $file) {
            $totalSize += $file->getSize();
        }
        $this->data['size'] = $totalSize;
        return $totalSize;
    }
    function save($filename,  $permissions=null) {
        copy($this->file,$filename);   
        if( $permissions != null) {
            chmod($filename,$permissions);
        }
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