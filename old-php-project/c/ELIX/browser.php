<?php
/**
 * @author Edwards
 * @copyright 2015
 * 
 * this class provides tools for "sniffing" (it is unreliable) the users browser and useragent
 */
namespace ELIX;
 
class BROWSER
{
    
    
    static private $UA = null;
    
    /**
     * SITE_system::isMSIE()
     * Is UserAgent Internet Explorer
     * @param integer $version
     * @return
     */
    static function setUA($ua){
        if(empty($ua)){
            self::$UA = isset($_SERVER['HTTP_USER_AGENT'])? $_SERVER['HTTP_USER_AGENT']:'';
        }else{
            self::$UA = $ua;
        }
    }
    static function getUA(){
        if(self::$UA === null) self::setUA('');
        return self::$UA;
    }
    static function getUALowerCase(){
        if(self::$UA === null) self::setUA('');
        return strtolower(self::$UA);
    }
    static function isMSIE($version=0)
    {        
        if(preg_match('/MSIE/i',self::getUA()) && !preg_match('/Opera/i',self::getUA()))
        {
            //This is Internet Explorer. (Insert joke here)';       
            return true;
        }
        return false;
    }
    static function clientIP() {
        $ip = $_SERVER['REMOTE_ADDR'];
     
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } elseif (!empty($_SERVER['HTTP_X_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_X_CLIENT_IP'];
        }
     
        return $ip;
    }
    static function matchVersion($item='chrome',$version=0,$operator='>='){
        $t = explode(' ',self::getUALowerCase());
        $item = strtolower($item);
        $l = strlen($item);
        foreach($t as $v){
            if($item==substr($v,0,$l)){
                $vn =substr($v,$l+1); //add extra one for the SLASH
                return version_compare($vn,$version,$operator);
            }
        }
        return false;
    }
    static function match($item=''){
        if(preg_match("/$item/i",self::getUA()))
            return true;
        else
            return false;
    }
    /* 
    static function isCHROME($version=0){
        return self::isWebkit($version);
    }
    */
    static function isOpera($version=0)
    {
        //This is either Chrome or Safari
        if(preg_match('/Opera/i',self::getUA()))
            return true;
        else
            return false;
    }  
    static function isNetscape($version=0)
    {
        //This is Netscape, and you really need to upgrade
        if(preg_match('/Netscape/i',self::getUA()))
            return true;
        else
            return false;
    } 
    static function isMozilla($version=0)
    {
        //This is either Firefox
        if(preg_match('/mozilla/i',self::getUA()) && !preg_match('/compatible/', self::getUA()))
            return true;
        else
            return false;
    }
    static function isWebkit($version=0)
    {
        //This is either Chrome or Safari
        if(preg_match('/webkit/i',self::getUA()))
            return true;
        else
            return false;
    }
    static function isRobot($filename)
    {
        $s = self::getUALowerCase();
        if(empty($s)) return false;
        
        if(strpos( $s,'bot/')) return true;
        //if(strpos( $s,'googlebot')) return true;
        if(strpos( $s,'mediapartners-google')) return true;
        //if(strpos( $s,'msnbot')) return true;
        //if(strpos( $s,'bingbot')) return true;
        if(strpos( $s,'yandexbot')) return true;
        //if(strpos( $s,'baiduspider')) return true;
        //if(strpos( $s,'mj12bot')) return true;
        //if(strpos( $s,'exabot')) return true;
        if(strpos( $s,'crawler@')) return true;
        if(strpos( $s,'spider/')) return true;
        
        return false;
    }
    
     
    static function isMobile(){
        static $is = null;
        if($is === null ) $is = self::_isMobile();
        return $is;
    }
    static function isBlackberry(){
        $s = self::getUALowerCase();
        return strpos($s,'blackberry') !== false;
    }
	static function isIphone(){
        $s = self::getUALowerCase();
        if (strpos($s,'iphone')) {
            if (strpos($s,'ipad'))
                return false; //ipod says its an iphone but it's not
            else 
                return true;
        }
        return false;
    }
 
    static function _isMobile()
    {
        $http_useragent = self::getUALowerCase();
        if(empty($http_useragent)) return false;
        if(self::isBlackberry()) return true;
        if(self::isIphone()) return true;
        
        
        if (preg_match('/(up.browser|up.link|mmp|symbian|smartphone|midp|wap|phone|android)/i', strtolower($_SERVER['HTTP_USER_AGENT']))) {
            return true;
        }
        if(isset($_SERVER['HTTP_X_WAP_PROFILE'])) return true;
        if(isset($_SERVER['HTTP_PROFILE'])) return true;
        $mobile_ua = substr($http_useragent, 0, 4);
        $mobile_agents = array(
            'w3c ','acs-','alav','alca','amoi','audi','avan','benq','bird','blac',
            'blaz','brew','cell','cldc','cmd-','dang','doco','eric','hipt','inno',
            'ipaq','java','jigs','kddi','keji','leno','lg-c','lg-d','lg-g','lge-',
            'maui','maxo','midp','mits','mmef','mobi','mot-','moto','mwbp','nec-',
            'newt','noki','palm','pana','pant','phil','play','port','prox',
            'qwap','sage','sams','sany','sch-','sec-','send','seri','sgh-','shar',
            'sie-','siem','smal','smar','sony','sph-','symb','t-mo','teli','tim-',
            'tosh','tsm-','upg1','upsi','vk-v','voda','wap-','wapa','wapi','wapp',
            'wapr','webc','winw','winw','xda ','xda-');
         
        if (in_array($mobile_ua,$mobile_agents)) {
            return true;
        }
        if(strpos($http_useragent,' ppc;')>0) { //detect opera
            return true;
        }
        if (strpos($http_useragent,'operamini') > 0) {
            return true;
        }
        if (strpos($http_useragent,'opera mini') > 0) {
            return true;
        }
        if (strpos($http_useragent,'iemobile')>0) {
            return true;
        }
        if((strpos($http_useragent, 'windows phone') !== false)||(strpos($http_useragent, 'windows ce') !== false)){ 
            // But WP7 is also Windows, with a slightly different characteristic
            return true;
        }
        $a  =isset($_SERVER["HTTP_ACCEPT"])?strtolower($_SERVER["HTTP_ACCEPT"]):'';
        if ((strpos($a,'application/vnd.wap.xhtml+xml') )){
            return true;
        }
       	$regex_match="/(nokia|iphone|android|motorola|^mot\-|softbank|foma|docomo|kddi|up\.browser|up\.link|";
    	$regex_match.="htc|dopod|blazer|netfront|helio|hosin|huawei|novarra|CoolPad|webos|techfaith|palmsource|";
    	$regex_match.="blackberry|alcatel|amoi|ktouch|nexian|samsung|^sam\-|s[cg]h|^lge|ericsson|philips|sagem|wellcom|bunjalloo|maui|";	
    	$regex_match.="symbian|smartphone|midp|wap|phone|windows ce|iemobile|^spice|^bird|^zte\-|longcos|pantech|gionee|^sie\-|portalmmm|";
    	$regex_match.="jig\s browser|hiptop|^ucweb|^benq|haier|^lct|opera\s*mobi|opera\*mini|320x320|240x320|176x220";
    	$regex_match.=")/i";		
    	return preg_match($regex_match, $http_useragent);
    }
}

?>