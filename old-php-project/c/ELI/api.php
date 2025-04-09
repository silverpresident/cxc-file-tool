<?php
/**
 * @author Edwards
 * @copyright  2013
 */
class ELI_api
{
    static function signatureVerify($ARRAY,$key){
        array_change_key_case($ARRAY,CASE_LOWER);
        if(!isset($ARRAY['signature']))  return false;
        $s = $ARRAY['signature'];
        unset($ARRAY['signature']);
        ksort($ARRAY);
        $q = http_build_query($ARRAY);
        $sig = md5($q.$key);
        return ($s==strtolower($sig));
    }
    
    static function signature($ARRAY,$key){
        unset($ARRAY['signature']);
        ksort($ARRAY);
        $q = http_build_query($ARRAY);
        return strtolower(md5($q.$key));
    }
    static function query($ARRAY,$signature=''){
        if($signature)$ARRAY['signature'] = $signature;
        return http_build_query($ARRAY);
    }
} 
  
?>