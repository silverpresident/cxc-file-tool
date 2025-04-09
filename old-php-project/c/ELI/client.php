<?php
/**
 * @author Edwards
 * @copyright 2010
 */
class ELI_client
{
    /**
     * ELI_client::ip()
     * if the user sits behind a proxy, then you will get the IP of the proxy server 
     * and not the real user address. Fortunately we can make some additional refinement 
     * to get more accurate results. Proxy servers extend the HTTP 
     * header with new property which stores the original IP. 
     * The name of this filed is X-Forwarded-For or Client-Ip. 
     * @return String
     */
    static function IP() {
        $ip = $_SERVER['REMOTE_ADDR'];
     
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        }
     
        return $ip;
    }
}
?>