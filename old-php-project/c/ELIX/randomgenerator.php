<?php
/**
 * @author Edwards
 * @copyright 2015
 * 
 * this class provides tools for generating randoom data
 * 
 * ELIX::randomgenerator()
 * ELIX::randomgenerator()->getGenerator($options);
 */
namespace ELIX;
//rand telphone

class randomgenerator{
    static function getGenerator($options = array()){
        return new randomgenerator_instance($options);
    }
    static function word($length=6){
        if($length == 0)$length = mt_rand( 2, 11 );
        
        if($length ==1){
            $words = array('a','e','i','o','u','y');
            return $words[array_rand($words)];
        }
        
        // consonant sounds
        $cons = array(
            // single consonants. Beware of Q, it's often awkward in words
            'b', 'c', 'd', 'f', 'g', 'h', 'j', 'k', 'l', 'm',
            'n', 'p', 'r', 's', 't', 'v', 'w', 'x', 'z',
            // possible combinations excluding those which cannot start a word
            'pt', 'gl', 'gr', 'ch', 'ph', 'ps', 'sh', 'st', 'th', 'wh', 'qu', 'kw'
        );
        // consonant combinations that cannot start a word
        $cons_cant_start = array(
            'ck', 'cm',
            'dr', 'ds',
            'ft',
            'gh', 'gn',
            'kr', 'ks',
            'ls', 'lt', 'lr',
            'mp', 'mt', 'ms',
            'ng', 'ns',
            'rd', 'rg', 'rs', 'rt',
            'ss',
            'ts', 'tch',
        );
       
       /*if($length < 4){
            $cons = array(
                // single consonants. Beware of Q, it's often awkward in words
                'b', 'c', 'd', 'f', 'g', 'h', 'j', 'k', 'l', 'm',
                'n', 'p', 'r', 's', 't', 'v', 'w', 'x', 'z',
                // possible combinations excluding those which cannot start a word
                'pt', 'gl', 'gr', 'ch', 'ph', 'ps', 'sh', 'st', 'th', 'wh', 'qu', 'kw'
            );
            $cons_cant_start = array(
                'ck', 'cm',
                'dr', 'ds',
                'ft',
                'gh', 'gn',
                'kr', 'ks',
                'ls', 'lt', 'lr',
                'mp', 'mt', 'ms',
                'ng', 'ns',
                'rd', 'rg', 'rs', 'rt',
                'ss',
                'ts', 'tch',
            );
       }*/
        // wovels
        $vows = array(
            // single vowels
            'a', 'e', 'i', 'o', 'u', 'y',
            // vowel combinations your language allows
            'ee', 'oa', 'oo',
        );
       
        // start by vowel or consonant ?
        $current = ( mt_rand( 0, 1 ) == '0' ? 'cons' : 'vows' );
       
        $word = '';
           
        while( strlen( $word ) < $length ) {
       
            // After first letter, use all consonant combos
            if( strlen( $word ) == 2 )
                $cons = array_merge( $cons, $cons_cant_start );
     
             // random sign from either $cons or $vows
            $rnd = ${$current}[ mt_rand( 0, count( ${$current} ) -1 ) ];
           
            // check if random sign fits in word length
            if( strlen( $word . $rnd ) <= $length ) {
                $word .= $rnd;
                // alternate sounds
                $current = ( $current == 'cons' ? 'vows' : 'cons' );
            }
        }
       
        return $word;
    }
    
    static function sentence($words =0, $max_letter_length=0) {
        if($words == 0)$words = mt_rand( 4, 7 );
        $a = array();
        if($max_letter_length < ($words * 2)) $max_letter_length = 0;
        /*$c = 0;
        $lc = 0;
        $wl = mt_rand( 1, 8 ); 
        while($c < $words && ($max_letter_length>0 && $max_letter_length>$lc)) {
            $w = self::word($wl);
            $a[] = $w;
            $c ++;
            $lc += strlen($w);
            $m = $max_letter_length-$lc;
            if($m<3) $m = 3;
            $wl = mt_rand( 1, $m ); 
        }*/
        $lc = 0;
        $minLet = 3;
        $maxLet = mt_rand( 1, 11); 
        for($i=0;$i<$words;$i++){
            $w = self::word(mt_rand( $minLet, $maxLet ));
            $a[] = $w;
            $lc += strlen($w);
            if($max_letter_length){
                if($lc >= $max_letter_length){
                    break;
                }
                $maxLet = $max_letter_length-$lc;
                if($maxLet < $minLet){
                    $maxLet = $minLet;
                    $minLet = 1;
                }
            }
        }
        return implode(' ',$a);
    }
    static function paragraph($sentences=5,$max_letter_length=0) {
        if($sentences == 0)$sentences = mt_rand( 3, 6 );
        $a = array();
        if($max_letter_length < ($sentences * 11)) $max_letter_length = 0;
        
        $lc = 0;
        $minWords = 3;
        $maxWords = mt_rand( 4, 11); 
        for($i=0;$i<$sentences;$i++){
            $w = self::sentence(mt_rand( $minWords, $maxWords ));
            $a[] = ucfirst($w) .'.';
            $lc += strlen($w);
            if($max_letter_length){
                if($lc >= $max_letter_length){
                    break;
                }
                $maxWords = $max_letter_length-$lc;
                if($maxWords < $minWords){
                    $maxWords = $minWords;
                    $minWords = 2;
                }
            }
        }
        
        /*
        
        
        $c = 0; 
        $wl = mt_rand( 4, 8 ); 
        while($c < $length && ($max_letter_length>0 && $max_letter_length<$lc)) {
            $w = self::sentence($wl);
            $a[] = ucfirst($w) .'.';
            $c ++;
            $lc += strlen($w);
            $m = $max_letter_length-$lc;
            if($m<3) $m = 3;
            $wl = mt_rand( 3, $m ); 
        }
        */
        return implode(' ',$a);
    }
    private static function get_url_contents($url){
        if(ini_get('allow_url_fopen')){
            return file_get_contents($url);
        }
        $ch = curl_init();
        curl_setopt ($ch, CURLOPT_URL, $url);
        curl_setopt ($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt ($ch, CURLOPT_RETURNTRANSFER, true);
        $contents = curl_exec($ch);
        if (curl_errno($ch)) {
          $contents = '';
        }
        curl_close($ch);
        return $contents;
    }
    
    static function lipsum($amount = 1, $what = 'paras', $start = 0) {
        $what =strtolower($what);
        if(!in_array($what,array('paras','words','bytes','lists')))
            $what = 'paras';
        try{    
            $contents = self::get_url_contents("http://www.lipsum.com/feed/xml?amount=$amount&what=$what&start=$start");
            return simplexml_load_string($contents)->lipsum;
        }catch(Exception $e){
            return '';
        }
    }
/*
(integer) - The number of paragraphs to generate.
short, medium, long, verylong - The average length of a paragraph.
decorate - Add bold, italic and marked text.
link - Add links.
ul - Add unordered lists.
ol - Add numbered lists.
dl - Add description lists.
bq - Add blockquotes.
code - Add code samples.
headers - Add headers.
allcaps - Use ALL CAPS.
prude - Prude version.
plaintext - Return plain text, no HTML.
*/
    static function loripsum($params=array(3,'plaintext','prude')) {
        if(is_array($params)){
            $p = implode('/',$params);
        }else{
            $p = (string)$params;
        }
        $p =str_replace(array(' ',',',';'),'/',$p);
        $x = explode('/',$p);
        sort($x);
        
        try{
            $p = implode('/',$x);
            return self::get_url_contents("http://loripsum.net/api/$p");
        }catch(Exception $e){
            return '';
        }
    }
    static function name($fullname=false,$gender=0) {
        $r = self::random(2);
        if($r == 1){
            $l = self::random(8);
            $r = 2+self::random(9);
            $n[] = ucfirst(self::word($l));
            if($fullname)$n[] = ucfirst(self::word(7));            
        }else{
            $n[] = self::firstname($gender);
            if($fullname)$n[] = self::surname();
        }
        
        return implode(' ',$n);
    }
    private static function _femaleNames() {
        static $n = null;
        if($n === null){
            $txt = file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . 'random-name-female.txt');
            $txt = str_replace(array(' ',"\n"),'',$txt);
            $n =explode(',',$txt);
        } 
        return $n;
    }
    private static function _maleNames() {
        static $n = null;
        if($n === null){
            $txt = file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . 'random-name-male.txt');
            $txt = str_replace(array(' ',"\n"),'',$txt);
            $n =explode(',',$txt);
        } 
        return $n;
    }
    private static function _surNames() {
        static $n = null;
        if($n === null){
            $txt = file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . 'random-name-last.txt');
            $txt = str_replace(array(' ',"\n"),'',$txt);
            $n =explode(',',$txt);
        } 
        return $n;
    }
    static function firstname($gender=0) {
        if($gender==1){
            $n = self::_maleNames();
        }elseif($gender==2){
            $n = self::_femaleNames();
        }else{
            $n = array_merge(self::_maleNames(),self::_femaleNames());
        }
        return $n[array_rand($n)];
    }
    static function surname() {
        $n = self::_surNames();
        return $n[array_rand($n)];
    }
    static function lastname() {
        static $pref = null;
        static $suf = null;
        
        $r = self::random(2);
        $l = self::random(8);
        if($r == 1){
            if($pref === null){
                $pref =array('Mac','Mc','O','ab','ap','an','wa','arap','ibn','bet','bar','ben','fitz','van','von','de','a');
            }
            $n[] = $pref[array_rand($pref)];
        }
        $n[] = ucfirst(self::word($l));
        if($r == 2){
            if($suf === null){
                $suf =array('i','ian','son','sen','de','en','vich','ez','tos','ides','kes','fi');
            }
            $n[] = $suf[array_rand($suf)];
        }
        return implode('',$n);
    }
    private static function random($max =9,$min=0){
        if ($min>$max) { $a=$min; $min=$max; $max=$a; }
        return mt_rand( $min,$max );
    }
    static function dateofbirth($minAge=10,$maxAge=0) {
        if(!$minAge) $minAge = self::random(9);
        if($maxAge < $minAge) $maxAge = $minAge;
        $age = mt_rand($minAge,$maxAge);
        $ts = time();
        $ts -= (31557600 * $age);
        return date_create("@{$ts}")->format('Y-m-d');
    }
    static function date($daysAgo=15,$daysFuture=15) {
        $ts = time();
        $tsMin = $ts - (86400 * $daysAgo);
        $tsMax = $ts + (86400 * $daysFuture);
        $ts = mt_rand($tsMin,$tsMax);
        return date_create("@{$ts}")->format('Y-m-d');
    }
    static function time($minSeconds=0,$maxSeconds =86400, $return_seconds =false) {
        if(func_num_args()==0){
            return mt_rand(0,23).":".str_pad(mt_rand(0,59), 2, "0", STR_PAD_LEFT);
        }
        if($minSeconds < 0){
            $minSeconds = 0;
        }
        if($maxSeconds > 86400){
            $maxSeconds = 86400;
        }
        if(($minSeconds==0) && ($maxSeconds ==86400)){
            if($return_seconds){
                return mt_rand(0,23).":".str_pad(mt_rand(0,59), 2, "0", STR_PAD_LEFT).":".str_pad(mt_rand(0,59), 2, "0", STR_PAD_LEFT);
            }else{
                return mt_rand(0,23).":".str_pad(mt_rand(0,59), 2, "0", STR_PAD_LEFT);
            }
        }
        $ts = mt_rand($minSeconds,$maxSeconds);
        return sprintf($return_seconds?"%02d:%02d:%02d":"%02d:%02d", floor($ts/3600), ($ts/60)%60,  $ts%60);

    }
    static function getDateObject($daysAgo=15,$daysFuture=15) {
        $ts = time();
        $tsMin = $ts - (86400 * $daysAgo);
        $tsMax = $ts + (86400 * $daysFuture);
        $ts = mt_rand($tsMin,$tsMax);
        return date_create("@{$ts}");
    }
    public function int($max =100,$min=0) {
        return self::random($max,$min);
    }
    public function letter($case=0) {
        if($case == CASE_LOWER){
            $i = self::random(122,97);
        }else if($case == CASE_UPPER){
            $i = self::random(90,65);
        }else{
            $i = self::random(122,65);
            if($i > 90 && $i<97){
                $i = self::random(90,65);
            }
        }
        return chr($i);
    }
    public function character() {
        $i = self::random(126,33);
        return chr($i);
    }
    public function digit(){
        return self::random(9,0);
    }
    public function real($round=2,$max=9,$min=0){
        if($round > 9){
            $round = 9;
        }
        if($round < 0){
            $round = 1;
        }
        $randomfloat = $min + mt_rand() / mt_getrandmax() * ($max - $min);
        if($round){
            return round($randomfloat,$round);
        }
        return $randomfloat;
    }
    public function seed($seed=null){
        if($seed === null){
            $seed = time();
        }
        $seed = abs(intval($seed)) % 9999999 + 1;
        mt_srand($seed);
    }
}

class randomgenerator_instance{
    protected $data = array();
    static $last_para = '';
    /*
    options: 
    min
    max
    length
    max_length
    max_word_length
    max_sentence_length
    vary_length :default true
    vary_word_length :default true
    vary_sentence_length :default true
    vary_paragraph_length
    use_lorem  :default true
    punctuate :default true  (cap first letter and fullstop for entence)
    */
    public function __invoke($obj) {
        //MAY this should return a new object with options merged
    }
    public function int() {
        
    }
    public function letter() {
        
    }
    public function character() {
        
    }
    public function bool(){
        
    }
    public function real(){
        
    }
    public function toArray(){
        $a = $this->data;
        if(func_num_args()){
            $f = func_get_arg(0);
            if(is_array($f)){
                $f = array_map('strtolower', $f);
                foreach($a  as $name=>$v){
                    if(!in_array($name,$f)) unset($a[$name]);
                }
            }
        }
        return $a;
    }
    public function __construct($data=array()) {
        if(func_num_args() && is_array($data))
            $this->data = array_change_key_case($data,CASE_LOWER);
    }
    public function __get($name) {
        $name = strtolower($name);
        if(isset($this->data[$name]) || array_key_exists($name,$this->data))
            return $this->data[$name];
        if(method_exists($this,$name))
            return $this->$name();
        return '';
    }
    public function __set($name, $value) {
        $name = strtolower($name);
        if($value===null)
            unset($this->data[$name]);
        else
            $this->data[$name] = $value;
    }
    public function __unset($name) {
        $name = strtolower($name);
        unset($this->data[$name]);
    }
    public function __isset($name) {
        $name = strtolower($name);
        return isset($this->data[$name]);
    }
    public function __toString() {
        if(method_exists($this,'tostring')) return $this->toString();
        $c = get_called_class();
        return "@ $c ($this->id)";
    }
    public function __call($name, $arguments) {
        $name =strtolower($name);
        if($name == 'tostring' ) return $this->__toString();
        if(isset($this->data[$name]) || array_key_exists($name,$this->data)) return $this->data[$name];
        
        $c = get_called_class();
        trigger_error("method $c -> $name which does not exist");
        return '';
    }
    public function word() {
        
    }
    public function sentence() {
        
    }
    public function paragraph() {
        
    }
    public function lorem() {
        
    }
}