<?php
/**
 * @author Edwards
 * @copyright 2017
 *
 */
namespace ELIX;

class nameParser extends \ELIX\PropertyBag{
    public function __construct($data) {
        if(is_scalar($data)){
            $this->setName($data);
        }
    }

    public function setName($name){
        $name = trim($name);
        $this->data = array();
        if(!$name){
            return;
        }
        $f =0;
        $name = str_replace(array('  ','  '),' ',$name);
        $name = str_replace(array('- ',' -','--'),'-',$name);
        $name = str_ireplace(array('Mc '),'Mc',$name);
        $name = str_ireplace(array('Mac '),'Mac',$name);
        $name = str_ireplace(array("O' "),"O'",$name);
        $name = str_ireplace(array(' Snr.',' Snr'),'',$name);
        
        $x = explode(' ', $name);
        $n = strtoupper($x[0]);
        $n = str_replace('.','',$n);
        if(in_array($n,array('MR','MS','MRS','DR','MISTER','MISS','MISTRES'))){
            $f =1;
            $this->data['title'] =  str_replace('.','',$x[0]);
            unset($x[0]);
            $name = implode(' ', $x);
            $x = explode(' ', $name);
        }
        $c = count($x);
        if($c ==0){
            return;
        }
        $this->data['original'] = $name;
        if($i = strpos($name,',')){
            $this->data['family_name'] = $this->data['lastname'] = substr($name,0,$i);
            $name = trim(substr($name,$i+1));
            $this->data['given_name'] = $name;
            $x = explode(' ', $name);
            $this->data['firstname'] = $x[0];
            unset($x[0]);
            $this->data['middlename'] = implode(' ',$x);
        }else{
            $l = $c-1;
            $this->data['family_name'] = $this->data['lastname'] = $x[$l];
            if($l==0) return;
            unset($x[$l]);
            $this->data['given_name'] = implode(' ',$x);
            $this->data['firstname'] = $x[0];
            unset($x[0]);
            $this->data['middlename'] = implode(' ',$x);
            
        }
    }
    public function __toString() {
        $n =array();
        if(isset($this->data['given_name'])) $n[] = $this->data['given_name'];
        if(isset($this->data['family_name'])) $n[] = $this->data['family_name'];
        return implode(' ', $n);
    }
    public function getPrintName() {
        $n =array();
        if(isset($this->data['firstname'])) $n[] = substr($this->data['firstname'],0,1);
        if(isset($this->data['lastname'])) $n[] = $this->data['lastname'];
        return implode(' ', $n);
    }
    public function getInitials() {
        $n =array();
        if(isset($this->data['firstname'])) $n[] = $this->data['firstname'];
        if(isset($this->data['lastname'])) $n[] = $this->data['lastname'];
        $i = new initials_parser();
        $i->setInitials(implode(' ', $n));
        return $i;
    }
}
class initials_parser extends \ELIX\PropertyBag{
    public function setInitials($name)
    {
        //todo replace all not alpha non hyphen characers
        $this->data['original'] = $name;
        $name=trim($name);
        $i = array();
        if(strpos($name,' ')){
            $x = explode(' ',$name);
            if(isset($x[0]))
                $i[] = substr($x[0],0,1);
            
            $l = count($x)-1;
            if($l>0){
                $x[$l] == str_replace(' ','',$x[$l]);
                $a = explode('-',$x[$l],2);
                foreach($a as $v)
                    $i[] = substr($v,0,1);
            }
        }else{
            $i[] = substr($name,0,5);
        }
        $this->data['initials'] = strtoupper(implode('',$i));
    }
    public function __toString() {
        if($this->initials) return $this->initials;
        return '';
    }

}
