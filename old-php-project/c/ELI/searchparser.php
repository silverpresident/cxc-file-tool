<?php
/**
 * Search Parser
 * 
 * ParseTerms
 *  should handle EXACT via ""
 *   NEG via ! NOT -  (the - must be preceed by a space or at the start to avoid compound words)
 *   POS via [SPACE] and AND 
 *   wild card via *
 * 
 * ParseExpression
 *   Expression allows all the above except FIELD can be specified via "FIELDS:*" e.g. title:apple
 * 
 * 
 */
 
 /*
 TEST CASES
 apple
 apple pine
 apple and pine
 apple or pine
 apple not pine
 apple +pine
 apple -pine
 +apple pine
 -apple and pine
 apple and -pine
 apple and not pine
 apple or not pine 
 apple | pine
 appl*
 appl and pin*
 *pple
 *pple or pine
 ap*le
 apple pin*
 "apple pie"
 "apple-pie"
 "apple+pie"
 "apple and pie"
 "apple or pie"
 "apple -pie"
 "apple +pie"
 apple &pie
 apple & pie
 title:pie
 title:>pie
 title:<pie
 title:=pie
 title:-pie
 title:+pie
 title:apple pie
 title:"apple pie"
 title:apple*pie
 title:"apple*pie"
 -title:pie
 -title:apple pie
 +title:pie
 "title":pie
 title=pie
 title:pie name:john
 title:pie and name:john
 title:pie -name:john
 
 */
class ELI_SearchParser
{
    private $expression = true;
    private $parsed = array();
    private $original = '';
    
    public function __construct($query, $expression=true) {
        
        if(func_num_args()==1){
            if(is_bool($query)){
                $expression = $query;
                $query = '';
            }
        }
        $this->expression = $expression;
        if($query){
            $this->parse($query);
        }
    }
    function clear(){
        $this->original = '';
        $this->parsed = array();
    }
    function parse($query){
        $this->clear();
        if(!$query) return array();
        $this->original = $query;
        $this->parseExpr($query);
        return $this->parsed;
    }
    function setExpression($on){
        $this->expression = (bool)$on;
    }
    function parsed(){
        return $this->parsed;
    }
    function original(){
        return $this->original;
    }
    function query_str(){
        //retyurn reonstructed expr;
        throw new Exception('Not yet implemented');
        //return $this->original;
    }

    private function parseTerm($str){
        //parse term
        #extract exact quoted terms
        $ArrWords = $this::CreateWordList($str);
        if ($ArrWords[0] == "AND" || $ArrWords[0] == "OR") {
            $ArrWords = $this::RemoveArrayElement($ArrWords,0);
        }
        $nextOp='AND';
        $res =array();
        foreach($ArrWords as $word){
            $u = strtoupper($word);
            $u = str_replace(array('&&','&'),'AND',$u);
            $u = str_replace(array('||','|'),'OR',$u);
            if($u=='AND' || $u=='OR' || $u=='NOT'){
                $nextOp= $u;
            }else{
                $a=array('type'=>3,'kind'=>'exact','operator'=>$nextOp);
                
                $cs = substr($word,0,1);
                $stri = substr($word,1);
                $stri = substr($stri,0,-1);
                
                //kind LOWERCASE text of TYPE [EXACT, LIKE, START, END]
                if(strpos($stri,'*') ){
                    $a['type']= 4; $a['kind'] = 'like';
                }elseif($cs=='*'){
                    $a['type']= 5; $a['kind'] = 'start';
                }elseif(substr($word,-1)=='*'){
                    $a['type']= 6; $a['kind'] = 'end';
                }
                
                
                
                
                $o = new ELI_SearchParserItem($a);
                $o->setTerm($word);
                if($o->word) $res[] = $o;
            }
        }
        return $res;
    }
    private function parseExpr($str){
        //find field and parse expression
        //field
        
        
        //normalize
        
        
        //FORBIDS QUERY TO HAVE TWO OR MORE ADJACENT SPACES
        $str = preg_replace("/(\s{2,})/"," ",$str);
    
        if($this->expression &&  $i = strpos($str,':')){
            $str = preg_replace("/(:{2,})/",":",$str);
            do{
                $f = substr($str,0,$i);
                $rest = substr($str,$i+1);
                if($i = strpos($rest,':')){
                    $s = strrpos($rest,' ',$i);
                    if($s>=0){
                        $str = substr($rest,$i+1);
                        $rest = substr($rest,0,$i);
                    }
                }else{
                    $str = '';
                }
                $a=array('type'=>1,'kind'=>'expr');
                $a['terms'] = $this->parseTerm($rest);
                $o = new ELI_SearchParserItem($a);
                $o->setField($f);
                $this->parsed[] = $o;
                $i = strpos($str,':');
            }while($i>-1);
        }else{
            $a=array('type'=>2,'kind'=>'terms');
            $a['terms'] = $this->parseTerm($str);
            $this->parsed[] = new ELI_SearchParserItem($a);
        }
    }
    
    private function CreateWordList($strQuery) {
        // http://www.idealguide.net/cms/search/beta/ViewCode.php
        //-----------------------------------------------------------------------\\
        //                   CreateWordList(string $strQuery)                    \\
        //  DESC: Returns a array containing the words the user wants to search  \\
        //        for, including boolean keywords and literal phrases.           \\
        //        This means, the array returned by this function may contain    \\
        //        only a keyword (AND,OR,NOT or similars) or contain more than   \\
        //        one word. This is necessary for proper work of the ParseQuery  \\
        //        function.                                                      \\
        //-----------------------------------------------------------------------\\
    
        $q = $strQuery;
        $q = stripslashes($q);
        $QueryLength = strlen($strQuery);
        for ($i=0;$i<$QueryLength-1;$i++) {
        if ($i >= strlen($q)) {
            break;
        }
            if ($q{$i} == " ") {
                $q{$i} = chr(0);
            }
            if ($q{$i} == '"') {
                $q{$i} = chr(0);
                $nextQuote = ($i!=$QueryLength) ? strpos($q,'"',$i+1) : $QueryLength;
                if ($nextQuote !== false) {
                    $q{$nextQuote} = chr(0);
                }
                if ($nextQuote != $QueryLength - 1) {
                    $i = $nextQuote+1;
                }
            }
        }
    
        if ($q{0} == chr(0)) {
            $q = $this::GetMidString($q,1,strlen($q));
        }
        
        $theArray = explode(chr(0),$q);
        $theArray = $this::ClearBlankElements($theArray);
        return $theArray;
    }
    static private function GetMidString($str,$iS,$iE) {
        //-----------------------------------------------------------------------\\
        //              GetMidString(string $str, int $iS, int $iE)              \\
        //  DESC: Returns the portion of $str starting at $iS and ending at $iE  \\
        //-----------------------------------------------------------------------\\
        return substr($str,$iS,$iE - $iS + 1);
    }
    
    private static function RemoveArrayElement($srcArray,$intEIndex) {
        //-----------------------------------------------------------------------\\
        //           RemoveArrayElement(array $srcArray, int $intEIndex          \\
        //  DESC: Removes the item $intEIndex from $srcArray and changes         \\
        //        all the keys from it in order not to leave any blank items     \\
        //        and returns the resultant array.                               \\
        //-----------------------------------------------------------------------\\
    
        $retArray = array();
        for ($i=0;$i<$intEIndex;$i++) {
            $retArray[$i] = $srcArray[$i];
        }
        
        for ($i=$intEIndex+1;$i<count($srcArray);$i++) {
            $retArray[$i-1] = $srcArray[$i];
        }
        
        return $retArray;
    }
    
    private static function ClearBlankElements($arr) {
        //-----------------------------------------------------------------------\\
        //                    ClearBlankElements(array $arr)                     \\
        //  DESC: Searches $arr for blank items (including items containing      \\
        //        only a white space) and removes them using the                 \\
        //        RemoveArrayElement function. Also trims the item value.        \\
        //-----------------------------------------------------------------------\\
        
        $thearr = array();
        foreach ($arr as $val) {
            if ($val != "" && $val != " ") {
                array_push($thearr,trim($val));
            }
        }
        return $thearr;
    }

}
class ELI_SearchParserItem{
    //type INTERGER []
    //kind LOWERCASE text of TYPE [EXACT, LIKE, START, END]
    //term
    //field
    //operator [AND OR NOT]
    protected $data = array();
    public function __construct($data=array()) {
        if(func_num_args() && is_array($data))
            $this->data = array_change_key_case($data,CASE_LOWER);
    }
    public function __get($name) {
        $name = strtolower($name);
        if(isset($this->data[$name]) || array_key_exists($name,$this->data))
            return $this->data[$name];
        
        return '';
    }
    public function __set($name, $value) {
        $name = strtolower($name);
        if((null ===$value))
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
        return print_r($this,1);
    }
    public function __call($name, $arguments) {
        if(in_array($name,array('field','type','kind','predicate','term','terms','operator'))){
            return $this->$name;
        }
        return false;
    }
    public function setField($word) {
        if($word){
            $f = 'field';
            $cs = substr($word,0,1);
            $cr = substr($word,1);
            $this->data[$f] = $word;
            
            if($cs=='+'||$cs=='&'){
                $this->data['predicate'] = 'MUST';
                $this->data[$f] = $cr;
            }elseif($cr=='-'||$cs == '!'){
                $this->data['predicate'] = 'NOT';
                $this->data[$f] = $cr;
            }
        }
    }
    public function setTerm($word) {
        if($word){
            $f = 'term';
            $cs = substr($word,0,1);
            $cr = substr($word,1);
            $this->data[$f] = $word;
            
            if($cs=='+'||$cs=='&'){
                $this->data['predicate'] = 'MUST';
                $this->data[$f] = $cr;
            }elseif($cr=='-'||$cs == '!'){
                $this->data['predicate'] = 'NOT';
                $this->data[$f] = $cr;
            }elseif($cr=='>'){
                $this->data['predicate'] = 'GT';
                $this->data[$f] = $cr;
            }elseif($cr=='<'){
                $this->data['predicate'] = 'LT';
                $this->data[$f] = $cr;
            }
        }
    }
    public function getTerm() {
        
    }
    
}
class ELI_SearchParserTransformer{
  
    private $escape = null;
    private $fields = array();
    private $default = '';
    public function setAcceptableFields($a =array()) {
        $this->fields = $a;
    }
    public function setDefaultField($word) {
        $this->default = $word;
    }
    public function setEscape($callable) {
        if(is_callable($callable)){
            $this->escape = $callable;
        }
    }
    public function getQueryString($eliParsed) {
        return implode(' ',$this->getQueryArray($eliParsed));
    }
    private function getDefault(){
        //raise error if not set
        return $this->default;
    }
    private function getField($field){
        if($field){
            if(count($this->fields)){
                if(in_array($field,$field)) return $field;
            }else{
                return $field;
            }
        }
        return $this->getDefault();
    }
    private function getEscape($v){
        if(is_numeric($v)) return $v;
        if(is_callable($this->escape)) return $this->escape($v);
        return $v;
    }
    public function getQueryArray($eliParsed) {
        $a =array();
        
        foreach($eliParsed as $item){
                $p = '';
                foreach($this->terms() as $it){
                    if($item->kind() =='terms'){
                        $f = $this->getField($it->field);
                        $p = $it->predicate;
                    }else{
                        $f = $this->getField($item->field);
                        $p = $item->predicate?$item->predicate:$it->predicate;
                    }
                    
                    $t = $this->getEscape($it->term);
                    $s = '';
                    if($this->kind=='exact'){
                        $s = "$f = '$t'";
                    }elseif($p=='GT'){
                        $s = "$f > '{$t}'";
                    }elseif($p=='LT'){
                        $s = "$f < '{$t}'";
                    }elseif($this->kind=='start'){
                        $s = "$f LIKE '{$t}%'";
                    }elseif($this->kind=='end'){
                        $s = "$f LIKE '%{$t}'";
                    }else/*if($this->kind=='like')*/{
                        $t =str_replace('*','%',$t);
                        $s = "$f LIKE '%{$t}%'";
                    }
                    if($p=='NOT'){
                        $s = "NOT ($s)";
                    }
                    if(count($a)>1){
                        if($this->operator =='OR'){
                            $s = "OR $s";
                        }else{
                            $s = "{$this->operator} $s";
                        }
                    }
                    $a[] = $s;
                }
        }
        return $a;
    }
      
}
?>