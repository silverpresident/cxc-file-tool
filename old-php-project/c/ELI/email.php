<?php
/**
 * @author Shane 
 * @copyright 2013
 * 
 * 
 */

class ELI_email  
{
    public $errors = array();
   
    private $props = array();
    private $body = array();
    private $_body = array();
    private $attachments = array();
    private $headers = array();
    private $data;
    private $data_split;
    static public $mencarr = array('7bit' => '', '8bit' => '', 'quoted-printable' => '', 'base64' => '', 'binary' => '');

    function setFile($filepath){
        $this->setData(file_get_contents($filepath));
    }
    function setData($data){
        $this->data = $data;
        $this->_decode();
    }
    function reduce(){
        $this->data = '';
        $this->data_split = array();
    }
    static public function split_message($str = null) {
		if (!(is_string($str) && $str != ''))$str='';
		
		$ret = false;
		if (strpos($str, "\r\n\r\n")) $ret = explode("\r\n\r\n", $str, 2);
		else if (strpos($str, "\n\n")) $ret = explode("\n\n", $str, 2);
		if ($ret) 
            return array('header' => trim($ret[0]), 'content' => $ret[1]);
		else 
            return false;
		
	}
    private function _decode(){
        
        $parts = self::split_message($this->data);
        if($parts)
            $this->data_split = $parts;
        else
            $this->data_split = array('header' => '', 'content' => '');
        
        #split header
        $this->headers = self::split_header($this->data_split['header']);
        $arr_unique = array('to','from','subject');
        $arr_props = array();
        foreach($this->headers as $item){
            $name2= $item['_name'];
            if(in_array($name2,$arr_unique) && !isset($this->props[$name2]))
                $this->props[$name2] = $item['value'];
            if(in_array($name2,$arr_props) && !isset($this->props[$name2]))
                $this->props[$name2] = $item['value'];   
        }
        $temp = $this->getHeaderValue('received');
        $i = strpos($temp,'for ');
        $i+=4;
        $iend = strpos($temp,';', $i);
        $this->props['for'] = substr($temp,$i,$iend-$i);
        /*if(isset($this->props['to'])){
            $this->props['_to'] = $this->props['to'];
            if (preg_match("/.*<(.*)>/", $this->props['to'], $match))
            {
                $this->props['to'] = $match[1];
            }
        }*/
        if(isset($this->props['from'])){
            $this->props['_from'] = $this->props['from'];
            if (preg_match("/.*<(.*)>/", $this->props['from'], $match))
            {
                $this->props['from'] = $match[1];
            }
        }
        
        $type = $boundary = '';
		if ($item = $this->getHeader('content-type')) {
			$type = strtolower($item['value']);
            
			foreach ($item['content'] as $hnam => $hval) {
				if (strtolower($hnam) == 'boundary') {
					$boundary = $hval;
					break;
				}
			}
		}
        $this->props['boundary'] = $boundary;
        $this->props['type'] = $type;
		
        #split content
        $str = $this->data_split['content'];
        $body=array();
        if (substr($type, 0, strlen('multipart/')) == 'multipart/' && $boundary && strstr($str, '--'.$boundary.'--')) 
            $body = self::_parts($str, $boundary, strtolower(substr($type, strlen('multipart/'))));
		if (count($body) == 0) 
            $body[] = self::_content($this->data);
        if($body)
            $this->body = $body;
        foreach($this->body as $k => $item){
            if(isset($item['disposition']) && $item['disposition']['value']=='attachment'){
                $aa = array();
                $aa['type'] = $item['type']['value'];
                $aa['name'] = isset($item['type']['extra']['name'])?$item['type']['extra']['name']:'';
                $aa['filename'] = isset($item['disposition']['extra']['filename'])?$item['disposition']['extra']['filename']:'';
                if($aa['filename'] == '' && $aa['name']!='') $aa['filename']=$aa['name'];
                if($aa['name'] == '' && $aa['filename']!='') $aa['name']=$aa['filename'];
                $aa['content'] = $item['content'];
                $aa['filename'] = self::mayDecode($aa['filename']);
                $aa['name'] = self::mayDecode($aa['name']);
                $this->attachments[] = $aa;
            }
        }
    }
    static private function mayDecode($txt){
        if (preg_match("/=\?utf-?8\?B\?(.*)\?=/i", $txt, $match)){
            return base64_decode($match[1]);
        }elseif (preg_match("/=\?utf-?8\?@\?(.*)\?=/i", $txt, $match)){
            return quoted_printable_decode($match[1]);
        }else{
            return $txt;
        }
    }
    static public function is_alpha($str = null, $num = true, $add = '') {
	
		
			if ($str != '') {
				$lst = 'abcdefghijklmnoqprstuvwxyzABCDEFGHIJKLMNOQPRSTUVWXYZ'.$add;
				if ($num) $lst .= '1234567890';
				$len1 = strlen($str);
				$len2 = strlen($lst);
				$match = true;
				for ($i = 0; $i < $len1; $i++) {
					$found = false;
					for ($j = 0; $j < $len2; $j++) {
						if ($lst{$j} == $str{$i}) {
							$found = true;
							break;
						}
					}
					if (!$found) {
						$match = false;
						break;
					}
				}
				return $match;
			} else return false;
		
	}
    static private function split_header($str){
        
        $str = str_replace(array(";\r\n\t", "; \r\n\t", ";\r\n ", "; \r\n "), '; ', $str);
		$str = str_replace(array(";\n\t", "; \n\t", ";\n ", "; \n "), '; ', $str);
		$str = str_replace(array("\r\n\t", "\r\n "), '', $str);
		$str = str_replace(array("\n\t", "\n "), '', $str);
		$arr = array();
		foreach (explode("\n", $str) as $line) {
			$line = trim(self::str_clear($line));
			if ($line != '') {
				if (count($exp1 = explode(':', $line, 2)) == 2) {
					$name = rtrim($exp1[0]);
					$val1 = ltrim($exp1[1]);
					if (strlen($name) > 1 && self::is_alpha($name, true, '-') && $val1 != '') {
						$name = ucfirst($name);
						$hadd = array();
						if (substr(strtolower($name), 0, 8) == 'content-') {
							$exp2 = explode('; ', $val1);
							$cnt2 = count($exp2);
							if ($cnt2 > 1) {
								for ($i = 1; $i < $cnt2; $i++) {
									if (count($exp3 = explode('=', $exp2[$i], 2)) == 2) {
										$hset = trim($exp3[0]);
										$hval = trim($exp3[1], ' "');
										if ($hset != '' && $hval != '') $hadd[strtolower($hset)] = $hval;
									}
								}
							}
						}
						$val2 = (count($hadd) > 0) ? trim($exp2[0]) : $val1;
                        $name2= strtolower($name);
						$arr[] = array('name' => $name,'_name'=>$name2, 'value' => $val2, 'content' => $hadd);
                         
					}
				}
			}
		}
        return $arr;
    }
    
    static private function _parts($str = null, $boundary = null, $multipart = null) {
		
		$err = array();
		if (!(is_string($str) && $str != '')) $err[] = 'invalid content value';
		if (!(is_string($boundary) && $boundary != '')) $err[] = 'invalid boundary value';
		if (!(is_string($multipart) && $multipart != '')) $err[] = 'invalid multipart value';
		if (count($err) > 0){
		  foreach($err as $item)
            $this->errors[] = $item;
		}
		else {
			$ret = array();
			if (count($exp = explode('--'.$boundary.'--', $str)) == 2) {
				if (count($exp = explode('--'.$boundary, $exp[0])) > 2) {
					$cnt = 0;
					foreach ($exp as $split) {
						$cnt++;
						if ($cnt > 1 && $part = self::split_message($split)) {
							if ($harr = self::split_header($part['header'])) {
								$type = $newb = false;
								foreach ($harr as $hnum) {
									if (strtolower($hnum['name']) == 'content-type') {
										$type = strtolower($hnum['value']);
										foreach ($hnum['content'] as $hnam => $hval) {
											if (strtolower($hnam) == 'boundary') {
												$newb = $hval;
												break;
											}
										}
										if ($newb) break;
									}
								}
								if (substr($type, 0, strlen('multipart/')) == 'multipart/' && $newb && strstr($part['content'], '--'.$newb.'--')) 
                                    $ret = self::_parts($part['content'], $newb, $multipart.'|'.strtolower(substr($type, strlen('multipart/'))));
								else {
									$res = self::_content($split);
									$res['multipart'] = $multipart;
									$ret[] = $res;
								}
							}
						}
					}
				}
			}
			return $ret;
		}
	}

	static private function _content($str = null ) {
		if (!(is_string($str) && $str != '')){
		      $this->errors[] = 'invalid content value';
		}
		else {
			if (!$part = self::split_message($str)) return null;
			if (!$harr = self::split_header($part['header'])) return null;
			$body = array();
			$clen = strlen('content-');
			$encoding = false;
			foreach ($harr as $hnum) {
				if (substr(strtolower($hnum['name']), 0, $clen) == 'content-') {
					$name = strtolower(substr($hnum['name'], $clen));
					if ($name == 'transfer-encoding') $encoding = strtolower($hnum['value']);
					else if ($name == 'id') $body[$name] = array('value' => trim($hnum['value'], '<>'), 'extra' => $hnum['content']);
					else $body[$name] = array('value' => $hnum['value'], 'extra' => $hnum['content']);
				}
			}
			if ($encoding == 'base64' || $encoding == 'quoted-printable') 
                $body['content'] = self::decode_content($part['content'], $encoding);
			else {
				if ($encoding) $body['transfer-encoding'] = $encoding;
				$body['content'] = $part['content'];
			}
			if (substr($body['content'], -2) == "\r\n") $body['content'] = substr($body['content'], 0, -2);
			else if (substr($body['content'], -1) == "\n") $body['content'] = substr($body['content'], 0, -1);
			return $body;
		}
	}
    static public function decode_content($str = null, $encoding = null ) {
        $err = array();
		if (!is_string($str)) $err[] = 'invalid content type';
		if ($encoding == null) $encoding = '7bit';
		else if (!is_string($encoding)) $err[] = 'invalid encoding type';
		else {
			$encoding = strtolower($encoding);
			if (!isset(self::$mencarr[$encoding])) $err[] = 'invalid encoding value';
		}
		if (count($err) > 0){
		  foreach($err as $item)
            $this->errors[] = $item;
		}
		else {
			if ($encoding == 'base64') {
				$str = trim(self::str_clear($str));
				return base64_decode($str);
			} else if ($encoding == 'quoted-printable') {
				return quoted_printable_decode($str);
			} else return $str;
		}
	}
    static public function str_clear($str = null, $addrep = null) {
		$err = array();
		$rep = array("\r", "\n", "\t");
		if (!is_string($str)) return '';
        
		if ($addrep == null) $addrep = array();
		if (is_array($addrep)) {
			if (count($addrep) > 0) {
				foreach ($addrep as $strrep) {
					if (is_string($strrep) && $strrep != '') $rep[] = $strrep;
					else {
						
						break;
					}
				}
			}
		}
		return ($str == '') ? '' : str_replace($rep, '', $str);
		
	}
    public function __toString() {
        return print_r($this,1);
    }
    public function bodyExists($type){
        return array_key_exists($type,$this->body);
    }
    public function headerExists($name){
        $name = strtolower($name);
        $r = array_key_exists($name,$this->headers);
        if($r) return true;
        foreach($this->headers as $item){
            if($name == $item['_name'])
                return true;
        }
        return false;
    }
    public function getContent($type=null){
        if(func_num_args()==0){
            $name = strtolower('content');
            if(array_key_exists($name,$this->data_split)){
                return $this->data_split[$name];
            }
            return '';
        }
        $type = strtolower($type);
        if($type=='text') $type='plain';
        
        if(array_key_exists($type,$this->_body)){
            return $this->_body[$type]['content'];
        }
        $name = 'text/' . $type;
        foreach($this->body as $k => $item){
            
            if($name == $item['type']['value']){
                $this->_body[$type] = &$this->body[$k];
                return $item['content'];
            }
        }
        
        
        
        if($type=='plain'){
            $fx = __FUNCTION__;
            return strip_tags($this->$fx('html'));
        }
        return '';
    }
    public function getBody($type=null){
        if(func_num_args()==0){
            $name = strtolower('content');
            if(array_key_exists($name,$this->data_split)){
                return $this->data_split[$name];
            }
            return '';
        }
        $type = strtolower($type);
        if($type=='text') $type='plain';
        
        if(array_key_exists($type,$this->_body)){
            return $this->_body[$type];
        }
        $name = 'text/' . $type;
        foreach($this->body as $k => $item){
            
            if($name == $item['type']['value']){
                $this->_body[$type] = &$this->body[$k];
            }
            return $item;
        }
        
        
        
        if($type=='plain'){
            $fx = __FUNCTION__;
            return strip_tags($this->$fx('html'));
        }
        return null;
    }
    
    public function getHeaderValue($name){
        $name = strtolower($name);
        if(array_key_exists($name,$this->headers)){
            return $this->headers[$name]['value'];
        }
        foreach($this->headers as $item){
            if($name == $item['_name'])
                return $item['value'];
        }
        return '';
    }
    public function getHeader($name=null){
        if(func_num_args()==0){
            $name = strtolower('header');
            if(array_key_exists($name,$this->data_split)){
                return $this->data_split[$name];
            }
            return '';
        }
        $name = strtolower($name);
        if(array_key_exists($name,$this->headers)){
            return $this->headers[$name];
        }
        foreach($this->headers as $item){
            if($name == $item['_name'])
                return $item;
        }
        return '';
    }
    public function __get($name) {
        $name = strtolower($name);
        $fx ='get'.$name;
        if(method_exists($this,$fx)){
            return $this->$fx();
        }
        if(method_exists($this,$name)){
            return $this->$name();
        }
        if($name=='plain' || $name=='text'){
            return $this->getContent('plain');
        }
        if($name=='html'){
            return $this->getContent('html');
        }
        if($name=='attachments'){
            return $this->attachments;
        }
        if(array_key_exists($name,$this->props)){
            return $this->props[$name];
        }/**/
        if(property_exists($this,$name)){
            return $this->$name;
        }
        return '';
    }

}
?>