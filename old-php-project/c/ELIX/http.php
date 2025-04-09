<?php
/**
 * @author Edwards
 * @copyright 2016
 * 
 * http://stackoverflow.com/questions/14745587/how-to-use-wget-in-php
 *  
 * 
 * 
 */
namespace ELIX;
/**
  *  echo HTTP::POST('http://accounts.kbcomp.co',
  *      array(
  *            'user_name'=>'demo@example.com',
  *            'user_password'=>'demo1234'
  *      )
  *  );
  *  OR
  *  echo HTTP::GET('http://api.austinkregel.com/colors/E64B3B/1');
  *                  
  */

class HTTP{
    public static function GET($url,Array $options=array()){
        $ch = curl_init();
        if(count($options)>0){
            curl_setopt_array($ch, $options);
        }
        curl_setopt($ch, CURLOPT_URL, $url);
        $json = curl_exec($ch);
        curl_close($ch);
        return $json;
    
    }
    public static function POST($url, $postfields, $options = null){
        $ch = curl_init();
        if(!is_array($options)){
            $options = array();
        }
        $options[CURLOPT_URL] = $url;
        $options[CURLOPT_POSTFIELDS] = $postfields;
        if(!isset($options[CURLOPT_RETURNTRANSFER]))$options[CURLOPT_RETURNTRANSFER] = true;
        //if(!isset($options[CURLOPT_HEADER]))$options[CURLOPT_HEADER] = true;
        curl_setopt_array($ch, $options);
        $json = curl_exec($ch);
        curl_close($ch);
        return $json;
    }
}