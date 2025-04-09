<?php
/**
 * @author Edwards
 * @copyright 2010
 */

class ELI
{
    const VERSION = '1.0';
    
    static function loadapi($name)
    {
        $f =dirname(__FILE__) . DIRECTORY_SEPARATOR . "{$name}.php";
        if(file_exists($f)) include_once($f);
    }
    static function exists($name)
    {
        return file_exists(dirname(__FILE__) . DIRECTORY_SEPARATOR . "{$name}.php");
    }
    static function __callStatic($name, $arguments) {
        $name =strtolower($name);
        if($name=='curl') $name ='http';
        if($name=='url') $name ='uri';
        if($name=='file') $name ='document';
        if($name=='icalendar') $name ='ical';
        if(in_array($name ,array('cachecontrol','cacheable','cache','cachefile','cookie','collection'
        ,'date','directory','document','enviroment','email','event','http','ical','image','method','object'
        ,'path','response','rss','robotstxt'
        ,'searchparser','session','sitemap','uri'))){
            self::loadapi($name);
            $class = __CLASS__ . '_' . $name;
            $reflect  = new ReflectionClass($class);
            $instance = $reflect->newInstanceArgs($arguments);
            return $instance;
        }
        array_unshift($arguments,$name);
        return call_user_func_array("self::build",$arguments);
    }

    static function build($object)
    {
        $object=strtolower(trim($object));
        self::loadapi($object);
        $class = __CLASS__ . '_' . $object;
        if(!class_exists($class,false))
        {
            eval("class $class { public \$_ORIGIN = 'created by default in ELI::build';  }");
        }
        if(func_num_args()>1){
            $reflect  = new ReflectionClass($class);
            $arguments = func_get_args();
            array_shift($arguments);
            $instance = $reflect->newInstanceArgs($arguments);
            return $instance;
        }
        return new $class();
    }
    static function debug_backtrace(){
        $trace = (func_num_args())?func_get_arg(0): debug_backtrace();
        $traceline = "\t#%2d %s(%s): %s(%s)";
        //array_shift($trace);// removes call to this function
        
        
    
        if(!isset($trace[0]['line']) && !isset($trace[0]['file'])){
            $trace[0]['line'] = '@';
            $trace[0]['file'] = '.';
            
        }
        
        $result = array();
        foreach ($trace as $key => $stackPoint) {
            if (!isset ($stackPoint['file']))
            {
                $stackPoint['file'] = '[PHP Kernel]';
            }
        
            if (!isset ($stackPoint['line']))
            {
                $stackPoint['line'] = '';
            }
        
            $stackPoint['function'] = (empty($stackPoint['class']))?$stackPoint['function']:"{$stackPoint['class']}{$stackPoint['type']}{$stackPoint['function']}";
            $args = array();
            if(isset($stackPoint['args']))
            {
                foreach($stackPoint['args'] as $k=>$arg){
                    $args[$k] = gettype($arg);
                    /*if(is_object($arg) && method_exists($arg,'__toString')){
                        $args[$k] = (string) $arg;
                    }elseif(is_array($arg)){
                        $args[$k] = print_r($arg,1);
                        $args[$k] = str_replace("Array\n(","\nArray(",$args[$k]);
                        $args[$k] = str_replace("\n","\n\t\t",$args[$k]);
                    }elseif(!is_scalar($arg)){
                        //$args[$k] = var_export($arg,1);
                        $args[$k] = print_r($arg,1);
                        if(strlen($args[$k]) > 300) $args[$k] = substr($args[$k],0,300) . "\n*** LOG DATA TRUNCATED";
                    }elseif((null ===$arg)){
                        $args[$k] = 'NULL';
                    }elseif(is_bool($arg))
                        $args[$k] = ($arg)?'TRUE':'FALSE';
                    else
                        $args[$k] = print_r($arg,1);*/
                }
            }
            $result[] = sprintf(
                $traceline,
                $key,$stackPoint['file'],$stackPoint['line'],$stackPoint['function'],implode(', ',$args)
            );
        }
        // trace always ends with {main}
        //$result[] = "\t#" .  ++$key . ' {main}';
        $result[] = str_replace(array('():','()'),'',sprintf($traceline,++$key,'{main}','','',''));
            
        return implode("\n",$result);
    }
    
    static function systemLoad($interval = 1){
        $rs = sys_getloadavg();
        $interval = (int)$interval;
        if($interval>=15) $interval=3;
        if($interval>=5) $interval=2;
        $interval = $interval >= 1 && 3 <= $interval ? $interval-1 : 0;
        
        
        $coreCount = self::numCpuCores();
        $procCount = self::processCount();
        /*
        $load  = $rs[$interval];
        $pa = $load * $procCount;
        
        return round($pa,2);*/
        
        $load  = $rs[0];
        $pa = $load * $procCount;
        if($interval==0) return round($pa,2);
        
        $load  = $rs[$interval];
        return round($pa * $load * $coreCount,2);
        
        /*if($load<1) $load +=1; 
        $load = $load* $coreCount;
        $base = $coreCount/2;*/
        
        //return round(($load * 100) / $coreCount,2);
        //return round(($load * 10) / $coreCount,2);
    }
    /*private*/ static function numCpuCores()
    {
        static $numCpus;
        if(isset($numCpus)) return $numCpus;
        
      $numCpus = 1;
     
      if (is_file('/proc/cpuinfo'))
      {
        $cpuinfo = file_get_contents('/proc/cpuinfo');
        preg_match_all('/^processor/m', $cpuinfo, $matches);
     
        $numCpus = count($matches[0]);
      }
      else if ('WIN' == strtoupper(substr(PHP_OS, 0, 3)))
      {
        $process = @popen('wmic cpu get NumberOfCores', 'rb');
     
        if (false !== $process)
        {
          fgets($process);
          $numCpus = intval(fgets($process));
     
          pclose($process);
        }
      }
      else
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
    
}
?>