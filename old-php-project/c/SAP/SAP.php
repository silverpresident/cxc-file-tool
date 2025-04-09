<?php
/**
 * @author Edwards
 * @copyright 2010
 * 
 * SAP is not limited to ORS or ESMS and is used as a general framework by elixom
 * CORE_DIR 
 */
namespace{
ini_set('display_errors', 'Off');
//error_reporting( E_ALL ^ E_NOTICE);
if (! function_exists('__er')) {
    function __er($a){
        if(func_num_args()>1){
            foreach(func_get_args() as $it){
                __er($it);
            }
            return;
        }
        if(is_scalar($a))
            error_log(var_export($a,1));
        else
            error_log(print_r($a,1));
    }
}

if (!defined('CORE_DIR')){
    throw new Exception('CONSTANT "CORE_DIR" must be defined before including SAP');
}

class SAP
{
    const VERSION = 2017050272;
    
    public static function name()
    {
        //DEPRECATED
        return 'SAP';
    }
    public static function versionLabel()
    {
        //DEPRECATED
        return "v2015 By Shane Edwards";
    }
    public static function version()
    {
        return "7.6";
    }
    public static function released()
    {
        //DEPRECATED
        return "Tuesday, June 3, 2013";
    }
    public static function cacheVersion(){
        static $cv = null;
        if ($cv === null){
            $cv = self::VERSION . filemtime(__FILE__);
        }
        return $cv;
    }
    private static function _getCharcode($str,$i){
        return self::_uniord(substr($str, $i, 1));
    }
    private static function _fromCharCode(){
        $output = '';
        $chars = func_get_args();
        foreach($chars as $char){
            $output .= chr((int) $char);
        }
        return $output;
    }
    private static function _uniord($c){
        $h = ord($c{0});
        if ($h <= 0x7F) {
            return $h;
        } else if ($h < 0xC2) {
            return false;
        } else if ($h <= 0xDF) {
            return ($h & 0x1F) << 6 | (ord($c{1}) & 0x3F);
        } else if ($h <= 0xEF) {
            return ($h & 0x0F) << 12 | (ord($c{1}) & 0x3F) << 6 | (ord($c{2}) & 0x3F);
        } else if ($h <= 0xF4) {
            return ($h & 0x0F) << 18 | (ord($c{1}) & 0x3F) << 12 | (ord($c{2}) & 0x3F) << 6 | (ord($c{3}) & 0x3F);
        } else {
            return false;
        }
    }
    public static function simpleEncrypt($message, $cypherKey)
    {
        //https://www.henryalgus.com/creating-basic-javascript-encryption-between-frontend-and-backend/
        $result = "";
        for($i = 0;$i < strlen($message);$i++) {
        	$a = self::_getCharcode($message,$i);
        	$b = $a ^ $cypherKey;
        	$result .= self::_fromCharCode($b);
        }
        
        return $result;
    }
    public static function isOffline(){
        $p = SAP::root( 'offline.html');
        if (file_exists($p)) return $p;
        return false;
    }
    public static function checkCache($etag, $lastdate, $dieOnMatch=true){
        $match = false;
        $HTTP_IF_NONE_MATCH = isset($_SERVER['HTTP_IF_NONE_MATCH'])?$_SERVER['HTTP_IF_NONE_MATCH']:'';
        $HTTP_IF_MODIFIED_SINCE = isset($_SERVER['HTTP_IF_MODIFIED_SINCE'])?$_SERVER['HTTP_IF_MODIFIED_SINCE']:'';
        
        if ($HTTP_IF_NONE_MATCH && $etag)
        {
            $mtag = trim(trim($HTTP_IF_NONE_MATCH),'"');
            if ( $mtag == $etag) 
            {
                $match = 1;
            }
            $HTTP_IF_MODIFIED_SINCE ='';
        }
        if ($HTTP_IF_MODIFIED_SINCE && $lastdate)
        {
            if (!($lastdate instanceof \DateTime)){
                $lastdate = new \DateTime($lastdate);
            }
            if ($lastdate->format('U') >= @strtotime($HTTP_IF_MODIFIED_SINCE)) 
            {
                $match = 2;
            }
        }
        if ($match && $dieOnMatch){
            if ($etag){
                 Header("ETag: \"$etag\"");
            }
            if ($lastdate){
                $temp = $lastdate->format('D, d M Y H:i:s');
                Header("Last-Modified: $temp GMT");
            }
            if ($match == 1){
                header("HTTP/1.1 304 Not Modified Etag Same");
            }
            if ($match == 2){
                header("HTTP/1.1 304 Not Modified Date Checked");
            }
            die();
        }
        return $match;
    }
    public static function httpsRedirect($absolute_root){
        if (empty($absolute_root)){
            $absolute_root = 'https://' .filter_input(INPUT_SERVER, 'HTTP_HOST');
        }
        if (parse_url($absolute_root,PHP_URL_SCHEME) !== 'https'){
            $parsed_url = parse_url($absolute_root);
            $scheme = 'https://'; 
            $host     = isset($parsed_url['host']) ? $parsed_url['host'] : ''; 
            $port     = isset($parsed_url['port']) ? ':' . $parsed_url['port'] : ''; 
            $path     = isset($parsed_url['path']) ? $parsed_url['path'] : ''; 
            $absolute_root = "{$scheme}{$host}{$port}{$path}";
        }
        $absolute_root = rtrim($absolute_root,'/');
        $HTTP_UPGRADE_INSECURE_REQUESTS = getenv('HTTP_UPGRADE_INSECURE_REQUESTS');
        
        if ($HTTP_UPGRADE_INSECURE_REQUESTS){
            $l = strlen($absolute_root);
            $SCRIPT_URI = filter_input(INPUT_SERVER, 'SCRIPT_URI');
            if ($SCRIPT_URI === null){
                $SCRIPT_URI = getenv('SCRIPT_URI');
            }
            if ($absolute_root == substr($SCRIPT_URI,0,$l)){
                return;
            }
            
            $https = filter_input(INPUT_SERVER, 'HTTPS');
            if(!empty($https) && ('off' !== $https)){
                return;
            }
            $HTTP_X_FORWARDED_PROTO = filter_input(INPUT_SERVER, 'HTTP_X_FORWARDED_PROTO');
            if($HTTP_X_FORWARDED_PROTO === 'https'){
                return;
            }
            $REQUEST_SCHEME = filter_input(INPUT_SERVER, 'REQUEST_SCHEME');
            if($REQUEST_SCHEME === 'https'){
                return;
            }
            
            if ( ($https === null) &&($HTTP_X_FORWARDED_PROTO === null) &&($REQUEST_SCHEME === null)){
                $https = getenv('HTTPS');
                if(!empty($https) && ('off' !== $https)){
                    return;
                }
                $HTTP_X_FORWARDED_PROTO = getenv('HTTP_X_FORWARDED_PROTO');
                if($HTTP_X_FORWARDED_PROTO === 'https'){
                    return;
                }
                $REQUEST_SCHEME = getenv('REQUEST_SCHEME');
                if($REQUEST_SCHEME === 'https'){
                    return;
                }
            }
            if ($SCRIPT_URI){
                $CURRENT_URI = $SCRIPT_URI;
            }else {
                $CURRENT_URI = filter_input(INPUT_SERVER, 'HTTP_HOST');
                if ($CURRENT_URI === null){
                    $CURRENT_URI = getenv('HTTP_HOST').getenv('REQUEST_URI');
                } else {
                    $CURRENT_URI .= filter_input(INPUT_SERVER, 'REQUEST_URI');
                }
            }
            $l = strlen($absolute_root)-8;
            //error_loG(sprintf("-- A == B: |%s| == |%s|", substr($absolute_root,8),substr($CURRENT_URI,0,$l)));
            
            if (substr($absolute_root,8) == substr($CURRENT_URI,0,$l)){
                
                $absolute_url = $absolute_root .  substr($CURRENT_URI,$l);
                //error_loG("====ABS URL IS: $absolute_url|");
                //error_loG("==== MADE FROM C: $CURRENT_URI|");
            } else {
                $path = parse_url($absolute_root,PHP_URL_PATH);
                $REQUEST_URI = getenv('REQUEST_URI');
                $absolute_url = $absolute_root. $REQUEST_URI;
                if ($path){
                    $l = strlen($path);
                    if ($path == substr($REQUEST_URI,0,$l)){
                        $absolute_url = $absolute_root. substr($REQUEST_URI,$l);
                        //error_log("X abs: |$absolute_url|");
                    }
                }
                //error_loG("====ABS URL IS: $absolute_url|");
                //error_loG("==== MADE FROM R: $REQUEST_URI|");
                //error_loG("==== MADE FROM P: $path|");
            }
            
            //error_log("REDIRECTING TO SECURE HOST: |$absolute_url|");
            header("Location: $absolute_url",true,307);
            die();
        }
    }
    public static function setFileResponse($filename){
        if (!$filename){
            return;
        }
        $RES = self::getInstance('RESPONSE');
        $mime = self::getMime($filename);
        $lmod = '';
        if (file_exists($filename)){
            $RES->setStatus(200);
            $RES->setDownload($filename);
            $bn = \basename($filename);
            $description = "Downloaded from resource({$bn}) ";
            $age = 60 * 60 * 24; //1 day
            if (substr($mime,0,5) =='image'){
                $age *= 28;
            } elseif ($mime =='application/javascript'){
                $age *= 30;
            } elseif ($mime =='text/css'){
                $age *= 60;
            } elseif (substr($mime,0,11) =='application'){
                $age *= 14;
            } else {
                $age *= 90;
            }
            $temp = filemtime($filename);
            if ($temp)
                $lmod = date_create("@$temp",new \DateTimeZone("GMT"))->format('D, d M Y H:i:s');
        } else {
            $RES->setStatus(404);
            $description = "Requested content not available. $filename";
            $age = 60;    
        }
        if ($age && !SAP::isDemoSite()){
            $RES->setHeader('Cache-control',"max-age=$age");
        }else
            $RES->setHeader('Cache-control',"no-cache");
        
        $RES->setDate('now');
        if ($mime){
            $RES->setContentType($mime);
        }
        if ($lmod)$RES->setHeader('Last-Modified',$lmod);
        $RES->setHeader('Content-Description',"$description");
    }
    public static function send404(){
        header('Connection: close');
        $p = SAP::root( '404.php');
        if (file_exists($p)){
            $r = include_once($p);
            if (strlen($r) > 1) echo $r;
        } else {
            echo "Page not found!";
        }
        die();
    }
    public static function sendFavIcon(){
        header('Connection: close');
        $filename = '';
        foreach (array('favicon.png','favicon.ico','favicon.gif','favico.png') as $f){
            $p = SAP::assets('icon',$f);
            if (file_exists($p)){
                $filename = $p;
                break;
            }
            $p = SAP::root($f);
            if (file_exists($p)){
                $filename = $p;
                break;
            }
        }
        if ($filename){
            $mime = self::getMime($filename);
            $size = filesize($filename);
            $age = 60 * 60; //1 hour
            header("Content-Disposition: inline; filename=$f" );
            Header("Cache-Control: max-age=$age");
            $temp = date_create('now',new DateTimeZone("GMT"))->format('D, d M Y H:i:s');
            Header("Date: $temp GMT");
            if ($mime)Header("Content-Type: $mime");
            header("Content-Length: {$size}");
            Header("Status: 200");
            if ($_SERVER["REQUEST_METHOD"] == 'HEAD') die();
            echo file_get_contents($filename);
        }
        die();
    }
    public static function sendSiteMap(){
        $RES = SAP::getInstance('RESPONSE');
        $RES->setContentType('text/xml')
            ->setCharset('iso-8859-1');
        
        $SM = ELIX::sitemap();
        $SM->add(SAP::absolute(),null,'weekly');
        SAP::propagateCall('SiteMap',$SM);
        $RES->setBody($SM->toString());
        $RES->send();
        die();
    }
    public static function sendRobotPage(){
        $PATH = SAP::getInstance('PATH');
        $RES = SAP::getInstance('RESPONSE');
        $RES->setContentType('text/plain')
            ->setCharset('iso-8859-1');
        SAP::propagateCall('Robots',$RES,$PATH);
        $RES->send();
    }
    
    public static function sendCron(){
        $PATH = SAP::getInstance('PATH');
        $RES = SAP::getInstance('RESPONSE');
        SAP::propagateCall('CronTrigger',$RES,$PATH);
        $RES->send();
    }
    public static function sendSeoPage()
    {
        $PATH = SAP::getInstance('PATH');
        $RES = SAP::getInstance('RESPONSE');
        SAP::propagateCall('Seo',$RES,$PATH);
        $RES->send();
    }
    public static function isSiteMap($pg){
        if (!$pg) return false;
        $pg =strtolower($pg);
        if ($pg == 'sitemap') return true;
        if ($pg == 'sitemap.xml') return true;
        return false;
    }
    public static function isSeoPage($pg){
        if (!$pg) return false;
        $pg =strtolower($pg);
        $temp =substr($pg,0,6);
        $arr =array('google','bingsiteauth.xml');
        return in_array($temp,$arr)||in_array($pg,$arr);
    }
    public static function isRobotPage($pg){
        if (!$pg) return false;
        $pg =strtolower($pg);
        if ($pg == 'robots') return true;
        if ($pg == 'robots.txt') return true;
        return false;
    }
    private static $endpointValue =null;
    public static function isEndpoint($pg=null){
        if (func_num_args()==0){
            return self::$endpointValue;
        }
        if (!$pg) return false;
        $pg2 = $pg =strtolower($pg);
        if ($i = strpos($pg,'.')){
            $pg2 = substr($pg,0, $i);
        }
        if ($pg == 'cron' || $pg2 == 'cron'){
            self::$endpointValue = 1;
            return self::$endpointValue;
        }
        if ($pg == 'ajax' || $pg2 == 'ajax'){
            self::$endpointValue =32;
            return self::$endpointValue;
        }
        if ($pg == 'api' || $pg2 == 'api'){
            self::$endpointValue = 33;
            return self::$endpointValue;
        }
        self::$endpointValue = 0;
        return false;
    }
    public static function isAjaxEndpoint(){
        return self::$endpointValue == 32;
    }
    public static function isApiEndpoint(){
        return self::$endpointValue == 33;
    }
    public static function isCronEndpoint(){
        return self::$endpointValue == 1;
    }
    
    public static function isFavIcon($pg){
        if (!$pg) return false;
        $pg =strtolower($pg);
        if ($pg == 'favicon') return true;
        if ($i = strpos($pg,'.')){
            $pg = substr($pg,0, $i);
            if ($pg == 'favicon') return true;
        }
        return false;
    }
    public static function isMobileDevice(){
        static $result = null;
        if ($result === null){
            $http_useragent =isset($_SERVER["HTTP_USER_AGENT"])?' ' .strtolower($_SERVER["HTTP_USER_AGENT"]):'';
            $isM = false;
            if ($http_useragent){
                if (preg_match('/(up\.browser|up\.link|mmp|symbian|smartphone|midp|wap|phone|android)/i', $http_useragent)){
                    $isM =  true;
                } else {
                    $regex_match="/(nokia|iphone|android|motorola|^mot\-|softbank|foma|docomo|kddi|up\.browser|up\.link|";
                    $regex_match.="htc|dopod|blazer|netfront|helio|hosin|huawei|novarra|CoolPad|webos|techfaith|palmsource|";
                    $regex_match.="blackberry|alcatel|amoi|ktouch|nexian|samsung|^sam\-|s[cg]h|^lge|ericsson|philips|sagem|wellcom|bunjalloo|maui|";    
                    $regex_match.="symbian|smartphone|midp|wap|phone|windows ce|iemobile|^spice|^bird|^zte\-|longcos|pantech|gionee|^sie\-|portalmmm|";
                    $regex_match.="jig\s browser|hiptop|^ucweb|^benq|haier|^lct|opera\s*mobi|opera\*mini|320x320|240x320|176x220";
                    $regex_match.=")/i";        
                       $isM =  preg_match($regex_match, $http_useragent);
                    if (!$isM){
                        $a  =isset($_SERVER["HTTP_ACCEPT"])?strtolower($_SERVER["HTTP_ACCEPT"]):'';
                        if ((strpos($a,'application/vnd.wap.xhtml+xml') )){
                            $isM = true;
                        }
                    }
                }
            }
            $result = $isM?1:0;
        }
        return $result;
    }
    public static function isMSIE()
    {
        static $UA;
        if (empty($UA) && isset($_SERVER['HTTP_USER_AGENT'])) $UA = $_SERVER['HTTP_USER_AGENT'];
        if (preg_match('/MSIE/i',$UA) && !preg_match('/Opera/i',$UA)){
            return true;
        }
        if (strpos($UA,'Trident/7')){
            return true;
        }
        if (strpos($UA,'Edge/12')){
            return true;
        }
        return false;
    }
    //used so file name in VIEW|FRAGMENT|COMPONENT does not conflict with variable extracted
    static private $temp_file_name = '';
    public static function view($pg, $variables=array()){
        if (!$pg) return null;
        $pg = str_replace('.',DIRECTORY_SEPARATOR, $pg);
        
        $p = SAP::core('view',"$pg.php");
        if (!file_exists($p)){
            $p = SAP::core('pg',"$pg.php");
        }
        if (!file_exists($p)){
            throw new \SAP\ViewNotFoundException($pg);
        }
        
        self::$temp_file_name = $p;
        unset($p,$pg);
        $GET = self::getInstance('GET');
        if (!empty($variables)){
            extract($variables);
        }
        return include(self::$temp_file_name);
    }
    public static function fragment($pg, $variables=array())
    {
        if (!$pg) return null;
        $pg =str_replace('.',DIRECTORY_SEPARATOR, $pg);
        
        $p = SAP::core('component',"$pg.php");
        if (!file_exists($p)){
            $p = SAP::core('view',"$pg.inc");
        }
        if (!file_exists($p)){
            $p = SAP::core('pg',"$pg.inc");
        }
        if (!file_exists($p)){
            return null;
        }
        try{
            $GET = self::getInstance('GET');
            self::$temp_file_name = $p;
            unset($p,$pg);
            if (!empty($variables)){
                extract($variables);
            }
            return include(self::$temp_file_name);
        } catch (\Exception $e)
        {
            $pgcon = HTML::build('div');
            $p = $pgcon->create('p')->append("There was a fault which may cause incorrect or incomplete data. <br />");
            $p->append($e->getMessage());
            if (0 != error_reporting())
            {
                self::getLogger()->log($e->getMessage(),' -- TRACE --',$e->getTraceAsString());
            }
            return $pgcon;
        }
    }
    
    public static function component($pg, $variables=array())
    {
        return self::fragment($pg, $variables);
    }
    public static function content($pg, $variables=array())
    {
        try{
            return self::view($pg, $variables);
        } catch (\Exception $e)
        {
            $pgcon = HTML::build('div');
            $p = $pgcon->create('p')->append("There was a fault which may cause incorrect or incomplete data. <br />");
            $p->append($e->getMessage());
            if (0 != error_reporting())
            {
                self::getLogger()->log($e->getMessage(),' -- TRACE --',$e->getTraceAsString());
            }
            return $pgcon;
        }
    }
    
    public static function getHtmlBlock($pg)
    {
        try{
            $pg =str_replace('.',DIRECTORY_SEPARATOR, $pg);
            $filepath = SAP::core('view',$pg.'.php');
            if ( !file_exists($filepath)){
                $filepath = SAP::core('pg',$pg.'.php');
            }
            if ( file_exists($filepath)){
                $r = include($filepath);
            } else {
                $r = null;
            }
            if (is_object($r)){
                return $r;
            }
            $main = HTML::build('div');
            if ($r !== null){
                $main->append($r);
            }
        } catch (exception $e){
            $main = HTML::build('div');
            $p = $main->create('p')->append("There was a fault which may cause incorrect or incomplete data. <br />");
            $p->append($e->getMessage());
            if (0 != error_reporting())
            {
                self::getLogger()->log($e->getMessage(),' -- TRACE --',$e->getTraceAsString());
            }
        }
        return $main;
    }
    
    public static function getScriptBlock($pg)
    {
        $script = HTML::build('script');
        try{
            $pg =str_replace('.',DIRECTORY_SEPARATOR, $pg);
            $filepath = SAP::core('view',$pg.'.js');
            if ( !file_exists($filepath)){
                $filepath = SAP::core('pg',$pg.'.js');
            }
            if ( file_exists($filepath)){
                $script->append(file_get_contents($filepath));
            } else {
                $script->append("/* DOES NOT EXIST: {$pg }.js*/");
            }
        } catch (exception $e){
            $script->append("/* There was a fault which may cause incorrect or incomplete data.\n ERR: {$e->getMessage()} */");
            if (0 != error_reporting())
            {
                self::getLogger()->log($e->getMessage(),' -- TRACE --',$e->getTraceAsString());
            }
        }
        return $script;
    }
    public static function getStyleSheet($pg)
    {
        $sheet = HTML::build('style');
        try{
            $pg =str_replace('.',DIRECTORY_SEPARATOR, $pg);
            $filepath = SAP::core('view',$pg.'.css');
            if ( !file_exists($filepath)){
                $filepath = SAP::core('pg',$pg.'.css');
            }
            if ( file_exists($filepath)){
                $sheet->append(file_get_contents($filepath));
            } else {
                $sheet->append("/* DOES NOT EXIST: {$pg }.css */");
            }
        } catch (exception $e){
            $sheet->append("/* There was a fault which may cause incorrect or incomplete data.\n ERR: {$e->getMessage()} */");
            if (0 != error_reporting())
            {
                self::getLogger()->log($e->getMessage(),' -- TRACE --',$e->getTraceAsString());
            }
        }
        return $sheet;
    }
    public static function getJsonFromFile($pg)
    {
        $a = [];
        try{
            $pg = str_replace('.',DIRECTORY_SEPARATOR, $pg);
            $filepath = SAP::core('view',$pg.'.json');
            if ( !file_exists($filepath)){
                $filepath = SAP::core('pg',$pg.'.json');
            }
            if ( file_exists($filepath)){
                $str = file_get_contents($filepath);
                if($str && is_string($str)){
                    $a = @json_decode($str,true);
                    if(is_array($a)){
                        return $a;
                    } else if ($a ===null){
                        return ['_ERROR_'=>json_last_error(), '_DATA_'=>$str];
                    }
                    return $a;
                }
            } else {
                return ['_ERROR_'=>"/* DOES NOT EXIST: pg/{$pg }.json */"];
            }
        } catch (exception $e){
            if (0 != error_reporting())
            {
                self::getLogger()->log($e->getMessage(),' -- TRACE --',$e->getTraceAsString());
            }
            return ['_ERROR_'=>"/* There was a fault which may cause incorrect or incomplete data.\n ERR: {$e->getMessage()} */"];
        }
        return $a;
    }
    public static function pageAuth($pg)
    {
        //DEPRECATED
        /*
        the use of this method is deprecated.
        the use of the 'pgu' folder is also deprecated
        */
        if (!$pg) return null; 
        
        $p = SAP::core('pg',"$pg.php");
         if (!file_exists($p)){
            $p = SAP::core('pgu',"$pg.php");
        }
        if (!file_exists($p)){
            return null;
        }
        try{
            $r = include($p);
            if (is_scalar($r)){
                return HTML::build('div')->append($r);
            }
            return $r;
        } catch (\Exception $e)
        {
            $pgcon = HTML::build('div');
            $p = $pgcon->create('p')->append("There was a fault which may cause incorrect or incomplete data. <br />");
            $p->append($e->getMessage());
            if (0 != error_reporting())
            {
                self::getLogger()->log($e->getMessage(),' -- TRACE --',$e->getTraceAsString());
            }
            return $pgcon;
        }
    }
    public static function page($pg)
    {
        //DEPRECATED: for view() method
        if (!$pg) return null;
        
        $p = SAP::core('pgu',"$pg.php");
        if (!file_exists($p)){
            return null;
        }
        try{
            $r = include($p);
            if (is_scalar($r)){
                return HTML::build('div')->append($r);
            }
            return $r;
        } catch (\Exception $e)
        {
            $pgcon = HTML::build('div');
            $p = $pgcon->create('p')->append("There was a fault which may cause incorrect or incomplete data. <br />");
            $p->append($e->getMessage());
            if (0 != error_reporting())
            {
                self::getLogger()->log($e->getMessage(),' -- TRACE --',$e->getTraceAsString());
            }
            return $pgcon;
        }
    }
    
    
    public static function isPage($pg){
        $p = SAP::core('pg',"$pg.php");
        if (file_exists($p)) return true;
        
        $p = SAP::core('view',"$pg.php");
        return file_exists($p);
    }
    public static function isContent($pg){
        $p = SAP::core('pg',"$pg.php");
        return file_exists($p);
    }
    public static function isFragment($pg){
        $p = SAP::core('pg',"$pg.inc");
        return file_exists($p);
    }
    public static function isComponent($pg){
        $p = SAP::core('component',"$pg.php");
        return file_exists($p);
    }
    private function is_session_started()
    {
        if ( php_sapi_name() !== 'cli' ){
            if ( version_compare(phpversion(), '5.4.0', '>=') ){
                return session_status() === PHP_SESSION_ACTIVE ? true : false;
            } else {
                return session_id() === '' ? false : true;
            }
        }
        return false;
    }
    public static function startSession($cache_type='AUTO', $cookie_domain=''){
        if ($cookie_domain !== null){
            if ($cookie_domain == ''){
                $x = explode('.',self::domain());
                if (count($x) >2) array_shift($x);
                $cookie_domain =  '.' . implode('.',$x);
            }
            $currentCookieParams = session_get_cookie_params(); 
            session_set_cookie_params( 
                $currentCookieParams["lifetime"], 
                $currentCookieParams["path"], 
                $cookie_domain, 
                $currentCookieParams["secure"], 
                $currentCookieParams["httponly"] 
            );
        }
        $cache_type = strtoupper($cache_type);
        if (!isset($_SESSION)){
            session_cache_limiter('private_no_expire');
            @session_start();
            if ($cache_type == 'AUTO') $cache_type='CONTROLLED';
        }
        if (!headers_sent() && $cache_type=='CONTROLLED'){
            if (function_exists('header_remove')){
                header_remove( 'Expires' );
                header_remove( 'Pragma' );
                header_remove( 'Cache-control' );
            } else {
                header('Expires:',true);
                header('Pragma:',true);
                header('Cache-control:',true);
            }
        }
    }
    public static function define_autoload()
    {
        static $def = false;
        if ($def) return;
        error_log('deprecated! SAP: define_autoload (autoload is auto registered using spl)');
        //spl_autoload_register(array('SAP','LoadApi'));
        $def = true;
    }
    
    public static function makeEtag(){
        $a =func_get_args();
        $a[] =self::version();
        return md5(implode('-',$a));
    }
    public static function getMime($filename)
    {
        $mime = 'application/octet-stream';
            $mime_types = array(
                'txt' => 'text/plain',
                'htm' => 'text/html',
                'html' => 'text/html',
                'php' => 'text/html',
                'css' => 'text/css',
                'js' => 'application/javascript',
                'json' => 'application/json',
                'xml' => 'application/xml',
                'swf' => 'application/x-shockwave-flash',
                'flv' => 'video/x-flv',
    
                // images
                'png' => 'image/png',
                'jpe' => 'image/jpeg',
                'jpeg' => 'image/jpeg',
                'jpg' => 'image/jpeg',
                'gif' => 'image/gif',
                'bmp' => 'image/bmp',
                'ico' => 'image/vnd.microsoft.icon',
                'tiff' => 'image/tiff',
                'tif' => 'image/tiff',
                'svg' => 'image/svg+xml',
                'svgz' => 'image/svg+xml',
    
                // archives
                'zip' => 'application/zip',
                'rar' => 'application/x-rar-compressed',
                'cab' => 'application/vnd.ms-cab-compressed',
    
                // audio/video
                'mp3' => 'audio/mpeg',
                'qt' => 'video/quicktime',
                'mov' => 'video/quicktime',
    
                // adobe
                'pdf' => 'application/pdf',
                // ms office
                'doc' => 'application/msword',
                'rtf' => 'application/rtf',
                'xls' => 'application/vnd.ms-excel',
                'ppt' => 'application/vnd.ms-powerpoint',
    
                // open office
                'odt' => 'application/vnd.oasis.opendocument.text',
                'ods' => 'application/vnd.oasis.opendocument.spreadsheet',
            );
            $x =explode('.',$filename);
            $ext = strtolower(array_pop($x));
            if (array_key_exists($ext, $mime_types)){
                $mime = $mime_types[$ext];
            }
        return $mime;
    }
    public static function isCoreService($page){
        if (SAP::isCronEndpoint($page)){
            return 1;
        } elseif (SAP::isFavIcon($page)){
            return 2;
        } elseif (SAP::isSiteMap($page)){
            return 4;
        } elseif (SAP::isRobotPage($page)){
            return 8;
        } elseif (SAP::isSeoPage($page)){
            return 16;
        }
        return false;
    }
    public static function sendCoreService($page){
        if (SAP::isCronEndpoint($page)){
            SAP::sendCron();
            return true;
        } elseif (SAP::isFavIcon($page)){
            SAP::sendFavIcon();
            return true;
        } elseif (SAP::isSiteMap($page)){
            SAP::sendSiteMap();
            return true;
        } elseif (SAP::isRobotPage($page)){
            SAP::sendRobotPage();
            return true;
        } elseif (SAP::isSeoPage($page)){
            SAP::sendSeoPage($page);
            return true;
        }
        return false;
    }
    
    public static function loadPost($name){
        $f = SAP::core('post',"{$name}.php");
        if (file_exists($f)){
            include_once($f);
            return true;
        }
        return false;
    }
    
    public static function getInstance($object=''){
        static $instance = array();
        $item = strtoupper($object);
        if (!isset($instance[$item])){
            $class = "SAPI_{$object}";
            if ($item=='METHOD'){
                $s = self::getInstance('SERVER');
                if ($s->REQUEST_METHOD=='GET' || $s->REQUEST_METHOD=='POST'){
                    $item = $s->REQUEST_METHOD;
                } else {
                    $item = count($_POST)?'POST':'GET';
                }
            }
            if (in_array($item,array('REQUEST','POST','FILES','GET','SERVER','INPUT','HEADERS','ENV'))){
                $instance[$item] = ELIX::request()->$item();
            } elseif (in_array($item,array('HOST','REMOTE'))){
                $x = "get{$item}";
                $instance[$item] = self::getInstance('SERVER')->$x();
            } elseif ($item=='PATH'){
                $instance[$item] =SAP::path();
            } elseif ($item=='RESPONSE'){
                $R = ELIX::eli_response();
                $R->server = 'AJAX/1.1 (ELIXOM-SAP/3) SMP/SAP/' . SAP::version() . '(+' . SAP::www() .')';
                $R->setLastModified('now');
                $R->setDate('now');
                $instance[$item] = $R;
            } elseif ($item=='HTML'){
                return SAP::html();
            } elseif ($item=='COOKIE'){
                $instance[$item] = ELIX::cookie()->getManager();
            } elseif ($item=='CRON'){
                $instance[$item] = new \SAP\cron_item;
            } elseif ($item=='PDF'){
                $instance[$item] = new EXPDF('P','in','letter');
            } elseif (class_exists($class)){
                $instance[$item] = new $class();
            } elseif (class_exists($object)){
                $instance[$item] = new $object();
            }
        }
        return $instance[$item];
    }
    
    public static function getLogger()
    {
        static $a = null;
        if ($a === null){
            
            $path = getcwd() .'/.logs';
            if (!is_dir($path)){
                @mkdir($path);
            }
            $fp = $path. '/.htaccess';
            if (!file_exists($fp)) file_put_contents($fp,"deny from all\nOptions -Indexes");
            
            $d = date('YmdH').'m'.date('i');
            $fp = $path . "/{$d}.log";
            $a = ELIX::log($fp);
        }
        return $a;
    }
    protected static function path(){
        static $path = null;
        if (null ===$path){
            $path = ELIX::request()->path();
            $path->trim(explode(DIRECTORY_SEPARATOR,$_SERVER['SCRIPT_NAME']));
            $path->trim('index.php');
            $path->pageSplit();
            $path->trim('index');
        }
        return $path;
    }
    
    private static function html(){
        static $RES = null;
        if ((null ===$RES)){
            $RES = new \SAP\html_response_envelop;
            $RES->initialize();
        }
        return $RES;
    }
    public static function getJScomposer($include_sap_core){
        $JSC = new \SAP\js_file_composer();
        if ($include_sap_core){
            $dirpath = __DIR__.DIRECTORY_SEPARATOR .'js'.DIRECTORY_SEPARATOR;
            $JSC->addFile($dirpath. 'sap.js' );
            $JSC->addDirectory($dirpath,'.js');
        }
        return $JSC;
    }
    public static function getCSScomposer($include_sap_core){
        $CSSC = new \SAP\css_file_composer();
        if ($include_sap_core){
            $dirpath = __DIR__.DIRECTORY_SEPARATOR.'css'.DIRECTORY_SEPARATOR;
            $CSSC->addFile($dirpath. 'sap.css' );
            $CSSC->addDirectory($dirpath,'.css');
        }
        return $CSSC;
    }
    public static function getSCSScomposer($include_sap_core){
        $CSSC = new \SAP\scss_css_file_composer();
        if ($include_sap_core){
            $dirpath = __DIR__.DIRECTORY_SEPARATOR.'scss'.DIRECTORY_SEPARATOR;
            $CSSC->addFile(rtrim($dirpath,DIRECTORY_SEPARATOR ).DIRECTORY_SEPARATOR . 'variables.scss' );
            $CSSC->addDirectory($dirpath,'.scss');
        }
        return $CSSC;
    }
    public static function getLesscomposer($include_sap_core){
        $CSSC = new \SAP\less_css_file_composer();
        if ($include_sap_core){
            $dirpath = __DIR__.DIRECTORY_SEPARATOR.'less'.DIRECTORY_SEPARATOR;
            $CSSC->addFile(rtrim($dirpath,DIRECTORY_SEPARATOR ).DIRECTORY_SEPARATOR . 'variables.less' );
            $CSSC->addDirectory($dirpath,'.less');
        }
        return $CSSC;
    }
    public static function setErrorToException(){
        //THIS function is to be used of debuggin only
        $fx = function ($errNumber, $errStr, $errFile, $errLine ){
            if (0 == error_reporting())
            {
                return;
            }
            throw new ErrorException($errStr, 0, $errNumber, $errFile, $errLine);
        };
        set_error_handler($fx);
    }
    public static function setLogLevel($level = 'DEVELOPER'){
        $level = strtoupper($level);
        $old = error_reporting();
        ini_set('ignore_repeated_source', 0);
        ini_set('ignore_repeated_errors', 1);
        if ($level == 'DEVELOPER'){
            error_reporting(-1);
            ini_set('display_errors', 1);
            ini_set('display_startup_errors', 1);
        } elseif ($level == 'ALL'){
            error_reporting(E_ALL);
            ini_set('display_errors', 0);
            ini_set('log_errors', 1);
        } elseif ($level == 'NONE'){
            error_reporting(0);
            ini_set('display_errors', 0);
            ini_set('log_errors', 1);
        } else {
            #PRODUCTION
            error_reporting( E_ALL ^ E_NOTICE);
            ini_set('display_errors', 0);
            ini_set('log_errors', 1);
        }
        static $first = true;
        if ($first){
            $first = false;
        }else if ($old != error_reporting()){
            error_log( "error_reporting level changed ($level)");
        }
    }
    public static function serverRoot($file='')
    {
        static $serverRoot = null;
        if ((null ===$serverRoot))
        {
            if (isset($_SERVER["DOCUMENT_ROOT"])){
                $serverRoot = $_SERVER["DOCUMENT_ROOT"] . DIRECTORY_SEPARATOR;
            }else
                $serverRoot = dirname(self::root()) . DIRECTORY_SEPARATOR;
        }
        if (func_num_args()>1){
            $file = implode(DIRECTORY_SEPARATOR,func_get_args());
        }
        $file = ltrim($file,DIRECTORY_SEPARATOR);
        return $serverRoot.$file;
    }
    private static $root = null;
    public static function setRoot($root=''){
        if (null === $root)
        {
            $root = dirname(__DIR__);
        } elseif (!$root)
        {
            $root = dirname(CORE_DIR);
        } else {
            $root = realpath($root);
        }
        self::$root = rtrim($root,DIRECTORY_SEPARATOR). DIRECTORY_SEPARATOR;
        return self::$root;
    }
    public static function root($file=''){
        
        if (null === self::$root)
        {
            $root = dirname(CORE_DIR);
            self::$root = rtrim($root,DIRECTORY_SEPARATOR). DIRECTORY_SEPARATOR;
        }
        if (func_num_args()>1){
            $file = implode(DIRECTORY_SEPARATOR,func_get_args());
        }
        $file = ltrim($file,DIRECTORY_SEPARATOR);
        return self::$root.$file;
    }
    public static function core($file='')
    {
        //$r[] = self::root('.core');
        $r = [rtrim(CORE_DIR,DIRECTORY_SEPARATOR)];
        if (func_num_args()){
            $file = implode(DIRECTORY_SEPARATOR,func_get_args());
            $r[] = ltrim($file,DIRECTORY_SEPARATOR);
        }
        return implode( DIRECTORY_SEPARATOR,$r);
    }
    public static function assets($file='')
    {
        $r[] = self::root('.assets');
        if (func_num_args()){
            $file = implode(DIRECTORY_SEPARATOR,func_get_args());
            $r[] = ltrim($file,DIRECTORY_SEPARATOR);
        }
        return implode( DIRECTORY_SEPARATOR,$r);
    }
    
    public static function domain($username=''){
        static $www = null;
        if ((null ===$www))
        {
            $www = $_SERVER['HTTP_HOST'];
        }
        $username = rtrim($username,'@');
        if ($username) $username .='@';
        return $username.$www;
    }
    public static function www($file='')
    {
        static $www = null;
        if ((null ===$www))
        {
            $x = dirname($_SERVER['SCRIPT_NAME']);
            if (substr($x,-1) != '/')
                $x .='/';
            
            $www = '//'. $_SERVER['HTTP_HOST']. $x ;
        }
        if (func_num_args()>1){
            $file = implode('/',func_get_args());
        }
        $file = ltrim($file,'/');
        return $www.$file;
    }
    public static function absolute($file='')
    {
        static $www = null;
        if ((null === $www))
        {
            $x = dirname($_SERVER['SCRIPT_NAME']);
            if (substr($x,-1) != '/')
                $x .='/';
            
            $PROTO = filter_input(INPUT_SERVER, 'HTTP_X_FORWARDED_PROTO');
            if (empty($PROTO)){
                $PROTO = filter_input(INPUT_SERVER, 'REQUEST_SCHEME');
            }
            if (empty($PROTO)){
                if (isset($_SERVER['HTTPS']) && ($_SERVER['HTTPS'] !== 'off')){
                    $PROTO = 'https';
                }
            }
            if (empty($PROTO)){
                $PROTO = 'http';
            }
            $www = $PROTO . '://'. $_SERVER['HTTP_HOST']. $x ;
        }
        if (func_num_args()>1){
            $file = implode('/',func_get_args());
        }
        $file = ltrim($file,'/');
        return $www.$file;
    }
    public static function base($file='')
    {#adds the base url only if necessary
        static $www = null;
        if ((null ===$www))
        {
            $x = dirname($_SERVER['SCRIPT_NAME']);
            if (substr($x,-1) != '/')
                $x .='/';
            
            $www = $_SERVER['HTTP_HOST']. $x ;
            if ($www == $_SERVER['HTTP_HOST']. $x )
                $www = $x ;
            else
                $www = '//'. $_SERVER['HTTP_HOST']. $x ; 
        }
        if (func_num_args()>1){
            $file = implode('/',func_get_args());
        }
        $file = ltrim($file,'/');
        return $www.$file;
    }
    public static function isLocalhost()
    {
        if ($_SERVER['SERVER_ADDR']=='127.0.0.1') return true;
        return (strpos( $_SERVER["HTTP_HOST"],'localhost')!==false);
    }
    
    public static function isDemo()
    {
        trigger_error("todo this function is deprecated; in most cases the
        intention is to test if tthe siste is production (::isAlphaSite) or dev (::isDevSite);
        ::isDemoSite should only return true for the EDEMO High demo site");
        
        
        if (defined("_DEMO_") && _DEMO_) return true;
        if ((strpos($_SERVER["HTTP_HOST"],'sms.') !== false) && strpos($_SERVER["REQUEST_URI"],'_test')) return true;
        if ((strpos($_SERVER["HTTP_HOST"],'smp.') !== false) && strpos($_SERVER["REQUEST_URI"],'_test')) return true;
        if ((strpos($_SERVER["HTTP_HOST"],'demo.') !== false) ) return true;
        return false;
    }
    public static function isDemoSite()
    {////should only return true for the EDEMO High demo site
        if (defined("_DEMO_") && _DEMO_) return true;
        if (isset($_ENV['_DEMO_']) && $_ENV['_DEMO_']) return $_ENV['_DEMO_'];
        return false;
    }
    public static function isDevSite()
    {
        if (defined("_DEV_") && _DEV_) return true;
        if (isset($_ENV['_DEV_']) && $_ENV['_DEV_']) return $_ENV['_DEV_'];
        return false;
    }
    public static function isBetaSite()
    {
        if (defined("_BETA_") && _BETA_) return _BETA_;
        if (isset($_ENV['_BETA_']) && $_ENV['_BETA_']) return $_ENV['_BETA_'];
        return false;
    }
    public static function isAlphaSite()
    {//the demo site is an alpha site
        if (self::isDevSite()) return false;
        if (self::isBetaSite()) return false; 
        return true;
    }
    
    
    public static function getModules(){
        static $a = array();
        if (func_num_args() && func_get_arg(0)) $a = array();
        if (empty($a) && is_dir(SAP::core('E'))){
            $dir = dir(SAP::core('E'));
            if ($dir){
                while (($file = $dir->read()) !== false)
                {
                    if (@filesize(SAP::core('E',$file)) < 10){
                        continue;
                    }
                    if (substr($file,-4) == '.php' ){
                        $pn  = substr($file,0,-4);
                        $a[$pn]= "\\E\\{$pn}";
                    }
                }
                $dir->close();
            }
            asort($a);
        }
        return $a;
    }
    public static function event($data=array()){
        return new \SAP\event($data);
    }
    public static function propagateAjax(){
        $PATH =SAP::getInstance('PATH');
        $REQ = SAP::getInstance('METHOD');
        $GET = SAP::getInstance('GET');
        $RES = SAP::getInstance('RESPONSE');
        if (!headers_sent()){
            if (function_exists('header_remove')){
                header_remove( 'Expires' );
                header_remove( 'Pragma' );
                header_remove( 'Cache-control' );
            } else {
                header('Expires:',true);
                header('Pragma:',true);
                header('Cache-control:',true);
            }
        }
        $PATH->promoteif ('ajax');
        ignore_user_abort(); //ignore the browse stop button
        SAP::propagateCall('ajax',$RES,$REQ,$PATH);
        
        if ( $RES->cached){
            $RES->setStatus(304,'Not Modified cached');
        }else if (isset($_SERVER['HTTP_IF_NONE_MATCH']) && $RES->isCacheable)
        {
            $mtag = trim(trim($_SERVER['HTTP_IF_NONE_MATCH']),'"');
            if ( $mtag == $RES->getETag()) 
            {
                $RES->setStatus(304,'Not Modified Etag Same');
            }
        }
        if (SAP::getRedirect()){
            Header("Status: "  .SAP::getStatus());
            header('Location: '.SAP::getRedirect());
            return ;
        }
        $RES->send();
    }
    public static function propagateCall($method='Ping'){
        $a = func_get_args();
        array_shift($a);
        
        if ($method instanceof \SAP\event){
            $EVENT = $method;
            $method='';
        } else {
            $EVENT = new \SAP\event($method);
        }
        $method2 = $EVENT->which();
        if (substr($method2,0,2) != 'ON') $method2 = 'on' . $method2;
        array_unshift($a,$EVENT);
        
        foreach (SAP::getModules() as $m=>$C){
            if (class_exists($C)){
                if (method_exists($C,$method2)) $method = $method2;
                elseif (method_exists($C,'on')) $method = 'on';
                else $method='';
                
                if ($method){
                    try{
                        $r = call_user_func_array (array($C,$method),$a);
                        if ($EVENT->isStopped()) break;
                    } catch (Exception $e){
                       error_log($e->getMessage());
                       SAP::getLogger()->log($e);
                    }
                } 
            }
        }
        return $EVENT;
    }
        
    //ORIGINALLY RESPONSE
    private static $title=null; //disply in caption tab
    private static $page_title=null; //html displayed on page
    private static $desc=null; 
    public static function setDescription($desc)
    {
        self::$desc=$desc;   
    }
    public static function getDescription(){
        return self::$desc;
    }
    public static function setTitle($html_title,$page_title=null)
    {
        if (!(null ===$html_title))self::$title=$html_title;
        self::$page_title = (null ===$page_title)?$html_title:$page_title;
    }
    public static function setCaption($html_title){
        self::$title=$html_title;
    }
    public static function getTitle(){
        return self::$title;
    }
    public static function getPageTitle(){
        return self::$page_title;
    }
    private static $status='303';
    private static $redirect='';
    public static function redirect($url, $status='303')
    {
        self::$status = $status;
        self::$redirect = filter_var($url, FILTER_SANITIZE_URL);
    }
    public static function getRedirect(){
        if (self::$redirect){
            $url = self::$redirect;
            $temp = strpos($url,'//');
            if ($url == '/'){
                $url = self::base();
            }elseif (($temp === false) || ($temp > 6)){
                $url = self::www($url);
            }
            return $url;
        } else {
            return false;
        }
    }
    public static function getStatus(){
        return self::$status;
    }
    private static $flags=array();
    public static function setFlag($flag, $value=true)
    {
        if (is_array($flag)){
            foreach ($flag as $f){
                self::setFlag($f,$value);
            }
            return;
        }
        $flag = strtolower($flag);
        self::$flags[$flag] = $value;
    }
    public static function getFlag($flag)
    {
        if (isset(self::$flags[$flag])){
            return self::$flags[$flag];
        }
        return false;
    }
    public static function end(){
        if (SAP::getRedirect()){
            header("Status: ".SAP::getStatus());
            header('Location: '.SAP::getRedirect());
        }
        die();
    }
    //END ORIGINAL RESPONSE
    //START ORIGINAL REQUEST
    public static function isAjaxRequest()
    {
        static $r = null;
        if (null === $r)
        {
            $r =  isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
        }
        return $r;
    }
    //END ORIGINAL REQUEST
}

if (!function_exists('sap_autoloader')){
    function sap_autoloader($class_name)
    {
        
        if (defined('INCLUDE_DIR')){
            $dir = INCLUDE_DIR . DIRECTORY_SEPARATOR;
        } else {
            $dir = SAP::root('.includes') . DIRECTORY_SEPARATOR;
        }
        
        if (strpos($class_name,'\\')){
            $f = SAP::core("{$class_name}.php");
            if (file_exists($f)) return require_once( $f);
            
            $x = explode('\\',strtolower($class_name));
            $x[0] = strtoupper($x[0]);
            $f = SAP::core(implode(DIRECTORY_SEPARATOR,$x) . ".php");            
            if (file_exists($f)) return require_once( $f);
            
            $f = $dir . implode(DIRECTORY_SEPARATOR,$x).'.php';
            if (file_exists($f)) require_once( $f);
            $f = $dir . $x[0]. DIRECTORY_SEPARATOR. 'autoload.php';
            if (file_exists($f)) require_once( $f);
            return;
        }
        
        $f = SAP::core("{$class_name}.php");
        if (file_exists($f)) return require_once( $f);
        
        if (strpos($class_name,'_')!==false){
            list($r,$b) = explode('_',$class_name,2);
            $r = strtoupper($r);
            $b = strtolower($b);
        }else{
            $r = $b = $class_name;
        }
        
        $f = $dir. $r . DIRECTORY_SEPARATOR ."autoload.php";
        if (file_exists($f)) require_once( $f);
        
        $f = $dir. $r . DIRECTORY_SEPARATOR ."{$b}.php";
        if (file_exists($f)) require_once( $f);
        
        //error_log("asking: $class_name");
    }
    
    if (version_compare(PHP_VERSION, '5.3', '<')){
        spl_autoload_register( 'sap_autoloader');
    } else {
        spl_autoload_register( 'sap_autoloader',true);
    }
}
}
namespace SAP{
use HTML;
use SAP;

class ViewNotFoundException extends \Exception{}
class html_response_envelop{
    function body(){
        $html = $this->html();
        return $html->body();
    }
    function container(){
        static $CONTAINER = null;
        if ((null ===$CONTAINER)){
            $CONTAINER = $this->body()->create('main')->class('container-fluid outer');
        }
        return $CONTAINER;
    }
    function content(){
        static $document = null;
        if ((null ===$document)){
            $document = $this->container()->create('div')->class('row-fluid');
        }
        return $document;
    }
    function html(){
        static $document = null;
        if ((null ===$document)){
            $document = HTML::build('HTML');
            $document->doctype('html');
        }
        return $document;
    }
    public function __toString(){
        return __CLASS__;
    }
    public function initialize(){
        $RES = SAP::getInstance('RESPONSE');
        $RES->setContentType('text/html');
        $RES->setStatusOk();
        $RES->getCacheControl()->mustRevalidate(true);
        SAP::propagateCall('HtmlInitialize',$this->html());
    }
    public function finalize(){
        SAP::propagateCall('Script',$this->html());
        SAP::propagateCall('Style',$this->html());
        SAP::propagateCall('HtmlFinalize',$this->html());
    }
    public function send(){
        if (SAP::getRedirect()){
            header("Status: "  .SAP::getStatus());
            header('Location: '.SAP::getRedirect());
            return ;
        }
        $this->finalize();
        
        $RES = SAP::getInstance('RESPONSE');
        $RES->setBody((string)$this->html());
        if ($RES->getOption('cache-type','none')!='none'){
            $HCC = $RES->getCacheControl();
            $ct = (int)$RES->getOption('cache-time',0);
            if ($ct == 0){
                $HCC->reset('')->mustRevalidate(true)->noCache(true);
            } else {
                if ($RES->getOption('cache-type','') == 'public'){
                    $HCC->setPublic(true);
                }
                $HCC->maxAge($ct);
                $RES->setHeader('X-html-cache-type',$RES->getOption('cache-type',''));
                $RES->setHeader('X-html-cache-create',time());
            }
        }
        $RES->send();
    }
}
class asset_file_composer{
    protected $path ;
    protected $mime ;
    protected $mtime = null;
    protected $content = null ;
    protected $leading_content = '';
    protected $file_list = [];
    public function __construct($dirpath = null){
        if ($dirpath){
            $this->addDirectory($dirpath);
        }
    }
    
    public function __get($name){
        $name =strtolower($name);
        if ($name == 'mtime'){
            return $this->mtime();
        }
        if ($name == 'mime'){
            return $this->mime();
        }
        if ($name == 'size'){
            return $this->size();
        }
    }
    public function mime(){
        return $this->mime;
    }
    public function mtime(){
        if ($this->mtime === null){
            $this->getFileList();
        }
        return $this->mtime;
    }
    public function size(){
        return strlen($this->getContents());
    }
    public function addFile($path){
        $path = realpath($path);
        if (file_exists($path)){
            $this->file_list[] = $path;
            $this->mtime = max($this->mtime,filemtime($path));
        }
    }
    public function addDirectory($path,$suffix=null){
        $path = realpath($path);
        if (is_dir($path)){
            $path = rtrim($path,DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
            $dirIter = dir($path);
            if ($dirIter){
                $a = array();
                $mtime= array($this->mtime);
                if ($suffix){
                    $suffix_len = 0 - strlen($suffix);
                }
                while (($file = $dirIter->read()) !== false)
                {
                    if ($file=='..') continue;
                    if ($file=='.') continue;
                    if ($suffix){
                        if (substr($file,$suffix_len) != $suffix ){
                            continue;
                        }
                    }
                    $f = $path . $file;
                    if (is_dir($f)){
                        continue;
                    }
                    $mtime[] = filemtime($f);
                    if (!in_array($f, $this->file_list)){
                        $a[] = $f;
                    }
                }
                $dirIter->close();
                sort($a);
                foreach($a as $f){
                    $this->file_list[] = $f;
                }
                $this->mtime = max($mtime);
            }
        }
    }
    public function setLeadingContent($str){
        $this->leading_content = $str;
    }
    protected function getFileList(){
        return $this->file_list;
    }
    public function getContents(){
        if ($this->content === null){
            $contents = $this->leading_content;
            $list = $this->getFileList();
            foreach ($list as $file){
                if (file_exists($file)){
                    $contents .= file_get_contents($file)."\n";
                }
            }
            $this->content = $contents;
        }
        return $this->content;
    }
}
class js_file_composer extends asset_file_composer{
    protected $mime = 'application/javascript';
}
class css_file_composer extends asset_file_composer{
    protected $mime = 'text/css';
}
class scss_css_file_composer extends asset_file_composer{
    protected $mime = 'text/css';
    public function __construct($dirpath = null){
        if (!class_exists("SCSSPHP")){
            throw new \Exception('SCSSPHP class to complie .scss file not found');
        }
        parent::__construct($dirpath);
    }
    public function getContents(){
        if ($this->content === null){
            $contents = $this->leading_content;
            $list = $this->getFileList();
            foreach ($list as $file){
                if (file_exists($file)){
                    $contents.= file_get_contents($file)."\n";
                }
            }
            
            $vcomp = new \SCSSPHP();
            //$vcomp->setFormatter("scss_formatter_compressed");
            $this->content = $vcomp->compile($contents);
        }
        return $this->content;
    }
}
class less_css_file_composer extends asset_file_composer{
    protected $mime = 'text/css';
    public function __construct($dirpath = null){
        if (!class_exists("LESSPHP")){
            throw new \Exception('LESSPHP class to complie .less file not found');
        }
        parent::__construct($dirpath);
    }
    public function getContents(){
        if ($this->content === null){
            $contents = $this->leading_content;
            $list = $this->getFileList();
            foreach ($list as $file){
                if (file_exists($file)){
                    $contents.= file_get_contents($file)."\n";
                }
            }
            $vcomp = new \LESSPHP();
            $this->content = $vcomp->compile($contents);
        }
        return $this->content;
    }
}
class event
{
    protected $event = null;
    protected $timestamp = null;
    protected $data = array();
    
    protected $result = null;
    protected $handled = null;
    protected $stopped = null;
    protected $cancelled = null;
    
    
    public function __construct($type){
        if (is_scalar($type)){
            $this->event = $type;
        } elseif (is_array($type)){
            $type = array_change_key_case($type,CASE_LOWER);
            if (isset($type['type'])){
                $this->event = $type['type'];
                unset($type['type']);
            } elseif (isset($type['eventtype'])){
                $this->event = $type['eventtype'];
                unset($type['eventtype']);
            } elseif (isset($type['event'])){
                $this->event = $type['event'];
                unset($type['event']);
            }
            $this->data = $type;
        }
        $this->event = strtoupper($this->event);
        $this->timestamp = time(); 
    }

    public function __call($name, $arguments){
        $name = strtolower($name);
        if (substr($name,0,2)=='is'){
            switch(substr($name,2)){
            case 'propagationstopped':
            case 'stopped': return $this->stopped();
            case 'handled': return $this->handled();
            case 'cancelled': return $this->cancelled();
            case 'unhandled': return $this->handled ===null;
            }
        }
        switch($name){
            case 'stoppropagation':
            return $this->stop();
            case 'setresult':
                return call_user_func_array(array($this,'result'),$arguments);
            case 'getresult':
            case 'result':
                return $this->result;
            case 'timestamp': return $this->timestamp;
            case 'event': return $this->event;
        }
    }
    public function __get($name){
        $name = strtolower($name);
        if (array_key_exists($name,$this->data))
            return $this->data[$name];
        if (method_exists($this,$name))
            return $this->$name();
        
        if (substr($name,0,2)=='is'){
            return $this->__call($name,array());
        }
        return '';
    }
    public function __set($name, $value){
        $name = strtolower($name);
        if ((null ===$value))
            unset($this->data[$name]);
        else
            $this->data[$name] = $value;
    }
    public function __unset($name){
        $name = strtolower($name);
        unset($this->data[$name]);
    }
    public function __isset($name){
        $name = strtolower($name);
        return isset($this->data[$name]);
    }

    public function result(){
        if (func_num_args()){
            $this->result = func_get_arg(0);
            $this->handled = true;
        } else {
            return $this->result;
        }
    }
    public function received(){
        return $this->received;
    }
    public function setHandled(){
        $this->handled = true;
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
    public function handle(){
        if (func_num_args()){
            $this->result = func_get_arg(0);
        }
        $this->handled = true;
    }
    public function handled(){
        return $this->handled || $this->stopped;
    }
    
    public function which(){
        return strtoupper($this->event);
    }
    
    public function setData($data ){
        $this->data = $data;
    }
    public function getData( ){
        return $this->data;
    }
    public function toArray( ){
        return $this->data;
    }
} 
}
