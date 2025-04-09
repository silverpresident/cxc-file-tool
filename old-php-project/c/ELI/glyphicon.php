<?php
/**
 * @author Edwards
 * @copyright  2013
 */
class ELI_glyphicon
{
    protected $type = 0;
    
    public function __get($name) {
        return $this->$name();
    }
    public function __set($name, $value) {
        
    }
    public function __call($name, $arguments) {
        $c = $this->getClass()->$name();
        if(class_exists('HTML',false)){
            $span = HTML::build('span');
            $span->class($c);
            if(count($arguments)){
                $a1 = $arguments[0];
                if(is_string($a1)){
                    $span->AddClass($a1);
                }elseif(is_array($a1)){
                    if(isset($a1['class'])){
                        $span->AddClass($a1['class']);
                    }
                    if(isset($a1['title'])){
                        $span->title($a1['title']);
                    }
                    if(isset($a1['style'])){
                        $span->style($a1['style']);
                    }
                }
            }
            return $span;
        }else{
            $span =array("<span class='$c");
            if(count($arguments)){
                $a1 = $arguments[0];
                if(is_string($a1)){
                    $span[] = " $a1'";
                }elseif(is_array($a1)){
                    if(isset($a1['class'])){
                        $span[] = " {$a1['class']}'";
                    }
                    if(isset($a1['title'])){
                        $span[] = " title='{$a1['title']}'";
                    }
                    if(isset($a1['style'])){
                        $span[] = " style='{$a1['style']}'";
                    }
                }
            }
            $span[] = "></span>";
            return implode('',$span);
        }
    }
    static function setType($type){
        if(is_String($type)){
            $type = substr(strtolower(trim($type)),0,1);
            if($type=='f') $this->type =1;
            if($type=='g') $this->type =0;
        }else{
            $this->type = (int)$type;
        }
    }
    
    static function getClass(){
        if($this->type ==1){
            return new ELI_glyphicon_fa;
        }
        return new ELI_glyphicon_glyph;
    }
    static function getCdn($version ='latest')
    {
        
        if($this->type ==1){
            if($version == 'latest')$version = '4.4.0';
            if(empty($version))$version = '4.3.0';
            return "//cdnjs.cloudflare.com/ajax/libs/font-awesome/{$version}/css/font-awesome.min.css";
        }
        if($version == 'latest')$version = '3.0.0';
        if(empty($version))$version = '3.0.0';
        
        return "//netdna.bootstrapcdn.com/bootstrap/{$version}/css/bootstrap-glyphicons.css";
    }
} 
class ELI_glyphicon_glyph
{
    public function __get($name) {
        return $this->$name();
    }
    public function __set($name, $value) {
        
    }
    public function __call($name, $arguments) {
        $name =strtolower($name);
        $name = str_replace('_','-',$name);
        return "glyphicon glyphicon-{$name}";
    }
}
class ELI_glyphicon_fa
{
    public function __get($name) {
        return $this->$name();
    }
    public function __set($name, $value) {
        
    }
    public function __call($name, $arguments) {
        $name =strtolower($name);
        $name = str_replace('_','-',$name);
        return "fa fa-{$name}";
    }
}