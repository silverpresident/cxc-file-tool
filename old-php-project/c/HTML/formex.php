<?php
/**
 * @author Edwards
 * @copyright 2010
 */
HTML::loadapi('form');
//define('HTML_FORM_ASUL', 1);
defined('HTML_FORM_BOOTSTRAP3') OR define('HTML_FORM_BOOTSTRAP3', 3);
defined('HTML_FORM_BOOTSTRAP') OR define('HTML_FORM_BOOTSTRAP', 2);
defined('HTML_FORM_ASTABLE') OR define('HTML_FORM_ASTABLE', 1);
defined('HTML_FORM_ASFIELDSET') OR define('HTML_FORM_ASFIELDSET', 0);
class HTML_formEx extends HTML_form
{
    const HTML_FORM_BOOTSTRAP3 = 3;
    const HTML_FORM_BOOTSTRAP = 2;
    const HTML_FORM_ASTABLE = 1;
    const HTML_FORM_ASFIELDSET = 0;
    protected $tag = 'form';
    protected $caption = '';
    protected $key = '';
    protected $instructions = '';
    protected $postbody = null;
    protected $hidden = array();
    protected $body = array();
    protected $buttons = array();
    protected $attr  = array('method'=>'POST','role'=>'form');
    public $render =  HTML_FORM_BOOTSTRAP3;
    public static function getConstants(){
        static $cg = null;
        if($cg===null){
            $cg = new HTML_formEx_constants();
        }
        return $cg;
    }
    public function instructions($value=null)
    {
        $key = __FUNCTION__;
        if((null ===$value))
            return $this->$key;
        else
        {
            $this->$key = $value;
            return $this;
        }
    }
    public function key($value=null)
    {
        $key = __FUNCTION__;
        if((null ===$value))
            return $this->$key;
        else
        {
            $this->$key = $value;
            return $this;
        }
    }
    public function caption($value=null)
    {
        if((null !==$value))
        {
            $this->caption = $value;
            return $this;
        }else
            return $this->caption;
    }
    public function autocomplete($value=null)
    {
        $key = __FUNCTION__;
        if((null !==$value))
        {
            $this->attr[$key] = $value;
            return $this;
        }else
            return $this->attr($key);
    }
    public function autofocus($value=null)
    {
        $key = __FUNCTION__;
        if((null !==$value))
        {
            $this->attr[$key] = $value;
            return $this;
        }else
            return $this->attr($key);
    }
    public function addSection($label='')
    {
        $this->body[] = array('l'=>$label,'t'=>'S');
    }
    public function closeSection()
    {
        $this->body[] = array('l'=>'','t'=>'-S');
    }
    public function closeBody()
    {
        $this->postbody =array();
    }
    /**/
    
    public function addHTML($innerHTML)
    {
        if((null ===$this->postbody))
            $this->body[] = $innerHTML;
        else
            $this->postbody[] = $innerHTML;
    }
    public function addInput($inputHTML, $label,$besideHTML='')
    {
        if((null ===$this->postbody))
            $this->body[] = array('l'=>$label, 'i'=>$inputHTML,'b'=>$besideHTML);
        else
            $this->postbody[] = array('l'=>$label, 'i'=>$inputHTML,'b'=>$besideHTML);
    }
    public function createHidden($name, $value='')
    {
        $el = HTML::input('hidden',$name);
        $el->parent($this);
        $el->value($value);
        $this->hidden[] = $el;
        return $el;
    }
    public function createInput($inputType,$name, $label,$besideHTML='')
    {
        $inputType = strtolower($inputType);
        if(in_array($inputType,array('select','textarea')))
            $el = HTML::build($inputType,$name);
        else
            $el = HTML::input($inputType,$name);
        $el->parent($this);
        if((null ===$this->postbody))
            $this->body[] = array('l'=>$label,'i'=>$el,'b'=>$besideHTML);
        else
            $this->postbody[] = array('l'=>$label,'i'=>$el,'b'=>$besideHTML);
        return $el;
    }
    public function createInputButton($name,$label,$inputType, $besideHTML='')
    {
        $inputType = strtolower($inputType);
        if(!in_array($inputType,array('button','submit','reset','image')))
            $inputType = 'button';
        $el = HTML::input($inputType,$name);
        $el->parent($this);
        $el->value($label);
        $el->attr('title',$label);
        $this->buttons[] = array('i'=>$el,'b'=>$besideHTML);
        
        return $el;
    }
    public function createButton($name,$label,$inputType, $besideHTML='')
    {
        $inputType = strtolower($inputType);
        if(!in_array($inputType,array('button','submit','reset','image')))
            $inputType = 'button';
        $el = HTML::build('button');
        $el->name($name)->type($inputType);
        $el->parent($this);
        $el->append($label);
        $this->buttons[] = array('i'=>$el,'b'=>$besideHTML);
        
        return $el;
    }
    public function innerHTML($value=null)
    {
        if((null !==$value))
            throw new Exception('Cannot set Extended FORM (FormEx) innerHTML');
            
        if($this->render == HTML_FORM_BOOTSTRAP3){
            return $this->render_bs3();
        }
        $r= array();
        if(!empty($this->caption))
            $r[] = "<h2>{$this->caption}</h2>";
        if(isset($this->instructions))
            $r[] = (string)$this->instructions;
        if($this->render == HTML_FORM_ASTABLE)
        {
            $r[] = "<table>";
        }
        $inset = 0;
        foreach($this->body as $v)
        {
            if(is_array($v))
            {
                $l = $i = '';
                $b = (empty($v['b']))?'':$v['b'];
                if(isset($v['t']))
                {
                    if($v['t']=='-S')
                    {
                        if($inset)
                        {
                            
                            if($this->render != HTML_FORM_ASTABLE)
                                $r[] = "</fieldset>";
                        }
                        $inset--;
                    }elseif($v['t']=='S')
                    {
                        if($inset)
                        {
                            $inset--;
                            if($this->render != HTML_FORM_ASTABLE)
                                $r[] = "</fieldset>";
                        }
                        $inset++;
                        if($this->render == HTML_FORM_ASTABLE)
                        {
                            $l = "<tr><th colspan='100%'>{$v['l']}</th>";
                            $i = "</tr>";
                        }else
                        {
                            $l = "<fieldset>"; 
                            $i = "\n<legend>{$v['l']}</legend>";
                        }
                    }
                    if($inset<0) $inset=0;
                }else
                {
                    if($v['i'] instanceof HTML_element)
                        $n = ' id= "th_' . $v['i']->name() .'"';
                    else
                        $n='';
                    if($this->render == HTML_FORM_BOOTSTRAP){
                        if(empty($v['l']))
                        {
                            $l = "<div class='control-group'><span$n>";
                            $b .= '</span>';
                        }else
                        {
                            $l = "<div class='control-group'><label class='control-label' $n>{$v['l']}</label>";
                        }
                        $b .= '</div>';
                        $i = "<div class='controls'>" . (string)$v['i'] . '</div>';
                    }elseif($this->render == HTML_FORM_ASTABLE)
                    {
                        $l = "<tr$n>";
                        if(empty($v['l']))
                        {
                            $i = "<td colspan='100%'>{$v['i']}{$b}</td>";
                        }else
                        {
                            $l .= "<th scope='row'>{$v['l']}</th>";
                            $i = "<td>{$v['i']}{$b}</td>";
                        }
                        $b = '</tr>';
                    }else
                    {
                        if(empty($v['l']))
                        {
                            $l = "<div><span$n>";
                            $b .= '</span>';
                        }else
                        {
                            $l = "<div><label$n>{$v['l']}</label>";
                        }
                        $b .= '</div>';
                        $i = (string)$v['i'];
                        
                    }
                }
            }else
            {
                if($this->render == HTML_FORM_ASTABLE)
                {
                     $l = "<tr><td colspan='100%'>";
                     $i = $v;
                     $b = "</td></tr>";
                }else
                {
                    $l = $v;
                    $i = '';
                    $b = '';
                }
            }
            
            $r[] = "$l{$i}{$b}";
        }
        if($inset)
        {
            $inset--;
            if($this->render != HTML_FORM_ASTABLE)
                $r[] = "</fieldset>";
        }
        if($this->render == HTML_FORM_ASTABLE)
        {
            $r[] = "</table>";
        }
        if(is_array($this->postbody))
            foreach($this->postbody as $v)
            {   if(is_array($v))
                    $r[] = "{$v['i']}";
                else
                    $r[] = "{$v}";
            }
        if(count($this->buttons))
        {
            if($this->render == HTML_FORM_BOOTSTRAP)
                $r[] = "<div class='form-actions'>";
            else
                $r[] = "<fieldset class='buttons'>";
            foreach($this->buttons as $v)
            {   if(is_array($v)){
                    if(($this->render == HTML_FORM_BOOTSTRAP) && $v['i'] instanceof HTML_element){
                        $v['i']->addClass('btn');
                    }
                    $r[] = "{$v['i']}{$v['b']}";
                }else
                    $r[] = "{$v}";
            }
            if($this->render == HTML_FORM_BOOTSTRAP)
                $r[] = "</div>";
            else
                $r[] = "</fieldset>";
        }
        if(isset($this->key))
            $r[] = (string)$this->key;
        //hidden
        foreach($this->hidden as $v)
        {   
            $r[] = (string)$v;
        }
        $r[] = parent::innerHTML();
        return implode("\n",$r);
    }
    private function render_bs3()
    {
        if(!empty($this->caption))
            $r[] = "<h2>{$this->caption}</h2>";
        if(isset($this->instructions))
            $r[] = (string)$this->instructions;
        
        $inset = 0;
        foreach($this->body as $v)
        {
            if(is_array($v))
            {
                $l = $i = '';
                $b = (empty($v['b']))?'':$v['b'];
                if(isset($v['t']))
                {
                    if($v['t']=='-S')
                    {
                        if($inset)
                        {
                            $r[] = "</fieldset>";
                        }
                        $inset--;
                    }elseif($v['t']=='S')
                    {
                        if($inset)
                        {
                            $inset--;
                            $r[] = "</fieldset>";
                        }
                        $inset++;
                         
                        $l = "<fieldset>"; 
                        $i = "\n<legend>{$v['l']}</legend>";
                         
                    }
                    if($inset<0) $inset=0;
                }else
                {
                    if($v['i'] instanceof HTML_element){
                        $n = ' id= "th_' . $v['i']->name() .'"';
                        $v['i']->addClass('form-control');
                    }else
                        $n='';
                        
                    if(empty($v['l']))
                    {
                        $l = "<div class='form-group'><span$n>";
                        $b .= '</span>';
                    }else
                    {
                        $l = "<div class='form-group'><label class='control-label col-md-3' $n>{$v['l']}</label>";
                    }
                    $b .= '</div>';
                    $i = "<div class='col-md-9'>" . (string)$v['i'] . '</div>';
                    
                }
            }else
            {
                
                $l = $v;
                $i = '';
                $b = '';
                
            }
            
            $r[] = "$l{$i}{$b}";
        }
        if($inset)
        {
            $inset--;
            $r[] = "</fieldset>";
        }
        
        if(is_array($this->postbody))
            foreach($this->postbody as $v)
            {   if(is_array($v))
                    $r[] = "{$v['i']}";
                else
                    $r[] = "{$v}";
            }
        if($c = count($this->buttons))
        {
            $r[] = "<div class='form-group'><div class='controls col-md-offset-3'>";
            foreach($this->buttons as $v)
            {   if(is_array($v)){
                    if( $v['i'] instanceof HTML_element){
                        $v['i']->addClass('btn');
                        if($c==1)$v['i']->addClass('btn-primary btn-block');
                        else $v['i']->addClass('btn-default ');
                    }
                    $r[] = "{$v['i']}{$v['b']}";
                }else
                    $r[] = "{$v}";
            }
            $r[] = "</div></div>";            
        }
        if(isset($this->key))
            $r[] = (string)$this->key;
        //hidden
        foreach($this->hidden as $v)
        {   
            $r[] = (string)$v;
        }
        $r[] = parent::innerHTML();
        return implode("\n",$r);
    }
}
class HTML_formEx_constants{
    protected $data =array();
    public function __get($name) {
        $name = strtolower($name);
        if(isset($this->data[$name])) return $this->data[$name];
        return null;
    }

    public function __construct() {
        $this->data['bootstrap3'] = 3;
        $this->data['asfieldset'] = 0;
        $this->data['astable'] = 1;
        //$this->data['form_asfieldset'] = 0;
        //$this->data['form_astable'] = 1;
        $arr =array('cancel','save'/*,'ok','back','next','update','add','reset','edit','delete','send'*/);
        
        $i = 1;
        foreach($arr as $v){
            $this->data[$v] = $i;
            //$this->data["btn_{$v}"] = $i;
                $i *= 2;
        }
        
    }
}