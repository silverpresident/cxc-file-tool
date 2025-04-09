<?php
/**
 * ELI_captcha1
 *  
 * @package ELI
 * @author Edwards
 * @copyright 2011
 * @version $Id$ 
 * @access public
 */
abstract class ELI_captcha1
{
    static function exists($id){
        return false;
    }
    static function get($id){
        return false;
    }
    static function clean($id=0){
        
    }
    static function test($id,$answer)
    {
        $o = static::get($id);
        static::clean($id);
        if($o === false){
            return false;
        }
        try{
            if(is_numeric($o->answer))
                return ($o->answer == $answer);
            else
                return (strtoupper($o->answer) == strtoupper($answer));
        }catch(Exception $e){
            return false;
        }
    }
    static function generate()
    {
        $n1 = rand(0,35);
        if($n1>=20) $o= static::generate3(); 
        elseif($n1>=12) $o= static::generate2();
        else $o=static::generate1();
        
        return $o;
    }
    static function generate1(){
        $col = new ELI_object();
        $n1 = rand(0,35);
        $r1 = rand(1,4);
        $r2 = rand(1,9);
        if($r1 == 2)
        {
            $s = ($r2>5)?'+':'plus';
            $n2 = rand(8,25);
            $a =  $n1 + $n2;
        }elseif($r1 == 4 && ($n1 <9))
        {
            $s = ($r2>5)?'times':'multiplied by';
            $n2 = $n1;
            $n1 = rand(1,3);
            $a =  $n1 * $n2;
        }elseif($r1 == 5 && ($n1 % 2 == 0) && $n1 >0)
        {
            $s = 'divided by';
            $n2 = $n1;
            $n1 = rand(1,2);
            $a =  $n2 / $n1;
        }else
        {
            $s = ($r2>5)?'-':'minus';
            $a = rand(0,35);
            $n2 = $n1+$a;
        }
        $col->q = "What is <strong>$n2</strong> $s <strong>$n1</strong>?";
        $col->type = 'number'; 
        $col->answer = $a;
        $col->maxlength = strlen(''.$a) + 2;
        return $col;
    }
    static function generate2()
    {
        $col = new ELI_object();
        
        $r1 = rand(1,10);
        $r2 = rand(1,9);
        if($r1 == 2)
        {
            $words = array('cat','cut','boy','bay','buy','girl','pen','pan','pin','pun','pet',
            'hat','hit','hot','hut','dog','dig','man','men','sad','van','fan','fun','gun',
            'jug','jaw','lot','mat','mug','nut','nest','rat','run','set');
            $a = $words[array_rand($words)];
            $col->q = "Type <em>$a</em>";
        }elseif($r1 == 3){
            $q = array('1','2','4','5','6','7','9','10','1');
            $words = array('one','two','four','five','six','seven','nine','ten','one');
            $s = $q[$r2-1];
            $a = $words[$r2-1];
            $col->q = "Spell <em>$s</em> ";
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
            $col->q = "Which is a body part <em>$i[1]</em>, <em>$i[2]</em> or <em>$i[0]</em>?";
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
            $col->q = "Which is a day of the week <em>$i[1]</em>, <em>$i[2]</em> or <em>$i[0]</em>?";
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
            $col->q = "Which is a fruit <em>$i[1]</em>, <em>$i[2]</em> or <em>$i[0]</em>?";
        }
        $col->answer = $a;
        $col->type = 'text';
        $col->maxlength = strlen(''.$a) * 2;
        return $col;
    }
    static function generate3()
    {
        $r1 = rand(1,9);
        
        $col = new ELI_object();
        if($r1 == 2){
            $i[0] = rand(10,100);
            $i[1] = rand($i[0],$i[0]*2);
            $i[2] = rand(50,100);
            shuffle($i);
            $a = min($i);
            $col->q = "Which is the smallest <em>$i[1]</em>, <em>$i[2]</em> or <em>$i[0]</em>?";
            
        }elseif($r1 == 3){
            $i[0] = rand(10,100);
            $i[1] = rand($i[0],$i[0]*2);
            $i[2] = rand(50,100);
            shuffle($i);
            $a = max($i);
            $col->q = "Which is the largest <em>$i[1]</em>, <em>$i[2]</em> or <em>$i[0]</em>?";
            
        }elseif($r1 == 4){
            $words = array('apple','mango','Tuesday','cat','chess','royal','answer','word','today','tuesday','file','computer');
            $r1 = array_rand($words);
            $s = $words[$r1];
            $a = strlen($s);
            $col->q = "How many letters are in '<em>$s</em>'";
        }else{
            $s = (string)rand(100,999999);
            $r2 = rand(1,strlen($s))-1;
            $a = $s[$r2];
            if($a==0) $s[$r2] =$a=rand(1,9);
            $r2+=1;
            if($r2<4){
                $arr=array(1=>'st','nd','rd');
                $p = $arr[$r2];
            }else{
                $p = 'th';
            }
            
            $col->q = "In <em>$s</em>, what is the <em>{$r2}$p</em> digit?";
        }
        
        $col->type = 'number';
        $col->answer = $a;
        $col->maxlength = strlen(''.$a) + 2;
        return $col;
    }
}
?>