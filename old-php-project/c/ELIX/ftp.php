<?php
/**
 * @author Edwards
 * @copyright 2016
 * 
 *  http://php.net/manual/en/function.ftp-rawlist.php
 * 
 * 
 */
namespace ELIX;
/**
  *  $ftp = new ftp('ftp.example.com'); 
$ftp->ftp_login('username','password'); 
var_dump($ftp->ftp_nlist()); 

  *                  
  */

class FTP{
    public static function connect($url){
        $ftp = new self($url);
        $ftp->pasv(true);
        return $ftp;
    }
    
    
    private $conn; 
    private $messageArray = array();
    public function __construct($url=null){
        if($url){
            $this->conn = ftp_connect($url);
            if(!$this->conn){
                $this->logMessage('FTP connection has failed!');
                $this->logMessage('Attempted to connect to ' . $server );
            }
        } 
    }
    public function __deconstruct(){
        $this->close();
    }
    public function close(){
        if ($this->conn) {
            ftp_close($this->conn);
        }
    }
    private function logMessage($message) 
    {
        $this->messageArray[] = $message;
    }
    public function getMessages()
    {
        return $this->messageArray;
    }
    public function get($local_file, $server_file, $mode = FTP_BINARY){
        if (ftp_get($this->conn,$local_file, $server_file, $mode)) {
            
        } else {
            $this->messageArray[] = "There was a problem writing file $server_file to $local_file";
        }
    }
    public function fget($local_handle, $server_file, $mode = FTP_BINARY){
        if (ftp_fget($this->conn,$local_handle, $server_file, $mode)) {
            
        } else {
            $this->messageArray[] = "There was a problem writing file $server_file to $local_file";
        }
    }
    public function nb_get($local_file, $server_file, $mode = FTP_BINARY){
        if (ftp_nb_get($this->conn,$local_file, $server_file, $mode)) {
            
        } else {
            $this->messageArray[] = "There was a problem writing file $server_file to $local_file";
        }
    }
    public function nb_fget($local_handle, $server_file, $mode = FTP_BINARY){
        if (ftp_nb_fget($this->conn,$local_handle, $server_file, $mode)) {
            
        } else {
            $this->messageArray[] = "There was a problem writing file $server_file to $local_file";
        }
    }
    function is_dir( $dir )
    {
        $cwd = $this->pwd();
        if( @ftp_chdir( $this->conn, $dir ) ) {
            ftp_chdir( $this->conn, $cwd);
            return true;
        } else {
            return false;
        }
    }
    function listDetailed( $directory = '.') {
        $items = array();
        if (is_array($children = @ftp_rawlist($this->conn, $directory))) { 
             

            foreach ($children as $child) { 
                $chunks = preg_split("/\s+/", $child);
                $item =array(); 
                list($item['perms'], $item['number'], $item['user'], $item['group'], $item['size'], $item['month'], $item['day'], $item['time']) = $chunks; 
                if(strpos($item['time'],':')){
                    $item['year'] = date('Y');
                }else{
                    $item['year'] = $item['time'];
                    $item['time'] = '00:00';
                }
                $perms = $chunks[0];
                $item['perms']=substr($perms, 1);
                $item['permsn']= self::chmodnum($item['perms']); 
                
                if (substr($perms, 0, 1) == "d")
                 {
                    $item['type'] = 'folder';
                 }
                elseif (substr($perms, 0, 1) == "l")
                 {
                    $item['type'] = 'link';
                 }
                else
                 {
                    $item['type'] = 'file';
                 }
                
                array_splice($chunks, 0, 8); 
                $item['file']= implode(" ", $chunks);
                $item['raw']=  $child;
                $items[implode(" ", $chunks)] = $item; 
            } 
        } 

            return $items; 

        // Throw exception or return false < up to you 
    }
    public function __call($func,$a){ 
        
        if(substr($func,0,4) == 'ftp_'){
            $cfunc = $func;
        }else{
            $cfunc = "ftp_{$func}";
        }
        
        
        if(function_exists($cfunc)){ 
            array_unshift($a,$this->conn); 
            return call_user_func_array($cfunc,$a); 
        }else{ 
            // replace with your own error handler. 
            $this->logMessage("$func is not a valid FTP function"); 
        }
    } 
    static function chmodnum($mode) {
       $realmode = "";
       $legal =  array("","w","r","x","-");
       $attarray = preg_split("//",$mode);
       for($i=0;$i<count($attarray);$i++){
           if($key = array_search($attarray[$i],$legal)){
               $realmode .= $legal[$key];
           }
       }
       $mode = str_pad($realmode,9,'-');
       $trans = array('-'=>'0','r'=>'4','w'=>'2','x'=>'1');
       $mode = strtr($mode,$trans);
       $newmode = '';
       $newmode .= $mode[0]+$mode[1]+$mode[2];
       $newmode .= $mode[3]+$mode[4]+$mode[5];
       $newmode .= $mode[6]+$mode[7]+$mode[8];
       return $newmode;
    }
} 
