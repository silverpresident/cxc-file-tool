<?php
class ELI_robotsTXT_item{
    public $url;
    public $type;
    public $comment;
    function type(){
        return ucfirst($this->type);
    }
    function __toString(){
        $t = $this->type();
        if($t=='#'){
            $c = ($this->comment)?" $this->comment":'';
            return "$t $this->url{$c}";
        }elseif($t=='Literal'){
            $c = ($this->comment)?" $this->comment":'';
            return "$this->url{$c}";
        }else{
            $c = ($this->comment)?" # $this->comment":'';
            return "$t: $this->url{$c}";
        }
    }
}
class ELI_robotsTXT_group{
    public $url;
    public $type;
    public $comment;
    protected $items=array();
    function add($url,$type='Disallow'){
        $el = new ELI_robotsTXT_item();
        $el->url=$url;
        $el->type = ucfirst($type);
        $this->items[] = $el;
        return $el;
    }
    function disallow($url){
        return $this->add($url,__FUNCTION__);
    }
    function allow($url){
        return $this->add($url,__FUNCTION__);
    }
    function sitemap($url){
        return $this->add($url,__FUNCTION__);
    }
    function comment($url){
        return $this->add($url,'#');
    }
    function literal($text){
        $el = new ELI_robotsTXT_item();
        $el->url=$text;
        $el->type = 'Literal';
        $this->items[] = $el;
        return $el;
    }
    function type(){
        return ucfirst($this->type);
    }
    function __toString(){
        $a =array();
        if($this->type){
            $t = $this->type();
            $c = ($this->comment)?" # $this->comment":'';
            $a[] ="$t: $this->url{$c}";
        }
        
        foreach($this->items as $item){ 
            if($item->type() =='Allow'){
                $a[] = (string)$item; 
            }
        }
        foreach($this->items as $item){ 
            if($item->type() =='Disallow'){
                $a[] = (string)$item; 
            }
        }
        foreach($this->items as $item){ 
            if($item->type() !='Allow' && $item->type() !='Disallow' && $item->type() !='User-agent'){
                $a[] = (string)$item; 
            }
        }
        foreach($this->items as $item){ 
            if($item->type() =='User-agent'){
                $a[] = (string)$item; 
            }
        }
        return implode("\n",$a);
    }
}
class ELI_robotsTXT extends ELI_robotsTXT_group{
    function userAgent($agent){
        $el = new ELI_robotsTXT_group();
        $el->url=$agent;
        $el->type = 'User-agent';
        $this->items[] = $el;
        return $el;
    }
    public function output(){
        return $this->__toString();
    }
    public function send(){
        echo $this->output();
    }
    
}
?>