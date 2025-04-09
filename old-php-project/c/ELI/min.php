<?php
/**
 * @author Edwards
 * @copyright  2011
 */
class ELI_min
{
    static function compressCss($buffer) {
        //http://www.php.net/manual/en/function.ob-start.php#71298
        $buffer = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $buffer); // remove comments
        $buffer = str_replace(array("\r\n", "\r", "\n", "\t", '  ', '    ', '    ', '  '), ' ', $buffer); // remove tabs, spaces, newlines, etc.
        $buffer = str_replace('{ ', '{', $buffer); // remove unnecessary spaces.
        $buffer = str_replace(' }', '}', $buffer);
        $buffer = str_replace('; ', ';', $buffer);
        $buffer = str_replace(', ', ',', $buffer);
        $buffer = str_replace(' {', '{', $buffer);
        $buffer = str_replace('} ', '}', $buffer);
        $buffer = str_replace(': ', ':', $buffer);
        $buffer = str_replace(' ,', ',', $buffer);
        $buffer = str_replace(' ;', ';', $buffer);
        return $buffer;
    }
    static function compressJS($buffer) {
        //http://castlesblog.com/2010/august/14/php-javascript-css-minification
        /* remove comments */
        $buffer = preg_replace("/((?:\/\*(?:[-^*]|(?:\*+[^*\/]))*\*+\/)|(?:\/\/.*))/", "", $buffer);
        /* remove tabs, spaces, newlines, etc. */
        $buffer = str_replace(array("\r\n","\r","\t","\n"), '', $buffer);
        /* remove other spaces before/after ) */
        $buffer = preg_replace(array('(( )+\))','(\)( )+)'), ')', $buffer);
        return $buffer;
    }
    static function removeComments($buffer) {
        //http://castlesblog.com/2010/august/14/php-javascript-css-minification
        /* remove comments */
        $buffer = str_replace(array("\t"), '', $buffer);
        $buffer = str_replace(array("\r\n","\r","\t","\n"), "\n", $buffer);
        //remove multi line
        $buffer = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $buffer); // remove comments
        //remove single line 
        //$buffer = preg_replace("/((?:\/\*(?:[^*]|(?:\*+[^*\/]))*\*+\/)|(?:\/\/.*))/", "", $buffer);
        
        $buffer = preg_replace(array('(( )+\))','(\)( )+)'), ')', $buffer);
        return $buffer;
    }
    static function removeComments_($buffer) {
        //http://castlesblog.com/2010/august/14/php-javascript-css-minification
        /* remove comments */
        $buffer = preg_replace("/((?:\/\*(?:[^*]|(?:\*+[^*\/]))*\*+\/)|(?:\/\/.*))/", "", $buffer);
        ///(?:([^\/"']+|\/\*(?:[^*]|\*+[^*\/])*\*+\/|"(?:[^"\\]|\\.)*"|'(?:[^'\\]|\\.)*')|\/\/.*)/

        return $buffer;
    }
} 
  
?>