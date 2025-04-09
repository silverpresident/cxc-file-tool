<?php
/**
 * @author Edwards
 * @copyright  2011
 * 
 */
/**
compiler options
is a text file
each line represents a derective
the first character reperents a command
the CWD is the dir of the compiler file
= means literal line to be placed on currect block
+ means include a file
& means include all files in a dir
. mean include all files in current directory EXCEPT this (the compiler file)
# is a compiler comment and will be omitted
% mean save grouped block into the file e.g.  'stuff.js' or '../stuff..js'
% with no param means clear current block
~ means echo the currecnt block and clear the buffer
@ mean compress the curret block, if followed by css to use basic css compression, js to use JS compression
! means remove comment same params as @
^ set the join character, by default it is a \n note the should be no space after the ^


*/
class ELI_js
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