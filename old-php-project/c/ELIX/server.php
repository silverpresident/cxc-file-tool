<?php
/**
 * @author Edwards
 * @copyright 2015
 * 
 * this class provides tools for  the  server
 */
namespace ELIX;

class SERVER{
    /*
    return a scale of 0 - 3 saying how busy the system is estimaed to be
    0 = not busy
    1 = working | busy
    2 = very busy
    3 = overloaded
    */
    public function getBusyStatus(){
        $load = self::getLoadAverage();
        if(($load[0] < $load[1]) && ($load[0] < $load[2])){
            return 0;
        }
        if($load[1]){
            $d1 = $load[1] * 4;
            if($load[0] > $d1){
                return 3;
            }
        }
        if($load[2]){
            $d2 = $load[2] * 3;
            if($load[0] > $d2){
                return 2;
            }
        }
        $load = self::_LoadFromTopPercentage();
        if($load['idle'] > 70){
            return 0;
        }
        if(($load['user'] + $load['sys']) > 70 ){
            return 3;
        }
        if(($load['user'] + $load['sys']) > 55 ){
            return 2;
        }
        if(($load['user'] + $load['sys']) > 40 ){
            return 1;
        }
        if($load['idle'] && ($load['idle'] < 40)){
            return 1;
        }
        /*$pcount = self::processCount();
        $cpus = self::getCpuCount();
        $running = self::getRunningProcesses();
        $ppu = $pcount/$cpus;*/
        
        return 0;
    }
    public function getMemoryUsage(){
        $memory_usage = -1;
        if($free = _shell_exec('free')){
        	$free = (string)trim($free);
        	$free_arr = explode("\n", $free);
        	$mem = explode(" ", $free_arr[1]);
        	$mem = array_filter($mem);
        	$mem = array_merge($mem);
        	$memory_usage = $mem[2];
        }
    	return $memory_usage;
    }
    public function getMemoryUsagePercentage(){
        $memory_usage = -1;
        if($free = _shell_exec('free')){
            $free = (string)trim($free);
        	$free_arr = explode("\n", $free);
        	$mem = explode(" ", $free_arr[1]);
        	$mem = array_filter($mem);
        	$mem = array_merge($mem);
        	$memory_usage = $mem[2]/$mem[1]*100;
        }
    	return $memory_usage;
    }
    public static function getLoadAverage(){
        if(function_exists('sys_getloadavg')){
            return sys_getloadavg();
        }elseif($fdata = @file_get_contents('/proc/loadavg')){
            return explode(' ',$fdata);
        }elseif($str = _shell_exec('uptime')){
            $str = substr(strrchr($str,":"),1); 
            return array_map("trim",explode(",",$str)); 
        }
        return array(0,0,0);
    }
    public static function getRunningProcesses(){
        if($fdata = @file_get_contents('/proc/loadavg')){
            $x = explode(' ',$fdata);
            return (int)$x[3];
        }
        return 1;
    }
    public static function getLoadPercent(){
        return self::_LoadFromTopPercentage();
    }
    
    public function getCpuUsage(){
        $load = -1;
        if(self::isWindows()){
            return self::_winServerLoad();
        }
        $x = self::getLoadAverage();
        $load = $x[0];
    	return $load;
    }
    static function getCpuCount()
    {
        static $numCpus;
        if (isset($numCpus))
            return $numCpus;
        
        $numCpus = 1;
        
        if (is_file('/proc/cpuinfo'))
        {
            $cpuinfo = file_get_contents('/proc/cpuinfo');
            preg_match_all('/^processor/m', $cpuinfo, $matches);
            $numCpus = count($matches[0]);
        } else
            if ('WIN' == strtoupper(substr(PHP_OS, 0, 3)))
            {
                $process = @popen('wmic cpu get NumberOfCores', 'rb');
        
                if (false !== $process)
                {
                    fgets($process);
                    $numCpus = intval(fgets($process));
                    pclose($process);
                }
            } else
            {
                $process = @popen('sysctl -a', 'rb');
                if (false !== $process)
                {
                    $output = stream_get_contents($process);
                    preg_match('/hw.ncpu: (\d+)/', $output, $matches);
                    if ($matches)
                    {
                        $numCpus = intval($matches[1][0]);
                    }
                    pclose($process);
                }
            }
            return $numCpus;
    }
    /*
    Array
(
    [user] => 23.1
    [nice] => 0.5
    [sys] => 4.5
    [idle] => 72
)
    */
    public static function getCalculatedStatLoad(){
        $stat1 = file('/proc/stat');
        sleep(1); 
        $stat2 = file('/proc/stat');
        $info1 = explode(" ", preg_replace("!cpu +!", "", $stat1[0])); 
        $info2 = explode(" ", preg_replace("!cpu +!", "", $stat2[0])); 
        $dif = array(); 
        $dif['user'] = $info2[0] - $info1[0]; 
        $dif['nice'] = $info2[1] - $info1[1]; 
        $dif['sys'] = $info2[2] - $info1[2]; 
        $dif['idle'] = $info2[3] - $info1[3]; 
        $total = array_sum($dif); 
        $cpu = array(); 
        foreach($dif as $x=>$y) $cpu[$x] = round($y / $total * 100, 1);
        
        return $cpu;
    }
    public static function isWindows(){
        return (stristr(PHP_OS, 'win')); 
    }
    static function processCount() {
      static $ver, $runs = 0;
      
      // check if php version supports clearstatcache params, but only check once
      if ( is_null( $ver ) )
        $ver = version_compare( PHP_VERSION, '5.3.0', '>=' );
     
      // Only call clearstatcache() if function called more than once */
      if ( $runs++ > 0 ) { // checks if $runs > 0, then increments $runs by one.
        
        // if php version is >= 5.3.0
        if ( $ver ) {
          clearstatcache( true, '/proc' );
        } else {
          // if php version is < 5.3.0
          clearstatcache();
        }
      }
      
      $stat = stat( '/proc' );
     
      // if stat succeeds and nlink value is present return it, otherwise return 0
      return ( ( false !== $stat && isset( $stat[3] ) ) ? $stat[3] : 0 );
    }
    private static function _winServerLoad(){
        if(self::isWindows() && class_exists("COM")){
            $wmi = new COM("Winmgmts://");
            $server = $wmi->execquery("SELECT LoadPercentage FROM Win32_Processor");  
            $cpu_num = 0;
            $load_total = 0;
            foreach($server as $cpu)
            {
                $cpu_num++;
                $load_total += $cpu->loadpercentage;
            }
    
            return round($load_total/$cpu_num);
        }
        return 0;
    }
    private static function _LoadFromTopPercentage(){
        $cmd ="top -b -n 1| grep 'Cpu(s):'";
        $free = _shell_exec($cmd);
        $free = substr($free,8);
        $x = explode(', ',$free);
        $load =array('user'=>0,'nice'=>0,'sys'=>0,'idle'=>0);
        foreach($x as $i){
            $r = substr($i,-2);
            if($r == 'us'){
                $load['user'] = (float)$i;
                continue;
            }
            if($r == 'ni'){
                $load['nice'] = (float)$i;
                continue;
            }
            if($r == 'sy'){
                $load['sys'] = (float)$i;
                continue;
            }
            if($r == 'id'){
                $load['idle'] = (float)$i;
                continue;
            }
            
            //0.8%wa,  0.0%hi,  0.2%si,  0.0%st

        }
        return $load;
    }
}

function _shell_exec($cmd){
    $func = 'shell_exec';
    if(function_exists($func)){
        if (is_callable($func) && false === stripos(ini_get('disable_functions'), $func)){
            return shell_exec($cmd);
        }
    }
    return '';
}