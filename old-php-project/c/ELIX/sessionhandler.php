<?php
/**
 * @author Edwards
 * @copyright 2015
 * 
 * http://eddmann.com/posts/securing-sessions-in-php/
 * https://gist.github.com/eddmann/10262795
 * http://blog.teamtreehouse.com/how-to-create-bulletproof-sessions
 */
namespace ELIX;


class SessionHandler extends \SessionHandler {

    protected  $name, $cookie;
    protected $key = 'elix', $_use_ip=true;

    public function __construct($key, $name = 'ELIX_SESSION', $cookie = array())
    {
        $this->key = $key;
        $this->name = $name;
        $this->cookie = $cookie;

        $this->cookie += array(
            'lifetime' => 0,
            'path'     => ini_get('session.cookie_path'),
            'domain'   => ini_get('session.cookie_domain'),
            'secure'   => isset($_SERVER['HTTPS']),
            'httponly' => true
        );

        $this->setup();
    }

    protected function setup()
    {
        ini_set('session.use_cookies', 1);
        ini_set('session.use_only_cookies', 1);

        session_name($this->name);

        session_set_cookie_params(
            $this->cookie['lifetime'], $this->cookie['path'],
            $this->cookie['domain'], $this->cookie['secure'],
            $this->cookie['httponly']
        );
        if(!self::validateSession())
    	{
    		$_SESSION = array();
    		session_destroy();
    		session_start();
    	}
    }

    public function start()
    {
        if (session_id() === '') {
            if (session_start()) {
                return (mt_rand(0, 4) === 0) ? $this->refresh() : true; // 1/5
            }
        }
    
        return false;
    }
    
    public function forget()
    {
        if (session_id() === '') {
            return false;
        }
    
        $_SESSION = array();
    
        setcookie(
            $this->name, '', time() - 42000,
            $this->cookie['path'], $this->cookie['domain'],
            $this->cookie['secure'], $this->cookie['httponly']
        );
    
        return session_destroy();
    }
    
    public function refresh()
    {
        return session_regenerate_id(true);
    }
    public function setKey($id)
    {
        $this->key = $id;
    }
    public function read($id)
    {
        return mcrypt_decrypt(MCRYPT_3DES, $this->key, parent::read($id), MCRYPT_MODE_ECB);
    }
    
    public function write($id, $data)
    {
        return parent::write($id, mcrypt_encrypt(MCRYPT_3DES, $this->key, $data, MCRYPT_MODE_ECB));
    }
    public function isExpired($ttl = 30)
    {
        $activity = isset($_SESSION['_last_activity'])
            ? $_SESSION['_last_activity']
            : false;
    
        if ($activity !== false && time() - $activity > $ttl * 60) {
            return true;
        }
    
        $_SESSION['_last_activity'] = time();
    
        return false;
    }
    
    public function isFingerprint()
    {
        $remoteAddress=($this->_use_ip) ? $_SERVER['REMOTE_ADDR'] : FALSE ;
        $hash = md5(
            $_SERVER['HTTP_USER_AGENT'] .
            (inet_ntop(inet_pton($remoteAddress)) & inet_ntop(inet_pton('255.255.0.0')))
        );
    
        if (isset($_SESSION['_fingerprint'])) {
            return $_SESSION['_fingerprint'] === $hash;
        }
    
        $_SESSION['_fingerprint'] = $hash;
    
        return true;
    }
    
    public function isValid($ttl = 30)
    {
        return ! $this->isExpired($ttl) && $this->isFingerprint();
    }
    public function get($name)
    {
        $parsed = explode('.', $name);
    
        $result = $_SESSION;
    
        while ($parsed) {
            $next = array_shift($parsed);
    
            if (isset($result[$next])) {
                $result = $result[$next];
            } else {
                return null;
            }
        }
    
        return $result;
    }
    
    public function put($name, $value)
    {
        $parsed = explode('.', $name);
    
        $session =& $_SESSION;
    
        while (count($parsed) > 1) {
            $next = array_shift($parsed);
    
            if ( ! isset($session[$next]) || ! is_array($session[$next])) {
                $session[$next] = array();
            }
    
            $session =& $session[$next];
        }
    
        $session[array_shift($parsed)] = $value;
    }
    static function regenerateSession()
    {
    	// If this session is obsolete it means there already is a new id
    	if(isset($_SESSION['OBSOLETE']) || $_SESSION['OBSOLETE'] == true)
    		return;
    
    	// Set current session to expire in 10 seconds
    	$_SESSION['OBSOLETE'] = true;
    	$_SESSION['EXPIRES'] = time() + 10;
    
    	// Create new session without destroying the old one
    	session_regenerate_id(false);
    
    	// Grab current session ID and close both sessions to allow other scripts to use them
    	$newSession = session_id();
    	session_write_close();
    
    	// Set session ID to the new one, and start it back up again
    	session_id($newSession);
    	session_start();
    
    	// Now we unset the obsolete and expiration values for the session we want to keep
    	unset($_SESSION['OBSOLETE']);
    	unset($_SESSION['EXPIRES']);
    }
    static protected function validateSession()
    {
    	if( isset($_SESSION['OBSOLETE']) && !isset($_SESSION['EXPIRES']) )
    		return false;
    
    	if(isset($_SESSION['EXPIRES']) && $_SESSION['EXPIRES'] < time())
    		return false;
    
    	return true;
    }
}
