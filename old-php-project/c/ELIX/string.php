<?php
/**
 * @author Edwards
 * @copyright 2015
 * 
 * this class provides tools for string
 * https://github.com/nikic/scalar_objects
 * https://code.google.com/p/php-string/downloads/detail?name=string.php
 * https://github.com/alecgorge/PHP-String-Class/blob/master/string.php
 */
namespace ELIX;
if (!class_exists('LogicException')) {
    class LogicException extends Exception{}
}
if (!class_exists('BadFunctionCallException')) {
    class BadFunctionCallException extends LogicException{}
}
if (!class_exists('BadMethodCallException')) {
    class BadMethodCallException extends BadFunctionCallException{}
}

/*

<?php
  SIMILAR text  
   
   function sim($s1,$s2){
       if($s1 === $s2) return 100;
       
       $l1 = strlen($s1);
       $l2 = strlen($s2);
       $l0 = $l1+$l2;
       $ls = min($l1,$l2);
       if($ls ==0) return 0;
       if($l1==$l2){
           if($s1 < $s2){
                $os = $s2;
                $a = str_split ($s1);
            }else
            {
                $os = $s1;
                $a = str_split ($s2);
            }
       }else{
           if($ls == $l1){
                $os = $s2;
                $a = str_split ($s1);
            }else
            {
                $os = $s1;
                $a = str_split ($s2);
            }
       }
        $sc = 0;
        $i = 0;
        $cb = '';
        foreach($a as $c){
            $c2 = $os[$i];
            $i++;
            if($c == $c2){
                $sc+=2;
            }elseif(strtoupper($c) == strtoupper($c2)){
                $sc+=1;
            }elseif($c == $cb){
                $sc+=.5;
            }
            $cb=$c;
            
        }
        return ($sc / ($l0))*100;
       //print_r($a1);
   }
   
   echo "\nblank: " , sim("","");
   echo "\nblank: " , sim("a","a");
   echo "\nblank: " , sim("a","A");
   echo "\nblank: " , sim("aaa","aaa");
   echo "\nblank: " , sim("aaa","aaA");
   echo "\nblank: " , sim("aaa","aab");
   echo "\nblank: " , sim("aab","aaa");
   echo "\nblank: " , sim("aaa","abb");
   echo "\nblank: " , sim("abb","aaa");
   echo "\nblank: " , sim("a","ab");
   echo "\nblank: " , sim("a","b");
   echo "\nblank: " , sim("a","");
   
   echo "\n\n", sim("Mozilla/5.0 (Windows; U; Windows NT 6.0; en-US) AppleWebKit/533.1 (KHTML, like Gecko) Maxthon/3.0.8.2 Safari/533.1"
   ,"Mozilla/5.0 (Windows; U; Windows NT 6.1; en-US) AppleWebKit/532.4 (KHTML, like Gecko) Maxthon/3.0.6.27 Safari/532.4");
?>

*/

class string{
    protected $data = '';
    protected $_index = 0;
    
    public function __construct($s=null) {
        if(func_num_args()){
            $this->data = (string)$s;
        }
    }
    public function __get($key)
    {
        $key = strtolower($key);
        if ($key === 'length') {
            return $this->length();
        }
        /*if ($key === 'encoding') {
            return $this->getEncoding();
        }*/
        throw new BadMethodCallException('Undefined property.');
    }
    public function __toString() {
        return $this->data;
    }
    public function key()
    {
        return $this->_index;
    }
    public function count()
    {
        return $this->length();
    }
    public function current()
    {
        return $this->substr($this->_index,1);
    }
    public function next()
    {
        ++$this->_index;
    }
    /**
     * Rewind the Iterator to the first element.
     */
    public function rewind()
    {
        $this->_index = 0;
    }
    public function valid()
    {
        return ($this->_index >= 0 && $this->_index < $this->length());
    }
    
    
    /**
     * Checks if the string contains character at $offset.
     * Example:
     * <code>
     * <?php
     * $string = new String('example');
     * var_dump(isset($string[2])); // prints: bool(true)
     * ?>
     * </code>
     * @param int $offset character index, counting from zero.
     * @return bool
     */
    public function offsetExists($offset)
    {
        return ($offset >= 0 && $offset < $this->length());
    }
    
    /**
     * Provides array access for accessing characters.
     * Example:
     * <code>
     * <?php
     * $string = new String('offsetGet');
     * echo $string[3]; // prints: s
     * ?>
     * </code>
     * @param int $offset character index, counting from zero.
     * @uses String::charAt
     * @return String
     */
    public function offsetGet($offset)
    {
        return $this->charAt($offset);
    }
    
    /**
     * String is immutable. Calling this method will result in an exception.
     * @param int $offset
     * @param string $value
     * @throws BadMethodCallException
     */
    public function offsetSet($offset, $value)
    {
        throw BadMethodCallException();
    }
    
    /**
     * String is immutable. Calling this method will result in an exception.
     * @param int $offset
     * @param string $value
     * @throws BadMethodCallException
     */
    public function offsetUnset($offset)
    {
        throw BadMethodCallException();
    }
    
    public function toProper(){ return new self(ucwords($this->data)); }
    public function toTitle(){ return new self(ucfirst($this->data)); }
    public function toInt(){ return parseInt($this->data); }
    public function toFloat(){ return parseFloat($this->data); }
    public function toString(){ return (string)$this->data; }
    public function len(){ return strlen($this->data);}
    public function length(){ return strlen($this->data);}
    
    public function substr($start,$length=null){
        if(func_num_args()==1)
            return new self(substr($this->data,$start));
        else
            return new self(substr($this->data,$start,$length));
    }
    public function substring($start,$length=null){
        if(func_num_args()==1)
            return new self(substr($this->data,$start));
        else
            return new self(substr($this->data,$start,$length));
    }
    public function charAt($index)
    {
        return $this->substr($index, 1);
    }
    public function explode($delimiter){
        return explode($delimiter,$this->data);
    }
    public function indexOf($search,$offset=null){
        return strpos($this->data,$search,(int)$offset);
    }
    /**
     * Returns the index of the last occurance of $substr in the string.
     * In case $substr is not a substring of the string, returns false.
     * @param String $substr substring
     * @param int $offset
     * @return int|bool
     */
    public function lastIndexOf($substr, $offset = 0)
    {
        /*if (function_exists('mb_strrpos')) {
            $pos = mb_strrpos($this->data, (string)$substr, (int)$offset, $this->getEncoding());
        } else if ($this->getEncoding() === 'UTF-8' && function_exists('utf8_strrpos')) {
            $pos = utf8_strrpos($this->data, (string)$substr, ($offset === 0 ? null : $offset));
        } else if (function_exists('iconv_strrpos')) {
            $pos = iconv_strrpos($this->data, (string)$substr, (int)$offset, $this->getEncoding());
        } else {*/
            $pos = strrpos($this->data, (string)$substr, (int)$offset);
        //}
        return $pos;
    }
    public function insert($offset, $string)
    {
        return $this->splice($offset, 0, $string);
    }
    /**
     * Returns the leftmost $length characters of a string.
     * @param int $length number of characters.
     * @return String
     */
    public function left($length)
    {
        return $this->substr(0, $length);
    }
    public function right($length)
    {
        return $this->substr(-$length);
    }
    
    /**
     * Checks if the string is empty or whitespace-only.
     * @return bool true if the string is blank
     */
    public function isBlank()
    {
        return ($this->trim()->_string === '');
    }
    
    /**
     * Checks if the string is empty.
     * @return bool true if the string is empty
     */
    public function isEmpty()
    {
        return ($this->data === '');
    }
    public function isLowerCase()
    {
        return $this->equals($this->toLower());
    }
    public function isPalindrome()
    {
        return ($this->equals($this->reverse()));
    }
    /**
     * Checks is the string is unicase.
     * Unicase string is one that has no case for its letters.
     * @return bool true if the string is unicase
     */
    public function isUnicase()
    {
        return $this->toLowerCase()->equals($this->toUpperCase());
    }
    
    /**
     * Checks if the string is upper case.
     * String is considered upper case if all the characters are upper case.
     * @return bool true if the string is upper case
     */
    public function isUpperCase()
    {
        return $this->equals($this->toUpperCase());
    }
    public function replace( $search , $replace , int &$count =null )
    {
        return new self(str_replace($search , $replace, $this->data,$count));
    }
    public function ireplace( $search , $replace , int &$count =null )
    {
        return new self(str_ireplace($search , $replace, $this->data,$count));
    }
    public function concat( $newstr)
    {
        return new self($this->data . (string)$newstr);
    }
    public function match( $pattern,$flags=null,$offset=null)
    {
        preg_match_all($pattern,$this->data,$matches,$flags,$offset);
        return $matches;
    }
    public function contains($substr)
    {
        return ($this->indexOf($substr) !== false);
    }
    public function endsWith($substr)
    {
        $substr = new self($substr);
        return ($this->lastIndexOf($substr) === $this->length() - $substr->length());
    }
    public function equals($string)
    {
        return ($this->compareTo($string) === 0);
    }
    public function equalsIgnoreCase($string)
    {
        return ($this->compareToIgnoreCase($string) === 0);
    }
    public function compareTo($string, $length = null)
    {
        if ($length === null) {
            return strcmp($this->data, (string)$string);
        }
        return strncmp($this->data, (string)$string, (int)$length);
    }
    public function compareToIgnoreCase($string, $length = null)
    {
        if ($length === null) {
            return strncasecmp($this->data, (string)$string);
        }
        return strncasecmp($this->data, (string)$string, (int)$length);
    }
    public function format($arglist )
    {
        $args = array();
        if(func_num_args()==1){
            if(is_array($arglist)){
                $args = $arglist;
            }else{
                $args = func_get_args();
            }
        }else{
            $args = func_get_args();
        }
         
        return new self(vsprintf($this->data,$args));
    }
    /**
     * Removes all occurrences of a substring from the string.
     * @param string $substr substring
     * @param bool $regex whether $substr is a regular expression
     * @return String
     */
    public function remove($substr)
    {
        return $this->replace($substr, '');
    }
    
    public function removeDuplicates($substr)
    {
        $pattern = '/('.preg_quote($substr, '/').')+/';
        return $this->replaceRegex($pattern, $substr);
    }
    
    /**
     * Removes first occurrence of a substring from the string.
     * @param string $substr substring
     * @param bool $regex whether $substr is a regular expression
     * @return String
     */
    public function removeOnce($substr)
    {
        return $this->removeRegex($substr, 1);
    }
    
    public function removeRegex($pattern, $limit = null)
    {
        $this->replaceRegex($pattern, '', $limit);
    }
    
    public function removeSpaces()
    {
        return $this->remove(array(" ", "\r", "\n", "\t", "\0", "\x0B"));
    }
    
    public function replaceRegex($search, $replace, $limit = null)
    {
        $limit = (($limit === null) ? -1 : (int)$limit);
        $string = preg_replace($search, $replace, $this->data, $limit);
        return new self($string);
    }
    public function reverse()
    {
        /*if ($this->getEncoding() === 'UTF-8' && function_exists('utf8_strrev')) {
            $string = utf8_strrev($this->data);
        } else {
            $string = strrev($this->data);
        }*/
        $string = strrev($this->data);
        return new self($string);
    }
    
    /**
     * Removes a part of the string and replace it with something else.
     * Example:
     * <code>
     * $string = new String('The fox jumped over the lazy dog.');
     * echo $string->splice(4, 0, 'quick brown ');
     * </code>
     * prints 'The quick brown fox jumped over the lazy dog.'
     * @return String
     */
    public function splice($offset, $length = null, $replacement = '')
    {
        $count = $this->length();
        
        // Offset handling (negative values measure from end of string)
        if ($offset < 0) {
            $offset += $count;
        }
        
        // Length handling (positive values measure from $offset; negative, from end of string; omitted = end of string)
        if ($length === null) {
            $length = $count;
        } else if ($length < 0) {
            $length += $count - $offset;
        }

        return new self($this->substring(0, $offset) .
                        (string)$replacement .
                        $this->substring($offset + $length)
                        );
    }
    
    public function splitRegex($pattern)
    {
        $array = preg_split($pattern, $this->data);
        return $array;
    }
    /**
     * Removes extra spaces and reduces string's length.
     * Extra spaces are repeated, leading or trailing spaces.
     * It will also convert all spaces to white-spaces.
     * @return String
     */
    public function squeeze()
    {
        return $this
               ->replace(array("\r\n", "\r", "\n", "\t", "\0", "\x0B"), ' ')
               ->removeDuplicates(' ')
               ->trim()
               ;
    }
    
    /**
     * Checks if the string starts with a substring.
     * @param string $substr substring
     * @return bool true if the string starts with $substr.
     */
    public function startsWith($substr)
    {
        return ($this->indexOf($substr) === 0);
    }
    
    public function swapCase()
    {
        $string = '';
        $length = $this->length();
        for ($i = 0; $i < $length; $i++) {
            $char = $this->charAt($i);
            if ($char->isLowerCase()) {
                $string .= $char->toUpper();
            } else {
                $string .= $char->toLower();
            }
        }
        return new self($string);
    }
    /**
     * Converts the string to array.
     * Each element in the array contains one character.
     * @return array
     */
    public function toArray()
    {
        /*if ($this->getEncoding() === 'UTF-8' && function_exists('utf8_str_split')) {
            return utf8_str_split($this->data, 1);
        }*/
        return str_split($this->data, 1);
    }
    
    /**
     * Returns JSON representation of the string.
     * @return string
     */
     public function toJson()
     {
        return json_encode($this->data);
     }
     
    
    /**
     * Returns the literal value of the string.
     * @return string
     */
    public function valueOf()
    {
        return $this->data;
    }
    
    public function __call($name, $arguments) {
        $name=strtolower($name);
        if($name =='istr') $name='stristr';
        if($name =='wordcount') $name='word_count';
        if($name =='tolowercase') $name='tolower';
        if($name =='touppercase') $name='toupper';
        if($name =='trimend') $name='rtrim';
        if($name =='trimstart') $name='ltrim';
        if(in_array($name,array('trim','rtrim','ltrim','md5','sha1','crc32',
                            'lcfirst','ucfirst','lcwords','ucwords',
                            'stristr'))){
            array_unshift($arguments,$this->data);
            return new self(call_user_func_array($name,$arguments));
        }
        if(in_array($name,array('repeat','pad','rot13','shuffle'))){
            $fx = 'str_'.$name;
            array_unshift($arguments,$this->data);
            return new self(call_user_func_array($fx,$arguments));
        }
        
        if(in_array($name,array('chr','pbrk','rchr','rev','str','toupper','tolower','tr',))){
            $fx = 'str'.$name;
            array_unshift($arguments,$this->data);
            return new self(call_user_func_array($fx,$arguments));
        }
        $fx = 'str'.$name;
        if(function_exists($fx)){
            array_unshift($arguments,$this->data);
            return call_user_func_array($fx,$arguments);
        }
        $fx = 'str_'.$name;
        if(function_exists($fx)){
            array_unshift($arguments,$this->data);
            return call_user_func_array($fx,$arguments);
        }
        if(function_exists($name)){
            array_unshift($arguments,$this->data);
            return call_user_func_array($name,$arguments);
        }
        
        
        
    }
    /**
     * Returns String with the first string argument.
     * If no string is found, returns an empty String.
     * Example:
     * <code>
     * <?php
     * echo String::first(array(), 0, 'first', null, 'second'); // prints: first
     * ?>
     * </code>
     * @return String
     */
    public static function first()
    {
        $args = func_get_args();
        foreach ($args as $arg) {
            if (is_string($arg) || $arg instanceof self) {
                return new self($arg);
            }
        }
        return new self();
    }

}
function parseFloat($value) {
    return floatval(preg_replace("/[^-0-9.]/","",$value));

}
function parseInt($value) {
    return intval(preg_replace("/[^-0-9.]/","",$value));

}