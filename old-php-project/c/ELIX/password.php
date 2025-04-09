<?php
/**
 * @author Edwards
 * @copyright 2016
 * 
 * 
 * 
 * $PML = ELIX::password();
 * $hpwd = $PML->get('hashed-password');
 *      $hpwd->needs_rehash()
 *      $hpwd->new_hash('plain-password');
 *      $hpwd->hash;
 *      $hpwd->verify('plain-password');
 * $ppwd = $PML->getPlain('plain-password');
 *      $hpwd->needs_rehash()
 *      $hpwd->new_hash('plain-password');
 *      $hpwd->hash;
 *      $hpwd->plain;
 *      $hpwd->verify('plain-password');
 */

namespace ELIX;
include_once('password-compat.inc');

class PassWord{
    public function get($hashed){
         return new PassWord_item($hashed);
    }
    public function getPlain($plain){
        $hashed = password_hash($plain,PASSWORD_BCRYPT);
        if($hashed === FALSE){
            $hashed = md5($plain);
        }
        return new PassWord_item($hashed,$plain);
    }
    public function getInspector($plain){
        return new PassWord_inspector($plain);
    }
    public function generate($length = 6,$prefix=''){
        if($length <= 0){
            $l = strlen($prefix);
            $length = $l +8;
        }
        if($length <5){
            $possible = str_shuffle("23456789bcdfghjkmnpqrstvwxyz");
            $plain = substr($possible,0,$length);
        }else if($length < 9){
            $possible = str_shuffle('84726abcdefghjkmnpqrstuvwxyzABCDEFGHIJKLMNPQRSTUVWXWZ&#$');
            $plain = substr(uniqid(),0,3);
            $plain .= substr($prefix . $possible,0,$length-3);
        }else{
            $possible = str_shuffle('0123456789abcdefghjkmnpqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXWZ%&#$');
            if(strlen($possible) < $length){
                $possible .= uniqid();
                $possible .= substr(strrev(Time()),0,3);
            }
            
            $plain = substr($prefix.$possible,0,$length);
            $lpossible = strlen($possible)-1;
            $i = strlen($plain);
            while ($i < $length) { 	
                // pick a random character from the possible ones
                $char = substr($possible, mt_rand(0, $lpossible), 1);	
                $plain .= $char;
                $i++;
            }
        }
        
        $hashed = password_hash($plain,PASSWORD_BCRYPT);
        if($hashed === FALSE){
            $hashed = md5($plain);
        }
        return new PassWord_item($hashed,$plain);
    }
    public function hash($plain){
        $hashed = password_hash($plain,PASSWORD_BCRYPT);
        if($hashed === FALSE){
            $hashed = md5($plain);
        }
        return $hashed;
    }
    
    public function verify($plain,$hashed){
        if(substr($hashed,0,1) != '$'){
            return $hashed == md5($plain);
        }
        return password_verify($plain,$hashed);
    }
    public function info($hashed){
        return password_get_info($hashed);
    }
    public function needs_rehash($hashed){
        if(substr($hashed,0,1) != '$'){
            return true;
        }
        return password_needs_rehash($hashed,PASSWORD_BCRYPT);
    }
    
}
class PassWord_item
{
    private $data = array();
    private $hash = null;
    private $plain = null;
    public function __construct($hash,$plain='') {
        $this->hash = $hash;
        $this->plain = $plain;
        $this->data = password_get_info($hash);
    }
    public function __toString() {
        return $this->hash;
    }
    public function __get($name) {
        if(method_exists($this,$name)){
            return $this->$name();
        }
    }

    public function toString() {
        return $this->hash;
    }
    public function id(){
        return $this->hash;
    }
    public function hash(){
        return $this->hash;
    }
    public function plain(){
        return $this->plain;
    }
    public function info(){
        return $this->data;
    }
    public function needs_rehash(){
        if(substr($this->hash,0,1) != '$'){
            return true;
        }
        return password_needs_rehash($this->hash,PASSWORD_BCRYPT);
    }
    public function verify($plain){
        if(substr($this->hash,0,1) != '$'){
            return $this->hash == md5($plain);
        }
        return password_verify($plain,$this->hash);
    }
    public function new_hash($plain){
        if($this->verify($plain)){
            if(password_needs_rehash($this->hash,PASSWORD_BCRYPT)){
                $hashed = password_hash($plain,PASSWORD_BCRYPT,$this->data);
                if($hashed === FALSE){
                    $hashed = md5($plain);
                }
                return $hashed;
            }
            return '';
        }
        return false;
    }
}
class PassWord_inspector
{
    private $plain = null;
    private $strength = null;
    public function __get($name) {
        if(method_exists($this,$name)){
            return $this->$name();
        }
        return null;
    }

    public function __construct($plain='') {
        $this->plain = $plain;
    }
    public function __toString() {
        return $this->plain;
    }
    public function toString() {
        return $this->plain;
    }
    public function plain() {
        return $this->plain;
    }
    static private $commonPasswords = array(
            'abc123','admin','admin123',
            'password','passw0rd','password1',
            'qwerty','qwertyuiop','silver','sunshine','hello','welcome','orange','red',
            '111111','letmein','dragon','baseball','football','chelsea',
            'monkey','master','access','matrix','secret',
            '696969','666666','123321','112233','131313','123123',
            'mustang','michael','shadow','computer','iloveu','iloveyou','princess',
            );
            
    public function isCommon() {
        if(!$this->plain){
            return true;
        }
        $sl = strtolower($this->plain);
        $sl = preg_replace('/[^a-z0-9]/','',$sl);
        
        if(strpos('01234567890',$this->plain) !== false){
            return true;
        }
        if(strpos('09876543210',$this->plain) !== false){
            return true;
        }
        
        if(strpos('abcdefghijklmnopqrstuvwxyz',$this->plain) !== false){
            return true;
        }
        if(in_array($sl,self::$commonPasswords)){
            return true;
        }
        if(strlen($this->plain) < 11){
            $x = str_split($this->plain);
            $x = array_unique($x);
            if(count($x)==1) return true;
        }
        return false;
    }
    public function length() {
        return strlen($this->plain);
    }
    const STRONGEST = 19;
    const STRONG = 12;
    const NORMAL = 8;
    
    public function strength() {
        if($this->strength !== null){
            return $this->strength;
        }
        $s = 0;
        if(!$this->plain){
            $this->strength = $s;
            return $s;
        }
        $len = strlen($this->plain);
        
        /**
         * A STRONG password
         *  len more than 10      = +2
         *     @10   = 2
         *     @7    = 1
         *  char distinct >10     = +3
         *     @10   = 3
         *     @7    = 2
         *     @5    = 1
         *  letter aft nonletter  = +1
         *  letter after num      = +1
         *  num after non num     = +1
         *  has num               = +1
         *  has lower             = +1
         *  has upper             = +1
         *  has symbol            = +1
         *  has >2 num            = +1
         *  has >2 lower          = +1
         *  has >2 upper          = +1
         *  has >2 symbol         = +1
         *  starts w num or sym   = +1
         *  has space             = +1
            has > 1 space         = +1
         * 
         * 
         * 
         * A FAIRLY STRONG password
         *  len more than 7      = +2
         *  char distinct > 7    = +2
         *  +any single point above
         * 
         * 
        */
        $x = str_split($this->plain);
        $x = array_unique($x);
        $uniqueChars = count($x);
        
        if($uniqueChars > 3){
            if($uniqueChars > 10){
                $s++;
            }
            if($uniqueChars > 7){
                $s++;
            }
            if($uniqueChars > 5){
                $s++;
            }
            if($len > 10){
                $s++;
            }
            if($len > 7){
                $s++;
            }
        }else{
            $s--;
        }
        
        
        if(preg_match('/([^a-zA-Z][a-zA-Z])/',$this->plain)){
            $s++; //wordLetterAfterOther
        }
        if(preg_match('/([0-9][a-zA-Z])/',$this->plain)){
            $s++; //wordLetterAfterNumber
        }
        if(preg_match('/([^0-9]+[0-9][^0-9]+[0-9])/',$this->plain)){
            $s++; //wordThreeNumbers
        }
        
        preg_match_all('/[\d]/', $this->plain, $matches, PREG_OFFSET_CAPTURE);
        if(isset($matches[0][0])){
            $p = $matches[0][0][1];
            $s++; //contains
            if($p == 0) $s++; //is first
            if(count($matches[0])>2){
                $s++; //more than 2
            }
        }
        preg_match_all('/[a-z]/', $this->plain, $matches, PREG_OFFSET_CAPTURE);
        if(isset($matches[0][0])){
            $p = $matches[0][0][1];
            $s++; //contains
            if(count($matches[0])>2){
                $s++; //more than 2
            }
        }
        preg_match_all('/[A-Z]/', $this->plain, $matches, PREG_OFFSET_CAPTURE);
        if(isset($matches[0][0])){
            $p = $matches[0][0][1];
            $s++; //contains
            if(count($matches[0])>2){
                $s++; //more than 2
            }
        }
        preg_match_all('/[@#$^?!&*_~=,:]/', $this->plain, $matches, PREG_OFFSET_CAPTURE);
        if(isset($matches[0][0])){
            $p = $matches[0][0][1];
            $s++; //contains
            if($p == 0) $s++; //is first
            if(count($matches[0])>2){
                $s++; //more than 2
            }
        }
        preg_match_all('/[ ]/', $this->plain, $matches, PREG_OFFSET_CAPTURE);
        if(isset($matches[0][0])){
            $s++; //contains
            if(count($matches[0])>1){
                $s++; //more than 1
            }
        }

        /*if($s < 0){
            $this->strength = $s;
            return 0;
        }*/
        $this->strength = $s;
        return $s;
    }
    //hasLetter
    ///hasnumber
    //hassymbol
    //haslength ( > 7)
    
}