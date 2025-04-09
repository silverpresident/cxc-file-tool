<?php
/**
 * @author Shane 
 * @copyright 2010
 * http://www.w3.org/Protocols/rfc2616/rfc2616-sec14.html#sec14.9
 * this class is intended primarily to handle RESPONSE directives
 * 
 * cache-response-directive =
           "public"                               ; Section 14.9.1
         | "private" [ "=" <"> 1#field-name <"> ] ; Section 14.9.1
         | "no-cache" [ "=" <"> 1#field-name <"> ]; Section 14.9.1
         | "no-store"                             ; Section 14.9.2
         | "no-transform"                         ; Section 14.9.5
         | "must-revalidate"                      ; Section 14.9.4
         | "proxy-revalidate"                     ; Section 14.9.4
         | "max-age" "=" delta-seconds            ; Section 14.9.3
         | "s-maxage" "=" delta-seconds           ; Section 14.9.3
         | cache-extension                        ; Section 14.9.6
 * 
 * 
 * 
 * age is an alternate for Expires
 * 
 * private
Indicates that all or part of the response message is intended for a single user and MUST NOT be cached by a shared cache. This allows an origin server to state that the specified parts of the
response are intended for only one user and are not a valid response for requests by other users. A private (non-shared) cache MAY cache the response.
Note: This usage of the word private only controls where the response may be cached, and cannot ensure the privacy of the message content.
 * 
 */
class ELI_cache
{
    var $version = '2.5'; //for site wide refresh
    private $e = array();
    private $c = array();
    private $last_modified = '';
    private $modified_since = 0;
    private $expires = '';
    private $date = '';
    public function __construct() {
        $this->SetExpires();
    }
    private function version(){
        return __CLASS__. $this->version;
    }
    /**
     * jolCache::SetCacheControl()
     * 
     * @param mixed $param
     *      max-age=0
     *      s-maxage=0
     *      post-check=0 *IE
     *      pre-check=0 *IE
     *      private
     *      public
     *      no-transform
     *      no-store
     *      no-cache 
     *      must-revalidate
     *      proxy-revalidate 
     *      
     *   
     * @param mixed $value
     * @return void
     */
    
    function SetCacheControl($param,$value=true)
    {
        $param = strtolower($param);
        if(in_array($param,array('public','private')))
        {
            unset($this->c['public'],$this->c['private']);
        }
            
        if($value || is_numeric($value))
            $this->c[$param] = $value;
        else
            unset($this->c[$param]);
        return $this;
    }
    function UnsetCacheControl($param)
    {
        unset($this->c[$param]);
    }
    private function _GetCacheControl()
    {
        $r=array();
        foreach($this->c as $k=>$v)
        {
            if(in_array($k,array('max-age','s-maxage','post-check','pre-check')))
            {
                $v = (int)$v;
                $r[] = "$k=$v";
            }else
                $r[] = $k;
        }
        if(count($r))
            return implode(', ',$r);
        else
            return '';
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
    function Etag()
    {
        if(!count($this->e)) return '';
        return md5($this->version() . $this->last_modified. serialize($this->e));
    }
    function SetModified($date='now')
    {
        if((null ===$date))
        {
            $this->last_modified = '';
            $this->modified_since = 0;
            return;
        }
        elseif(!($date instanceof DateTime))
        {
            if(is_numeric($date))
            {
                $date = new DateTime("@$date");
            }else
            {
                $date = new DateTime($date);
            }
            if($date===false)
                $date= new DateTime();
        }
        $this->last_modified = $date->format('D, d M Y H:i:s'); //Sat, 29 Oct 1994 19:43:31 GMT
        $this->modified_since = $date->format('U');
    }
    function SetExpires($date='now')
    {
        if((null ===$date))
        {
            $this->expires = '';
            return;
        }
        elseif(!($date instanceof DateTime))
        {
            if(is_numeric($date))
            {
                $date = new DateTime("@$date");
            }else
            {
                $date = new DateTime($date);
            }
            if($date===false)
                $date= new DateTime();
        }
        $this->expires = $date->format('D, d M Y H:i:s');
    }
    function SetDate($date='now')
    {
        if((null ===$date))
        {
            $this->date = '';
            return;
        }
        elseif(!($date instanceof DateTime))
        {
            if(is_numeric($date))
            {
                $date = new DateTime("@$date");
            }else
            {
                $date = new DateTime($date);
            }
            if($date===false)
                $date= new DateTime();
        }
        $this->date = $date->format('D, d M Y H:i:s');
    }
    function SetHeaders()
    {
        if (!headers_sent()) {
            $etag = $this->_GetCacheControl();
            if (!empty($etag))                  Header("Cache-control: {$etag}");
            if (!empty($this->date))            Header("Date: {$this->date} GMT");
            $etag = $this->Etag();
            if (!empty($etag))                  Header("ETag: \"{$etag}\"");
            if (!empty($this->expires))         Header("Expires: {$this->expires} GMT");
            if (!empty($this->last_modified))   Header("Last-Modified: $this->last_modified GMT");
        }
    }
    function checkAll()
    {
        $this->checkETag();
        $this->checkIfModified();
    }
    function checkIfModified()
    {
        if ($this->_MatchModified())
        {       
            $t = isset($_SERVER['HTTP_IF_NONE_MATCH'])?$this->_MatchETag():true;
            if($t)
            {
                /*$etag = $this->_GetCacheControl();
                if (!empty($etag))                  Header("Cache-control: {$etag}");*/
                $this->SetHeaders();
                if (!headers_sent()){
                    header("HTTP/1.1 304 Not Modified Date Checked");
                    die();
                }
            }
        }
    }
    
    function checkETag()
    {
        if ($this->_MatchETag())
        {
            /*$etag = $this->_GetCacheControl();
            if (!empty($etag))                  Header("Cache-control: {$etag}");*/
            $this->SetHeaders();
            if (!headers_sent()){
                header("HTTP/1.1 304 Not Modified Etag Same");
                die();
            }
        }
        unset($_SERVER['HTTP_IF_MODIFIED_SINCE']);
    }
    private function _MatchETag()
    {
        if(isset($_SERVER['HTTP_IF_NONE_MATCH']))
        {
            $mtag = trim(trim($_SERVER['HTTP_IF_NONE_MATCH']),'"');
            return $mtag == $this->Etag();
        }
        return false;
    }
    private  function _MatchModified()
    {
        if(isset($_SERVER['HTTP_IF_MODIFIED_SINCE']))
        {
            return ($this->modified_since >= @strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']) );
        }
        return false;
    }
}
?>