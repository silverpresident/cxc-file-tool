<?php
/**
 * @author Edwards
 * @copyright 2015
 * @used  
 * 
 * this class provides tools for zip compresson
 * currently this file ONLY compresses it does not decompress
 * 
 * MAKING A ZIP FILE
 * $ZIP = ZIP::zip();
 * $ZIP = ZIP::tar();
 * $ZIP = ZIP::gzip();
 * $ZIP = ZIP::bzip();
 * $ZIP = ZIP::bzip2();
 * 
 * $ZIP->open()   -opens an existing file and works NOT IN MEMORY
 * $ZIP->load()   -opens an existing file and works IN MEMORY
 * $ZIP->create() -create file and works NOT IN MEMORY
 * $ZIP->extract() -extract an existing file to a dir and save in memory
 * 
 * $ZIP->addFile();
 * $ZIP->addFromString();
 * $ZIP->addDir();
 * 
 * $ZIP->save()  | $ZIP->output() | $ZIP->close();
 */

/*--------------------------------------------------
 | TAR/GZIP/BZIP2/ZIP ARCHIVE CLASSES 2.1
 | By Devin Doucette
 | Copyright (c) 2005 Devin Doucette
 | Email: darksnoopy@shaw.ca
 +--------------------------------------------------
 | Email bugs/suggestions to darksnoopy@shaw.ca
 +--------------------------------------------------
 | This script has been created and released under
 | the GNU GPL and is free to use and redistribute
 | only if this copyright statement is not removed
 +--------------------------------------------------*/

namespace ELIX;
class ZIP{
    public function __call($name, $arguments) {
        return self::__callStatic($name, $arguments);
    }
    public function get($type) {
        $arguments = func_get_args();
        $name = array_shift($arguments);
        return self::__callStatic($name, $arguments);
    }
    public static function __callStatic($name, $arguments) {
        $name =strtolower($name);
        if(in_array($name,array('bzip2'))) $name ='bzip';
        if(in_array($name,array('tz'))) $name ='tar';
        
        if(!in_array($name,array('zip','bzip','gzip','tar'))) $name ='zip';
        $class =  'ZIP_' . $name;
        $reflect  = new ReflectionClass($class);
        $instance = $reflect->newInstanceArgs($arguments);
    }
    


}


class ZIP_trait
{
    protected $working_in_memory =true;
    protected $filehandle =null;
    
    protected $type='';
    protected $extension = '';
    protected $options = array();
    protected $files = array ();
    protected $error = array ();
    protected $openmethod =0;
    protected $openedpath = '';
    protected $buffer = '';
    protected $created = null; 
    
    public function __construct($name='') {
        //check for inmemory
        //filename
        
        $this->options = array (
			'basedir' => ".",
			//'name' => '',
			'prepend' => "",
			'overwrite' => 0,
			//'recurse' => 1,
			'storepaths' => 1,
			'followlinks' => 0,
			'level' => 3,
			'method' => 1,
			'sfx' => "",
			'comment' => ""
		);
		$this->files = array ();
		/*$this->exclude = array ();
		$this->storeonly = array ();
		$this->error = array ();*/
        //$name
    }
    public function __call($name, $arguments) {
        
        if(in_array($name,array('mime'))){
            $name = 'get'.$name;
            return call_user_func_array(array($this->getHeader(),$name),$arguments);
        }
    }
    public function __destruct() {
        $this->close();
    }
    

    public function getMime(){
        switch ($this->type)
		{
		case "zip":
            return 'application/zip';
		case "bzip":
            return 'application/x-bzip2';
		case "gzip":
            return 'application/x-gzip';
		case "tar":
            return 'application/x-tar';
		}
        return '';
    }
    public function getExtension(){
        return $this->extension;
    }
    function basedir($value=null) {
        $name = __FUNCTION__;
        if(func_num_args()){
            $value = str_replace("\\", "/", $value);
			$value = preg_replace("/\/+/", "/", $value);
			$value = preg_replace("/\/$/", "", $value);
            $this->options['basedir']=$value;
            unset($this->options['path']);
            return $this;
        }else{
            return $this->options[$name];
        }
        
    }
    function comment($value=null) {
        $name = __FUNCTION__;
        if(func_num_args()){
            $this->options[$name]=$value;
            $this->created = false;
            return $this;
        }else{
            return $this->options[$name];
        }
    }
    function sfx($value=null) {
        $name = __FUNCTION__;
        if(func_num_args()){
            $this->options['sfx']=$value;
            $this->created = false;
            return $this;
        }else{
            return $this->options[$name];
        }
    }
    
    function prepend($value=null) {
        $name = __FUNCTION__;
        if(func_num_args()){
            $value = str_replace("\\", "/", $value);
			$value = preg_replace("/^(\.*\/+)+/", "", $value);
			$value = preg_replace("/\/+/", "/", $value);
			$value = preg_replace("/\/$/", "", $value) . "/";
            $this->options['prepend']=$value;
            return $this;
        }else{
            return $this->options[$name];
        }
        
    }
    function setOptions($options)
	{
		foreach ($options as $key => $value)
			$this->options[$key] = $value;
		if (!empty ($this->options['basedir']))
		{
			$this->options['basedir'] = str_replace("\\", "/", $this->options['basedir']);
			$this->options['basedir'] = preg_replace("/\/+/", "/", $this->options['basedir']);
			$this->options['basedir'] = preg_replace("/\/$/", "", $this->options['basedir']);
		}
		/*if (!empty ($this->options['name']))
		{
			$this->options['name'] = str_replace("\\", "/", $this->options['name']);
			$this->options['name'] = preg_replace("/\/+/", "/", $this->options['name']);
		}*/
		if (!empty ($this->options['prepend']))
		{
			$this->options['prepend'] = str_replace("\\", "/", $this->options['prepend']);
			$this->options['prepend'] = preg_replace("/^(\.*\/+)+/", "", $this->options['prepend']);
			$this->options['prepend'] = preg_replace("/\/+/", "/", $this->options['prepend']);
			$this->options['prepend'] = preg_replace("/\/$/", "", $this->options['prepend']) . "/";
		}
	}
    function getOptions()
	{
		$o =$this->options;
        $o['inmemory'] = $this->working_in_memory;
        $o['type'] = $this->type; 
        $o['extension'] = $this->extension;
        return $o;
	}
    function getErrors()
	{
		return $this->error; 
	}
    protected function _getOption($name){
        if(isset($this->options[$name])) return $this->options[$name];
        return false;
    }
    
    function load($filepath)
    {//opens an existing file and works IN MEMORY
        if(file_exists($filepath)){
            //TODO open for read only
            $handle = fopen($filepath, "rb");
            if(!$handle )$this->error[] = "Could not open file {$filepath}.";
            else $this->openedpath = $filepath;
        }else{
            $handle = false;
            $this->error[] = "File does not exist {$filepath}.";
        }
        $this->buffer = '';
        $this->working_in_memory = true;
        if($handle){
            $this->created = true;
            $this->buffer = stream_get_contents($handle);
            fclose($handle);
        }
        $this->openmethod = 2;
    } 
 
    function open($filepath)
    { //opens an existing file and works NOT IN MEMORY
        if(file_exists($filepath)){
            //TODO open for read+write
            $handle = fopen($filepath, "rb");
            if(!$handle )$this->error[] = "Could not open file {$filepath}.";
            else $this->openedpath = $filepath;
        }else{
            $handle = fopen($filepath, "wb");
            if(!$handle )$this->error[] = "File does not exist {$filepath}.";
            else $this->openedpath = $filepath;
        }
        if($handle){
            $this->created = true;
            $this->working_in_memory = false;
            $this->filehandle = $handle;
        }
        $this->openmethod = 1;
    }
    function create($filepath)
    {//create file and works NOT IN MEMORY
        if(file_exists($filepath)){
            if(filesize($filepath)){
                $this->error[] = "File already exist {$filepath}.";
                $handle = tmpfile();
            }else{
                //TODO open for write only
                $handle = fopen($filepath, "wb");
                if(!$handle )$this->error[] = "Could not open file {$filepath}.";
                else $this->openedpath = $filepath;
            }
        }else{
            $handle = fopen($filepath, "wb");
            if(!$handle ) $this->error[] = "Could not create file {$filepath}.";
            else $this->openedpath = $filepath;
        }
        $this->buffer = '';
        $this->working_in_memory = false;
        if($handle){
            $this->created = true;
            $this->filehandle = $handle;
        }else{
            $this->created = true;
            $this->filehandle = tmpfile();
        }
        $this->openmethod = 3;
    }
    
    public function close() {
        if(is_resource($this->filehandle)){
            @fclose($this->filehandle);
        }
        $this->openedpath = '';
        $this->buffer = '';
    }
    
    function addEmptyDir($dirname){
        $f = new ZIP_file($filename);
        $f->emptydir =true;
        $this->files[] = $f;
        return $f;
    }
    function addFile($filepath,$filename = '')
    {
        if(!$filename) $filename = basename($filename);
        $f = new ZIP_file($filename);
        $f->setPath($filepath);
        $this->files[] = $f;
        return $f;
    }
    function addFromString($filename,$filedata='')
    {
        if(!$filename) $filename = uniqid('file');
        $f = new ZIP_file($filename);
        $f->setData($filedata);
        $this->files[] = $f;
        return $f;
    }
    function addDir($dirpath, $recurse)
    {
        if(is_dir($dirpath)){
            $a =array();
            $dirpath =rtrim($dirpath,DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;
            $dir = dir($dirpath);
            while (($file = $dir->read()) !== false)
            {
                if($file =='.') continue;
                if($file =='..') continue;
                $fpath = $dirpath.$file;
                if(is_dir($fpath)){
                    if($recurse) $this->addDir($fpath,$recurse);
                    continue;
                }
                $f = new ZIP_file($file);
                $f->setPath();
                $this->files[] = $f;
                $a[] = $f;
            }
            $dir->close();
            return $a;
        }
        return false;
    }
    function getLength()
    {
        //todo fix
        if(!$this->created) $this->_start();
        if($this->working_in_memory){
            return strlen($this->buffer);
        }elseif(is_resource($this->filehandle)){
            $fs = fstat($this->filehandle);
            if(isset($fs['size']))return $fs['size'];
        }
        return strlen($this->buffer);
    }
    function output($destination)
    {
        //O to STDOUT
        //S return string
        //else filename
        if(!$destination) $destination='O';
        if(strlen(trim($destination)==1)) $destination = strtoupper(trim($destination));
        if(!$this->created) $this->_start();
        
        switch($destination){
            case 'O':
                if($this->working_in_memory)
                    echo $this->buffer;
                else
                    fpassthru($this->filehandle);
            break;
            case 'S':
                if($this->working_in_memory)
                    return $this->buffer;
                else{
                    return stream_get_contents($this->filehandle);
                }
            break;
            case 'F':
                $destination='';
            default:
                if(!$destination){
                    if($this->openedpath)
                        $destination = $this->openedpath;
                    else
                        $destination = tempnam(sys_get_temp_dir());
                }
                if($f = fopen($destination,'wb')){
                    if($this->working_in_memory)
                        fwrite($f,$this->buffer);
                    elseif(is_resource($this->filehandle)){
                        fwrite($f,stream_get_contents($this->filehandle));
                    }
    			    fclose($f);
                    return $destination;
                }else{
                    throw Exception('Unable to create output file: '.$destination);
                }
        }
    }
    function save($destination=null)
    {
        if(!$destination) $destination='F';
        return $this->output($destination);
    }
    
    protected function _add($data){
        $this->created = false; 
        if($this->working_in_memory){
            $this->buffer .= $data;
        }else{
            fwrite($this->filehandle, $data);
        }
    }
}
class ZIP_file{
/*    getName
    getRelPath
    getPath
    if ($current->getType() == 2 || $current->getStat(7) == 0)
    
    storeonly //do not compree
    exclude //do not include
    emptydir
    $f->setPath($filepath)
    ZIP_file($filename)
        $f->setData($filedata);
        followlinks
        storepaths
    
    array ('name' => $fullname, 'name2' => $this->options['prepend'] .
					preg_replace("/(\.+\/+)+/", "", ($this->options['storepaths'] == 0 && strstr($fullname, "/")) ?
					substr($fullname, strrpos($fullname, "/") + 1) : $fullname),
					'type' => @is_link($fullname) && $this->options['followlinks'] == 0 ? 2 : 0,
					'ext' => substr($file, strrpos($file, ".")), 'stat' => stat($fullname));
*/
}
class ZIP_zip extends ZIP_trait
{
    public function __construct($name='') {
        parent::__construct($name);
        $this->type = 'zip';
        $this->extension = 'zip';
    }
    
    protected function _start()
	{
	   //TODO: incomplete
        if($this->created) return 0;
	    $this->created = false;
		if(!$this->working_in_memory && !is_resource( $this->filehandle)){
            $this->filehandle = tmpfile();
        }
        
		$files = 0;
		$offset = 0;
		$central = "";

		if (!empty ($this->options['sfx']))
			if ($fp = @fopen($this->options['sfx'], "rb"))
			{
				$temp = fread($fp, filesize($this->options['sfx']));
				fclose($fp);
				$this->addData($temp);
				$offset += strlen($temp);
				unset ($temp);
			}
			else
				$this->error[] = "Could not open sfx module from {$this->options['sfx']}.";

		$pwd = getcwd();
		chdir($this->options['basedir']);

		foreach ($this->files as $current)
		{
			if ($current->getPath() == $this->options['name'])
				continue;

			$timedate = explode(" ", date("Y n j G i s", $current->getStat(9)));
			$timedate = ($timedate[0] - 1980 << 25) | ($timedate[1] << 21) | ($timedate[2] << 16) |
				($timedate[3] << 11) | ($timedate[4] << 5) | ($timedate[5]);

			$block = pack("VvvvV", 0x04034b50, 0x000A, 0x0000, (isset($current['method']) || $this->options['method'] == 0) ? 0x0000 : 0x0008, $timedate);

			if ($current->getStat(7) == 0 && $current['type'] == 5)
			{
				$block .= pack("VVVvv", 0x00000000, 0x00000000, 0x00000000, strlen($current->getName()) + 1, 0x0000);
				$block .= $current->getName() . "/";
				$this->addData($block);
				$central .= pack("VvvvvVVVVvvvvvVV", 0x02014b50, 0x0014, $this->options['method'] == 0 ? 0x0000 : 0x000A, 0x0000,
					(isset($current['method']) || $this->options['method'] == 0) ? 0x0000 : 0x0008, $timedate,
					0x00000000, 0x00000000, 0x00000000, strlen($current->getName()) + 1, 0x0000, 0x0000, 0x0000, 0x0000, $current['type'] == 5 ? 0x00000010 : 0x00000000, $offset);
				$central .= $current->getName() . "/";
				$files++;
				$offset += (31 + strlen($current->getName()));
			}
			else if ($current->getStat(7) == 0)
			{
				$block .= pack("VVVvv", 0x00000000, 0x00000000, 0x00000000, strlen($current->getName()), 0x0000);
				$block .= $current->getName();
				$this->addData($block);
				$central .= pack("VvvvvVVVVvvvvvVV", 0x02014b50, 0x0014, $this->options['method'] == 0 ? 0x0000 : 0x000A, 0x0000,
					(isset($current['method']) || $this->options['method'] == 0) ? 0x0000 : 0x0008, $timedate,
					0x00000000, 0x00000000, 0x00000000, strlen($current->getName()), 0x0000, 0x0000, 0x0000, 0x0000, $current['type'] == 5 ? 0x00000010 : 0x00000000, $offset);
				$central .= $current->getName();
				$files++;
				$offset += (30 + strlen($current->getName()));
			}
			else if ($fp = @fopen($current->getPath(), "rb"))
			{
				$temp = fread($fp, $current->getStat(7));
				fclose($fp);
				$crc32 = crc32($temp);
				if (!isset($current['method']) && $this->options['method'] == 1)
				{
					$temp = gzcompress($temp, $this->options['level']);
					$size = strlen($temp) - 6;
					$temp = substr($temp, 2, $size);
				}
				else
					$size = strlen($temp);
				$block .= pack("VVVvv", $crc32, $size, $current->getStat(7), strlen($current->getName()), 0x0000);
				$block .= $current->getName();
				$this->addData($block);
				$this->addData($temp);
				unset ($temp);
				$central .= pack("VvvvvVVVVvvvvvVV", 0x02014b50, 0x0014, $this->options['method'] == 0 ? 0x0000 : 0x000A, 0x0000,
					(isset($current['method']) || $this->options['method'] == 0) ? 0x0000 : 0x0008, $timedate,
					$crc32, $size, $current->getStat(7), strlen($current->getName()), 0x0000, 0x0000, 0x0000, 0x0000, 0x00000000, $offset);
				$central .= $current->getName();
				$files++;
				$offset += (30 + strlen($current->getName()) + $size);
			}
			else
				$this->error[] = "Could not open file {$current->getPath()} for reading. It was not added.";
		}

		$this->addData($central);

		$this->addData(pack("VvvvvVVv", 0x06054b50, 0x0000, 0x0000, $files, $files, strlen($central), $offset,
			!empty ($this->options['comment']) ? strlen($this->options['comment']) : 0x0000));

		if (!empty ($this->options['comment']))
			$this->addData($this->options['comment']);

		chdir($pwd);
        
		if ($this->inmemory == 0)
		{
			fclose($this->handle);
		}
        $this->created = true;
		return 1;
	}
}
class ZIP_tar extends ZIP_trait
{
    public function __construct($name='') {
        parent::__construct($name);
        $this->type = 'tar';
        $this->extension = 'tar';
    }
    protected function _open()
	{
		return @fopen($this->options['name'], "rb");
	}
    /*function open($filepath)
    { //opens an existing file and works NOT IN MEMORY
        parent::open($filepath);
    }    
    function load($filepath)
    {//opens an existing file and works IN MEMORY
        parent::load($filepath);
    } 
 
    function create($filepath)
    {//create file and works NOT IN MEMORY
        parent::create($filepath);
    }*/
    function extract($dirpath)
	{//extract an existing file to a dir and save in memory
	   //TODO: incomplete
        if(!is_dir($dirpath)){
            $this->error[] = "Directory does not exist. {$dirpath}";
            if(mkdir($dirpath) ===false){
                $this->error[] = "Counld not make directory. {$dirpath}";
                return false;
            }
        }
        
        if($this->working_in_memory){
            //extract from memory
        }else{
            //extract from file not in memory
            $pwd = getcwd();
    		chdir($dirpath);
            if($this->filehandle){
                //extract from open file
                $fp = $this->filehandle;
            }else
            {
                $fp = $this->_open();
                if(!$fp) $this->error[] = "Could not open file ";
            }
    		if ($fp)
    		{
    			if ($this->options['inmemory'] == 1)
    				$this->files = array ();
    
    			while ($block = fread($fp, 512))
    			{
    				$temp = unpack("a100name/a8mode/a8uid/a8gid/a12size/a12mtime/a8checksum/a1type/a100symlink/a6magic/a2temp/a32temp/a32temp/a8temp/a8temp/a155prefix/a12temp", $block);
    				$file = array (
    					'name' => $temp['prefix'] . $temp['name'],
    					'stat' => array (
    						2 => $temp['mode'],
    						4 => octdec($temp['uid']),
    						5 => octdec($temp['gid']),
    						7 => octdec($temp['size']),
    						9 => octdec($temp['mtime']),
    					),
    					'checksum' => octdec($temp['checksum']),
    					'type' => $temp['type'],
    					'magic' => $temp['magic'],
    				);
    				if ($file['checksum'] == 0x00000000)
    					break;
    				else if (substr($file['magic'], 0, 5) != "ustar")
    				{
    					$this->error[] = "This script does not support extracting this type of tar file.";
    					break;
    				}
    				$block = substr_replace($block, "        ", 148, 8);
    				$checksum = 0;
    				for ($i = 0; $i < 512; $i++)
    					$checksum += ord(substr($block, $i, 1));
    				if ($file['checksum'] != $checksum)
    					$this->error[] = "Could not extract from {$this->options['name']}, it is corrupt.";
    
    				if ($this->options['inmemory'] == 1)
    				{
    					$file['data'] = fread($fp, $file['stat'][7]);
    					fread($fp, (512 - $file['stat'][7] % 512) == 512 ? 0 : (512 - $file['stat'][7] % 512));
    					unset ($file['checksum'], $file['magic']);
    					$this->files[] = $file;
    				}
    				else if ($file['type'] == 5)
    				{
    					if (!is_dir($file['name']))
    						mkdir($file['name'], $file['stat'][2]);
    				}
    				else if ($this->options['overwrite'] == 0 && file_exists($file['name']))
    				{
    					$this->error[] = "{$file['name']} already exists.";
    					continue;
    				}
    				else if ($file['type'] == 2)
    				{
    					symlink($temp['symlink'], $file['name']);
    					chmod($file['name'], $file['stat'][2]);
    				}
    				else if ($new = @fopen($file['name'], "wb"))
    				{
    					fwrite($new, fread($fp, $file['stat'][7]));
    					fread($fp, (512 - $file['stat'][7] % 512) == 512 ? 0 : (512 - $file['stat'][7] % 512));
    					fclose($new);
    					chmod($file['name'], $file['stat'][2]);
    				}
    				else
    				{
    					$this->error[] = "Could not open {$file['name']} for writing.";
    					continue;
    				}
    				chown($file['name'], $file['stat'][4]);
    				chgrp($file['name'], $file['stat'][5]);
    				touch($file['name'], $file['stat'][9]);
    				unset ($file);
    			}
    		}
    
    		chdir($pwd);    
        }
        
        
        
		
	}
    protected function _start()
	{
	    if($this->created) return 0;
	    
        $this->created = false;
		if(!$this->working_in_memory && !is_resource( $this->filehandle)){
            $this->filehandle = tmpfile();
        }
        $bdir = $this->_getOption('basedir'); 
		foreach ($this->files as $current)
		{
			$path = $current->getRelPath($bdir);
			if (strlen($current->getName()) > 99)
			{
				$path = substr($current->getName(), 0, strpos($current->getName(), "/", strlen($current->getName()) - 100) + 1);
				$current->getName() = substr($current->getName(), strlen($path));
				if (strlen($path) > 154 || strlen($current->getName()) > 99)
				{
					$this->error[] = "Could not add {$path}{$current->getName()} to archive because the filename is too long.";
					continue;
				}
			}
			$block = pack("a100a8a8a8a12a12a8a1a100a6a2a32a32a8a8a155a12", 
                $current->getName(), sprintf("%07o", 
				$current->getStat(2)), sprintf("%07o", $current->getStat(4)), 
                sprintf("%07o", $current->getStat(5)), 
				sprintf("%011o", $current->getType() == 2 ? 0 : $current->getStat(7)), 
                sprintf("%011o", $current->getStat(9)), 
				"        ", $current->getType(), 
                $current->getType() == 2 ? @readlink($current->getPath()) : "", 
                "ustar ", " ", 
				"Unknown", "Unknown", "", "", 
                !empty ($path) ? $path : "",
                 "");

			$checksum = 0;
			for ($i = 0; $i < 512; $i++)
				$checksum += ord(substr($block, $i, 1));
			$checksum = pack("a8", sprintf("%07o", $checksum));
			$block = substr_replace($block, $checksum, 148, 8);

			if ($current->getType() == 2 || $current->getStat(7) == 0)
				$this->_add($block);
			else if ($fp = @fopen($current->getPath(), "rb"))
			{
				$this->_add($block);
				while ($temp = fread($fp, 1048576))
					$this->_add($temp);
				if ($current->getStat(7) % 512 > 0)
				{
					$temp = "";
					for ($i = 0; $i < 512 - $current->getStat(7) % 512; $i++)
						$temp .= "\0";
					$this->_add($temp);
				}
				fclose($fp);
			}
			else
				$this->error[] = "Could not open file {$current->getPath()} for reading. It was not added.";
		}

		$this->_add(pack("a1024", ""));

		$this->_close();
        $this->created = true;
		return 1;
	}
    protected function _close(){
        //TODO: incomplete
        if (!$this->working_in_memory)
		{
			fclose($this->filehandle);
			if ($this->options['type'] == "gzip" || $this->options['type'] == "bzip")
				unlink($this->options['basedir'] . "/" . $this->options['name'] . ".tmp");
		}
    }
}
class ZIP_gzip extends ZIP_tar
{
    public function __construct($name='') {
        parent::__construct($name);
        $this->type = 'gzip';
        $this->extension = 'gz';
    }
    protected function _open()
	{
		return @gzopen($this->options['name'], "rb");
	}
    protected function _start()
	{
	    if($this->created) return 0;
	    
	    parent::_start();
        
		if ($this->working_in_memory){
            $this->buffer = gzencode($this->buffer, $this->_getOption('level'));
		}else
		{
  		//TODO: incomplete
			$pwd = getcwd();
			chdir($this->options['basedir']);
			if ($fp = gzopen($this->options['name'], "wb{$this->options['level']}"))
			{
				fseek($this->archive, 0);
				while ($temp = fread($this->archive, 1048576))
					gzwrite($fp, $temp);
				gzclose($fp);
				chdir($pwd);
			}
			else
			{
				$this->error[] = "Could not open {$this->options['name']} for writing.";
				chdir($pwd);
				return 0;
			}
		}
        
        $this->_close();
        $this->created = true;
		return 1;
	}
    protected function _close(){
        //TODO: incomplete
        if (!$this->working_in_memory)
		{
			fclose($this->filehandle);
			if ($this->options['type'] == "gzip" || $this->options['type'] == "bzip")
				unlink($this->options['basedir'] . "/" . $this->options['name'] . ".tmp");
		}
    }
    function open($filepath)
    { //opens an existing file and works NOT IN MEMORY
        parent::open($filepath);
    }    
    function load($filepath)
    {//opens an existing file and works IN MEMORY
        parent::load($filepath);
        $this->buffer = gzdecode($this->buffer )''
    } 
}
class ZIP_bzip extends ZIP_tar
{
    public function __construct($name='') {
        parent::__construct($name);
        $this->type = 'bzip';
        $this->extension = 'bzip';
    }
    protected function _open()
	{
		return @bzopen($this->options['name'], "rb");
	}
    protected function _start()
	{
        if($this->created) return 0;
	    
        parent::_start();
        
        if ($this->working_in_memory){
            $this->buffer = bzcompress($this->buffer, $this->_getOption('level'));
		}else
        {//TODO: imcomplete
			$pwd = getcwd();
			chdir($this->options['basedir']);
			if ($fp = bzopen($this->options['name'], "wb"))
			{
				fseek($this->archive, 0);
				while ($temp = fread($this->archive, 1048576))
					bzwrite($fp, $temp);
				bzclose($fp);
				chdir($pwd);
			}
			else
			{
				$this->error[] = "Could not open {$this->options['name']} for writing.";
				chdir($pwd);
				return 0;
			}
		}
	}
    protected function _close(){
        //TODO: incomplete
        if (!$this->working_in_memory)
		{
			fclose($this->filehandle);
			if ($this->options['type'] == "gzip" || $this->options['type'] == "bzip")
				unlink($this->options['basedir'] . "/" . $this->options['name'] . ".tmp");
		}
    }
    function open($filepath)
    { //opens an existing file and works NOT IN MEMORY
        parent::open($filepath);
    }    
    function load($filepath)
    {//opens an existing file and works IN MEMORY
        parent::load($filepath);
        $this->buffer = bzdecompress($this->buffer )''
    }
}






















<?php
	
    
class ZIP_archive{
    
    function save($filename=null, $permissions=null) {
        if(func_num_args()==0 || is_null($filename)){
            $filename = $this->path;
        }
        if($this->inmemory){
            if(!$this->created) $this->create();
            $handle = fopen($filename, "wb");
            fwrite($handle,$this->archive);
            fclose($handle);
            
        }else{
            if($this->path != $filename)copy($this->path,$filename);
        }
        if( $permissions != null) {
            chmod($filename,$permissions);
        }
    }
    
    /*function length(){
        if($this->inmemory){
            return strlen($this->archive);
        }else{
            return filesize($this->path);
        }
    }
    function output() {
        if($this->inmemory){
            if(!$this->created) $this->create();
            print($this->archive);
        }else{
             if($this->length < 60000)
            {
                echo file_get_contents($this->path);
            }else
            {
                $fp = fopen($this->path, "rb");
                while (!feof($fp))
                {
                    echo fread($fp, 65536);
                    flush(); // this is essential for large downloads
                }
                fclose($fp);
            }
		}
        
    }*/
    
    
    protected $options = array();
    protected $files = array ();
	protected $exclude = array ();
	protected $storeonly = array ();
	protected $error = array ();
    protected $handle = null;
    protected $archive = null;
    
    public function __get($name) {
        $name=strtolower($name);
        if(!isset($this->options[$name])){
            if(method_exists(__CLASS__,$name))
                return $this->$name();    
        }
            
        if(isset($this->options[$name]))
            return $this->options[$name];
        else
            return '';
    }
    
    public function __set($name, $value) {
        $name=strtolower($name);
        if(method_exists(__CLASS__,$name))
            return $this->$name($value);    
        $this->options[$name] = $value;
    }
    
    
    
    
    private function listFiles($list)
	{
		if (!is_array ($list))
		{
			$temp = $list;
			$list = array ($temp);
			unset ($temp);
		}

		$files = array ();

		$pwd = getcwd();
		chdir($this->options['basedir']);

		foreach ($list as $current)
		{
			$current = str_replace("\\", "/", $current);
			$current = preg_replace("/\/+/", "/", $current);
			$current = preg_replace("/\/$/", "", $current);
			if (strstr($current, "*"))
			{
				$regex = preg_replace("/([\\\^\$\.\[\]\|\(\)\?\+\{\}\/])/", "\\\\\\1", $current);
				$regex = str_replace("*", ".*", $regex);
				$dir = strstr($current, "/") ? substr($current, 0, strrpos($current, "/")) : ".";
				$temp = $this->parseDir($dir);
				foreach ($temp as $current2)
					if (preg_match("/^{$regex}$/i", $current2['name']))
						$files[] = $current2;
				unset ($regex, $dir, $temp, $current);
			}
			else if (@is_dir($current))
			{
				$temp = $this->parseDir($current);
				foreach ($temp as $file)
					$files[] = $file;
				unset ($temp, $file);
			}
			else if (@file_exists($current))
				$files[] = array ('name' => $current, 'name2' => $this->options['prepend'] .
					preg_replace("/(\.+\/+)+/", "", ($this->options['storepaths'] == 0 && strstr($current, "/")) ?
					substr($current, strrpos($current, "/") + 1) : $current),
					'type' => @is_link($current) && $this->options['followlinks'] == 0 ? 2 : 0,
					'ext' => substr($current, strrpos($current, ".")), 'stat' => stat($current));
		}

		chdir($pwd);
		unset ($current, $pwd);
		usort($files, array ($this, "sortFiles"));
		return $files;
	}
    
    
    

	
	function parseDir($dirname)
	{
		if ($this->options['storepaths'] == 1 && !preg_match("/^(\.+\/*)+$/", $dirname))
			$files = array (array ('name' => $dirname, 'name2' => $this->options['prepend'] .
				preg_replace("/(\.+\/+)+/", "", ($this->options['storepaths'] == 0 && strstr($dirname, "/")) ?
				substr($dirname, strrpos($dirname, "/") + 1) : $dirname), 'type' => 5, 'stat' => stat($dirname)));
		else
			$files = array ();
		$dir = @opendir($dirname);

		while ($file = @readdir($dir))
		{
			$fullname = $dirname . "/" . $file;
			if ($file == "." || $file == "..")
				continue;
			elseif (@is_dir($fullname))
			{
				if (empty ($this->options['recurse']))
					continue;
				$temp = $this->parseDir($fullname);
				foreach ($temp as $file2)
					$files[] = $file2;
			}
			elseif (@file_exists($fullname))
				$files[] = array ('name' => $fullname, 'name2' => $this->options['prepend'] .
					preg_replace("/(\.+\/+)+/", "", ($this->options['storepaths'] == 0 && strstr($fullname, "/")) ?
					substr($fullname, strrpos($fullname, "/") + 1) : $fullname),
					'type' => @is_link($fullname) && $this->options['followlinks'] == 0 ? 2 : 0,
					'ext' => substr($file, strrpos($file, ".")), 'stat' => stat($fullname));
		}

		@closedir($dir);

		return $files;
	}

	function sortFiles($a, $b)
	{
		if ($a['type'] != $b['type'])
			if ($a['type'] == 5 || $b['type'] == 2)
				return -1;
			else if ($a['type'] == 2 || $b['type'] == 5)
				return 1;
		elseif ($a['type'] == 5)
			return strcmp(strtolower($a['name']), strtolower($b['name']));
		elseif ($a['ext'] != $b['ext'])
			return strcmp($a['ext'], $b['ext']);
		elseif ($a['stat'][7] != $b['stat'][7])
			return $a['stat'][7] > $b['stat'][7] ? -1 : 1;
		else
			return strcmp(strtolower($a['name']), strtolower($b['name']));
		return 0;
	}
} 