<?php
class ELI_URI{
    private $source;
    private $parts =array();
    private $_terms=array('scheme','user','pass','host','port','path','query','fragment');
    protected $_extensions = array(
		'aero', 'arpa', 'asia', 'coop', 'info', 'jobs', 'mobi',
		'museum', 'name', 'travel',
	);
    public function __construct(){
        if(func_num_args()){
            $this->parse((string)func_get_arg(0));
        }
    }
    public function __toString() {
        return $this->build();
    }
    public function source() {
        return $this->source;
    }
    public function __get($name) {
        $name = strtolower($name);
        if(method_exists($this,$name)){
            return $this->$name();
        }
        
        if(isset($this->parts[$name]) || array_key_exists($name,$this->parts))
            return $this->parts[$name];
        
        return '';
    }
    public function set($name, $value) {
        $name = strtolower($name);
        if(method_exists($this,$name)){
            return $this->$name($value);
        }
        if((null ===$value) || $value === '')
            unset($this->parts[$name]);
        else{
            $this->parts[$name] = $value;
        }
        return $this;
    }
    public function __set($name, $value) {
        $name = strtolower($name);
        if(method_exists($this,$name)){
            return $this->$name($value);
        }
        if((null ===$value) || $value === '')
            unset($this->parts[$name]);
        else{
            $this->parts[$name] = $value;
        }
        return $this;
    }
    public function normalize($name='') {
        if($name=='' || $name=='scheme'){
            if(isset($this->parts['scheme'])){
                $this->parts['scheme'] = strtolower($this->parts['scheme']);
                
            }
        }
        if($name=='' || $name=='host'){
            if(isset($this->parts['host'])){
                $this->parts['host'] = strtolower($this->parts['host']);
                
            }
        }
        if($name=='' || $name=='port'){
            if(isset($this->parts['port']) && isset($this->parts['scheme']) && $this->parts['port']== getservbyname($this->parts['scheme'],'tcp')){
                unset($this->parts['port']);
            }
        }
        // Normalize case of %XX percentage-encodings (RFC 3986, section 6.2.2.1)
        foreach (array('user','pass', 'host', 'path') as $part) {
            if(isset($this->parts[$part])){
                if($part=='path'){                    
                    //$this->parts[$part] = explode('/',preg_replace('/%[0-9a-f]{2}/ie', 'strtoupper("\0")',$this->path()));
                    $this->parts[$part] = preg_replace_callback('/%[0-9a-f]{2}/i',function($m) { return strtoupper($m[0]); }, $this->path());
                }else{
                    //$this->parts[$part] = preg_replace('/%[0-9a-f]{2}/ie', 'strtoupper("\0")',$this->parts[$part]);
                    $this->parts[$part] = preg_replace_callback('/%[0-9a-f]{2}/i',function($m) { return strtoupper($m[0]); }, $this->parts[$part]);
                }
            }
        }
        if($name=='' || $name=='path'){
            if(!isset($this->parts['path'])){
                $this->parts['path']=array();
            }
        }
        return $this;
        
    }
    public function __unset($name) {
        $this->__set($name,null);
    }
    public function __isset($name) {
        $name = strtolower($name);
        return isset($this->data[$name]);
    }
    public function has($name) {
        $name = strtolower($name);
        return !empty($this->data[$name]);
    }
    public function is($name,$value) {
        $name = strtolower($name);
        if($name=='path'){
            return trim($this->$name(),'/')==trim($value,'/');
        }elseif(in_array($name,array('scheme','host'))){
            return strtolower($value) == strtolower($this->$name());
        }
        return $this->$name()==$value;
    }
    
    public function __call($name, $arguments) {
        $name = strtolower($name);
        $n3 = substr($name,0,3);
        $r3 = substr($name,3);
        
        if($n3=='get'){
            if($r3=='password') $r3='pass';
            if($r3=='')
                return $this->__toString(); 
            if(method_exists($this,$r3)){
                return $this->$r3();
            }
            if( in_array($r3,$this->_terms))
                return $this->__get($r3);
            
        }
        if($n3=='set'){
            if($r3=='password') $r3='pass';
            if(method_exists($this,$r3)){
                return $this->$r3($arguments[0]);
            }
            if( in_array($r3,$this->_terms))
                return $this->__set($r3,$arguments[0]);
            
        }
        if($n3=='has'){
            if($r3=='password') $r3='pass';
            if($r3=='queryvariable') $r3='query';
            if($r3=='queryvariables') $r3='query';
            if( in_array($r3,$this->_terms))
                return $this->has($r3);
        }
        
        if($name=='password') $name='pass';
        if(in_array($name,$this->_terms)){
            if(count($arguments)==0){
                return $this->__get($name);
            }else{
                return $this->__set($name,$arguments[0]);
            }
        }
        if(substr($name,0,2)=='is'){
            $r3 = substr($name,2);
            if($r3=='password') $r3='pass';
            if(method_exists($this,$r3)){
                return $this->is($r3,$arguments[0]);
            }
            if(in_array($r3,$this->_terms))
                return $this->is($r3,$arguments[0]);
            
        }
        return $this;
    }
    /**
     *
     * @param string $varName the key to which a value must be returned
     * @return string the value of the query parameter is returned e.g
     * if url is path/name and @param $varName is path then "name" is returned
     * returns null if the requested var does not exist
     */
    public function QueryVariables() {
        if(func_num_args()==0){
            if(isset($this->parts['query'])){
                return $this->parts['query'];
            }else{
                return array();
            }
        }else{
            $v =func_get_arg(0);
            if(is_array($v))
                $this->parts['query']  = func_get_arg(0);
            elseif(empty($v))
                $this->parts['query']  = array();
            return $this;
        }
    }

    /**
     *
     * @param string $varName the name of the key you wish to set a value to
     * this can be an existing key, in which case the old value is overriden
     * @param string $value
     */
    public function QueryVariable($varName='', $value=null) {
        if(func_num_args()==0){
            if(!isset($this->parts['query'])){
                $this->parts['query']  = array();
            }
            $a = &$this->parts['query'];
            return $a;
        }elseif(func_num_args()==1){
            if(!isset($this->parts['query'][$varName])){
                return '';
            }else{
                return $this->parts['query'][$varName];
            }
        }else{
            if(!isset($this->parts['query'])){
                $this->parts['query']  = array();
            }
            if((null ===$value)){
                unset($this->parts['query'][$varName]);
            }else{
                $this->parts['query'][$varName] = $value;
            }
            return $this;
        }
    }
    /**
     *
     * 
     * @return 
     */
    public function segments() {
        if(func_num_args()==0){
            if(isset($this->parts['path'])){
                return $this->parts['path'];
            }else{
                return array();
            }
        }else{
            $v =func_get_arg(0);
            if(is_array($v))
                $this->parts['path']  = func_get_arg(0);
            elseif(empty($v))
                $this->parts['path']  = array();
            return $this;
        }
    }

    /**
     *
     * @param int $index the index of the segment part, NULL to add new segment
     * 
     * @param string $value
     */
    public function segment($index=null, $value=null) {
        if(func_num_args()==0){
            if(!isset($this->parts['path'])){
                $this->parts['path']  = array();
            }
            $a = &$this->parts['path'];
            return $a;
        }
        
        if(func_num_args()==1){
            if(!isset($this->parts['path'])){
                return '';
            }
            $index = (int)$index;
            $c = count($this->parts['path']);
            if($index < 0){
                if($c==0){
                    $index = 0;
                }elseif(abs($index)<=$c)
                    $index = $c+$index;
            }
            if(!isset($this->parts['path'][$index])){
                return '';
            }else{
                return $this->parts['path'][$index];
            }
        }else{
            if(!isset($this->parts['path'])){
                $this->parts['path']  = array();
            }
            $c = count($this->parts['path']);
            if((null ===$index)) 
                $index = $c;
            else 
                $index = (int)$index;
            
            if($index==-1 ) $index =($c)? $c-1:0;
            if($index < 0){
                if($c==0){
                    $index = 0;
                }elseif(abs($index)<=$c)
                    $index = $c+$index;
            }
            if($index >=0){
                if((null ===$value)){
                    unset($this->parts['path'][$index]);
                }else{
                    $this->parts['path'][$index] = $value;
                }
                $this->parts['path'] = explode('/',implode('/',$this->parts['path']));
            }
            return $this;
        }
    }
    public function parse($uri = null) {

        # No URI is set, the current request URI will be used
        if(func_num_args()==0) {
            if(!empty($_SERVER["SCRIPT_URI"]))
                $this->parts = parse_url($_SERVER["SCRIPT_URI"]);
            if(!empty($_SERVER["SERVER_PORT"]))
                $this->port($_SERVER["SERVER_PORT"]);
            if(!empty($_SERVER["HTTP_HOST"]))
                $this->host = $_SERVER["HTTP_HOST"];
            elseif(!empty($_SERVER["SERVER_NAME"]))
                $this->host = $_SERVER["SERVER_NAME"];
            if(isset($_SERVER['HTTPS'])){
                $this->scheme ='https';
            }
            $this->source = $this->build();
            return $this;
        }
        if(is_array($uri)){
            $k = 'protocol';
            if(isset($uri[$k])) $this->scheme($uri[$k]);
            $k = 'authority';
            if(isset($uri[$k])) $this->authority($uri[$k]);
            $k = 'search';
            if(isset($uri[$k])) $this->query($uri[$k]);
            $k = 'anchor';
            if(isset($uri[$k])) $this->fragment($uri[$k]);
            foreach($this->_terms as $k){
                if(isset($uri[$k])) $this->$k($uri[$k]);
            }
            $this->source = $this->__toString();
            return $this;
        }
        $this->source = $uri = (string)$uri;
        $p = parse_url($uri); 
        $this->parts = $p;
        if(isset($p['query'])) $this->query($p['query']);
        if(isset($p['path'])) $this->path($p['path']);
        
        # Regular Expression from RFC 2396 (appendix B)
        preg_match('"^(([^:/?#]+):)?(//([^/?#]*))?([^?#]*)(\?([^#]*))?(#(.*))?"', $uri, $matches);
        
        # Extract the URI scheme from the string
        if(array_key_exists(2, $matches)) $this->scheme( $matches[2] );
        if(array_key_exists(4, $matches)) $this->authority ($matches[4]);
        if(array_key_exists(5, $matches)) $this->path($matches[5]);
        if(array_key_exists(7, $matches)) $this->query($matches[7]);
        if(array_key_exists(9, $matches)) $this->fragment($matches[9]);
        
        return $this;
        
    }
    # Returns the URI as a string
    # You can specify what parts should be built
    public function build(array $parts = array('scheme', 'user', 'pass', 'host','port', 'path', 'query', 'fragment')) {
        if(func_num_args()>1){
            $parts = func_get_args();
        }
        if($parts){
            $a = array();
            foreach($parts as $k)
            {
                if(isset($this->parts[$k])) $a[$k] = $this->parts[$k];
            }
            return self::http_build_url($a);
        }else{
            return self::http_build_url($this->parts);
        }
    }
    
    #FOR FILTERING
    public function scheme() {
        $name = 'scheme';
        if(func_num_args()){
            $this->parts[$name] = (string) func_get_arg(0);
            $this->parts['scheme'] = trim($this->parts['scheme'],'/');
            $this->parts['scheme'] = trim($this->parts['scheme'],':');
            return $this;
        }else{
            if(isset($this->parts[$name]))
                return $this->parts[$name];
            else
                return '';
        }
    }
    public function port() {
        $name = 'port';
        if(func_num_args()){
            $this->parts[$name] = (int) func_get_arg(0);
            return $this;
        }else{
            if(isset($this->parts[$name]))
                return $this->parts[$name];
            else
                return '';
        }
    }
    
    public function authority() {
        
        if(func_num_args()>1){
            if(func_num_args()>=4)$this->port = func_get_arg(3);
            if(func_num_args()>=2)$this->host = func_get_arg(2);
            if(func_num_args()>=4)$this->pass = func_get_arg(1);
            if(func_num_args()>=1)$this->user = func_get_arg(0);
            return $this;
        }elseif(func_num_args()){
            $authority =func_get_arg(0);
            # Extract username, password, host and port from authority
            preg_match('"(([^:@]*)(:([^:@]*))?@)?([^:]*)(:(.*))?"', $authority, $matches);
            
            if(array_key_exists(2, $matches)) $this->user = $matches[2];
            if(array_key_exists(4, $matches)) $this->pass = $matches[4];
            if(array_key_exists(5, $matches)) $this->host = $matches[5];
            if(array_key_exists(7, $matches)) $this->port($matches[7]);
            return $this;
        }else{
            $r='//';
            if(!empty($this->parts['user'])) $r = urlencode($this->parts['user']); 
            if(!empty($this->parts['pass'])) $r .= ':'. urlencode($this->parts['pass']);
            if($r) $r .= '@';
            if(!empty($this->parts['host'])) $r .= urlencode($this->parts['host']);
            if(!empty($this->parts['port'])) $r .= ':' .urlencode($this->parts['port']);
            return $r;
        }
    }
    public function path() {
        if(func_num_args()>1){
            $path =func_get_args();
            $this->parts['path'] = array_filter(explode('/', implode('/',$path)));
            return $this;
        }elseif(func_num_args()){
            $path =func_get_arg(0);
            $path =trim($path,'/');
            $this->parts['path'] = array_filter(explode('/', $path));
            return $this;
        }else{
            if(!empty($this->parts['path']))
                return implode('/',$this->parts['path']);
            else
                return '';
        }
    }
    public function query() {
        $name = 'query';
        if(func_num_args()){
            $query = func_get_arg(0);
            if(!is_array($query)){
                parse_str($query, $query); 
            }
            $this->parts[$name] = $query;
            return $this;
        }else{
            if(!empty($this->parts[$name]))
                return http_build_query($this->parts[$name], '');
            else
                return'';
        }
    }
    public function fragment() {
        $name = 'fragment';
        if(func_num_args()>1){
            $fragment=func_get_args();
            $this->parts[$name] = implode('/',$fragment);
            return $this;
        }elseif(func_num_args()){
            $fragment= func_get_arg(0);
            if(is_array($fragment)) $fragment = http_build_query($fragment);
            $this->parts[$name] = (string)$fragment;
            return $this;
        }else{
            if(isset($this->parts[$name]))
                return $this->parts[$name];
            else
                return '';
        }
    }
    /*
    // Get the TLD.
		// First, see if we match a 3-letter (.com) or 2.2-letter (.co.uk) TLD.
		if (preg_match('/(\.(?:[a-z]{3}|[a-z]{2}(?:\.[a-z]{2})?))$/', $this->host, $tld))
		{
			$this->tld = $tld[1];
		}
		else
		{
			// Standard format didn't match - check our array of TLD extensions.
			$ext = pathinfo($this->host, PATHINFO_EXTENSION);
			
			if (in_array($ext, $this->_extensions))
			{
				$this->tld = '.'.$ext;
			}
			else
			{
				// Nothing found.
				return $this->_setInvalid('Invalid top level domain extension.');
			}
		}
		
		// Remove subdomain from host, separate subdomain(s) from domain.
		$domain = explode('.', substr($this->host, 0, -strlen($this->tld)));
		
		$this->domain = array_pop($domain);
		
		$this->subdomain = implode('.', $domain);
		$this->subdomainArray = $domain;
    public function domain() {
        $name = 'fragment';
        if(func_num_args()>1){
            $fragment=func_get_args();
            $this->parts[$name] = implode('/',$fragment);
            return $this;
        }elseif(func_num_args()){
            $fragment= func_get_arg(0);
            if(is_array($fragment)) $fragment = http_build_query($fragment);
            $this->parts[$name] = (string)$fragment;
            return $this;
        }else{
            if(isset($this->parts[$name]))
                return $this->parts[$name];
            else
                return '';
        }
    }
    public function tld() {
        $name = 'fragment';
        if(func_num_args()>1){
            $fragment=func_get_args();
            $this->parts[$name] = implode('/',$fragment);
            return $this;
        }elseif(func_num_args()){
            $fragment= func_get_arg(0);
            if(is_array($fragment)) $fragment = http_build_query($fragment);
            $this->parts[$name] = (string)$fragment;
            return $this;
        }else{
            if(isset($this->parts[$name]))
                return $this->parts[$name];
            else
                return '';
        }
    }
    public function subdomain() {
        $name = 'fragment';
        if(func_num_args()>1){
            $fragment=func_get_args();
            $this->parts[$name] = implode('/',$fragment);
            return $this;
        }elseif(func_num_args()){
            $fragment= func_get_arg(0);
            if(is_array($fragment)) $fragment = http_build_query($fragment);
            $this->parts[$name] = (string)$fragment;
            return $this;
        }else{
            if(isset($this->parts[$name]))
                return $this->parts[$name];
            else
                return '';
        }
    }*/
    public function isAbsolute()
    {	        
        return $this->_has('scheme');
    }
    /**
     * Returns an ELI_URI instance representing an absolute URL relative to
     * this URL.
     *
     * @param ELI_URI|string $reference relative URL
     *
     * @return ELI_URI
     */
    public function resolve($reference)
    {
        if (!$reference instanceof ELI_URI) {
            $reference = new self($reference);
        }
        if (!$this->isAbsolute()) {
            throw new Exception('Base-URL must be absolute');
        }

        // A non-strict parser may ignore a scheme in the reference if it is
        // identical to the base URI's scheme.
        if ($reference->scheme == $this->scheme) {
            $reference->scheme('');
        }

        $target = new self('');
        
        if ($reference->scheme) {
            $target->scheme = $reference->scheme;
            $target->authority($reference->authority());
            $target->path  = self::removeDotSegments($reference->path());
            $target->query = $reference->query;
        } else {
            $authority = $reference->authority();
            if ($authority) {
                $target->authority($authority);
                $target->path  = self::removeDotSegments($reference->path());
                $target->query = $reference->query;
            } else {
                if ($reference->path() == '') {
                    $target->path = $this->path;
                    if ($reference->query()) {
                        $target->query = $reference->query;
                    } else {
                        $target->query = $this->query;
                    }
                } else {
                    if (substr($reference->path(), 0, 1) == '/') {
                        $target->path = self::removeDotSegments($reference->path());
                    } else {
                        // Merge paths (RFC 3986, section 5.2.3)
                        if ($this->host && $this->path == '') {
                            $target->path( '/' . $this->path());
                        } else {
                            $p =$this->path();
                            $i = strrpos($p, '/');
                            if ($i !== false) {
                                $target->path( substr($p, 0, $i + 1));
                            }
                            $target->path($target->path() . $reference->path());
                        }
                        $target->removeDotSegments();
                    }
                    $target->query = $reference->query;
                }
                $target->authority($this->authority());
            }
            $target->scheme = $this->scheme;
        }

        $target->fragment = $reference->fragment;

        return $target;
    }

    /**
     * Removes dots as described in RFC 3986, section 5.2.4, e.g.
     * "/foo/../bar/baz" => "/bar/baz"
     *
     * @param string $path a path
     *
     * @return string a path
     */
    public static function removeDotSegments($path='')
    {
        if(isset($this)){
            if(isset($this->parts['path'])){
                $path = array();
                foreach($this->parts['path'] as $k => $v){
                    if($v =='.'){
                        //unset
                    }elseif($v =='..'){
                        //unset parent
                        $i = count($path)-1;
                        if($i > -1){
                            unset($path[$i]);
                        }
                    }else{
                        $path[] = $v;
                    }
                }
                $this->parts['path'] = $path;
            }
            return $this;
        }elseif(func_num_args()){
            if(func_num_args()>1){
                $p = func_get_args();
            }else{
                $p =func_get_arg(0);
                $p = explode('/', $p);
            }
            $path = array();
            foreach($p as $k => $v){
                if($v =='.'){
                    //unset
                }elseif($v =='..'){
                    //unset parent
                    $i = count($path)-1;
                    if($i > -1){
                        unset($path[$i]);
                    }
                }else{
                    $path[] = $v;
                }
            }
            return implode('/',$path);
        }
        return '';
        /*
        $output = '';

        // Make sure not to be trapped in an infinite loop due to a bug in this
        // method
        $j = 0;
        while ($path && $j++ < 100) {
            if (substr($path, 0, 2) == './') {
                // Step 2.A
                $path = substr($path, 2);
            } elseif (substr($path, 0, 3) == '../') {
                // Step 2.A
                $path = substr($path, 3);
            } elseif (substr($path, 0, 3) == '/./' || $path == '/.') {
                // Step 2.B
                $path = '/' . substr($path, 3);
            } elseif (substr($path, 0, 4) == '/../' || $path == '/..') {
                // Step 2.C
                $path   = '/' . substr($path, 4);
                $i      = strrpos($output, '/');
                $output = $i === false ? '' : substr($output, 0, $i);
            } elseif ($path == '.' || $path == '..') {
                // Step 2.D
                $path = '';
            } else {
                // Step 2.E
                $i = strpos($path, '/');
                if ($i === 0) {
                    $i = strpos($path, '/', 1);
                }
                if ($i === false) {
                    $i = strlen($path);
                }
                $output .= substr($path, 0, $i);
                $path = substr($path, $i);
            }
        }

        return $output;*/
    }
    /**
     * Returns a ELI_URI instance representing the canonical URL of the
     * currently executing PHP script.
     *
     * @return  ELI_URI
     */
    public static function getCanonical()
    {
        if (!isset($_SERVER['REQUEST_METHOD'])) {
            // ALERT - no current URL
            throw new Exception('Script was not called through a webserver');
        }

        // Begin with a relative URL
        $url = new self($_SERVER['PHP_SELF']);
        $url->scheme = isset($_SERVER['HTTPS']) ? 'https' : 'http';
        $url->host   = $_SERVER['SERVER_NAME'];
        $port = $_SERVER['SERVER_PORT'];
        if (($url->scheme == 'http' && $port != 80) ||
            ($url->scheme == 'https' && $port != 443)) {
            $url->port = $port;
        }
        $url->parse($url->build());
        return $url;
    }
    /**
     * Returns a ELI_URI instance representing the URL used to retrieve the
     * current request.
     *
     * @return  ELI_URI
     */
    public static function getRequested()
    {
        if (!isset($_SERVER['REQUEST_METHOD'])) {
            // ALERT - no current URL
            throw new Exception('Script was not called through a webserver');
        }

        // Begin with a relative URL
        $url = new self($_SERVER['REQUEST_URI']);
        $url->scheme = isset($_SERVER['HTTPS']) ? 'https' : 'http';
        // Set host and possibly port
        $url->Authority($_SERVER['HTTP_HOST']);
        $port = $_SERVER['SERVER_PORT'];
        if (($url->scheme == 'http' && $port != 80) ||
            ($url->scheme == 'https' && $port != 443)) {

            $url->port = $port;
        }
        $url->parse($url->build());
        
        return $url;
    }
    /**
     * Returns a string representation of this URL.
     *
     * @return  string
     */
    public static function http_build_url($parts)
    {
        $url = '';
        // See RFC 3986, section 5.3
        if(isset($parts['scheme'])){
            $url .= rtrim($parts['scheme'],':') . ':';
        }
        if(isset($parts['authority'])){
            $url .= '//'.$parts['authority'] ;
        }else{
            $r='';
            if(!empty($parts['user'])) $r = urlencode($parts['user']); 
            if(!empty($parts['pass'])) $r .= ':'. urlencode($parts['pass']);
            if($r) $r .= '@';
            if(!empty($parts['host'])) $r .= urlencode($parts['host']);
            if(!empty($parts['port'])) $r .= ':' .urlencode($parts['port']);
            if($r)$url .= '//'.$r;
        }
        if(isset($parts['path'])){
            if(is_array($parts['path'])) $parts['path'] = array_filter($parts['path']);
            $r = trim(implode('/',$parts['path'] ),'/');
            if($r)$url .= '/'.$r;
        }
        if(isset($parts['query'])){
            if(is_array($parts['query'])) $parts['query']= http_build_query($parts['query']);
            $parts['query'] = ltrim($parts['query'] ,'?');
            if($parts['query'] )$url .= '?'.$parts['query'] ;
        }
        if(isset($parts['fragment'])){
            $parts['fragment'] = ltrim($parts['fragment'] ,'#');
            if($parts['fragment'] )$url .= '#'.$parts['fragment'] ;
        }
        return $url;
    }
    public static function isIP($ip_addr) 
    { 
      //first of all the format of the ip address is matched 
      if(preg_match("/^(\d{1,3})\.(\d{1,3})\.(\d{1,3})\.(\d{1,3})$/",$ip_addr)) 
      { 
        //now all the intger values are separated 
        $parts=explode(".",$ip_addr); 
        //now we need to check each part can range from 0-255 
        foreach($parts as $ip_parts) 
        { 
          if(intval($ip_parts)>255 || intval($ip_parts)<0) 
          return FALSE; //if number is not within range of 0-255 
        } 
        return TRUE; 
      } 
      else 
        return FALSE; //if format of ip address doesn't matches 
    } 

}

