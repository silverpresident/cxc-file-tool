<?php

/**
 * @author Edwards
 * @copyright 2012
 * @version 20161026
 */

class HTML_element
{
    protected $tag = 'div';
    protected $attr = array();
    protected $nodes = array();
    protected $parent = null;
    protected $_join_with = '';
    protected $_inner_join_with = '';
    public function __construct()
    {
        if (func_num_args() && func_get_arg(0)) {
            $param = func_get_arg(0);
            if ($param instanceof self)
                $this->parent = $param;
            elseif (is_scalar($param)) {
                if (method_exists($this, 'name')) {
                    $this->name($param);
                } else {
                    $this->id($param);
                }
            }
            //a second element should be the innerHtml
        }
    }


    public function html()
    {
        return $this->__toString();
    }

    public function __toString()
    {
        $r = array();
        if ($this->isVoidElement()) {
            $r[] = $this->getOpenTag();
        } else {
            $r[] = $this->getOpenTag();
            $r[] = $this->innerHTML();
            $r[] = $this->getCloseTag();
        }

        return implode($this->_join_with, $r);
    }
    public function parent()
    {
        if (func_num_args()) {
            if (func_get_arg(0) instanceof self)
                $this->parent = func_get_arg(0);
            return $this;
        } else {
            $key = __function__;
            return $this->$key;
        }
    }

    public function __call($name, $arguments)
    {
        if (strtolower($name) == 'empty')
            return $this->reset();
        if (count($arguments) == 0)
            return static::attr($name);
        else
            return static::attr($name, $arguments[0]);
    }

    public function data($key = '', $value = null)
    {
        $n = func_num_args();
        if ($n == 0) {
            $d = array();
            foreach ($this->attr as $k => $v) {
                if (strtolower(substr($k, 0, 5)) == 'data-') {
                    $d[substr($k, 5)] = &$this->attr[$k];
                }
            }
            return $d;
        } elseif ($n == 2) {
            $key = "data-{$key}";
            if ((null === $value) || (is_bool($value) && $value == false))
                unset($this->attr[$key]);
            else {
                $this->attr[$key] = $value;
            }

            return $this;
        } elseif ($n == 1) {
            if (is_array($key)) {
                foreach ($key as $k => $value) {
                    if (is_bool($value)) {
                        $value = $value ? 'true' : 'false';
                    }
                    $this->data($k, $value);
                }
                return $this;
            }
            $key = "data-{$key}";
            if (isset($this->attr[$key]))
                return $this->attr[$key];
            else
                return null;
        }
    }
    public function aria($key = '', $value = null)
    {
        $n = func_num_args();
        if ($n == 0) {
            $d = array();
            foreach ($this->attr as $k => $v) {
                if (strtolower(substr($k, 0, 5)) == 'aria-') {
                    $d[substr($k, 5)] = &$this->attr[$k];
                }
            }
            return $d;
        } elseif ($n == 2) {
            $key = "aria-{$key}";
            if ((null === $value) || (is_bool($value) && $value == false))
                unset($this->attr[$key]);
            else {
                $this->attr[$key] = $value;
            }

            return $this;
        } elseif ($n == 1) {
            if (is_array($key)) {
                foreach ($key as $k => $v) {
                    $this->aria($k, $v);
                }
                return $this;
            }
            $key = "aria-{$key}";
            if (isset($this->attr[$key]))
                return $this->attr[$key];
            else
                return null;
        }
    }

    public function attr($key = '', $value = null)
    {
        $n = func_num_args();
        if ($n == 0) {
            return $this->attr;
        } elseif ($n == 2) {
            if ((null === $value) || (is_bool($value) && $value == false))
                unset($this->attr[$key]);
            else {

                $this->attr[$key] = $value;
            }

            return $this;
        } elseif ($n == 1) {
            if (is_array($key)) {
                foreach ($key as $k => $v) {
                    $this->attr($k, $v);
                }
                return $this;
            }
            if (isset($this->attr[$key]))
                return $this->attr[$key];
            else
                return null;
        }
    }
    public function _BOOTSTRAP($object)
    {
        include_once ('_BOOTSTRAP.php');
        $value = _BOOTSTRAP::build($object);
        $this->nodes[] = $value;
        return $value;
    }
    public function _STYLESHEET($href = '')
    {
        $value = HTML::build('link');
        $value->rel('stylesheet');
        if (func_num_args())
            $value->href($href);
        $this->nodes[] = $value;
        return $value;
    }
    public function input($object, $name = '')
    {
        $el = HTML::input($object, $name);
        if (func_num_args() > 2) {
            $value = func_get_arg(2);
            $el->value($value);
        }
        if (get_class($this) != __class__) {
            $el->parent($this);
        }
        $this->nodes[] = $el;
        return $el;
    }
    public function create($object)
    {
        $el = HTML::build($object);
        $n = func_num_args();
        if ($n > 1) {
            $name = func_get_arg(1);
            if ($el instanceof HTML_element_nameable) {
                $el->name($name);
            } else {
                $el->id($name);
            }
        }
        if ($n > 2) {
            $value = func_get_arg(2);
            if ($el instanceof HTML_input) {
                $el->value($value);
            } else {
                $el->innerHtml($value);
            }
        }
        if (get_class($this) != __class__) {
            $el->parent($this);
        }
        $this->nodes[] = $el;
        return $el;
    }
    public function createFragment()
    {
        if (func_num_args() == 1) {
            $el = HTML::buildFragment(func_get_arg(0));
        } else {
            $el = HTML::buildFragment();
        }
        $this->nodes[] = $el;
        return $el;
    }

    public function createHidden($name, $value = '')
    {
        $el = HTML::input('hidden', $name);
        $el->parent($this);
        $el->value($value);
        $this->nodes[] = $el;
        return $el;
    }
    /**
     * HTML_element::add()
     *  add a decendent child. 
     *      - if the $node is a HTML element it will be added
     *      - if the $node is scalar a proper decendent will be created with $node as the inner text
     *      - by default decendent the child will be the type of the parent element except where the parent has a defined childType e.g. UL, OL
     * @param mixed $node
     * @return void
     */
    public function add($node = '')
    {
        $tag = $this->tagName();
        if ($tag == 'ul' || $tag = 'ol') {
            if (is_array($node)) {
                $el = array();
                foreach ($node as $item) {
                    $el[] = $this->add($item);
                }
                return $el;
            } elseif ($node instanceof HTML_element) {
                $el = $node;
            } else {
                $el = HTML::build('li');
                $el->append($node);
            }
            $this->nodes[] = $el;
            return $el;
        }

        if (func_num_args()) {
            if ($node instanceof HTML_element) {
                $el = $node;
            } else {
                $c = get_called_class();
                $el = new $c();
                $el->append($node);
            }
        } else {
            $c = get_called_class();
            $el = new $c();
        }
        $this->nodes[] = $el;
        return $el;
    }

    private function tagName()
    {
        return strtolower($this->tag);
    }

    public function prepend($value = '')
    {
        if ($n = func_num_args()) {
            if ($n > 1) {
                foreach (func_get_args() as $v)
                    $this->prepend($v);
            } elseif (is_array($value)) {
                $value = array_reverse($value);
                foreach ($value as $v) {
                    $this->prepend($v);
                }
            } else {
                if (strlen($value) && (trim($value) == '')) {
                    $value = str_replace(' ', '&nbsp;', $value);
                }
                array_unshift($this->nodes, $value);
            }
        }
        return $this;
    }
    public function append($value = '')
    {
        if ($n = func_num_args()) {
            if ($n > 1) {
                foreach (func_get_args() as $v) {
                    $this->append($v);
                }
            } elseif (is_array($value)) {
                foreach ($value as $v) {
                    $this->append($v);
                }
            } elseif ($value instanceof HTML_element) {
                $this->nodes[] = $value;
            } else {
                if (strlen($value) && (trim($value) == '')) {
                    $value = str_replace(' ', '&nbsp;', $value);
                }
                $this->nodes[] = $value;
            }
        }
        return $this;
    }
    public function delete($node)
    {
        if (is_numeric($node)) {
            if ($node == -1)
                $node = count($this->nodes) - 1;
            if (isset($this->nodes[$node])) {
                unset($this->nodes[$node]);
            }
        } else {
            foreach ($this->nodes as $i => $item) {
                if ($node === $item) {
                    unset($this->nodes[$i]);
                }
            }
        }
        return $this;
    }

    public function node($nodeIndex = 0)
    {
        if (func_num_args() == 0) {
            return $this->nodes;
        } else {
            return $this->nodes[$nodeIndex];
        }
    }
    public function nodes()
    {
        if (func_num_args() == 0) {
            return $this->nodes;
        } else {
            $nodeIndex = func_get_arg(0);
            return $this->nodes[$nodeIndex];
        }
    }
    public function reset()
    {
        $this->nodes = array();
        return $this;
    }

    public function innerHTML($value = null)
    {
        if (func_num_args() == 0) {
            $r = array();
            foreach ($this->nodes as $i)
                $r[] = (string )$i;
            return trim(implode($this->_inner_join_with, $r));
        }
        if (is_array($value))
            $this->nodes = $value;
        else
            $this->nodes = array($value);
        return $this;
    }
    public function addClass($class = '')
    {
        if (func_num_args()) {
            $a = func_get_args();
            $a = array_filter($a);

            if (empty($this->attr['class'])) {
                $r = $a;
            } else {
                $r = array_merge(array($this->attr['class']), $a);
            }
            $r = explode(' ', implode(' ', $r));
            $r = array_filter(array_unique($r));
            $this->attr['class'] = implode(' ', $r);
        } elseif (!isset($this->attr['class'])) {
            $this->attr['class'] = '';
        }
        return $this;
    }

    public function setClass($class = '', $replace = false)
    {
        if ($replace) {
            return $this->attr('class', $class);
        } else {
            return $this->addClass($class);
        }
    }
    public function removeClass($class = '')
    {
        if (func_num_args()) {
            if (!empty($this->attr['class'])) {
                $r = explode(' ', $this->attr['class']);
                $a = func_get_args();
                $a = explode(' ', implode(' ', $a));
                $a = array_filter($a);

                $r = array_diff($r, $a);
                $r = array_unique($r);
                $this->attr['class'] = implode(' ', $r);
            }
        } else {
            unset($this->attr['class']);
        }
        return $this;
    }
    private static function parseStyle($value)
    {

        $results = array();
        $value = trim($value);
        $value = trim($value, ';');
        if (empty($value))
            return $results;

        if (stripos($value, 'url(') !== false) {
            $s = explode(';', $value);
            $l = -1;
            foreach ($s as $i => $v) {
                if (strpos($v, ':') == false) {
                    if ($l > -1) {
                        $s[$l] = $s[$l] . $v;
                        $s[$i] = '';
                    }
                } else {
                    $l = $i;
                }
                $s[$i] = trim($s[$i]);
            }
            $s = array_filter($s);
            foreach ($s as $i => $v) {
                if (strpos($v, ':')) {
                    list($p, $v) = explode(':', $v, 2);
                    $p = strtolower($p);
                    $results[$p] = trim($v);
                }
            }
        } else {
            preg_match_all("/([\w-]+)\s*:\s*([^;]+)\s*;?/", $value, $matches, PREG_SET_ORDER);
            foreach ($matches as $match) {
                $results[strtolower($match[1])] = $match[2];
            }
        }
        return $results;


        /* does not work with
        background-image: url(/example.jpeg?arg=1;arg=2);
        TEST string:
        
        color:#777;background-image: url(/example.jpeg?arg=1;arg=2);font-size:16px;font-weight:bold;left:214px  ; position:relative; top:   70px
        */

    }
    public function addStyle($value = null)
    {
        if (func_num_args()) {
            if (empty($this->attr['style'])) {
                $c = array();
            } else {
                $c = self::parseStyle($this->attr['style']);
            }
            $a = func_get_args();
            if (count($a) == 1) {
                if (is_array($value))
                    $value = implode(';', $value);
            } else {
                $a = array_filter($a);
                $value = implode(';', $a);
            }

            $n = self::parseStyle($value);
            $a = array_merge($c, $n);

            $r = array();
            foreach ($a as $k => $v)
                if (!empty($v))
                    $r[] = "$k: $v";

            $this->attr['style'] = implode('; ', $r);

        } elseif (!isset($this->attr['style'])) {
            $this->attr['style'] = '';
        }
        return $this;
    }
    public function show()
    {
        $this->style('display', '');
        return $this;
    }
    public function hide()
    {
        $this->style('display', 'none');
        return $this;
    }
    public function style($value = null)
    {
        $n = func_num_args();
        if ($n == 0) {
            return $this->attr('style');
        } else {
            $sa = array();
            if (!empty($this->attr['style'])) {
                $s = explode(';', $this->attr['style']);
                $s = array_map('trim', $s);
                $s = array_filter($s);
            } else
                $s = array();

            foreach ($s as $k => $v) {
                if (strpos($v, ':')) {
                    list($p, $v) = explode(':', $v, 2);
                    $p = strtolower($p);
                    $v = trim($v);
                } else
                    $v = $p = '';
                if (!empty($p) && !empty($v))
                    $sa[$p] = $v;
            }
            if ($n == 1) {
                $s = explode(';', $value);
                $s = array_map('trim', $s);
                $s = array_filter($s);
                foreach ($s as $k => $v) {
                    if (strpos($v, ':')) {
                        list($p, $v) = explode(':', $v, 2);
                        $p = strtolower($p);
                        $v = trim($v);
                    } else {
                        $v = $p = '';
                    }
                    if (!empty($p) && !empty($v))
                        $sa[$p] = $v;
                }
            } else {
                $value = strtolower($value);
                $sa[$value] = func_get_arg(1);
            }
            $r = array();
            foreach ($sa as $k => $v)
                if (!empty($v))
                    $r[] = "$k: $v";
            return $this->attr('style', implode(';', $r));
        }
    }
    public function getAttributes()
    {
        $a = $this->attr;
        if (func_num_args()) {
            $r = func_get_arg(0);
            if (is_array($r)) {
                $a = array_merge($this->attr, $r);
            }
        }
        $r = array();
        foreach ($a as $k => $v) {
            if (is_bool($v)) {
                if ($v)
                    $r[] = $k;
            } elseif (is_array($v)){
                foreach($v as $k1=>$v1){
                    $v1 = htmlspecialchars("{$v1}", ENT_QUOTES, null, false);
                    $r[] = "{$k}-{$k1}='$v1'";
                }
            }else {
                $v = htmlspecialchars("{$v}", ENT_QUOTES, null, false);
                $r[] = "$k='$v'";
            }
        }
        return trim(implode(' ', $r));
    }
    public function getOpenTag()
    {
        $a = $this->getAttributes();
        if (empty($a))
            return "<{$this->tag}>";

        $r[] = "<{$this->tag}";
        $r[] = $a . '>';
        return implode(' ', $r);
    }
    public function getCloseTag()
    {
        if ($this->isVoidElement())
            return '';
        return "</{$this->tag}>";
    }
    public function isEmpty()
    {
        return $this->innerHTML() == '';
    }

    protected function isVoidElement()
    {
        return in_array($this->tag, array(
            "area",
            "base",
             /*"basefont",*/
            "br",
            "col",
             /*"command",*/
            "embed",
             /* "frame",*/
            "hr",
            "img",
            "input",
            "keygen",
            "link",
            "meta",
            "param",
            "source",
            "track",
            "wbr"));
    }
}
/*
trait HTML_hyperlink_trait
{
public function target($value='_blank')
{
$l=strtoupper($value);
if(in_array($l,array('_blank','_self','_parent','_top')))
$this->attr(__FUNCTION__,$l);
else
$this->attr(__FUNCTION__,$value);
}
}*/
class HTML_element_generic extends HTML_element
{
    public function __construct()
    {
        if (func_num_args() && func_get_arg(0)) {
            $param = func_get_arg(0);
            if ($param instanceof HTML_element)
                $this->parent = $param;
            elseif (is_scalar($param)) {
                $this->tag = $param;
            }
        }
    }
}
class HTML_element_nameable extends HTML_element
{

    public function name($value = null, $setID = false)
    {
        if (func_num_args() == 0)
            return $this->attr(__function__ );
        else {
            if (func_num_args() > 1) {
                $id = $setID;
                if (is_bool($setID) && $setID)
                    $id = $value;
                elseif (is_string($setID)) {
                    if ($setID != '')
                        $id = $setID;
                    $setID = true;
                } else
                    $id = $setID ? $value : '';
                if ($setID) {
                    $this->id($id);
                }
            }
            return $this->attr(__function__, $value);
        }
    }
}
