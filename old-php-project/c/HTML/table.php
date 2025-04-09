<?php

/**
 * @author Edwards
 * @copyright 2010
 * @version 20130501.1
 */

class HTML_caption extends HTML_element
{
    protected $tag = 'caption';
    public function add($element = '')
    {
        $this->append($element);
    }
}
class HTML_table_td extends HTML_element
{
    protected $tag = 'td';
    public function tr()
    {
        error_log('HTML_table_td::tr is DEPRECATED for HTML_table_td::parent()');
        return $this->parent();
    }
    public function add($element = '')
    {
        $this->append($element);
    }
}
class HTML_table_th extends HTML_table_td
{
    protected $tag = 'th';
}
class HTML_table_tr extends HTML_element
{
    protected $tag = 'tr';
    public function create($object)
    {
        $object = strtolower($object);
        if ($object == 'td' || $object == 'th') {
            return $this->addChild($object);
        } else {
            $el = $this->addChild('td');
            return $el->create($object);
        }
    }
    public function add($element = '')
    {
        if (func_num_args()) {
            if ($element instanceof HTML_table_td) {
                $this->nodes[] = $element;
                return $element;
            } else {
                return $this->addChild('td', $element);
            }
        }
        return $this->addChild('td');
    }
    public function append($innerHTML = '')
    {
        if ($n = func_num_args()) {
            if ($n > 1) {
                foreach (func_get_args() as $v) {
                    $this->append($v);
                }
            } elseif (is_array($element)) {
                foreach ($element as $v) {
                    $this->append($v);
                }
            } else {
                if ($element instanceof HTML_table_td) {
                    $this->nodes[] = $element;
                } else {
                    $this->addChild('td', $element);
                }
            }
        } else {
            $this->addChild('td');
        }
        return $this;
    }
    public function addChild($type, $innerHTML = '')
    {
        $type = strtolower($type);
        if ($type != 'th')
            $type = 'td';
        $c = 'HTML_table_' . $type;
        $el = new $c();
        $el->parent($this);
        if (func_num_args() > 1)
            $el->innerHtml($innerHTML);
        $this->nodes[] = $el;
        return $el;
    }
    public function addTh($innerHTML = null)
    {
        if (func_num_args() == 0) {
            return $this->addChild('th');
        } elseif (func_num_args() > 1) {
            $r = array();
            foreach (func_get_args() as $v)
                $r[] = $this->addChild('th', $v);
            return $r;
        } elseif (is_array($innerHTML)) {
            $r = array();
            foreach ($innerHTML as $v)
                $r[] = $this->addChild('th', $v);
            return $r;
        } else
            return $this->addChild('th', $innerHTML);
    }
    public function addTd($innerHTML = null)
    {
        if (func_num_args() == 0) {
            return $this->addChild('td');
        } elseif (func_num_args() > 1) {
            $r = array();
            foreach (func_get_args() as $v)
                $r[] = $this->addChild('td', $v);
            return $r;
        } elseif (is_array($innerHTML)) {
            $r = array();
            foreach ($innerHTML as $v)
                $r[] = $this->addChild('td', $v);
            return $r;
        } else
            return $this->addChild('td', $innerHTML);
        return $this;
    }
    public function cells()
    {
        return $this->nodes;
    }
}
abstract class HTML_table_section extends HTML_element
{
    public function table()
    {
        return $this->parent;
    }
    public function create($object)
    {
        $object = strtolower($object);
        if ($object == 'tr') {
            return $this->addTr();
        } else {
            $el = $this->addTr();
            return $el->create($object);
        }
    }
    public function add($element = '')
    {
        if (func_num_args()) {
            if ($element instanceof HTML_table_tr) {
                $this->nodes[] = $element;
                return $element;
            } else {
                $tr = $this->addTr();
                $tr->add($element);
                return $tr;
            }
        }
        return $this->addTr();
    }
    public function append($element = '')
    {
        if ($n = func_num_args()) {
            if ($n > 1) {
                foreach (func_get_args() as $v) {
                    $this->append($v);
                }
            } elseif (is_array($element)) {
                foreach ($element as $v) {
                    $this->append($v);
                }
            } else {
                if ($element instanceof HTML_table_tr) {
                    $this->nodes[] = $element;
                } else {
                    $tr = $this->addTr();
                    $tr->append($element);
                }
            }
        }
        return $this;
    }
    public function addRow()
    {
        return $this->addTr();
    }
    public function tr()
    {
        return $this->addTr();
    }
    public function addTr()
    {
        $el = new HTML_table_tr();
        $el->parent($this);
        $this->nodes[] = $el;
        return $el;
    }
    public function __isset($name)
    {
        return count($this->nodes);
    }
    public function rows()
    {
        return count($this->nodes);
    }
    public function innerHTML($value = null)
    {
        $r = array();
        foreach ($this->nodes as $tr)
            $r[] = (string )$tr;
        return implode("\n", $r);
    }
    public function __toString()
    {
        $r = array();
        $ih = $this->innerHTML();
        $r[] = $this->getOpenTag();
        $r[] = $ih;
        $r[] = $this->getCloseTag();
        return implode('', $r);
    }
}
class HTML_table_thead extends HTML_table_section
{
    protected $tag = 'thead';
}
class HTML_table_tbody extends HTML_table_section
{
    protected $tag = 'tbody';
}
class HTML_table_tfoot extends HTML_table_section
{
    protected $tag = 'tfoot';
}
class HTML_col extends HTML_element
{
    protected $tag = 'col';
}
class HTML_colgroup extends HTML_element
{
    protected $tag = 'colgroup';
    public function table()
    {
        return $this->parent();
    }
    public function add($element = '')
    {
        if (func_num_args()) {
            if ($element instanceof HTML_col) {
                $this->nodes[] = $element;
                return $element;
            } else {
                $tr = $this->addCol();
                $tr->append($element);
                return $tr;
            }
        }
        return $this->addCol();
    }
    public function addCol()
    {
        $el = new HTML_col;
        $this->nodes[] = $el;
        $el->parent($this);
        return $el;
    }
    public function create($object)
    {
        $object = strtolower($object);
        if ($object != 'col') {
            trigger_error('COLGROUP can only contain COL elements');
            return $this->addCol();
        }
        return $this->addCol();
    }
}
class HTML_table extends HTML_element
{
    protected $tag = 'table';
    private $caption = null;
    private $colgroup = null;
    private $colgroups = array();
    private $thead = null;
    private $tbody = null;
    private $tbodies = array();
    private $tfoot = null;

    public function add($element = '')
    {
        if (func_num_args()) {
            if (($element instanceof HTML_table_tr) || ($element instanceof HTML_table_td)) {
                return $this->tbody()->add($element);
            } elseif ($element instanceof HTML_caption) {
                $this->caption($element);
                return $element;
            } elseif ($element instanceof HTML_colgroup) {
                $this->colgroup = $element;
                return $element;
            } elseif ($element instanceof HTML_table_tbody) {
                $this->tbodies[] = $element;
                $this->tbody = $element;
                return $element;
            } elseif ($element instanceof HTML_table_thead) {
                $this->thead = $element;
                return $element;
            } elseif ($element instanceof HTML_table_tfoot) {
                $this->thead = $element;
                return $element;
            } else {
                $tr = $this->addTr();
                $tr->add($element);
                return $tr;
            }
        }
        return $this->addTr();
    }
    public function colGroup()
    {
        if ((null === $this->colgroup)) {
            $this->createColGroup();
        }
        return $this->colgroup;
    }
    public function createColGroup()
    {
        $value = new HTML_colgroup();
        $value->parent($this);
        $this->colgroups[] = $value;
        $this->colgroup = $value;
        return $value;
    }
    public function addColGroup()
    {
        return $this->createColGroup();
    }
    public function createTHead()
    {
        if (null === $this->thead) {
            $this->thead = new HTML_table_thead;
            $this->thead->parent($this);
        }
        return $this->thead;
    }
    public function deleteTHead()
    {
        $this->thead = null;
        return $this;
    }

    public function createTFoot()
    {
        if (null === $this->tfoot) {
            $this->tfoot = new HTML_table_tfoot;
            $this->tfoot->parent($this);
        }
        return $this->tfoot;
    }
    public function deleteTFoot()
    {
        $this->tfoot = null;
        return $this;
    }
    public function thead()
    {
        $key = __function__;
        if ((null === $this->thead)) {
            $this->thead = new HTML_table_thead();
            $this->thead->parent($this);
        }
        return $this->$key;
    }
    public function tbody()
    {
        $key = __function__;
        if ((null === $this->tbody))
            $this->addTBody();
        return $this->$key;
    }
    public function addTBody()
    {
        $value = new HTML_table_tbody();
        $value->parent($this);
        $this->tbodies[] = $value;
        $this->tbody = $value;
        return $value;
    }
    public function createTBody()
    {
        $value = new HTML_table_tbody();
        $value->parent($this);
        $this->tbodies[] = $value;
        $this->tbody = $value;
        return $value;
    }
    public function tbodies()
    {
        return $this->tbodies;
    }
    public function tfoot()
    {
        $key = __function__;
        if ((null === $this->tfoot)) {
            $this->tfoot = new HTML_table_tfoot();
            $this->tfoot->parent($this);
        }
        return $this->$key;
    }
    public function create($object)
    {
        $object = strtolower($object);
        if (in_array($object, array(
            'caption',
            'colgroup',
            'tfoot',
            'thead',
            'tr'))) {
            return $this->$object();
        } elseif ($object = 'tbody') {
            return $this->addTBody();
        } else {
            return parent::create($object);
        }
    }
    public function createCaption()
    {
        if (null === $this->caption) {
            $this->caption = new HTML_caption;
            $this->caption->parent($this);
        }
        return $this->caption;
    }
    public function deleteCaption()
    {
        $this->caption = null;
        return $this;
    }
    public function caption($value = null)
    {
        if (func_num_args()) {
            if ((null === $value)) {
                $this->caption = null;
                return $this;
            } elseif (is_scalar($value)) {
                if (!($this->caption instanceof HTML_element))
                    $this->caption = new HTML_caption;
                $this->caption->innerHTML($value);
                $this->caption->parent($this);
            } else {
                if (($this->caption instanceof HTML_caption))
                    $this->caption = $value;
                else {
                    if (!($this->caption instanceof HTML_caption))
                        $this->caption = new HTML_caption;
                    $this->caption->innerHTML($value);
                }

                if ($this->caption instanceof HTML_element)
                    $this->caption->parent($this);
            }
            return $this->caption;
        } else {
            if ((null === $this->caption))
                $this->caption = new HTML_caption($this);
            return $this->caption;
        }
    }
    public function addTr()
    {
        return $this->tbody()->addTr();
    }
    public function addRow()
    {
        return $this->addTr();
    }
    public function tr()
    {
        return $this->addTr();
    }

    public function rows()
    {
        $r = array();
        if (!empty($this->thead))
            $r += $this->thead->rows();
        foreach ($this->tbodies as $tb)
            $r += $tb->rows();
        if (!empty($this->tfoot))
            $r += $this->tfoot->rows();
        return $r;
    }
    public function innerHTML($value = null)
    {
        $r = array();
        if (!empty($this->caption))
            $r[] = (string )$this->caption;
        if (!empty($this->colgroup))
            $r[] = (string )$this->colgroup;
        if (!empty($this->thead))
            $r[] = (string )$this->thead;
        foreach ($this->tbodies as $tb)
            $r[] = (string )$tb;
        if (!empty($this->tfoot))
            $r[] = (string )$this->tfoot;
        return implode("\n", $r);
    }
    public function __toString()
    {
        $r = array();
        $ih = $this->innerHTML();
        $r[] = $this->getOpenTag();
        $r[] = $ih;
        $r[] = $this->getCloseTag();
        return implode('', $r);
    }
}
class HTML_td extends HTML_table_td
{
}
class HTML_th extends HTML_table_th
{
}
class HTML_tr extends HTML_table_tr
{
}
class HTML_tbody extends HTML_table_tbody
{
}
class HTML_thead extends HTML_table_thead
{
}
class HTML_tfoot extends HTML_table_tfoot
{
}
