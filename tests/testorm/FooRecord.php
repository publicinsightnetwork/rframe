<?php

class FooRecord {
    /* internal params */
    protected $id;
    protected $value;
    
    /* externel references */
    protected $bar_refs = array();
    protected $foo_refs = array();
    
    public function __construct($foo_id, $foo_value) {
        $this->id = $foo_id;
        $this->value = $foo_value;
    }
    
    public function get_id() {
        return $this->id;
    }
    
    public function get_value() {
        return $this->value;
    }

    public function set_value($val) {
        $this->value = $val;
    }

    public function add_bar(BarRecord $bar) {
        $this->bar_refs[] = $bar;
    }
    
    public function remove_bar(BarRecord $bar) {
        foreach ($this->bar_refs as $idx => $b) {
            if ($bar->get_id() == $b->get_id()) {
                array_splice($this->bar_refs, $idx, 1);
                unset($b);
            }
        }
    }

    public function get_bars() {
        return $this->bar_refs;
    }
    
    public function add_foo(FooRecord $foo) {
        $this->foo_refs[] = $foo;
    }
    
    public function get_foos() {
        return $this->foo_refs;
    }
    
}
