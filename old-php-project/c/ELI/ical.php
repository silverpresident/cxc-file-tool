<?php
/**
 * @author President
 * @copyright 2015
 * @version 20150129
 * 
 * STATUS: confirmed,tentative,cancelled
 */
class ELI_ical_field{
    protected $name;
    protected $props =array();
    protected $value;
    public function __construct() {
        if($n = func_num_args()){
            $a = func_get_args();
            if($n>=1 && is_scalar($a[0])) $this->name = $a[0];
            if($n==2 && is_scalar($a[1])) $this->value = $a[1];
        }
    }

    public function setValue($val){
        $this->value = $val;
    }
    public function setName($name){
        $this->name = strtoupper($name);
    }
    public function setProp($field,$value){
        $field = strtoupper(trim($field));
        $this->props[$field] = $value;
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

}
class ELI_ical_item{
    protected $version = '1.0';  
    protected $type = 'VEVENT';
    protected $data =  array();
    protected $items =  array();
    
    public function addAlarm(){
        $o = new ELI_ical_item();
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
    public function getField($field){
        $field = strtoupper(trim($field));
        if(!$field) return null;
        if($field =='LASTMODIFIED') $field= 'LAST-MODIFIED';
        
        if(isset($this->data[$field])) return $this->data[$field];
         return null;
    }
    public function setField($field,$value){
        $field = strtoupper(trim($field));
        if(!$field) return;
        if($field =='LASTMODIFIED') $field= 'LAST-MODIFIED';
        if(strpos($field,';')){
            $x = explode(';',$field);
            $f = $x[0];
        }else{
            $f = $field;
        }
        $l = strlen($value);
        if($f == 'DTSTART'){
            if($l<=10) $field = 'DTSTART;VALUE=DATE';
            else if($l>10) $field = 'DTSTART';
            else unset($this->data['DTSTART;VALUE=DATE']);
        }
        if($f == 'DTEND'){
            if($l<=10) $field = 'DTEND;VALUE=DATE';
            else if($l>10) $field = 'DTEND';
            else unset($this->data['DTEND;VALUE=DATE']);
            unset($this->data['DURATION']);
        }
        if($f == 'DURATION'){
            unset($this->data['DTEND']);
            unset($this->data['DTEND;VALUE=DATE']);
        }
        $this->data[$field] = $value;
        return $this;
    }
    function send(){
        echo $this->output();
    }
    function output()
    {
        $r = array();
        $r[] ="BEGIN:{$this->type}";
        $esc = array('DTSTAMP','CREATED','LAST-MODIFIED','DTSTART','DTEND',
        'DTSTART;VALUE=DATE','DTEND;VALUE=DATE','URL','UID','ORGANIZER','ATTACH',
        'DUE','EXDATE','RDATE','DURATION','REPEAT','RRULE','TRIGGER'
        /*'','','',''*/);
        foreach($this->data as $k=>$v){
            if(strpos($k,';')){
                $x = explode(';',$k);
                $f = $x[0];
            }else{
                $f = $k;
            }
            if(!in_array($f,$esc)){
                $v = preg_replace('/([\,;])/','\\\$1', $v);
                $v = str_replace(array("\n","\r","\n","\r"),'\n',$v);
            }
            $r[] = "$k:{$v}";
        }
        foreach($this->items as $item)
        {
            $r[] = $item->output();
        }
        $r[] = "END:{$this->type}";
        return implode("\r\n",$r);
    }
}
class ELI_ical
{
    protected $version = '1.0';  
    protected $type = 'VCALENDAR';
    protected $data =  array();
    protected $items =  array();
    public function __construct() {
        $this->data['VERSION'] = '2.0';
    }
    public function __toString() {
        return $this->output();
    }


    public function addItem(){
        $o = new ELI_ical_item();
        $this->items[] = $o;
        return $o;
    }
    public function addEvent($summary){
        $o = new ELI_ical_item();
        $this->items[] = $o;
        $o->setField('SUMMARY',$summary);
        return $o;
    }
    public function addTodo($summary){
        $o = new ELI_ical_item();
        $this->items[] = $o;
        $o->setType('VTODO');
        $o->setField('SUMMARY',$summary);
        return $o;
    }
    public function getField($field){
        $field = strtoupper(trim($field));
        if(!$field) return null;
        if(isset($this->data[$field])) return $this->data[$field];
         return null;
    }
    public function setField($field,$value){
        $field = strtoupper(trim($field));
        if(!$field) return;
        $this->data[$field] = $value;
        return $this;
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
    
    function send(){
        echo $this->output();
    }
    function output()
    {
        $r = array();
        $r[] ="BEGIN:VCALENDAR";
        foreach($this->data as $k=>$v){
            $r[] = "$k:{$v}";
        }
        //$r[] = '';
        
        /*echo "PRODID:-//JOL calendar/calendar.stjago.com//EN\r\n";
        echo "CALSCALE:GREGORIAN\r\n";
        echo "METHOD:PUBLISH";
        echo "X-WR-CALNAME:St Jago Online Calendar";
        echo "X-WR-CALDESC:Calendar of St Jago Related Events";
        echo "X-WR-TIMEZONE:America/Jamaica";
        echo "\r\n";*/
        
        foreach($this->items as $item)
        {
            $r[] = $item->output();
        }
        
        //$r[] = '';
        $r[] = "END:VCALENDAR";
        return implode("\r\n",$r);
    }
}

?>