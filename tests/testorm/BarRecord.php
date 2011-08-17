<?php

class BarRecord {
    /* internal params */
    protected $id;
    protected $value;
    
    /* externel references */
    protected $foo_refs = array();
    
    public function __construct($bar_id, $bar_value) {
        $this->id = $bar_id;
        $this->value = $bar_value;
    }
    
    public function get_id() {
        return $this->id;
    }
    
    public function get_value() {
        return $this->value;
    }
    
    public function add_foo(FooRecord $foo) {
        $this->foo_refs[] = $foo;
    }
    
    public function get_foos() {
        return $this->foo_refs;
    }
    
}
