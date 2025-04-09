<?php
/**
 * @author Edwards
 * @copyright 2015
 * 
 * 
 * this class provides tools for  the response
 * 
 */
namespace ELIX;
if(!defined('CONTENT_TYPE_PLAIN')) define('CONTENT_TYPE_PLAIN','text/plain');
if(!defined('CONTENT_TYPE_HTML')) define('CONTENT_TYPE_HTML','text/html');
if(!defined('CONTENT_TYPE_JSON')) define('CONTENT_TYPE_JSON','application/json');
if(!defined('CONTENT_TYPE_DOWNLOAD')) define('CONTENT_TYPE_DOWNLOAD','application/x-download');
if(!defined('CONTENT_TYPE_XML')) define('CONTENT_TYPE_XML','application/xml');
if(!defined('CONTENT_TYPE_SCRIPT')) define('CONTENT_TYPE_SCRIPT','application/javascript');

if(!defined('HTTP_NO_CONTENT')) define('HTTP_NO_CONTENT',204);

class RESPONSE_new {
    protected $body =null;
    protected $headers = null;
    protected $sendType ='';
    protected $autoError =null;
    
    protected $options = array();
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
    
    
    static function getHelper()
    {
        static $a = null;
        if($a === null) $a = new RESPONSE_helper;
        return $a;
    }
    private function getBodyString()
    {
        if(NULL === $this->body){
            if($this->autoError){
                $ST = $this->getStatus();
                if(! $ST->getCode() ){
                    $ST->setStatus( 500);
                }
                if($ST->getCode()==304 || $ST->getCode()==204 ) return '';
                return RESPONSE_helper::getErrorBody($ST->getCode(),$this->getSendType());
            }
            return '';
        }elseif($this->body instanceof RESPONSE_body_trait){
            return $this->body->toString($this->getSendType());
        }
        return (string)$this->body;
    }
    private function getBodyLength()
    {
        if(NULL === $this->body){
            if($this->autoError){
                $ST = $this->getStatus();
                if(! $ST->getCode() ){
                    $ST->setStatus( 500);
                }
                if($ST->getCode()==304 || $ST->getCode()==204 ) return 0;
                return strlen(RESPONSE_helper::getErrorBody($ST->getCode(),$this->getSendType()));
            }
            return 0;
        }elseif($this->body instanceof RESPONSE_body_trait){
            return $this->body->getLength($this->getSendType());
        }
        return strlen((string)$this->body);
    }
    
    /**
     * RESPONSE::output()
     * 
     * @param string $destination
     *      O = sends to stdout with headers equivalent to calling ->send()
     *      B = sends only body as string
     *      H = sends only header as string
     *      M = returns the complete MESSAGE string i.e. HEADER\n\nBODY
     *      A = returns headers as string
     *      C = returns body as string
     *      filename save the M to a file
     *      
     * @return void
     */
    public function output($destination='O')
    {
        if(!$destination) $destination='O';
        if(strlen(trim($destination)==1)) $destination = strtoupper(trim($destination));
        $ST = $this->getStatus();
        if(! $ST->getCode() ){
            $ST = new RESPONSE_header_status();
            $ST->setStatus( ($this->body===null)?500:200 );
        }
        if ( ! empty($_SERVER['FCGI_SERVER_VERSION']))
		{
            $sl = 'Status: ' . trim($ST->getStatus() .' ' . $ST->getMessage());
		}else{
            $sl = (string)$ST;
		}
        $CL = $this->getHeader()->get('Content-length');
        if($CL ===false && !in_array($destination,array('B','C'))){
            if($ST->getCode()!=304 && $ST->getCode()!=204 ){
                $this->getHeader()->add('Content-length',$this->getBodyLength());
            }
        }
        $hl = (string)$this->getHeader();
        switch($destination){
            case 'O':
            case 'H':
                header($sl);
                $this->getHeader()->send();
                if($destination =='H') break;
            case 'B':
                if($ST->getCode()==304 || $ST->getCode()==204 ) return '';
                echo $this->getBodyString();
            break;
            case 'M':
                $b = $this->getBodyString();
                return "{$sl}\n{$hl}\n\n{$b}";
            break;
            case 'A':
                return "{$sl}\n{$hl}";
            break;
            case 'C':
                if(NULL === $this->body){
                    return '';
                }else{
                    return $this->getBodyString();
                }
            break;
            default:
                if($ST->getCode()==304 || $ST->getCode()==204 )
                    $b = '';
                else
                    $b = $this->getBodyString();
                if($f = fopen($destination,'wb')){
                    fwrite($f,"{$sl}\n{$hl}\n\n{$b}");
    			    fclose($f);
                }else{
                    throw Exception('Unable to create output file: '.$destination);
                }
        }
    }
    public function getStatus()
    {
        return $this->getHeader()->getStatus();
    }
    public function getHeader()
    {
        if(NULL == $this->header){
             $this->header = new RESPONSE_header;
        }
        if(func_num_args()){
            trigger_Error('Calling this method getHeader(value) to retrieve a value is deprecates. Try ->getHeader()->get(value)->value');
            return $this->header->get(func_get_arg(0))->value;
        }
        return $this->header;
    }
    public function setCacheControl($value){
        $this->getHeader()->getCacheControl()->set($value);
        return $this;
    }
    public function getCacheControl(){
        return $this->getHeader()->getCacheControl();
    }
    public function getBody()
    {
        if(NULL == $this->body) $this->header = new RESPONSE_body_dynamic;
        return $this->body;
    }
    
    public function __set($name, $value) {
        $name = strtolower($name);
        if($name=='iscacheable'){
            error_log('->isCacheable from ELI library is not supported in ELIX. Write you local implementation.');
            return;
        }
    }
    /**
	 * Redirects to another uri/url.  Sets the redirect header,
	 * sends the headers and exits.  Can redirect via a Location header
	 * or using a refresh header.
	 *
	 * The refresh header works better on certain servers like IIS.
	 *
	 * @param   string  $url     The url
	 * @param   string  $method  The redirect method
	 * @param   int     $code    The redirect status code
	 *
	 * @return  void
	 */
	public static function redirect($url = '', $method = 'location', $code = 302)
	{
		$response = new static;
		$response->getStatus()->setStatus($code);
        
		if ($method == 'location')
		{
			$response->getHeader()->add('Location', $url);
		}
		elseif ($method == 'refresh')
		{
			$response->getHeader()->add('Refresh', '0;url='.$url);
		}
		else
		{
			return;
		}
		$response->send(true);
		exit;
	}
    /**
    301 – Moved Permanently, the API has moved a resource in response to the request, 
    or an old resource is requested. Location contains the new URI.
    302 Found
    303 See Other
    307 Temporary Redirect
    */
    function setMoved($location,$message='', $type = 301){
        if(!in_array($type,array(301,302,303,307))) $type =301;
        $this->getStatus()->setStatus($type,$message);
        $this->getHeader()->add('Location',$location,true);
        return $this;
    }
    function setAutoError($bool=true)
    {
        $this->autoError = $bool;
        return $this;
    }
    function setFile($filepath,$filename='')
    {
        if(!$filename && $filepath) $filename = basename($filepath);
        $this->body = new RESPONSE_body_file($filepath);
        $this->getHeader()->setFilename($filename);
        return $this;
    }
    function setDownload($filepath,$filename='')
    {
        if(!$filename && $filepath) $filename = basename($filepath);
        $this->body = new RESPONSE_body_file($filepath);
        $this->getHeader()->setContentDisposition('attachment');
        $this->getHeader()->setFilename($filename);
        $this->getHeader()->add('Content-type','application/x-download',false);
        
        return $this;
    }
    function setDownloadData($filedata,$filename='')
    {
        $this->body = new RESPONSE_body_data($filedata);
        $this->getHeader()->add('Content-type','application/x-download',false);
        $this->getHeader()->setContentDisposition('attachment');
        $this->getHeader()->setFilename($filename);
        return $this;
    }
    function setBody($body)
    {
        if(is_bool($body)){
            $this->body = new RESPONSE_body_data($body?'TRUE':'FALSE');
        }elseif(is_scalar($body)){
            $this->body = new RESPONSE_body_data($body);
        }elseif(is_array($body)){
            $this->body = new RESPONSE_body_array($body);
        }else{
            $this->body = new RESPONSE_body_dynamic($body);
        }
        return $this;
    }
    function setSendType($type){
        $this->sendType = $type;
    }
    
    function getSendType(){
        $type = strtolower($this->sendType);
        switch($type)
    	{
    	    case 'text':
                return 'TEXT'; //uses implode cast with new line
            case 'string':
                return 'STRING'; //uses string cast

            case 'export':
            case 'debug':
                return 'EXPORT'; //uses echo without cast

            case 'json':
            case 'text/json':
            case 'application/json':
                return 'JSON'; //uses json encode

            case 'xml':
            case 'text/xml':
            case 'application/xml':
                return 'XML'; //uses json encode

            case 'text/plain':
            case 'text/html':
            case 'text/plain':
            case 'application/x-download':
                return 'STRING';

            case 'text/javascript': 
            case 'application/javascript': 
            case 'echo':
                return 'ECHO'; //uses echo without cast

    	}
        if($type = $this->getHeader()->getContentType())
        {
            switch(strtolower($type->getContent()))
        	{
        	    case 'text/json':
                case 'application/json':
                    return 'JSON'; //uses json encode

                case 'text/xml':
                case 'application/xml':
                    return 'XML'; 

                case 'text/plain':
                case 'text/html':
                case 'application/x-download':
                    return 'STRING';
                case 'text/javascript': 
                case 'application/javascript': 
                    return 'ECHO'; //uses echo without cast

        	}
        }
        return 'ANY';

    }
    public function reset()
    {
        $this->getStatus()->reset();
        $this->getHeader()->reset();
        $this->body = null;
    } 
    public function __toString() { return $this->Output('M'); }
    public function __call($name, $arguments) {
        $name = strtolower($name);
        if($name =='getstatusheader') return $this->getStatus();
        if($name =='clear') return $this->reset();
        
        static $status_fx =array('setprotocol','setstatus','setstatusmessage',
                            'setstatuserror','setstatusok',
                            'setstatuscreated','setstatusaccepted',
                            'setstatusnocontent','setstatusmoved',
                            'setstatusnotmodified','setstatusunauthorized',
                            'setstatusnotfound','setstatusconflict',
                            'setstatusfailed','setstatusbadrequest',
                            );
        
        if(in_array($name,$status_fx)){
            call_user_func_array(array($this->getStatus(),$name),$arguments);
            return $this;
        }
        
        
        static $header_fx =array(
                            'setfilename','setcanonical',
                            'setexpires','getcontentdisposition',
                            'setdate','setcontenttype','getcontenttype',
                            'setcharset','getcharset',
                            'csp','csp_report','cors',
                            'maxage','smaxage','nocache','nostore','mustrevalidate',
                            );
        
        if(in_array($name,$header_fx)){
            call_user_func_array(array($this->getHeader(),$name),$arguments);
            return $this;
        }
        if( $name == 'append'){
            if($this->body === null){
                $this->setBody(array());
            }elseif($this->body instanceof RESPONSE_body_array){
                //nothng to convert
            }elseif($this->body instanceof RESPONSE_body_data){
                //do not convert
            }elseif($this->body instanceof RESPONSE_body_dynamic){
                //do not convert
            }elseif($this->body instanceof RESPONSE_body_file){
                //cant append
                return $this;
            }else{
                return $this;
            }
            call_user_func_array(array($this->getBody(),$name),$arguments);
            return $this;
        }
        
        static $array_body_fx =array(
                            'addfield','unsetfield',
                            'setfield','setsubfield',
                            'getfield'
                            );
        
        if(in_array($name,$array_body_fx)){
            if($this->body === null){
                $this->setBody(array());
            }elseif($this->body instanceof RESPONSE_body_array){
                //nothng to convert
            }elseif($this->body instanceof RESPONSE_body_data){
                $this->setBody(array($this->body->getRaw()));
            }elseif($this->body instanceof RESPONSE_body_dynamic){
                $this->setBody(array($this->body->getRaw()));
            }elseif($this->body instanceof RESPONSE_body_file){
                $this->body = null;
                $this->setBody(array());
            }else{
                $this->setBody(array($this->body));
            }
            call_user_func_array(array($this->getBody(),$name),$arguments);
            return $this;
        } 
 
        static $deprecated_fx=array('getexpires','getheader','getetag','hasstatus');
        if(in_array($name,$deprecated)){
            error_log('this function is deprecated in ELIX:response: ' .$name);
            return $this;
        }
        
    }

    
}
class RESPONSE_helper{
    
    public static $statuses = array(
		100 => 'Continue',
		101 => 'Switching Protocols',
		102 => 'Processing',
		200 => 'OK',
		201 => 'Created',
		202 => 'Accepted',
		203 => 'Non-Authoritative Information',
		204 => 'No Content',
		205 => 'Reset Content',
		206 => 'Partial Content',
		207 => 'Multi-Status',
		208 => 'Already Reported',
		226 => 'IM Used',
		300 => 'Multiple Choices',
		301 => 'Moved Permanently',
		302 => 'Found',
		303 => 'See Other',
		304 => 'Not Modified',
		305 => 'Use Proxy',
		307 => 'Temporary Redirect',
		308 => 'Permanent Redirect',
		400 => 'Bad Request',
		401 => 'Unauthorized',
		402 => 'Payment Required',
		403 => 'Forbidden',
		404 => 'Not Found',
		405 => 'Method Not Allowed',
		406 => 'Not Acceptable',
		407 => 'Proxy Authentication Required',
		408 => 'Request Timeout',
		409 => 'Conflict',
		410 => 'Gone',
		411 => 'Length Required',
		412 => 'Precondition Failed',
		413 => 'Request Entity Too Large',
		414 => 'Request-URI Too Long',
		415 => 'Unsupported Media Type',
		416 => 'Requested Range Not Satisfiable',
		417 => 'Expectation Failed',
		418 => 'I\'m a Teapot',
		422 => 'Unprocessable Entity',
		423 => 'Locked',
		424 => 'Failed Dependency',
		426 => 'Upgrade Required',
		428 => 'Precondition Required',
		429 => 'Too Many Requests',
		431 => 'Request Header Fields Too Large',
		500 => 'Internal Server Error',
		501 => 'Not Implemented',
		502 => 'Bad Gateway',
		503 => 'Service Unavailable',
		504 => 'Gateway Timeout',
		505 => 'HTTP Version Not Supported',
		506 => 'Variant Also Negotiates',
		507 => 'Insufficient Storage',
		508 => 'Loop Detected',
		509 => 'Bandwidth Limit Exceeded',
		510 => 'Not Extended',
		511 => 'Network Authentication Required',
	);
    static function getStatus($status=null)
    {
        if(func_num_args()){
            if(isset(self::$statuses[$status]))
                return self::$statuses[$status];
            return '';
        }else{
            return self::$statuses;
        }
    }
    static function getStatusClass($status=null)
    {
        if(func_num_args()){
            if($status >= 500 && $status < 600){
                return 'Server Error';
            }elseif ($status >= 400){
                return 'Client Error';
            }elseif ($status >= 300){
                return 'Redirection';
            }elseif ($status >= 200){
                return 'OK';
            }elseif ($status >= 100){
                return 'Informational';
            }
            return '';
        }else{
            return array('100'=>'Informational','200'=>'OK','300'=>'Redirection','400'=>'Client Error','500'=>'Server Error');
        }
    }
    static function getFormattedDate($date)
    {
        $temp = date_create($date);
        if($temp===false) return '';
        return $temp->format('D, d M Y H:i:s').' GMT';
    }
    
    static function json_encode($string){
        if(is_array($string)){
            array_walk_recursive($string, function(&$val) {if(is_bool($val)) return; $val = utf8_encode($val); });
        }elseif(!is_bool($string)){
            $string = utf8_encode($string);
        }
        if(version_compare(phpversion(),'5.4.0','>=')){
            return json_encode($string,JSON_UNESCAPED_UNICODE);
        }
        return json_encode($string);
    }
    static function getErrorBody($code,$type){
        $message = $statusMessage = self::getStatus($code);
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
        $status_header = trim($code .' ' . $statusMessage);
        $signature = ($_SERVER['SERVER_SIGNATURE'] == '') ? $_SERVER['SERVER_SOFTWARE'] . ' Server at ' . $_SERVER['SERVER_NAME'] . ' Port ' . $_SERVER['SERVER_PORT'] : $_SERVER['SERVER_SIGNATURE'];
        $body =array();
        $body['status'] = $status_header;
        $body['title'] = $statusMessage;
        $body['message'] = $message;
        $body['code'] = $code;
        $body['result'] = self::getStatusClass($code);
        $body['signature'] = $signature;
        echo self::json_encode($body);
                
        switch($type){
        case 'JSON':
            return self::json_encode($body);;
        break;
        case 'TEXT': //UNTESTED
            $str='';
            foreach($body as $v){
                $str .= (string)$v;
                $str .= "\n";
            }
            return $str;
        break;
        case 'STRING': //UNTESTED
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
            return array2text($body,'');
        break;
        case 'ECHO': //UNTESTED
            return "/*$status_header \n{$statusMessage} \n{$message}\n{$signature} */";
        break;
        case 'EXPORT': //UNTESTED
            return var_export($body,1);
        break;
        case 'XML': //UNTESTED
            if(class_exists('XML_Serializer',false)){
                // using the XML_SERIALIZER Pear Package  
                $options = array  
                (  
                    'indent' => '    ',  
                    'addDecl' => false,  
                    'rootName' =>'result',  
                    XML_SERIALIZER_OPTION_RETURN_RESULT => true  
                );
                $serializer = new XML_Serializer($options);  
                return $serializer->serialize($body);
            }elseif(class_exists('SimpleXMLExtended',false)){
                if(!function_exists('array2xml')){
                    function array2xml($array, $xml = false){
                        if($xml === false){
                            $xml = new SimpleXMLExtended('<result/>');
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
        break;
        default:
        case 'ANY': //UNTESTED
            $xbody = "<!DOCTYPE HTML>\n<html> <head> <title>$status_header</title></head>\n";
            $xbody .= "<body><h1>{$statusMessage}</h1> \n<p>{$message}</p>\n";
            $xbody .= "<hr /><address>{$signature}</address></body></html>";
            echo $xbody;
        break;
        }
    }
    
}
class RESPONSE_header_status /*extends RESPONSE_header_item*/ {
    protected $code=null;
    protected $message='';
    protected $protocol = '';
    public function __construct($protocol=null, $code=null, $message='') {
        $n = func_num_args();
        $this->reset();
        if($n >= 1) $this->setProtocol( $protocol);
        if($n >= 2) $this->code = $code;
        if($n >= 3) $this->message = $message;
    }
    public function __set($name, $value) {
        $name = strtolower($name);
        if($name =='value') $name ='code';
        if($name=='code') $this->code = (int)$value;
        if($name=='protocol' && $value) $this->protocol = $value;
        if($name=='message') $this->message = (string)$value;
    }
    public function __get($name) {
        $name = strtolower($name);
        if($name =='value') $name ='code';
        if($name=='code') return $this->code;
        if($name=='protocol') return $this->protocol;
        if($name=='message') return $this->message;
        if($name=='replace') return true;
        if($name=='name') return 'Status';
        return null;
    }
    public function __toString() {
        if(!$this->protocol || !$this->code) return '';
        return trim("{$this->protocol} {$this->code} {$this->message}");
    }
    
    public function reset(){
        $this->setProtocol();
        $this->code = 0;
        $this->message = '';
    }
    public function getStatus(){
        return $this->code;
    }
    public function getStatusMessage(){
        return $this->message;
    }
    public function getCode(){
        return $this->code;
    }
    
    public function getMessage(){
        return $this->message;
    }
    public function getProtocol(){
        return $this->protocol;
    }
    public function getStatusHeader(){
        return $this->__toString();
    }
    function setProtocol($protocol=null)
    {
        if(func_num_args()==0)$protocol = isset($_SERVER["SERVER_PROTOCOL"]) ? $_SERVER["SERVER_PROTOCOL"] : 'HTTP/1.1';
        if($protocol)$this->protocol = strtoupper($protocol);
        return $this;
    }
    function setStatusMessage($message='')
    {
        $this->message =$message;
    }
    function setStatus($code,$message='')
    {
        $this->code = $code;
        if(!$message && $code){
            $message = RESPONSE_helper::getStatus($code);
        }
        $this->message =$message;
        return $this;
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
            $this->setStatus($type);
        }else{
            $this->setStatus($type,$message);
        }
        return $this;
    }
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
    304 – Not Modified, the client already has this data, used when the client provided a 
    If-Modified-Since header and the data hasn’t been modified. 
    Date header is required, ETag and Content-Location should be same as a 200, 
    Expires, Cache-Control and Vary are required if they’ve changed since last sent.
    */
    function setStatusNotModified($message='Not Modified'){
        return $this->setStatus(304,$message);
    }
    /**
    301 – Moved Permanently, the API has moved a resource in response to the request, 
    or an old resource is requested. Location contains the new URI.
    302 Found
    303 See Other
    307 Temporary Redirect
    */
    function setStatusMoved($message='', $type = 301){
        if(!in_array($type,array(301,302,303,307))) $type =301;
        $this->setStatus($type,$message);
        return $this;
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
}
class RESPONSE_header_item{
    protected $name;
    protected $value;
    protected $replace = false;
    protected $status;
    public function __construct($name, $value, $replace=true,$status=null) {
        $this->name = $name;
        $this->value = $value;
        $this->replace = $replace;
        $this->status=$status;
    }

    public function __get($name) {
        $name = strtolower($name);
        if($name == 'value'){
            return $this->value;
        }
        if($name == 'name'){
            return $this->name;
        }
        if($name == 'replace'){
            return $this->replace;
        }
        if($name == 'status'){
            return $this->status;
        }
        return null;
    }
    public function __set($name, $value) {
        $name = strtolower($name);
        if($name == 'value'){
            $this->value = $value;
            return;
        }
        if($name == 'name'){
            $this->name = $value;
            return;
        }
        if($name == 'replace'){
            $this->replace = $value;
            return;
        }
        if($name == 'status'){
            $this->status = $value;
            return;
        }
    }
    public function __toString() {
        if(!$this->name || !$this->value) return '';
        return "{$this->name}: {$this->value}";
    }
    public function getHeader()
    {
        return $this->__toString();
    }
    public function getNameUpper()
    {
        return strtoupper($this->name);
    }
    public function getContent()
    {
        $x = explode(';', $this->value);
        return trim($x[0]);
    }
    public function setContent($value)
    {
        $x = explode(';', $this->value);
        $x[0] = $value;
        return implode(';',$x);
    }
    public function getParam()
    {
        $x = explode(';', $this->value,2);
        if(count($x)>1)
            return $x[1];
        return '';
    }
    public function setParam($param)
    {
        $this->value = $this->getContent();
        $this->addParam($param);
        return $this;
    }
    public function addParam($param)
    {
        $this->value .= ';'.$param;
        return $this;
    }
}
class RESPONSE_header_cachecontrol extends RESPONSE_header_item{
    protected $atoms =array();
    protected $name = 'Cache-control';
    public function __construct($value, $replace=true) {
        $this->set($value);
        $this->replace = $replace;
    }
    public function __get($name) {
        $name = self::fixName($name);
        if($name == 'value'){
            return $this->value();
        }
        if(isset($this->atoms[$name])){
            return $this->atoms[$name];
        }
        return null;
    }
    public function __set($name, $value) {
        $name = self::fixName($name);
        if($name == 'value'){
            $this->set($value);
            return;
        }
        if($value !== null){
            if(in_array($name,array('must-revalidate','no-cache',
                                    'no-store','no-transform','public','private',
                                    'proxy-revalidate'))){
                $value =(bool)$value;
            }else{
                $value =(int)$value;
            }
        }
        $this->atoms[$name] = $value;
    }
    public function __call($name, $arguments) {
        $name = self::fixName($name);
        if(count($arguments)){
            $this->__set($name,$arguments[0]);
            return $this;
        }else{
            if(isset($this->atoms[$name])){
                return $this->atoms[$name];
            }
            return null;
        }
    }
    private static function fixName($name){
        $name = strtolower($name);
        $name =str_replace('_','-',$name);
        static $trans = null;
        if($trans === null){
            $trans =array();
            $trans['mustrevalidate'] = 'must-revalidate';
            $trans['nocache'] = 'no-cache';
            $trans['nostore'] = 'no-store';
            $trans['notransform'] = 'no-transform';
            $trans['proxyrevalidate'] = 'proxy-revalidate';
            $trans['maxage'] = 'max-age';
            $trans['smaxage'] = 's-maxage';
        }
        if(isset($trans[$name])){
            $name = $trans[$name];
        }
        return $name;
    }
    public function value() {
        if(func_num_args()){
            $v = implode(', ',func_get_args());
            $this->set($v);
            return $this;
        }
        $r =array();
        foreach($this->atoms as $n=>$v){
            if($n == 'max-age' || ($n=='s-maxage')){
                $r[] = "$n=$v";
            }elseif($v){
                $r[] = $n;
            }
        }
        return implode(', ',$r);
    }
    public function __toString() {
        $v = $this->value();
        if($v)
            return 'Cache-control: ' . $v;
        return $v;
    }
    public function set($cachedirective) {
        $n = func_num_args();
        
        if($n == 1){
            $this->atoms = array();
            $x = explode(',',$cachedirective);
            foreach($x as $v){
                if(strpos($v,'=')){
                    $r =explode('=',$v,2);
                    $this->__set($r[0],$r[1]);
                }else{
                   $this->__set($v,true); 
                }
            }
        }else if($n == 0){
            $this->atoms = array();
        }else if($n == 2){
            $this->__set($cachedirective,func_get_arg(1));
        }
        return $this;
    }
    public function get($name) {
        return $this->__get($name);
    }
    public function getHeader()
    {
        return $this->__toString();
    }
    public function getNameUpper()
    {
        return strtoupper($this->name);
    }
    public function getContent()
    {
        return $this->value();
    }
    public function setContent($value)
    {
        $this->__set($value);
        return $this;
    }
    public function getParam()
    {
        return $this->value();
    }
    public function setParam($param)
    {
        $this->__set($param,1);
        return $this;
    }
    public function addParam($param)
    {
        $this->__set($param,1);
        return $this;
    }
}


class RESPONSE_body_trait
{
    protected $data =null;
    public function getRaw() {return $this->data; }
    public function toString($type){
        return (string)$this->data;
    }
    public function getLength($type){
        return strlen((string)$this->data);
    }
}
class RESPONSE_body_dynamic extends RESPONSE_body_trait{
    
    public function __construct($body=null) {
        $this->data = $body;
    }
    public function __toString() {
        if(NULL === $this->data){
            return '';
        }
        if(is_object($this->data)){
            if(method_exists($this->data,'toString')) return $this->data->toString();
            if(method_exists($this->data,'__toString')) return (string)$this->data;
            try{
                return (string)$this->data;
            }catch(Exception $e){
                error_log("Could not convert to string in ELIX:response/RESPONSE_body_dynamic. " . $e->getMessage());
                return '';
            }
        }
        if(is_array($this->data)){
            return implode('',$this->data);
        }
        if(is_bool($this->data)){
            return $this->data?'true':'false';
        }
        if(is_scalar($this->data)){
            return $this->data;
        }
        return (string)$this->data;
    }
    public function getLength($type){
        return strlen( $this->toString($type));
    }
    public function toString($type){
        switch($type){
        case 'JSON': 
            if(NULL === $this->data){
                return 'null';
            }
            if(is_bool($this->data)){
                return $this->data?'true':'false';
            }
            try{
                $str= RESPONSE_helper::json_encode($this->data);
            }catch(exception $e){
                error_log($e->getMessage());
            }
            if(json_last_error()){
                $es = false;
                if(json_last_error() === JSON_ERROR_UTF8){
                    //$s = iconv('UTF-8', 'UTF-8//IGNORE', $s);
                    //$es = ' - Malformed UTF-8 characters, possibly incorrectly encoded';
                    if(is_scalar($this->data))
                        $body = utf8_encode($this->data);
                    elseif(is_array($this->data))
                        $body = array_map('utf8_encode', $this->data);
                    elseif(is_object($this->data)){
                        $body =(array)$this->data;
                        $body = array_map('utf8_encode', $body);
                    }
                            
                    $str= RESPONSE_helper::json_encode($this->data);
                }else{
                    if (!function_exists('json_last_error_msg')) {
                        function json_last_error_msg() {
                            static $errors = array(
                                JSON_ERROR_NONE             => null,
                                JSON_ERROR_DEPTH            => 'Maximum stack depth exceeded',
                                JSON_ERROR_STATE_MISMATCH   => 'Underflow or the modes mismatch',
                                JSON_ERROR_CTRL_CHAR        => 'Unexpected control character found',
                                JSON_ERROR_SYNTAX           => 'Syntax error, malformed JSON',
                                JSON_ERROR_UTF8             => 'Malformed UTF-8 characters, possibly incorrectly encoded'
                            );
                            $error = json_last_error();
                            return array_key_exists($error, $errors) ? $errors[$error] : "Unknown error ({$error})";
                        }
                    }
                    $es = json_last_error_msg();
                }
                if($es)error_log("JSON encode error: " . json_last_error(). " $es");
            }
            return $str;
        break;
        case 'TEXT': //UNTESTED
            if(NULL === $this->data){
                return 'null';
            }
            if(is_bool($this->data)){
                return $this->data?'true':'false';
            }
            if(is_scalar($this->data)){
                return $this->data;
            }
            $str ='';
            if(is_array($this->data)){
                foreach($this->data as $v){
                    $str .= (string)$v;
                    $str .= "\n";
                }
                return $str;
            }
            if(is_object($this->data)){
                $a = (array)$this->data;
                foreach($a as $v){
                    $str .= (string)$v;
                    $str .= "\n";
                }
                return $str;
            }
        break;
        case 'STRING': //UNTESTED
            if(NULL === $this->data){
                return 'null';
            }
            if(is_bool($this->data)){
                return $this->data?'true':'false';
            }
            if(is_array($this->data)){
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
                return array2text($this->data,'');
            }
            
            return (string)$this->data;
        break;
        case 'ECHO': //UNTESTED
            if(NULL === $this->data){
                return 'null';
            }
            if(is_bool($this->data)){
                return $this->data?'true':'false';
            }
            if(is_object($this->data)){
                return (string)$this->data;
            }
            return $this->data;
        break;
        case 'EXPORT': //UNTESTED
            return var_export($this->data,1);
        break;
        case 'XML': //UNTESTED
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
                return $serializer->serialize($this->data);
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
                return array2xml($this->data);
            }
        break;
        case 'ANY': //UNTESTED
            return (string)$this->data;
        break;
        }
    }
 
    function append($body){
        if(is_array($this->data)){
            $this->data[] = $body;
        }
        if(is_scalar($this->data)){
            $this->data .= $body;
        }
    }
    
    
}

class RESPONSE_body_array extends RESPONSE_body_dynamic implements \ArrayAccess, \Iterator, \Countable{
    public function __construct($body=array()) {
        if(is_array($body))
            $this->data = $body;
        else
            $this->data = array($body);
    }
    public function __toString() {
        return implode('',$this->data);
    }
    function append($body){
        $this->data[] = $body;
    }
    function addField($field,$content){
        
        if($field){
            if(!isset($this->data[$field]))$this->data[$field] = $content;
        }else
            $this->data[$field] = $content;
        
    }
    function unsetField($field){
        unset($this->data[$field]);
    }
    function setField($field,$content=null){
        
        if(func_num_args() ==1){
            if(is_Array($field)){
                foreach($field as $k=>$v)$this->data[$k] = $v; 
            }else{
                $this->data[$field] = '';
            }
        }else
            $this->data[$field] = $content;
    }
    function setSubField($field,$subIndex,$content=null){
        if(!empty($this->data[$field]) && !is_array($this->data[$field])){
            $this->data[$field][] = $this->data[$field];
        }
        if($subIndex){
            if(is_Array($subIndex)){
                foreach($subIndex as $k=>$v)$this->data[$field][$k] = $v;
            }else{
                $this->data[$field][$subIndex] = $content;
            }
        }else
            $this->data[$field][] = $content;
    }
    function getField($field,$subIndex=null){
        if(func_num_args()>1){
            if(isset($this->data[$field][$subIndex])){
                return $this->data[$field][$subIndex];
            }elseif((null ===$subIndex) && isset($this->data[$field])){
                return $this->data[$field];
            }else{
                return null;
            }
        }
        if(isset($this->data[$field])) return $this->data[$field];
        return null;
    }
    public function offsetSet($offset,$value) {
        if ($offset == '') {
            $this->data[] = $value;
        }else {
            $this->data[$offset] = $value;
        }
    }

    public function offsetExists($offset) {
        return isset($this->data[$offset]);
    }

    public function offsetUnset($offset) {
        unset($this->data[$offset]);
    }

    public function offsetGet($offset) {
        return isset($this->data[$offset]) ? $this->data[$offset] : null;
    }

    public function rewind() {
        return reset($this->data);
    }

    public function current() {
        return current($this->data);
    }

    public function key() {
        return key($this->data);
    }

    public function next() {
        return next($this->data);
    }

    public function valid() {
        return key($this->data) !== null;
    }    

    public function count() {
        return count($this->data);
    }
}
class RESPONSE_body_data extends RESPONSE_body_trait
{
    public function __construct($data) {
        $this->data = (string)$data;
    }
    public function __toString() {
        return $this->data;
    }
    public function toString($type){
        if($type =='JSON'){
            return RESPONSE_helper::json_encode($this->data);
        }
        return $this->__toString();
    }
    public function __destruct() {
        unset($this->data);
    }
    public function md5(){
        return md5($this->data);
    }
    function append($body){
        $this->data .= $body;
    }
}

class RESPONSE_body_file extends RESPONSE_body_data
{
    protected $filepath=null;
    public function __construct($filepath) {
        $this->filepath = (string)$filepath;
    }
    public function __toString() {
        if(NULL === $this->data){
            if(NULL === $this->filepath) return '';
            if(file_exists($this->filepath))
                $this->data = file_get_contents($this->filepath);
            else
                $this->data = '';
        }
        return $this->data;
    }
    public function toString($type){
        return $this->__toString();
    }
    public function getLength($type){
        if(NULL === $this->data){
            if(NULL === $this->filepath) return 0;
            return filesize($this->filepath);
        }
        return strlen($this->data);
    }
    public function md5(){
        return md5_file($this->filepath);
    }
}
class RESPONSE_header implements \ArrayAccess, \IteratorAggregate, \Countable{
    protected $items = array();
    protected $filename=null;
    protected $contentDisposition = null;
    protected $cacheControl = null;
    protected $status = null;
    //SPL
    public function getIterator() {
        return new \ArrayIterator($this->items);
    }
    
    public function offsetSet($offset,$value) {
        if ($offset == '') {
            $i = strpos($value,':');
            if($i){
                $this->add(substr($value,0,$i),substr($value,$i+1)); 
            }
        }else {
            $this->items[$offset] = $value;
        }
        
    }
    public function offsetExists($offset) {
        return isset($this->items[$offset]);
    }
    public function offsetUnset($offset) {
        unset($this->items[$offset]);
    }

    public function offsetGet($offset) {
        return isset($this->items[$offset]) ? $this->items[$offset] : null;
    }
    public function count() {
        return count($this->items);
    }
    
    //--STATUS
    
    public function getStatus()
    {
        if(NULL == $this->status){
             if(isset($this->items[0])){
                if($this->items[0] instanceof RESPONSE_header_status){
                    $this->status = $this->items[0];
                }else{
                    $this->status = new RESPONSE_header_status();
                    array_unshift($this->items,$this->status);
                }
             }else{
                $this->status = new RESPONSE_header_status();
                $this->items[0] = $this->status;
             }
        }
        return $this->status;
    }
    
    //--CACHE CONTROL
    public function setCacheControl($value){
        $this->getCacheControl()->set($value);
        return $this;
    }
    public function getCacheControl(){
        if($this->cacheControl === null){
            $this->cacheControl = new RESPONSE_header_cachecontrol;
            $this->items[] = $this->cacheControl;
        }
        return $this->cacheControl;
    }
    public function noCache() {
        if(func_num_args()){
            $this->getCacheControl()->set(__FUNCTION__,func_get_arg(0));
            return $this;
        }else{
            $this->getCacheControl()->get(__FUNCTION__);
        }
    }
    public function noStore() {
        if(func_num_args()){
            $this->getCacheControl()->set(__FUNCTION__,func_get_arg(0));
            return $this;
        }else{
            $this->getCacheControl()->get(__FUNCTION__);
        }
    }
    
    public function sMaxAge(){
        if(func_num_args()){
            $this->getCacheControl()->set(__FUNCTION__,func_get_arg(0));
            return $this;
        }else{
            $this->getCacheControl()->get(__FUNCTION__);
        }
    }
    
    public function maxAge(){
        if(func_num_args()){
            $this->getCacheControl()->set(__FUNCTION__,func_get_arg(0));
            return $this;
        }else{
            $this->getCacheControl()->get(__FUNCTION__);
        }
    }
    public function mustRevalidate(){
        if(func_num_args()){
            $this->getCacheControl()->set(__FUNCTION__,func_get_arg(0));
            return $this;
        }else{
            $this->getCacheControl()->get(__FUNCTION__);
        }
    }
    public function proxyRevalidate(){
        if(func_num_args()){
            $this->getCacheControl()->set(__FUNCTION__,func_get_arg(0));
            return $this;
        }else{
            $this->getCacheControl()->get(__FUNCTION__);
        }
    }
    
    
    
    /*
    //TODO:
    public function cookies()
    {
        
    }
    	
    */
    
    public function append($name, $value, $replace = true)
    {
        $this->add($name, $value, $replace);
        return $this;
    }
    public function header($name, $value, $replace = true)
    {
        $this->add($name, $value, $replace);
        return $this;
    }
    public function clear()
    {
        $this->filename ='';
        $this->items = array();
        $this->contentDisposition = null;
    }
    public function get($name)
    {
        $uname = strtoupper($name);
        $a =array();
        foreach($this->items as $oh){
            if($oh->getNameUpper() == $uname) $a[] = $oh;
        }
        $c = count($a);
        if($c==0) return false;
        if($c==1) return $a[0];
        return $a;
    }
    public function seek($name,$default='')
    {
        $uname = strtoupper($name);
        foreach($this->items as $oh){
            if($oh->getNameUpper() == $uname) return $oh;
        }
        return $this->add($name, $default,false);
    }
    public function add($name, $value, $replace = true)
	{
        $uname = strtoupper($name);
        if($uname=='STATUS'){
            $this->getStatus()->code = (int)$value;
            $i = strpos($value,' ');
            if($i){
                $this->getStatus()->message = substr($value,$i+1);
            }
            return $this->getStatus();
        }
        if($uname=='CACHE-CONTROL'){
            
            if(NULL === $this->cacheControl){
                $h = new RESPONSE_header_cachecontrol($value, $replace);
                $this->cacheControl = $h;
            }elseif($replace){
                $h = $this->cacheControl; 
            }else{
                $h = new RESPONSE_header_cachecontrol($value, $replace);
                $this->cacheControl = $h;
            }
        }else{
            $h = new RESPONSE_header_item($name, $value, $replace);
        }
        
        if($uname=='CONTENT-DISPOSITION'){
            if(NULL === $this->contentDisposition)
                $this->contentDisposition = $h;
        }
        
        
        $i = count($this->items);
		if ($replace)
		{
            $c = $i; 
            for($i=0; $i<$c;$i++){
                $oh = $this->items[$i];
                if($oh->getNameUpper() == $uname) break;
            }
		}
        $this->items[$i] = $h;
		return $h;
	}
    public function __toString() {
        $a =array();
        foreach($this->items as $h)
            $a[] = (string)$h;
        $a =array_filter($a);
        return implode("\n",$a);
    }
    public function __set($name, $value) {
        $name = ucfirst($name);
        $name =str_replace('_','-',$name);
        $this->add($name,$value,true);
    }

    public function send()
    {
        foreach($this->items as $h){
            $s = (string)$h;
            if($s) header($s,$h->replace);
        }
    }
    
    function CORS($value=null){
        $p = 'Access-Control-Allow-Origin';
        if(func_num_args()==0){
            return $this->get($p);
        }
        $this->add($p,$value,true);
        return $this;
    }
    function CORS_ALLOW_ALL(){
        $this->add('Access-Control-Allow-Origin','*',true);
        return $this;
    }
    function CSP($value=null){
        $p = 'Content-Security-Policy';
        if(func_num_args()==0){
            return $this->get($p);
        }
        $this->add($p,$value,true);
        return $this;
    }
    function CSP_Report($value=null){
        $p = 'Content-Security-Policy-Report-Only';
        if(func_num_args()==0){
            return $this->get($p);
        }
        $this->add($p,$value,true);
        return $this;
    }
    
    public function getContentDisposition(){
        if(NULL === $this->contentDisposition){
            return null;
        }
        return $this->contentDisposition->getValue();
    }
    public function getContentType(){
        return $this->get('content-type');
    }
    public function getCharset(){
        $h = $this->get('content-type');
        if($h !== false){
            if(is_array($h)) $h = $h[0];
            return $h->getParam('charset');
        }
        return '';
    }
    public function setCanonical($url){
        $h = $this->add('Link',$url,false);
        $h->addParam("rel=\"canonical\"");
        return $this;
    }
    public function setCharset($charset = 'UTF8'){
        $h = $this->get('content-type');
        if($h !== false){
            $p = $charset?"charset=$charset":'';
            if(is_array($h)){
                foreach($h as $it) $h->setParam($p); 
            }else{
                $h->setParam($p);
            }
        }
        return $this;
    }
    public function setContentDisposition($type){
        $type = strtolower($type);
        if(in_array($type,array('inline','attachment'))){
            if(NULL === $this->contentDisposition)
                $this->add('Content-disposition',$type);
            else
                $this->contentDisposition->setContent($type);
        }
        return $this;
    }
    
    public function setContentType($type = CONTENT_TYPE_PLAIN){
        $this->add('Content-type',$type);
        return $this;
    }
    public function setDate($date='now'){
        $d = date_create($date);
        if(!$d) $d = date_create();
        $this->add('Date',$d->format('r'));
        return $this;
    }
    
    public function setExpires($date){
        $temp = date_create($date);
        if($temp===false) return $this;
        $h = $this->add('Expires' ,$temp->format('D, d M Y H:i:s').' GMT', true);
        return $this; 
    }
    public function setFilename($filename='')
    {
        $this->filename = $filename;
        $fn = '';
        if($this->filename){
            $fn .= 'filename="'.$this->filename.'"';
            if(urlencode($this->filename) != $this->filename){
                $fn .= "; filename*=UTF-8''" . rawurlencode($this->filename);
            }
        }
        
        if(NULL === $this->contentDisposition){
            if($fn) $this->add('Content-disposition','inline')->setParam($fn);
        }else
            $this->contentDisposition->setParam($fn);
        
        return $this;
    }
    
}
class RESPONSE_compat extends RESPONSE_new {
    
    public function send() { return $this->Output('O'); }
    public function sendHeaders(){ $this->output('H'); }
    public function header() { return $this->getHeader(); }
    public function headers() { return $this->getHeader(); }
    public function body() { return $this->getBody(); }
    
    
    //ensures compatibiltiy with ELI_repsonse
    protected $_meta = null;
    protected $_cacheable = null;
    protected $_cached =null;
    protected $etag =null;
    protected $server =null;
    protected $preventDefault = false;
    
    function clear()
    {
        parent::reset();
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
        trigger_error('Deprecated. This funciton was never implemented');
    }
    
    function setFilename($filename='')
    {
        $this->getHeader()->setFilename($filename);
    }
    
    function getETag(){
        if(empty($this->etag)){
            $a = func_get_args();
            $a[] = $this->contentType;
            if($this->download){
                if(is_bool($this->download))
                    $a[] = md5($this->filebody);
                elseif(file_exists($this->download))
                    $a[] = md5_file($this->download);
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
        trigger_error('Deprecated');
        if($this->_meta === null){
            $this->_meta = new ELI_response_meta;
        }
        return $this->_meta; 
    }
    function setCanonical($url){
        $this->getHeader()->setCanonical($url);
        return $this;
    }
    function setHeader($property,$content='',$replace=true)
    {
        $this->getHeader()->add($property,$content,$replace);
        return $this;
    }
    
    
    function getServer(){
        if((null ===$this->server)){
            return 'ELI_REPONSE/1.1 (ELI/2)';
        }
        return $this->server;
    }
    function hasStatus(){
        return $this->status != null;
    }
    public function getHeaderList(){
        return $this->getHeader();
    }
    
    function setLastModified($date='now'){
        $d = date_create($date,new \DateTimeZone("GMT"));
        if(!$d) $d = date_create('now',new \DateTimeZone("GMT"));
        $this->getHeader()->add('Last-Modified',$d->format('r'));
    }
    
}
class RESPONSE extends RESPONSE_compat {
    
}