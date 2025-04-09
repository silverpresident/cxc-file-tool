<?php
/**
 * @author President
 * @copyright 2015
 * @version 20150129
 *
 * STATUS: confirmed,tentative,cancelled
 */
namespace ELIX;


class ical_field{
    protected $name;
    protected $props =array();
    protected $value;
    public function __construct() {
        if($n = func_num_args()){
            $a = func_get_args();
            if($n>=1 && is_scalar($a[0])) $this->name = strtoupper($a[0]);
            if($n==2 && is_scalar($a[1])) $this->value = $a[1];
        }
    }
    public function __get($name) {
        $name = strtoupper($name);
        if($name == 'NAME'){
            return $this->name;
        }
        if($name == 'VALUE'){
            return $this->value;
        }
        return null;
    }


    public function setValue($val){
        $this->value = $val;
        return $this;
    }
    public function setName($name){
        $this->name = strtoupper($name);
        if(strpos($name,';')){
            $x = explode(';',$name,2);
            $this->name = strtoupper($x[0]);
            unset($x[0]);
            foreach($x as $prop){
                if(strpos($prop,'=')){
                    $p = explode('=',$prop);
                    $this->setProp($p[0],$p[1]);
                }
            }
        }else{
            $this->name = strtoupper($name);
        }
        return $this;
    }
    public function setProp($field,$value){
        $field = strtoupper(trim($field));
        $this->props[$field] = $value;
        return $this;
    }
    public function __toString() {
        if(!$this->name) return '';
        $a = array_filter($this->props);
        $pp=array();
        foreach($a as $k=>$v) $pp[] = "$k=$v";
        $p=implode(';',$pp);
        if($p)
            return "{$this->name};{$p}:{$this->value}";
        else
            return "{$this->name}:{$this->value}";
    }
    public function output(){
        if(!$this->name) return '';
        $a = array_filter($this->props);
        $pp=array();
        foreach($a as $k=>$v) $pp[] = "$k=$v";
        $p=implode(';',$pp);

        $value = $this->value;
        static $esc = array('DTSTAMP','CREATED','LAST-MODIFIED','DTSTART','DTEND',
        'URL','UID','ORGANIZER','ATTACH',
        'DUE','EXDATE','RDATE','DURATION','REPEAT','RRULE','TRIGGER'
        );
        if(!in_array($this->name,$esc)){
            $value = preg_replace('/([\,;])/','\\\$1', $value);
            $value = str_replace(array("\n","\r","\n","\r"),'\n',$value);
        }

        if($p)
            return "{$this->name};{$p}:{$value}";
        else
            return "{$this->name}:{$value}";

    }

}
class ical_item{
    protected $type = 'VEVENT';
    protected $data =  array();
    protected $items =  array();

    public function addField($field,$value){
        if($field =='LASTMODIFIED') $field= 'LAST-MODIFIED';
        $fld = new ical_field($field,$value);

        if($fld->name == 'DTSTART'){
            if(strlen($value)<=10){
                $fld->setProp('VALUE','DATE');
            }
        }
        if($fld->name == 'DTEND'){
            if(strlen($value)<=10){
                $fld->setProp('VALUE','DATE');
            }
        }

        $this->data[] = $fld;
        return $fld;
    }
    public function setField($field,$value){
        $field = strtoupper(trim($field));
        if(!$field) return null;
        if($field =='LASTMODIFIED') $field= 'LAST-MODIFIED';
        $new_fld = new ical_field($field,$value);
        $found = false;
        foreach($this->data as $fld){
            if($fld->name == $new_fld->name){
                $fld->setValue($value);
                $found = true;
            }
        }
        if(!$found){
            $this->AddField($field,$value);
        }
        return $this;
    }

    public function getField($field){
        $field = strtoupper(trim($field));
        if(!$field) return null;
        $new_fld = new ical_field($field);
        if($field =='LASTMODIFIED') $field= 'LAST-MODIFIED';
        $field = $new_fld->name;

        $a =array();
        foreach($this->data as $fld){
            if($fld->name == $field){
                $a[] = $fld;
            }
        }
        return $a;
    }
    public function addItem(){
        $o = new ical_item();
        $this->items[] = $o;
        return $o;
    }
    public function addAlarm(){
        $o = new ical_item();
        $this->items[] = $o;
        $o->setType('VALARM');
        return $o;
    }
    public function getType(){
        return $this->type;
    }
    public function setType($type){
        $type = strtoupper(trim($type));
        if(!$type) return;
        if(substr($type,0,1) != 'V') $type = 'V'.$type;
        if(in_array($type,array('VEVENT','VTODO','VJOURNAL','VFREEBUSY','VTIMEZONE','VALARM'))){
            $this->type = $type;
        }
        return $this;
    }
    public function output()
    {
        $r = array();
        $r[] ="BEGIN:{$this->type}";

        foreach($this->data as $fld){
            $line = $fld->output();
            if(strlen($line)>75){
                $x = str_split($line,75);
                foreach($x as $i => $l){
                    $r[] = ($i==0?'':' '). $l;
                }
            }else{
                $r[] = $line;
            }

        }
        foreach($this->items as $item)
        {
            $line = $item->output();
            $r[] = $line;
        }
        $r[] = "END:{$this->type}";
        return implode("\r\n",$r);
    }
}
class ical extends ical_item
{
    protected $type = 'VCALENDAR';
    public function __construct() {
        $this->addField('VERSION','2.0');
    }
    public function __toString() {
        return $this->output();
    }
    public function addEvent($summary){
        $o = new ical_item();
        $this->items[] = $o;
        $o->setField('SUMMARY',$summary);
        return $o;
    }
    public function addTodo($summary){
        $o = new ical_item();
        $this->items[] = $o;
        $o->setType('VTODO');
        $o->setField('SUMMARY',$summary);
        return $o;
    }
    public function getContentType(){
        return 'text/calendar';
    }
    public function getDateFormatString(){
        return 'Ymd\THis';
    }
    public function getType(){
        return $this->type;
    }
    public function setType($type){
        $type = strtoupper(trim($type));
        if(!$type) return;
        if(substr($type,0,1) != 'V') $type = 'V'.$type;
        if(in_array($type,array('VCALENDAR'))){
            $this->type = $type;
        }
        return $this;
    }
    public function send(){
        echo $this->output();
    }
}