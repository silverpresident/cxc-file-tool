<?php
/**
 * @author Edwards
 * @copyright 2015
 * @used  
 * 
 * 
 */
namespace ELIX;

class CAPTCHA{
    public function getMath()
    {
        return new math_captcha();
    }
    public function getText()
    {
        return new text_captcha();
    }
    public function getQuiz()
    {
        return new quiz_captcha();
    }
    public function getImage()
    {
        return new image_captcha();
    }
    public function get()
    {
        return new image_captcha();
    }
    public function getRandom()
    {
        
        $n1 = rand(0,35);
        if($n1>=30) return $this->getImage(); 
        elseif($n1>=25) return $this->getMath();
        elseif($n1>=18) return $this->getText();
        elseif($n1>=12) return $this->getQuiz();
        else return $this->get();
        
        return $o;
    }
    public function getRandomNoImage()
    {
        
        $n1 = rand(0,27);
        if($n1>=25) return $this->getMath(); 
        elseif($n1>=18) return $this->getText();
        elseif($n1>=12) return $this->getQuiz();
        else return $this->getMath();
        
        return $o;
    }
}
class captcha_base
{
    protected $data = array();
    function type() { return ''; }
    function answer() {
        $name = __FUNCTION__;
        if(isset($this->data[$name])) return $this->data[$name];
        return ''; 
    }
    function question() {
        $name = __FUNCTION__;
        if(isset($this->data[$name])) return $this->data[$name];
        return ''; 
    }
    function length() { return  strlen($this->answer() ); }
    function maxlength() { return  strlen($this->answer() ) + 2 ; }
    function q() { return $this->html; }
    public function __get($name) {
        $name = strtolower($name);
        if(isset($this->data[$name]) || array_key_exists($name,$this->data))
            return $this->data[$name];
        if(method_exists($this,$name))
            return $this->$name();
        return '';
    }
}
class math_captcha extends captcha_base
{
    public $allow_plus = true;
    public $allow_divide = true;
    public $allow_minus = true;
    public $allow_multiply = true;
    public $allow_word = true;
    public $max = 30;
    public $min = 0;
    
    public function type() { return 'math'; }
    public function max(){
        $mn = $this->min();
        $mx = (int)$this->max;
        if($mn > $mx) $mx = $mn+30;
        if($mx < 0) $mx = 30;
        if(($mx-$mn) < 20) $mx = $mn + 20;
        return $mx;
    }
    public function min(){
        return (int)$this->min;
    }
    public function generate(){
        $words = array();
        if($this->allow_divide) $words[] = '/';
        if($this->allow_multiply) $words[] = '*';
        if($this->allow_plus) $words[] = '+';
        if($this->allow_minus) $words[] = '-';
        
        if(count($words)==0){
            $words = array('-','+');
        }
        $r1 = array_rand($words);
        $op = $words[$r1];
        $mn = $this->min();
        $mx = $this->max();
        
        if($op == '*'){
            $n2 = rand(1,($mx*.2));
            if($n2 > 10){
                $n1 = rand(1,3);
            }else{
                $n1 = rand($mn,($mx*.5));
            }
            $a = $n1 * $n2;
        }elseif($op == '/'){
            srand ((double) microtime() * 1000000);
            $n2 = rand(1,($mx*.2));
            $n1 = rand($mn,($mx*.5));
            
            $c=0;
            while($n1 % $n2 !=0){
                $n1--;
                if($c++ > 5)$n1 = $n2 *rand(1,4) ;
                if($c++ > 3)$n1 = rand(6,($mx*.6));
            }
            if($n1==0)$n1=$n2;
            $a = $n1 / $n2;
        }else{
            $a = rand($mn,$mx);
            $n2 = rand(0,($mx*.8));
            if($op=='-'){
                $n1 = $a+$n2;
                if($n1 < 0){
                    $n1 =abs($n1);
                    $a = $n1+$n2;
                }
            }else{
                $n1 = $a-$n2;
                if($n1 < 0){
                    $n1 =abs($n1);
                    $a = $n1-$n2;
                }
                if($n1 < $n2){
                    $a = $n1;
                    $n1 =$n2;
                    $n2 = $a;
                    $a = $n1-$n2;
                }
            }
        }
        
        $words['*'] = 'times';
        $words['+'] = 'plus';
        $words['-'] = 'minus';
        $words['/'] = 'divided by';
        
        $this->data['op'] = $op;
        $this->data['n1'] = $n1;
        $this->data['n2'] = $n2;
        $this->data['answer'] = $a;
        
        $r1 = rand(1,4);
        if($r1 == 3)$words['*'] = 'multiplied by';
        if($r1 > 2){
            $op = $words[$op];
        }elseif($op == '*'){
            $op = '&times;'; //&#215;
        }elseif($op == '/'){
            $op = '&divide;'; //&#247;
        }
        $this->data['operation'] = $op;
        $this->data['question'] = "What is $n1 $op $n2?";
        $this->data['html'] = "What is <strong>$n1</strong> $op <strong>$n2</strong>?";
        $this->data['input'] = 'number';
        return $this;
    }
}
class text_captcha extends captcha_base
{
    function type() { return 'text'; }
    public function generate(){
        $r1 = rand(1,10);
        $r2 = rand(1,9);
        $this->data['input'] = 'text';
        if($r1 == 2)
        {
            $words = array('cat','cut','boy','bay','buy','girl','pen','pan','pin','pun','pet',
            'hat','hit','hot','hut','dog','dig','man','men','sad','van','fan','fun','gun',
            'jug','jaw','lot','mat','mug','nut','nest','rat','run','set');
            $a = $words[array_rand($words)];
            $this->data['answer'] = $a;
            $this->data['html'] = "Type <em>$a</em>";
        }elseif($r1 == 3){
            $q = array('1','2','4','5','6','7','9','10','1');
            $words = array('one','two','four','five','six','seven','nine','ten','one');
            $s = $q[$r2-1];
            $a = $words[$r2-1];
            $this->data['answer'] = $a;
            $this->data['html'] = "Spell <em>$s</em> ";
        }elseif($r1 == 4)
        {
            $words = array('head','hand','foot','eye','ear','chin','finger','leg','hair');
            $r1 = array_rand($words);
            $decoy = array('cat','bed','car','pen','hat','dog','man','sad','van');
            $rk = array_rand($decoy, 2);
            $i[0] = $a = $words[$r1];
            $i[1] = $decoy[$rk[0]];
            $i[2] = $decoy[$rk[1]];
            shuffle($i);
            $this->data['answer'] = $a;
            $this->data['html'] = "Which is a body part <em>$i[1]</em>, <em>$i[2]</em> or <em>$i[0]</em>?";
        }elseif($r1 == 5)
        {
            $words = array('Monday','Tuesday','Sunday','Thursday','Friday');
            $r1 = array_rand($words);
            $decoy = array('Cat','Bed','Car','Head','January','Frogs','Lizard','Apple','Finger');
            $rk = array_rand($decoy, 2);
            $i[0] = $a = $words[$r1];
            $i[1] = $decoy[$rk[0]];
            $i[2] = $decoy[$rk[1]];
            shuffle($i);
            $this->data['answer'] = strtolower($a);
            $this->data['html'] = "Which is a day of the week <em>$i[1]</em>, <em>$i[2]</em> or <em>$i[0]</em>?";
        }
        else
        {
            $words = array('apple','mango','pear','orange','banana','plum');
            $r1 = array_rand($words);
            $decoy = array('cat','bed','car','pen','Tuesday','hand','Wednesday','hair','van');
            $rk = array_rand($decoy, 2);
            $i[0] = $a = $words[$r1];
            $i[1] = $decoy[$rk[0]];
            $i[2] = $decoy[$rk[1]];
            shuffle($i);
            $this->data['answer'] = strtolower($a);
            $this->data['html'] = "Which is a fruit <em>$i[1]</em>, <em>$i[2]</em> or <em>$i[0]</em>?";
        }
        $this->data['question'] = strip_tags($this->data['html']);
        return $this;
    }
}
class quiz_captcha extends captcha_base
{
    function type() { return 'quiz'; }
    public function generate(){
        $r1 = rand(1,9);
        $this->data['input'] = 'number';
        if($r1 == 2){
            $i[0] = rand(10,100);
            $i[1] = rand($i[0],$i[0]*2);
            $i[2] = rand(50,100);
            shuffle($i);
            $a = min($i);
            $this->data['answer'] = $a;
            $this->data['html'] = "Which is the smallest <em>$i[1]</em>, <em>$i[2]</em> or <em>$i[0]</em>?";
            
        }elseif($r1 == 3){
            $i[0] = rand(10,100);
            $i[1] = rand($i[0],$i[0]*2);
            $i[2] = rand(50,100);
            shuffle($i);
            $a = max($i);
            $this->data['answer'] = $a;
            $this->data['html'] = "Which is the largest <em>$i[1]</em>, <em>$i[2]</em> or <em>$i[0]</em>?";
            
        }elseif($r1 == 4){
            $words = array('apple','mango','Tuesday','cat','chess','royal','answer','word','today','tuesday','file','computer');
            $r1 = array_rand($words);
            $s = $words[$r1];
            $a = strlen($s);
            $this->data['answer'] = $a;
            $this->data['html'] = "How many letters are in '<em>$s</em>'";
        }else{
            $s = (string)rand(100,999999);
            $r2 = rand(1,strlen($s))-1;
            $a = $s[$r2];
            if($a==0) $s[$r2] =$a=rand(1,9);
            $r2+=1;
            $arr=array(1=>'first','second','third','fourth','fifth','sixth','seventh');
            $p = $arr[$r2];
            
            $this->data['answer'] = $a;
            $this->data['html'] = "In <em>$s</em>, what is the <em>$p</em> digit?";
        }
        $this->data['question'] = strip_tags($this->data['html']);
        return $this;
    }
}
class image_captcha extends captcha_base
{
    public $charset = '';
    public $allow_alpha = true;
    public $allow_case = true; //2 = lower only, 4 = upper, 1 or true = both
    public $allow_num = true;
    public $allow_symbol = true;
    public $allow_bg = true;
    public $allow_decoy = 0; //1 mix, 2 place at end
    public $decoy_color = '0,0,0';
    public $text_color = '255,0,0';
    public $bg_color = '255,255,255';
    public $length = 4;
    public $use_png = true;
    protected $img = null;
    
    public function __destruct() {
        if($this->img === null) return;
        ImageDestroy($this->img);
    }

    public function type() { return 'image'; }
    public function html() { return $this->img(); }
    public function question() { return $this->img(); }
    public function charset(){
        if(strlen($this->charset)>5) return $this->charset;
        $c=array();
        if($this->allow_symbol){
            $c[] = '#=@$%';
        }
        if($this->allow_num){
            $c[] = '123456789';
        }
        if($this->allow_alpha){
            $a = 'abcdefghjkmnpqrstuvwxyz';
            if($this->allow_case === 2){
                $c[] = $a; 
            }elseif($this->allow_case > 2){
                $c[] = strtoupper($a) . 'L'; 
            }else{
                $c[] = $a;
                $c[] = strtoupper($a) . 'L'; 
            }
        }
        return implode('',$c);
    }
    public function imageHref(){
        return $this->imageDataUri();
    }
    public function datauri(){
        return $this->imageDataUri();
    }
    public function imageDataUri(){
        if(isset($this->data['datauri']) && $this->data['datauri']) return $this->data['datauri'];
        ob_start();
        $im = $this->getImage();
        if($this->use_png){
            $mt = 'image/png';
            imagepng($im,NULL,9);
        }else{
            $mt = 'image/jpeg';
            imagejpeg($im,NULL,10);
        }
        $imageData = ob_get_clean();
        $imageData = base64_encode($imageData);
        $this->data['datauri'] = 'data:'.$mt.';base64,'.$imageData;
        return $this->data['datauri'];
    }
    public function img(){
        $a='';
        $p = $this->imageDataUri();
        return "<img src='$p' alt=\"$a\" />";
    }
    
    public function generate(){
        $l = (int)$this->length;
        if($l <2) $l =3;
        if($l >12) $l =10;
        
        $this->img = null;
        unset($this->data['datauri']);
        $this->data['answer'] = $this->getRandom($l);
        if($this->allow_decoy){
            $this->data['decoy'] = $this->getRandom($l-1);
        }
        return $this;
    }
    private function getRandom($length=6)
    {
        $cset = $this->charset();
        $slen = strlen($cset)-1;
        if($slen <2) return $cset;
        
    	srand ((double) microtime() * 1000000);
    	$random_string = "";
        $exclude =array();
    	for($i=0;$i<$length;$i++)
        {
            $i1 = rand(0,$slen);
            //do{
                $c = $cset[$i1];
            //}while(in_array($c,$exclude)) ;
    		$random_string.= $c; 
    	}
    	return $random_string;
    }
     public function getImage(){
        if($this->img !== null) return $this->img;
        
        $p = __DIR__ . DIRECTORY_SEPARATOR;
        
        $file = '';
        if($this->allow_bg){
            $files =array('captcha.jpg','captcha2.jpg','captcha3.jpg','captcha4.jpg','captcha5.jpg','');
            $file = $files[array_rand($files)];
            if($file) $file = "$p{$file}";
        }
        $font = "{$p}monofont.ttf";
        
        $l = strlen($this->answer);
        $ld = strlen($this->decoy);
        $w = 14 * ($l+$ld);
        $h = 21; //27
        if($w<28) $w = 14 * 7;
        if($file && file_exists($file)){
            $im = imagecreatefromjpeg($file);
            #resize image
            $cw= imagesx($im);
            $ch = imagesy($im);
            $new_image = imagecreatetruecolor($w, $h);
            imagecopyresampled($new_image, $im, 0, 0, 0, 0, $w, $h, $cw, $ch);
            $im = $new_image;
            unset($new_image);
            
        }else if ($this->allow_bg){
            $im  = imagecreatetruecolor($w, $h);
            $col = ImageColorAllocate($im, 255,255,255);
            imagefill($im, 0, 0, $col);
            $col1 = ImageColorAllocate($im, 0, rand(100,255),0);
            $col2 = ImageColorAllocate($im, rand(200,255), 215,0);
            $y1=$y2 =$x1 = $x2 =0;
            for($i = 1; $i<5;$i++){
                $x1 = rand($x1,$w);
                $x2 = rand($x2,$w);
                $y1 = rand($y1,$h);
                $y2 = rand($y2,$h);
                imageline ($im , $x1 , $y1 , $x2 , $y2 , $col1 );
                imageline ($im , $y1 , $x1 , $y2 , $x2 , $col2 );
                $a = 0;
                if($x1 % 2){
                    $x1 = rand(0,$w);
                    $y1 = rand(0,$h);
                    $a = 1;
                }
                if($x2 % 2){
                    $x2 = rand($x1,$w);
                    $y2 = rand($y1,$h);
                    $a = 1;
                }
                if($a)imageline ($im , $x1 , $y1 , $x2 , $y2 , $col2 );
                if($i % 2){
                    $y1=$y2 =$x1 = $x2 =0;
                }
            }
            for($i = 1; $i<15;$i++){
                $x1 = rand($x1,$w);
                $y1 = rand($y1,$h);
                if($x1 % 2){
                    imagefilledellipse($im , $x1 , $y1 , 2,2 , $col2 );
                    $x1 = rand(0,$w);
                    $y1 = rand(0,$h);
                }
                imagefilledellipse($im , $x1 , $y1 , 2,2 , $col1 );
                if($i % 5){
                    $y1 =$x1  =0;
                }
            }
            for($i = 1; $i<5;$i++){
                $x1 = rand(0,$w);
                $x2 = rand(0,$w);
                $y1 = rand(0,$h);
                $y2 = rand(0,$h);
                imageline ($im , $x1 , $y1 , $x2 , $y2 , $col2 );
                $x1 = rand(0,$w);
                $y1 = rand($y1,$h);
                imagefilledellipse($im , $x1 , $y1 , 2,2 , $col1 );
            }
        }else{
            
            $im  = imagecreatetruecolor($w, $h);
            if($this->bg_color){
                $c = is_array($this->bg_color)?$this->bg_color:explode(',',$this->bg_color);
                if(!isset($c[0])) $c[0] = rand(50,150);
                if(!isset($c[1])) $c[1] = rand(1,100);
                if(!isset($c[2])) $c[2] = rand(100,200);
                for($i=0;$i<3;$i++){
                    $c[$i] = (int)$c[$i];
                    if($c[$i]<0) $c[$i] = 0;
                    if($c[$i]>0) $c[$i] = 255;
                }
                $col = ImageColorAllocate($im, $c[0], $c[1],$c[2]);
            }else{
                $col = ImageColorAllocate($im, 255, 255,255);
            }
            imagefill($im, 0, 0, $col);
        }
        
            
        if($this->text_color){
            $c = is_array($this->text_color)?$this->text_color:explode(',',$this->text_color);
            if(!isset($c[0])) $c[0] = rand(50,150);
            if(!isset($c[1])) $c[1] = rand(1,100);
            if(!isset($c[2])) $c[2] = rand(100,200);
            for($i=0;$i<3;$i++){
                $c[$i] = (int)$c[$i];
                if($c[$i]<0) $c[$i] = 0;
                if($c[$i]>0) $c[$i] = 255;
            }
            $col_text = ImageColorAllocate($im, $c[0], $c[1],$c[2]);
        }else{
            $col_text = ImageColorAllocate($im, 255, 0, 0);
        }
        $ans = $this->answer;
        if($ans && $this->allow_decoy && $this->decoy){
            if($this->decoy_color){
                $c = is_array($this->decoy_color)?$this->decoy_color:explode(',',$this->decoy_color);
                if(!isset($c[0])) $c[0] = rand(50,150);
                if(!isset($c[1])) $c[1] = rand(1,100);
                if(!isset($c[2])) $c[2] = rand(100,200);
                for($i=0;$i<3;$i++){
                    $c[$i] = (int)$c[$i];
                    if($c[$i]<0) $c[$i] = 0;
                    if($c[$i]>0) $c[$i] = 255;
                }
                $col_decoy = ImageColorAllocate($im, $c[0], $c[1],$c[2]);
            }else{
                $col_decoy = ImageColorAllocate($im, 0, 0, 0);
            }
            $rand = '';
            $decoy = $this->decoy;
            for($i=0;$i<$ld;$i++)
            {
                $rand .= $decoy[$i].' ';
            }
            if(file_exists($font))
                imagettftext($im, 20, 0, 7,19,$col_decoy,$font,$rand);
            else
                imagestring($im, 5, 8, 5,$rand,$col_decoy);
                
            $rand = '';
            
            for($i=0;$i<$l;$i++)
            {
                $rand .= ' '.$ans[$i];
            }
            if(file_exists($font))
                imagettftext($im, 20, 0, 7,19,$col_text,$font,$rand);
            else
                imagestring($im, 5, 8, 5,$rand,$col_text);
        }elseif($ans){
            $rand = $ans;
            if(file_exists($font))
                imagettftext($im, 20, 0, 7,19,$col_text,$font,$rand);
            else
                imagestring($im, 5, 8, 5,$rand,$col_text);
        }
        $this->img = $im;
        return $im;
    }
}