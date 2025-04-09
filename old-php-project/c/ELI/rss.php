<?php
/**
* @author Edwards
* @copyright  2010
*/
define('ELI_NONE',0);
define('ELI_HTMLSPECIAL','HTML');
define('ELI_RSSENCODE','RSS');
define('ELI_CDATA','CDATA');

function ELI_rssxmlspecialchars($text) {
   return str_replace('&#039;', '&apos;', htmlspecialchars($text, ENT_QUOTES));
}
function ELI_rssspecialchars($text) 
{
    //return htmlentities($text);
    return htmlspecialchars($text);
}
function ELI_rssencode($text,$method=0) 
{
    if($method == ELI_CDATA)
    {
        return "<![CDATA[$text]]>";
    }elseif($method == ELI_RSSENCODE)
    {
        return ELI_rssxmlspecialchars($text);
    }elseif($method == ELI_HTMLSPECIAL)
    {
        return htmlspecialchars($text);
    }else
        return $text;
    
}
class ELI_rssitem
{
    protected $p  = array();
    /**
     * 0 = none
     * 1 = htmlspecial char
     * 2 =  xML special char
     * 3 = CDATA
     */
    var $html_content_coding = ELI_HTMLSPECIAL;
    
    /**
     *properties: title, link, description
     */
    public function __construct($properties=array())
    {
        $this->p = $properties;
        return $this;
    }
    function _checkProps()
    {
        $prop=array('title'=>'','link'=>'','description'=>'');
        $s = '';
        foreach($prop as $p => $v)
        {
            if(!array_key_exists($p,$this->p))
                $this->p[$p]=$v;
            $s .= $this->p[$p];
        }
        return (trim($s) !='');
    }
    public function html()
    {
        if($this->_checkProps())
        {
            $ia = array();
            foreach($this->p as $p => $v)
            {
                if($p=='enclosure')
                {
                    $ia[] = "<$p $v/>";
                }else
                {
                    if($p=='description')
                        $v = ELI_rssencode($v,$this->html_content_coding);
                    else
                        $v = ELI_rssspecialchars($v);
                    
                    $ia[] = "<$p>$v</$p>";
                }
            }
            if(count($ia))
            {
                $c = implode("\n\t",$ia);
                return "<item>\n\t{$c}\n</item>";   
            }else
                return '';
        }else
            return '';
        
    }
    public function __toString(){
        return $this->html();
    }
    
    public function __isset($pn){
        return(array_key_exists($pn, $this->p));
    }
    public function __unset($pn){
        unset($this->p[$pn]);
    }
    public function __set($pn, $v){
        if(!is_array($v))
        {
            foreach($v as $k => $a)
            {
                $this->p[$k]=$a;
            }
        }else
        {
            $this->p[$pn]=$v;
        }
        return $this;
    }       

    public function __get($pn){
        if(isset($this->p[$pn]))
            return $this->p[$pn];
        else
            return null;
    }
}
class ELI_rssimage extends ELI_rssitem
{
    /**
     * 0 = none
     * 1 = htmlspecial char
     * 2 =  RSS special char
     * 3 = CDATA
     */
    var $html_content_coding = ELI_HTMLSPECIAL;
    function _checkProps()
    {
        $prop=array('title'=>'','link'=>'','url'=>'');
        $s = '';
        foreach($prop as $p => $v)
        {
            if(!array_key_exists($p,$this->p))
                $this->p[$p]=$v;
            $s .= $this->p[$p];
        }
        return (trim($s) !='');
    }
    public function html()
    {
        if($this->_checkProps())
        {
            $ia = array();
            foreach($this->p as $p => $v)
            {
                //$v=ELI_rssspecialchars($v);
                //$v = ELI_rssencode($v,$this->html_content_coding);
                if($p=='description')
                    $v = ELI_rssencode($v,$this->html_content_coding);
                else
                    $v = ELI_rssspecialchars($v);                
                $ia[] = "<$p>$v</$p>";
            }
                
            if(count($ia))
            {
                $c = implode("\n\t",$ia);
                return "<image>\n\t{$c}\n</image>";    
            }else
                return '';
        }else
            return '';
        
    }
}
class ELI_rsschannel
{
    protected $c = array(); //items
    protected $p  = array(); //prooperties
    protected $i  = null; //image
    /**
     * 0 = none
     * 1 = htmlspecial char
     * 2 =  RSS special char
     * 3 = CDATA
     */
    var $html_content_coding = ELI_HTMLSPECIAL;
    function _checkProps($properties=array())
    {
        $prop=array('title'=>'','link'=>'','description'=>'');
        foreach($prop as $p => $v)
        {
            if(!array_key_exists($p,$this->p))
                $this->p[$p]=$v;
        }
        //return $properties;
    }
    public function html()
    {
        $this->_checkProps();
        $ia = array();
        foreach($this->p as $p => $v)
        {
            //$v=ELI_rssspecialchars($v);
            //$v = ELI_rssencode($v,$this->html_content_coding);
            if($p=='description')
                $v = ELI_rssencode($v,$this->html_content_coding);
            else
                $v = ELI_rssspecialchars($v);
            $ia[] = "<$p>$v</$p>";
        }
        $d = implode("\n",$ia);
        $i = (is_null($this->i))?'':$this->i."\n";
        //items
        $ia = array();
        foreach($this->c as $p => $v)
        {
            $ia[] =$v->html();
        }
        $c = implode("\n",$ia);
        return "<channel>\n{$d}\n{$i}{$c}\n</channel>";
    }
    public function __toString(){
        return $this->html();
    }
    public function setProperty($propertyName, $value=null)
    {
        if($propertyName !='link' && !is_null($this->p[$propertyName]))
            $value = html_entity_decode($value);
        if(isset($this->p[$propertyName]))
        {
            if((null ===$this->p[$propertyName]))
                unset($this->p[$propertyName]);
            else
                $this->p[$propertyName] = $value;
        }else
        {
            if(!is_null($this->p[$propertyName]))$this->p[$propertyName] = $value;
        }
    }
    public function __construct($title='',$link='',$description='',$properties=array())
    {
        //$title=ELI_rssxmlspecialchars($title);
        $title = html_entity_decode($title);
        $description = html_entity_decode($description);
        $properties['title']=$title;
        $properties['link']=$link;
        $properties['description']=$description;
        $this->p = $properties;
        return $this;
    }
    /**
     * add an item to channel
     * 
     */
    public function add($title='',$link='',$description='',$properties=array())
    {
        $properties['title']=$title;
        $properties['link']=$link;
        $properties['description']=$description;
        $tmp = new ELI_rssitem($properties);
        $tmp->html_content_coding = $this->html_content_coding;
        $pn = count($this->c);
        if($pn>0) $pn++;
        $this->c[$pn] = $tmp;
        return $this->c[$pn];
    }
    public function image($title='',$link='',$url='')
    {
        $properties['title']=$title;
        $properties['link']=$link;
        $properties['url']=$url;
        if(implode('',$properties)!='')
        {
            if((null ===$this->i))
            {
                $tmp = new ELI_rssimage($properties);
                $this->i = $tmp;       
            }else
            {
                foreach($properties as $k=>$v)
                {
                    $this->i->$k = $v;
                }        
            }
        }
        return $this->i;   
    }
    
}

class ELI_rss
{
    /*doc properties */
    var $xml_version = '1.0';
    var $xml_encoding = 'ISO-8859-1';
    var $xml_stylesheet = '';
    var $rss_version = '2.0';
    var $generator = 'ELI 2011 - ELIXOM CMS built by Shane Edwards';
    /**
     * 0 = none
     * 1 = htmlspecial char
     * 2 =  RSS special char
     * 3 = CDATA
     */
    var $html_content_coding = ELI_HTMLSPECIAL;
    
    protected $c = array(); //channels
    
    /*BUILDING AN RSS */
    
    public function add($title='',$link='',$description='',$properties=array())
    {
        $tmp =  new ELI_rsschannel($title,$link,$description,$properties);
        $tmp->ttl= 24 * 60;
        $tmp->html_content_coding = $this->html_content_coding;
        $pn = count($this->c);
        if($pn>0) $pn++;
        $this->c[$pn] = $tmp;
        return $this->c[$pn];
    }
    public function html()
    {
        $a = $ia =$out =array();
        
        if($this->xml_version) $a[] = "version='{$this->xml_version}'";
        if($this->xml_encoding) $a[] = "encoding='{$this->xml_encoding}'";
        $temp = implode(' ',$a);
        if($temp) $temp = ' '.$temp;
        
        $out[] ="<?xml{$temp}?>";
        if($this->xml_stylesheet != '') $out[] ="<?xml-stylesheet type=\"text/css\" href=\"{$this->xml_stylesheet}\"?>";
        if($this->generator != '') $out[] ="<!-- generator=\"{$this->generator}\" -->";
        
        foreach($this->c as $p => $v)
        {
            $ia[] =$v->html();
        }
        $out[] ="<rss version=\"{$this->rss_version}\">";
        $out[] =implode("\n",$ia);
        $out[] ="</rss>";
        
        
        return implode("\n",$out);
    }
    
    public function __toString(){
        return $this->html();
    } 
    
    
    /*READING AN RSS */
    var $error = '';
    var $url = '';
    //var $rawDoc = null;
    var $channels = array();
    public function Load($url)
    {
        $this->url = false;
        $this->error = false;
        $this->channels =array();
        if($url){
            $this->url=$url;
            try{
                $doc  = new DOMDocument();
                @$doc->load($url);
            }catch(Exception $e)
            {
                $this->error= $e->getMessage();
            }
            //try{
            @$this->setChannels($doc->getElementsByTagName("channel"));
            /*}catch(Exception $e)
            {
            $this->error= $e->getMessage();
            }*/
        } else {
            $this->error="No URL for RSS feed";
        }
    }
    public function LoadLocalFile($filename)
    {
        $this->url = false;
        $this->error = false;
        $this->channels =array();
        if($url){
            $this->url=$url;
            $doc  = new DOMDocument();
            ob_start(); // Start output buffering.
            include($filename);
            $file = ob_get_clean();
            $doc->loadXML($file);
            //try{
            $this->setChannels($doc->getElementsByTagName("channel"));
            /*}catch(Exception $e)
            {
            $this->error= $e->getMessage();
            }*/
        } else {
            $this->error="No URL for RSS feed";
        }
    }
    private function setChannels($channels)
    {
        foreach($channels as $channel)
        {
            $i = count($this->channels);
            // Processing channel
            // get description of channel, type 0
            $y = $this->parseTag($channel);
            $y["type"] = 0;
            $this->channels[$i]['data'] = $y;


            // Processing articles
            $items = $channel->getElementsByTagName("item");
            foreach($items as $item)
            {
                $y = $this->parseTag($item);
                $y["type"] = 1;
                $this->channels[$i]['items'][] = $y;
            }
        }
    }
    private function parseTag($item)
    {
        $y = array();
        $tnl = $item->getElementsByTagName("title");
        $tnl = $tnl->item(0);
        $title = (empty($tnl))?'': $tnl->firstChild->textContent;

        $tnl = $item->getElementsByTagName("link");
        $tnl = $tnl->item(0);
        $link = (empty($tnl))?'': $tnl->firstChild->textContent;

        $tnl = $item->getElementsByTagName("pubDate");
        $tnl = $tnl->item(0);
        $date = (empty($tnl))?'': $tnl->firstChild->textContent;

        $tnl = $item->getElementsByTagName("description");
        $tnl = $tnl->item(0);
        $description = (empty($tnl->firstChild->textContent))?'': $tnl->firstChild->textContent;


        $y["title"] = $title;
        $y["link"] = $link;
        $y["date"] = $date;
        $y["description"] = $description;

        return $y;
    }
}
?>