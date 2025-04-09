<?php
/**
 * @author Edwards
 * @copyright 2010
 * @version 20130829.1
 * 
 * www.vinaysahni.com/best-practices-for-a-pragmatic-restful-api
 */
if(!defined('CONTENT_TYPE_PLAIN')) define('CONTENT_TYPE_PLAIN','text/plain');
if(!defined('CONTENT_TYPE_HTML')) define('CONTENT_TYPE_HTML','text/html');
if(!defined('CONTENT_TYPE_JSON')) define('CONTENT_TYPE_JSON','application/json');
if(!defined('CONTENT_TYPE_PDF')) define('CONTENT_TYPE_PDF','application/pdf');
if(!defined('CONTENT_TYPE_DOWNLOAD')) define('CONTENT_TYPE_DOWNLOAD','application/x-download');
if(!defined('CONTENT_TYPE_XML')) define('CONTENT_TYPE_XML','application/xml');
if(!defined('CONTENT_TYPE_SCRIPT')) define('CONTENT_TYPE_SCRIPT','application/javascript');

if(!defined('HTTP_NO_CONTENT')) define('HTTP_NO_CONTENT',204);


class ELI_response
{
    public function __toString() {
        return get_class($this);
    }
    protected $_meta = null;
    protected $_cacheable = null;
    protected $_cached =null;
    protected $ccHeader =null;
    protected $status =null;
    protected $statusMessage ='';
    protected $contentType ='';
    protected $contentDisposition ='';
    protected $contentCharset = '';
    protected $sendType ='';
    protected $body =null;
    protected $filebody =null;
    protected $etag =null;
    protected $server =null;
    protected $filepath =null;
    protected $filename =null;
    protected $headers = array();
    protected $preventDefault = false;
    protected $options = array();
    protected $debug = array();
    
    protected $_prepared_body = null;
    protected $_override_type = null;
    
    function clear()
    {
        $this->body = null;
        $this->etag = null;
        $this->server = null;
        $this->status = null;
        $this->_cached = null;
        $this->_prepared_body = null;
        $this->_override_type = null;
        $this->statusMessage = '';
        $this->contentType = '';
        $this->contentCharset = '';
        $this->contentDisposition = '';
        $this->sendType = '';
        $this->headers = array();
        $this->options = array();
        return $this;
    }
    function reset(){
        $this->clear();
        return $this;
    }
    function getOption($property,$default=null){
        $p = strtolower($property);
        if(isset($this->options[$p])){
            return $this->options[$p];
        }
        return $default;
    }
    function setOption($property,$content='')
    {
        $p = strtolower($property);
        if($content ===null){
            unset($this->options[$p]);
        }else{
            $this->options[$p] = $content;
        }
        return $this;
    }
    public function __get($name) {
        $name = strtolower($name);
        if($name =='cached' || $name =='is_cached' || $name =='iscached'){
            return $this->_cached;
        }
        if($name =='cacheable' || $name =='is_cacheable' || $name =='iscacheable'){
            return $this->_cacheable;
        }
    }
    public function __set($name, $value) {
        $name = strtolower($name);
        if($name =='cached' || $name =='is_cached' || $name =='iscached'){
            $this->_cached = $value;
        }
        if($name =='cacheable' || $name =='is_cacheable' || $name =='iscacheable'){
            $this->_cacheable = $value;
        }
    }

    function preventDefault($set=null){
        if(func_num_args()){
            $this->preventDefault = $set;
            return $this;
        }
        return $this->preventDefault;
    }
    function setFilename($filename='')
    {
        $this->filename = $filename;
        return $this;
    }
    function setFile($filepath,$filename='',$type=null)
    {
        $this->debug[] = "setFile($filepath)";
        $this->filepath = $filepath;
        $this->_prepared_body = null;
        if(func_num_args()>1){
            $this->filename = $filename;
        }elseif(!$this->filename && $filepath){
            $this->filename = basename($filepath);
        }
        if(func_num_args()>2){
            $this->contentType = $type;
        }
        return $this;
    }
    function setDownload($filepath,$filename='',$type=null)
    {
        $this->_prepared_body = null;
        $this->filepath = $filepath;
        if($filepath){
            if(!$this->contentDisposition){
                $this->contentDisposition = 'attachment';
            }
        }
        if(func_num_args()>1){
            $this->filename = $filename;
        }elseif(!$this->filename && $filepath){
            $this->filename = basename($filepath);
        }
        if(func_num_args()>2){
            $this->contentType = $type;
        }
        return $this;
    }
    function setDownloadData($filedata,$filename='',$type=null)
    {
        $this->_prepared_body = null;
        $this->filebody = $filedata;
        if(!$this->contentDisposition){
            $this->contentDisposition = 'attachment';
        }
        if(func_num_args()>1){
            $this->filename = $filename;
        }
        if(func_num_args()>2){
            $this->contentType = $type;
        }
        return $this;
    }
    function hasBody(){
        if ($this->body !== null){
            return true;
        }
        if($this->filebody !== null){
            return true;
        }
        if($this->filepath){
            return true;
        }
        return false;
    }
    function setBody($body)
    {
        $this->_prepared_body = null;
        $this->body = $body;
        return $this;
    }
    protected function _mk_body_array(){
        $this->_prepared_body = null;
        if(!is_array($this->body)){
            if(empty($this->body))
                $this->body = array();
            else
                $this->body = array($this->body);
        }
    }
    function append($body){
        $this->_prepared_body = null;
        if(is_array($this->body)){
            $this->body[] = $body;
        }elseif($this->body === null){
            $this->body = $body;
        }else{
            $this->body .= $body;
        }
        return $this;
    }
    function addField($field,$content){
        $this->_mk_body_array();
        if($field){
            if(!isset($this->body[$field]))$this->body[$field] = $content;
        }else
            $this->body[$field] = $content;
        return $this;
    }
    function unsetField($field){
        $this->_prepared_body = null;
        unset($this->body[$field]);
        return $this;
    }
    function setField($field,$content=null){
        $this->_mk_body_array();
        if(func_num_args() ==1){
            if(is_array($field)){
                foreach($field as $k=>$v)$this->body[$k] = $v; 
            }else{
                $this->body[$field] = '';
            }
        }else
            $this->body[$field] = $content;
        return $this;
    }
    function setSubField($field,$subIndex,$content=null){
        $this->_mk_body_array();
        if(!empty($this->body[$field]) && !is_array($this->body[$field])){
            $this->body[$field][] = $this->body[$field];
        }
        if($subIndex){
            if(is_Array($subIndex)){
                foreach($subIndex as $k=>$v)$this->body[$field][$k] = $v;
            }else{
                $this->body[$field][$subIndex] = $content;
            }
        }else
            $this->body[$field][] = $content;
        return $this;
    }
    function getField($field,$subIndex=null){
        if(!is_array($this->body)) return null;
        if(func_num_args()>1){
            if(isset($this->body[$field][$subIndex])){
                return $this->body[$field][$subIndex];
            }elseif((null ===$subIndex) && isset($this->body[$field])){
                return $this->body[$field];
            }else{
                return null;
            }
        }
        if(isset($this->body[$field])) return $this->body[$field];
        return null;
    }
    
    function getBody(){
        
        if($this->filepath){
            if((NULL === $this->filebody)){
                if(file_exists($this->filepath))
                    $this->filebody = file_get_contents($this->filepath);
                else
                    $this->filebody = '';
            }
            if(!$this->filebody){
                $this->debug[] = "Read file is empty in getBody filepath:($this->filepath)";
            }
            return $this->filebody;
        }
        if($this->filebody){
            return $this->filebody;
        }
        if(!$this->body){
            $this->debug[] = "Body is empty in getBody (body)";
            $this->debug[] = "filepath:($this->filepath)";
        }
        return $this->body;
    }
    function getETag(){
        if(empty($this->etag)){
            $a = func_get_args();
            $a[] = $this->contentType;
            if($this->filebody){
                $a[] = md5($this->filebody);
            }elseif(file_exists($this->filepath)){
                $a[] = md5_file($this->filepath);
            }else{
                $a[] = serialize($this->body);
            }
            $this->etag = md5(implode('-',$a));
        }
        return $this->etag;
    }
    function getMeta(){
        //DEPRECATE this
        if($this->_meta === null){
            $this->_meta = new ELI_response_meta;
        }
        return $this->_meta; 
    }
    function setCanonical($url){
        $this->setHeader('Link',"<{$url}>; rel=\"canonical\"",false);
        return $this;
    }
    function CSP($value=null){
        $p = 'Content-Security-Policy';
        if(func_num_args()==0){
            return $this->getHeader($p);
        }
        $this->setHeader($p,$value,true);
    }
    function CSP_Report($value=null){
        $p = 'Content-Security-Policy-Report-Only';
        if(func_num_args()==0){
            return $this->getHeader($p);
        }
        $this->setHeader($p,$value,true);
    }
    function getHeader($property){
        $p = strtolower($property);
        if($p=='server'){
            return $this->server;
        }
        if($p=='content-type'){
            return $this->contentType;
        }
        if($p=='cache-control'){
            return $this->getCacheControl();
        }
        $r = array();
        foreach($this->headers as $k =>$h)
        {
            $x = explode(':',$h->header,2);
            if(strtolower($x[0]) ==$p)
            {
                $r[] = ltrim($x[1]);
            }
        }
        if($c = count($r)){
            if($c==1) return $r[0];
            return $r;
        }
        return null;
    }
    function setHeader($property,$content='',$replace=true)
    {
        if(func_num_args()==1){
            $x = explode(':',$property,2);
            $property = trim($x[0]);
            if(isset($x[1])){
                $content = ltrim($x[1]);
            }
        }
        $p = strtolower($property);
        if($p=='server'){
            $this->server = $content;
            return $this;
        }
        if($p=='content-type'){
            $this->contentType = $content;
            return $this;
        }
        if($p=='cache-control'){
            $this->getCacheControl()->reset($content);
            return $this;
        }
        if($content===null){
            $replace = true;
            $content ='';
        }
        if($replace)
        {
            foreach($this->headers as $k =>$h)
            {
                $x = explode(':',$h->header,2);
                if(strtolower($x[0]) ==$p)
                {
                    unset($this->headers[$k]);
                }
            }            
        }
        if($content !== ''){
             $this->headers[] = new ELI_response_header("$property: $content", $replace);
        }
        return $this;
    }
    function setStatus($code,$message='')
    {
        $this->status = $code;
        if(!$message && $code>=200 && $code<300) $message = 'OK';
        $this->statusMessage =$message;
        return $this;
    }
    function setContentType($type = CONTENT_TYPE_PLAIN){
        if($i = strpos($type,';')){
            $r = trim(substr($type,$i+1));
            $type = substr($type,$i);
            if(strtolower(substr($r,0,8))=='charset='){
                $this->setCharset(substr($r,8));
            }
        }
        $this->contentType = $type;
        return $this;
    }
    function setCharset($type = 'UTF-8'){
        $this->contentCharset = $type;
        return $this;
    }
    function setContentDisposition($type){
        $type = strtolower($type);
        if(in_array($type,array('inline','attachment'))) $this->contentDisposition = $type;
        return $this;
    }
    function setSendType($type = CONTENT_TYPE_PLAIN){
        $this->sendType = $type;
        return $this;
    }
    function getContentDisposition(){
        return  $this->contentDisposition;
    }
    function getCharset(){
        return $this->contentCharset;
    }
    function getSendType(){
        $type = strtolower($this->sendType);
        switch($type)
    	{
    	    case 'text':
                return 'TEXT'; //uses implode cast with new line
            case 'string':
                return 'STRING'; //uses string cast
    		    break;
            case 'echo':
                return 'ECHO'; //uses echo without cast
    		    break;
            case 'json':
            case 'text/json':
            case 'application/json':
                return 'JSON'; //uses json encode
    		    break;
            case 'xml':
            case 'text/xml':
            case 'application/xml':
                return 'XML'; //uses xml encode
    		    break;
            case 'text/plain':
            case 'text/html':
            case 'text/plain':
            case 'text/javascript': 
                return 'STRING';
    			break;
    		
            case 'application/x-download':
            case 'application/javascript': 
                return 'STRING';
    			break;
    	}
        $type = strtolower($this->contentType);
        switch($type)
    	{
    	    case 'text/json':
            case 'application/json':
                return 'JSON'; //uses json encode
    		    break;
            case 'text/xml':
            case 'application/xml':
                return 'XML'; //uses xml encode
    		    break;
            case 'text/plain':
            case 'text/html':
            case 'text/javascript': 
            case 'application/x-download':
            case 'application/javascript': 
                'STRING';
    			break;
    	}
        switch($this->getContentType())
    	{
    	    case CONTENT_TYPE_JSON:
                return 'JSON'; //uses json encode
    		    break;
            case CONTENT_TYPE_HTML:
                'STRING';
    			break;
    	}
        
        return 'ANY';
    }
    
    function getContentLength(){
        $body = $this->_getPreparedBody();
        if(is_scalar($body)){
            return strlen($body);
        }
        return null;
    }
    function getContentType(){
        if(empty($this->contentType)){
            if(is_array($this->body)){
                return CONTENT_TYPE_JSON;
            }elseif(is_object($this->body)){
                if($this->body instanceof HTML_element)
                    return CONTENT_TYPE_HTML;
                else
                    return CONTENT_TYPE_JSON;
            }else
                return CONTENT_TYPE_PLAIN;
        }
        return $this->contentType;
    }
    function getCacheControl(){
        if((null ===$this->ccHeader)){
            include_once(__DIR__ .'/cachecontrol.php');
            $this->ccHeader = new ELI_cachecontrol();
        }
        return $this->ccHeader;
    }
    function getServer(){
        if((null ===$this->server)){
            return 'ELI_REPONSE/1.1 (ELI/2)';
        }
        return $this->server;
    }
    function getStatusHeader(){
        if((null ===$this->status)){
            $body = $this->getBody();
            if((null ===$body)){
                return '500 Server exited correctly but without a response';
            }else{
                return '200 OK';
            }
        }else{
            return trim($this->status . ' ' . $this->statusMessage);
        }
    }
    function hasStatus(){
        return $this->status != null;
    }
    function getStatus(){
        if((null ===$this->status)){
            $body = $this->getBody();
            if((null ===$body)){
                return 500;
            }else{
                return 200;
            }
        }else{
            return $this->status;
        }
    }
    protected function _getSendFunction(){
        $body = $this->getBody();
        $f = '';
        if($body === null) $f = 'NULL';
        elseif(is_array($body)) $f = 'ARRAY';
        elseif(is_scalar($body)) $f = 'SCALAR';
        elseif(is_object($body)) $f = 'OBJECT';
        elseif(is_bool($body)) $f = 'BOOL';
        
        $t = $this->getSendType();
        
        return "{$f}_{$t}";
    }
    protected function _getContentTypeHeader(){
        $contentType =$this->getContentType();
        $charset = strtolower($this->getCharset());
        if(!$contentType) return '';
        if($charset) $contentType .= ';charset=' . $charset;
        return $contentType;
    }
    public function getHeaderList(){
        $a = array();
        $status_header = $this->getStatusHeader();
        //used by non-CGI must be first
        $a[] = new ELI_response_header('HTTP/1.1 ' . $status_header);
        //used by CGI
        $a[] = new ELI_response_header('Status: ' . $status_header);
        
        // set the content type
        $contentType =$this->_getContentTypeHeader();
        //if($this->download) $a[] = new ELI_response_header('Content-type: application/x-download'); 
        if($contentType) $a[] = new ELI_response_header('Content-type: ' . $contentType);
        if($this->_override_type){
            $a[] = new ELI_response_header('Content-type: ' . $this->_override_type,true);
        }
        if($this->contentDisposition || $this->filename){
            if($this->contentDisposition){
                $cd = $this->contentDisposition;
            }else{
                $cd = 'inline';
            }
            if($this->filename){
                $cd .= '; filename="'.$this->filename.'"';
                if(urlencode($this->filename) != $this->filename){
                    $cd .= "; filename*=UTF-8''" . rawurlencode($this->filename);
                }
            }
            $a[] = new ELI_response_header('Content-Disposition: '.$cd);
        }
        $temp =$this->getServer();
        if($temp) $a[] = new ELI_response_header('Server: ' . $temp);
        
        foreach($this->headers as $h){
            $a[] = $h;
        }
        if($this->_cacheable){
            $etag = $this->getETag();
            if (!empty($etag)) $a[] = new ELI_response_header("Etag: \"$etag\"");
        }
        if($this->ccHeader !== null){
            $temp = $this->getCacheControl()->toString();
            if($temp)$a[] = new ELI_response_header("Cache-Control: $temp");/**/
        }
        if($cd = $this->getContentLength()){
            $a[] = new ELI_response_header('Content-Length: '.$cd);
        }
        
        return $a;
    }
    public function sendHeaders(){
        if(headers_sent()){
            return false;
        }
        foreach($this->getHeaderList() as $h){
            header($h->header,$h->replace,$h->status);
        }
        return true;
    }
    
    private function _getPreparedBody(){
        //returns the STRING respenation that should be sent
        if($this->_prepared_body === null){
            $status = $this->getStatus();
            $pbody ='';
            $body = $this->getBody();
            $sendfx = $this->_getSendFunction();
            $contentType =$this->getContentType();
            $this->debug[] = "Send function: $sendfx";
            
            switch($sendfx){
            case 'SCALAR_STRING': //UNTESTED
            case 'SCALAR_TEXT': //UNTESTED
            case 'SCALAR_ANY': //UNTESTED
            case 'SCALAR_ECHO': //UNTESTED
            case 'OBJECT_STRING': //UNTESTED
            case 'OBJECT_TEXT': //UNTESTED
            case 'OBJECT_ANY': //UNTESTED
            case 'OBJECT_ECHO':
            case 'BOOL_ECHO': //UNTESTED
            case 'BOOL_ANY': //UNTESTED
            case 'SCALAR_XML': //UNTESTED
                $pbody = (string)$body;
            break;
            case 'ARRAY_ANY': //UNTESTED
            case 'ARRAY_STRING': 
            case 'ARRAY_ECHO': 
            case 'ARRAY_TEXT':
                if($sendfx == 'ARRAY_TEXT') {
                    $fx = function($v, $k) use (&$pbody) {
                        $pbody .= (string)$v;
                        $pbody .= "\n";
                    };
                }else{
                    $fx = function($v, $k)  use (&$pbody){
                        $pbody .= (string)$v;
                    };
                }
                array_walk_recursive($body, $fx);
            break;
            case 'SCALAR_JSON':
                if(substr(trim($body),0,1)=='{'){
                    $pbody = $body;
                    break;
                }
            case 'ARRAY_JSON': 
            case 'OBJECT_JSON': //UNTESTED
            case 'BOOL_JSON': //UNTESTED
                if($this->filepath || $this->filebody)
                    $pbody = $body;
                else
                    $pbody = self::_prepareAsJson($body);
            break;
            case 'ARRAY_XML': //UNTESTED
            case 'OBJECT_XML': //UNTESTED
            case 'BOOL_XML': //UNTESTED
                if($this->filepath || $this->filebody)
                    $pbody = $body;
                else
                    $pbody = self::_prepareAsXml($body);
            break;
            case 'BOOL_STRING':
            case 'BOOL_TEXT':
                $pbody = var_export($body,true);
            break;
            default:
                if($body !== null)
                {
                    switch($contentType){
                    case CONTENT_TYPE_JSON:
                    case 'text/json': 
                        if($this->filepath || $this->filebody)
                            $pbody = $body;
                        else
                            $pbody = self::_prepareAsJson($body);
                    break;
                    case CONTENT_TYPE_XML:
                    case 'text/xml':
                        if($this->filepath || $this->filebody){
                            $pbody = $body;
                        }
                        elseif($this->getSendType()=='STRING'){
                            if(is_array($body)){
                                foreach($body as $v){
                                    $pbody .= (string)$v;
                                }
                            }else{
                                $pbody = (string)$body;
                            }
                        }else{
                            $pbody = self::_prepareAsXml($body);
                        }   
                    break;
                    case CONTENT_TYPE_SCRIPT:
                    case 'text/javascript':
                    case CONTENT_TYPE_PLAIN:
                    default:
                        if(is_scalar($body)){
                            $pbody = $body;
                        }elseif(is_array($body)){
                            if($this->getSendType()=='STRING'){
                                foreach($body as $v){
                                    $pbody .= (string)$v;
                                }
                            }else{
                                if(!function_exists('implode_r')){
                                    function implode_r ($pieces){ 
                                         $out = ""; 
                                         foreach ($pieces as $piece) {
                                                $out .= (is_array($piece)) ? implode_r($piece) : strval($piece);
                                        }
                                        return $out; 
                                    }
                                }
                                $pbody =  implode_r($body);
                            }
                        }else{
                            $pbody = (string)$body;
                        }
                    }
                }elseif($status >=300){// we need to create the body if none is passed
                	// create some body messages
                	$message = '';
                	// this is purely optional, but makes the pages a little nicer to read
                	// for your users.  Since you won't likely send a lot of different status codes,
                	// this also shouldn't be too ponderous to maintain
                	switch($status)
                	{
                		case 401: $message = 'You must be authorized to view this page.';
                			break;
                		case 404: $message = 'The requested URL ' . $_SERVER['REQUEST_URI'] . ' was not found.';
                			break;
                		case 405: $message = 'The requested application is not implemented.';
                			break;
                        case 500: $message = 'The server encountered an error processing your request.';
                			break;
                		case 501: $message = 'The requested method is not implemented.';
                			break;
                        case 503: $message = 'The service is temporarily unable to service your request due to maintenance downtime.';
                			break;
                	}
                    if((null ===$this->status)){
                        $message = 'The server had no response to your request.';
                    }
                    $status_header = $this->getStatusHeader();
                	// servers don't always have a signature turned on (this is an apache directive "ServerSignature On")
                	$signature = ($_SERVER['SERVER_SIGNATURE'] == '') ? $_SERVER['SERVER_SOFTWARE'] . ' Server at ' . $_SERVER['SERVER_NAME'] . ' Port ' . $_SERVER['SERVER_PORT'] : $_SERVER['SERVER_SIGNATURE'];
                    $body =array();
                    
                    switch($contentType){
                    case CONTENT_TYPE_JSON:
                        $body['status'] = $status_header;
                        $body['title'] = $this->statusMessage;
                        $body['message'] = $message;
                        $body['signature'] = $signature;
                        $pbody = self::json_encode($body);
                    break;
                    case CONTENT_TYPE_XML:
                    case 'text/xml':
                        $body['status'] = $status_header;
                        $body['title'] = $this->statusMessage;
                        $body['message'] = $message;
                        $body['signature'] = $signature;
                        // using the XML_SERIALIZER Pear Package  
                        $options = array  
                        (  
                            'indent' => '     ',  
                            'addDecl' => false,  
                            'rootName' => 'result',  
                            XML_SERIALIZER_OPTION_RETURN_RESULT => true  
                        );  
                        $serializer = new XML_Serializer($options);
                        $this->_override_type = 'application/xml';  
                        $pbody = $serializer->serialize($body);  
                    break;
                    case CONTENT_TYPE_SCRIPT:
                    case 'text/javascript':
                        $pbody = "/*{$this->statusMessage} \n{$message}\n{$signature} */";
                    break;
                    case CONTENT_TYPE_PLAIN:
                        $pbody =  "{$this->statusMessage} \n{$message}\n{$signature}";
                    break;
                    default:
                        $xbody = "<!DOCTYPE HTML>\n<html> <head> <title>$status_header</title></head>\n";
                        $xbody .= "<body><h1>{$this->statusMessage}</h1> \n<p>{$message}</p>\n";
                        $xbody .= "<hr /><address>{$signature}</address></body></html>";
                        $pbody = $xbody;
                    }
                }
            }
            $this->_prepared_body = $pbody;
        }
        return $this->_prepared_body;
    }
    public function send()
    {
        $this->sendHeaders();
        $status = $this->getStatus();
        if($status==304 /*|| $status==301*/ || $status==204 ) return ''; 
        /*
        304 Not modified
        204 No Content
        301 Moved - location header should be setDate header is required, ETag and Content-Location should be same as a 200, 
        Expires, Cache-Control and Vary are required if they’ve changed since last sent.
        */
        
        echo $this->_getPreparedBody();
        return $this;
    }
    function flush(){
        try{
            @ob_end_clean();
            @ob_flush();
            @flush();
        }catch(Exception $e){
            
        }
        if(function_exists('fastcgi_finish_request'))@fastcgi_finish_request(); // important when using php-fpm!
        usleep(.5);
        return $this;
    }
    static private function json_encode($string){
        /*if(is_array($string)){
            array_walk_recursive($string, function(&$val) {if(is_bool($val) || ($val===null)) return; $val = utf8_encode($val); });
        }elseif(!is_bool($string) && ($string !== null) && !is_numeric($string)){
            $string = utf8_encode((string)$string);
        }*/
        if(is_array($string)){
            array_walk_recursive($string, function(&$val) {if(is_string($val) ) $val = utf8_encode($val); });
        }elseif(is_string($string)){
            $string = utf8_encode((string)$string);
        }
        if(version_compare(phpversion(),'5.4.0','>=')){
            return json_encode($string,JSON_UNESCAPED_UNICODE);
        }
        return json_encode($string);
    }
    /*VERSION 2 function*/
    function setStatusOk($message=''){
        return $this->setStatus(200,$message);
    }
    function setStatusCreated($message='Created'){
        return $this->setStatus(201,$message);
    }
    /**
     * Accepted, a clients request is pending and will be completed later. 
     * Location header contains the expected address of the new resource so it can be checked later.
    */
    function setStatusAccepted($message=''){
        return $this->setStatus(202,$message);
    }
    function setStatusNoContent($message='No Content'){
        return $this->setStatus(204,$message);
    }
    /**
    301 – Moved Permanently, the API has moved a resource in response to the request, 
    or an old resource is requested. Location contains the new URI.
    302 Found
    303 See Other
    307 Temporary Redirect
    */
    function setStatusMoved($location,$message='', $type = 301){
        if(!in_array($type,array(301,302,303,307))) $type =301;
        $this->setStatus($type,$message);
        $this->setHeader('Location',$location);
        return $this;
    }
    /**
    304 – Not Modified, the client already has this data, used when the client provided a 
    If-Modified-Since header and the data hasn’t been modified. 
    Date header is required, ETag and Content-Location should be same as a 200, 
    Expires, Cache-Control and Vary are required if they’ve changed since last sent.
    */
    function setStatusNotModified($message='Not Modified'){
        return $this->setStatus(304,$message);
    }
    /**
    401 – Unauthorized, wrong credentials provided, or no credentials provided. 
    WWW-Authenticate header should describe the authentication methods accepted. 
    Entity-body could contain more details about the error.*/
    function setStatusUnauthorized($message='Unauthorized'){
        return $this->setStatus(401,$message);
    }
    /**
     404 – Not Found, no resource matches the requested URI, there is no reference to it on the server
    */
    function setStatusNotFound($message='Not Found'){
        return $this->setStatus(404,$message);
    }
    /**
     * 409 – Conflict, client attempted to do something which would leave a resource in an inconsistent state, 
     * such as create a user with an already taken name. 
     * Location could point to the source of the conflict. Entity-body to describe the conflict.

    */
    function setStatusConflict($message='Conflict'){
        return $this->setStatus(409,$message);
    }
    /**
     * 412 – Precondition failed, client wanted to modify a resource using a 
     * If-Unmodified-Since/If-Match header, the resource had been modified by someone else.
    */
    function setStatusFailed($message='Precondition failed'){
        return $this->setStatus(412,$message);
    }
    /**
     * 400 – Bad Request, there is a client-side problem, the document in the entity-body should 
contain more info on the problem
    */
    function setStatusBadRequest($message='Bad Request'){
        return $this->setStatus(400,$message);
    }
    /**
     * 400 Bad Request - The request is malformed, such as if the body does not parse
401 Unauthorized - When no or invalid authentication details are provided. Also useful to trigger an auth popup if the API is used from a browser
403 Forbidden - When authentication succeeded but authenticated user doesn't have access to the resource
404 Not Found - When a non-existent resource is requested
405 Method Not Allowed - When an HTTP method is being requested that isn't allowed for the authenticated user
410 Gone - Indicates that the resource at this end point is no longer available. Useful as a blanket response for old API versions
415 Unsupported Media Type - If incorrect content type was provided as part of the request
422 Unprocessable Entity - Used for validation errors
429 Too Many Requests - When a request is rejected due to rate limiting
    */
    function setStatusError($type=410, $message='Gone'){
        if($type < 400 || $type >499) $type = 410;
        if(func_num_args()==1){
            $a[400] = 'Bad Request';
            $a[401] = 'Unauthorized';
            $a[403] = 'Forbidden';
            $a[404] = 'Not Found';
            $a[405] = 'Method Not Allowed';
            $a[410] = 'Gone';
            $a[415] = 'Unsupported Media Type';
            $a[422] = 'Unprocessable Entity';
            $a[429] = 'Too Many Requests'; 
            $message = isset($a[$type])?$a[$type]:'Bad Request';
        }
        return $this->setStatus($type,$message);
    }
    /**
     * 
    */
    public function setDate($date='now'){
        //'D, d M Y H:i:s T'  
        if(is_numeric($date)){
            $value = gmdate('r',$date);
        }else{
            $d = date_create($date);
            if(!$d) $d = date_create('now');
            $value = gmdate('r',$d->format('U'));
        }
        $this->setHeader('Date',$value);
        return $this;
    }
    function setExpires($date){
        if(is_numeric($date)){
            $value = gmdate('r',$date);
        }else{
            $d = date_create($date);
            if(!$d) $d = date_create('now');
            $value = gmdate('r',$d->format('U'));
        }
        $this->setHeader('Expires',$value);
        return $this; 
    }
    public function setLastModified($date='now'){
        if(is_numeric($date)){
            $value = gmdate('r',$date);
        }else{
            $d = date_create($date);
            if(!$d) $d = date_create('now');
            $value = gmdate('r',$d->format('U'));
        }
        $this->setHeader('Last-Modified',$value);
        return $this;
    }
    
    private static function _prepareAsXml($body){
        if(is_string($body)){
            return $body;
        }
        if(class_exists('XML_Serializer',false)){
            // using the XML_SERIALIZER Pear Package  
            $options = array  
            (  
                'indent' => '    ',  
                'addDecl' => false,  
                'rootName' =>'root',  
                XML_SERIALIZER_OPTION_RETURN_RESULT => true  
            );
            $serializer = new XML_Serializer($options);  
            return $serializer->serialize($body);
        }elseif(class_exists('SimpleXMLExtended',false)){
            if(!function_exists('array2xml')){
                function array2xml($array, $xml = false){
                    if($xml === false){
                        $xml = new SimpleXMLExtended('<root/>');
                    }
                    foreach($array as $key => $value){
                        if(is_array($value)){
                            if(!is_numeric($key)){
                            	array2xml($value, $xml->addChild($key));
                            } else {
                            	array2xml($value, $xml->addChild('item'));
                            }
                        }else{
                            if(!is_numeric($key)){
                            	$xml->addChild($key, $value);
                            } else {
                            	$xml->addChild('item', $value);
                            }
                        }
                    }
                    return $xml->asXML();
                }
            }
            return array2xml($body);
        }
    }  
    private static function _prepareAsJson($body){
        try{
            $str = self::json_encode($body);
        }catch(exception $e){
            error_log(print_r($e,1));
        }
        if(json_last_error()){
            $es = false;
            switch (json_last_error()) {
                case JSON_ERROR_NONE:
                    $es = ' - No errors';
                break;
                case JSON_ERROR_DEPTH:
                    $es = ' - Maximum stack depth exceeded';
                break;
                case JSON_ERROR_STATE_MISMATCH:
                    $es = ' - Underflow or the modes mismatch';
                break;
                case JSON_ERROR_CTRL_CHAR:
                    $es = ' - Unexpected control character found';
                break;
                case JSON_ERROR_SYNTAX:
                    $es = ' - Syntax error, malformed JSON';
                break;
                case JSON_ERROR_UTF8:
                    //$s = iconv('UTF-8', 'UTF-8//IGNORE', $s);
                    //$es = ' - Malformed UTF-8 characters, possibly incorrectly encoded';
                    if(is_scalar($body))
                        $body = utf8_encode($body);
                    elseif(is_object($body))
                        $body = utf8_encode((string)$body);
                    else
                        $body = array_map('utf8_encode', $body);
                    $str= self::json_encode($body);
                break;
                default:
                    $es = ' - Unknown error';
                break;
            }
            if($es)error_log("JSON encode error: " . json_last_error(). " $es");
        }
        return $str;
    }
    /*private function sendAsPHP(){
        header("Content-Type: application/php;charset=utf-8");
	echo serialize($out);
	exit;
    }
    private function sendAsTEXT(){
        if(!function_exists('array2text')){
        function array2text($array, $indent = ''){
            $xml = '';
            foreach($array as $key => $value){
                if(is_array($value)){
                    $xml .= array2text($value,"{$indent}*");
                }else{
                    $xml .= "{$indent}$key: $value\n";
                }
            }
            return $xml;
        }
        }
        echo array2text($this->getBody(),'');        
    }*/
    
}

class ELI_response_meta{
    const HAS_CACHED = 8;
    const NOSTORE = 2;
    const DELETE_CACHE = 4;
    const SEND_CACHE = 1;
    protected $flags = 0;
    public function setFlag($flag){
        $this->flags |=(int)$flag;
        return $this;
    }
    public function unsetFlag($flag){
        $flag = (int)$flag;
        if((($this->flags & $flag) == $flag)){
            $this->flags ^= $flag;
        }
        return $this;
    }
    public function useCache(){
        $flag = self::SEND_CACHE;
        return (($this->flags & $flag) == $flag);
    }
    public function saveCache(){
        $flag = self::NOSTORE;
        return !(($this->flags & $flag) == $flag);
    }
    public function deleteCache(){
        $flag = self::DELETE_CACHE;
        return (($this->flags & $flag) == $flag);
    }
    public function hasCached(){
        $flag = self::HAS_CACHED;
        return (($this->flags & $flag) == $flag);
    }
}
class ELI_response_header{
    public $header;
    public $replace;
    public $status =null;
    public function __construct($header,$replace=true,$status=null) {
        $this->header = $header;
        $this->replace = $replace;
        $this->status=$status;
    }
    public function toString() {
        return $this->header;
    }
    
    public function toArray() {
        return array(
        'header'=>$this->header,
        'replace'=>$this->replace,
        'status'=>$this->status,
        );
    }
    public function __toString() {
        return $this->toString();
    }
}