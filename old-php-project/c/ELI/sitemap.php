<?php

/**
 * @author Shane
 * @copyright 2010
 */
if(!function_exists('EliUrlEncode')){
    function EliUrlEncode($string) {
        $entities = array('%21', '%2A', '%27', '%28', '%29', '%3B', '%3A', '%40', '%26', '%3D', '%2B', '%24', '%2C', '%2F', '%3F', '%25', '%23', '%5B', '%5D');
        $replacements = array('!', '*', "'", "(", ")", ";", ":", "@", "&", "=", "+", "$", ",", "/", "?", "%", "#", "[", "]");
        $string = str_replace($entities, $replacements, urlencode($string));
        $entities = array( '&',"'",'"','>','<');
        $replacements = array('&amp;','&apos;','&quot;','&gt;','&lt;');
    
        $string = str_replace($entities, $replacements, $string);
        return $string;
    }
}
class ELI_SitemapItem{
    protected $data = array();
    function toArray(){
        return $this->data;
    }
    public function __construct($data=array()) {
        if(func_num_args() && is_array($data)){
            $this->data = array_change_key_case($data,CASE_LOWER);
            if(isset($this->data['priority'])) $this->priority($this->data['priority']);
            if(isset($this->data['changefreq'])) $this->changefreq($this->data['changefreq']);
        }
    }
    public function __get($name) {
        $name = strtolower($name);
        if(isset($this->data[$name]) || array_key_exists($name,$this->data))
            return $this->data[$name];
        return '';
    }
    public function __set($name, $value) {
        $name = strtolower($name);
        if($name=='priority'){
            $this->priority($value);
            return;
        }
        if($name=='changefreq'){
            $this->changefreq($value);
            return;
        }
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
    //0 to 10
    public function priority($value=null) {
        if(func_num_args()==0){
            if(isset($this->data['priority'])){
                $p = $this->data['priority'];
                return ($p/10);
            }
            return 0;
        }
        if(!is_numeric($value)){
            $value = strtolower($value);
            if($value=='highest') $this->data['priority'] = 10;
            if($value=='high') $this->data['priority'] = 8;
            if($value=='medium') $this->data['priority'] = 6;
            if($value=='normal') $this->data['priority'] = 5;
            if($value=='low') $this->data['priority'] = 3;
            if($value=='lowest') $this->data['priority'] = 1;
            if($value=='none') $this->data['priority'] = 0;
        }elseif($value <= 0 || $value===null){
            $this->data['priority'] = 0;
        }elseif($value > 0 && $value < 1){
            $this->data['priority'] = $value*10;
        }elseif($value <10 ){
            $this->data['priority'] = $value;
        }elseif($value > 10 ){
            $this->data['priority'] = 10;
        }
        return $this;
    }
    public function changeFreq($value=null) {
        if(func_num_args()==0){
            if(isset($this->data['changefreq'])){
                return $this->data['changefreq'];
            }
            return 'none';
        }
        $value = trim(strtolower($value));
        switch($value){
        case 'always':case 'hourly':case 'daily':case 'weekly':case 'monthly':case 'yearly':case 'never':
            $this->data['changefreq'] = $value;
            break;
        default:
            $this->data['changefreq'] = 'none';
        }
    }
}
class ELI_Sitemap
{
    var $items=array();
    var $generator = 'ELI 2012/v2 - Elixom CMS built by Shane Edwards';
    var $xml_version = '1.0';
    var $xml_encoding = 'UTF-8';
    var $xml_stylesheet = '';
    var $nameSpace = 'http://www.sitemaps.org/schemas/sitemap/0.9';
    function setNameSpace($url){
        $this->nameSpace = $url;
    }
    /**
     * jolSitemap::inject()
     * 
     * @param mixed $loc
     * @param mixed $lastmod
     * @param string $changefreq: always,hourly,daily,weekly,monthly,yearly,never,none
     * @param integer $priority 0 to 10
     * @return void
     */
     
    function inject($url,$lastmod=null,$changefreq='none',$priority=0)
	{
		$row['href'] = $url;
		if((null ===$lastmod)) $lastmod =date('Y-m-d');
        $row['lastmod'] =$lastmod;
        $row['changefreq']=$changefreq;
        $row['priority']=$priority;
        $el = new ELI_SitemapItem($row);
        //$this->items[] = (object)$row;
        $this->items[] = $el;
        return $el;
	}
    function add($url,$lastmod=null,$changefreq='none',$priority=0)
	{
		$row['href'] = $url;
		if((null ===$lastmod)) $lastmod =date('Y-m-d');
        $row['lastmod'] =$lastmod;
        $row['changefreq']=$changefreq;
        $row['priority']=$priority;
        $el = new ELI_SitemapItem($row);
        //$this->items[] = (object)$row;
        $this->items[] = $el;
        return $el;
	}
    function __toString(){
        return $this->output();
    }
    
	public function generate(){
	   return $this->output();
	}
	public function output()
	{
        $r[] ="<?xml version=\"{$this->xml_version}\" encoding=\"{$this->xml_encoding}\" ?>";
		$r[] = "<urlset xmlns=\"{$this->nameSpace}\" xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\" xsi:schemaLocation=\"http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd\">";
        if($this->xml_stylesheet != '') $r[] ="<?xml-stylesheet type=\"text/css\" href=\"{$this->xml_stylesheet}\"?>";
        if($this->generator != '') $r[] ="<!-- generator=\"{$this->generator}\" -->";
        
        if(count($this->items))
        {
            foreach($this->items as $ro)
    		{
				$r[] = "<url>";
    			$ro->href=EliUrlEncode($ro->href);
    			$r[] = "<loc>{$ro->href}</loc>";
    			if(!empty($ro->lastmod))$r[] = "<lastmod>{$ro->lastmod}</lastmod>";
    			if($ro->changefreq && $ro->changefreq!='none')
    				$r[] = "<changefreq>{$ro->changefreq}</changefreq>";
    			if($ro->priority>0)
    			{
                    $priority = $ro->priority();
                    $r[] = "<priority>{$priority}</priority>";
    			}
    			$r[] = "</url>";
    		}
        }
		$r[] = "</urlset>";
		return implode("\n",$r);
	}
	function generateTXT()
	{
		$r = array();
        if(count($this->items))
        {
            foreach($this->items as $ro)
    		{
				$r[] = EliUrlEncode($ro->href);		
    		}
        }
		return implode("\n",$r);
	}
    static function submit($url, $services=array('google','bing'))
    {
        $sm = urlencode($url);
        $log = $urls = array();
        if(in_array('google',$services))$urls[] = 'http://www.google.com/webmasters/tools/ping?sitemap=';
        if(in_array('bing',$services))$urls[] = 'http://www.bing.com/webmaster/ping.aspx?siteMap=';
        foreach($urls as $url){
            $c_url = $url.$sm;
            $res = file_get_contents($c_url,false);
            $log[] = "====URL: $c_url";
            $log[] = "=HEADER-RESPONSE";
            $http_response_header['RESULT'] = $res;
            $log[] = print_r($http_response_header,1);
        }
        return $log;
        
    }
}
?>