<?php

/**
 * ELI_http
 * Originally OOCurl v 0.3
 *
 * Provides an Object-Oriented interface to the PHP cURL
 * functions and clean up some of the curl_setopt() calls.
 *
 * @package ELI_http
 * @author James Socol <me@jamessocol.com>
 * @version 0.3.0
 * @copyright Copyright (c) 2008-2013, James Socol
 * @license See LICENSE
 */

/**
 * Curl connection object
 *
 * Provides an Object-Oriented interface to the PHP cURL
 * functions and a clean way to replace curl_setopt().
 *
 * Instead of requiring a setopt() function and the CURLOPT_*
 * constants, which are cumbersome and ugly at best, this object
 * implements curl_setopt() through overloaded getter and setter
 * methods.
 *
 * For example, if you wanted to include the headers in the output,
 * the old way would be
 *
 * <code>
 * curl_setopt($ch, CURLOPT_HEADER, true);
 * </code>
 *
 * But with this object, it's simply
 *
 * <code>
 * $ch->header = true;
 * </code>
 *
 * <b>NB:</b> Since, in my experience, the vast majority
 * of cURL scripts set CURLOPT_RETURNTRANSFER to true, the {@link Curl}
 * class sets it by default. If you do not want CURLOPT_RETURNTRANSFER,
 * you'll need to do this:
 *
 * <code>
 * $c = new ELI_http;
 * $c->returntransfer = false;
 * </code>
 *
 * @package ELI_http
 * @author James Socol <me@jamessocol.com>
 * @version 0.3.0
 */
class ELI_http
{
	/**
	 * Store the curl_init() resource.
	 * @var resource
	 */
	protected $ch = NULL;

	/**
	 * Store the CURLOPT_* values.
	 *
	 * Do not access directly. Access is through {@link __get()}
	 * and {@link __set()} magic methods.
	 *
	 * @var array
	 */
	protected $options = array();

	/**
	 * Store the custom headers resource.
	 * @var resource
	 */
	protected $headers = array();
	/**
	 * Flag the Curl object as linked to a {@link ELI_HttpParallel}
	 * object.
	 *
	 * @var bool
	 */
	protected $multi = false;

	/**
	 * Store the response. Used with {@link fetch()} and
	 * {@link fetch_json()}.
	 *
	 * @var string
	 */
	protected $response;
	protected $responseHeaders;

	/**
	 * The version of the ELI_http library.
	 * @var string
	 */
	const VERSION = '0.3';

	/**
	 * Create the new {@link Curl} object, with the
	 * optional URL parameter.
	 *
	 * @param string $url The URL to open (optional)
	 * @return Curl A new ELI_http object.
	 * @throws ErrorException
	 */
	public function __construct ( $url = NULL )
	{
		// Make sure the cURL extension is loaded
		if ( !extension_loaded('curl') )
			throw new ErrorException("cURL library is not loaded. Please recompile PHP with the cURL library.");

		// Create the cURL resource
		$this->ch = curl_init();

		// Set some default options
		$this->url = $url;
		$this->returntransfer = true;
		// Applications can override this User Agent value
		$this->useragent = 'ELI_http (cURL) '.self::VERSION;

		// Return $this for chaining
		return $this;
	}

	/**
	 * When destroying the object, be sure to free resources.
	 */
	public function __destruct()
	{
		$this->close();
	}

	/**
	 * If the session was closed with {@link Curl::close()}, it can be reopened.
	 *
	 * This does not re-execute {@link Curl::__construct()}, but will reset all
	 * the values in {@link $options}.
	 *
	 * @param string $url The URL to open (optional)
	 * @return bool|Curl
	 */
	public function init ( $url = NULL )
	{
		// If it's still init'ed, return false.
		if ( $this->ch ) return false;

		// init a new ELI_http session
		$this->ch = curl_init();

		// finally if there's a new URL, set that
		if ( !empty($url) ) $this->url = $url;

		// return $this for chaining
		return $this;
	}
    public function reset (){
        if(function_exists('curl_reset'))
            return curl_reset($this->ch);
        else{
            $url = $this->url;
            $this->close();
            $this->init($url);
        }
    }
	/**
	 * Execute the cURL transfer.
	 *
	 * @return mixed
	 */
	public function exec ()
	{
		return curl_exec($this->ch);
	}
    
    public function header($hString,$replace=false){
        if(!$hString) return false;
        $hString = ltrim($hString,': ');
        if(!$hString) return false;
        
        $x =explode(':',$hString,2);
        $hn = '';
        $del = false;
        if(count($hString)==1){
            if(func_num_args()==1){
                foreach($this->headers as $k=>$v){
                    if(strtolower(substr($v,0,$l))==$hn){
                        return $v;
                    }
                }
                return null;
            }else{
                $hn = strtolower(trim($hString));
                $del =true;
            }
        }else{
            $hn = strtolower(trim($x[0]));
            if(trim($x[1])==''){
                $del = $replace?true:false;
            }
        }
        if($replace){
            $l =strlen($hn);
            foreach($this->headers as $k=>$v){
                if(strtolower(substr($v,0,$l))==$hn){
                    $this->headers[$k] = $hString;
                }
            }
        }else{
            if(!$del)
                $this->headers[] = $hString;
        }
        return $this;
    }

	/**
	 * Close the cURL session and free the resource.
	 */
	public function close ()
	{
		if ( !empty($this->ch) && is_resource($this->ch) )
			curl_close($this->ch);
	}

	/**
	 * Return an error string from the last execute (if any).
	 *
	 * @return string
	 */
	public function error()
	{
		return curl_error($this->ch);
	}

	/**
	 * Return the error number from the last execute (if any).
	 *
	 * @return integer
	 */
	public function errno()
	{
		return curl_errno($this->ch);
	}

	/**
	 * Get cURL version information (and adds ELI_http version info)
	 *
	 * @return array
	 */
 	public function version ()
 	{
 		$version = curl_version();

 		$version['eli_http_version'] = self::VERSION;
 		$version['eli_httpparallel_version'] = ELI_HttpParallel::VERSION;

 		return $version;
 	}

	/**
	 * Get information about this transfer.
	 *
	 * Accepts any of the following as a parameter:
	 *  - Nothing, and returns an array of all info values
	 *  - A CURLINFO_* constant, and returns a string
	 *  - A string of the second half of a CURLINFO_* constant,
	 *     for example, the string 'effective_url' is equivalent
	 *     to the CURLINFO_EFFECTIVE_URL constant. Not case
	 *     sensitive.
	 *
	 * @param mixed $opt A string or constant (optional).
	 * @return mixed An array or string.
	 */
	public function info ( $opt = false )
	{
		if (false === $opt) {
			$result = curl_getinfo($this->ch);
            if($result===false) return false;
            return (object)$result;
		}

		if ( is_int($opt) || ctype_digit($opt) ) {
			return curl_getinfo($this->ch,$opt);
		}

		if (constant('CURLINFO_'.strtoupper($opt))) {
			return curl_getinfo($this->ch,constant('CURLINFO_'.strtoupper($opt)));
		}
	}

	/**
	 * Magic property setter.
	 *
	 * A sneaky way to access curl_setopt(). If the
	 * constant CURLOPT_$opt exists, then we try to set
	 * the option using curl_setopt() and return its
	 * success. If it doesn't exist, just return false.
	 *
	 * Also stores the variable in {@link $options} so
	 * its value can be retrieved with {@link __get()}.
	 *
	 * @param string $opt The second half of the CURLOPT_* constant, not case sensitive
	 * @param mixed $value
	 * @return void
	 */
	public function __set ( $opt, $value )
	{
	    if(strtoupper($opt) =='REFERRER') $opt ='REFERER';
		$const = 'CURLOPT_'.strtoupper($opt);
		if ( defined($const) ) {
            $this->options[constant($const)] = $value;
		}
	}

	/**
	 * Magic property getter.
	 *
	 * When options are set with {@link __set()}, they
	 * are also stored in {@link $options} so that we
	 * can always find out what the options are now.
	 *
	 * The default cURL functions lack this ability.
	 *
	 * @param string $opt The second half of the CURLOPT_* constant, not case sensitive
	 * @return mixed The set value of CURLOPT_<var>$opt</var>, or NULL if it hasn't been set (ie: is still default).
	 */
	public function __get ( $opt )
	{
	    $opt = strtoupper($opt);
        if($opt =='RESPONSE' || $opt=='BODY'){
            return $this->response;
        }
        if($opt=='RESPONSEHEADER'){
            return $this->responseHeaders;
        }
        if( $opt=='RESPONSEHEADERS'){
            return $this->getResponseHeaders();
        }
	    if($opt =='REFERRER') $opt ='REFERER';
        
        $const = 'CURLOPT_'.$opt;
        if(isset($this->options[constant($const)]))
            return $this->options[constant($const)];
        else
            return null;
	}

	/**
	 * Magic property isset()
	 *
	 * Can tell if a CURLOPT_* value was set by using
	 * <code>
	 * isset($curl->*)
	 * </code>
	 *
	 * The default cURL functions lack this ability.
	 *
	 * @param string $opt The second half of the CURLOPT_* constant, not case sensitive
	 * @return bool
	 */
	public function __isset ( $opt )
	{
	    $const = 'CURLOPT_'.strtoupper($opt);
		return isset($this->options[constant($const)]);
	}

	/**
	 * Magic property unset()
	 *
	 * Unfortunately, there is no way, short of writing an
	 * extremely long, but mostly NULL-filled array, to
	 * implement a decent version of
	 * <code>
	 * unset($curl->option);
	 * </code>
	 *
	 * @todo Consider implementing an array of all the CURLOPT_*
	 *       constants and their default values.
	 * @param string $opt The second half of the CURLOPT_* constant, not case sensitive
	 * @return void
	 */
	public function __unset ( $opt )
	{
	   $const = 'CURLOPT_'.strtoupper($opt);
		unset($this->options[constant($const)]);
	}
    

	/**
	 * Grants access to {@link Curl::$ch $ch} to a {@link ELI_HttpParallel} object.
	 *
	 * @param ELI_HttpParallel $mh The ELI_HttpParallel object that needs {@link Curl::$ch $ch}.
	 */
	public function grant ( ELI_HttpParallel $mh )
	{
		$mh->accept($this->ch);
		$this->multi = true;
	}

	/**
	 * Removes access to {@link Curl::$ch $ch} from a {@link ELI_HttpParallel} object.
	 *
	 * @param ELI_HttpParallel $mh The ELI_HttpParallel object that no longer needs {@link Curl::$ch $ch}.
	 */
	public function revoke ( ELI_HttpParallel $mh )
	{
		$mh->release($this->ch);
		$this->multi = false;
	}
    
    public function __call($name, $arguments) {
        $name = strtoupper($name);
        $l3 = substr($name,0,3);
        $l4 = substr($name,0,4);
        if(substr($name,3,1)=='_'){
            $r =substr($name,4);
        }else{
            $r =substr($name,3);
        }
        if($r =='REFERRER') $r ='REFERER';
        $const = 'CURLOPT_'.$r;
        
        if($r && $l3=='SET'){
            $value = $arguments[0];
    		if ( defined($const) ) {
  				$this->options[constant($const)] = $value;
    		}
        }elseif($name=='GETINFO' || $name=='GET_INFO'){
            return $this->info();
        }elseif($r &&  $l3=='GET'){
            if(isset($this->options[constant($const)]))
                return $this->options[constant($const)];
        }
    }
    
    function setopt($opt, $value){
        if ( is_int($opt) || ctype_digit($opt) ) {
			$this->options[$opt] = $value;
            return;
		}
        $const = strtoupper($opt);
        if (! defined($const) ) $const = 'CURLOPT_'.strtoupper($opt);
        if (! defined($const) ) $const = 'CURL_'.strtoupper($opt);
        if (! defined($const) ) $const = 'CURL'.strtoupper($opt);
            
        if ( defined($const) ) {
				$this->options[constant($const)] = $value;
		}
    }
    /**
	 * Set username/pass for basic http auth
	 * @param string user
	 * @param string pass
	 * @access public
	 */
	function set_credentials($username,$password)
	{
        $this->options[CURLOPT_USERPWD] = "$username:$password";
	}
	function get_credentials()
	{
		return $this->__get('USERPWD');
	}
    function set_useragent($agent){
        $ucaseagent =strtoupper($agent);
        $setagent = '';
        switch($ucaseagent){
        case 'OPERA': $setagent = 'Opera/9.25 (Windows NT 6.0; U; en)'; break;
        case 'NETSCAPE': $setagent = 'Mozilla/4.8 [en] (Windows NT 6.0; U)'; break;
        case 'IE7': $setagent = 'Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 6.0)'; break;
        case 'GOOGLEBOT': $setagent = 'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)'; break;
        case 'FIREFOX3':
        case 'FIREFOX2':
        case 'FIREFOX1': 
        case 'FIREFOX':
            $setagent = 'Mozilla/5.0 (Windows; U; Windows NT 5.1; pl; rv:1.9) Gecko/2008052906 Firefox/3.0'; break;
        }
        if($setagent)
            $this->useraget = $setagent;
        else
            $this->useraget = $agent;
        
    }
    function followRedirects($value)
	{
        $this->options[CURLOPT_FOLLOWLOCATION] = $value;
        return $this;
	}
    
    private function _setopt($options=array()){
        
		foreach ( $this->options as $const => $value ) {
			curl_setopt($this->ch, $const, $value);
		}
		foreach ( $options as $const => $value ) {
		      if(is_string($const)){
		         if(defined($const)) curl_setopt($this->ch, constant($const), $value);
		      }else
			     curl_setopt($this->ch, $const, $value);
		}

        //curl_setopt_array($this->ch, ($options + $this->options));
        curl_setopt($this->ch,CURLOPT_HEADER,true);
        if(count($this->headers)) curl_setopt($this->ch, CURLOPT_HTTPHEADER, $this->headers);
    }
    private function _setResponse($response){
        if($response===false || $response===null){
            $this->response ='';
            $this->responseHeaders ='';
            return;
        }
        $header_size = curl_getinfo($this->ch, CURLINFO_HEADER_SIZE);
        if($header_size){
            $this->responseHeaders = substr($response, 0, $header_size);
            $this->response = substr($response, $header_size);
        }else{
            if(strpos($response,"\r\n\r\n")){
                list($this->responseHeaders, $this->response) = explode("\r\n\r\n", $response, 2);
            }Else{
                $this->responseHeaders = '';
                $this->response = $response;
            }
        }
    }
    function put($inFile)
	{
	    if(!is_resource($inFile)){
	       $fp = fopen($inFile,'r');
           $s = filesize($inFile);
	    }else{
	       $fp = $inFile;
           $fstat = fstat($fp);
           $s = $fstat['size'];
	    }
        
        curl_setopt($this->ch, CURLOPT_INFILE,$inFile);
        curl_setopt($this->ch, CURLOPT_INFILESIZE,$s);
        curl_setopt($this->ch, CURLOPT_PUT,true);
		$this->response = $this->exec();
        if(!is_resource($inFile)){
            fclose($fp);
        }
        if($this->errno())
            return false;
        else
		  return $this->response;
	}
    
    

	/**
	 * If the Curl object was added to a {@link ELI_HttpParallel}
	 * object, then you can use this function to get the
	 * returned data (whatever that is). Otherwise it's similar
	 * to {@link exec()} except it saves the output, instead of
	 * running the request repeatedly.

	 * fetch data from target URL	 
	 * return data returned from url or false if error occured 
	 * @param int timeout in sec for complete curl operation (default 5)
	 * @see $multi
	 * @return string data
	 * @access public
	 */
	function get($timeout=10)
	{
        if ( $this->multi ) {
            return curl_multi_getcontent($this->ch);
        } else {
            if ( $this->response ) {
                return $this->response;
            } else {
                $o =array();
                if(func_num_args()){
                    $o[CURLOPT_TIMEOUT] = $timeout;
                }
                $this->_setopt($o);
				curl_setopt($this->ch, CURLOPT_HTTPGET,true);
                $this->_setResponse($this->exec());
                return $this->response;
            }
        }
            
	}

	/**
	 * Fetch a JSON encoded value and return a JSON
	 * object. Requires the PHP JSON functions. Pass TRUE
	 * to return an associative array instead of an object.
	 *
	 * @param bool array optional. Return an array instead of an object.
	 * @return mixed an array or object (possibly null).
	 */
	public function get_json ( $array = false )
	{
		return json_decode($this->get(), $array);
	}
	function head($timeout=10)
	{
        $o =array();
        if(func_num_args()){
            $o[CURLOPT_TIMEOUT] = $timeout;
        }
        $this->_setopt($o);
        curl_setopt($this->ch, CURLOPT_HTTPGET,true);
        curl_setopt($this->ch, CURLOPT_CUSTOMREQUEST,'HEAD');
        curl_setopt($this->ch, CURLOPT_NOBODY, true);
        curl_setopt($this->ch, CURLOPT_HEADER, true); 
        curl_setopt($this->ch, CURLOPT_FRESH_CONNECT, true);
        $this->_setResponse($this->exec());
        //$result = $this->exec();
        if($this->errno())
        return false;
        else{
        return $this->responseHeaders;
}
	}
    /**
	 * Fetch data from target URL
	 * and store it directly to file	 	 
	 * @param string url	 
	 * @param resource value stream resource(ie. fopen)
	 * @param string ip address to bind (default null)
	 * @param int timeout in sec for complete curl operation (default 5)
	 * @return boolean true on success false othervise
	 * @access public
	 */
	function download( $fp)
	{
	   if ( $this->response ) {
	       if(is_resource($fp)){
    			return fwrite($fp,$this->response);
            }else{
                return file_put_contents($fp,$this->response);
            }
		} else {
            $this->_setopt();
            $this->_setopt();
		      curl_setopt($this->ch, CURLOPT_HTTPGET,true);
              // store data into file rather than displaying it
		      curl_setopt($this->ch, CURLOPT_FILE, $fp);
			 $this->_setResponse($this->exec());
			if($this->errno())
    		{
    			return false;
    		}
    		else
    		{
    			return true;
    		}
		}
		
	}
    /**
	 * Send post data to target URL	 
	 * return data returned from url or false if error occured
	 * @param mixed post data (assoc array ie. $foo['post_var_name'] = $value or as string like var=val1&var2=val2)
	 * @param string ip address to bind (default null)
	 * @return string data
	 * @access public
	 */
	function post($postdata)
	{
	   $this->_setopt();
		//set method to post
		curl_setopt($this->ch, CURLOPT_POST, true);

		//generate post string
		$post_array = array();
		if(is_array($postdata))
		{		
			foreach($postdata as $key=>$value)
			{
				$post_array[] = urlencode($key) . "=" . urlencode($value);
			}

			$post_string = implode("&",$post_array);
		}
		else 
		{
			$post_string = $postdata;
		}

		// set post string
		curl_setopt($this->ch, CURLOPT_POSTFIELDS, $post_string);


		//and finally send curl request
		$this->_setResponse($this->exec());
			if($this->errno())
		{
			return false;
		}
		else
		{
			return $this->response;
		}
	}
    
	/**
	 * Send multipart post data to the target URL	 
	 * return data returned from url or false if error occured
	 * (contribution by vule nikolic, vule@dinke.net)
	 * @param string url
	 * @param array assoc post data array ie. $foo['post_var_name'] = $value
	 * @param array assoc $file_field_array, contains file_field name = value - path pairs
	 * @param string ip address to bind (default null)
	 * @param int timeout in sec for complete curl operation (default 30 sec)
	 * @return string data
	 * @access public
	 */
	function postMultipart($postdata, $file_field_array=array())
	{
	   $this->_setopt();
       
		//set method to post
		curl_setopt($this->ch, CURLOPT_POST, true);

		// disable Expect header
		// hack to make it working
		$headers = array("Expect: ");
		curl_setopt($this->ch, CURLOPT_HTTPHEADER, $headers);

		// initialize result post array
		$result_post = array();

		//generate post string
		$post_array = array();
		$post_string_array = array();
		if(!is_array($postdata))
		{
			return false;
		}

		foreach($postdata as $key=>$value)
		{
			$post_array[$key] = $value;
			$post_string_array[] = urlencode($key)."=".urlencode($value);
		}

		$post_string = implode("&",$post_string_array);


		// set post string
		//curl_setopt($this->ch, CURLOPT_POSTFIELDS, $post_string);


		// set multipart form data - file array field-value pairs
		if(!empty($file_field_array))
		{
			foreach($file_field_array as $var_name => $var_value)
			{
				if(strpos(PHP_OS, "WIN") !== false) $var_value = str_replace("/", "\\", $var_value); // win hack
				$file_field_array[$var_name] = "@".$var_value;
			}
		}

		// set post data
		$result_post = array_merge($post_array, $file_field_array);
		curl_setopt($this->ch, CURLOPT_POSTFIELDS, $result_post);


		//and finally send curl request
		$this->_setResponse($this->exec());
			if($this->errno())
		{
			return false;
		}
		else
		{
			return $this->response;
		}
	}

	/**
	 * Set file location where cookie data will be stored and send on each new request
	 * @param string absolute path to cookie file (must be in writable dir)
	 * @access public
	 */
	function store_cookies($cookie_file)
	{
        $this->COOKIEJAR = $cookie_file;
        $this->COOKIEFILE = $cookie_file;
        return $this;
	}
    /**
	 * Get http response code	 
	 * @access public
	 * @return int
	 */
	function status()
	{
		return curl_getinfo($this->ch, CURLINFO_HTTP_CODE);
	}
    
    function getResponse(){
        return $this->response;
    }
    function getResponseHeader($header=null){
        if(func_get_args()){
            $a = array_change_key_case($this->getResponseHeaders(),CASE_LOWER);
            $header =strtolower(rtrim($header,' :'));
            if(isset($a[$header])) return $a[$header];
            return null;
        }else{
            return $this->responseHeaders;
        }
    }
    function getResponseHeaders()
    {
        $raw_headers = $this->responseHeaders;
        $headers = array();
        $key = ''; // [+]

        foreach(explode("\n", $raw_headers) as $i => $h)
        {
            $h = explode(':', $h, 2);

            if (isset($h[1]))
            {
                if (!isset($headers[$h[0]]))
                    $headers[$h[0]] = trim($h[1]);
                elseif (is_array($headers[$h[0]]))
                {
                    // $tmp = array_merge($headers[$h[0]], array(trim($h[1]))); // [-]
                    // $headers[$h[0]] = $tmp; // [-]
                    $headers[$h[0]] = array_merge($headers[$h[0]], array(trim($h[1]))); // [+]
                }
                else
                {
                    // $tmp = array_merge(array($headers[$h[0]]), array(trim($h[1]))); // [-]
                    // $headers[$h[0]] = $tmp; // [-]
                    $headers[$h[0]] = array_merge(array($headers[$h[0]]), array(trim($h[1]))); // [+]
                }

                $key = $h[0]; // [+]
            }
            else // [+]
            { // [+]
                if (substr($h[0], 0, 1) == "\t") // [+]
                    $headers[$key] .= "\r\n\t".trim($h[0]); // [+]
                elseif (!$key) // [+]
                    $headers[0] = trim($h[0]);trim($h[0]); // [+]
            } // [+]
        }

        return $headers;
    }

}

/**
 * Implements parallel-processing for cURL requests.
 *
 * The PHP cURL library allows two or more requests to run in
 * parallel (at the same time). If you have multiple requests
 * that may have high latency but can then be processed quickly
 * in series (one after the other), then running them at the
 * same time may save time, overall.
 *
 * You must create individual {@link Curl} objects first, add them to
 * the ELI_HttpParallel object, execute the ELI_HttpParallel object,
 * then get the data from the individual {@link Curl} objects. (Yes,
 * it's annoying, but it's limited by the PHP cURL library.)
 *
 * For example:
 *
 * <code>
 * $a = new ELI_http("http://www.yahoo.com/");
 * $b = new ELI_http("http://www.microsoft.com/");
 *
 * $m = new ELI_HttpParallel($a, $b);
 *
 * $m->exec(); // Now we play the waiting game.
 *
 * printf("Yahoo is %n characters.\n", strlen($a->fetch()));
 * printf("Microsoft is %n characters.\n", strlen($a->fetch()));
 * </code>
 *
 * You can add any number of {@link Curl} objects to the
 * ELI_HttpParallel object's constructor (including 0), or you
 * can add with the {@link add()} method:
 *
 * <code>
 * $m = new ELI_HttpParallel;
 *
 * $a = new ELI_http("http://www.yahoo.com/");
 * $b = new ELI_http("http://www.microsoft.com/");
 *
 * $m->add($a);
 * $m->add($b);
 *
 * $m->exec(); // Now we play the waiting game.
 *
 * printf("Yahoo is %n characters.\n", strlen($a->fetch()));
 * printf("Microsoft is %n characters.\n", strlen($a->fetch()));
 * </code>
 *
 * @package ELI_http
 * @author James Socol <me@jamessocol.com>
 * @version 0.3.0
 * @since 0.1.2
 */
class ELI_HttpParallel
{
	/**
	 * Store the cURL master resource.
	 * @var resource
	 */
	protected $mh;

	/**
	 * Store the resource handles that were
	 * added to the session.
	 * @var array
	 */
	protected $ch = array();

	/**
	 * Store the version number of this class.
	 */
	const VERSION = '0.3.0';

	/**
	 * Initialize the multisession handler.
	 *
	 * @uses add()
	 * @param Curl $curl,... {@link Curl} objects to add to the Parallelizer.
	 * @return ELI_HttpParallel
	 */
	public function __construct ()
	{
		$this->mh = curl_multi_init();

		foreach ( func_get_args() as $ch ) {
			$this->add($ch);
		}

		return $this;
	}

	/**
	 * On destruction, frees resources.
	 */
	public function __destruct ()
	{
		$this->close();
	}

	/**
	 * Close the current session and free resources.
	 */
	public function close ()
	{
		foreach ( $this->ch as $ch ) {
			curl_multi_remove_handle($this->mh, $ch);
		}
		curl_multi_close($this->mh);
	}

	/**
	 * Add a {@link Curl} object to the Parallelizer.
	 *
	 * Will throw a catchable fatal error if passed a non-Curl object.
	 *
	 * @uses Curl::grant()
	 * @uses ELI_HttpParallel::accept()
	 * @param Curl $ch Curl object.
	 */
	public function add ( Curl $ch )
	{
		// get the protected resource
		$ch->grant($this);
	}

	/**
	 * Remove a {@link Curl} object from the Parallelizer.
	 *
	 * @param Curl $ch Curl object.
	 * @uses Curl::revoke()
	 * @uses ELI_HttpParallel::release()
	 */
	public function remove ( Curl $ch )
	{
		$ch->revoke($this);
	}

	/**
	 * Execute the parallel cURL requests.
	 */
	public function exec ()
	{
		do {
			curl_multi_exec($this->mh, $running);
		} while ($running > 0);
	}

	/**
	 * Accept a resource handle from a {@link Curl} object and
	 * add it to the master.
	 *
	 * @param resource $ch A resource returned by curl_init().
	 */
	public function accept ( $ch )
	{
		$this->ch[] = $ch;
		curl_multi_add_handle($this->mh, $ch);
	}

	/**
	 * Accept a resource handle from a {@link Curl} object and
	 * remove it from the master.
	 *
	 * @param resource $ch A resource returned by curl_init().
	 */
	public function release ( $ch )
	{
		if ( false !== $key = array_search($this->ch, $ch) ) {
			unset($this->ch[$key]);
			curl_multi_remove_handle($this->mh, $ch);
		}
	}
}
if (!function_exists('curl_setopt_array')) {
   function curl_setopt_array(&$ch, $curl_options)
   {
       foreach ($curl_options as $option => $value) {
           if (!curl_setopt($ch, $option, $value)) {
               return false;
           } 
       }
       return true;
   }
}
