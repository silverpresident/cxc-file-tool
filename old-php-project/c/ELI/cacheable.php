<?php
/**
 * @author Shane 
 * @copyright 2010
 * 
 * 
 */
class ELI_cacheable
{
    function dir(){
        if(func_num_args()){
            $this->root = realpath(func_get_arg(0));
        }else{
            if(empty($this->root)) $this->root = dirname(__FILE__). DIRECTORY_SEPARATOR . 'cache';
            if(!is_dir($this->root)) mkdir($this->root);
        }
        return $this->root;

    }
    function clean(){
        $dir= $this->dir();
        $d = dir($dir);
        $omit =array('.','..');
        if(substr($dir, -1) != DIRECTORY_SEPARATOR) $dir .= DIRECTORY_SEPARATOR;
        $df=array();
        $cachetime = time() - (60 * 600);
        while(false !== ($entry = $d->read())){
            if (!in_array($entry,$omit)){
                 $cachefile =  $dir.$entry;
                 if(is_file($cachefile) &&  filemtime($cachefile) < $cachetime ) {
                    unlink($cachefile);
                    $df[] = $cachefile;
                }
            }
        }
        $d->close();
    }
    public $version = '0.1'; //for site wide refresh
    public $noteRefferer = true;
    public $notePost = true;
    private $e = array();
    private $root = '';
    protected $data = array();
    public function __toString() {
        return print_r($this,1);
    }
    public function __get($name) {
        $name = strtolower($name);
        
        if(isset($this->data[$name]) || array_key_exists($name,$this->data))
            return $this->data[$name];
        elseif(method_exists($this,$name))
            return $this->$name();
        else
            return '';
    }
    public function __set($name, $value) {
        $name = strtolower($name);
        if((null ===$value))
            unset($this->data[$name]);
        else{
            if($name=='minutes'){
                if($value<1) $value = 1;
                if($value>600) $value = 600;   
            }
            $this->data[$name] = $value;
        }
            
    }
    public function filename() {
        $m[] = $this->version();
        $SERVER = ELI_method::build('SERVER');
        $m[] = strtolower($SERVER->HTTP_HOST.$SERVER->REQUEST_URI);
        if($this->noteRefferer) $m[] = strtolower($SERVER->HTTP_REFERER);
        if($this->notePost && count($_POST)) $m[] = serialize($_POST); 
        foreach($this->e  as $v)
            $m[] = $v;
        $this->data[__FUNCTION__] = urlencode(strtolower($SERVER->HTTP_HOST.substr($SERVER->REQUEST_URI,0,100) ))."-".md5(implode('-',$m));
        return $this->data[__FUNCTION__];
    }
    public function filepath () { 
        $this->data[__FUNCTION__] =  self::dir().DIRECTORY_SEPARATOR.$this->filename.'.cache';
        return $this->data[__FUNCTION__];
    }
    public function minutes () {
        if(empty($this->data[__FUNCTION__])) $this->data[__FUNCTION__] = 10;
        if($this->data[__FUNCTION__] < 1) $this->data[__FUNCTION__] = 1;
        return $this->data[__FUNCTION__];
    }
    public function isCached(){
        $cachefile = $this->filepath;
        $cachetime = 60*$this->minutes;
        return (file_exists($cachefile) && time() - $cachetime < filemtime($cachefile) && (filesize($cachefile)>16));
    }
    public function readFile(){
        header("X-ELI-Cacheable: Using cached file [{$this->filepath}]");
        readfile($this->filepath);
    }
    public function start() {
        $cachefile = $this->filepath;
        
        // How long to keep cache file?
        $cachetime = 60*$this->minutes;
        if (file_exists($cachefile) && time() - $cachetime < filemtime($cachefile) && (filesize($cachefile)>16)) {
            header("X-ELI-Cacheable: Using cached file [$cachefile]");
            readfile($cachefile);
            die();
        }
        ob_start();
        register_shutdown_function(array($this,'endOfPage'));
    }
    function endOfPage()
    {
        $cachefile = $this->filepath;
        $webpage = ob_get_contents();//and no echo
        if(strlen($webpage)>32){
            if ($fp = fopen($cachefile, 'w+'))
            {
                if (flock($fp, LOCK_EX))
                { // do an exclusive lock
                    ftruncate($fp, 0); // truncate file
                }   
                //compress unnecesary spaces
                $busca = array('/\>[^\S ]+/s','/[^\S ]+\</s','/(\s)+/s');
                $reemplaza = array('>','<','\\1');
                $webpage = preg_replace($busca, $reemplaza, $webpage);
                
                fwrite($fp, $webpage);
                fclose($fp);
                
            }
            if ($fp = fopen($cachefile.'h', 'w+')){
                if (flock($fp, LOCK_EX))
                { // do an exclusive lock
                    ftruncate($fp, 0); // truncate file
                }
                fwrite($fp, print_r(headers_list(),1));
                fclose($fp);
            }
        }
        //echo $webpage;
    }
    
    function AddEntityProperty($value)
    {
        if(func_num_args()>0)
            $value = func_get_args();
        if(is_array($value)){
            foreach($value as $e)
                $this->e[] = $e;
        }else
            $this->e[] = $value;
    }
   
    private function version(){
        return __CLASS__. $this->version;
    }
    
    
}
?>