<?php
/**
 * @author Edwards
 * @copyright  2014
 * 
 * 
 * 10000+20000+20000+4000+7500
 * 
 * 
 */
 
//TODO: complete this module.
class ELI_password
{
    static function gem($length=8,$charset=null){
        $length = (int)$length;
        if(!$length) $length = rand(1,64);
        if((null ===$charset)){
            if($length < 32)
                return substr(md5(uniqid()), 0, $length);
            else{
                $charset = md5(uniqid());
                return substr($charset . str_repeat(str_shuffle($charset),$length/32), 0, $length);
            }
        }elseif(empty($charset)){
            $charset = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
            $l = strlen($charset);
            if($length < $l)
                return substr(str_shuffle($charset),0,$length);
            else
                $charset = str_shuffle($charset) . str_shuffle($charset) . str_shuffle($charset);
                return substr(str_repeat($charset,($length/$l), 0, $length));
        }else{
            $password = '';
            $lpossible = strlen($charset)-1;
            $i = 0; 
            while ($i < $length) { 	
                // pick a random character from the possible ones
                $char = substr($charset, mt_rand(0, $lpossible), 1);
                if($i < $lpossible)	{
                // we don't want this character if it's already in the password
                    if(!strstr($password, $char)){
                        $password .= $char;
                        $i++;
                    }
                }else{
                    $password .= $char;
                    $i++;
                }	
            }
            return $password;
        }
    }
    static function generate($letters = 8,$numbers=2,$symbols=0,$charset=null)
    {	
        if(is_array($letters)){
            $a = $letters;
            $upper = isset($a['upper'])?$a['upper']:0;
            $lower = isset($a['lower'])?$a['lower']:0;
            $letters = isset($a['letters'])?$a['letters']:8;
            if(empty($letters)) $letters =0;
            if($letters && !is_numeric($letters)){
                 $letters=(int)$letters;
                 if(!$letters) $letters = 8;
            }
            $numbers = isset($a['numbers'])?$a['numbers']:8;
            if(empty($numbers)) $numbers =0;
            if($numbers && !is_numeric($numbers)){
                 $numbers=(int)$numbers;
                 if(!$numbers) $numbers = 2;
            }
            $symbols = isset($a['symbols'])?$a['symbols']:0;
            if(empty($symbols)) $symbols =0;
            if($symbols && !is_numeric($symbols)){
                 $symbols=(int)$symbols;
                 //if(!$symbols) $symbols = 0;
            }
            $charset = isset($a['charset'])?$a['charset']:'';
            $charset_num = isset($a['charset_numbers'])?$a['charset_numbers']:'';
            $charset_sym = isset($a['charset_symbols'])?$a['charset_symbols']:'';
            $charset_let = isset($a['charset_letters'])?$a['charset_letters']:'';
        }else{
            $lower = $upper = 0;
            $charset_num = $charset_sym = $charset_let='';
        }
        $password = '';
        if($upper || $lower){
            if($upper){
                $cset = '';
                if($charset_let){
                    $cset = preg_replace("/[^A-Z]/", "", $charset_let);
                }elseif($charset){
                    $cset = preg_replace("/[^A-Z]/", "", $charset);
                }
                if(!$cset){
                    $cset = 'ABCDEFGHJKLMNPQRSTUVWXYZ';
                }
                $password .= self::gem($upper,$cset);
            }
            if($lower){
                $cset = '';
                if($charset_let){
                    $cset = preg_replace("/[^a-z]/", "", $charset_let);
                }elseif($charset){
                    $cset = preg_replace("/[^a-z]/", "", $charset);
                }
                if(!$cset){
                    $cset = 'abcdefghjkmnpqrstuvwxyz';
                }
                $password .= self::gem($lower,$cset);
            }
            $letters = $upper+$lower;
        }elseif($letters){
            $cset = '';
            if($charset_let){
                $cset = preg_replace("/[^a-zA-Z]/", "", $charset_let);
            }elseif($charset){
                $cset = preg_replace("/[^a-zA-Z]/", "", $charset);
            }
            if(!$cset){
                $cset = 'abcdefghjkmnpqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ';
            }
            $password .= self::gem($letters,$cset);
        }
        if($numbers){
            $cset = '';
            if($charset_num){
                $cset = preg_replace("/[^0-9]/", "", $charset_num);
            }elseif($charset){
                $cset = preg_replace("/[^0-9]/", "", $charset);
            }
            if(!$cset){
                $cset = '123456789';
                if(!$letters) $cset.='0';
            }
            $password .= self::gem($numbers,$cset);
        }
        if($password){
            $password = str_shuffle($password);
            $x = rand(1,strlen($password));
            $front = substr($password,0,$x);
            $back = substr($password,$x);
        }else{
            $front = $back = '';
        }
        $password = '';
        if($symbols){
            $cset = '';
            if($charset_sym){
                $cset = preg_replace("/[0-9a-zA-Z]/", "", $charset_sym);
            }elseif($charset){
                $cset = preg_replace("/[0-9a-zA-Z]/", "", $charset);
            }
            if(!$cset){
                $cset = '~!@#$%^&*=+?';
                if(($letters+$numbers)==0) $cset .= ':>.-<';
            }
            $password .= self::gem($symbols,$cset);
        }
        return $front.$password.$back;
    }
    static function verify($password,$hashed_password) {
        $x = explode('$',$hashed_password,3);
        if($x[0]=='h1'){
            $comp = self::hash1($password,$x[1]);
        }elseif($x[0]=='h2'){
            $comp = self::hash2($password,$x[1]);
        }else{
            $comp=$password.'---.k';
        }
        return $comp===$hashed_password;
    }
    static function get_salt($hashed_password) {
        $x = explode('$',$hashed_password,3);
        return $x[1];
    }
    
    static function salt($length=9, $entropy=3) {
        if($length < 2) $length =2;
        if($length > 28) $length =28;
        $s = md5(microtime() .'|'. $entropy);
        if($entropy>4) $s = sha1($s);
        if($entropy>2 && $length>4) $s = substr(time(),2,4).$s;
        return substr($s,2,$length);
    }
    static function hash1($password,$salt=null) {
        if(empty($salt)){
            $salt = self::salt(10);
        }elseif(strlen($salt)>17){
            $salt = substr(md5($salt),0,17);
        }
        $buffer ='h1$';
        $buffer .= $salt.'$';
        $buffer .= md5($password.'|'.$salt);
        
        return $buffer;
    }
    static function hash2($password,$salt=null) {
        if(empty($salt)){
            $salt = self::salt(10);
        }elseif(strlen($salt)>15){
            $salt = substr(md5($salt),0,17);
        }
        $buffer ='h2$';
        $buffer .= $salt.'$';
        $buffer .= sha1($password.'|'.$salt);
        
        return $buffer;
    }
} 
  
?>