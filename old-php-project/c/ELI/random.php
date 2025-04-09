<?php
/**
 * @author Edwards
 * @copyright  2015
 * 
 * Generate random items
 * 
 */
/**


*/
class ELI_random
{
    
    static function word($length=6){
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
    
    static function sentence($words =6, $max_letter_length=0) {
        $a = array();
        if($max_letter_length < ($words * 2)) $max_letter_length = 0;
        $c = 0; $lc = 0;
        $wl = mt_rand( 1, 8 ); 
        while($c < $words && ($max_letter_length>0 && $max_letter_length<$lc)) {
            $w = self::word($wl);
            $a[] = $w;
            $c ++;
            $lc += strlen($w);
            $m = $max_letter_length-$lc;
            if($m<3) $m = 3;
            $wl = mt_rand( 1, $m ); 
        }
        
        return implode(' ',$a);
    }
    static function paragraph($length=5,$max_letter_length=0) {
        $a = array();
        if($max_letter_length < ($length * 3)) $max_letter_length = 0;
        $c = 0; $lc = 0;
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
        
        return implode(' ',$a);
    }
    static $live = false;
    static $buffer = array();
    static function useLive($state) {
        
        
    }
    static function reCache() {
        //fetch a url
        //strin all tags
        //store in array
        
    }
    
} 
  
?>